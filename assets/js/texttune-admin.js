/**
 * TextTune AI Admin Settings JavaScript
 *
 * Handles the provider/model toggle and keeps the post-save redirect on the
 * active tab. Tab switching and the API key reveal button are provided by
 * WP-Backend UI (wpb-admin.js, data-wpb-tabs / data-wpb-reveal).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var radios = document.querySelectorAll('.texttune-provider-radio');
        var modelSelects = document.querySelectorAll('.texttune-model-select');
        var visionModelSelects = document.querySelectorAll('.texttune-vision-model-select');
        var tabLinks = document.querySelectorAll('.texttune-settings .nav-tab[data-texttune-tab]');
        var refererInput = document.getElementById('texttune-referer');

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

        /**
         * Point the post-save redirect (_wp_http_referer) at the given tab, so
         * options.php returns to the tab the user was on.
         */
        function syncRefererTab(tab) {
            if (!refererInput || !tab) return;
            var url = refererInput.value;
            if (/([?&])tab=[^&#]*/.test(url)) {
                url = url.replace(/([?&])tab=[^&#]*/, '$1tab=' + encodeURIComponent(tab));
            } else {
                url += (url.indexOf('?') === -1 ? '?' : '&') + 'tab=' + encodeURIComponent(tab);
            }
            refererInput.value = url;
        }

        tabLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                syncRefererTab(link.getAttribute('data-texttune-tab'));
            });
        });

        // Initial state (wpb-admin.js may have activated a tab from the URL hash).
        var activeTab = document.querySelector('.texttune-settings .nav-tab-active[data-texttune-tab]');
        if (activeTab) {
            syncRefererTab(activeTab.getAttribute('data-texttune-tab'));
        }
    });
})();
