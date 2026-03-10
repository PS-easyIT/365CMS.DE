<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<script>
(function() {
    var liveStyle = document.createElement('style');
    liveStyle.id = 'customizer-live-preview';
    document.head.appendChild(liveStyle);

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
        palette.style.cssText = 'display:flex;gap:6px;flex-wrap:wrap;padding:1rem 0 0;';

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
            swatch.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:2px;';
            var dot = document.createElement('div');
            dot.style.cssText = 'width:32px;height:32px;border-radius:50%;border:2px solid rgba(0,0,0,.1);background:' + input.value + ';';
            var label = document.createElement('span');
            label.style.cssText = 'font-size:0.68rem;color:#64748b;max-width:48px;text-align:center;line-height:1.2;';
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

    updateLivePreview();
})();

function previewLogoUpload(input) {
    if (!input.files || !input.files[0]) {
        return;
    }

    var reader = new FileReader();
    reader.onload = function(event) {
        var wrap = document.getElementById('logo-preview-wrap');
        var image = document.getElementById('logo-preview-img');
        if (image && image.tagName === 'IMG') {
            image.src = event.target.result;
        } else if (wrap) {
            wrap.innerHTML = '<img id="logo-preview-img" src="' + event.target.result + '" alt="Logo" class="customizer-logo-preview-img">';
        }
        var urlField = document.querySelector('input[name="header_logo_url"]');
        if (urlField) {
            urlField.value = '';
        }
    };
    reader.readAsDataURL(input.files[0]);
}

function syncLogoUrlPreview(url) {
    var wrap = document.getElementById('logo-preview-wrap');
    if (!wrap) {
        return;
    }

    if (url && /^https?:\/\//.test(url)) {
        wrap.innerHTML = '<img id="logo-preview-img" src="' + url + '" alt="Logo" class="customizer-logo-preview-img" onerror="this.parentElement.innerHTML=\'<span class=&quot;customizer-logo-preview-empty&quot; style=&quot;color:#ef4444&quot;>Bild konnte nicht geladen werden</span>\'">';
    }
}

function showResetConfirm() {
    var modal = document.getElementById('confirm-reset-modal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeResetModal() {
    var modal = document.getElementById('confirm-reset-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function confirmReset() {
    closeResetModal();
    var form = document.getElementById('reset-form');
    if (form) {
        form.submit();
    }
}
</script>
