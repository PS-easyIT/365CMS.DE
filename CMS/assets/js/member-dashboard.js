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

    function initFilePond() {
        if (!window.FilePond) {
            return;
        }

        document.querySelectorAll('input.filepond').forEach(function (input) {
            const endpoint = input.getAttribute('data-upload-endpoint');
            const token = input.getAttribute('data-upload-token');
            const path = input.getAttribute('data-upload-path');
            if (!endpoint || !token || !path) {
                return;
            }

            window.FilePond.create(input, {
                allowMultiple: true,
                server: {
                    process: {
                        url: endpoint,
                        method: 'POST',
                        headers: {
                            'X-CSRF-Token': token
                        },
                        ondata: function (formData) {
                            formData.append('target_path', path);
                            formData.append('csrf_token', token);
                            formData.append('member_upload', '1');
                            return formData;
                        }
                    }
                },
                labelIdle: 'Dateien hierher ziehen oder <span class="filepond--label-action">durchsuchen</span>'
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
        initFilePond();
        initBackupCodeCopy();
    });
}());
