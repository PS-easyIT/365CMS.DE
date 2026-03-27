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

                form.submit();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindPluginToggleSubmit();
    });
})();