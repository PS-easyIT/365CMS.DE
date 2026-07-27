<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
.customizer-layout { display: flex; gap: 2rem; align-items: flex-start; }
.customizer-nav { width: 240px; flex-shrink: 0; background: #fff; border-radius: var(--tblr-border-radius-lg, 12px); border: 1px solid var(--tblr-border-color, #e6e7e9); overflow: hidden; }
.customizer-nav a { display: block; padding: 1rem 1.5rem; color: #64748b; text-decoration: none; border-left: 3px solid transparent; transition: all .2s; }
.customizer-nav a:hover { background: #f8fafc; color: var(--tblr-primary, #206bc4); }
.customizer-nav a.active { background: #eff6ff; color: var(--tblr-primary, #206bc4); border-left-color: var(--tblr-primary, #206bc4); font-weight: 600; }
.customizer-content { flex: 1; }
.customizer-alert + .customizer-alert { margin-top: .75rem; }
.customizer-checkbox-row { display: flex; align-items: center; gap: .5rem; margin-top: .5rem; }
.customizer-checkbox-label { cursor: pointer; }
.customizer-logo-upload { display: flex; flex-direction: column; gap: 10px; }
.customizer-logo-upload-row { display: flex; align-items: center; gap: 8px; }
.customizer-logo-preview-wrap { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 12px; display: flex; align-items: center; gap: 12px; min-height: 60px; }
.customizer-logo-preview-img { max-height: 48px; max-width: 200px; }
.customizer-logo-preview-empty { color: #94a3b8; font-size: .85rem; }
.customizer-upload-button { cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: .45rem .9rem; background: #3b82f6; color: #fff; border-radius: 5px; font-size: .85rem; font-weight: 600; }
.customizer-upload-input { display: none; }
.customizer-upload-hint { color: #64748b; font-size: .8rem; }
.customizer-color-row { display: flex; align-items: center; gap: 10px; }
.customizer-color-input { height: 38px; padding: 2px; width: 60px; border: 1px solid #ddd; border-radius: 4px; }
.customizer-color-text { width: 120px; }
.customizer-field-help { display: block; }
.customizer-form-actions { justify-content: space-between; }
.form-actions-card { position: sticky; bottom: 1rem; z-index: 10; }
.customizer-reset-note { color: #64748b; font-size: .875rem; }
.customizer-palette-wrap { padding: 1rem; border-bottom: 1px solid #f1f5f9; background: #fafafa; }
.customizer-palette-title { font-size: .75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .5rem; }
.customizer-palette { display: flex; gap: 6px; flex-wrap: wrap; padding: 1rem 0 0; }
.customizer-swatch { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.customizer-swatch-dot { width: 32px; height: 32px; border-radius: 50%; border: 2px solid rgba(0,0,0,.1); }
.customizer-swatch-label { font-size: 0.68rem; color: #64748b; max-width: 48px; text-align: center; line-height: 1.2; }
.customizer-modal-hidden { display: none; }
.customizer-modal-content { max-width: 480px; }
.customizer-hidden-form { display: none; }
.customizer-preview-error { color: #ef4444; }
@media (max-width: 960px) {
    .customizer-layout { flex-direction: column; }
    .customizer-nav { width: 100%; }
}
</style>
