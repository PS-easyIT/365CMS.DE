7# cms-jobads · Phase 3: Workflow & Veröffentlichung (40 → 60 %)

**Ziel dieser Phase:** Der vollständige Freigabe-Workflow wird eingeführt und  
Stellenanzeigen können tatsächlich veröffentlicht werden – intern auf dem CMS-Jobboard  
sowie extern via XML-Feeds (Indeed, Bundesagentur) und Google Jobs (Schema.org).  
Dazu kommt der Agentur-Modus und das Bewerbungsformular.

**Voraussetzung:** Phase 1 + Phase 2 vollständig abgenommen  
**Zeitschätzung:** ~7–9 Entwicklungswochen

---

## Inhaltsverzeichnis

1. [Freigabe-Workflow](#1-freigabe-workflow)
2. [Status-Modell (vollständig)](#2-status-modell-vollständig)
3. [Workflow-Konfiguration](#3-workflow-konfiguration)
4. [E-Mail-Benachrichtigungen](#4-e-mail-benachrichtigungen)
5. [Agentur-Modus](#5-agentur-modus)
6. [Veröffentlichungs-Engine](#6-veröffentlichungs-engine)
7. [CMS-Jobboard Frontend](#7-cms-jobboard-frontend)
8. [Google Jobs (Schema.org)](#8-google-jobs-schemaorg)
9. [XML-Feeds für externe Portale](#9-xml-feeds-für-externe-portale)
10. [Bewerbungsformular & -eingang](#10-bewerbungsformular--eingang)
11. [AGG-Compliance-Check](#11-agg-compliance-check)
12. [Auto-Gender-Formulierung](#12-auto-gender-formulierung)
13. [Datenbank-Erweiterungen Phase 3](#13-datenbank-erweiterungen-phase-3)
14. [Klassen & Hooks Phase 3](#14-klassen--hooks-phase-3)
15. [Abnahme-Kriterien Phase 3](#15-abnahme-kriterien-phase-3)

---

## 1. Freigabe-Workflow

### 1.1 Warum ein strukturierter Workflow?

In Phase 1 konnten Anzeigen nur als Entwurf gespeichert werden.  
Phase 3 führt den vollständigen **mehrstufigen Freigabeprozess** ein:  
Erstellung → Prüfung → Freigabe → optional Kunden-Freigabe → Veröffentlichung.

### 1.2 Standard-Workflow (direkte Unternehmen)

```
[ENTWURF]
  Ersteller arbeitet an Anzeige
    ↓  „Zur Prüfung einreichen"
[ZUR PRÜFUNG]
  Zugewiesener Prüfer erhält E-Mail
    ↓  „Änderungen einfordern"   ──→  [ENTWURF + Kommentar-Thread]
    ↓  „Freigeben"
[FREIGEGEBEN]
  Anzeige bereit zur Veröffentlichung
    ↓  „Sofort veröffentlichen" oder „Termin eingeben"
[AKTIV]
  Auf gewählten Kanälen live
    ↓  manuell oder automatisch (Ablaufdatum)
[ABGELAUFEN]  oder  [PAUSIERT]  oder  [BESETZT]  oder  [ARCHIVIERT]
```

### 1.3 Agentur-Workflow (mit Kunden-Freigabe)

```
[ENTWURF]
    ↓
[ZUR PRÜFUNG] (interne Agentur-Prüfung)
    ↓ intern freigegeben
[AN KUNDEN GESENDET]
  Kunde erhält Vorschau-Link (kein CMS-Login erforderlich)
    ↓  Kunde lehnt ab    ──→  Kommentar + zurück zu [ENTWURF]
    ↓  Kunde genehmigt
[FREIGEGEBEN]
    ↓
[AKTIV]
```

---

## 2. Status-Modell (vollständig)

```sql
-- Erweiterung der job_ads.status-Spalte um alle Werte
ALTER TABLE {prefix}jobads_job_ads
    MODIFY COLUMN status ENUM(
        'draft',                -- Entwurf (nur lokal)
        'review',               -- Zur internen Prüfung eingereicht
        'changes_requested',    -- Prüfer forderte Überarbeitung
        'intern_approved',      -- Intern freigegeben (bei Agentur: noch nicht an Kunden)
        'awaiting_client',      -- Wartet auf Kunden-Genehmigung (Agentur-Modus)
        'client_rejected',      -- Kunde hat abgelehnt (zurück zu draft)
        'approved',             -- Vollständig freigegeben, bereit zur Veröffentlichung
        'active',               -- Aktuell live auf mind. einem Kanal
        'paused',               -- Temporär pausiert
        'expired',              -- Ablaufdatum überschritten
        'filled',               -- Stelle besetzt
        'archived'              -- Archiviert
    ) DEFAULT 'draft';
```

### 2.1 Status-Übergänge und Berechtigungen

| Von → Nach | Wer darf auslösen | Bedingung |
|---|---|---|
| `draft` → `review` | Ersteller, Abteilungsleiter | Pflichtfelder gefüllt |
| `review` → `changes_requested` | Prüfer, CA | Kommentar Pflicht |
| `review` → `intern_approved` | Prüfer, CA | — |
| `review` → `approved` | CA, SA (ohne Kunden-Schritt) | — |
| `intern_approved` → `awaiting_client` | AA, SA | Nur Agentur-Modus |
| `awaiting_client` → `client_rejected` | System (Kunden-Link) | Kommentar Pflicht |
| `awaiting_client` → `approved` | System (Kunden-Link) | — |
| `changes_requested` → `draft` | System automatisch | — |
| `approved` → `active` | CA, AA, SA | Kanal-Auswahl Pflicht |
| `active` → `paused` | CA, AA, SA, DM | — |
| `paused` → `active` | CA, AA, SA | — |
| `active` → `filled` | CA, AA, SA, DM | — |
| `* → `archived` | CA, AA, SA | — |

---

## 3. Workflow-Konfiguration

### 3.1 Konfigurierbare Workflow-Schritte

Firmen-Admins können den Workflow pro Firma anpassen:

| Option | Beschreibung |
|---|---|
| Kunden-Freigabe-Schritt | Ja / Nein (nur für Agenturen sinnvoll) |
| Anzahl Prüfer | 1 oder mehrere (parallel oder sequenziell) |
| Prüfer-Zuordnung | Global (immer dieselben) oder per Anzeige wählbar |
| Deadline Prüfung | Anzahl Tage, nach denen eine Eskalation erfolgt |
| Eskalation beim Ablauf | E-Mail an übergeordneten Verantwortlichen |
| Kommentar Pflicht bei Ablehnung | Ja / Nein |

```sql
CREATE TABLE {prefix}jobads_workflow_configs (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id              INT UNSIGNED NOT NULL,
    client_approval_enabled TINYINT(1) DEFAULT 0,
    review_mode             ENUM('single','parallel','sequential') DEFAULT 'single',
    default_reviewers_json  JSON DEFAULT NULL,
    review_deadline_days    TINYINT UNSIGNED DEFAULT 5,
    escalation_user_id      INT UNSIGNED DEFAULT NULL,
    reject_comment_required TINYINT(1) DEFAULT 1,
    FOREIGN KEY (company_id) REFERENCES {prefix}jobads_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.2 Freigabe-Protokoll

```sql
CREATE TABLE {prefix}jobads_approvals (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_ad_id    INT UNSIGNED NOT NULL,
    workflow_step VARCHAR(60) NOT NULL,   -- 'review', 'client_approval', etc.
    reviewer_id  INT UNSIGNED DEFAULT NULL,
    client_token VARCHAR(64) DEFAULT NULL,  -- für Kunden-Link (kein Login)
    action       ENUM('approved','rejected','changes_requested') NOT NULL,
    comment      TEXT DEFAULT NULL,
    notified_at  DATETIME DEFAULT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_ad_id) REFERENCES {prefix}jobads_job_ads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.3 Kommentar-Thread pro Anzeige

```sql
CREATE TABLE {prefix}jobads_ad_comments (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_ad_id    INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED DEFAULT NULL,
    client_token VARCHAR(64) DEFAULT NULL,  -- Kommentar via Kunden-Link
    content      TEXT NOT NULL,
    is_internal  TINYINT(1) DEFAULT 1,      -- intern = nur für CMS-User sichtbar
    parent_id    INT UNSIGNED DEFAULT NULL,  -- Threading
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_ad_id) REFERENCES {prefix}jobads_job_ads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. E-Mail-Benachrichtigungen

### 4.1 Trigger-Tabelle

| Trigger | Empfänger | Vorlage |
|---|---|---|
| Anzeige eingereicht | Zugewiesener Prüfer | `review_requested` |
| Änderungen eingefordert | Ersteller | `changes_requested` |
| Intern freigegeben | Firmen-Admin / Agentur | `intern_approved` |
| An Kunden gesendet | Kunden-Kontakt (per Token-Link) | `client_preview` |
| Kunde genehmigt | Firmen-Admin / Agentur | `client_approved` |
| Kunde abgelehnt | Ersteller + Prüfer | `client_rejected` |
| Vollständig freigegeben | Ersteller | `fully_approved` |
| Anzeige veröffentlicht | Ersteller + Abteilungsleiter | `ad_published` |
| Anzeige abgelaufen | Firmen-Admin | `ad_expired` |
| Stelle besetzt | Team (optional) | `ad_filled` |
| Prüf-Deadline überschritten | Eskalations-Kontakt | `review_overdue` |

### 4.2 E-Mail-Vorlagen-Verwaltung

```sql
CREATE TABLE {prefix}jobads_email_templates (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED DEFAULT NULL,   -- NULL = globale Vorlage
    trigger_key  VARCHAR(60) NOT NULL,
    subject      VARCHAR(255) NOT NULL,
    body_html    TEXT NOT NULL,
    body_text    TEXT NOT NULL,
    is_active    TINYINT(1) DEFAULT 1,
    UNIQUE KEY uq_template (company_id, trigger_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Verfügbare Platzhalter in E-Mail-Vorlagen:**
`{ad_title}`, `{company_name}`, `{department_name}`, `{creator_name}`,  
`{reviewer_name}`, `{preview_url}`, `{edit_url}`, `{approve_url}`,  
`{reject_url}`, `{deadline_date}`, `{comment_text}`

### 4.3 Kunden-Link (für Agentur-Modus)

Kunden erhalten einen signierten Token-Link ohne CMS-Login:

```
https://cms.domain.de/jobads/preview?token=abc123xyz
```

Die Token-Seite zeigt:
- Vollständige Anzeigen-Vorschau (im Firmen-Design)
- Kommentar-Feld
- Zwei Buttons: **[Genehmigen]** und **[Änderungen anfordern]**
- Token ist 7 Tage gültig, einmalig verwendbar (nach Aktion ungültig)

---

## 5. Agentur-Modus

### 5.1 Mandanten-Verwaltung

Ein Agentur-Admin (Rolle `jobads_agency_admin`) kann mehrere Kunden-Firmen verwalten.  
Die Trennung erfolgt über die `{prefix}jobads_mandants`-Tabelle (Phase 2).

**Agentur-Dashboard:**
```
┌──────────────────────────────────────────────────────┐
│ 📊 AGENTUR-ÜBERSICHT                                 │
├──────────────────────────────────────────────────────┤
│ Kunde           Aktive Anzeigen  Offen  Wartet       │
│ ─────────────────────────────────────────────────────│
│ Firma A GmbH    ████████  8      3      1 ⏳         │
│ Firma B AG      ████  4         1      0             │
│ Startup C       ██  2           2      0             │
├──────────────────────────────────────────────────────┤
│ ⚠️ 1 Anzeige wartet auf Kunden-Genehmigung (5d alt) │
│ ⏰ 3 Anzeigen laufen in 7 Tagen ab                   │
└──────────────────────────────────────────────────────┘
```

### 5.2 Recruiter-Zuweisung

```sql
CREATE TABLE {prefix}jobads_recruiter_assignments (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    company_id   INT UNSIGNED NOT NULL,
    is_primary   TINYINT(1) DEFAULT 0,
    assigned_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assignment (user_id, company_id),
    FOREIGN KEY (company_id) REFERENCES {prefix}jobads_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5.3 Agentur-Profil-Pool

Agenturen können **mandat-übergreifende Profile** anlegen, die allen Kunden zur Verfügung  
stehen – aber von Kunden nicht direkt bearbeitet werden können.

| Pool-Typ | Beschreibung |
|---|---|
| Agentur-Skill-Profile | Z. B. „Agentur-Standard IT-Dev" – für alle IT-Kunden |
| Agentur-Anzeigen-Vorlagen | Textvorlagen im Agentur-Design |
| Agentur-Konditionen-Basis | Mindestanforderungen, die alle Kunden erben |

---

## 6. Veröffentlichungs-Engine

### 6.1 Veröffentlichungs-Ablauf

```
Anzeige status = 'approved'

Schritt 1: Kanal-Auswahl (Checkboxen)
  ✅ CMS-Jobboard
  ✅ Google Jobs (Schema.org)
  ✅ Indeed XML-Feed
  ✅ Bundesagentur für Arbeit XML
  ➖ StepStone      (Phase 4)
  ➖ LinkedIn Jobs  (Phase 4)

Schritt 2: Zeitplan
  ○ Sofort veröffentlichen
  ● Terminiert: [Datum] [Uhrzeit]
  Ablaufdatum: [optional]

Schritt 3: Bestätigen → status = 'active'
```

### 6.2 Veröffentlichungs-Tabelle

```sql
CREATE TABLE {prefix}jobads_publications (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_ad_id        INT UNSIGNED NOT NULL,
    channel          VARCHAR(60) NOT NULL,
    status           ENUM('pending','scheduled','published','error','expired','withdrawn') DEFAULT 'pending',
    scheduled_at     DATETIME DEFAULT NULL,
    published_at     DATETIME DEFAULT NULL,
    expires_at       DATETIME DEFAULT NULL,
    channel_ref_id   VARCHAR(255) DEFAULT NULL,   -- Externe ID beim Portal
    last_synced_at   DATETIME DEFAULT NULL,
    error_message    TEXT DEFAULT NULL,
    UNIQUE KEY uq_ad_channel (job_ad_id, channel),
    FOREIGN KEY (job_ad_id) REFERENCES {prefix}jobads_job_ads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 6.3 Cron-Job für terminierte Veröffentlichungen

```php
// Registrierung im Plugin-Init
add_action('cms_cron_hourly', [CMS_JobAds_Publisher::class, 'process_scheduled']);

// Methode
public static function process_scheduled(): void {
    global $db;
    $pending = $db->get_results(
        "SELECT * FROM {prefix}jobads_publications
         WHERE status = 'scheduled' AND scheduled_at <= NOW()"
    );
    foreach ($pending as $pub) {
        self::publish_to_channel($pub->job_ad_id, $pub->channel);
    }
}
```

---

## 7. CMS-Jobboard Frontend

### 7.1 Frontend-Routing

```
/jobs/                        → Stellenbörse Übersicht
/jobs/{slug}/                 → Stellenanzeige Detail
/jobs/firma/{company-slug}/   → Alle Stellen einer Firma
/jobs/feed/indeed.xml         → Indeed XML-Feed
/jobs/feed/ba.xml             → Bundesagentur XML-Feed
/jobs/feed/google/            → Google Jobs Sitemap
/jobs/preview/{token}/        → Kunden-Vorschau (tokenbasiert)
/jobs/apply/{job-ad-id}/      → Bewerbungsformular
```

### 7.2 Übersichts-Seite

**Elemente:**
- Suchfeld (Jobtitel + Volltext)
- Filter-Leiste: Branche, Beschäftigungsart, Homeoffice, Standort + Radius
- Ergebnis-Grid (Karten, 12 pro Seite, Pagination)
- Sortierung: Datum, Relevanz *(keine KI-Relevanz)*

**Karten-Layout:**

```
┌─────────────────────────────────────────────────────┐
│ [Firmen-Logo]  Firma GmbH                           │
│ SENIOR BACKEND DEVELOPER                            │
│ 📍 Hamburg  |  💻 Hybrid 3 Tage  |  ∞ Unbefristet  │
│ 🎁 13 Benefits  |  💶 65.000–85.000 €/Jahr         │
│ Erstellt: vor 2 Tagen                [Jetzt bewerben]│
└──────────────────────────────────────────────────────┘
```

### 7.3 Detailseite

```
[Firmen-Banner / Logo]
JOBTITEL (m/w/d)
Firma GmbH · Hamburg + Remote · Vollzeit · Sofort

[Jetzt bewerben] [Merken ♡] [Teilen]

━━━━━━━━━━━━━━━
🏢 ÜBER UNS
[Unternehmenstext]

🎯 DEINE AUFGABEN
• Punkt 1
• Punkt 2

✅ DAS BRINGST DU MIT
Must: ...
Nice-to-have: ...

🔧 SKILLS GEWÜNSCHT
[PHP] [MySQL] [REST-API] [Git]

📐 RAHMENBEDINGUNGEN
┌──────────────┬──────────────────┐
│ Vertragsart  │ Unbefristet      │
│ Arbeitszeit  │ 40h/Woche        │
│ Remote       │ Hybrid 3 Tage    │
│ Gehalt       │ 65–85k €/Jahr    │
│ ...          │ ...              │
└──────────────┴──────────────────┘

🎁 DAS BIETEN WIR
[Icon: 🏠 Homeoffice] [Icon: 📚 Weiterbildung]
[Icon: 🚲 Jobrad]     [Icon: 🏋️ Wellpass]

👤 ANSPRECHPARTNER
Max Mustermann · Recruiting
📧 jobs@firma.de

[JETZT BEWERBEN]
```

---

## 8. Google Jobs (Schema.org)

Jede aktive Stellenanzeige erhält automatisch strukturierte Daten nach Schema.org `JobPosting`.

```php
// class-schema-org.php
public function render_job_posting_schema(int $job_ad_id): string {
    $ad   = CMS_JobAds_Job_Ads::get($job_ad_id);
    $cond = CMS_JobAds_Inheritance::resolve('job_ad', $job_ad_id, 'conditions');
    $comp = CMS_JobAds_Companies::get($ad->company_id);

    $schema = [
        '@context'          => 'https://schema.org/',
        '@type'             => 'JobPosting',
        'title'             => $ad->title,
        'description'       => wp_strip_all_tags($ad->tasks . ' ' . $ad->req_must),
        'datePosted'        => $ad->published_at,
        'validThrough'      => $ad->expires_at ?? '',
        'employmentType'    => $this->map_employment_type($cond['employment_degree']),
        'hiringOrganization' => [
            '@type'  => 'Organization',
            'name'   => $comp->name,
            'sameAs' => $comp->website,
            'logo'   => cms_get_media_url($comp->logo_id),
        ],
        'jobLocation' => [
            '@type'   => 'Place',
            'address' => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $comp->address,
                'addressLocality' => $comp->city,
                'postalCode'      => $comp->zip,
                'addressCountry'  => $comp->country,
            ],
        ],
    ];

    // Salärspanne, falls angegeben:
    if ($cond['salary_from'] ?? null) {
        $schema['baseSalary'] = [
            '@type'    => 'MonetaryAmount',
            'currency' => 'EUR',
            'value'    => [
                '@type'    => 'QuantitativeValue',
                'minValue' => $cond['salary_from'],
                'maxValue' => $cond['salary_to'] ?? $cond['salary_from'],
                'unitText' => strtoupper($cond['salary_period']),
            ],
        ];
    }

    // Remote-Arbeit
    if (($cond['remote_type'] ?? 'vor_ort') !== 'vor_ort') {
        $schema['jobLocationType'] = 'TELECOMMUTE';
    }

    return '<script type="application/ld+json">'
         . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
         . '</script>';
}
```

---

## 9. XML-Feeds für externe Portale

### 9.1 Indeed XML-Feed

Abrufbar unter `/jobs/feed/indeed.xml` (konfigurierbare URL).  
Format nach Indeed Publisher-Spezifikation:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<source>
  <publisher>Unternehmensname</publisher>
  <publisherurl>https://domain.de</publisherurl>
  <job>
    <title><![CDATA[Senior Backend Developer (m/w/d)]]></title>
    <date>2026-02-19</date>
    <referencenumber>JOB-2026-0123</referencenumber>
    <url><![CDATA[https://domain.de/jobs/senior-backend-developer/]]></url>
    <company><![CDATA[Firma GmbH]]></company>
    <city>Hamburg</city>
    <state>Hansestadt Hamburg</state>
    <country>DE</country>
    <postalcode>20095</postalcode>
    <salary>65000-85000 EUR pro Jahr</salary>
    <jobtype>Vollzeit</jobtype>
    <remotetype>Hybrid</remotetype>
    <description><![CDATA[...vollständiger Text...]]></description>
  </job>
</source>
```

### 9.2 Bundesagentur für Arbeit XML

Format nach BA-Schnittstellenspezifikation (JOBNETZ-Standard).

| Pflichtfeld BA | Datenquelle |
|---|---|
| `<stellen-nr>` | `ref_number` |
| `<bezeichnung>` | `title` |
| `<beruf>` | abgeleitet aus `position.branch_key` |
| `<arbeitsort>` | Firmen-Adresse |
| `<beschaeftigungsart>` | `conditions.employment_degree` |
| `<eintrittsdatum>` | `start_date` |
| `<bewerbungsfrist>` | `deadline` |
| `<kontakt>` | `contact_user` |

### 9.3 Feed-Konfiguration

```sql
CREATE TABLE {prefix}jobads_feed_configs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED DEFAULT NULL,  -- NULL = global
    feed_type       ENUM('indeed','ba','google','rss','custom') NOT NULL,
    is_enabled      TINYINT(1) DEFAULT 1,
    feed_token      VARCHAR(64) DEFAULT NULL,   -- für private Feed-URLs
    include_filters_json JSON DEFAULT NULL,     -- nur Stellen mit Status X oder Branche Y
    exclude_filters_json JSON DEFAULT NULL,
    last_generated  DATETIME DEFAULT NULL,
    UNIQUE KEY uq_feed (company_id, feed_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 10. Bewerbungsformular & -eingang

### 10.1 Basis-Bewerbungsformular

```
┌────────────────────────────────────────┐
│ BEWERBUNG: Senior Backend Developer   │
│ Firma GmbH · Hamburg                  │
├────────────────────────────────────────┤
│ Vorname *      [______________]        │
│ Nachname *     [______________]        │
│ E-Mail *       [______________]        │
│ Telefon        [______________]        │
├────────────────────────────────────────┤
│ Anschreiben    [______________]        │
│                [   Textarea   ]        │
├────────────────────────────────────────┤
│ Lebenslauf *   [Datei hochladen  📎]   │
│                PDF, DOC, max. 10 MB    │
│ Weitere Unterlagen [Datei hochladen]   │
├────────────────────────────────────────┤
│ ☒ Ich habe die Datenschutzerklärung   │
│   gelesen und stimme der Verarbeitung  │
│   meiner Daten gemäß DSGVO zu *        │
├────────────────────────────────────────┤
│                      [Bewerbung senden]│
└────────────────────────────────────────┘
```

### 10.2 Bewerbungs-Tabelle (Phase 3)

```sql
CREATE TABLE {prefix}jobads_applications (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_ad_id         INT UNSIGNED NOT NULL,
    first_name        VARCHAR(100) NOT NULL,
    last_name         VARCHAR(100) NOT NULL,
    email             VARCHAR(255) NOT NULL,
    phone             VARCHAR(50) DEFAULT NULL,
    cover_letter      TEXT DEFAULT NULL,
    cv_media_id       INT UNSIGNED DEFAULT NULL,
    attachments_json  JSON DEFAULT NULL,
    source_channel    VARCHAR(100) DEFAULT 'cms_form',
    pipeline_stage    ENUM(
        'eingegangen','ungesichtet','gesichtet',
        'pruefung','vorauswahl',
        'interview_geplant','interview_durch',
        'angebot','besetzt','absage'
    ) DEFAULT 'eingegangen',
    internal_notes    TEXT DEFAULT NULL,
    rating            TINYINT UNSIGNED DEFAULT NULL,
    gdpr_consent      TINYINT(1) DEFAULT 0,
    gdpr_delete_at    DATE DEFAULT NULL,
    applied_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_updated      DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_ad_id) REFERENCES {prefix}jobads_job_ads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 10.3 Eingangs-Verarbeitung

**Nach Absenden des Formulars:**
1. Daten validieren (serverseitig, nie nur clientseitig)
2. Upload-Scan (Dateityp + Größe prüfen, gefährliche Typen blockieren: `exe, js, php ...`)
3. Datensatz in `{prefix}jobads_applications` einfügen
4. `applied_at` + `gdpr_delete_at` (= heute + konfigurierbare Frist, Standard 6 Monate) setzen
5. Bestätigungs-E-Mail an Bewerber (mit Referenznummer)
6. Eingangs-Benachrichtigung an zuständige Person (Ansprechpartner der Anzeige)
7. Bewerbungszähler der Anzeige erhöhen: `UPDATE ... SET application_count = application_count + 1`
8. Audit-Log-Eintrag

### 10.4 DSGVO-Datenlösch-Automatik (Basis)

```sql
-- Cron: täglich ausführen
DELETE FROM {prefix}jobads_applications
WHERE gdpr_delete_at IS NOT NULL
  AND gdpr_delete_at < CURDATE()
  AND pipeline_stage NOT IN ('angebot','besetzt');  -- Laufende Prozesse schützen
```

Zusätzlich: Anzeige im Admin mit Liste "In X Tagen automatisch gelöscht" → Admin kann Frist verlängern oder Datensatz manuell anonymisieren.

---

## 11. AGG-Compliance-Check

Der AGG-Check prüft Anzeigentexte auf potentiell diskriminierende Formulierungen  
(Allgemeines Gleichbehandlungsgesetz §1: Alter, Geschlecht, Herkunft, Religion,  
Behinderung, sexuelle Identität).

### 11.1 Implementierung (regelbasiert, kein KI)

```php
// class-agg-checker.php
class CMS_JobAds_AGG_Checker {

    private array $rules = [];

    public function __construct() {
        // Musterlisten aus Konfigurations-Datei laden
        $this->rules = include JOBADS_DIR . 'data/agg-patterns.php';
    }

    public function check(string $text): array {
        $warnings = [];
        foreach ($this->rules as $rule) {
            foreach ($rule['patterns'] as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $warnings[] = [
                        'category'   => $rule['category'],
                        'severity'   => $rule['severity'],   // 'warning' oder 'info'
                        'found'      => $matches[0],
                        'suggestion' => $rule['suggestion'],
                        'law_ref'    => $rule['law_ref'],
                    ];
                }
            }
        }
        return $warnings;
    }
}
```

**Beispiele aus `agg-patterns.php`:**

```php
return [
    [
        'category'   => 'Alter',
        'severity'   => 'warning',
        'patterns'   => ['/\bjung(?:es|er|e)?\b/i', '/\bbis 35 Jahre\b/i',
                         '/\bBerufseinsteiger\b/i', '/\bStudent\b/i'],
        'suggestion' => 'Verwenden Sie stattdessen: "mit erster Berufserfahrung" oder lassen Sie Altersangaben weg.',
        'law_ref'    => '§1 AGG (Alter)',
    ],
    [
        'category'   => 'Geschlecht',
        'severity'   => 'warning',
        'patterns'   => ['/\bMann\b/i', '/\bFrau\b/i', '/\ber soll\b/i'],
        'suggestion' => 'Verwenden Sie die (m/w/d)-Kennzeichnung im Titel.',
        'law_ref'    => '§1 AGG (Geschlecht)',
    ],
    // ... weitere Regeln
];
```

### 11.2 Integration im Editor

- Check läuft beim Speichern im Hintergrund (AJAX-Request)
- Ergebnis: oranger Banner über dem Editor mit Liste der Hinweise
- Kein hartes Blockieren (can save despite warnings)
- Status-Feld in DB: `agg_warnings_json` in `job_ads`-Tabelle

---

## 12. Auto-Gender-Formulierung

Titel werden automatisch mit Genderkennzeichnung versehen.

### 12.1 Verfügbare Modi

| Modus | Beispiel | Einstellbar auf |
|---|---|---|
| `m/w/d` Klammer | Senior Developer (m/w/d) | Firma, Anzeige |
| Doppelnennung | Entwicklerin / Entwickler | Firma, Anzeige |
| Generic-Maskulinum + Hinweis | Senior Developer¹ | Firma, Anzeige |
| Genderneutral | Fachkraft für Entwicklung | Firma, Anzeige |
| Keine Änderung | (manuell) | Anzeige |

### 12.2 Implementierung

```php
// class-gender-tools.php
class CMS_JobAds_Gender_Tools {

    public function append_gender_label(string $title, string $mode): string {
        return match($mode) {
            'mwd'        => rtrim($title) . ' (m/w/d)',
            'double'     => $this->double_form($title),
            'no_change'  => $title,
            default      => rtrim($title) . ' (m/w/d)',
        };
    }

    private function double_form(string $title): string {
        // Einfache Endungs-Erkennung auf Basis Wörterbuch
        $lookup = include JOBADS_DIR . 'data/gender-lookup.php';
        foreach ($lookup as $pattern => $replacement) {
            if (preg_match($pattern, $title)) {
                return preg_replace($pattern, $replacement, $title);
            }
        }
        return $title . ' (m/w/d)'; // Fallback
    }
}
```

---

## 13. Datenbank-Erweiterungen Phase 3

**Neue Tabellen:**

```
{prefix}jobads_workflow_configs         ← Workflow-Einstellungen pro Firma
{prefix}jobads_approvals                ← Freigabe-Protokoll
{prefix}jobads_ad_comments              ← Kommentar-Threads
{prefix}jobads_email_templates          ← E-Mail-Vorlagen
{prefix}jobads_publications             ← Veröffentlichungen pro Kanal
{prefix}jobads_feed_configs             ← Feed-Konfigurationen
{prefix}jobads_applications             ← Bewerbungen
{prefix}jobads_recruiter_assignments    ← Recruiter ↔ Firma (Agentur)
```

**Geänderte Tabellen:**
- `{prefix}jobads_job_ads`: `status`-Spalte erweitert, `agg_warnings_json` hinzugefügt,
  `view_count`, `apply_click_count`, `published_at`, `expires_at`, `filled_at`

**Gesamt Phase 1+2+3: 30 Tabellen**

---

## 14. Klassen & Hooks Phase 3

**Neue Klassen:**

```
includes/
├── class-workflow.php              ← Status-Übergänge + Berechtigungsprüfung
├── class-approvals.php             ← Freigabe-Protokoll
├── class-notifications.php        ← E-Mail-Versand + Vorlagen-Rendering
├── class-publisher.php             ← Veröffentlichungs-Engine + Cron
├── class-schema-org.php            ← Google Jobs Structured Data
├── class-feed-indeed.php           ← Indeed XML-Generator
├── class-feed-ba.php               ← BA XML-Generator
├── class-applications.php         ← Bewerbungs-CRUD + DSGVO-Cron
├── class-agg-checker.php           ← AGG Compliance Prüfung
├── class-gender-tools.php          ← Auto-Gender-Formulierung
└── class-client-preview.php       ← Tokenbasierte Kunden-Vorschau
```

**Neue Hooks Phase 3:**

```php
// Actions
do_action('jobads_status_changed',         $job_ad_id, $old_status, $new_status, $user_id);
do_action('jobads_ad_submitted_for_review',$job_ad_id, $reviewer_ids);
do_action('jobads_ad_approved',            $job_ad_id, $user_id);
do_action('jobads_ad_rejected',            $job_ad_id, $user_id, $comment);
do_action('jobads_ad_published',           $job_ad_id, $channels);
do_action('jobads_ad_expired',             $job_ad_id);
do_action('jobads_ad_filled',              $job_ad_id);
do_action('jobads_application_received',   $application_id, $job_ad_id);
do_action('jobads_application_stage_changed', $application_id, $old, $new);
do_action('jobads_feed_generated',         $feed_type, $company_id, $count);

// Filters
$html    = apply_filters('jobads_job_ad_detail_html',  $html, $job_ad);
$fields  = apply_filters('jobads_apply_form_fields',   $fields, $job_ad);
$xml     = apply_filters('jobads_feed_indeed_item',    $xml, $job_ad);
$xml     = apply_filters('jobads_feed_ba_item',        $xml, $job_ad);
$schema  = apply_filters('jobads_schema_org_data',     $schema, $job_ad);
$title   = apply_filters('jobads_gender_title',        $title, $mode, $job_ad_id);
$warnings= apply_filters('jobads_agg_warnings',        $warnings, $text, $job_ad_id);
```

---

## 15. Abnahme-Kriterien Phase 3

- [ ] Status-Wechsel `draft → review → approved → active` funktioniert vollständig
- [ ] Berechtigungsprüfung: Recruiter kann NICHT freigeben (403)
- [ ] E-Mail bei jedem Status-Wechsel (alle 11 Trigger-Vorlagen)
- [ ] E-Mail-Vorlagen im Admin bearbeitbar
- [ ] Agentur-Modus: Kunden-Preview-Link generierbar, 7 Tage gültig
- [ ] Kunden-Link: Genehmigen + Ablehnen mit Kommentar funktioniert
- [ ] Eskalations-E-Mail nach konfigurierten Tagen (Cron-Test)
- [ ] Veröffentlichung auf CMS-Jobboard: Anzeige im Frontend sichtbar
- [ ] Terminierte Veröffentlichung: Cron setzt Status zum geplanten Zeitpunkt
- [ ] Auto-Ablauf: Anzeige nach `expires_at` auf Status `expired` gesetzt
- [ ] Schema.org JobPosting im HTML-Quelltext der Detailseite vorhanden
- [ ] Google Search Console: kein Fehler im Rich-Result-Test
- [ ] Indeed XML-Feed valide (gegen Indeed XSD validiert)
- [ ] BA XML-Feed vorhanden und strukturell korrekt
- [ ] Bewerbungsformular: alle Pflichtfelder validiert (server-side)
- [ ] Bewerbungs-Upload: gefährliche Dateitypen werden abgewiesen
- [ ] Bestätigungs-E-Mail an Bewerber wird gesendet
- [ ] Eingangs-Benachrichtigung an Ansprechpartner wird gesendet
- [ ] DSGVO-Cron: `gdpr_delete_at` wird korrekt gesetzt und abgelaufen gelöscht
- [ ] AGG-Check: mind. 5 Testmuster werden erkannt und als Warnung angezeigt
- [ ] Auto-Gender: alle 4 Modi funktionieren korrekt
- [ ] Bewerbungs-Liste im Admin: Pagination, Filterbig status, Notizen speicherbar
- [ ] 30 Tabellen fehlerfrei, keine SQL-Warnungen im Debug-Log

---

**→ Zurück zu:** [PHASE-2-PROFILE-VERERBUNG.md](PHASE-2-PROFILE-VERERBUNG.md)  
**→ Weiter mit:** [PHASE-4-PROFI-FEATURES.md](PHASE-4-PROFI-FEATURES.md)

*Stand: 19. Februar 2026 · cms-jobads Phase 3/5*
