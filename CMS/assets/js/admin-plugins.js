(function () {
    function bindPluginToggleSubmit() {
        document.querySelectorAll('.js-plugin-toggle-submit').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var form = toggle.form;

                if (!form) {
                    return;
                }

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
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindPluginToggleSubmit();
    });
})();