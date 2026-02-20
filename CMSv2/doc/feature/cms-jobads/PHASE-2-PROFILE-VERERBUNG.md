# cms-jobads · Phase 2: Profile & Vererbungs-Engine (20 → 40 %)

**Ziel dieser Phase:** Einführung des zentralen **Profil-Systems** für Skills, Benefits und  
Rahmenbedingungen sowie der **Vererbungs-Engine**. Master-Vorlagen können auf Firmen-Ebene  
gepflegt werden, Abteilungen verwenden und verfeinern diese Profile – mit konfigurierbarer  
Weitergabe von Änderungen nach unten.

**Voraussetzung:** Phase 1 vollständig abgenommen  
**Zeitschätzung:** ~6–8 Entwicklungswochen

---

## Inhaltsverzeichnis

1. [Kern-Konzept: Das Profil-System](#1-kern-konzept-das-profil-system)
2. [Vererbungs-Modi im Detail](#2-vererbungs-modi-im-detail)
3. [Änderungs-Propagierung (Master → Kind)](#3-änderungs-propagierung-master--kind)
4. [Skill-Profile & Skill-Katalog](#4-skill-profile--skill-katalog)
5. [Benefits-Profile (vererbbar)](#5-benefits-profile-vererbbar)
6. [Rahmenbedingungen-Profile (vererbbar)](#6-rahmenbedingungen-profile-vererbbar)
7. [Profil-Bibliothek: Verwaltungs-Interface](#7-profil-bibliothek-verwaltungs-interface)
8. [Abteilungs-Nutzung & Verfeinerung](#8-abteilungs-nutzung--verfeinerung)
9. [Vollständiges RBAC](#9-vollständiges-rbac)
10. [Branchen-spezifische Pflichtfelder](#10-branchen-spezifische-pflichtfelder)
11. [Datenbank-Erweiterungen Phase 2](#11-datenbank-erweiterungen-phase-2)
12. [Klassen & Hooks Phase 2](#12-klassen--hooks-phase-2)
13. [Abnahme-Kriterien Phase 2](#13-abnahme-kriterien-phase-2)

---

## 1. Kern-Konzept: Das Profil-System

### 1.1 Was sind „Profile"?

Ein **Profil** ist eine benannte, zentral gepflegte Sammlung von Einstellungen  
(Skills, Benefits oder Rahmenbedingungen), die einer Abteilung oder Position  
als Vorlage dient. Das Profil liegt auf einer **höheren Ebene** (Firma oder Mandant),  
die Kindebenen **nutzen, erben und dürfen verfeinern**.

```
EBENEN-ÜBERSICHT

[Mandant/Firma]  legt Master-Profile an
       │
       │  ← Profil wird zugewiesen
       ▼
[Abteilung A]    verwendet Profil »IT-Entwicklung«
       │         • kann eigene Items ergänzen
       │         • kann Items überschreiben (sofern erlaubt)
       │         • erhält Änderungen von oben (sofern aktiviert)
       ▼
[Position X]     inherits von Abteilung A
                 • kann weiter verfeinern
                 • finale Sammlung = Basis für Stellenanzeige
```

### 1.2 Drei Profil-Typen

| Profil-Typ | Beschreibung | Ebene |
|---|---|---|
| **Skill-Profil** | Gruppe von Skill-Anforderungen (Sprachen, Zertifikate, Hard-/Soft-Skills) | Firma / global |
| **Benefits-Profil** | Vorkonfigurierte Benefit-Sammlung für eine Stellengruppe | Firma / Abteilung |
| **Konditionen-Profil** | Rahmenbedingungen-Vorlage (Vertrag, Zeit, Remote, Gehalt-Spanne) | Firma / Abteilung |

Ein Positions-Profil (aus Phase 1) referenziert je ein Skill-Profil, ein Benefits-Profil  
und ein Konditionen-Profil. Alle drei können separat zugewiesen werden.

---

## 2. Vererbungs-Modi im Detail

Jedes einzelne **Item** in einem Profil (ein Benefit, ein Skill, ein Konditions-Feld)  
trägt einen `inherit_mode`. Dieser steuert, wie Kindebenen das Item behandeln dürfen.

### 2.1 Die drei Vererbungs-Modi

```
MODUS A – FORCED (Erzwungen)
┌────────────────────────────────────────────────────┐
│  Das Item ist auf allen Kindebenen aktiv.           │
│  Kinder KÖNNEN den Wert NICHT überschreiben.        │
│  Änderungen am Master werden SOFORT propagiert.     │
│  Symbol im Interface: 🔒                            │
└────────────────────────────────────────────────────┘

Beispiel: Firma schreibt vor: "Unbefristeter Vertrag für alle Stellen"
→ Abteilung und Position können das nicht wegkonfigurieren.


MODUS B – DEFAULT (Standard, überschreibbar)
┌────────────────────────────────────────────────────┐
│  Das Item ist auf Kindebenen standardmäßig aktiv.   │
│  Kinder DÜRFEN den Wert überschreiben oder          │
│  das Item deaktivieren.                             │
│  Änderungen am Master: Kind erhält "Update         │
│  verfügbar"-Hinweis und kann manuell akzeptieren.   │
│  Symbol im Interface: 🔓                            │
└────────────────────────────────────────────────────┘

Beispiel: Firma setzt "Homeoffice 2 Tage/Woche" als Standard
→ Abteilung Entwicklung überschreibt auf 4 Tage/Woche – bleibt so.
→ Wenn Firma auf 3 Tage ändert: Abteilung sieht Hinweis, entscheidet selbst.


MODUS C – OPTIONAL (Opt-in)
┌────────────────────────────────────────────────────┐
│  Das Item existiert im Katalog, ist auf             │
│  Kindebenen NICHT automatisch aktiv.                │
│  Kinder können es aktivieren und konfigurieren.     │
│  Symbol im Interface: ⬜                            │
└────────────────────────────────────────────────────┘

Beispiel: "Dienstwagen" – bei Firma generell deaktiviert,
aber Abteilung Vertrieb aktiviert es gezielt.
```

### 2.2 Übersichtstabelle

| Modus | Aktiv in Kind | Kind kann überschreiben | Propagierung bei Änderung |
|---|---|---|---|
| `forced` 🔒 | Immer | ❌ Nein | Sofort + automatisch |
| `default` 🔓 | Ja (Standard) | ✅ Ja | Hinweis → manuelle Übernahme |
| `optional` ⬜ | Nein (Standard) | ✅ Ja (Aktivierung durch Kind) | Nur wenn Kind geerbt hat |

### 2.3 Sonderfälle

**Modus-Konflikte:** Wenn Firma ein Item als `forced` setzt, Abteilung aber eine widersprüchliche eigene Einstellung hat → Firma-Wert überschreibt immer. Das Interface zeigt einen orangen Hinweis „Überschrieben durch übergeordnete Ebene".

**Ausnahme-Antrag:** (Optional, Phase 4) Abteilung kann eine Abweichung von einem `forced`-Item anfragen, die ein Firmen-Admin genehmigt.

---

## 3. Änderungs-Propagierung (Master → Kind)

### 3.1 Ablauf bei `forced`-Änderung

```
Firmen-Admin ändert:
"Vertragsart: unbefristet" → FORCED

    → System ermittelt alle Kind-Entitäten:
       Abteilung A, Abteilung B, Abteilung C
       Position X, Position Y (direkt von Firma)
       Alle aktiven Stellenanzeigen mit Status != 'archived'

    → Datenbank-Update in {prefix}jobads_inherited_values
       SET value = 'unbefristet', source_level = 'company', forced = 1
       WHERE entity_type IN ('department','position','job_ad')
         AND item_key = 'contract_type'

    → Audit-Log:
       action = 'forced_propagation'
       details = { from: 'company:5', item: 'contract_type',
                   affected_entities: [dept:3, dept:7, pos:12, ...] }

    → Keine E-Mail / kein UI-Hinweis nötig (da erzwungen)
```

### 3.2 Ablauf bei `default`-Änderung

```
Firmen-Admin ändert:
"Homeoffice: 2 Tage" → DEFAULT (war vorher: 1 Tag)

    → System ermittelt:
       - Ent-itäten, die den Wert nicht überschrieben haben
         → Update sofort (sie hatten den Standard)
       - Entitäten, die den Wert manuell überschrieben haben
         → Kein automatisches Update
         → Eintrag in {prefix}jobads_update_notices:
           { entity_type, entity_id, item_key,
             old_master_value, new_master_value,
             noticed_at: NOW() }

    → Im Admin-Interface der Abteilung:
       ⚠️ Gelber Banner: „Übergeordneter Standard wurde geändert.
          Aktuell: 3 Tage (eigene Einstellung). Neuer Standard: 2 Tage.
          [Übernehmen] [Beibehalten]"
```

### 3.3 Update-Notices Datenbank-Tabelle

```sql
CREATE TABLE {prefix}jobads_update_notices (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type       ENUM('company','department','position','job_ad') NOT NULL,
    entity_id         INT UNSIGNED NOT NULL,
    profile_type      ENUM('conditions','benefits','skills') NOT NULL,
    item_key          VARCHAR(100) NOT NULL,
    old_master_value  JSON,
    new_master_value  JSON,
    noticed_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at       DATETIME DEFAULT NULL,
    resolution        ENUM('accepted','kept') DEFAULT NULL,
    resolved_by       INT UNSIGNED DEFAULT NULL,
    INDEX idx_entity  (entity_type, entity_id),
    INDEX idx_unresolved (resolved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.4 Vererbungs-Resolver (PHP)

```php
// class-inheritance.php (Vereinfacht)
class CMS_JobAds_Inheritance {

    /**
     * Auflösen: alle effektiven Werte für eine Entität,
     * mit Berücksichtigung der Vererbungs-Hierarchie
     */
    public function resolve(string $type, int $id, string $profile_type): array {
        $chain = $this->get_ancestry_chain($type, $id);
        // z. B. ['company:5', 'department:12', 'position:7']

        $resolved = [];
        foreach ($chain as $ancestor) {
            $items = $this->get_profile_items($ancestor, $profile_type);
            foreach ($items as $item) {
                if ($item->inherit_mode === 'forced') {
                    // Immer überschreiben, egal was Kind definiert hat
                    $resolved[$item->item_key] = [
                        'value'  => $item->value_json,
                        'source' => $ancestor,
                        'locked' => true,
                    ];
                } elseif ($item->inherit_mode === 'default') {
                    if (! isset($resolved[$item->item_key])) {
                        // Noch nicht von Kind gesetzt → Standard verwenden
                        $resolved[$item->item_key] = [
                            'value'  => $item->value_json,
                            'source' => $ancestor,
                            'locked' => false,
                        ];
                    }
                    // Wenn Kind bereits eigenen Wert hat → beibehalten
                }
                // 'optional' → nur wenn Kind aktiv gesetzt hat (separat behandelt)
            }
        }
        return $resolved;
    }

    private function get_ancestry_chain(string $type, int $id): array {
        // Gibt Kette von Eltern → Kind zurück:
        // ['company:X', 'department:Y', 'position:Z']
        // Reihenfolge: niedrigste Priorität zuerst (wird oben überschrieben)
    }
}
```

---

## 4. Skill-Profile & Skill-Katalog

### 4.1 Skill-Katalog (Zentrale Liste)

Ein globaler Skill-Katalog mit normierten Einträgen, aus dem Profile zusammengestellt werden.

```sql
CREATE TABLE {prefix}jobads_skills_catalog (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category     VARCHAR(100) NOT NULL,  -- 'hard_skill','soft_skill','language','certification','license'
    branch_key   VARCHAR(100) DEFAULT NULL,  -- NULL = branchenübergreifend
    slug         VARCHAR(150) NOT NULL UNIQUE,
    label        VARCHAR(255) NOT NULL,
    description  VARCHAR(500) DEFAULT NULL,
    is_system    TINYINT(1) DEFAULT 0,
    sort_order   SMALLINT UNSIGNED DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Mitgelieferte Skill-Kategorien:**

| Kategorie | Beispiele |
|---|---|
| **Hard Skills** | PHP 8, Python, SQL, CAD/CAM, Schweißen (MIG/MAG), DATEV, SAP FI |
| **Soft Skills** | Teamfähigkeit, Eigeninitiative, Kommunikationsstärke, Belastbarkeit |
| **Sprachen** | Deutsch (A1–Muttersprache), Englisch, Französisch, Türkisch, … |
| **Zertifikate** | AWS Certified Dev, ITIL 4, Staplerschein, ADR-Schein, AEVO, PSM I |
| **Führerscheine** | A, B, BE, C1, C, CE, T, Staplerschein |
| **Qualifikationen** | Meisterbrief, Gesellenbrief, Hochvolt-HV3, G26, G41, HACCP |

### 4.2 Skill-Profil

Ein **Skill-Profil** fasst mehrere Skill-Einträge zu einer benannten Vorlage zusammen, z. B. „Backend-Dev Junior" oder „LKW-Fahrer CE Fernverkehr".

```sql
CREATE TABLE {prefix}jobads_skill_profiles (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_type    ENUM('system','company','department') DEFAULT 'company',
    owner_id      INT UNSIGNED DEFAULT NULL,  -- NULL = System-Profil
    name          VARCHAR(255) NOT NULL,
    slug          VARCHAR(255) NOT NULL,
    branch_key    VARCHAR(100) DEFAULT NULL,
    description   VARCHAR(500) DEFAULT NULL,
    is_template   TINYINT(1) DEFAULT 0,   -- Vorlage (nicht direkt nutzbar, nur als Basis)
    status        ENUM('active','archived') DEFAULT 'active',
    created_by    INT UNSIGNED DEFAULT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE {prefix}jobads_skill_profile_items (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id        INT UNSIGNED NOT NULL,
    skill_id          INT UNSIGNED NOT NULL,
    requirement_level ENUM('must','should','nice') DEFAULT 'must',
    inherit_mode      ENUM('forced','default','optional') DEFAULT 'default',
    custom_note       VARCHAR(500) DEFAULT NULL,
    sort_order        SMALLINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES {prefix}jobads_skill_profiles(id)   ON DELETE CASCADE,
    FOREIGN KEY (skill_id)   REFERENCES {prefix}jobads_skills_catalog(id)   ON DELETE CASCADE,
    UNIQUE KEY uq_profile_skill (profile_id, skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.3 Skill-Profil verwenden & verfeinern

**Aus Abteilungs-Sicht:**
1. Abteilung wählt ein Skill-Profil aus (von Firma oder System)
2. Profil wird geladen mit allen Items und ihren `inherit_mode`-Werten
3. `forced`-Items: angezeigt, aber grau/gesperrt (keine Bearbeitung möglich)
4. `default`-Items: aktiv, aber Abteilung kann Wert/Level ändern oder Item deaktivieren
5. `optional`-Items: inaktiv angezeigt, Abteilung kann aktivieren und konfigurieren
6. Abteilung kann **eigene Skill-Items ergänzen** (werden mit `optional` im Eltern-Profil nicht sichtbar)

**Eigene Ergänzungen** sind nur in der lokalen Abteilungs-Kopie gespeichert  
(`{prefix}jobads_entity_skill_overrides`), nicht im Master-Profil.

### 4.4 Skill-Override-Tabelle

```sql
CREATE TABLE {prefix}jobads_entity_skill_overrides (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type       ENUM('department','position','job_ad') NOT NULL,
    entity_id         INT UNSIGNED NOT NULL,
    profile_id        INT UNSIGNED NOT NULL,
    skill_id          INT UNSIGNED DEFAULT NULL,  -- NULL = lokal hinzugefügt
    action            ENUM('override','disable','add') NOT NULL,
    requirement_level ENUM('must','should','nice') DEFAULT 'must',
    custom_note       VARCHAR(500) DEFAULT NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_override (entity_type, entity_id, profile_id, skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Benefits-Profile (vererbbar)

### 5.1 Benefits-Profil-Tabellen

```sql
CREATE TABLE {prefix}jobads_benefit_profiles (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_type   ENUM('system','company','department') DEFAULT 'company',
    owner_id     INT UNSIGNED DEFAULT NULL,
    name         VARCHAR(255) NOT NULL,
    slug         VARCHAR(255) NOT NULL,
    description  VARCHAR(500) DEFAULT NULL,
    branch_key   VARCHAR(100) DEFAULT NULL,
    is_template  TINYINT(1) DEFAULT 0,
    status       ENUM('active','archived') DEFAULT 'active',
    created_by   INT UNSIGNED DEFAULT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE {prefix}jobads_benefit_profile_items (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id    INT UNSIGNED NOT NULL,
    benefit_id    INT UNSIGNED NOT NULL,
    inherit_mode  ENUM('forced','default','optional') DEFAULT 'default',
    value_json    JSON DEFAULT NULL,    -- Vorbelegter Wert (Betrag, Text, etc.)
    sort_order    SMALLINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES {prefix}jobads_benefit_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (benefit_id) REFERENCES {prefix}jobads_benefits_catalog(id) ON DELETE CASCADE,
    UNIQUE KEY uq_profile_benefit (profile_id, benefit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5.2 Vordefinierte System-Bundles (Phase 2)

| Bundle-Name | Ziel-Branche | Inhalt (Auszug) |
|---|---|---|
| `it_startup_base` | IT | Homeoffice 4d, MacBook, Weiterbildungsbudget, Konferenzbudget |
| `it_konzern_base` | IT | Firmenwagen, BAV, Jobticket, 30 Tage Urlaub |
| `handwerk_standard` | Handwerk | Werkzeug gestellt, Schutzkleidung, Urlaubsgeld, Weihnachtsgeld |
| `pflege_tvoed` | Gesundheit | Tarifvertrag TVöD-P, Schichtzulagen, VBL, Jobticket |
| `logistik_transport` | Logistik | Tankgutschein, Dienstfahrzeug, Modul-95-Finanzierung |
| `bildung_kirche` | Bildung/Soziales | AVR-Tarif, Zusatzurlaub, Fortbildungs-Budget |
| `kaufmaennisch_kmu` | Kaufmännisch | BAV, 30 Tage Urlaub, Weiterbildung, mobiles Arbeiten |

### 5.3 Benefits-Override-Tabelle

```sql
CREATE TABLE {prefix}jobads_entity_benefit_overrides (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type   ENUM('department','position','job_ad') NOT NULL,
    entity_id     INT UNSIGNED NOT NULL,
    profile_id    INT UNSIGNED NOT NULL,
    benefit_id    INT UNSIGNED DEFAULT NULL,  -- NULL = lokal hinzugefügt
    action        ENUM('override','disable','add') NOT NULL,
    value_json    JSON DEFAULT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_override (entity_type, entity_id, profile_id, benefit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 6. Rahmenbedingungen-Profile (vererbbar)

### 6.1 Konditionen-Profil-Tabellen

```sql
CREATE TABLE {prefix}jobads_condition_profiles (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_type   ENUM('system','company','department') DEFAULT 'company',
    owner_id     INT UNSIGNED DEFAULT NULL,
    name         VARCHAR(255) NOT NULL,
    slug         VARCHAR(255) NOT NULL,
    description  VARCHAR(500) DEFAULT NULL,
    branch_key   VARCHAR(100) DEFAULT NULL,
    is_template  TINYINT(1) DEFAULT 0,
    status       ENUM('active','archived') DEFAULT 'active',
    created_by   INT UNSIGNED DEFAULT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE {prefix}jobads_condition_profile_items (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id     INT UNSIGNED NOT NULL,
    field_key      VARCHAR(100) NOT NULL,   -- z. B. 'contract_type', 'remote_type', 'salary_from'
    inherit_mode   ENUM('forced','default','optional') DEFAULT 'default',
    value_json     JSON NOT NULL,           -- Wert/Konfiguration des Feldes
    display_label  VARCHAR(255) DEFAULT NULL,
    sort_order     SMALLINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES {prefix}jobads_condition_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY uq_profile_field (profile_id, field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 6.2 Beispiel-Profil „IT-Vollzeit-Remote"

```json
{
  "profile_name": "IT-Vollzeit-Remote",
  "items": [
    { "field_key": "contract_type",   "value": "unbefristet", "inherit_mode": "forced"   },
    { "field_key": "employment_degree","value": "vollzeit",   "inherit_mode": "forced"   },
    { "field_key": "remote_type",     "value": "hybrid",      "inherit_mode": "default"  },
    { "field_key": "remote_days_week","value": 3,             "inherit_mode": "default"  },
    { "field_key": "hours_from",      "value": 38,            "inherit_mode": "default"  },
    { "field_key": "hours_to",        "value": 40,            "inherit_mode": "default"  },
    { "field_key": "salary_period",   "value": "jahr",        "inherit_mode": "forced"   },
    { "field_key": "salary_negotiable","value": true,         "inherit_mode": "optional" }
  ]
}
```

### 6.3 Typische Profil-Vorlagen pro Branche

| Profil | Branche | Besondere `forced`-Felder |
|---|---|---|
| `vollzeit_unbefristed_standard` | Alle | contract_type, employment_degree |
| `schichtdienst_3schicht` | Industrie | shift_model (forced), Zuschläge |
| `pflege_tagdienst` | Gesundheit | shift_model, konfession_tarif (forced) |
| `lkw_fernverkehr_ce` | Logistik | driver_licenses (forced: CE, Modul95) |
| `kita_teilzeit` | Bildung | extended_criminal_record (forced: true) |
| `remote_first_it` | IT | remote_type: vollremote (forced) |
| `aussendienstmitarbeiter` | Kaufmännisch | driver_license B (forced), travel (forced) |

---

## 7. Profil-Bibliothek: Verwaltungs-Interface

### 7.1 Neue Admin-Menü-Einträge

```
📋 Stellenanzeigen
   ├── ...
   ├── 📚 Profil-Bibliothek           [NEU in Phase 2]
   │    ├── Skill-Profile             → Liste + Editor
   │    ├── Benefits-Profile          → Liste + Editor
   │    ├── Konditionen-Profile       → Liste + Editor
   │    └── Skill-Katalog             → Skills verwalten
   └── ...
```

### 7.2 Profil-Editor Interface

**Kopfbereich jedes Profil-Editors:**
```
┌─────────────────────────────────────────────────────────┐
│ Profil-Name: [____________]  Branche: [Dropdown___]     │
│ Beschreibung: [_______________________]                  │
│ Eigentümer: ○ System  ● Firma: [Firma-Select]           │
│             ○ Abteilung: [Abt.-Select]                  │
├─────────────────────────────────────────────────────────┤
│ GEERBTE ITEMS (von übergeordnetem Profil)               │
│                                 [Profil auswählen ▼]    │
│  🔒 Vertrag unbefristet          [nicht änderbar]       │
│  🔓 Homeoffice 2 Tage/Woche      [Wert: ___] [Modus ▼] │
│  ⬜ Dienstwagen                  [Aktivieren]           │
├─────────────────────────────────────────────────────────┤
│ EIGENE ITEMS (auf dieser Ebene definiert)               │
│  🔓 Weiterbildungsbudget 1.500 € [Wert: ___] [Modus ▼] │
│  [+ Item hinzufügen aus Katalog]                        │
├─────────────────────────────────────────────────────────┤
│ [Speichern]  [Vorschau Auflösung]  [Duplikieren]        │
└─────────────────────────────────────────────────────────┘
```

### 7.3 Auflösungs-Vorschau

Eine dedizierte Seite „Vererbung simulieren":
- Benutzer wählt Entität (z. B. Abteilung Entwicklung > Position Senior Dev)
- System zeigt vollständig aufgelöste Liste aller effektiven Werte
- Jedes Item zeigt: **woher kommt es** (Firma, Abteilung, Position) + **Modus**
- Konflikte (Kind-Override bei `forced`) werden rot markiert

---

## 8. Abteilungs-Nutzung & Verfeinerung

### 8.1 Profil-Zuweisung an Abteilung

Im Abteilungs-Formular gibt es nun einen Reiter **„Profile"**:

```
[Tab: Basis] [Tab: Profile] [Tab: Kontakte]

── SKILL-PROFIL ────────────────────────────────
Verwendetes Profil: [Backend-Dev Standard ▼]
  → [Profil ansehen und verfeinern]

── BENEFITS-PROFIL ─────────────────────────────
Verwendetes Profil: [IT Startup Base ▼]
  → [Profil ansehen und verfeinern]

── KONDITIONEN-PROFIL ──────────────────────────
Verwendetes Profil: [Vollzeit Remote IT ▼]
  → [Profil ansehen und verfeinern]

[Update-Hinweise: 2 offen ⚠️]  → [Anzeigen]
```

### 8.2 Verfeinerungs-Regeln

| Was die Abteilung DARF | Was die Abteilung NICHT DARF |
|---|---|
| `optional`-Items aktivieren | `forced`-Items deaktivieren |
| `default`-Items überschreiben | `forced`-Item-Werte ändern |
| Eigene Items aus Katalog ergänzen | Das Profil der übergeordneten Firma direkt ändern |
| Ein anderes Profil als Basis wählen | Branchen-Pflichtfelder entfernen |
| Eigene neue Skills außerhalb des Profils hinzufügen | |

### 8.3 Positions-Verfeinerung

Positionen (innerhalb einer Abteilung) erben von **der Abteilung**, nicht direkt von der Firma.  
Das bedeutet: Was die Abteilung überschrieben hat, zeigt die Position bereits in der  
angepassten Version. Die Position kann dann nochmals verfeinern (gleiche Regeln).

```
Firma:       "Homeoffice default: 2 Tage"
Abteilung A: "Homeoffice default: 4 Tage"    ← überschrieben (🔓)
Position X:  erbt "4 Tage"                    ← sieht Abteilungs-Wert als Basis
Position Y:  überschreibt auf "5 Tage (vollremote)"
```

### 8.4 Stellenanzeige-Ebene

Die Stellenanzeige ist die unterste Ebene. Sie erbt von der Position (oder direkt von  
der Abteilung, wenn keine Position zugewiesen). Alle Werte sind im Anzeigen-Editor  
als Auto-befüllung sichtbar, mit Kennzeichnung der Herkunft.

Im Editor erscheint neben jedem Feld:
- 🔒 = von oben erzwungen, nicht änderbar
- 🔓 = geerbt, aber für diese Anzeige änderbar
- ✏️ = auf dieser Ebene manuell gesetzt

---

## 9. Vollständiges RBAC

Phase 2 führt alle Rollen und ihre Capabilities vollständig ein.

### 9.1 Rollen-Erweiterungen

| Rolle | Neu in Phase 2 |
|---|---|
| `jobads_superadmin` | Mandanten anlegen, Roles verwalten, alle Firmen |
| `jobads_agency_admin` | Mehrere Firmen verwalten, Agentur-Pool-Profile |
| `jobads_recruiter` | Zugewiesene Firmen, KEINE Profil-Änderungen |
| `jobads_company_admin` | Alle Firmen-Daten, Profile anlegen/bearbeiten |
| `jobads_dept_manager` | Eigene Abteilung + Profile verfeinern |
| `jobads_authorized` | Anzeigen erstellen für Abteilung (delegiert) |
| `jobads_approver` | Nur freigeben/ablehnen (kommt in Phase 3) |
| `jobads_viewer` | Nur lesen |

### 9.2 Capabilities-Tabelle

| Capability | SA | AA | RC | CA | DM | AU | AP | VW |
|---|---|---|---|---|---|---|---|---|
| `jobads_manage_mandants` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `jobads_manage_companies` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `jobads_manage_departments` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `jobads_manage_profiles` | ✅ | ✅ | ❌ | ✅ | ⚠️ | ❌ | ❌ | ❌ |
| `jobads_create_ads` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| `jobads_publish_ads` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `jobads_approve_ads` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ✅ | ❌ |
| `jobads_view_applications` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| `jobads_view` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

> ⚠️ = nur eigene Abteilung / eigene Profile

---

## 10. Branchen-spezifische Pflichtfelder

Phase 2 führt das **Branchen-Konfigurationssystem** ein. Jede zugewiesene Branche aktiviert ein Set von Pflichtfeldern und Hinweisen.

### 10.1 Branchen-Tabellen

```sql
CREATE TABLE {prefix}jobads_branches (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   INT UNSIGNED DEFAULT NULL,
    key_name    VARCHAR(100) NOT NULL UNIQUE,
    label       VARCHAR(255) NOT NULL,
    icon        VARCHAR(50) DEFAULT NULL,
    sort_order  SMALLINT UNSIGNED DEFAULT 0,
    is_system   TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE {prefix}jobads_branch_field_configs (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_key     VARCHAR(100) NOT NULL,
    field_key      VARCHAR(100) NOT NULL,
    field_label    VARCHAR(255) NOT NULL,
    field_type     ENUM('text','select','multiselect','boolean','date') DEFAULT 'text',
    field_options  JSON DEFAULT NULL,
    is_required    TINYINT(1) DEFAULT 0,
    compliance_hint VARCHAR(500) DEFAULT NULL,   -- z. B. "Pflicht nach §20a IfSG"
    sort_order     SMALLINT UNSIGNED DEFAULT 0,
    UNIQUE KEY uq_branch_field (branch_key, field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 10.2 Validierung im Editor

Wenn eine Stellenanzeige einer Branche zugeordnet ist, prüft das System beim Speichern/Einreichen:
- Alle `is_required = 1`-Felder der Branche müssen ausgefüllt sein
- Compliance-Hinweise werden als Info-Boxen eingeblendet (kein Blocker, sondern Hinweis)

---

## 11. Datenbank-Erweiterungen Phase 2

**Neue Tabellen (zusätzlich zu Phase-1-Tabellen):**

```
{prefix}jobads_skills_catalog            ← Normierter Skill-Katalog
{prefix}jobads_skill_profiles            ← Skill-Profil-Kopf
{prefix}jobads_skill_profile_items       ← Items + inherit_mode
{prefix}jobads_entity_skill_overrides    ← Abteilungs/Positions-Überschreibungen
{prefix}jobads_benefit_profiles          ← Benefits-Profil-Kopf
{prefix}jobads_benefit_profile_items     ← Items + inherit_mode
{prefix}jobads_entity_benefit_overrides  ← Überschreibungen
{prefix}jobads_condition_profiles        ← Konditionen-Profil-Kopf
{prefix}jobads_condition_profile_items   ← Items + inherit_mode
{prefix}jobads_entity_condition_overrides← Überschreibungen
{prefix}jobads_update_notices            ← Hinweise bei default-Änderungen
{prefix}jobads_mandants                  ← Mandanten-Verwaltung
{prefix}jobads_branches                  ← Branchen-Taxonomie
{prefix}jobads_branch_field_configs      ← Branchen-Pflichtfelder
```

**Gesamt Phase 1+2: 22 Tabellen**

---

## 12. Klassen & Hooks Phase 2

**Neue Klassen:**

```
includes/
├── class-inheritance.php          ← Vererbungs-Resolver (Kern-Klasse)
├── class-skill-profiles.php       ← Skill-Profile CRUD + Logik
├── class-benefit-profiles.php     ← Benefits-Profile CRUD + Propagierung
├── class-condition-profiles.php   ← Konditionen-Profile CRUD + Propagierung
├── class-branches.php             ← Branchen + Pflichtfeld-Konfiguration
├── class-mandants.php             ← Mandanten-Verwaltung
└── class-update-notices.php       ← Hinweis-Verwaltung (pending updates)
```

**Neue Hooks Phase 2:**

```php
// Actions
do_action('jobads_profile_created',       $type, $profile_id, $data);
do_action('jobads_profile_updated',       $type, $profile_id, $old, $new);
do_action('jobads_forced_propagation',    $type, $item_key, $affected_entities);
do_action('jobads_update_notice_created', $entity_type, $entity_id, $notice);
do_action('jobads_update_notice_resolved',$notice_id, $resolution);
do_action('jobads_profile_assigned',      $entity_type, $entity_id, $profile_type, $profile_id);
do_action('jobads_skill_override_saved',  $entity_type, $entity_id, $overrides);

// Filters
$resolved = apply_filters('jobads_resolved_skills',     $resolved, $entity_type, $entity_id);
$resolved = apply_filters('jobads_resolved_benefits',   $resolved, $entity_type, $entity_id);
$resolved = apply_filters('jobads_resolved_conditions', $resolved, $entity_type, $entity_id);
$can      = apply_filters('jobads_can_override_item',   $bool, $user_id, $inherit_mode, $entity);
```

---

## 13. Abnahme-Kriterien Phase 2

- [ ] Skill-Katalog befüllt: mind. 50 System-Skills (alle Kategorien)
- [ ] Skill-Profil anlegen, Items mit Vererbungs-Modus konfigurieren
- [ ] Benefits-Profil anlegen mit 7 System-Bundles geladen
- [ ] Konditionen-Profil anlegen mit 7 Branchen-Vorlagen geladen
- [ ] Profil an Firma zuweisen → Abteilung erbt automatisch
- [ ] Abteilung kann `default`-Item überschreiben (gespeichert in Override-Tabelle)
- [ ] Abteilung KANN NICHT `forced`-Item ändern (Formular-Feld deaktiviert/hidden)
- [ ] `optional`-Item durch Abteilung aktivierbar
- [ ] Firmen-Admin ändert `forced`-Item → alle Kind-Entitäten werden sofort aktualisiert
- [ ] Firmen-Admin ändert `default`-Item → Update-Notice nur bei bereits überschriebenen Kindern
- [ ] Update-Notice im Abteilungs-Interface als Banner sichtbar
- [ ] „Übernehmen" und „Beibehalten" bei Update-Notice funktionsfähig
- [ ] Auflösungs-Vorschau zeigt korrekte Herkunft jedes Werts
- [ ] Konflikt-Markierung bei `forced`-Überschreibungsversuch
- [ ] Branchen-Pflichtfelder werden bei Anzeigen-Erstellung eingefordert
- [ ] Vollständiges RBAC: alle 8 Rollen mit korrekten Capabilities
- [ ] Mandanten-Tabelle befüllt und im Admin verwaltbar
- [ ] 22 Tabellen fehlerfrei nach Update-Routine (Installer prüft DB-Version)

---

**→ Zurück zu:** [PHASE-1-FUNDAMENT.md](PHASE-1-FUNDAMENT.md)  
**→ Weiter mit:** [PHASE-3-WORKFLOW-VEROEFFENTLICHUNG.md](PHASE-3-WORKFLOW-VEROEFFENTLICHUNG.md)

*Stand: 19. Februar 2026 · cms-jobads Phase 2/5*
