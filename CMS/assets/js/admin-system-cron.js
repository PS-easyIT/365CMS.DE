(function () {
    'use strict';

    function setBusyState(buttons, busy) {
        buttons.forEach(function (button) {
            button.disabled = busy;
        });
    }

    function formatPayload(payload) {
        try {
            return JSON.stringify(payload, null, 2);
        } catch (error) {
            return String(payload);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('[data-cron-runner-form]');
        var output = document.querySelector('[data-cron-runner-output]');

        if (!form || !output) {
            return;
        }

        var endpoint = String(form.getAttribute('data-cron-runner-endpoint') || '').trim();
        var token = String(form.getAttribute('data-cron-runner-token') || '').trim();
        var buttons = Array.prototype.slice.call(form.querySelectorAll('[data-cron-runner-trigger]'));

        if (endpoint === '' || token === '' || buttons.length === 0) {
            return;
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var mechanism = String(button.getAttribute('data-cron-runner-trigger') || 'direct').toLowerCase();
                var action = mechanism === 'loopback' ? 'run_cron_loopback' : 'run_cron_direct';
                var formData = new FormData(form);
                formData.set('action', action);
                formData.set('csrf_token', token);

                output.textContent = 'Cron-Runner startet (' + mechanism + ') …';
                setBusyState(buttons, true);

                fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.text().then(function (body) {
                        var payload;
                        try {
                            payload = JSON.parse(body);
                        } catch (error) {
                            payload = {
                                success: false,
                                error: 'Antwort war kein gültiges JSON.',
                                raw: body
                            };
                        }

                        payload.http_status = response.status;
                        return payload;
                    });
                }).then(function (payload) {
                    output.textContent = formatPayload(payload);
                }).catch(function (error) {
                    output.textContent = formatPayload({
                        success: false,
                        error: error instanceof Error ? error.message : String(error)
                    });
                }).finally(function () {
                    setBusyState(buttons, false);
                });
            });
        });
    });
})();