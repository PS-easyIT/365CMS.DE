<?php
declare(strict_types=1);

/**
 * Support & Dokumentation-Modul
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class SupportModule
{
    private const DOCS = [
        [
            'title'       => 'Erste Schritte',
            'description' => 'Installation, Konfiguration und erste Seiten erstellen.',
            'icon'        => 'rocket',
            'links'       => [
                ['label' => 'Installationsanleitung', 'url' => '#install'],
                ['label' => 'Erste Konfiguration',    'url' => '#config'],
                ['label' => 'Erste Seite erstellen',   'url' => '#first-page'],
            ],
        ],
        [
            'title'       => 'Themes',
            'description' => 'Theme-Entwicklung, Anpassung und Customizer.',
            'icon'        => 'palette',
            'links'       => [
                ['label' => 'Theme-Übersicht',        'url' => '#theme-overview'],
                ['label' => 'Theme-Entwicklung',      'url' => '#theme-dev'],
                ['label' => 'Customizer-API',         'url' => '#customizer'],
                ['label' => 'Komponenten-Referenz',   'url' => '#components'],
            ],
        ],
        [
            'title'       => 'Plugins',
            'description' => 'Plugin-Entwicklung, Hooks und API-Referenz.',
            'icon'        => 'puzzle',
            'links'       => [
                ['label' => 'Plugin-Architektur',  'url' => '#plugin-arch'],
                ['label' => 'Hooks & Filter',      'url' => '#hooks'],
                ['label' => 'Datenbank-Zugriff',   'url' => '#database'],
                ['label' => 'Eigenes Plugin',       'url' => '#create-plugin'],
            ],
        ],
        [
            'title'       => 'Sicherheit',
            'description' => 'Sicherheits-Best-Practices und CSRF/XSS-Schutz.',
            'icon'        => 'shield-check',
            'links'       => [
                ['label' => 'CSRF-Schutz',        'url' => '#csrf'],
                ['label' => 'XSS-Prävention',     'url' => '#xss'],
                ['label' => 'Passwort-Policy',    'url' => '#passwords'],
                ['label' => 'DSGVO-Konformität',  'url' => '#dsgvo'],
            ],
        ],
    ];

    private const FAQ = [
        [
            'question' => 'Wie setze ich das Admin-Passwort zurück?',
            'answer'   => 'Verwenden Sie die Passwort-Vergessen-Funktion auf der Login-Seite oder aktualisieren Sie den Eintrag direkt in der Datenbank-Tabelle cms_users.',
        ],
        [
            'question' => 'Warum werden meine CSS-Änderungen nicht angezeigt?',
            'answer'   => 'Leeren Sie den Browser-Cache (Strg+Shift+R) und den CMS-Cache unter Diagnose → Datenbank → Cache leeren.',
        ],
        [
            'question' => 'Wie installiere ich ein neues Plugin?',
            'answer'   => 'Laden Sie das Plugin in den Ordner /plugins/ hoch und aktivieren Sie es im Admin-Bereich unter Plugins.',
        ],
        [
            'question' => 'Wie erstelle ich ein Backup?',
            'answer'   => 'Gehen Sie zu Backup & Restore und klicken Sie auf "Vollständiges Backup" für ein komplettes Backup inkl. Dateien und Datenbank.',
        ],
        [
            'question' => 'Wie aktiviere ich den Wartungsmodus?',
            'answer'   => 'Unter Einstellungen → Wartungsmodus aktivieren. Administratoren haben weiterhin Zugriff.',
        ],
    ];

    /**
     * Daten für die Support-Seite
     */
    public function getData(): array
    {
        return [
            'docs'    => self::DOCS,
            'faq'     => self::FAQ,
            'version' => defined('CMS_VERSION') ? CMS_VERSION : '?.?.?',
        ];
    }
}
