/**
 * TextTune AI Admin Settings JavaScript
 *
 * Handles provider/model toggle and API key visibility.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var radios = document.querySelectorAll('.texttune-provider-radio');
        var modelSelects = document.querySelectorAll('.texttune-model-select');
        var toggleBtn = document.getElementById('texttune-toggle-key');
        var apiKeyInput = document.getElementById('texttune-api-key');

        /**
         * Show only the model dropdown matching the selected provider.
         */
        function updateModelVisibility() {
            var selected = document.querySelector('.texttune-provider-radio:checked');
            if (!selected) return;

            var provider = selected.value;

            modelSelects.forEach(function (select) {
                if (select.getAttribute('data-provider') === provider) {
                    select.style.display = '';
                    select.name = 'texttune_ai_settings[model]';
                } else {
                    select.style.display = 'none';
                    select.name = '';
                }
            });
        }

        // Listen for provider changes.
        radios.forEach(function (radio) {
            radio.addEventListener('change', updateModelVisibility);
        });

        // Initial state.
        updateModelVisibility();

        // Toggle API key visibility.
        if (toggleBtn && apiKeyInput) {
            toggleBtn.addEventListener('click', function () {
                if (apiKeyInput.type === 'password') {
                    apiKeyInput.type = 'text';
                    toggleBtn.textContent = 'Verbergen';
                } else {
                    apiKeyInput.type = 'password';
                    toggleBtn.textContent = 'Anzeigen';
                }
            });
        }
    });
})();
