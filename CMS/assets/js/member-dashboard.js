(function () {
    'use strict';

    function toBase64UrlFromBufferSource(bufferSource) {
        if (!bufferSource) {
            return '';
        }

        const view = bufferSource instanceof Uint8Array
            ? bufferSource
            : new Uint8Array(bufferSource);

        return toBase64Url(view);
    }

    function toBase64Url(uint8Array) {
        let binary = '';
        uint8Array.forEach(function (byte) {
            binary += String.fromCharCode(byte);
        });
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    function fromBase64Url(value) {
        if (!value) {
            return new Uint8Array();
        }
        const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
        const padded = normalized + '==='.slice((normalized.length + 3) % 4);
        const binary = atob(padded);
        const bytes = new Uint8Array(binary.length);
        for (let index = 0; index < binary.length; index += 1) {
            bytes[index] = binary.charCodeAt(index);
        }
        return bytes;
    }

    function normalizePublicKeyOptions(options) {
        if (!options || typeof options !== 'object') {
            return options;
        }

        const publicKey = options.publicKey && typeof options.publicKey === 'object'
            ? options.publicKey
            : options;

        if (publicKey.challenge) {
            publicKey.challenge = fromBase64Url(publicKey.challenge);
        }

        if (publicKey.user && publicKey.user.id) {
            publicKey.user.id = fromBase64Url(publicKey.user.id);
        }

        if (Array.isArray(publicKey.excludeCredentials)) {
            publicKey.excludeCredentials = publicKey.excludeCredentials.map(function (credential) {
                if (credential.id) {
                    credential.id = fromBase64Url(credential.id);
                }
                return credential;
            });
        }

        return publicKey;
    }

    function initPasskeys() {
        const form = document.querySelector('[data-passkey-form]');
        const trigger = document.querySelector('[data-passkey-register]');
        if (!form || !trigger || !window.PublicKeyCredential || !navigator.credentials || typeof navigator.credentials.create !== 'function') {
            return;
        }

        trigger.addEventListener('click', async function () {
            const optionsJson = form.getAttribute('data-passkey-options') || '{}';
            let options;
            try {
                options = JSON.parse(optionsJson);
            } catch (error) {
                window.alert('Die Passkey-Optionen konnten nicht gelesen werden.');
                return;
            }

            try {
                trigger.setAttribute('disabled', 'disabled');

                const credential = await navigator.credentials.create({
                    publicKey: normalizePublicKeyOptions(options)
                });

                if (!credential || !credential.response || !credential.response.clientDataJSON || !credential.response.attestationObject) {
                    window.alert('Der Passkey konnte nicht erstellt werden.');
                    return;
                }

                form.querySelector('input[name="client_data_json"]').value = toBase64UrlFromBufferSource(credential.response.clientDataJSON);
                form.querySelector('input[name="attestation_object"]').value = toBase64UrlFromBufferSource(credential.response.attestationObject);
                form.submit();
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Passkey-Registrierung wurde abgebrochen.');
            } finally {
                trigger.removeAttribute('disabled');
            }
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    async function uploadMemberFile(endpoint, token, path, file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('target_path', path);
        formData.append('csrf_token', token);
        formData.append('member_upload', '1');

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': token
            },
            body: formData,
            credentials: 'same-origin'
        });

        const payload = await response.json().catch(function () {
            return {};
        });

        if (!response.ok || payload.success === false) {
            throw new Error(payload.error || 'Upload fehlgeschlagen.');
        }

        return payload;
    }

    function initMemberUploads() {
        document.querySelectorAll('[data-member-upload-form]').forEach(function (form) {
            const endpoint = form.getAttribute('data-upload-endpoint');
            const path = form.getAttribute('data-upload-path');
            const input = form.querySelector('input[type="file"]');
            const status = form.querySelector('[data-member-upload-status]');
            const results = form.querySelector('[data-member-upload-results]');
            let currentToken = form.getAttribute('data-upload-token') || '';

            if (!endpoint || !currentToken || !path || !input || !status || !results) {
                return;
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const files = Array.from(input.files || []);
                if (files.length === 0) {
                    status.hidden = false;
                    status.textContent = 'Bitte mindestens eine Datei auswählen.';
                    results.hidden = true;
                    results.innerHTML = '';
                    return;
                }

                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                }

                status.hidden = false;
                status.textContent = 'Upload läuft…';
                results.hidden = false;
                results.innerHTML = '';

                let hadError = false;

                for (let index = 0; index < files.length; index += 1) {
                    const file = files[index];
                    status.textContent = 'Upload ' + (index + 1) + ' von ' + files.length + ': ' + file.name;

                    try {
                        const payload = await uploadMemberFile(endpoint, currentToken, path, file);
                        const item = document.createElement('div');
                        item.className = 'alert alert-success py-2 mb-0';
                        const fileName = document.createElement('strong');
                        fileName.textContent = file.name;
                        item.appendChild(fileName);
                        item.appendChild(document.createTextNode(' erfolgreich hochgeladen.'));
                        results.appendChild(item);

                        if (payload.new_token) {
                            currentToken = payload.new_token;
                            form.setAttribute('data-upload-token', payload.new_token);
                        }
                    } catch (error) {
                        hadError = true;
                        const item = document.createElement('div');
                        item.className = 'alert alert-danger py-2 mb-0';
                        const fileName = document.createElement('strong');
                        fileName.textContent = file.name;
                        item.appendChild(fileName);
                        item.appendChild(document.createTextNode(': ' + (error && error.message ? error.message : 'Upload fehlgeschlagen.')));
                        results.appendChild(item);
                    }
                }

                status.textContent = hadError
                    ? 'Upload abgeschlossen – mindestens eine Datei konnte nicht hochgeladen werden.'
                    : 'Upload erfolgreich abgeschlossen. Seite wird aktualisiert…';

                if (submitButton) {
                    submitButton.disabled = false;
                }

                if (!hadError) {
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 900);
                }
            });
        });
    }

    function initBackupCodeCopy() {
        const codes = document.querySelector('.member-backup-codes');
        if (!codes || !navigator.clipboard) {
            return;
        }

        codes.addEventListener('click', function () {
            const text = Array.from(codes.querySelectorAll('code')).map(function (node) {
                return node.textContent || '';
            }).join('\n');
            navigator.clipboard.writeText(text).catch(function () {
                return undefined;
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPasskeys();
        initMemberUploads();
        initBackupCodeCopy();
    });
}());
