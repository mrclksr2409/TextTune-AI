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
        var visionModelSelects = document.querySelectorAll('.texttune-vision-model-select');
        var toggleBtn = document.getElementById('texttune-toggle-key');
        var apiKeyInput = document.getElementById('texttune-api-key');

        function syncSelectsForProvider(selects, provider, nameWhenActive) {
            selects.forEach(function (select) {
                if (select.getAttribute('data-provider') === provider) {
                    select.style.display = '';
                    select.name = nameWhenActive;
                } else {
                    select.style.display = 'none';
                    select.name = '';
                }
            });
        }

        /**
         * Show only the model dropdowns matching the selected provider.
         */
        function updateModelVisibility() {
            var selected = document.querySelector('.texttune-provider-radio:checked');
            if (!selected) return;

            var provider = selected.value;
            syncSelectsForProvider(modelSelects, provider, 'texttune_ai_settings[model]');
            syncSelectsForProvider(visionModelSelects, provider, 'texttune_ai_settings[vision][model]');
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
