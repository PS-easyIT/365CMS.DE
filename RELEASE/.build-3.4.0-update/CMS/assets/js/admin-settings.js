(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var presetSelect = document.querySelector('[data-settings-permalink-preset]');
        var customGroup = document.querySelector('[data-settings-permalink-custom-group]');
        var customInput = document.querySelector('[data-settings-permalink-custom-input]');

        if (!presetSelect || !customGroup || !customInput) {
            return;
        }

        var syncCustomPermalinkState = function () {
            var isCustom = presetSelect.value === 'custom';

            customGroup.hidden = !isCustom;
            customGroup.setAttribute('aria-hidden', isCustom ? 'false' : 'true');
            customInput.disabled = !isCustom;
            customInput.required = isCustom;

            if (isCustom && document.activeElement === presetSelect) {
                customInput.focus();
            }
        };

        presetSelect.addEventListener('change', syncCustomPermalinkState, { passive: true });
        syncCustomPermalinkState();
    });
})();
