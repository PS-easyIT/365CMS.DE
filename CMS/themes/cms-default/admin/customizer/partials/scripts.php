<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<script>
(function() {
    var modal = document.getElementById('confirm-reset-modal');
    var resetForm = document.getElementById('reset-form');
    var liveStyle = document.createElement('style');
    liveStyle.id = 'customizer-live-preview';
    document.head.appendChild(liveStyle);

    function clearNode(node) {
        while (node && node.firstChild) {
            node.removeChild(node.firstChild);
        }
    }

    function normalizePreviewUrl(value) {
        var url = String(value || '').trim();
        var normalizedRelative = '';

        if (url === '' || /^(?:javascript|data|vbscript):/i.test(url) || /^\/\//.test(url) || /^[A-Za-z]:[\\/]/.test(url)) {
            return '';
        }

        if (/^https?:\/\//i.test(url)) {
            return url;
        }

        if (url.charAt(0) === '/') {
            return url;
        }

        if (/^[a-z][a-z0-9+.-]*:/i.test(url)) {
            return '';
        }

        normalizedRelative = url.replace(/\\/g, '/').replace(/^(?:\.\/)+/, '').replace(/^\/+/, '');
        if (!normalizedRelative || normalizedRelative.indexOf('..') !== -1) {
            return '';
        }

        return '/' + normalizedRelative;
    }

    function setPreviewStatus(uploadRoot, message, isError) {
        var status = uploadRoot ? uploadRoot.querySelector('[data-preview-status]') : null;
        if (!status) {
            return;
        }

        status.textContent = message || '';
        status.classList.toggle('customizer-preview-error', Boolean(isError));
    }

    function renderPreviewImage(uploadRoot, src) {
        var previewWrap = uploadRoot ? uploadRoot.querySelector('[data-preview-wrap]') : null;
        var previewImage = uploadRoot ? uploadRoot.querySelector('[data-preview-image]') : null;
        var placeholder = uploadRoot ? uploadRoot.querySelector('[data-preview-placeholder]') : null;

        if (!previewWrap) {
            return;
        }

        if (previewImage) {
            previewImage.src = src;
        } else {
            clearNode(previewWrap);
            previewImage = document.createElement('img');
            previewImage.alt = 'Logo';
            previewImage.className = 'customizer-logo-preview-img';
            previewImage.setAttribute('data-preview-image', '');
            previewImage.src = src;
            previewWrap.appendChild(previewImage);
        }

        if (placeholder) {
            placeholder.remove();
        }
    }

    function renderPreviewError(uploadRoot, message) {
        var previewWrap = uploadRoot ? uploadRoot.querySelector('[data-preview-wrap]') : null;
        if (!previewWrap) {
            return;
        }

        clearNode(previewWrap);
        var errorNode = document.createElement('span');
        errorNode.className = 'customizer-logo-preview-empty customizer-preview-error';
        errorNode.setAttribute('data-preview-placeholder', '');
        errorNode.textContent = message;
        previewWrap.appendChild(errorNode);
    }

    function renderPreviewEmpty(uploadRoot) {
        var previewWrap = uploadRoot ? uploadRoot.querySelector('[data-preview-wrap]') : null;
        if (!previewWrap) {
            return;
        }

        clearNode(previewWrap);
        var placeholder = document.createElement('span');
        placeholder.className = 'customizer-logo-preview-empty';
        placeholder.setAttribute('data-preview-placeholder', '');
        placeholder.textContent = '🖼️ Noch kein Logo ausgewählt';
        previewWrap.appendChild(placeholder);
    }

    function getUploadRoot(element) {
        return element ? element.closest('[data-customizer-image-upload]') : null;
    }

    function openResetModal() {
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.remove('customizer-modal-hidden');
        }
    }

    function closeResetModal() {
        if (modal) {
            modal.style.display = 'none';
            modal.classList.add('customizer-modal-hidden');
        }
    }

    function confirmReset() {
        closeResetModal();
        if (resetForm) {
            resetForm.submit();
        }
    }

    function updateLivePreview() {
        var rules = ':root {\n';
        var mapping = {
            'colors_accent_color': '--accent',
            'colors_accent_dark_color': '--accent-dark',
            'colors_ink_color': '--ink',
            'colors_ink_soft_color': '--ink-soft',
            'colors_ink_muted_color': '--ink-muted',
            'colors_ground_color': '--ground',
            'colors_surface_color': '--surface',
            'colors_surface_tint_color': '--surface-tint',
            'colors_rule_color': '--rule',
            'colors_header_bg_color': '--header-bg-preview',
            'colors_header_stripe_color': '--stripe-preview'
        };

        Object.keys(mapping).forEach(function(name) {
            var input = document.querySelector('input[name="' + name + '"][type="color"]');
            if (input) {
                rules += '  ' + mapping[name] + ': ' + input.value + ';\n';
            }
        });

        rules += '}';
        liveStyle.textContent = rules;

        var headerBackground = document.querySelector('input[name="colors_header_bg_color"][type="color"]');
        if (headerBackground) {
            document.querySelectorAll('.site-header').forEach(function(header) {
                header.style.background = headerBackground.value;
            });
        }
    }

    document.querySelectorAll('input[type="color"]').forEach(function(picker) {
        var textInput = picker.nextElementSibling;
        if (textInput && textInput.tagName === 'INPUT') {
            picker.addEventListener('input', function() {
                textInput.value = this.value;
                updateLivePreview();
            });
            textInput.addEventListener('input', function() {
                var value = this.value.trim();
                if (/^#[0-9a-fA-F]{6}$/.test(value)) {
                    picker.value = value;
                    updateLivePreview();
                }
            });
        }
    });

    var colorSection = document.querySelector('.customizer-content');
    if (colorSection && document.querySelector('input[name="colors_accent_color"]')) {
        var palette = document.createElement('div');
        palette.id = 'color-palette-preview';
        palette.className = 'customizer-palette';

        [
            { name: 'colors_accent_color', label: 'Akzent' },
            { name: 'colors_accent_dark_color', label: 'Akzent Dunkel' },
            { name: 'colors_ink_color', label: 'Text' },
            { name: 'colors_ground_color', label: 'Hintergrund' },
            { name: 'colors_surface_color', label: 'Surface' },
            { name: 'colors_header_bg_color', label: 'Header' },
            { name: 'colors_header_stripe_color', label: 'Streifen' }
        ].forEach(function(colorField) {
            var input = document.querySelector('input[name="' + colorField.name + '"][type="color"]');
            if (!input) {
                return;
            }

            var swatch = document.createElement('div');
            swatch.className = 'customizer-swatch';
            var dot = document.createElement('div');
            dot.className = 'customizer-swatch-dot';
            dot.style.background = input.value;
            var label = document.createElement('span');
            label.className = 'customizer-swatch-label';
            label.textContent = colorField.label;
            swatch.appendChild(dot);
            swatch.appendChild(label);
            palette.appendChild(swatch);

            input.addEventListener('input', function() {
                dot.style.background = this.value;
            });
        });

        var firstCard = document.querySelector('.customizer-content .admin-card');
        if (firstCard) {
            var previewWrap = document.createElement('div');
            previewWrap.className = 'customizer-palette-wrap';
            var title = document.createElement('div');
            title.className = 'customizer-palette-title';
            title.textContent = 'Farb-Vorschau';
            previewWrap.appendChild(title);
            previewWrap.appendChild(palette);
            firstCard.insertBefore(previewWrap, firstCard.firstChild);
        }
    }

    document.addEventListener('keydown', function(event) {
        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
            event.preventDefault();
            var saveButton = document.querySelector('button[type="submit"].btn-primary');
            if (saveButton) {
                saveButton.click();
            }
        }
    });

    document.querySelectorAll('[data-customizer-image-file]').forEach(function(fileInput) {
        fileInput.addEventListener('change', function() {
            var uploadRoot = getUploadRoot(fileInput);
            if (!fileInput.files || !fileInput.files[0]) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function(event) {
                renderPreviewImage(uploadRoot, String(event.target && event.target.result ? event.target.result : ''));
                var urlField = uploadRoot ? uploadRoot.querySelector('[data-customizer-image-url]') : null;
                if (urlField) {
                    urlField.value = '';
                }
                setPreviewStatus(uploadRoot, 'Lokale Datei ausgewählt.', false);
            };
            reader.readAsDataURL(fileInput.files[0]);
        });
    });

    document.querySelectorAll('[data-customizer-image-url]').forEach(function(urlField) {
        urlField.addEventListener('input', function() {
            var uploadRoot = getUploadRoot(urlField);
            var url = urlField.value.trim();
            var previewUrl = normalizePreviewUrl(url);
            var fileInput = uploadRoot ? uploadRoot.querySelector('[data-customizer-image-file]') : null;
            var hasLocalSelection = !!(fileInput && fileInput.files && fileInput.files.length > 0);

            if (url === '') {
                if (!hasLocalSelection) {
                    renderPreviewEmpty(uploadRoot);
                }
                setPreviewStatus(uploadRoot, '', false);
                return;
            }

            if (previewUrl === '') {
                setPreviewStatus(uploadRoot, 'Bitte eine gültige http(s)-URL oder einen internen Pfad wie /uploads/... eingeben.', true);
                return;
            }

            setPreviewStatus(uploadRoot, '', false);
            renderPreviewImage(uploadRoot, previewUrl);
            var previewImage = uploadRoot ? uploadRoot.querySelector('[data-preview-image]') : null;
            if (previewImage) {
                previewImage.onerror = function() {
                    renderPreviewError(uploadRoot, 'Bild konnte nicht geladen werden');
                    setPreviewStatus(uploadRoot, 'Die Bild-URL konnte nicht geladen werden.', true);
                };
            }
        });
    });

    document.querySelectorAll('[data-customizer-reset-open]').forEach(function(button) {
        button.addEventListener('click', openResetModal);
    });

    document.querySelectorAll('[data-customizer-reset-close]').forEach(function(button) {
        button.addEventListener('click', closeResetModal);
    });

    document.querySelectorAll('[data-customizer-reset-confirm]').forEach(function(button) {
        button.addEventListener('click', confirmReset);
    });

    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeResetModal();
            }
        });
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal && !modal.classList.contains('customizer-modal-hidden')) {
            closeResetModal();
        }
    });

    updateLivePreview();
})();
</script>
