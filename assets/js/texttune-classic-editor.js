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

        // Register a split button with dropdown menu.
        editor.addButton('texttune_ai_menu', {
            title: 'TextTune AI',
            image: 'dashicons-admin-customizer',
            icon: 'dashicon dashicons-admin-customizer',
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
