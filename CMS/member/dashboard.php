<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Dashboard';
$pageKey = 'dashboard';
$pageAssets = [];
$data = $controller->getDashboardData();
$settings = $data['settings'] ?? $settings;
$notifications = is_array($data['recent_notifications'] ?? null) ? $data['recent_notifications'] : [];
$activity = is_array($data['recent_activity'] ?? null) ? $data['recent_activity'] : [];
$pluginWidgets = is_array($data['plugin_widgets'] ?? null) ? $data['plugin_widgets'] : [];
$customWidgets = is_array($data['custom_widgets'] ?? null) ? $data['custom_widgets'] : [];
$profileCompletion = is_array($data['profile_completion'] ?? null) ? $data['profile_completion'] : ['percentage' => 0, 'missing' => []];
$subscription = $data['subscription'] ?? null;
$frontendModules = is_array($settings['frontend_modules'] ?? null) ? $settings['frontend_modules'] : [];
$showWelcome = !empty($settings['show_welcome']);
$showQuickstart = !array_key_exists('show_quickstart', $frontendModules) || !empty($frontendModules['show_quickstart']);
$showStats = !array_key_exists('show_stats', $frontendModules) || !empty($frontendModules['show_stats']);
$showCustomWidgets = !array_key_exists('show_custom_widgets', $frontendModules) || !empty($frontendModules['show_custom_widgets']);
$showPluginWidgets = !array_key_exists('show_plugin_widgets', $frontendModules) || !empty($frontendModules['show_plugin_widgets']);
$showNotificationsPanel = !array_key_exists('show_notifications_panel', $frontendModules) || !empty($frontendModules['show_notifications_panel']);
$showOnboarding = !array_key_exists('show_onboarding_panel', $frontendModules) || !empty($frontendModules['show_onboarding_panel']);
$onboarding = is_array($settings['onboarding'] ?? null) ? $settings['onboarding'] : [];
$profileMissing = is_array($profileCompletion['missing'] ?? null) ? $profileCompletion['missing'] : [];

include __DIR__ . '/partials/header.php';
?>
<div class="row g-4">
    <?php if ($showWelcome): ?>
        <div class="col-12">
            <div class="card member-hero-card">
                <div class="card-body p-4 p-lg-5">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-8">
                            <div class="page-pretitle">Persönlicher Überblick</div>
                            <h3 class="card-title mb-2"><?= htmlspecialchars(str_replace('{name}', $controller->getDisplayName(), (string)($settings['dashboard_greeting'] ?? 'Willkommen zurück, {name}!'))) ?></h3>
                            <p class="text-secondary mb-0">
                                <?= htmlspecialchars((string)($settings['dashboard_welcome_text'] ?? 'Hier findest du alle wichtigen Funktionen rund um Profil, Sicherheit, Dateien und Kommunikation.')) ?>
                            </p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <a href="<?= htmlspecialchars(SITE_URL) ?>/member/profile" class="btn btn-primary">Profil vervollständigen</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($showStats): ?>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-primary text-white avatar">👋</span></div>
                        <div class="col">
                            <div class="font-weight-medium"><?= (int)($data['login_count_30d'] ?? 0) ?> Logins</div>
                            <div class="text-secondary">in den letzten 30 Tagen</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-azure text-white avatar">🛡️</span></div>
                        <div class="col">
                            <div class="font-weight-medium"><?= (int)($data['security']['score'] ?? 0) ?>/100</div>
                            <div class="text-secondary">Security Score</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-green text-white avatar">✉️</span></div>
                        <div class="col">
                            <div class="font-weight-medium"><?= (int)($data['unread_messages'] ?? 0) ?> ungelesen</div>
                            <div class="text-secondary">Nachrichten im Posteingang</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-purple text-white avatar">📦</span></div>
                        <div class="col">
                            <div class="font-weight-medium"><?= (int)($data['active_sessions'] ?? 0) ?> aktive Sessions</div>
                            <div class="text-secondary">zuletzt aktiv <?= htmlspecialchars((string)($data['last_login_relative'] ?? '–')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-12 col-xl-8">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Schnellzugriff</h3>
                    </div>
                    <div class="card-body">
                        <div class="row row-cards">
                            <div class="col-sm-6 col-lg-4">
                                <a class="card card-link card-link-pop" href="<?= htmlspecialchars(SITE_URL) ?>/member/profile">
                                    <div class="card-body">
                                        <div class="h3 mb-1">Profil</div>
                                        <div class="text-secondary">Persönliche Daten und Sichtbarkeit verwalten</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-sm-6 col-lg-4">
                                <a class="card card-link card-link-pop" href="<?= htmlspecialchars(SITE_URL) ?>/member/security">
                                    <div class="card-body">
                                        <div class="h3 mb-1">Sicherheit</div>
                                        <div class="text-secondary">MFA, Passkeys und Passwort-Einstellungen</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-sm-6 col-lg-4">
                                <a class="card card-link card-link-pop" href="<?= htmlspecialchars(SITE_URL) ?>/member/media">
                                    <div class="card-body">
                                        <div class="h3 mb-1">Dateien</div>
                                        <div class="text-secondary">Medien hochladen, Ordner erstellen, Dateien verwalten</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($showOnboarding && !empty($onboarding['enabled'])): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?= htmlspecialchars((string)($onboarding['title'] ?? 'Onboarding')) ?></h3>
                        </div>
                        <div class="card-body">
                            <p class="text-secondary"><?= htmlspecialchars((string)($onboarding['intro'] ?? '')) ?></p>
                            <?php if (!empty($onboarding['steps']) && is_array($onboarding['steps'])): ?>
                                <ol class="mb-3 member-checklist">
                                    <?php foreach ($onboarding['steps'] as $step): ?>
                                        <li><?= htmlspecialchars((string)$step) ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars((string)(SITE_URL . ($onboarding['cta_url'] ?? '/member/profile'))) ?>" class="btn btn-outline-primary">
                                <?= htmlspecialchars((string)($onboarding['cta_label'] ?? 'Jetzt starten')) ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($showCustomWidgets && $customWidgets !== []): ?>
                <?php foreach ($customWidgets as $widget): ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="avatar avatar-sm"><?= htmlspecialchars((string)($widget['icon'] ?? '✨')) ?></span>
                                    <h3 class="card-title mb-0"><?= htmlspecialchars((string)($widget['title'] ?? 'Widget')) ?></h3>
                                </div>
                                <div class="text-secondary"><?= nl2br(htmlspecialchars((string)($widget['content'] ?? ''))) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($showPluginWidgets && $pluginWidgets !== []): ?>
                <?php foreach ($pluginWidgets as $widget): ?>
                    <div class="col-md-6">
                        <div class="card h-100 member-plugin-widget">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <div class="text-secondary small mb-1">Plugin</div>
                                        <h3 class="card-title mb-1"><?= htmlspecialchars((string)($widget['title'] ?? 'Plugin')) ?></h3>
                                        <p class="text-secondary mb-0"><?= htmlspecialchars((string)($widget['description'] ?? '')) ?></p>
                                    </div>
                                    <span class="avatar" style="background: <?= htmlspecialchars((string)($widget['color'] ?? '#4f46e5')) ?>; color: #fff;">
                                        <?= htmlspecialchars((string)($widget['icon'] ?? '🔌')) ?>
                                    </span>
                                </div>
                                <?php if (!empty($widget['stats']) && is_array($widget['stats'])): ?>
                                    <div class="h2 mb-1"><?= (int)($widget['stats']['count'] ?? 0) ?></div>
                                    <div class="text-secondary mb-3"><?= htmlspecialchars((string)($widget['stats']['label'] ?? 'Einträge')) ?></div>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars((string)($widget['link'] ?? '/member/dashboard')) ?>" class="btn btn-outline-primary btn-sm">
                                    <?= htmlspecialchars((string)($widget['link_label'] ?? 'Öffnen')) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Profil-Fortschritt</h3>
                        <span class="badge bg-primary-lt"><?= (int)($profileCompletion['percentage'] ?? 0) ?>%</span>
                    </div>
                    <div class="card-body">
                        <div class="progress progress-separated mb-3">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= (int)($profileCompletion['percentage'] ?? 0) ?>%" aria-valuenow="<?= (int)($profileCompletion['percentage'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <?php if ($profileMissing !== []): ?>
                            <div class="text-secondary small mb-2">Noch offen:</div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($profileMissing as $field): ?>
                                    <span class="badge bg-secondary-lt"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$field))) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-description">Stark – dein Profil ist vollständig gepflegt.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($showNotificationsPanel): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Letzte Benachrichtigungen</h3>
                            <a href="<?= htmlspecialchars(SITE_URL) ?>/member/notifications" class="btn btn-sm btn-outline-primary">Verwalten</a>
                        </div>
                        <div class="list-group list-group-flush list-group-hoverable">
                            <?php if ($notifications === []): ?>
                                <div class="card-body text-secondary">Aktuell gibt es keine neuen Benachrichtigungen.</div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="list-group-item">
                                        <div class="text-body"><?= htmlspecialchars((string)($notification->title ?? $notification->message ?? 'Benachrichtigung')) ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars((string)($notification->created_at ?? '')) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Account-Status</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-6 text-secondary">Letzter Login</dt>
                            <dd class="col-6 text-end"><?= htmlspecialchars((string)($data['last_login_formatted'] ?? '–')) ?></dd>
                            <dt class="col-6 text-secondary">Mitglied seit</dt>
                            <dd class="col-6 text-end"><?= (int)($data['account_age_days'] ?? 0) ?> Tagen</dd>
                            <?php if (!empty($data['subscription_module_enabled'])): ?>
                                <dt class="col-6 text-secondary">Abo</dt>
                                <dd class="col-6 text-end"><?= htmlspecialchars((string)($subscription->package_name ?? $subscription->name ?? 'Nicht aktiv')) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Letzte Aktivität</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($activity === []): ?>
                            <div class="text-secondary">Noch keine protokollierten Aktivitäten vorhanden.</div>
                        <?php else: ?>
                            <ul class="timeline mb-0">
                                <?php foreach ($activity as $entry): ?>
                                    <li class="timeline-event">
                                        <div class="timeline-event-icon bg-primary-lt">•</div>
                                        <div class="card timeline-event-card">
                                            <div class="card-body py-3">
                                                <div class="text-body fw-medium"><?= htmlspecialchars((string)($entry->description ?? $entry->action ?? 'Aktivität')) ?></div>
                                                <div class="text-secondary small"><?= htmlspecialchars((string)($entry->created_at ?? '')) ?></div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php';
