# Cookie & Compliance Management

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Dieses Modul stellt die DSGVO-Konformität (GDPR) des CMS sicher und bietet Tools zur Verwaltung von Cookie-Einwilligungen, Datenauskunfts-Anfragen und Datenlöschungen.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Cookie Scanner & Consent Banner](#2-cookie-scanner--consent-banner)
3. [Datenauskunft (Art. 15 DSGVO)](#3-datenauskunft-art-15-dsgvo)
4. [Datenlöschung (Art. 17 DSGVO)](#4-datenlöschung-art-17-dsgvo)
5. [Einwilligungs-Protokoll](#5-einwilligungs-protokoll)
6. [Impressum & Datenschutzerklärung](#6-impressum--datenschutzerklärung)
7. [Technische Details](#7-technische-details)

---

## 1. Überblick

**Datei-Übersicht:**

| Datei | Funktion |
|---|---|
| `admin/cookies.php` | Cookie-Scanner und Consent-Banner-Konfiguration |
| `admin/data-access.php` | Datenauskunft-Anfragen bearbeiten |
| `admin/data-deletion.php` | Datenlöschungs-Anfragen bearbeiten |

Alle drei Bereiche sind unter `Admin → Legal & DSGVO` zusammengefasst.

---

## 2. Cookie Scanner & Consent Banner

**Datei:** `admin/cookies.php`

### Cookie-Scanner
- **Automatischer Scan:** Durchsucht die Website nach gesetzten Cookies
- **Kategorisierung:** Ordnet Cookies automatisch zu:
  - `necessary` – Session-Cookie, CSRF-Token, Auth-Cookie
  - `statistics` – Besucherstatistiken, Analytics
  - `marketing` – Tracking-Pixel, Retargeting
  - `preferences` – Theme-Einstellungen, Sprachauswahl

### Consent Banner Konfiguration

| Einstellung | Beschreibung |
|---|---|
| `banner_title` | Überschrift des Banners |
| `banner_text` | Erklärungs-Text |
| `accept_all_label` | Button-Text „Alle akzeptieren" |
| `reject_label` | Button-Text „Nur notwendige" |
| `customize_label` | Button-Text „Einstellungen" |
| `position` | `bottom-bar`, `bottom-right`, `center-modal` |
| `privacy_link` | Link zur Datenschutzerklärung |
| `category_stats` | Statistik-Cookies ein-/ausblenden |
| `category_marketing` | Marketing-Cookies ein-/ausblenden |

**Cookie-Lebensdauer für Zustimmung:** Standard 365 Tage (konfigurierbar)

---

## 3. Datenauskunft (Art. 15 DSGVO)

**Datei:** `admin/data-access.php`

Bearbeitung von Anfragen auf Auskunft über gespeicherte Daten:

1. **Anfragenliste:** Tabelle aller eingegangenen Export-Anfragen
2. **Anfrage bearbeiten:**
   - Benutzer identifizieren (via E-Mail oder Benutzer-ID)
   - Gespeicherte Datenkategorien anzeigen
   - Export als **JSON** oder **XML** generieren
3. **Anfrage schließen:** Nach Übermittlung als „Erledigt" markieren

**Frist:** DSGVO schreibt Bearbeitung innerhalb von **30 Tagen** vor. System zeigt ablaufende Fristen als Warnung.

**Exportierte Datenkategorien:**
- Profildaten (Name, E-Mail, Telefon)
- Nutzungslog (Logins, Aktivitäten)
- Bestellungen und Rechnungen
- Nachrichten (Metadaten)
- Hochgeladene Dateien (Auflistung)

---

## 4. Datenlöschung (Art. 17 DSGVO)

**Datei:** `admin/data-deletion.php`

Bearbeitung von „Recht auf Vergessenwerden"-Anfragen:

### Löschoptionen

| Option | Beschreibung |
|---|---|
| **Sofort-Löschung** | Alle personenbezogenen Daten sofort löschen |
| **Anonymisierung** | Daten anonymisieren (Pflichtfelder erhalten, PII entfernt) |
| **Geplante Löschung** | Datum setzen für zukünftige automatische Löschung |

### Was wird gelöscht vs. anonymisiert

**Gelöscht:** Profildaten, Avatarbild, Login-Log, Benachrichtigungen, Mediendateien  
**Anonymisiert:** Bestellungen (User-ID → 0, Name → „Gelöschter Nutzer"), publizierte Beiträge  
**Behalten:** Rechnungsdaten (steuerliche Aufbewahrungspflicht 10 Jahre, § 147 AO)

### Protokoll
- Alle durchgeführten Löschungen werden in `cms_gdpr_log` protokolliert
- Protokolleintrag enthält: Datum, Admin, Anfrage-Typ, betroffene User-ID (anonymisiert)

---

## 5. Einwilligungs-Protokoll

Das System speichert alle Einwilligungsentscheidungen der Besucher:

```sql
CREATE TABLE cms_consent_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id  VARCHAR(64),      -- Anonymisiert (Hash der IP)
    ip_hash     VARCHAR(64),
    categories  JSON,             -- {"necessary":1,"statistics":0,"marketing":0}
    action      VARCHAR(20),      -- accept_all, reject_all, custom
    banner_ver  VARCHAR(10),      -- Version des Consent-Banners
    ts          DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

Protokoll ist nur für interne Compliance-Zwecke abrufbar, nicht öffentlich.

---

## 6. Impressum & Datenschutzerklärung

Quick-Links zu den Pflichtseiten im CMS:
- **Impressum:** Prüft ob eine Seite mit Slug `impressum` existiert
- **Datenschutzerklärung:** Prüft ob Seite `datenschutz` existiert
- **Warnung:** Gelb-Banner wenn Pflichtseiten fehlen oder nicht veröffentlicht

---

## 7. Technische Details

**Service:** `CMS\Compliance\GDPRService`

```php
// Daten exportieren
$gdpr = GDPRService::instance();
$exportPath = $gdpr->generateExport($userId, 'json');

// Daten löschen
$gdpr->deleteUserData($userId, $options = [
    'delete_media'  => true,
    'anonymize_orders' => true,
    'keep_billing' => true,
]);

// Einwilligung speichern
$gdpr->saveConsent($visitorHash, [
    'necessary'  => 1,
    'statistics' => $statsAccepted,
    'marketing'  => $marketingAccepted,
]);
```

**Hooks:**
```php
do_action('cms_gdpr_export_generated', $userId, $exportPath);
do_action('cms_gdpr_user_deleted', $userId, $anonymizedId);
add_filter('cms_gdpr_export_data', 'my_plugin_add_export_data', 10, 2);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
