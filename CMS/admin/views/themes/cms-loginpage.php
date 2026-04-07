<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
$pageOptions = is_array($data['page_options'] ?? null) ? $data['page_options'] : [];
$registrationEnabled = !empty($data['registration_enabled']);
$passkeyEnabled = !empty($data['passkey_enabled']);
$previewUrls = is_array($data['preview_urls'] ?? null) ? $data['preview_urls'] : [];

$renderSelect = static function (string $name, string $label, array $options, string $selectedValue): void {
    ?>
    <div class="col-md-4">
        <label class="form-label" for="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
        <select class="form-select" id="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
            <option value="0">— Nicht verlinken —</option>
            <?php foreach ($options as $option): ?>
                <?php $value = (string) ($option['id'] ?? '0'); ?>
                <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedValue === $value ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) ($option['title'] ?? $value), ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
};
?>
<div class="container-xl py-3">
    <div class="row g-4">
        <div class="col-12 col-xxl-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">CMS Loginpage</h2>
                </div>
                <div class="card-body">
                    <p class="text-secondary mb-4">Diese Einstellungen steuern die neue CMS-eigene Authentifizierungsstrecke für <code>/cms-login</code>, <code>/cms-register</code> und <code>/cms-password-forgot</code> – komplett unabhängig vom aktiven Frontend-Theme.</p>

                    <div class="alert alert-info mb-4" role="alert">
                        <strong>Status:</strong>
                        Registrierung ist aktuell <strong><?php echo $registrationEnabled ? 'aktiv' : 'deaktiviert'; ?></strong>,
                        Passkey-Login ist <strong><?php echo $passkeyEnabled ? 'sichtbar' : 'ausgeblendet'; ?></strong>.
                    </div>

                    <form method="POST" action="<?php echo htmlspecialchars((string) SITE_URL . '/admin/cms-loginpage', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                        <h3 class="h5 mb-3">Grundlayout</h3>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="brand_name">Brandname</label>
                                <input class="form-control" id="brand_name" name="brand_name" type="text" maxlength="80" value="<?php echo htmlspecialchars((string) ($settings['brand_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="logo_url">Logo-URL</label>
                                <input class="form-control" id="logo_url" name="logo_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['logo_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="card_width">Kartenbreite (px)</label>
                                <input class="form-control" id="card_width" name="card_width" type="number" min="380" max="720" value="<?php echo htmlspecialchars((string) ($settings['card_width'] ?? '480'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="footer_note">Footer-Hinweis</label>
                                <input class="form-control" id="footer_note" name="footer_note" type="text" maxlength="120" value="<?php echo htmlspecialchars((string) ($settings['footer_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <h3 class="h5 mb-3">Farben</h3>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label" for="background_start">Background Start</label>
                                <input class="form-control form-control-color" id="background_start" name="background_start" type="color" value="<?php echo htmlspecialchars((string) ($settings['background_start'] ?? '#0f172a'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="background_end">Background Ende</label>
                                <input class="form-control form-control-color" id="background_end" name="background_end" type="color" value="<?php echo htmlspecialchars((string) ($settings['background_end'] ?? '#1d4ed8'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="card_background">Card Hintergrund</label>
                                <input class="form-control form-control-color" id="card_background" name="card_background" type="color" value="<?php echo htmlspecialchars((string) ($settings['card_background'] ?? '#ffffff'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="text_color">Textfarbe</label>
                                <input class="form-control form-control-color" id="text_color" name="text_color" type="color" value="<?php echo htmlspecialchars((string) ($settings['text_color'] ?? '#0f172a'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="muted_color">Muted Text</label>
                                <input class="form-control form-control-color" id="muted_color" name="muted_color" type="color" value="<?php echo htmlspecialchars((string) ($settings['muted_color'] ?? '#475569'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="link_color">Linkfarbe</label>
                                <input class="form-control form-control-color" id="link_color" name="link_color" type="color" value="<?php echo htmlspecialchars((string) ($settings['link_color'] ?? '#1d4ed8'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="primary_color">Primary Button</label>
                                <input class="form-control form-control-color" id="primary_color" name="primary_color" type="color" value="<?php echo htmlspecialchars((string) ($settings['primary_color'] ?? '#2563eb'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="primary_text_color">Primary Text</label>
                                <input class="form-control form-control-color" id="primary_text_color" name="primary_text_color" type="color" value="<?php echo htmlspecialchars((string) ($settings['primary_text_color'] ?? '#ffffff'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="input_background">Input Hintergrund</label>
                                <input class="form-control form-control-color" id="input_background" name="input_background" type="color" value="<?php echo htmlspecialchars((string) ($settings['input_background'] ?? '#f8fafc'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="input_border">Input Border</label>
                                <input class="form-control form-control-color" id="input_border" name="input_border" type="color" value="<?php echo htmlspecialchars((string) ($settings['input_border'] ?? '#cbd5e1'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <h3 class="h5 mb-3">Texte & Bereiche</h3>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label" for="headline_login">Headline Login</label>
                                <input class="form-control" id="headline_login" name="headline_login" type="text" maxlength="120" value="<?php echo htmlspecialchars((string) ($settings['headline_login'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="subheadline_login">Subheadline Login</label>
                                <textarea class="form-control" id="subheadline_login" name="subheadline_login" rows="2"><?php echo htmlspecialchars((string) ($settings['subheadline_login'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="headline_register">Headline Register</label>
                                <input class="form-control" id="headline_register" name="headline_register" type="text" maxlength="120" value="<?php echo htmlspecialchars((string) ($settings['headline_register'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="subheadline_register">Subheadline Register</label>
                                <textarea class="form-control" id="subheadline_register" name="subheadline_register" rows="2"><?php echo htmlspecialchars((string) ($settings['subheadline_register'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="headline_forgot">Headline Passwort-Reset</label>
                                <input class="form-control" id="headline_forgot" name="headline_forgot" type="text" maxlength="120" value="<?php echo htmlspecialchars((string) ($settings['headline_forgot'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="subheadline_forgot">Subheadline Passwort-Reset</label>
                                <textarea class="form-control" id="subheadline_forgot" name="subheadline_forgot" rows="2"><?php echo htmlspecialchars((string) ($settings['subheadline_forgot'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-12">
                                <div class="border rounded p-3 h-100">
                                    <h4 class="h6 mb-3">Login</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="login_label_identifier">Label Benutzerfeld</label>
                                            <input class="form-control" id="login_label_identifier" name="login_label_identifier" type="text" value="<?php echo htmlspecialchars((string) ($settings['login_label_identifier'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="login_label_password">Label Passwortfeld</label>
                                            <input class="form-control" id="login_label_password" name="login_label_password" type="text" value="<?php echo htmlspecialchars((string) ($settings['login_label_password'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="login_identifier_placeholder">Placeholder Benutzerfeld</label>
                                            <input class="form-control" id="login_identifier_placeholder" name="login_identifier_placeholder" type="text" value="<?php echo htmlspecialchars((string) ($settings['login_identifier_placeholder'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="login_password_placeholder">Placeholder Passwortfeld</label>
                                            <input class="form-control" id="login_password_placeholder" name="login_password_placeholder" type="text" value="<?php echo htmlspecialchars((string) ($settings['login_password_placeholder'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="login_button_text">Button Login</label>
                                            <input class="form-control" id="login_button_text" name="login_button_text" type="text" value="<?php echo htmlspecialchars((string) ($settings['login_button_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="login_forgot_link_text">Link Passwort vergessen</label>
                                            <input class="form-control" id="login_forgot_link_text" name="login_forgot_link_text" type="text" value="<?php echo htmlspecialchars((string) ($settings['login_forgot_link_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="login_remember_label">Label „Angemeldet bleiben“</label>
                                            <input class="form-control" id="login_remember_label" name="login_remember_label" type="text" value="<?php echo htmlspecialchars((string) ($settings['login_remember_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="login_passkey_button_text">Passkey-Button</label>
                                            <input class="form-control" id="login_passkey_button_text" name="login_passkey_button_text" type="text" value="<?php echo htmlspecialchars((string) ($settings['login_passkey_button_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" id="login_show_remember" name="login_show_remember" type="checkbox" value="1" <?php echo ($settings['login_show_remember'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="login_show_remember">„Angemeldet bleiben“ anzeigen</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" id="login_show_passkey" name="login_show_passkey" type="checkbox" value="1" <?php echo ($settings['login_show_passkey'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="login_show_passkey">Passkey-Login anzeigen</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3 h-100">
                                    <h4 class="h6 mb-3">Registrierung</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" id="registration_enabled" name="registration_enabled" type="checkbox" value="1" <?php echo ($settings['registration_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="registration_enabled">Globale Registrierung aktivieren</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" id="member_registration_enabled" name="member_registration_enabled" type="checkbox" value="1" <?php echo ($settings['member_registration_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="member_registration_enabled">Mitglieder-Registrierung erlauben</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="register_label_email">Label E-Mail</label>
                                            <input class="form-control" id="register_label_email" name="register_label_email" type="text" value="<?php echo htmlspecialchars((string) ($settings['register_label_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="register_label_username">Label Benutzername</label>
                                            <input class="form-control" id="register_label_username" name="register_label_username" type="text" value="<?php echo htmlspecialchars((string) ($settings['register_label_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="register_label_password">Label Passwort</label>
                                            <input class="form-control" id="register_label_password" name="register_label_password" type="text" value="<?php echo htmlspecialchars((string) ($settings['register_label_password'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="register_label_password_confirm">Label Passwort bestätigen</label>
                                            <input class="form-control" id="register_label_password_confirm" name="register_label_password_confirm" type="text" value="<?php echo htmlspecialchars((string) ($settings['register_label_password_confirm'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="register_email_placeholder">Placeholder E-Mail</label>
                                            <input class="form-control" id="register_email_placeholder" name="register_email_placeholder" type="text" value="<?php echo htmlspecialchars((string) ($settings['register_email_placeholder'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="register_username_placeholder">Placeholder Benutzername</label>
                                            <input class="form-control" id="register_username_placeholder" name="register_username_placeholder" type="text" value="<?php echo htmlspecialchars((string) ($settings['register_username_placeholder'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="register_button_text">Button Registrierung</label>
                                            <input class="form-control" id="register_button_text" name="register_button_text" type="text" value="<?php echo htmlspecialchars((string) ($settings['register_button_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" id="register_require_terms" name="register_require_terms" type="checkbox" value="1" <?php echo ($settings['register_require_terms'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="register_require_terms">Rechts-Häkchen erzwingen</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="register_terms_label">Text für Rechts-Häkchen</label>
                                            <textarea class="form-control" id="register_terms_label" name="register_terms_label" rows="2"><?php echo htmlspecialchars((string) ($settings['register_terms_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="register_disabled_message">Hinweis bei deaktivierter Registrierung</label>
                                            <textarea class="form-control" id="register_disabled_message" name="register_disabled_message" rows="2"><?php echo htmlspecialchars((string) ($settings['register_disabled_message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3 h-100">
                                    <h4 class="h6 mb-3">Passwort vergessen</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="forgot_label_email">Label E-Mail</label>
                                            <input class="form-control" id="forgot_label_email" name="forgot_label_email" type="text" value="<?php echo htmlspecialchars((string) ($settings['forgot_label_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="forgot_email_placeholder">Placeholder E-Mail</label>
                                            <input class="form-control" id="forgot_email_placeholder" name="forgot_email_placeholder" type="text" value="<?php echo htmlspecialchars((string) ($settings['forgot_email_placeholder'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="forgot_request_button_text">Button Reset anfordern</label>
                                            <input class="form-control" id="forgot_request_button_text" name="forgot_request_button_text" type="text" value="<?php echo htmlspecialchars((string) ($settings['forgot_request_button_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="forgot_reset_button_text">Button Passwort ändern</label>
                                            <input class="form-control" id="forgot_reset_button_text" name="forgot_reset_button_text" type="text" value="<?php echo htmlspecialchars((string) ($settings['forgot_reset_button_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="forgot_done_button_text">Button nach Erfolg</label>
                                            <input class="form-control" id="forgot_done_button_text" name="forgot_done_button_text" type="text" value="<?php echo htmlspecialchars((string) ($settings['forgot_done_button_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="forgot_request_success_message">Erfolgsmeldung Link-Anforderung</label>
                                            <textarea class="form-control" id="forgot_request_success_message" name="forgot_request_success_message" rows="2"><?php echo htmlspecialchars((string) ($settings['forgot_request_success_message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="forgot_reset_success_message">Erfolgsmeldung Passwort-Reset</label>
                                            <textarea class="form-control" id="forgot_reset_success_message" name="forgot_reset_success_message" rows="2"><?php echo htmlspecialchars((string) ($settings['forgot_reset_success_message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h3 class="h5 mb-3">Recht & Footer-Links</h3>
                        <div class="row g-3 mb-4">
                            <?php $renderSelect('privacy_page_id', 'Datenschutzerklärung', $pageOptions, (string) ($settings['privacy_page_id'] ?? '0')); ?>
                            <?php $renderSelect('terms_page_id', 'Nutzungsbedingungen', $pageOptions, (string) ($settings['terms_page_id'] ?? '0')); ?>
                            <?php $renderSelect('imprint_page_id', 'Impressum', $pageOptions, (string) ($settings['imprint_page_id'] ?? '0')); ?>
                            <div class="col-md-3">
                                <label class="form-label" for="footer_link_login">Footer-Link Login</label>
                                <input class="form-control" id="footer_link_login" name="footer_link_login" type="text" value="<?php echo htmlspecialchars((string) ($settings['footer_link_login'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="footer_link_register">Footer-Link Registrierung</label>
                                <input class="form-control" id="footer_link_register" name="footer_link_register" type="text" value="<?php echo htmlspecialchars((string) ($settings['footer_link_register'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="footer_link_forgot">Footer-Link Reset</label>
                                <input class="form-control" id="footer_link_forgot" name="footer_link_forgot" type="text" value="<?php echo htmlspecialchars((string) ($settings['footer_link_forgot'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="footer_link_home">Footer-Link Startseite</label>
                                <input class="form-control" id="footer_link_home" name="footer_link_home" type="text" value="<?php echo htmlspecialchars((string) ($settings['footer_link_home'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <h3 class="h5 mb-3">Reset-E-Mail</h3>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="password_reset_expiry_minutes">Link gültig (Minuten)</label>
                                <input class="form-control" id="password_reset_expiry_minutes" name="password_reset_expiry_minutes" type="number" min="5" max="1440" value="<?php echo htmlspecialchars((string) ($settings['password_reset_expiry_minutes'] ?? '60'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="password_reset_email_subject">Mail-Betreff</label>
                                <input class="form-control" id="password_reset_email_subject" name="password_reset_email_subject" type="text" value="<?php echo htmlspecialchars((string) ($settings['password_reset_email_subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="password_reset_email_body">Mail-Text</label>
                                <textarea class="form-control" id="password_reset_email_body" name="password_reset_email_body" rows="8"><?php echo htmlspecialchars((string) ($settings['password_reset_email_body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <div class="form-hint">Verfügbare Platzhalter: <code>{site_name}</code>, <code>{brand_name}</code>, <code>{expires_minutes}</code>, <code>{reset_url}</code></div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <button class="btn btn-primary" type="submit">Speichern</button>
                            <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars((string) ($previewUrls['login'] ?? '/cms-login'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Login ansehen</a>
                            <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars((string) ($previewUrls['register'] ?? '/cms-register'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Register ansehen</a>
                            <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars((string) ($previewUrls['forgot_password'] ?? '/cms-password-forgot'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Reset ansehen</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xxl-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Hinweise</h3>
                </div>
                <div class="card-body text-secondary">
                    <ul class="mb-0 ps-3">
                        <li>Die Auth-Seiten laufen unabhängig vom aktiven Frontend-Theme.</li>
                        <li>Die festen Slugs sind <code>/cms-login</code>, <code>/cms-register</code> und <code>/cms-password-forgot</code>.</li>
                        <li>Bestehende alte Pfade werden intern auf die neue CMS-Loginpage umgeleitet.</li>
                        <li>Die Register-Freigabe nutzt bewusst die vorhandenen globalen CMS-Einstellungen statt eines zweiten parallelen Schalters.</li>
                        <li>„Angemeldet bleiben“ ist jetzt ein echter Persistenz-Schalter für die Session – keine UI-Attrappe mehr.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
