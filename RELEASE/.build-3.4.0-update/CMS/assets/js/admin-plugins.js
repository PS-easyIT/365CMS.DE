(function () {
    function updateToggleStatus(toggle) {
        if (!toggle) {
            return;
        }

        var row = toggle.closest('.admin-plugin-row');
        if (!row) {
            return;
        }

        var statusLabel = row.querySelector('.js-plugin-toggle-status');
        if (!statusLabel) {
            return;
        }

        if (toggle.checked) {
            statusLabel.textContent = 'Aktiv';
            statusLabel.classList.add('is-active');
            statusLabel.classList.remove('is-inactive');
            return;
        }

        statusLabel.textContent = 'Inaktiv';
        statusLabel.classList.add('is-inactive');
        statusLabel.classList.remove('is-active');
    }

    function bindPluginToggleSubmit() {
        document.querySelectorAll('.js-plugin-toggle-submit').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var form = toggle.form;

                updateToggleStatus(toggle);

                if (!form) {
                    return;
                }

                setTimeout(function () {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                        return;
                    }

                    var fallbackSubmitter = document.createElement('button');
                    fallbackSubmitter.type = 'submit';
                    fallbackSubmitter.hidden = true;
                    form.appendChild(fallbackSubmitter);
                    fallbackSubmitter.click();
                    fallbackSubmitter.remove();
                }, 0);
            });

            updateToggleStatus(toggle);
        });
    }

    function bindPluginDescriptionClampToggle() {
        document.querySelectorAll('.js-plugin-description').forEach(function (container) {
            var text = container.querySelector('.admin-plugin-description__text');
            var toggle = container.querySelector('.js-plugin-description-toggle');
            if (!text || !toggle) {
                return;
            }

            // Measure in collapsed state and only show toggle for overflow.
            container.classList.remove('is-expanded');
            var isOverflowing = text.scrollHeight > text.clientHeight + 2;
            if (!isOverflowing) {
                toggle.classList.add('d-none');
                return;
            }

            toggle.classList.remove('d-none');
            toggle.textContent = '... mehr';

            toggle.addEventListener('click', function () {
                var expanded = container.classList.toggle('is-expanded');
                toggle.textContent = expanded ? '... weniger' : '... mehr';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindPluginToggleSubmit();
        bindPluginDescriptionClampToggle();
    });
})();