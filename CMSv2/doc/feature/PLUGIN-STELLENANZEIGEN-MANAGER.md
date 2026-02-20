# Plugin-Dokumentation: Stellenanzeigen Manager & Workflow

**Plugin-Name:** `cms-jobads`  
**Version:** 1.0.0 (Konzept)  
**Kategorie:** Commerce & Business / HR & Recruiting  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## Inhaltsverzeichnis

1. [Konzept & Zielsetzung](#1-konzept--zielsetzung)
2. [Nutzergruppen & Rollen](#2-nutzergruppen--rollen)
3. [Datenmodell & Hierarchie](#3-datenmodell--hierarchie)
4. [Gewerke & Branchenkonfiguration](#4-gewerke--branchenkonfiguration)
5. [Stellen-Profile & Vorlagen](#5-stellen-profile--vorlagen)
6. [Benefits-System](#6-benefits-system)
7. [Rahmenbedingungen-System](#7-rahmenbedingungen-system)
8. [Stellenanzeigen-Erstellung (Workflow)](#8-stellenanzeigen-erstellung-workflow)
9. [Agentur-Modus](#9-agentur-modus)
10. [Freigabe-Workflow](#10-freigabe-workflow)
11. [Veröffentlichungs-Kanäle](#11-veröffentlichungs-kanäle)
12. [Frontend & Kandidaten-Sicht](#12-frontend--kandidaten-sicht)
13. [Bewerbungs-Management](#13-bewerbungs-management)
14. [Analytics & Reporting](#14-analytics--reporting)
15. [Datenbank-Schema](#15-datenbank-schema)
16. [Plugin-Architektur & Hooks](#16-plugin-architektur--hooks)
17. [Ausbaustufen nach Priorität](#17-ausbaustufen-nach-priorität)
18. [Integrations-Überlegungen](#18-integrations-überlegungen)

---

## 1. Konzept & Zielsetzung

### Vision
Ein vollständiger Lebenszyklus-Manager für Stellenanzeigen – von der Vorlage bis zur Besetzung. Das Plugin vereint zwei Hauptanwendungsszenarien:

**Szenario A – Agentur-gestützte Erstellung:**
Eine Personalvermittlungsagentur verwaltet mehrere Kundenunternehmen. Ihre Recruiter erstellen Stellenanzeigen aus einem zentralen Vorlagen-Pool heraus und veröffentlichen diese im Auftrag der Kundenunternehmen – inklusive Freigabe-Schleife durch den Kunden.

**Szenario B – Unternehmens-interner Workflow:**
Ein Unternehmen (Konzern, KMU, Behörde, Handwerksbetrieb) erstellt Stellenanzeigen intern. Abteilungsleiter oder bevollmächtigte Mitarbeiter initiieren den Prozess, die HR-Abteilung verfeinert und gibt frei, die Geschäftsführung genehmigt optional.

### Kern-Prinzipien
- **Vererbungs-Prinzip:** Einstellungen (Benefits, Rahmenbedingungen, Design) vererben sich von oben nach unten: Mandant → Firma → Abteilung → Position → Stellenanzeige
- **Überschreib-Prinzip:** Jede Ebene kann geerbte Einstellungen gezielt überschreiben
- **Vorlagen-Prinzip:** Stellenanzeigen entstehen aus Profil-Vorlagen, keine wiederholte Dateneingabe
- **Workflow-Prinzip:** Jede Anzeige durchläuft definierte Status-Stufen mit klaren Zuständigkeiten

---

## 2. Nutzergruppen & Rollen

### 2.1 Rollen-Übersicht

| Rolle | Kurzname | Beschreibung |
|---|---|---|
| **Super-Admin** | `jobads_superadmin` | Voller Zugriff, Mandanten-Verwaltung |
| **Agentur-Admin** | `jobads_agency_admin` | Verwaltet alle Kunden-Firmen der Agentur |
| **Agentur-Recruiter** | `jobads_recruiter` | Erstellt Anzeigen für zugewiesene Firmen |
| **Firmen-Admin** | `jobads_company_admin` | HR-Leitung, voller Firmen-Zugriff |
| **Abteilungsleiter** | `jobads_dept_manager` | Initiiert Stellen für eigene Abteilung |
| **Bevollmächtigter** | `jobads_authorized` | Delegierter mit eingeschränktem Erstell-Recht |
| **Freigeber** | `jobads_approver` | Nur Freigabe/Ablehnen, kein Erstellen |
| **Beobachter** | `jobads_viewer` | Nur lesen, keine Änderungen |
| **Kandidat** | `jobads_candidate` | Bewerbungs-Tracking im Frontend |

### 2.2 Rechte-Matrix

| Aktion | Super-Admin | Agentur-Admin | Recruiter | Firmen-Admin | Abt.-Leiter | Bevollm. |
|---|---|---|---|---|---|---|
| Mandanten verwalten | ✅ | ➖ | ➖ | ➖ | ➖ | ➖ |
| Firmen anlegen | ✅ | ✅ | ➖ | ➖ | ➖ | ➖ |
| Abteilungen anlegen | ✅ | ✅ | ➖ | ✅ | ➖ | ➖ |
| Positionen verwalten | ✅ | ✅ | ⚠️ | ✅ | ⚠️ | ➖ |
| Anzeige erstellen | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Anzeige freigeben | ✅ | ✅ | ➖ | ✅ | ➖ | ➖ |
| Vorlagen verwalten | ✅ | ✅ | ⚠️ | ✅ | ➖ | ➖ |
| Benefits konfigurieren | ✅ | ✅ | ➖ | ✅ | ⚠️ | ➖ |
| Rahmenbedingungen | ✅ | ✅ | ➖ | ✅ | ⚠️ | ➖ |
| Veröffentlichen | ✅ | ✅ | ➖ | ✅ | ➖ | ➖ |
| Analytics sehen | ✅ | ✅ | ⚠️ | ✅ | ⚠️ | ➖ |
| Bewerber sehen | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

> ⚠️ = eingeschränkt (nur eigene Abteilung / nur zugewiesene Firmen)

### 2.3 Delegations-System
**Beschreibung:** Abteilungsleiter können Befugnisse an einzelne Mitarbeiter delegieren.

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Ein-zu-Ein-Delegation (Abteilungsleiter → Mitarbeiter X) | 🟠 High |
| Stufe 2 | Zeitbegrenzte Delegation (Urlaubs-Vertretung, Ablauf-Datum) | 🟠 High |
| Stufe 3 | Scope-begrenzte Delegation (nur für Stelle Y) | 🟡 Mittel |
| Stufe 4 | Delegations-Protokoll (wer hat wann delegiert) | 🔴 Kritisch |
| Stufe 5 | Automatischer Delegation-Entzug nach X Tagen | 🟡 Mittel |

---

## 3. Datenmodell & Hierarchie

### 3.1 Hierarchie-Ebenen

```
Mandant (Agentur oder Holding)
└── Firma (Kundenunternehmen oder Tochtergesellschaft)
    ├── Firmen-Profil (Daten, Branding, Benefits, Rahmenbedingungen)
    └── Abteilung(en)
        ├── Abteilungs-Profil (Daten, spezifische Benefits, Rahmenbedingungen)
        ├── Abteilungsleiter / Bevollmächtigte
        └── Position(en) (standardisierte Stellenbeschreibung)
            ├── Positions-Profil (Anforderungen, Aufgaben, Skills)
            ├── Benefits-Überschreibung
            ├── Rahmenbedingungen-Überschreibung
            └── Stellenanzeige(n) (konkretes Ausschreib-Dokument)
                ├── Workflow-Status
                ├── Veröffentlichungs-Kanäle
                └── Bewerbungen
```

### 3.2 Vererbungs-Logik

```
Firmen-Benefit "Betriebliche Altersvorsorge" (global aktiviert)
  ↓ vererbt an alle Abteilungen
  Abteilung Entwicklung: erbt ✅
  Abteilung Auslieferung: ÜBERSCHREIBT auf ❌ (nur Vollzeit-Stellen)
    ↓ vererbt an alle Positionen in Auslieferung
    Position "Fahrer (TZ)": erbt ❌
    Position "Fuhrpark-Manager": ÜBERSCHREIBT auf ✅ (Führungsposition)
```

**Vererbungs-Priorität (von stark nach schwach):**
1. Stellenanzeige (höchste Priorität, explizite Überschreibung)
2. Position
3. Abteilung
4. Firma
5. Mandant / Agentur-Vorlage (niedrigste, globale Default-Werte)

### 3.3 Datenbank-Kern-Entitäten

```
cms_jobads_mandants          - Mandanten (Agenturen, Holdings)
cms_jobads_companies         - Firmen
cms_jobads_departments       - Abteilungen
cms_jobads_positions         - Positions-Profile
cms_jobads_job_ads           - Stellenanzeigen (konkrete Ausschreibungen)
cms_jobads_benefits          - Benefit-Definitionen
cms_jobads_benefit_assignments  - Benefits auf Ebene (Firma/Abt/Position)
cms_jobads_conditions        - Rahmenbedingungen-Definitionen
cms_jobads_condition_assignments
cms_jobads_templates         - Anzeigen-Vorlagen
cms_jobads_workflows         - Workflow-Definitionen
cms_jobads_workflow_steps    - Workflow-Schritte
cms_jobads_approvals         - Freigabe-Protokoll
cms_jobads_publications      - Veröffentlichungs-Einträge (pro Kanal)
cms_jobads_applications      - Bewerbungen
cms_jobads_contacts          - Zuständige Personen pro Ebene
cms_jobads_audit_log         - Alle Änderungen protokolliert
```

---

## 4. Gewerke & Branchenkonfiguration

### 4.1 Branchen-Taxonomie

Das Plugin liefert eine konfigurierbare Branchen-Taxonomie mit vordefinierten Gewerken. Jede Branche aktiviert branchen-spezifische Felder, Pflichtangaben und Vorlagen.

#### Branche: 🔧 Handwerk & Bau

| Gewerk | Typische Positionen | Besonderheiten |
|---|---|---|
| Elektroinstallation | Elektriker, Meister, Projektleiter | Schein-Pflicht (DGUV), Spannungsarbeiten-Qualifikation |
| Sanitär-Heizung-Klima (SHK) | Anlagenmechaniker, Meister | Gas-Wasserinstallateur-Schein |
| Zimmerei & Dachdeckerei | Zimmermann, Dachdeckergeselle | Höhentauglichkeit, Führerschein BE |
| Trockenbau & Innenausbau | Trockenbauer, Fliesenleger | Staubbindende Arbeiten, Asbest-Schulung |
| Maler & Lackierer | Maler, Pulverbeschichter | Lösungsmittel-Qualifikation |
| Kfz-Mechatronik | Kfz-Mechatroniker, Karosseriebauer | Hochvolt-Zertifizierung (Elektro-Fahrzeuge) |
| Tischlerei & Schreiner | Schreiner, CNC-Fachkraft | Maschinenführer-Schein |
| Garten & Landschaftsbau | Gärtner, Maschinist | Pflanzenschutz-Sachkunde |

**Branchen-spezifische Pflichtfelder (Handwerk):**
- Gesellenbrief / Meisterbrief (Pflichtprüfung ja/nein)
- Führerscheinklassen (dropdown: A, B, BE, C1, C1E, C, CE, T)
- Arbeitsmedizinische Eignungsuntersuchung (G26, G41 etc.)
- Berufsgenossenschaft (BG BAU, BGHM, etc.)
- Sozialvertrags-Pflicht (SOKA-BAU Tarifbindung)

---

#### Branche: 🏭 Industrie & Produktion

| Segment | Typische Positionen | Besonderheiten |
|---|---|---|
| Maschinenbau | CNC-Dreher, Schlosser, Konstrukteur | DIN-Toleranz-Kenntnisse, CAD/CAM |
| Chemie & Pharma | Chemikant, QS-Mitarbeiter | GDP/GMP-Kenntnisse, Hygieneschleusen |
| Lebensmittelproduktion | Fachkraft Lebensmitteltechnik | HACCP-Zertifikat Pflicht, Hygienebelehrung |
| Logistik & Lager | Lagerlogistiker, Staplerfahrer | Staplerschein, G25 Sehtauglichkeit |
| Elektronik & Mechatronik | Elektroniker, SPS-Programmierer | VDMA, IEC-Standards |
| Textil & Druck | Maschinen-Einrichter, Drucker | Farbsehen-Test, Lösungsmittel |
| Automotive | Produktionsmitarbeiter, KVP | IATF 16949, 5S-Kenntnisse |
| Metallverarbeitung | Schweißer, Dreher | DVS-Schweißschein, Material-Prüfung |

**Branchen-spezifische Pflichtfelder (Industrie):**
- Schichtmodell (Einzel, Zwei-, Drei-Schicht, 4-Schicht-Wechsel)
- Qualifikations-Nachweise (als wählbare Liste aus Norm-Datenbank)
- Reinraum-Klasse (falls zutreffend: ISO 1–9)
- Gefahrstoff-Umgang (Stoffe angebbar)
- Unterweisungs-Frequenz (monatlich, jährlich)

---

#### Branche: 💻 IT & Technologie

| Segment | Typische Positionen | Besonderheiten |
|---|---|---|
| Software-Entwicklung | Frontend-Dev, Backend-Dev, Full-Stack | Tech-Stack-Angabe, Remote-Kompatibilität |
| Data & KI | Data Scientist, ML-Engineer | Python/R Skills, Compute-Ressourcen |
| IT-Infrastruktur | Systemadministrator, DevOps, SRE | On-Call-Bereitschaft, Zertifizierungen |
| Cybersecurity | Security-Analyst, Penetration-Tester | Sicherheitsüberprüfung (Ü2) |
| IT-Projektmanagement | Scrum Master, PO, PM | Zertifikate (PMP, PSM, PRINCE2) |
| Cloud Computing | Cloud-Architect, Cloud-Engineer | AWS/Azure/GCP-Zertifizierungen |
| UX/UI Design | UX-Designer, UI-Engineer | Portfolio-Pflicht, Tool-Stack |
| IT-Support | 1st/2nd Level, Field-Techniker | Reaktionszeit-SLA, ITIL |

**Branchen-spezifische Felder (IT):**
- Tech-Stack (Mehrfachauswahl aus Taxonomie: Sprachen, Frameworks, Tools)
- Remote-Policy (Vollständig Remote, Hybrid X Tage, Vor-Ort)
- Bereitschafts-Dienst (ja/nein, Turnus)
- Sicherheitsüberprüfung erforderlich (Stufe)
- Agile-Methode (Scrum, Kanban, SAFe, kein)

---

#### Branche: 🏥 Gesundheit & Pflege

| Segment | Typische Positionen | Besonderheiten |
|---|---|---|
| Krankenpflege | Pflegefachkraft, Stations-Leitung | Approbation, Pflegekammer |
| Altenpflege | Altenpfleger, Pflegehelfer | Heimaufsicht-Anforderungen |
| Arztpraxis | MFA, Praxismanager | Datenschutz Medizin, GOÄ |
| Therapie | Physiotherapeut, Ergotherapeut | Zulassung Krankenkassen |
| Medizintechnik | Medizintechniker, MTRA | MPG-Beauftragter |
| Rettungsdienst | Notfallsanitäter, Rettungsassistent | NFS-Approbation, BLS/ALS |
| Labor | MTLA, Laborassistent | Strahlenschutz, LIMS |
| Verwaltung-Klinik | Patientenbegleitung, Abrechnung | DRG/ICD-Kenntnisse |

**Branchen-spezifische Pflichtfelder (Gesundheit):**
- Berufs-Approbation / -Zulassung (Typ, ausstellende Behörde)
- Masern-Impfnachweispflicht (§ 20a IfSG, ja/nein)
- Schichtdienst (Früh, Spät, Nacht, Wochenende)
- Konfession / Tarifvertrag (AVR, TVöD, TVÖD-Pflege, frei)
- Strahlenschutz-Kenntnisnachweis

---

#### Branche: 🚛 Logistik & Transport

| Segment | Typische Positionen | Besonderheiten |
|---|---|---|
| Fernverkehr | LKW-Fahrer (CE), Disponenten | Fahrerkarte, Module 95, ADR |
| Nahverkehr | C1-Fahrer, Zustellfahrer | Führerschein B/C1 |
| Luftfracht | Rampenagent, Ladeaufsicht | Gefahrgut IATA, Zuverlässigkeitsüberprüfung |
| Seefracht & Zoll | Zollspezialist, Spediteur | Zollbefähigung, AEO-Status |
| Lager & Kommissionierung | Lagerlogistiker, Kommissionierer | Staplerschein, WMS-Kenntnisse |
| Kurier & Express | Kurierfahrer, Teamleitung | Führerschein B, Zeitdruck-Toleranz |
| Disposition | Disponent, Flottenmanager | Lenk-/Ruhezeiten-Kenntnisse |
| Gefahrgut | Gefahrgutbeauftragter | ADR/IMDG/IATA Schein |

**Branchen-spezifische Pflichtfelder (Logistik):**
- Führerscheinklassen (Pflicht-Dropdown)
- Modul 95 / Berufskraftfahrer-Qualifikation (Gültig bis-Datum)
- ADR-Schein (Klasse, Gültig bis)
- Fahrerkarte (ja/nein)
- Tourengebiet (Regional, National, International, EU)
- Bereitschaft Wochenend-/Feiertagsarbeit

---

#### Branche: 🏫 Bildung & Soziales

| Segment | Typische Positionen | Besonderheiten |
|---|---|---|
| Kita & Kindergarten | Erzieher, Kindheitspädagoge | Erweitertes Führungszeugnis Pflicht |
| Schule | Lehrer, Sonderpädagoge | Verbeamtungsvoraussetzungen |
| Soziale Arbeit | Sozialarbeiter, Sozialpädagoge | §72a SGB VIII |
| Aus- und Weiterbildung | Ausbilder, Trainer | AEVO-Schein |
| Integration & Migration | Integrationslotse, Sprachlehrer | DaZ/DaF, Interkulturell |
| Jugendhilfe | Heimerzieherin, Betreuer | Nachtbereitschaft, Bezugspflege |
| Beratung & Coaching | Berater, Coach | Supervisor, ICF-Zertifizierung |

**Branchen-spezifische Pflichtfelder (Bildung/Soziales):**
- Erweitertes Führungszeugnis (§30a BZRG, ja/nein)
- Konfession/Träger (kirchlich, kommunal, frei, privat)
- Verbeamtung möglich (ja/nein)
- AEVO (Ausbilder-Eignungsschein vorhanden)

---

#### Branche: 🏢 Kaufmännisch & Verwaltung

| Segment | Typische Positionen | Besonderheiten |
|---|---|---|
| Buchhaltung & Controlling | Buchhalter, Controller, CFO | DATEV, SAP FI |
| Personalwesen | HR-Generalist, Personalreferent | Arbeitsrecht-Kenntnisse |
| Einkauf & Beschaffung | Einkäufer, Category Manager | Verhandlungs-Training |
| Vertrieb & Außendienst | Sales Manager, Key-Account | PKW-Pflicht, Reisebereitschaft |
| Marketing | Online-Marketer, Grafiker | Tool-Stack, Portfolio |
| Rechts- & Compliance | Jurist, Compliance-Officer | 2. Staatsexamen |
| Sekretariat & Assistenz | Assistent, Office-Manager | Sprachkenntnisse, Diskretion |
| Öffentliche Verwaltung | Verwaltungsfachangestellter, Beamter | Laufbahn, Besoldungsgruppe |

---

### 4.2 Branchen-Konfiguration im Plugin

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Branchen-Taxonomie (editierbar im Admin) | 🔴 Kritisch |
| Stufe 2 | Pflichtfeld-Sets pro Branche (aktiviert/deaktiviert) | 🔴 Kritisch |
| Stufe 3 | Branche → Standard-Vorlagen-Verknüpfung | 🟠 High |
| Stufe 4 | Branche → Standard-Benefits-Sets | 🟠 High |
| Stufe 5 | Branchen-spezifische Formular-Felder konfigurierbar (ohne Code) | 🟡 Mittel |
| Stufe 6 | Compliance-Hinweise pro Branche (§ Masernschutz, ADR etc.) | 🟡 Mittel |
| Stufe 7 | Mehrfach-Branche (Unternehmen in zwei Branchen tätig) | 🟢 Low |

---

## 5. Stellen-Profile & Vorlagen

### 5.1 Was ist ein Stellen-Profil?

Ein **Positions-Profil** ist eine wiederverwendbare, branchenoptimierte Vorlage für eine standardisierte Stelle. Es enthält alle typischen Texte, Anforderungen und Einstellungen – nicht die spezifische Ausschreibung, sondern den Rahmen dafür.

**Profil-Ebenen:**
```
System-Profil        → Mitgeliefert vom Plugin (100+ Profile für alle Branchen)
Mandanten-Profil     → Agentur-eigene Vorlagen für alle Kunden
Firmen-Profil        → Unternehmensspezifische Anpassung
Abteilungs-Profil    → Departmentspezifische Feinjustierung
```

### 5.2 Profil-Datenstruktur

```json
{
  "profile_id": "it_backend_developer_senior",
  "label": "Senior Backend Developer",
  "branch": "it_technology",
  "sub_branch": "software_development",
  "seniority": "senior",
  "employment_types": ["fulltime", "parttime", "freelance"],

  "sections": {
    "teaser": {
      "de": "Wir suchen einen erfahrenen Backend-Entwickler...",
      "en": "We are looking for an experienced backend developer...",
      "editable": true
    },
    "tasks": {
      "items": [
        "Entwicklung und Wartung skalierbarer Backend-Services",
        "Code-Reviews und Mentoring von Juniors",
        "Mitgestaltung der technischen Architektur"
      ],
      "min_items": 3,
      "max_items": 10,
      "editable": true,
      "extendable": true
    },
    "requirements_must": {
      "items": [
        "5+ Jahre Backend-Erfahrung (PHP, Python oder Java)",
        "Erfahrung mit REST-APIs und Microservices",
        "Versionskontrolle (Git)"
      ]
    },
    "requirements_nice": {
      "items": [
        "Kenntnisse in Kubernetes/Docker",
        "Open-Source-Beiträge"
      ]
    },
    "about_us": {
      "placeholder": "Hier Unternehmenstext einfügen",
      "auto_fill_from": "company_profile"
    }
  },

  "skills_taxonomy": ["php", "python", "java", "rest_api", "sql", "git"],
  "seniority_levels": ["junior", "mid", "senior", "lead", "principal"],
  "certifications_suggested": ["AWS Developer", "Oracle Java"],
  "benefits_category_hints": ["remote_work", "weiterbildung", "agiles_arbeiten"]
}
```

### 5.3 System-Profil-Bibliothek (Auswahl, 100+ geplant)

#### IT & Tech
- Junior/Mid/Senior Frontend Developer
- Junior/Mid/Senior Backend Developer
- Full-Stack Developer (3 Seniority-Stufen)
- DevOps Engineer / SRE
- Cloud Architect (AWS / Azure / GCP)
- Data Engineer / Data Scientist / ML Engineer
- UX Designer / UI Developer
- Product Owner / Scrum Master
- IT-Projektmanager
- Cybersecurity Analyst / Pentester
- IT-Systemadministrator (Linux/Windows)
- 1st/2nd/3rd Level IT-Support
- Datenbankadministrator (MySQL, PostgreSQL, Oracle)

#### Kaufmännisch
- Buchhalter (Debitoren, Kreditoren, Hauptbuch)
- Controller (Finanz, Projekt, Vertriebs-Controlling)
- HR-Generalist / HR-Business-Partner
- Recruiter (intern, technisch, Executive)
- Einkäufer / Category Manager
- Vertriebsmitarbeiter Innendienst/Außendienst
- Key-Account-Manager
- Marketing-Manager (Digital, Content, Event)
- Assistent der Geschäftsführung
- Office-Manager / Sekretärin

#### Handwerk
- Elektriker / Elektroniker (3 Stufen: Geselle, Vorarbeiter, Meister)
- Anlagenmechaniker SHK
- Kfz-Mechatroniker (konventionell + Hochvolt)
- Zimmermann / Dachdecker
- Maler und Lackierer
- Tischler / CNC-Fachkraft

#### Logistik
- LKW-Fahrer (C1, C, CE mit Modul-95)
- Lagerlogistiker / Kommissionierer
- Staplerfahrer
- Disponent (Nah-/Fernverkehr)
- Gefahrgutbeauftragter
- Zollspezialist

#### Gesundheit & Pflege
- Pflegefachkraft (Stationsdienst, Intensiv, Tagespflege)
- Altenpfleger / Altenpflegehelfer
- MFA – Medizinische Fachangestellte
- Physiotherapeut / Ergotherapeut
- MTLA – Medizinisch-technische Laborassistenz
- Notfallsanitäter / Rettungsassistent

### 5.4 Profil-Management Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | System-Profil-Bibliothek (30 Basis-Profile) | 🔴 Kritisch |
| Stufe 2 | Profil-Import/Export (JSON) | 🟠 High |
| Stufe 3 | Profil-Duplikation mit Kontext-Anpassung | 🟠 High |
| Stufe 4 | Profil-Versionierung (Änderungen nachverfolgen) | 🟡 Mittel |
| Stufe 5 | Profil-Vererbung (Senior-Profil erbt von Mid-Profil) | 🟡 

# Mittel |

---

## 6. Benefits-System

### 6.1 Konzept

Benefits sind strukturierte, kategorisierte Zusatzleistungen. Sie können auf jeder Hierarchie-Ebene (Mandant, Firma, Abteilung, Position) definiert und in der Anzeige automatisch dargestellt werden.

**Vererbungs-Mechanismus:**
- Firma definiert: "Betriebliche Altersvorsorge", "30 Tage Urlaub", "Dienstwagen für Führungskräfte"
- Abteilung Entwicklung ergänzt: "Home-Office 4 Tage/Woche", "Konferenz-Budget 1.500€/Jahr"
- Position "Senior Developer" ergänzt: "Firmenwagen" (überschreibt Einschränkung "nur Führung")
- Stellenanzeige erbt alles → zeigt alle Benefits kombiniert & dedupliziert

### 6.2 Benefit-Kategorien

#### Kategorie: Vergütung & Finanzielles
| Benefit | Typ | Konfigurierbare Parameter |
|---|---|---|
| Grundgehalt | Range | von/bis, Währung, Brutto/Netto, Verhandlungsbasis |
| Jahresbonus | Prozent/Fix | max. Höhe, Abhängigkeit (Ziel/Umsatz/frei) |
| Urlaubsgeld | Fest | Betrag |
| Weihnachtsgeld | Fest/Tariflich | Betrag, tarifgebunden ja/nein |
| Betriebliche Altersvorsorge | Ja/Nein | Arbeitgeberzuschuss % |
| Jobrad / E-Bike-Leasing | Ja/Nein | max. Leasingrate, Anzahl |
| Smartphone-Nutzung privat | Ja/Nein | Modell (optional) |
| Mitarbeiter-Beteiligung | Typ | Aktienoptionen, VWL, Gewinnbeteiligung |
| Reisekostenerstattung | Ja/Nein | km-Satz, Pauschale |
| Spesen-Regelung | Je nach Reise | Inland/Ausland-Sätze |

#### Kategorie: Arbeitszeit & Flexibilität
| Benefit | Typ | Konfigurierbare Parameter |
|---|---|---|
| Urlaubstage | Zahl | Anzahl (Pflicht: Hinweis auf gesetzlich 20) |
| Flexible Arbeitszeiten | Ja/Nein | Kernzeit von/bis |
| Homeoffice | Tage/Woche | 0–5, oder "vollständig remote" |
| Remote-First | Ja/Nein | Pflicht-Präsenztage/Monat |
| Gleitzeit | Ja/Nein | Funktionszeit |
| Vertrauensarbeitszeit | Ja/Nein | |
| Teilzeit möglich | Prozentsatz | Mindeststunden/Woche |
| 4-Tage-Woche | Ja/Nein | |
| Sabbatical-Option | Ja/Nein | Wartezeit |
| Workation | Tage/Jahr | Erlaubte Länder (optional) |

#### Kategorie: Entwicklung & Weiterbildung
| Benefit | Typ | Konfigurierbare Parameter |
|---|---|---|
| Weiterbildungsbudget | Betrag/Jahr | Bsp. 1.500€ |
| Zertifizierungs-Förderung | Ja/Nein | Welche Kategorien |
| Interne Schulungen | Ja/Nein | LMS-Zugang |
| Konferenz-Budget | Betrag/Jahr | Anzahl Konferenzen |
| Mentoring-Programm | Ja/Nein | |
| Studienförderung | Ja/Nein | Bindungsfrist |
| Coaching (extern) | Std./Jahr | |
| Sprachkurse | Ja/Nein | Sprachen |
| Technischer Lernpfad | Ja/Nein | Plattform (Coursera, Udemy, etc.) |

#### Kategorie: Gesundheit & Wohlbefinden
| Benefit | Typ | Konfigurierbare Parameter |
|---|---|---|
| Private Krankenzusatz | Ja/Nein | Versicherer (optional) |
| Betriebsarzt | Ja/Nein | |
| Sport-/Fitnessstudio | Ja/Nein | Budget/Monat, Kooperationspartner |
| Wellpass / EGYM | Ja/Nein | |
| Psychologische Beratung | Ja/Nein | Anbieter (Fürstenberg, OpenUp etc.) |
| Ergonomischer Arbeitsplatz | Ja/Nein | Höhenverstellbarer Tisch, etc. |
| Massage im Büro | Ja/Nein | Frequenz |
| Betriebliches Gesundheitsmanagement | Ja/Nein | |

#### Kategorie: Mobilität
| Benefit | Typ | Konfigurierbare Parameter |
|---|---|---|
| Firmenwagen | Ja/Nein | auch privat nutzbar, Klasse |
| ÖPNV-Ticket | Ja/Nein | 9€-Ticket, Jobticket, Vollzahlung |
| Parkplatz | Ja/Nein | kostenlos, Tiefgarage |
| Fahrrad-Leasing (JobRad) | Ja/Nein | max. Leasingrate |
| Reisekostenerstattung | km-Satz | |
| Umzugs-Zuschuss | Ja/Nein | Betrag |

#### Kategorie: Arbeitsumgebung & Unternehmenskultur
| Benefit | Typ | Konfigurierbare Parameter |
|---|---|---|
| Moderne Ausstattung | Ja/Nein | Betriebssystem: Mac/Linux/Windows |
| Kantine / Essenszuschuss | Ja/Nein | €/Tag |
| Kaffee/Getränke | Ja/Nein | |
| Hunde erlaubt | Ja/Nein | |
| Kita-Zuschuss | Betrag/Monat | |
| Firmenevents | Frequenz | Art: Sommerfest, Retreat, Team-Events |
| Fairer Umgang / DEI-Policy | Freitext | |
| Nachhaltigkeits-Engagement | Freitext | |

#### Kategorie: Sozialleistungen & Sicherheit
| Benefit | Typ | Konfigurierbare Parameter |
|---|---|---|
| Unbefristeter Vertrag | Ja/Nein | |
| Tarifvertrag | Ja/Name | Branche, IG-Metall, Ver.di etc. |
| Sozialleistungen (Standard) | Ja/Nein (Hinweis automatisch) | |
| Insolvenzversicherung | Ja/Nein | |
| Schutzkleidung gestellt | Ja/Nein | |
| Werkzeug gestellt | Ja/Nein | |

### 6.3 Benefits-Konfiguration Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Benefit-Katalog (alle Kategorien, je als ja/nein + Freitext) | 🔴 Kritisch |
| Stufe 2 | Benefit-Vererbung über Hierarchie-Ebenen | 🔴 Kritisch |
| Stufe 3 | Benefit-Templates (vorkonfiguriertes Bundle: "Startup-Paket", "Konzern-Standard") | 🟠 High |
| Stufe 4 | Benefit-Anzeige im Frontend (Icons, gruppiert nach Kategorie) | 🟠 High |
| Stufe 5 | Benefit-Ranking (Bewerber bewertet welche Benefits wichtig sind) | 🟡 Mittel |
| Stufe 6 | Benefit-Vergleich (zwei Stellen nebeneinander) | 🟡 Mittel |
| Stufe 7 | Benefit-Budget-Kalkulator (Gesamtkosten aller Benefits hochrechnen) | 🟢 Low |
| Stufe 8 | KI-Benchmark (marktübliche Benefits für diese Stelle anzeigen) | 🟢 Low |

### 6.4 Vordefinierte Benefit-Bundles

```
Bundle: "Klassisches Handwerk"
  ✅ Werkzeug gestellt, Schutzkleidung, Weihnachtsgeld, Urlaubsgeld
  ✅ 30 Tage Urlaub, Betriebliche Altersvorsorge
  ❌ Homeoffice, Remote-Optionen (nicht anwendbar)

Bundle: "IT-Startup"
  ✅ Homeoffice 4–5 Tage, Flexible Zeiten, MacBook
  ✅ Konferenz-Budget 2.000€, Weiterbildungsbudget 2.000€
  ✅ Hunde erlaubt, Kaffee/Getränke
  ❌ Firmenwagen (urban, nicht benötigt)

Bundle: "Krankenhaus / Pflege"
  ✅ Tarifvertrag TVöD-P, Schichtzulagen, Nachtdienstzuschlag
  ✅ Betriebliche Altersvorsorge (VBL), Jobticket
  ❌ Homeoffice, Remote (nicht möglich in Pflege)

Bundle: "Logistik / Transport"
  ✅ Tankgutscheine, Dienstfahrzeug, Führerschein-Finanzierung
  ✅ Modul-95-Finanzierung, Berufskraftfahrerprämie
  ❌ Homeoffice, Remote
```

---

## 7. Rahmenbedingungen-System

### 7.1 Konzept Rahmenbedingungen

Rahmenbedingungen sind **strukturierte, verbindliche Angaben** zur Stellen-Ausschreibung jenseits der Benefits. Sie beschreiben objektive Faktoren: Vertragsart, Arbeitszeit, Standort, Anforderungen.

**Unterschied Benefits vs. Rahmenbedingungen:**
- **Benefits** = freiwillige Zusatzleistungen (das Unternehmen gibt etwas obendrauf)
- **Rahmenbedingungen** = objektive Konditionen (wie, wo, wann, was ist Pflicht)

### 7.2 Rahmenbedingungen-Felder

#### Vertrags-Konditionen
| Feld | Typ | Werte |
|---|---|---|
| Vertragsart | Select | Unbefristet, Befristet (mit Dauer), Werkvertrag, Freelance, Minijob, Praktikum, Ausbildung, Duales Studium |
| Probezeitdauer | Monate | 1–6, keine |
| Wochenstunden | Range | von/bis oder exakter Wert |
| Beschäftigungsgrad | Auswahl | Vollzeit, Teilzeit (%), Geringfügig |
| Tarifbindung | Ja/Nein + Name | |
| Vergütungsmodell | Auswahl | Festgehalt, Gehalt + Provision, Stundenlohn, Tagessatz |
| Gehaltsangabe | Pflicht / freiwillig | Spanne oder Festwert, Brutto / Jahr oder Monat |

#### Arbeitsort & Mobilität
| Feld | Typ | Werte |
|---|---|---|
| Arbeitsort | Adresse + Typ | Büro, Filiale, Produktionsstätte, Baustelle, Homeoffice, Hybrid, Vollständig Remote |
| Reisebereitschaft | Prozent | 0 %, bis 25 %, bis 50 %, > 50 % |
| Führerschein required | Klassen (Multi) | A, B, BE, C1, C, CE, T, Stapler, etc. |
| Dienstwagen | Ja/Nein + Nutzungsrecht | Nur dienstlich, auch privat |
| Standort-Flexibilität | Select | Bindung an Standort, innerhalb Region, bundesweit |

#### Arbeitszeit-Modell
| Feld | Typ | Werte |
|---|---|---|
| Schichtarbeit | Ja/Nein + Modell | Früh/Spät/Nacht, 3-Schicht, 4-Schicht, 5-Schicht, Wechselschicht |
| Überstunden-Regelung | Typ | Kein Überstunden, Freizeitausgleich, Ausbezahlt, Vertrauensarbeitszeit |
| Rufbereitschaft | Ja/Nein + Turnus | |
| Wochenend-Arbeit | Ja/Nein + Frequenz | gelegentlich, regelmäßig, dauerhaft |
| Feiertags-Arbeit | Ja/Nein + Zuschlag | |

#### Sprach- & Qualifikations-Anforderungen
| Feld | Typ | Werte |
|---|---|---|
| Erstsprache am Arbeitsplatz | Select | Deutsch, Englisch, Mehrsprachig |
| Sprachanforderung Deutsch | Level | Keine, A2, B1, B2, C1, C2, Muttersprache |
| Sprachanforderung Englisch | Level | wie oben |
| Weitere Sprachen | Multi-Select + Level | |
| Ausbildungsabschluss Pflicht | Ja/Nein + Typ | Ausbildung, Bachelor, Master, Promotion |
| Fachrichtungs-Einschränkung | Freitext | |

#### Besondere Pflicht-Anforderungen
| Feld | Typ | Beschreibung |
|---|---|---|
| Sicherheitsüberprüfung | Stufe | Keine, Ü1, Ü2, Ü3, Geheimschutz |
| Erweit. Führungszeugnis | Ja/Nein | §30a BZRG |
| Masernschutznachweis | Ja/Nein | §20a IfSG |
| Arbeitsmedizin. Tauglichkeit | G-Nummern (Multi) | G25, G26, G37, G41, etc. |
| Eigene PKW-Nutzung | Ja/Nein | für Außendienst, Erstattung |
| Impfnachweise | Multi | Hepatitis B, Tetanus, etc. |

### 7.3 Rahmenbedingungen Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Alle Felder editierbar auf Firmen- und Positionsebene | 🔴 Kritisch |
| Stufe 2 | Vererbung von Rahmenbedingungen durch Hierarchie | 🔴 Kritisch |
| Stufe 3 | Pflichtfelder pro Branche automatisch einfordern | 🟠 High |
| Stufe 4 | Compliance-Warnungen (z. B. Gehaltsangabe in DE empfohlen) | 🟠 High |
| Stufe 5 | Rahmenbedingungen-Templates (Preset "Vollzeit unbefristet Standard") | 🟡 Mittel |
| Stufe 6 | Gesetzliche Mindestangaben automatisch überprüfen (MiLoG, EntgTranspG) | 🟡 Mittel |
| Stufe 7 | Arbeitnehmerüberlassungs-Kennzeichnung (AÜG) bei Zeitarbeit | 🔴 Kritisch |
| Stufe 8 | Automatischer Diskriminierungsschutz-Check (AGG) | 🟠 High |

---

## 8. Stellenanzeigen-Erstellung (Workflow)

### 8.1 Erstellungs-Modi

**Modus A – Aus Profil erstellen (empfohlen):**
1. Profil auswählen (Positions-Bibliothek oder Firmen-Profil)
2. Daten auto-befüllt aus: Profil + Firmenangaben + Benefits + Rahmenbedingungen
3. Redaktionelle Anpassung (einzelne Sektionen überschreiben)
4. Vorschau → Workflow → Veröffentlichung

**Modus B – Freie Erstellung:**
1. Leere Anzeige anlegen
2. Manuell alle Sektionen befüllen
3. Optionale Profil-Zuweisung im Nachhinein
4. Vorschau → Workflow → Veröffentlichung

**Modus C – Duplikation:**
1. Bestehende Anzeige klonen
2. Datum/Standort/Details anpassen
3. Direkt in Workflow einleiten

### 8.2 Anzeigen-Aufbau (Sektionen)

```
[1] HEADER
    - Jobtitel (Pflicht)
    - Referenznummer (auto-generiert, überschreibbar)
    - Standort (mehrere möglich)
    - Vertragsart-Tags
    - Eintrittsdatum (sofort / Datum)
    - Featured-Bild / Firmen-Banner

[2] UNTERNEHMENSPRÄSENTATION
    - Auto-befüllt aus Firmenprofil
    - Editierbar per Anzeige

[3] STELLE & AUFGABEN
    - Aufgaben-Liste (aus Profil + eigene Ergänzungen)
    - Aufgaben eindeutig formulierbar (AGG-Modus: ohne Diskriminierungsmerkmale)

[4] ANFORDERUNGEN
    - Muss-Anforderungen
    - Soll-Anforderungen
    - Wünschenswert (Nice-to-have)

[5] WIR BIETEN (Benefits)
    - Auto-befüllt aus Benefits-System
    - Anzeigereihenfolge konfigurierbar per Drag & Drop

[6] RAHMENBEDINGUNGEN
    - Auto-befüllt aus Rahmenbedingungen-System
    - Als strukturierte Tabelle oder als Freitext

[7] BEWERBUNGS-AUFRUF & KONTAKT
    - Ansprechpartner (aus Mitarbeitern wählbar)
    - Bewerbungs-Email oder internes Formular
    - Bewerbungsfrist (optional)
    - Keine Zwischenanfragen (ja/nein)
```

### 8.3 Anzeigen-Editor Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Structured-Form-Editor (alle Sektionen als Formular-Felder) | 🔴 Kritisch |
| Stufe 2 | Live-Vorschau (wie die Anzeige im Frontend aussieht) | 🔴 Kritisch |
| Stufe 3 | Block-Editor für freie Sektionen | 🟠 High |
| Stufe 4 | Multi-Standort-Anzeige (eine Anzeige, mehrere Orte) | 🟠 High |
| Stufe 5 | AGG-Check (automatische Warnung bei potenziell diskriminierenden Formulierungen) | 🟠 High |
| Stufe 6 | Lesbarkeits-Analyse (Flesch-Kincaid, Satzlänge) | 🟡 Mittel |
| Stufe 7 | KI-Formulierungshilfe (besser klingende Texte vorschlagen) | 🟡 Mittel |
| Stufe 8 | Mehrsprachige Anzeige (DE/EN parallel pflegen) | 🟡 Mittel |
| Stufe 9 | Barrierefreiheits-Hinweis (Inklusions-Statement, §164 SGB IX) | 🟡 Mittel |
| Stufe 10 | Auto-Gender (automatisch gegenderter Text: m/w/d, Genderstern, Doppelnennung) | 🟠 High |

---

## 9. Agentur-Modus

### 9.1 Agentur-spezifische Funktionen

| Funktion | Beschreibung | Priorität |
|---|---|---|
| Mandanten (Kunden-Firmen) verwalten | Anlegen, bearbeiten, archivieren | 🔴 Kritisch |
| Recruiter-Zuweisung | Welcher Recruiter ist für welche Stammkunden zuständig | 🟠 High |
| Kunden-Branding | Eigenständige Logos, Farben, Kontaktdaten pro Kunde | 🔴 Kritisch |
| Freigabe-Schleife | Kunde muss Anzeige vor Veröffentlichung genehmigen | 🔴 Kritisch |
| Agentur-Profil-Pool | Firmenübergreifende Stellen-Profile der Agentur | 🟠 High |
| Reporting pro Kunde | Anzeigen-Performance je Kunde | 🟠 High |
| White-Label-Preview-Links | Vorschau-Link für Kunden ohne CMS-Zugang | 🟠 High |
| Kunden-Portal-Zugang | Limitiertes Portal für Kunden (nur freigeben + berichten) | 🟡 Mittel |

### 9.2 Multi-Kunden-Dashboard

Ein zentrales Dashboard für Agentur-Admins mit:
- Übersicht aller aktiven Anzeigen nach Kunde
- Offene Freigaben (Wartet auf Kunden-OK)
- Ablaufende Anzeigen (in 7 Tagen beendet)
- Top-Kunden nach Anzeigen-Volumen
- Recruiter-Arbeitsauslastung

### 9.3 Agentur-Abrechnungs-Integration

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Leistungsnachweis pro Kunde (Anzeigen-Anzahl, Laufzeit) | 🟡 Mittel |
| Stufe 2 | Pauschale-Abrechnung pro Anzeige konfigurierbar | 🟡 Mittel |
| Stufe 3 | Share-of-Voice-Berichte (Anteil an Gesamtboard) | 🟢 Low |

---

## 10. Freigabe-Workflow

### 10.1 Workflow-Status-Modell

```
[ENTWURF]
  Ersteller arbeitet an Anzeige, noch nicht eingereicht
    ↓  Einreichen
[ZUR PRÜFUNG]
  Vorgesetzter / HR prüft
    ↓  Änderung anfordern   →  Geht zurück zu [ENTWURF + Kommentare]
    ↓  Intern freigegeben
[INTERN FREIGEGEBEN]
  Nur bei Agentur-Modus: Schritt zur Kunden-Freigabe
    ↓  An Kunden senden
[WARTET AUF KUNDEN]
  Kunden-Freigabe ausstehend (E-Mail/Portal)
    ↓  Abgelehnt → zurück zu [ENTWURF]
    ↓  Freigegeben
[FREIGEGEBEN – BEREIT]
  Anzeige kann sofort oder terminiert veröffentlicht werden
    ↓  Veröffentlichen
[AKTIV / VERÖFFENTLICHT]
  Auf gewählten Kanälen live
    ↓  Pausieren / Verlängern / Archivieren
[PAUSIERT]
  Temporär nicht sichtbar, keine neuen Bewerbungen
[BESETZT]
  Stelle wurde besetzt (manuell markiert oder aus Bewerbungs-Tracking)
[ARCHIVIERT]
  Nicht mehr aktiv, Daten erhalten für Wiederverwendung
```

### 10.2 Workflow-Konfiguration

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Standard-Workflow (wie oben beschrieben) | 🔴 Kritisch |
| Stufe 2 | Workflow-Stufen konfigurierbar (Schritte hinzufügen/entfernen) | 🟠 High |
| Stufe 3 | Parallele Freigabe (mehrere Freigebende gleichzeitig) | 🟠 High |
| Stufe 4 | Sequenzielle Freigabe (erst A, dann B) | 🟡 Mittel |
| Stufe 5 | Deadline pro Freigabe-Schritt (automatische Eskalation) | 🟠 High |
| Stufe 6 | Kommentar-Thread pro Freigabe-Schritt | 🟠 High |
| Stufe 7 | E-Mail-Vorlagen pro Workflow-Status-Change | 🔴 Kritisch |
| Stufe 8 | Slack/Teams-Integration bei Status-Änderung | 🟡 Mittel |
| Stufe 9 | Digitale Unterschrift (Freigabe per elektronischer Signatur) | 🟢 Low |
| Stufe 10 | Audit-Trail (vollständige Protokollierung jedes Schritts) | 🔴 Kritisch |

---

## 11. Veröffentlichungs-Kanäle

### 11.1 Interne Kanäle

| Kanal | Beschreibung | Priorität |
|---|---|---|
| CMS-Jobboard | Eigene Stellenseite im CMS | 🔴 Kritisch |
| Firmen-Website-Widget | Einbettbares Widget für externe Firmenwebsite | 🟠 High |
| Member-Dashboard | Im eingeloggten Bereich als gesonderte Rubrik | 🟠 High |
| REST-API-Feed | JSON-Endpunkt für Headless-Integration | 🟠 High |
| RSS-Feed | XML-Feed für Aggregatoren | 🟡 Mittel |

### 11.2 Externe Stellenbörsen

| Portal | Integration | Priorität |
|---|---|---|
| **Indeed** | XML-Feed (Indeed Publisher) | 🔴 Kritisch |
| **StepStone** | API oder XML-Feed | 🔴 Kritisch |
| **LinkedIn Jobs** | LinkedIn Job Posting API | 🟠 High |
| **XING Jobs** | XING-Job-Posting-API | 🟠 High |
| **Bundesagentur für Arbeit** | XML nach BA-Schnittstellenspezifikation | 🟠 High |
| **Google Jobs (structured data)** | Automatisches Schema.org-JobPosting | 🔴 Kritisch |
| **Monster** | XML-Feed | 🟡 Mittel |
| **Kimeta** | Metasuchmaschine, XML | 🟡 Mittel |
| **Jobware** | API | 🟡 Mittel |
| **GULP** | Für Freelancer-Stellen | 🟡 Mittel |
| **Medi-Jobs** | Gesundheitsbranche | 🟢 Low |
| **Pflegefinder** | Pflege-spezifisch | 🟢 Low |
| **Architektenkammer** | Bau-spezifisch | 🟢 Low |
| **Elektro.net** | Elektrohandwerk | 🟢 Low |

### 11.3 Social Media Distribution

| Kanal | Modus | Priorität |
|---|---|---|
| LinkedIn | Post mit Link, automatisch aus Anzeige | 🟠 High |
| XING | Post mit Link | 🟠 High |
| Facebook/Instagram | Bild-Post (Canva-Template-Export) | 🟡 Mittel |
| Twitter/X | Tweet mit Link | 🟡 Mittel |
| WhatsApp-Kanal | Broadcast-Message | 🟢 Low |
| Telegram | Kanal-Post | 🟢 Low |

### 11.4 Veröffentlichungs-Management

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Kanäle pro Anzeige auswählen (Checkbox-Liste) | 🔴 Kritisch |
| Stufe 2 | Terminierte Veröffentlichung (Datum/Uhrzeit) | 🔴 Kritisch |
| Stufe 3 | Automatische Ablauf-Deaktivierung | 🔴 Kritisch |
| Stufe 4 | Verlängerung mit einem Klick (+14/30 Tage) | 🟠 High |
| Stufe 5 | Feed-Mapping pro Kanal (unterschiedl. Formate) | 🟠 High |
| Stufe 6 | Budget-Management für Paid-Portale (Kosten tracken) | 🟡 Mittel |
| Stufe 7 | Kanal-Performance-Vergleich (Bewerbungen pro Kanal) | 🟡 Mittel |

---

## 12. Frontend & Kandidaten-Sicht

### 12.1 Stellenbörse-Frontend

| Element | Beschreibung | Priorität |
|---|---|---|
| Übersichts-Seite | Grid/Liste, Filter, Suche | 🔴 Kritisch |
| Detailseite | Vollständige Anzeige, Apply-Button | 🔴 Kritisch |
| Firmen-Jobseite | Alle Stellen einer Firma | 🟠 High |
| Abteilungs-Jobseite | Alle Stellen einer Abteilung | 🟡 Mittel |
| Empfehlungs-Widget | "Ähnliche Stellen" auf Detailseite | 🟡 Mittel |
| Merkliste | Stellen bookmarken (für eingeloggte User) | 🟡 Mittel |
| Job-Alert | E-Mail bei neuen Stellen nach Filter | 🟡 Mittel |

### 12.2 Such- & Filter

| Filter | Typ | Priorität |
|---|---|---|
| Stichwortsuche (Titel + Volltext) | Text | 🔴 Kritisch |
| Standort + Umkreis (km) | Geo + Range | 🟠 High |
| Branche | Multi-Select | 🟠 High |
| Beschäftigungsart | Multi-Check | 🔴 Kritisch |
| Homeoffice-Option | Toggle | 🟠 High |
| Einstiegslevel | Select | 🟠 High |
| Firma | Autocomplete | 🟡 Mittel |
| Abteilung | Select | 🟡 Mittel |
| Datum (ab wann veröffentlicht) | Datum-Range | 🟡 Mittel |
| Benefits (mind. X Benefits) | Multi-Check | 🟢 Low |
| Gehalt ab ... | Slider | 🟡 Mittel |

### 12.3 Anzeigen-Detailseite

```
┌────────────────────────────────────────────┐
│ [Firmen-Logo]    [Firmenname]              │
│ JOBTITEL                                   │
│ ⚡ Sofort | 📍 Hamburg + Remote | Vollzeit │
├────────────────────────────────────────────┤
│ [Apply Now] [Merken] [Teilen]              │
├────────────────────────────────────────────┤
│ 📋 Über das Unternehmen                    │
│ 🎯 Deine Aufgaben                          │
│ ✅ Das bringst du mit                      │
│ 🎁 Das bieten wir       [Icons-Grid]       │
│ 📐 Rahmenbedingungen    [Strukturiert]     │
│ 👤 Ansprechpartner      [Foto + Kontakt]   │
├────────────────────────────────────────────┤
│ [Jetzt Bewerben]                           │
│ [Ähnliche Stellen]                         │
└────────────────────────────────────────────┘
```

---

## 13. Bewerbungs-Management

### 13.1 Bewerbungs-Eingang

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Bewerbungs-Formular (Name, E-Mail, Nachricht, CV-Upload) | 🔴 Kritisch |
| Stufe 2 | Bestätigungs-E-Mail an Bewerber (automatisch) | 🔴 Kritisch |
| Stufe 3 | Eingangs-Bestätigung an Zuständigen (Recruiter / Abt.-Leiter) | 🔴 Kritisch |
| Stufe 4 | Datei-Upload (PDF, Word – Virus-Scan) | 🔴 Kritisch |
| Stufe 5 | DSGVO-Einwilligung mit Datenlöschfrist | 🔴 Kritisch |
| Stufe 6 | E-Mail-to-Bewerbung (Bewerber antwortet auf E-Mail) | 🟠 High |
| Stufe 7 | OneClick-Apply (Profil aus CMS vorausgefüllt) | 🟡 Mittel |
| Stufe 8 | LinkedIn Easy-Apply-Anbindung | 🟢 Low |

### 13.2 Bewerbungs-Pipeline

```
[EINGEGANGEN]
  → [UNGESICHTET] → [GESICHTET]
    ↓
  [IN PRÜFUNG]
    ↓ (Absage / weiter)
  [VORAUSWAHL BESTANDEN]
    ↓ (Telefoninterview geplant)
  [INTERVIEW GEPLANT]
    ↓
  [INTERVIEW DURCHGEFÜHRT]
    ↓ (Ja/Nein)
  [ANGEBOT GEMACHT]
    ↓
  [ANGEBOT AKZEPTIERT] → Stelle gilt als besetzt
  [ANGEBOT ABGELEHNT] → Suche weiter
  [ABSAGE GESENDET]
```

### 13.3 Kanban-Board für Bewerbungen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Kanban-Board pro Stelle (alle Bewerber als Karten) | 🟠 High |
| Stufe 2 | Drag & Drop zwischen Pipeline-Stufen | 🟠 High |
| Stufe 3 | Notizen und Tags pro Bewerber | 🟠 High |
| Stufe 4 | Bewertungs-System (1–5 Sterne) pro Bewerber | 🟠 High |
| Stufe 5 | Vergleichs-Ansicht (2–3 Bewerber nebeneinander) | 🟡 Mittel |
| Stufe 6 | Automatische Status-Mails bei Pipeline-Bewegung | 🟠 High |
| Stufe 7 | Team-Kommentare auf Bewerbung | 🟡 Mittel |
| Stufe 8 | Interview-Termin direkt aus CMS planen (Kalender-Integration) | 🟡 Mittel |
| Stufe 9 | Absage-Templates (mit personalisierbarem Namen) | 🟠 High |
| Stufe 10 | DSGVO-Frist-Management (nach X Monaten auto-löschen) | 🔴 Kritisch |

---

## 14. Analytics & Reporting

### 14.1 Stellen-Analytics

| Metrik | Beschreibung | Priorität |
|---|---|---|
| Views pro Anzeige | Seitenaufrufe gesamt + pro Tag | 🟠 High |
| Click-Through-Rate | Views → Apply-Klick | 🟠 High |
| Bewerbungs-Rate | Views → abgeschickte Bewerbung | 🟠 High |
| Kanal-Attribution | Welcher Kanal bringt Bewerbungen | 🟠 High |
| Zeit bis Besetzung | Laufzeit der Anzeige bis Stellenbesetzung | 🟡 Mittel |
| Drop-off im Formular | Wo brechen Bewerber ab | 🟡 Mittel |
| Qualitäts-Score | Bewerber-Bewertungen ø bei dieser Stelle | 🟡 Mittel |

### 14.2 Agentur-Reporting

| Report | Inhalt | Priorität |
|---|---|---|
| Kunden-Report | Aktive Stellen, Bewerbungen, Besetzungen pro Auswertezeit | 🟠 High |
| Recruiter-Leistung | Erstellte Anzeigen, Besetzungsquote | 🟡 Mittel |
| Top-Performing Profiles | Welche Stellen-Profile funktionieren am besten | 🟡 Mittel |
| Branchenvergleich | Durchschnittliche Besetzungsdauer nach Gewerk | 🟢 Low |

### 14.3 Dashboard-Widgets

- Aktive Stellenanzeigen (Zahl mit Trend)
- Offene Bewerbungen (nach Status)
- Anzeigen mit Freigabe-Bedarf (Ampel)
- Ablaufende Anzeigen (7-Tage-Vorschau)
- Bewerbungs-Eingang heute vs. Vorwoche
- Time-to-Fill (ø Tage bis Besetzung)

---

## 15. Datenbank-Schema

```sql
-- Mandanten
CREATE TABLE cms_jobads_mandants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    is_agency TINYINT(1) DEFAULT 0,
    logo_media_id INT,
    primary_color VARCHAR(7),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active','inactive','suspended') DEFAULT 'active'
);

-- Firmen
CREATE TABLE cms_jobads_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mandant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    branch_id INT,
    sub_branch_id INT,
    logo_media_id INT,
    cover_media_id INT,
    description TEXT,
    address_street VARCHAR(255),
    address_city VARCHAR(100),
    address_zip VARCHAR(20),
    address_country VARCHAR(2) DEFAULT 'DE',
    website_url VARCHAR(500),
    employee_count_range ENUM('1-10','11-50','51-200','201-500','501-1000','1000+'),
    founded_year YEAR,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mandant_id) REFERENCES cms_jobads_mandants(id)
);

-- Abteilungen
CREATE TABLE cms_jobads_departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    parent_department_id INT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    cost_center VARCHAR(50),
    description TEXT,
    manager_user_id INT,
    status ENUM('active','inactive') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES cms_jobads_companies(id),
    FOREIGN KEY (parent_department_id) REFERENCES cms_jobads_departments(id)
);

-- Bevollmächtigte / Kontaktpersonen
CREATE TABLE cms_jobads_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    entity_type ENUM('mandant','company','department','position') NOT NULL,
    entity_id INT NOT NULL,
    role ENUM('admin','manager','authorized','approver','viewer') NOT NULL,
    delegate_of_user_id INT NULL,
    delegation_expires_at DATETIME NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Positions-Profile
CREATE TABLE cms_jobads_positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT,
    department_id INT,
    profile_source ENUM('system','mandant','company','department') DEFAULT 'company',
    profile_id VARCHAR(100),
    title_de VARCHAR(255) NOT NULL,
    title_en VARCHAR(255),
    seniority ENUM('trainee','junior','mid','senior','lead','executive'),
    branch_id INT,
    tasks_json JSON,
    requirements_must_json JSON,
    requirements_nice_json JSON,
    skills_json JSON,
    status ENUM('active','draft','archived') DEFAULT 'active',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES cms_jobads_companies(id),
    FOREIGN KEY (department_id) REFERENCES cms_jobads_departments(id)
);

-- Benefits-Definitionen
CREATE TABLE cms_jobads_benefits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category ENUM('vergütung','arbeitszeit','entwicklung','gesundheit','mobilitaet','kultur','soziales') NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    label_de VARCHAR(255) NOT NULL,
    label_en VARCHAR(255),
    icon VARCHAR(100),
    value_type ENUM('boolean','range','amount','text','select') DEFAULT 'boolean',
    value_options_json JSON,
    is_system TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0
);

-- Benefit-Zuweisungen (auf jede Hierarchie-Ebene)
CREATE TABLE cms_jobads_benefit_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    benefit_id INT NOT NULL,
    entity_type ENUM('mandant','company','department','position','job_ad') NOT NULL,
    entity_id INT NOT NULL,
    is_enabled TINYINT(1) DEFAULT 1,
    value_json JSON,
    override_parent TINYINT(1) DEFAULT 0,
    note VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (benefit_id) REFERENCES cms_jobads_benefits(id)
);

-- Rahmenbedingungen-Zuweisungen
CREATE TABLE cms_jobads_conditions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type ENUM('mandant','company','department','position','job_ad') NOT NULL,
    entity_id INT NOT NULL,
    contract_type ENUM('unbefristet','befristet','werkvertrag','freelance','minijob','praktikum','ausbildung','dual') NULL,
    probation_months TINYINT NULL,
    hours_per_week_from DECIMAL(4,1),
    hours_per_week_to DECIMAL(4,1),
    employment_degree ENUM('vollzeit','teilzeit','geringfügig') DEFAULT 'vollzeit',
    tariff_bound TINYINT(1) DEFAULT 0,
    tariff_name VARCHAR(255),
    shift_model ENUM('keine','fruehspaet','dreischicht','vierschicht','wechsel') DEFAULT 'keine',
    remote_type ENUM('vor_ort','hybrid','vollremote') DEFAULT 'vor_ort',
    remote_days_per_week TINYINT DEFAULT 0,
    travel_percent TINYINT DEFAULT 0,
    driver_license_classes_json JSON,
    language_de_level ENUM('keine','A2','B1','B2','C1','C2','Muttersprache') DEFAULT 'B2',
    language_en_level ENUM('keine','A2','B1','B2','C1','C2','Muttersprachler') DEFAULT 'keine',
    security_clearance ENUM('keine','Ü1','Ü2','Ü3') DEFAULT 'keine',
    extended_criminal_record TINYINT(1) DEFAULT 0,
    measles_protection TINYINT(1) DEFAULT 0,
    salary_from DECIMAL(10,2),
    salary_to DECIMAL(10,2),
    salary_period ENUM('monat','jahr') DEFAULT 'jahr',
    salary_is_gross TINYINT(1) DEFAULT 1,
    salary_negotiable TINYINT(1) DEFAULT 1,
    override_parent TINYINT(1) DEFAULT 0,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
);

-- Stellenanzeigen
CREATE TABLE cms_jobads_job_ads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    department_id INT,
    position_id INT,
    reference_number VARCHAR(100) UNIQUE,
    title_de VARCHAR(255) NOT NULL,
    title_en VARCHAR(255),
    teaser_de TEXT,
    about_company_de TEXT,
    tasks_de TEXT,
    requirements_must_de TEXT,
    requirements_nice_de TEXT,
    application_info_de TEXT,
    contact_person_id INT,
    start_date DATE,
    application_deadline DATE,
    status ENUM('draft','review','intern_approved','awaiting_client','approved','active','paused','filled','archived') DEFAULT 'draft',
    workflow_id INT,
    featured TINYINT(1) DEFAULT 0,
    internal_only TINYINT(1) DEFAULT 0,
    view_count INT DEFAULT 0,
    apply_click_count INT DEFAULT 0,
    application_count INT DEFAULT 0,
    created_by INT,
    published_at DATETIME,
    expires_at DATETIME,
    filled_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES cms_jobads_companies(id),
    FOREIGN KEY (department_id) REFERENCES cms_jobads_departments(id),
    FOREIGN KEY (position_id) REFERENCES cms_jobads_positions(id)
);

-- Veröffentlichungen pro Kanal
CREATE TABLE cms_jobads_publications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_ad_id INT NOT NULL,
    channel ENUM('cms_board','api_feed','rss','indeed','stepstone','linkedin','xing','ba','google_jobs','monster','custom') NOT NULL,
    channel_reference VARCHAR(255),
    status ENUM('pending','published','error','expired') DEFAULT 'pending',
    published_at DATETIME,
    expires_at DATETIME,
    error_message TEXT,
    cost_amount DECIMAL(8,2),
    applications_attributed INT DEFAULT 0,
    FOREIGN KEY (job_ad_id) REFERENCES cms_jobads_job_ads(id)
);

-- Bewerbungen
CREATE TABLE cms_jobads_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_ad_id INT NOT NULL,
    applicant_user_id INT NULL,
    applicant_name VARCHAR(255) NOT NULL,
    applicant_email VARCHAR(255) NOT NULL,
    applicant_phone VARCHAR(50),
    cover_letter TEXT,
    cv_media_id INT,
    attachments_json JSON,
    source_channel VARCHAR(100),
    pipeline_stage ENUM('eingegangen','ungesichtet','gesichtet','pruefung','vorauswahl','interview_geplant','interview_durch','angebot','besetzt','absage') DEFAULT 'eingegangen',
    rating TINYINT,
    internal_notes TEXT,
    tags_json JSON,
    gdpr_consent TINYINT(1) DEFAULT 0,
    gdpr_delete_at DATE,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_status_change DATETIME,
    FOREIGN KEY (job_ad_id) REFERENCES cms_jobads_job_ads(id)
);

-- Freigabe-Protokoll
CREATE TABLE cms_jobads_approvals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_ad_id INT NOT NULL,
    step_name VARCHAR(100),
    approver_user_id INT NOT NULL,
    action ENUM('approved','rejected','changes_requested') NOT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_ad_id) REFERENCES cms_jobads_job_ads(id)
);

-- Audit-Log
CREATE TABLE cms_jobads_audit_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50),
    entity_id INT,
    action VARCHAR(100),
    changed_by INT,
    old_value_json JSON,
    new_value_json JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 16. Plugin-Architektur & Hooks

### 16.1 Core-Klassen

```
plugins/cms-jobads/
├── cms-jobads.php                          # Main Plugin (Singleton)
├── includes/
│   ├── class-installer.php                 # DB-Tabellen erstellen
│   ├── class-mandants.php                  # Mandanten-Verwaltung
│   ├── class-companies.php                 # Firmen-Verwaltung
│   ├── class-departments.php               # Abteilungen
│   ├── class-positions.php                 # Stellen-Profile
│   ├── class-job-ads.php                   # Stellenanzeigen (Kern)
│   ├── class-benefits.php                  # Benefits-Engine
│   ├── class-conditions.php                # Rahmenbedingungen-Engine
│   ├── class-inheritance.php               # Vererbungs-Resolver
│   ├── class-workflow.php                  # Freigabe-Workflow
│   ├── class-applications.php             # Bewerbungs-Verwaltung
│   ├── class-publications.php             # Veröffentlichungs-Manager
│   ├── class-branches.php                  # Branchenspezifische Felder
│   ├── class-analytics.php                 # Statistiken
│   ├── class-permissions.php               # RBAC
│   └── class-api.php                       # REST-API Endpoints
├── admin/
│   ├── class-admin-menu.php
│   ├── class-job-ad-editor.php
│   ├── class-company-form.php
│   ├── class-department-form.php
│   ├── class-benefits-ui.php
│   ├── class-kanban-board.php
│   └── class-dashboard-widgets.php
├── templates/
│   ├── archive-job-ad.php
│   ├── single-job-ad.php
│   ├── partials/
│   │   ├── job-ad-card.php
│   │   ├── benefits-grid.php
│   │   ├── conditions-table.php
│   │   ├── apply-form.php
│   │   └── filter-bar.php
├── assets/
│   ├── css/jobads-frontend.css
│   ├── css/jobads-admin.css
│   ├── js/jobads-editor.js
│   └── js/jobads-frontend.js
├── feeds/
│   ├── class-feed-indeed.php
│   ├── class-feed-stepstone.php
│   ├── class-feed-ba.php
│   └── class-feed-google-jobs.php
└── README.md
```

### 16.2 Hooks-Übersicht

```php
// ACTIONS
do_action('jobads_job_ad_created',    $job_ad_id, $data);
do_action('jobads_job_ad_updated',    $job_ad_id, $old, $new);
do_action('jobads_job_ad_published',  $job_ad_id, $channels);
do_action('jobads_job_ad_filled',     $job_ad_id);
do_action('jobads_job_ad_archived',   $job_ad_id);
do_action('jobads_approval_requested',$job_ad_id, $user_id);
do_action('jobads_approved',          $job_ad_id, $user_id);
do_action('jobads_rejected',          $job_ad_id, $user_id, $comment);
do_action('jobads_application_received', $application_id, $job_ad_id);
do_action('jobads_application_stage_changed', $application_id, $old, $new);
do_action('jobads_benefit_resolved',  $entity_type, $entity_id, $benefits);

// FILTERS
$title     = apply_filters('jobads_job_ad_title',      $title, $job_ad);
$benefits  = apply_filters('jobads_resolved_benefits', $benefits, $entity);
$sections  = apply_filters('jobads_ad_sections',       $sections, $job_ad);
$channels  = apply_filters('jobads_publication_channels', $channels);
$form_fields = apply_filters('jobads_apply_form_fields', $fields, $job_ad);
$feed_xml  = apply_filters('jobads_feed_indeed_item',  $xml, $job_ad);
$card_html = apply_filters('jobads_job_ad_card',       $html, $job_ad);
$statuses  = apply_filters('jobads_pipeline_stages',   $stages);
$can       = apply_filters('jobads_user_can',          $bool, $user, $action, $job_ad);
```

---

## 17. Ausbaustufen nach Priorität

### 🔴 Kritisch – Fundament

| ID | Feature |
|---|---|
| JA-K01 | Kern-Datenmodell: Mandant, Firma, Abteilung, Position, Anzeige |
| JA-K02 | Vererbungs-Resolver für Benefits + Rahmenbedingungen |
| JA-K03 | Stellen-Profil-Bibliothek (30 System-Profile, 7 Branchen) |
| JA-K04 | RBAC (alle Rollen und Rechte) |
| JA-K05 | Basis-Editor (Strukturiertes Formular, alle Sektionen) |
| JA-K06 | Standard-Freigabe-Workflow mit E-Mail-Benachrichtigungen |
| JA-K07 | Audit-Log (vollständig) |
| JA-K08 | Bewerbungs-Eingang + DSGVO-Fristenverwaltung |
| JA-K09 | Schema.org JobPosting (Google Jobs) |
| JA-K10 | Stellenbörse-Frontend (Übersicht + Detail + Formular) |
| JA-K11 | AGG-Diskriminierungsschutz-Check |
| JA-K12 | Delegations-Protokoll |

### 🟠 High – Kernfunktionalität

| ID | Feature |
|---|---|
| JA-H01 | Branchen-spezifische Pflichtfelder |
| JA-H02 | Agentur-Modus (Mandanten, Kunden-Freigabe-Portal) |
| JA-H03 | Indeed + BA + StepStone XML-Feeds |
| JA-H04 | Bewerbungs-Kanban-Board |
| JA-H05 | Auto-Gender-Personalisierung |
| JA-H06 | Benefit-Templates (vorkonfigurierte Bundles) |
| JA-H07 | Live-Vorschau im Editor |
| JA-H08 | LinkedIn/XING-Posting beim Veröffentlichen |
| JA-H09 | Multi-Standort-Anzeigen |
| JA-H10 | Zeitbegrenzte Delegations-Funktion |
| JA-H11 | Workflow-Deadline + Eskalation |
| JA-H12 | 30 weitere System-Profile (Gesamt 60) |

### 🟡 Mittel – Erweiterungen

| ID | Feature |
|---|---|
| JA-M01 | KI-Formulierungshilfe im Editor |
| JA-M02 | Bewerbungs-Vergleichs-Ansicht |
| JA-M03 | Interview-Kalender-Integration |
| JA-M04 | Agentur-Abrechnungs-Leistungsnachweis |
| JA-M05 | Benefit-Bewerber-Ranking |
| JA-M06 | Vollständig mehrsprachige Anzeige (DE+EN) |
| JA-M07 | Job-Alert E-Mail-Abonnement |
| JA-M08 | 40 weitere System-Profile (Gesamt 100) |
| JA-M09 | Kanal-Performance-Analytics |
| JA-M10 | Bewerbungs-Pipeline-Status-Mail-Vorlagen |

### 🟢 Low – Differenzierungsmerkmale

| ID | Feature |
|---|---|
| JA-L01 | KI-Profil-Generator (Jobtitel → Profil) |
| JA-L02 | KI-Job-Matching (Bewerber-Profil vs. Stelle) |
| JA-L03 | Profil-Community-Marktplatz |
| JA-L04 | Digitale Freigabe per elektronischer Signatur |
| JA-L05 | Budget-Manager für Paid-Job-Portale |
| JA-L06 | Benefit-Benchmark (marktübliche Benefits für diese Stelle) |
| JA-L07 | Predictive Time-to-Fill Analytics |
| JA-L08 | LinkedIn Easy-Apply-Integration |

---

## 18. Integrations-Überlegungen

### Mit anderen cms-Plugins

| Plugin | Verknüpfung |
|---|---|
| `cms-experts` | Experten-Profil ↔ Bewerber-Profil verknüpfbar |
| `cms-companies` | Firmen-Datenbasis für Anzeigen nutzen (keine Doppelpflege) |
| `cms-messaging` | Interner Chat zwischen Recruiter und Bewerber |
| `cms-subscriptions` | Premium-Abo für Firmen (mehr aktive Anzeigen, Top-Placement) |
| `cms-newsletter` | Job-Alert automatisch als E-Mail-Liste |
| `cms-forms` | Bewerbungsformulare mit dem Form-Builder konfigurieren |
| `cms-calendar` | Interviews als Calendar-Events eintragen |
| `cms-invoicing` | Agentur-Rechnungen an Kunden generieren |
| `cms-learning` | "Qualifiziere dich für diese Stelle" – Kurs-Empfehlung auf Detailseite |

### Externe Systeme

| System | Typ | Beschreibung |
|---|---|---|
| SAP HCM / SAP SuccessFactors | Bidirektional | Stellen-Import/-Export |
| Personio | API | Stellen synchronisieren + Bewerber-Sync |
| DATEV HR | Export | Personalakte bei Einstellung |
| HR4YOU | API | ATS-Integration |
| softgarden | API | Bewerbermanagement-Sync |
| ELSTER / Bundesagentur | XML | Meldepflichten bei Kurzarbeit |
| DocuSign / Skribble | API | Digitale Vertragsunterzeichnung |

---

*Letzte Aktualisierung: 19. Februar 2026*
