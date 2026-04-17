/**
 * TextTune AI — Media library vision integration.
 *
 * - Adds "Bild analysieren" to attachment details + media row + bulk action.
 * - Fetches a suggestion from the REST endpoint, presents a diff dialog,
 *   then persists the user's decision server-side.
 * - Runs a sequential progress loop for bulk selection.
 */
(function () {
    'use strict';

    var data = window.texttuneMediaData;
    if (!data || !data.restUrl) {
        return;
    }

    var FIELDS = ['alt', 'title', 'caption', 'description'];
    var enabledFields = Array.isArray(data.enabledFields) && data.enabledFields.length
        ? data.enabledFields
        : FIELDS.slice();

    /* -------- REST helper -------- */

    function request(body) {
        if (window.wp && window.wp.apiFetch) {
            return window.wp.apiFetch({
                url: data.restUrl,
                method: 'POST',
                data: body
            });
        }
        // Fallback: fetch with nonce.
        return fetch(data.restUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': data.nonce
            },
            body: JSON.stringify(body)
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok) {
                    var err = new Error(json && json.message ? json.message : 'HTTP ' + res.status);
                    err.code = json && json.code ? json.code : null;
                    err.responseJSON = json;
                    throw err;
                }
                return json;
            });
        });
    }

    /* -------- DOM helpers -------- */

    function findFieldInput(attachmentId, field) {
        // Media-modal selectors: <textarea id="attachments-123-alt">, -title, -caption, -description.
        var id = 'attachments-' + attachmentId + '-' + field;
        var el = document.getElementById(id);
        if (el) return el;

        // Compat for attachment.php / post.php edit screen.
        if (field === 'alt') {
            el = document.getElementById('attachment_alt');
            if (el) return el;
        }
        if (field === 'title') {
            el = document.getElementById('title');
            if (el) return el;
        }
        if (field === 'caption') {
            el = document.getElementById('attachment_caption');
            if (el) return el;
        }
        if (field === 'description') {
            el = document.getElementById('attachment_content');
            if (el) return el;
        }
        return null;
    }

    function readCurrentFromDom(attachmentId) {
        var out = {};
        FIELDS.forEach(function (f) {
            var input = findFieldInput(attachmentId, f);
            out[f] = input ? String(input.value || '') : null;
        });
        return out;
    }

    function writeFieldToDom(attachmentId, field, value) {
        var input = findFieldInput(attachmentId, field);
        if (!input) return false;
        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    }

    function setStatus(btn, text, cls) {
        if (!btn) return;
        var wrapper = btn.parentNode;
        if (!wrapper) return;
        var status = wrapper.querySelector('.texttune-analyze-status');
        if (!status) return;
        status.textContent = text || '';
        status.className = 'texttune-analyze-status' + (cls ? ' ' + cls : '');
    }

    /* -------- Modal (dialog) -------- */

    var activeModal = null;

    function closeModal() {
        if (activeModal && activeModal.parentNode) {
            activeModal.parentNode.removeChild(activeModal);
        }
        activeModal = null;
    }

    function buildModal(titleText) {
        closeModal();
        var overlay = document.createElement('div');
        overlay.className = 'texttune-modal-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');

        var dialog = document.createElement('div');
        dialog.className = 'texttune-modal';

        var header = document.createElement('div');
        header.className = 'texttune-modal-header';
        var h2 = document.createElement('h2');
        h2.textContent = titleText;
        header.appendChild(h2);

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'texttune-modal-close';
        closeBtn.setAttribute('aria-label', data.i18n.close);
        closeBtn.textContent = '\u00d7';
        closeBtn.addEventListener('click', closeModal);
        header.appendChild(closeBtn);

        var body = document.createElement('div');
        body.className = 'texttune-modal-body';

        var footer = document.createElement('div');
        footer.className = 'texttune-modal-footer';

        dialog.appendChild(header);
        dialog.appendChild(body);
        dialog.appendChild(footer);
        overlay.appendChild(dialog);

        overlay.addEventListener('click', function (ev) {
            if (ev.target === overlay) closeModal();
        });
        document.addEventListener('keydown', function esc(ev) {
            if (ev.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', esc);
            }
        });

        document.body.appendChild(overlay);
        activeModal = overlay;
        return { overlay: overlay, body: body, footer: footer };
    }

    /**
     * Show the per-field decision dialog.
     *
     * @param {number}   attachmentId
     * @param {Object}   current    { field => string|null }
     * @param {Object}   generated  { field => string }
     * @param {Function} onApply    (overwriteMap) => Promise<Object>
     * @param {Function} [onCancel]
     */
    function showDecisionDialog(attachmentId, current, generated, onApply, onCancel) {
        var hasAnyConflict = false;
        enabledFields.forEach(function (f) {
            var cur = current[f];
            if (cur !== null && cur !== undefined && String(cur).trim() !== '' && generated[f]) {
                hasAnyConflict = true;
            }
        });

        var modal = buildModal(data.i18n.dialogTitle);
        var intro = document.createElement('p');
        intro.textContent = hasAnyConflict ? data.i18n.dialogIntroConflict : data.i18n.dialogIntroEmpty;
        modal.body.appendChild(intro);

        var allActionsWrap = document.createElement('div');
        allActionsWrap.className = 'texttune-modal-all-actions';

        var allOvw = document.createElement('button');
        allOvw.type = 'button';
        allOvw.className = 'button';
        allOvw.textContent = data.i18n.allOverwrite;

        var allKeep = document.createElement('button');
        allKeep.type = 'button';
        allKeep.className = 'button';
        allKeep.textContent = data.i18n.allKeep;
        allActionsWrap.appendChild(allOvw);
        allActionsWrap.appendChild(allKeep);
        modal.body.appendChild(allActionsWrap);

        var table = document.createElement('table');
        table.className = 'texttune-modal-table widefat striped';
        var thead = document.createElement('thead');
        var thr = document.createElement('tr');
        [data.i18n.colField, data.i18n.colCurrent, data.i18n.colGenerated, data.i18n.colAction].forEach(function (t) {
            var th = document.createElement('th');
            th.textContent = t;
            thr.appendChild(th);
        });
        thead.appendChild(thr);
        table.appendChild(thead);

        var tbody = document.createElement('tbody');
        var radios = {};

        enabledFields.forEach(function (field) {
            if (!generated[field]) return;
            var tr = document.createElement('tr');

            var tdField = document.createElement('td');
            tdField.textContent = data.fieldLabels[field] || field;
            tr.appendChild(tdField);

            var tdCur = document.createElement('td');
            tdCur.className = 'texttune-cell-current';
            var curVal = current[field];
            if (curVal === null || curVal === undefined || String(curVal).trim() === '') {
                var em = document.createElement('em');
                em.textContent = data.i18n.empty;
                tdCur.appendChild(em);
            } else {
                tdCur.textContent = String(curVal);
            }
            tr.appendChild(tdCur);

            var tdGen = document.createElement('td');
            tdGen.className = 'texttune-cell-generated';
            tdGen.textContent = generated[field];
            tr.appendChild(tdGen);

            var tdAct = document.createElement('td');
            tdAct.className = 'texttune-cell-action';
            var isEmpty = curVal === null || curVal === undefined || String(curVal).trim() === '';
            var defaultVal = isEmpty ? 'overwrite' : 'keep';

            ['overwrite', 'keep'].forEach(function (value) {
                var id = 'ttai-' + field + '-' + value;
                var label = document.createElement('label');
                label.style.marginRight = '8px';
                var r = document.createElement('input');
                r.type = 'radio';
                r.name = 'ttai-act-' + field;
                r.value = value;
                r.id = id;
                if (value === defaultVal) r.checked = true;
                label.appendChild(r);
                label.appendChild(document.createTextNode(
                    ' ' + (value === 'overwrite' ? data.i18n.actionOverwrite : data.i18n.actionKeep)
                ));
                tdAct.appendChild(label);
                if (!radios[field]) radios[field] = [];
                radios[field].push(r);
            });
            tr.appendChild(tdAct);
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        modal.body.appendChild(table);

        function setAll(value) {
            Object.keys(radios).forEach(function (field) {
                radios[field].forEach(function (r) {
                    r.checked = (r.value === value);
                });
            });
        }
        allOvw.addEventListener('click', function () { setAll('overwrite'); });
        allKeep.addEventListener('click', function () { setAll('keep'); });

        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'button';
        cancelBtn.textContent = data.i18n.cancel;
        cancelBtn.addEventListener('click', function () {
            closeModal();
            if (onCancel) onCancel();
        });

        var applyBtn = document.createElement('button');
        applyBtn.type = 'button';
        applyBtn.className = 'button button-primary';
        applyBtn.textContent = data.i18n.apply;
        applyBtn.addEventListener('click', function () {
            var map = {};
            Object.keys(radios).forEach(function (field) {
                var chosen = radios[field].find(function (r) { return r.checked; });
                map[field] = chosen ? chosen.value : 'keep';
            });
            applyBtn.disabled = true;
            cancelBtn.disabled = true;
            applyBtn.textContent = data.i18n.saving;
            Promise.resolve(onApply(map)).then(function () {
                closeModal();
            }).catch(function (err) {
                applyBtn.disabled = false;
                cancelBtn.disabled = false;
                applyBtn.textContent = data.i18n.apply;
                showError(modal.body, err);
            });
        });

        modal.footer.appendChild(cancelBtn);
        modal.footer.appendChild(applyBtn);
    }

    function showError(container, err) {
        var existing = container.querySelector('.texttune-modal-error');
        if (existing) existing.parentNode.removeChild(existing);
        var box = document.createElement('div');
        box.className = 'notice notice-error texttune-modal-error';
        var p = document.createElement('p');
        var msg = (err && err.message) ? err.message : data.i18n.error;
        p.textContent = data.i18n.error + ': ' + msg;
        box.appendChild(p);
        if (err && err.code === 'texttune_no_api_key') {
            var a = document.createElement('a');
            a.href = data.settingsUrl;
            a.textContent = data.i18n.openSettings;
            a.target = '_blank';
            p.appendChild(document.createTextNode(' — '));
            p.appendChild(a);
        }
        container.insertBefore(box, container.firstChild);
    }

    /* -------- Single-image analyze flow -------- */

    function analyzeOne(attachmentId, triggerBtn) {
        setStatus(triggerBtn, data.i18n.analyzing, 'is-busy');
        if (triggerBtn) triggerBtn.disabled = true;

        return request({
            attachment_id: attachmentId,
            fields: enabledFields,
            save: false
        }).then(function (res) {
            var generated = res && res.generated ? res.generated : {};
            // Prefer values from the DOM (reflect user's unsaved edits) over DB snapshot.
            var domCurrent = readCurrentFromDom(attachmentId);
            var current = {};
            FIELDS.forEach(function (f) {
                current[f] = domCurrent[f] !== null && domCurrent[f] !== undefined
                    ? domCurrent[f]
                    : (res.current && res.current[f] ? res.current[f] : '');
            });

            return new Promise(function (resolve) {
                showDecisionDialog(
                    attachmentId,
                    current,
                    generated,
                    function (overwriteMap) {
                        return request({
                            attachment_id: attachmentId,
                            fields: enabledFields,
                            save: true,
                            overwrite_map: overwriteMap
                        }).then(function (saveRes) {
                            // Reflect saved values in any open form inputs.
                            (saveRes.applied || []).forEach(function (field) {
                                writeFieldToDom(attachmentId, field, saveRes.generated[field] || '');
                            });
                            setStatus(triggerBtn, data.i18n.saved, 'is-success');
                            resolve({ ok: true, applied: saveRes.applied || [] });
                        });
                    },
                    function () {
                        setStatus(triggerBtn, '', '');
                        resolve({ ok: false, cancelled: true });
                    }
                );
            });
        }).catch(function (err) {
            var msg = (err && err.message) ? err.message : data.i18n.error;
            setStatus(triggerBtn, msg, 'is-error');
            alert(data.i18n.error + ': ' + msg);
            return { ok: false, error: err };
        }).then(function (result) {
            if (triggerBtn) triggerBtn.disabled = false;
            return result;
        });
    }

    /* -------- Bulk flow -------- */

    function showBulkStrategyDialog(ids) {
        var modal = buildModal(data.i18n.bulkTitle);
        var p = document.createElement('p');
        p.textContent = data.i18n.bulkStrategyQ + ' (' + ids.length + ')';
        modal.body.appendChild(p);

        var strategies = [
            { value: 'overwrite', label: data.i18n.bulkStrategyOvw },
            { value: 'keep',      label: data.i18n.bulkStrategyKeep },
            { value: 'ask',       label: data.i18n.bulkStrategyAsk }
        ];

        strategies.forEach(function (s, idx) {
            var label = document.createElement('label');
            label.style.display = 'block';
            label.style.margin = '4px 0';
            var r = document.createElement('input');
            r.type = 'radio';
            r.name = 'ttai-bulk-strategy';
            r.value = s.value;
            if (idx === 1) r.checked = true; // default: keep
            label.appendChild(r);
            label.appendChild(document.createTextNode(' ' + s.label));
            modal.body.appendChild(label);
        });

        var startBtn = document.createElement('button');
        startBtn.type = 'button';
        startBtn.className = 'button button-primary';
        startBtn.textContent = data.i18n.bulkStart;

        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'button';
        cancelBtn.textContent = data.i18n.cancel;
        cancelBtn.addEventListener('click', closeModal);

        modal.footer.appendChild(cancelBtn);
        modal.footer.appendChild(startBtn);

        return new Promise(function (resolve) {
            startBtn.addEventListener('click', function () {
                var chosen = modal.body.querySelector('input[name="ttai-bulk-strategy"]:checked');
                var strategy = chosen ? chosen.value : 'keep';
                closeModal();
                resolve(strategy);
            });
        });
    }

    function runBulk(ids) {
        showBulkStrategyDialog(ids).then(function (strategy) {
            var progress = buildProgressModal(ids.length);
            var saved = 0, skipped = 0, cancelled = false;

            progress.cancelBtn.addEventListener('click', function () {
                cancelled = true;
                closeModal();
            });

            var index = 0;

            function next() {
                if (cancelled) return;
                if (index >= ids.length) {
                    progress.text.textContent = data.i18n.bulkDone
                        .replace('%1$d', saved)
                        .replace('%2$d', skipped);
                    progress.cancelBtn.textContent = data.i18n.close;
                    return;
                }
                var id = ids[index];
                progress.text.textContent = data.i18n.bulkProgress
                    .replace('%1$d', index + 1)
                    .replace('%2$d', ids.length);
                progress.bar.style.width = ((index / ids.length) * 100) + '%';

                index += 1;

                var handle;
                if (strategy === 'ask') {
                    handle = analyzeOneForBulk(id, null);
                } else {
                    handle = analyzeOneBulkDirect(id, strategy);
                }
                handle.then(function (res) {
                    if (res.ok && res.applied && res.applied.length) saved += 1;
                    else if (res.skipped) skipped += 1;
                    else if (!res.ok) skipped += 1;
                    progress.bar.style.width = ((index / ids.length) * 100) + '%';
                    setTimeout(next, 50);
                }).catch(function () {
                    skipped += 1;
                    setTimeout(next, 50);
                });
            }
            next();
        });
    }

    function analyzeOneBulkDirect(attachmentId, strategy) {
        // First fetch suggestion without saving to learn the `current` snapshot,
        // then decide per-field and save.
        return request({
            attachment_id: attachmentId,
            fields: enabledFields,
            save: false
        }).then(function (res) {
            var overwriteMap = {};
            enabledFields.forEach(function (f) {
                var cur = res.current && res.current[f] ? String(res.current[f]) : '';
                if (strategy === 'overwrite') {
                    overwriteMap[f] = 'overwrite';
                } else {
                    overwriteMap[f] = cur.trim() === '' ? 'overwrite' : 'keep';
                }
            });
            return request({
                attachment_id: attachmentId,
                fields: enabledFields,
                save: true,
                overwrite_map: overwriteMap
            }).then(function (saveRes) {
                return { ok: true, applied: saveRes.applied || [] };
            });
        }).catch(function (err) {
            if (err && err.code === 'texttune_not_an_image') {
                return { ok: false, skipped: true, reason: data.i18n.bulkSkippedNotImg };
            }
            return { ok: false, error: err };
        });
    }

    function analyzeOneForBulk(attachmentId) {
        // "Ask per image" strategy: reuse the interactive dialog.
        return analyzeOne(attachmentId, null).then(function (res) {
            return res || { ok: false };
        });
    }

    function buildProgressModal(total) {
        var modal = buildModal(data.i18n.bulkTitle);
        var text = document.createElement('p');
        text.textContent = data.i18n.bulkProgress.replace('%1$d', 0).replace('%2$d', total);
        modal.body.appendChild(text);

        var barWrap = document.createElement('div');
        barWrap.className = 'texttune-progress';
        var bar = document.createElement('div');
        bar.className = 'texttune-progress-bar';
        bar.style.width = '0%';
        barWrap.appendChild(bar);
        modal.body.appendChild(barWrap);

        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'button';
        cancelBtn.textContent = data.i18n.cancel;
        modal.footer.appendChild(cancelBtn);

        return { text: text, bar: bar, cancelBtn: cancelBtn };
    }

    /* -------- Event wiring -------- */

    document.addEventListener('click', function (ev) {
        var target = ev.target;
        if (!target) return;

        var btn = target.closest && target.closest('.texttune-analyze-btn');
        if (btn) {
            ev.preventDefault();
            var id = parseInt(btn.getAttribute('data-id'), 10);
            if (id) analyzeOne(id, btn);
            return;
        }

        var row = target.closest && target.closest('.texttune-analyze-row');
        if (row) {
            ev.preventDefault();
            var rid = parseInt(row.getAttribute('data-id'), 10);
            if (rid) analyzeOne(rid, null);
            return;
        }
    });

    // Auto-run bulk flow when arriving via the bulk-action redirect.
    if (Array.isArray(data.bulkIds) && data.bulkIds.length) {
        // Defer until DOM + media modal scripts have settled.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { runBulk(data.bulkIds); });
        } else {
            setTimeout(function () { runBulk(data.bulkIds); }, 100);
        }
    }
})();
