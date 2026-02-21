# cms-jobads · Phase 1: Fundament (0 → 20 %)

**Ziel dieser Phase:** Das Plugin ist installierbar, die Basisdaten sind pflegbar,  
die erste Stellenanzeige kann manuell erstellt und intern gespeichert werden.  
Keine Veröffentlichung, kein Workflow, keine Vererbung – nur das solide Fundament.

**Voraussetzungen:** 365CMS v2.6+, PHP 8.3+, PDO/MySQL  
**Zeitschätzung:** ~4–6 Entwicklungswochen (Solo-Entwickler)

---

## Inhaltsverzeichnis

1. [Plugin-Grundgerüst](#1-plugin-grundgerüst)
2. [Datenbank-Installation](#2-datenbank-installation)
3. [Rollen & Berechtigungen – Basis](#3-rollen--berechtigungen--basis)
4. [Stammdaten: Firmen & Abteilungen](#4-stammdaten-firmen--abteilungen)
5. [Stammdaten: Positionen & Stellen-Profile](#5-stammdaten-positionen--stellen-profile)
6. [Basis-Benefits-Katalog (flache Liste)](#6-basis-benefits-katalog-flache-liste)
7. [Basis-Rahmenbedingungen](#7-basis-rahmenbedingungen)
8. [Stellenanzeigen-Editor (manuell)](#8-stellenanzeigen-editor-manuell)
9. [Admin-Übersicht & Navigation](#9-admin-übersicht--navigation)
10. [Audit-Log (Basis)](#10-audit-log-basis)
11. [Abnahme-Kriterien Phase 1](#11-abnahme-kriterien-phase-1)
12. [Datenbank-Schema Phase 1](#12-datenbank-schema-phase-1)

---

## 1. Plugin-Grundgerüst

### 1.1 Verzeichnis-Struktur

```
plugins/cms-jobads/
├── cms-jobads.php              # Entry-Point (Singleton, Constants, Autoload)
├── README.md
├── includes/
│   ├── class-installer.php     # DB-Tabellen anlegen + aktualisieren
│   ├── class-roles.php         # Rollen & Capabilities registrieren
│   ├── class-companies.php     # Firmen CRUD
│   ├── class-departments.php   # Abteilungen CRUD
│   ├── class-positions.php     # Positions-Profile CRUD
│   ├── class-benefits.php      # Benefits-Katalog CRUD
│   ├── class-conditions.php    # Rahmenbedingungen CRUD
│   ├── class-job-ads.php       # Stellenanzeigen CRUD
│   └── class-audit-log.php     # Audit-Protokoll
├── admin/
│   ├── class-admin-menu.php    # Menü-Registrierung
│   ├── class-admin-pages.php   # Listen- und Formular-Seiten
│   └── views/                  # PHP-Templates für Admin-Seiten
├── assets/
│   ├── css/jobads-admin.css
│   └── js/jobads-admin.js
└── languages/
    └── cms-jobads-de_DE.po
```

### 1.2 Plugin-Header (cms-jobads.php)

```php
<?php
declare(strict_types=1);
/**
 * Plugin Name: CMS Job Ads
 * Description: Stellenanzeigen Manager & Workflow
 * Version:     1.0.0
 * Author:      365CMS
 * Requires:    2.6.0
 */

if ( ! defined('ABSPATH') ) exit;

define('JOBADS_VERSION',  '1.0.0');
define('JOBADS_DIR',      plugin_dir_path(__FILE__));
define('JOBADS_URL',      plugin_dir_url(__FILE__));
define('JOBADS_DB_VERSION', '1');

class CMS_JobAds {
    private static ?self $instance = null;

    public static function instance(): self {
        if ( is_null(self::$instance) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void {
        require_once JOBADS_DIR . 'includes/class-installer.php';
        require_once JOBADS_DIR . 'includes/class-roles.php';
        require_once JOBADS_DIR . 'includes/class-companies.php';
        require_once JOBADS_DIR . 'includes/class-departments.php';
        require_once JOBADS_DIR . 'includes/class-positions.php';
        require_once JOBADS_DIR . 'includes/class-benefits.php';
        require_once JOBADS_DIR . 'includes/class-conditions.php';
        require_once JOBADS_DIR . 'includes/class-job-ads.php';
        require_once JOBADS_DIR . 'includes/class-audit-log.php';
        require_once JOBADS_DIR . 'admin/class-admin-menu.php';
    }

    private function init_hooks(): void {
        register_activation_hook(__FILE__,   [CMS_JobAds_Installer::class, 'install']);
        register_deactivation_hook(__FILE__,  [CMS_JobAds_Installer::class, 'deactivate']);
        add_action('cms_init', [CMS_JobAds_Roles::class, 'register']);
        add_action('cms_admin_menu', [CMS_JobAds_Admin_Menu::class, 'register']);
    }
}

CMS_JobAds::instance();
```

---

## 2. Datenbank-Installation

### 2.1 Tabellen Phase 1 (8 Tabellen)

```sql
-- Firmen
CREATE TABLE {prefix}jobads_companies (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    address     VARCHAR(255),
    city        VARCHAR(100),
    zip         VARCHAR(20),
    country     CHAR(2) DEFAULT 'DE',
    website     VARCHAR(500),
    logo_id     INT UNSIGNED DEFAULT NULL,
    status      ENUM('active','inactive') DEFAULT 'active',
    created_at  DATETIME  DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME  ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Abteilungen
CREATE TABLE {prefix}jobads_departments (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id           INT UNSIGNED NOT NULL,
    parent_dept_id       INT UNSIGNED DEFAULT NULL,
    name                 VARCHAR(255) NOT NULL,
    slug                 VARCHAR(255) NOT NULL,
    cost_center          VARCHAR(50),
    manager_user_id      INT UNSIGNED DEFAULT NULL,
    status               ENUM('active','inactive') DEFAULT 'active',
    sort_order           SMALLINT UNSIGNED DEFAULT 0,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id)     REFERENCES {prefix}jobads_companies(id)   ON DELETE CASCADE,
    FOREIGN KEY (parent_dept_id) REFERENCES {prefix}jobads_departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Positions-Profile (standardisierte Stellenbeschreibungen)
CREATE TABLE {prefix}jobads_positions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED DEFAULT NULL,    -- NULL = System-Profil
    department_id   INT UNSIGNED DEFAULT NULL,
    title           VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) NOT NULL,
    branch_key      VARCHAR(100) DEFAULT NULL,
    seniority       ENUM('trainee','junior','mid','senior','lead','executive') DEFAULT 'mid',
    tasks_json      JSON,
    req_must_json   JSON,
    req_nice_json   JSON,
    skills_json     JSON,   -- Skill-Tags als Array
    is_system       TINYINT(1) DEFAULT 0,
    status          ENUM('active','draft','archived') DEFAULT 'active',
    created_by      INT UNSIGNED DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id)    REFERENCES {prefix}jobads_companies(id)   ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES {prefix}jobads_departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Benefits-Katalog (globale Definitionen)
CREATE TABLE {prefix}jobads_benefits_catalog (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category     VARCHAR(100) NOT NULL,   -- 'vergütung', 'arbeitszeit', etc.
    slug         VARCHAR(100) NOT NULL UNIQUE,
    label        VARCHAR(255) NOT NULL,
    icon         VARCHAR(100) DEFAULT NULL,
    value_type   ENUM('boolean','amount','range','text','select') DEFAULT 'boolean',
    value_options_json JSON DEFAULT NULL,
    is_system    TINYINT(1) DEFAULT 0,
    sort_order   SMALLINT UNSIGNED DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rahmenbedingungen-Templates (Vorlagen für Firmen/Abteilungen)
CREATE TABLE {prefix}jobads_conditions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type         ENUM('company','department','position','job_ad') NOT NULL,
    entity_id           INT UNSIGNED NOT NULL,
    contract_type       ENUM('unbefristet','befristet','werkvertrag','freelance',
                             'minijob','praktikum','ausbildung','dual') DEFAULT 'unbefristet',
    probation_months    TINYINT UNSIGNED DEFAULT 0,
    hours_from          DECIMAL(4,1) DEFAULT 40.0,
    hours_to            DECIMAL(4,1) DEFAULT 40.0,
    employment_degree   ENUM('vollzeit','teilzeit','geringfügig') DEFAULT 'vollzeit',
    shift_model         ENUM('keine','fruehspaet','dreischicht','vierschicht') DEFAULT 'keine',
    remote_type         ENUM('vor_ort','hybrid','vollremote') DEFAULT 'vor_ort',
    remote_days_week    TINYINT UNSIGNED DEFAULT 0,
    travel_percent      TINYINT UNSIGNED DEFAULT 0,
    driver_licenses_json JSON DEFAULT NULL,
    salary_from         DECIMAL(10,2) DEFAULT NULL,
    salary_to           DECIMAL(10,2) DEFAULT NULL,
    salary_period       ENUM('monat','jahr') DEFAULT 'jahr',
    salary_is_gross     TINYINT(1) DEFAULT 1,
    salary_negotiable   TINYINT(1) DEFAULT 1,
    language_de         ENUM('keine','A2','B1','B2','C1','C2','Muttersprache') DEFAULT 'B2',
    language_en         ENUM('keine','A2','B1','B2','C1','C2') DEFAULT 'keine',
    extra_fields_json   JSON DEFAULT NULL,   -- Branchen-Zusatzfelder
    updated_at          DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stellenanzeigen
CREATE TABLE {prefix}jobads_job_ads (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    department_id   INT UNSIGNED DEFAULT NULL,
    position_id     INT UNSIGNED DEFAULT NULL,
    ref_number      VARCHAR(100) DEFAULT NULL UNIQUE,
    title           VARCHAR(255) NOT NULL,
    teaser          TEXT,
    about_company   TEXT,
    tasks           TEXT,
    req_must        TEXT,
    req_nice        TEXT,
    application_info TEXT,
    contact_user_id INT UNSIGNED DEFAULT NULL,
    start_type      ENUM('sofort','datum') DEFAULT 'sofort',
    start_date      DATE DEFAULT NULL,
    deadline        DATE DEFAULT NULL,
    status          ENUM('draft','archived') DEFAULT 'draft',
    created_by      INT UNSIGNED NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id)    REFERENCES {prefix}jobads_companies(id)   ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES {prefix}jobads_departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id)   REFERENCES {prefix}jobads_positions(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Benefit-Zuweisungen an Entitäten (flach, keine Vererbung in Phase 1)
CREATE TABLE {prefix}jobads_benefit_assignments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    benefit_id  INT UNSIGNED NOT NULL,
    entity_type ENUM('company','department','position','job_ad') NOT NULL,
    entity_id   INT UNSIGNED NOT NULL,
    is_enabled  TINYINT(1) DEFAULT 1,
    value_json  JSON DEFAULT NULL,
    note        VARCHAR(500) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY  uq_assign (benefit_id, entity_type, entity_id),
    FOREIGN KEY (benefit_id) REFERENCES {prefix}jobads_benefits_catalog(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit-Log
CREATE TABLE {prefix}jobads_audit_log (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type  VARCHAR(60) NOT NULL,
    entity_id    INT UNSIGNED NOT NULL,
    action       VARCHAR(100) NOT NULL,
    changed_by   INT UNSIGNED NOT NULL,
    old_json     JSON DEFAULT NULL,
    new_json     JSON DEFAULT NULL,
    ip           VARCHAR(45) DEFAULT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_changed_by (changed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 3. Rollen & Berechtigungen – Basis

In Phase 1 werden **4 Basis-Rollen** angelegt. Vollständiges RBAC folgt in Phase 2.

| Rolle | Capability-Key | Was ist in Phase 1 möglich |
|---|---|---|
| `jobads_company_admin` | `jobads_manage_company` | Firmen, Abteilungen, Positionen verwalten; Anzeigen erstellen/bearbeiten |
| `jobads_recruiter` | `jobads_create_ads` | Anzeigen erstellen und bearbeiten |
| `jobads_viewer` | `jobads_view` | Nur lesen |
| Super-Admin des CMS | *(alle)* | Voller Zugriff |

```php
// class-roles.php (Auszug)
public static function register(): void {
    $roles = [
        'jobads_company_admin' => [
            'label' => __('Jobads Firmen-Admin', 'cms-jobads'),
            'caps'  => [
                'jobads_manage_company' => true,
                'jobads_create_ads'     => true,
                'jobads_view'           => true,
            ],
        ],
        'jobads_recruiter' => [
            'label' => __('Jobads Recruiter', 'cms-jobads'),
            'caps'  => [
                'jobads_create_ads' => true,
                'jobads_view'       => true,
            ],
        ],
        'jobads_viewer' => [
            'label' => __('Jobads Beobachter', 'cms-jobads'),
            'caps'  => ['jobads_view' => true],
        ],
    ];
    foreach ($roles as $key => $data) {
        if ( ! get_role($key) ) {
            add_role($key, $data['label'], $data['caps']);
        }
    }
}
```

---

## 4. Stammdaten: Firmen & Abteilungen

### 4.1 Firmen-Verwaltung (Admin)

**Felder Firmen-Formular:**

| Feld | Typ | Pflicht |
|---|---|---|
| Name | Text | ✅ |
| Slug (URL-Kürzel) | Text (auto-generiert) | ✅ |
| Beschreibung | Textarea | ➖ |
| Adresse (Straße, PLZ, Ort, Land) | Text-Gruppe | ➖ |
| Website | URL | ➖ |
| Logo | Medien-Upload | ➖ |
| Status (Aktiv / Inaktiv) | Select | ✅ |

**Admin-Liste:** Tabellarisch, sortierbar nach Name, filterbar nach Status.

### 4.2 Abteilungen-Verwaltung

**Felder Abteilungs-Formular:**

| Feld | Typ | Pflicht |
|---|---|---|
| Firma (Zuordnung) | Select | ✅ |
| Übergeordnete Abteilung | Select (optional, für Sub-Abteilungen) | ➖ |
| Name | Text | ✅ |
| Kostenstelle | Text | ➖ |
| Abteilungsleiter | User-Select | ➖ |
| Reihenfolge (sort_order) | Zahl | ➖ |
| Status | Select | ✅ |

**Hierarchie-Anzeige:** Verzeichnis-artige Darstellung (Eltern → Kinder) in der Admin-Liste.

---

## 5. Stammdaten: Positionen & Stellen-Profile

### 5.1 Konzept Positions-Profil

Ein Positions-Profil ist eine **wiederverwendbare Vorlage** für eine Stelle. Es enthält standardisierte Texte, Anforderungen und Skill-Tags, aber noch keine konkreten Konditionen.

**Profil-Typen in Phase 1:**
- **System-Profile** – mitgeliefert, schreibgeschützt (30 Vorlagen, 7 Branchen)
- **Firmen-Profile** – vom Firmen-Admin erstellt, firmenspezifisch

### 5.2 Positions-Formular

| Sektion | Felder | Hinweis |
|---|---|---|
| Basis | Titel, Slug, Branche, Seniority-Stufe | Pflichtfelder |
| Zuordnung | Firma (optional), Abteilung (optional) | Leer = global |
| Aufgaben | Wiederholungsfeld (Listeneinträge) | Min. 1, max. 15 |
| Muss-Anforderungen | Wiederholungsfeld | Min. 1, max. 15 |
| Soll-Anforderungen | Wiederholungsfeld | Optional |
| Skills | Tag-Eingabe (Freitext + Autovervollständigung aus Skill-Katalog) | Optional in Phase 1 |
| System-Profil? | Checkbox (nur Super-Admin) | Phase 1 |

### 5.3 Mitgelieferte System-Profile Phase 1 (30 Stück)

| Branche | Titel (Auswahl) |
|---|---|
| IT | Frontend Dev (Junior/Mid/Senior), Backend Dev (Junior/Mid/Senior), Full-Stack Dev (Mid/Senior), DevOps Engineer, IT-Support 1st/2nd Level |
| Kaufmännisch | Buchhalter, HR-Generalist, Vertriebsmitarbeiter Innendienst, Assistent Geschäftsführung, Office Manager |
| Handwerk | Elektriker Geselle/Meister, Kfz-Mechatroniker, Anlagenmechaniker SHK, Zimmermann |
| Logistik | LKW-Fahrer CE, Lagerlogistiker, Disponent Nahverkehr, Kommissionierer |
| Gesundheit | Pflegefachkraft, MFA – Med. Fachangestellte, Physiotherapeut |
| Bildung | Erzieher/in, Sozialarbeiter/in |
| Industrie | CNC-Dreher, Produktionsmitarbeiter |

---

## 6. Basis-Benefits-Katalog (flache Liste)

### 6.1 Was wird in Phase 1 geliefert?

Eine **vordefinierte, editierbare Liste** von Benefits ohne Vererbungs-Logik. Die Zuordnung zu Firmen, Abteilungen, Positionen oder Anzeigen geschieht manuell per Checkbox.

> Vererbung (Profil-System) kommt in **Phase 2**.

### 6.2 Mitgelieferte Benefits (Auswahl pro Kategorie)

**Vergütung & Finanzielles (10 Einträge):**
Grundgehalt-Spanne, Jahresbonus, Urlaubsgeld, Weihnachtsgeld, Betriebliche Altersvorsorge, VWL, Spesen-Regelung, Reisekostenerstattung, Mitarbeiterbeteiligung, Jobrad-Leasing

**Arbeitszeit & Flexibilität (8 Einträge):**
30 Tage Urlaub, Flexible Arbeitszeiten, Homeoffice (Tage/Woche), Gleitzeit, Vertrauensarbeitszeit, Teilzeit möglich, 4-Tage-Woche, Überstunden-Ausgleich

**Entwicklung & Weiterbildung (7 Einträge):**
Weiterbildungsbudget, Zertifizierungs-Förderung, Interne Schulungen, Konferenz-Budget, Sprachkurse, Studienförderung, Mentoring-Programm

**Gesundheit & Wohlbefinden (6 Einträge):**
Private Krankenzusatz, Betriebsarzt, Fitness/Wellpass-Zuschuss, Psychologische Beratung, Ergonomischer Arbeitsplatz, BGM-Programm

**Mobilität (5 Einträge):**
Firmenwagen, ÖPNV-Ticket/Jobticket, Parkplatz, Dienstfahrrad, Umzugs-Zuschuss

**Arbeitsumgebung & Kultur (5 Einträge):**
Moderne Ausstattung, Kantine/Essenszuschuss, Hundefreundliches Büro, Kita-Zuschuss, Firmenevents

**Soziales & Sicherheit (4 Einträge):**
Unbefristeter Vertrag, Tarifvertrag-Bindung, Schutzkleidung gestellt, Werkzeug gestellt

### 6.3 Benefits-Zuweisung (Phase 1 – manuell)

Auf jeder Entitäts-Seite (Firma / Abteilung / Position / Stellenanzeige) erscheint ein **Benefits-Panel**:
- Liste aller aktiven Benefits aus dem Katalog
- Checkbox: aktiviert/deaktiviert
- Optional: Freitext-Wert (z. B. "2.000 €/Jahr" beim Weiterbildungsbudget)
- Hinweis: "Diese Benefits gelten nur für diese Ebene. Vererbung: Phase 2."

---

## 7. Basis-Rahmenbedingungen

Analog zu Benefits: Ein Formular-Block mit den wichtigsten Konditionen, manuell auf jeder Entitäts-Ebene ausfüllbar. Noch keine Vererbungs-Logik.

**Felder des Rahmenbedingungen-Blocks:**

| Feld | Typ | Default |
|---|---|---|
| Vertragsart | Select | unbefristet |
| Probezeit (Monate) | Zahl 0–6 | 0 |
| Wochenstunden von/bis | Dezimal | 40 |
| Beschäftigungsgrad | Select | Vollzeit |
| Schichtmodell | Select | keine |
| Remote-Typ | Select | Vor Ort |
| Homeoffice Tage/Woche | Zahl 0–5 | 0 |
| Reisebereitschaft % | Select (0/25/50/>50) | 0 |
| Führerschein required | Multi-Checkbox | – |
| Sprache Deutsch | Level-Select | B2 |
| Sprache Englisch | Level-Select | keine |
| Gehalt von | Betrag | – |
| Gehalt bis | Betrag | – |
| Gehalt: Zeitraum | Monat/Jahr | Jahr |
| Verhandelbar? | Checkbox | ✅ |

---

## 8. Stellenanzeigen-Editor (manuell)

### 8.1 Erstellungs-Schritte (Phase 1)

```
Schritt 1: Basis
  → Jobtitel (Pflicht)
  → Firma (Pflicht)
  → Abteilung (optional)
  → Positions-Profil auswählen oder leer starten
  → [Wenn Profil gewählt: Daten auto-befüllen, alle Felder weiter editierbar]

Schritt 2: Inhalte
  → Teaser (kurze Zusammenfassung)
  → Über das Unternehmen (auto-befüllt aus Firma, überschreibbar)
  → Aufgaben (Listenfeld)
  → Muss-Anforderungen (Listenfeld)
  → Soll-Anforderungen (Listenfeld)
  → Bewerbungs-Infos (Freitext)

Schritt 3: Konditionen
  → Rahmenbedingungen-Block (Felder wie in §7)
  → Benefits-Panel (Checkboxen wie in §6)
  → Ansprechpartner wählen (User-Select)
  → Eintrittsdatum / Bewerbungsfrist

Schritt 4: Speichern als Entwurf
  [Noch kein Freigabe-Workflow in Phase 1]
```

### 8.2 Editor-Merkmale Phase 1

| Merkmal | Verfügbar in Phase 1 |
|---|---|
| Profil-Befüllung (Auto-Fill) | ✅ |
| Manuelles Überschreiben aller Felder | ✅ |
| Als Entwurf speichern | ✅ |
| Anzeige duplizieren | ✅ |
| Vorschau (internes Layout) | ✅ |
| Live-Preview (Frontend) | ❌ (folgt Phase 3) |
| Freigabe-Workflow | ❌ (folgt Phase 2) |
| Veröffentlichung | ❌ (folgt Phase 2/3) |
| AGG-Check | ❌ (folgt Phase 3) |
| Auto-Gender | ❌ (folgt Phase 3) |

---

## 9. Admin-Übersicht & Navigation

### 9.1 Menü-Struktur

```
📋 Stellenanzeigen               [Hauptmenü-Eintrag]
   ├── 📄 Alle Anzeigen           → Übersicht + Filter
   ├── ➕ Neue Anzeige            → Editor
   ├── 🏢 Firmen                  → Firmen verwalten
   ├── 🏬 Abteilungen             → Abteilungen verwalten
   ├── 📌 Positions-Profile       → Profile verwalten + System-Profile ansehen
   ├── 🎁 Benefits-Katalog        → Katalog pflegen
   └── ⚙️ Einstellungen           → Plugin-Grundeinstellungen
```

### 9.2 Übersichts-Seiten (Tabellen)

Alle Listen-Seiten haben:
- Sortierung nach Spalten (Name, Status, Datum)
- Suchfeld oben
- Status-Filter als Tab-Leiste (Alle / Aktiv / Entwurf / Archiviert)
- Massenaktionen (Löschen, Status ändern)
- Pagination (20 Einträge/Seite, konfigurierbar)

---

## 10. Audit-Log (Basis)

Jede Änderung an folgenden Entitäten wird automatisch protokolliert:

| Entität | Protokollierte Aktionen |
|---|---|
| Firma | erstellt, aktualisiert, status_geändert |
| Abteilung | erstellt, aktualisiert, gelöscht |
| Positions-Profil | erstellt, aktualisiert, archiviert |
| Stellenanzeige | erstellt, aktualisiert, archiviert, dupliziert |
| Benefits-Zuweisung | hinzugefügt, entfernt, wert_geändert |
| Rahmenbedingungen | aktualisiert |

**Aufbewahrung:** 12 Monate (konfigurierbar in den Einstellungen).  
**Admin-Ansicht:** Chronologische Liste mit Filter nach Entität, Benutzer und Zeitraum.

---

## 11. Abnahme-Kriterien Phase 1

Diese Phase gilt als **abgeschlossen**, wenn folgende Punkte erfüllt sind:

- [ ] Plugin installiert und deinstalliert ohne Fehler
- [ ] Alle 8 Datenbank-Tabellen werden korrekt angelegt
- [ ] Rollen werden bei Aktivierung registriert, bei Deaktivierung entfernt
- [ ] Firma anlegen, bearbeiten, (de)aktivieren – CRUD vollständig
- [ ] Abteilung anlegen inkl. Hierarchie (Eltern-Kind) – CRUD vollständig
- [ ] Positions-Profil anlegen (manuell + aus System-Profil) – CRUD vollständig
- [ ] 30 System-Profile sind nach Installation vorhanden
- [ ] Benefits-Katalog anzeigen, Eintrag aktivieren/deaktivieren
- [ ] Rahmenbedingungen-Block auf Firmen-Seite speicherbar
- [ ] Neue Stellenanzeige erstellen (leer und aus Profil), als Entwurf speichern
- [ ] Anzeige duplizieren
- [ ] Admin-Vorschau (internes Layout) anzeigelich korrekt
- [ ] Audit-Log schreibt bei jeder Änderung einen Eintrag
- [ ] Alle PHP-Dateien: `php -l` ohne Fehler
- [ ] Keine SQL-Fehler im Debug-Log
- [ ] CSRF-Schutz (Nonces) bei allen Formular-Aktionen vorhanden

---

## 12. Datenbank-Schema Phase 1

**8 Tabellen, ~45 Spalten gesamt**

```
{prefix}jobads_companies              ← Firmen
{prefix}jobads_departments            ← Abteilungen (mit parent_dept_id)
{prefix}jobads_positions              ← Positions-Profile
{prefix}jobads_benefits_catalog       ← Benefits-Definitionen (global)
{prefix}jobads_conditions             ← Rahmenbedingungen pro Entität
{prefix}jobads_benefit_assignments    ← Benefit ↔ Entität (flach, manuell)
{prefix}jobads_job_ads                ← Stellenanzeigen (nur Entwurf-Status)
{prefix}jobads_audit_log              ← Änderungsprotokoll
```

---

**→ Weiter mit:** [PHASE-2-PROFILE-VERERBUNG.md](PHASE-2-PROFILE-VERERBUNG.md)

*Stand: 19. Februar 2026 · cms-jobads Phase 1/5*
