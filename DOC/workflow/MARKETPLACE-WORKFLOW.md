# Marketplace Workflow – 365CMS

> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell
>
> **Bereich:** Plugin & Theme Marketplace · **Version:** 2.5.4  
> **Admin-Seiten:** `admin/marketplace.php`, `admin/plugins.php`, `admin/themes.php`  
> **Geplantes Feature:** Offizieller Marketplace-Dienst

---
<!-- UPDATED: 2026-03-08 -->

## Übersicht: Marketplace-Typen

| Typ | Quelle | Admin-Seite |
|---|---|---|
| **Plugins** | PLUGINS/ lokal + Remote | `admin/plugins.php` |
| **Themes** | THEMES/ lokal + Remote | `admin/themes.php` |
| **Marketplace** | Remote + Lizenzserver | `admin/marketplace.php` |

---

## Workflow 1: Plugin aus Marketplace installieren

### Via Admin-UI

```
Admin → admin/marketplace.php
    ↓
Plugin suchen / durchsuchen (Kategorie, Schlagwort)
    ↓
Plugin-Details anzeigen:
  - Version, Autor, Beschreibung
  - Abhängigkeiten (Requires: cms-experts >= 1.0.0?)
  - Kompatibilität (Min CMS-Version)
  - Lizenz (Free / Premium)
    ↓
"Installieren" Button
    → CSRF-Token im Request
    → Auth + isAdmin() Prüfung server-seitig
    → Download von autorisierter Quelle
    → SHA-256-Verifikation (gegen update.json)
    → Entpacken in plugins/
    → Activation-Test
    ↓
"Aktivieren" → plugin_activated-Hook
    → DB-Tabellen anlegen (via class-install.php)
    → Admin-Menüeintrag hinzufügen
    → Member-Dashboard-Tab registrieren
```

### Programmatisch

```php
$manager = \CMS\PluginManager::instance();

// 1. Info laden:
$info = $manager->getMarketplaceInfo('cms-bookings');
// → ['version' => '2.0.0', 'download_url' => '...', 'sha256' => '...']

// 2. Herunterladen + verifizieren:
$zipPath = $manager->downloadPlugin($info['download_url']);
$hash    = hash_file('sha256', $zipPath);

if (!hash_equals($info['sha256'], $hash)) {
    throw new \RuntimeException("SHA-256-Verifikation fehlgeschlagen für " . $info['slug']);
}

// 3. Installieren:
$manager->installFromZip($zipPath);

// 4. Aktivieren:
$manager->activatePlugin('cms-bookings');
```

---

## Workflow 2: Plugin deaktivieren / deinstallieren

### Deaktivieren (sicher – Daten bleiben erhalten)

```php
// Admin → admin/plugins.php → "Deaktivieren"
$manager->deactivatePlugin('cms-bookings');
// → Hooks::doAction('plugin_deactivated', 'cms-bookings')
// → Plugin-Klassen werden nicht mehr geladen
// → DB-Tabellen und Daten bleiben
// → Admin-Menüeintrag verschwindet
```

### Deinstallieren (destruktiv – Daten löschen)

```
Admin → admin/plugins.php → Plugin muss zuerst deaktiviert sein!
    ↓
"Deinstallieren" Button
    → Bestätigung via Modal (NICHT window.confirm):
      "Alle Daten von cms-bookings werden unwiderruflich gelöscht.
       [Ich verstehe] [Abbrechen]"
    → Intent-Token + CSRF
    ↓
server-seitig:
    → Hooks::doAction('plugin_uninstall', 'cms-bookings')
    → class-install.php::uninstall() ausführen
      → DROP TABLE cms_bookings
      → delete_option('bookings_settings')
    → Plugin-Verzeichnis löschen
    → Admin-Eintrag aus DB entfernen
```

---

## Workflow 3: Theme aus Marketplace installieren

```
Admin → admin/themes.php → "Themes durchsuchen"
    ↓
Theme auswählen:
  - Vorschaubild (screenshot.jpg, 400×300px)
  - Kompatibilitäts-Info
  - Demo-Link (falls vorhanden)
    ↓
"Installieren"
    → Download + SHA-256-Check
    → Entpacken in themes/
    → theme.json validieren
    → screenshot.jpg vorhanden?
    ↓
"Aktivieren" (separater Schritt!)
    → ThemeManager::activateTheme($slug)
    → Altes Theme bleibt als Fallback erhalten
    → Cache leeren
    → Redirect zu Customizer
```

---

## Workflow 4: Plugin aktualisieren

```
Admin → admin/plugins.php
    ↓
Update-Badge [ ! ] bei veralteten Plugins
    ↓
"Update-Details" → Changelog lesen
    ↓
"Auf v2.1.0 aktualisieren"
    → Backup der aktuellen Version (plugins/cms-bookings.zip)
    → Download neue Version
    → SHA-256 prüfen
    → Altes Verzeichnis umbenennen (cms-bookings.bak)
    → Neue Version entpacken
    → Migrations ausführen (update_migrations.php)
    → Test: Plugin läuft fehlerfrei?
      → ja: .bak-Verzeichnis löschen
      → nein: Rollback auf .bak
```

---

## Workflow 5: Eigenes Plugin zum Marketplace einreichen (Zukunft)

```
Status: Konzept (LOW-Priorität in ROADMAP_FEB2026.md)

Geplanter Prozess:
1. GitHub-Repository mit standardisierter Struktur
2. GitHub Release mit korrektem update.json (SHA-256!)
3. Marketplace-Registrierung via API:
   POST /marketplace/v1/plugins/submit
   → slug, name, description, version, license, category, github_url
4. Automatischer Review:
   - Plugin-Header vollständig?
   - Keine gefährlichen Funktionen (eval, exec...)?
   - SHA-256 im update.json korrekt?
5. Manuelle Freigabe durch Plattform-Admin
6. Listing im Marketplace mit Statistiken
```

---

## Marketplace index.json (lokale Quelle)

```json
// PLUGINS/index.json – Lokaler Plugin-Katalog:
{
    "version": "1.0.0",
    "plugins": [
        {
            "slug":        "cms-experts",
            "name":        "CMS Experts",
            "version":     "1.0.0",
            "description": "Experten-Verzeichnis mit Profilen",
            "category":    "community",
            "license":     "free",
            "requires":    "1.6.0",
            "sha256":      "abc123...",
            "path":        "cms-experts/cms-experts.php"
        }
    ]
}
```

---

## Checkliste: Marketplace-Sicherheit

```
VOR INSTALLATION:
[ ] Quelle vertrauenswürdig? (Offizieller Marketplace / bekannter Entwickler)
[ ] SHA-256 aus update.json verifiziert
[ ] Plugin-Changelog gelesen
[ ] Backup erstellt

NACH INSTALLATION:
[ ] Plugin-Datei syntaktisch korrekt (php -l)
[ ] keine gefährlichen Funktionen enthalten
[ ] Admin-Benutzer kann Plugin wieder deaktivieren

BEIM DEINSTALLIEREN:
[ ] Datensicherung der Plugin-Daten
[ ] Bestätigung über Datenverlust erhalten
[ ] CSRF-Token im Delete-Request vorhanden
```

---

## Referenzen

- [admin/plugins.php](../../CMS/admin/plugins.php) – Plugin-Management
- [admin/marketplace.php](../../CMS/admin/marketplace.php) – Marketplace
- [PLUGIN-DEVELOPMENT-WORKFLOW.md](PLUGIN-DEVELOPMENT-WORKFLOW.md) – Plugin entwickeln
- [PLUGIN-AUDIT.md](../audits/PLUGIN-AUDIT.md) – Sicherheitsanforderungen
- [ROADMAP_FEB2026.md](../feature/ROADMAP_FEB2026.md) – C-11: update.json SHA-256
