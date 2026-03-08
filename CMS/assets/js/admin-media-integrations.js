/**
 * Admin Media Integrations
 *
 * Aktiviert FilePond und elFinder in der Admin-Medienverwaltung.
 */
(function () {
    'use strict';

    function showMessage(type, message) {
        if (typeof window.cmsAlert === 'function') {
            window.cmsAlert(type, message);
            return;
        }
        console[type === 'danger' ? 'error' : 'log'](message);
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[src="' + src + '"]');
            if (existing) {
                if (existing.dataset.loaded === '1') {
                    resolve();
                    return;
                }
                existing.addEventListener('load', function () { resolve(); }, { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }

            var script = document.createElement('script');
            script.src = src;
            script.defer = true;
            script.addEventListener('load', function () {
                script.dataset.loaded = '1';
                resolve();
            }, { once: true });
            script.addEventListener('error', reject, { once: true });
            document.head.appendChild(script);
        });
    }

    function initFilePond() {
        var input = document.querySelector('[data-filepond-upload]');
        if (!input || typeof window.FilePond === 'undefined') {
            return;
        }

        var csrfToken = input.dataset.csrfToken || '';
        var uploadPath = input.dataset.uploadPath || '';
        var uploadUrl = input.dataset.uploadUrl || '';
        var reloadTimer = null;

        var pond = window.FilePond.create(input, {
            allowMultiple: true,
            credits: false,
            labelIdle: 'Dateien hierher ziehen oder <span class="filepond--label-action">auswählen</span>',
            server: {
                process: function (fieldName, file, metadata, load, error, progress, abort) {
                    var formData = new FormData();
                    formData.append('filepond', file, file.name);
                    formData.append('path', uploadPath);
                    formData.append('csrf_token', csrfToken);

                    var controller = new AbortController();

                    fetch(uploadUrl, {
                        method: 'POST',
                        body: formData,
                        signal: controller.signal
                    }).then(function (response) {
                        return response.json().then(function (data) {
                            return {
                                ok: response.ok,
                                data: data
                            };
                        });
                    }).then(function (result) {
                        if (result.data && result.data.new_token) {
                            csrfToken = result.data.new_token;
                            input.dataset.csrfToken = csrfToken;
                        }

                        if (!result.ok) {
                            error((result.data && result.data.error) || 'Upload fehlgeschlagen');
                            showMessage('danger', (result.data && result.data.error) || 'Upload fehlgeschlagen.');
                            return;
                        }

                        progress(true, file.size, file.size);
                        load((result.data && result.data.id) || file.name);
                    }).catch(function (err) {
                        if (err && err.name === 'AbortError') {
                            return;
                        }
                        error('Upload fehlgeschlagen');
                        showMessage('danger', 'Upload fehlgeschlagen.');
                    });

                    return {
                        abort: function () {
                            controller.abort();
                            abort();
                        }
                    };
                }
            }
        });

        pond.on('processfile', function (fileError) {
            if (fileError) {
                return;
            }

            window.clearTimeout(reloadTimer);
            reloadTimer = window.setTimeout(function () {
                window.location.reload();
            }, 700);
        });
    }

    function initElfinder() {
        var container = document.querySelector('[data-elfinder]');
        if (!container) {
            return;
        }

        var connectorUrl = container.dataset.connectorUrl || '';
        var csrfToken = container.dataset.csrfToken || '';
        var jqueryScript = container.dataset.jqueryScript || '';
        var jqueryUiScript = container.dataset.jqueryUiScript || '';
        var elfinderScript = container.dataset.elfinderScript || '';

        if (!connectorUrl || !jqueryScript || !jqueryUiScript || !elfinderScript) {
            showMessage('danger', 'elFinder konnte nicht initialisiert werden.');
            return;
        }

        Promise.resolve()
            .then(function () {
                if (window.jQuery) {
                    return;
                }
                return loadScript(jqueryScript);
            })
            .then(function () {
                if (window.jQuery && window.jQuery.ui) {
                    return;
                }
                return loadScript(jqueryUiScript);
            })
            .then(function () {
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.elfinder) {
                    return;
                }
                return loadScript(elfinderScript);
            })
            .then(function () {
                if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.elfinder)) {
                    throw new Error('elFinder UI nicht verfügbar');
                }

                var url = connectorUrl + (connectorUrl.indexOf('?') === -1 ? '?' : '&') + 'csrf_token=' + encodeURIComponent(csrfToken);
                window.jQuery(container).elfinder({
                    url: url,
                    lang: 'de',
                    resizable: false,
                    height: container.offsetHeight || 720,
                    customData: {
                        csrf_token: csrfToken
                    }
                });
            })
            .catch(function () {
                container.innerHTML = '<div class="alert alert-warning" role="alert">elFinder konnte nicht geladen werden. Bitte lokale Vendor-Dateien prüfen.</div>';
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initFilePond();
        initElfinder();
    });
})();