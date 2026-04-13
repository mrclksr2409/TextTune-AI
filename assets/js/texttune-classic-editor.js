/**
 * TextTune AI - Classic Editor (TinyMCE) Integration
 *
 * Adds a "Text optimieren" button to the TinyMCE toolbar in the Classic Editor.
 *
 * @package TextTune_AI
 */
(function () {
    'use strict';

    tinymce.PluginManager.add('texttune_ai', function (editor) {
        var isLoading = false;

        /**
         * Call the TextTune AI REST API to optimize content.
         *
         * @param {string}   content  The text to optimize.
         * @param {Function} callback Called with (error, optimizedContent).
         */
        function callOptimizeAPI(content, callback) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', texttuneClassicData.restUrl + 'texttune/v1/optimize', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-WP-Nonce', texttuneClassicData.nonce);

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;

                try {
                    var response = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && response.success) {
                        callback(null, response.content);
                    } else {
                        var msg = (response && response.message) || response.data && response.data.message || 'Unbekannter Fehler';
                        callback(msg, null);
                    }
                } catch (e) {
                    callback('Fehler beim Verarbeiten der Antwort.', null);
                }
            };

            xhr.onerror = function () {
                callback('Netzwerkfehler. Bitte versuche es erneut.', null);
            };

            xhr.send(JSON.stringify({
                content: content,
                post_type: texttuneClassicData.postType,
            }));
        }

        /**
         * Optimize the full editor content.
         */
        function optimizeFullContent() {
            if (isLoading) return;

            var content = editor.getContent();
            if (!content || !content.trim()) {
                alert('Der Beitrag hat keinen Inhalt zum Optimieren.');
                return;
            }

            isLoading = true;
            editor.setProgressState(true);
            updateButtonState();

            callOptimizeAPI(content, function (error, optimized) {
                isLoading = false;
                editor.setProgressState(false);
                updateButtonState();

                if (error) {
                    alert('TextTune AI Fehler: ' + error);
                    return;
                }

                // Save current content for undo.
                editor.undoManager.transact(function () {
                    editor.setContent(optimized);
                });

                editor.notificationManager.open({
                    text: 'Der gesamte Text wurde optimiert!',
                    type: 'success',
                    timeout: 3000,
                });
            });
        }

        /**
         * Optimize only the selected text.
         */
        function optimizeSelection() {
            if (isLoading) return;

            var selectedContent = editor.selection.getContent();
            if (!selectedContent || !selectedContent.trim()) {
                alert('Bitte wähle zuerst Text aus, den du optimieren möchtest.');
                return;
            }

            isLoading = true;
            editor.setProgressState(true);
            updateButtonState();

            callOptimizeAPI(selectedContent, function (error, optimized) {
                isLoading = false;
                editor.setProgressState(false);
                updateButtonState();

                if (error) {
                    alert('TextTune AI Fehler: ' + error);
                    return;
                }

                editor.undoManager.transact(function () {
                    editor.selection.setContent(optimized);
                });

                editor.notificationManager.open({
                    text: 'Der ausgewählte Text wurde optimiert!',
                    type: 'success',
                    timeout: 3000,
                });
            });
        }

        /**
         * Update the toolbar button disabled state.
         */
        function updateButtonState() {
            var btn = editor.controlManager && editor.controlManager.get('texttune_ai_menu');
            if (btn) {
                btn.disabled(isLoading);
            }
        }

        // Magic wand SVG as data URI for TinyMCE.
        var wandIcon = 'data:image/svg+xml;base64,' + btoa('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path fill="#555d66" d="M7.5 5.6L10 7 8.6 4.5 10 2 7.5 3.4 5 2l1.4 2.5L5 7zm12 9.8L17 14l1.4 2.5L17 19l2.5-1.4L22 19l-1.4-2.5L22 14zM22 2l-2.5 1.4L17 2l1.4 2.5L17 7l2.5-1.4L22 7l-1.4-2.5zm-7.63 5.29a.9959.9959 0 0 0-1.41 0L1.29 18.96c-.39.39-.39 1.02 0 1.41l2.34 2.34c.39.39 1.02.39 1.41 0L16.71 11.04c.39-.39.39-1.02 0-1.41l-2.34-2.34zM5.71 21.29L2.71 18.29 9 12l3 3-6.29 6.29z"/></svg>');

        // Register a split button with dropdown menu.
        editor.addButton('texttune_ai_menu', {
            title: 'TextTune AI',
            image: wandIcon,
            type: 'menubutton',
            menu: [
                {
                    text: 'Gesamten Text optimieren',
                    onclick: optimizeFullContent,
                },
                {
                    text: 'Auswahl optimieren',
                    onclick: optimizeSelection,
                },
            ],
        });
    });
})();
