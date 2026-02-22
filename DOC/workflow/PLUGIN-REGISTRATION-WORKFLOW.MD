# 365CMS - Plugin-basierter Registrierungs- & Member-Workflow

**Datum:** 17. Februar 2026  
**System:** CMS365/365CMS - IT Expert Hub Platform  
**Kategorie:** Workflow-Dokumentation

---

## 📋 Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Registrierungsprozess](#registrierungsprozess)
3. [Profil-Erstellung Workflow](#profil-erstellung-workflow)
4. [Plugin-basierte Datenverwaltung](#plugin-basierte-datenverwaltung)
5. [Member-Menü Kategorien](#member-menü-kategorien)
6. [Feature-Freischaltung](#feature-freischaltung)
7. [Technische Integration](#technische-integration)
8. [Workflow-Diagramme](#workflow-diagramme)

---

## 🎯 Übersicht

Das 365CMS-System implementiert einen **plugin-basierten Workflow** für die Registrierung und Profilverwaltung von verschiedenen Nutzertypen. Jedes Plugin ist für einen spezifischen Content-Type verantwortlich und liefert alle zugehörigen Daten, Frontend-Templates, Backend-Verwaltung und API-Endpunkte.

### Plugin-Ecosystem (60 Plugins)

Das System besteht aus einem **modularen Plugin-Ecosystem** mit 60 Plugins, die in 10 Kategorien organisiert sind (siehe PLUGIN-LIST.MD):

**10 Plugin-Kategorien:**
1. **Core Content (8 Plugins)** - Basis-Inhaltstypen (5 Required ⭐)
2. **Network & Community (8 Plugins)** - IT-Netzwerk-Features
3. **Events & Calendar (6 Plugins)** - Veranstaltungsmanagement
4. **Communication (7 Plugins)** - Kommunikationstools
5. **Commerce & Business (6 Plugins)** - Geschäftsfunktionen
6. **Media & Content (7 Plugins)** - Medienverwaltung
7. **SEO & Marketing (6 Plugins)** - Marketing-Tools
8. **SEO & Marketing (6 Plugins)** - Marketing-Tools
9. **Analytics & Monitoring (4 Plugins)** - Statistiken
10. **Integration & API (3 Plugins)** - Externe Services

**Mapping zu 6 Member-Menü-Kategorien:**
- Diese 10 Plugin-Kategorien werden auf **6 Member-Menü-Kategorien** abgebildet (siehe Abschnitt "Member-Menü Kategorien")
- Jedes Plugin registriert sich dynamisch im passenden Menü-Bereich
- Nur aktivierte Plugins erscheinen im Member-Dashboard

### Haupt-Nutzertypen (Required Plugins)

Die 4 primären Nutzertypen basieren auf den **Required Core Content Plugins**:

1. **IT-Experten** - Individuelle Fachkräfte (Plugin: `cms-experts` ⭐)
2. **Unternehmen** - Firmenprofile (Plugin: `cms-companies` ⭐)
3. **Event-Agenturen** - Veranstaltungsorganisatoren (Plugin: `cms-events` ⭐)
4. **Speaker** - Referenten/Sprecher (Plugin: `cms-speakers` ⭐)

**Zusätzlich verfügbar:**
- **Blogger** - Artikel-Autoren (Plugin: `cms-blog` ⭐)
- **+55 weitere Content-Typen** - Je nach aktivierten optionalen Plugins

### Architektur-Prinzipien

- ✅ **Single Responsibility:** Ein Plugin = Eine Funktion
- ✅ **Modularität:** Plugins sind unabhängig aktivierbar (60 Module)
- ✅ **Hook-basiert:** Integration über Actions & Filters
- ✅ **Status-gesteuert:** Features basierend auf Partner-/Abo-Status
- ✅ **Ecosystem-Driven:** Erweiterbar durch zusätzliche Plugins
- ✅ **6 Menu Categories:** Feste Menüstruktur für alle Plugins

---

## 🚀 Registrierungsprozess

### Phase 1: Initiale Registrierung (Startseite)

Der Registrierungsprozess beginnt **einheitlich** für alle Nutzertypen auf der Startseite.

```
┌─────────────────────────────────────────────┐
│         STARTSEITE / HOMEPAGE               │
│                                             │
│  ┌───────────────────────────────────┐     │
│  │   "Jetzt Registrieren" Button     │     │
│  └───────────────────────────────────┘     │
│                    ↓                        │
│  ┌───────────────────────────────────┐     │
│  │   Registrierungs-Formular         │     │
│  │                                   │     │
│  │  • Benutzername                   │     │
│  │  • E-Mail                         │     │
│  │  • Passwort                       │     │
│  │  • (Erstmal OHNE Nutzertyp!)      │     │
│  └───────────────────────────────────┘     │
│                    ↓                        │
│         Account wird erstellt               │
└─────────────────────────────────────────────┘
```

**Wichtig:** In dieser Phase wird **KEIN** spezifischer Nutzertyp ausgewählt. Der Benutzer erhält zunächst nur einen Standard-Account.

#### Technische Umsetzung

```php
// Core Authentication System
CMS\Auth::register([
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'role' => 'member' // Standard-Rolle
]);

// Trigger Hook für weitere Verarbeitung
CMS\Hooks::doAction('user_registered', $user_id);
```

---

### Phase 2: Profil-Auswahl (Member Dashboard)

Nach erfolgreicher Registrierung und Login landet der Nutzer im **Member Dashboard** und kann dort auswählen, welche Art von Profil er erstellen möchte.

```
┌──────────────────────────────────────────────────┐
│           MEMBER DASHBOARD                       │
│                                                  │
│  Willkommen, [Username]!                        │
│                                                  │
│  ┌────────────────────────────────────────┐    │
│  │     BEREICH: "Anlegen"                 │    │
│  │     (4 Required + 56 optionale Typen)  │    │
│  │                                        │    │
│  │  Wählen Sie, was Sie erstellen möchten:│   │
│  │                                        │    │
│  │  🔴 REQUIRED PROFILE (4)               │    │
│  │  ○ IT-Experten Profil (cms-experts)   │    │
│  │  ○ Firmen-Profil (cms-companies)      │    │
│  │  ○ Event-Agentur (cms-events)         │    │
│  │  ○ Speaker-Profil (cms-speakers)      │    │
│  │                                        │    │
│  │  🟢 CONTENT PROFILE (4)                │    │
│  │  ○ Blog-Artikel (cms-blog)            │    │
│  │  ○ Portfolio-Projekt (cms-portfolio)  │    │
│  │  ○ Testimonial (cms-testimonials)     │    │
│  │  ○ Seite (cms-pages)                  │    │
│  │                                        │    │
│  │  🔵 BUSINESS PROFILE (6)               │    │
│  │  ○ Marketplace-Produkt (cms-marketplace)│   │
│  │  ○ Projekt (cms-projects)             │    │
│  │  ○ Job-Angebot (cms-jobs)             │    │
│  │  ○ Rechnung (cms-invoicing)           │    │
│  │  ○ ... (je nach aktivierten Plugins)  │    │
│  │                                        │    │
│  │  🟡 EVENT TYPES (6)                    │    │
│  │  ○ Event erstellen (cms-events)       │    │
│  │  ○ Webinar (cms-webinars)             │    │
│  │  ○ Buchung (cms-bookings)             │    │
│  │  ○ ... (je nach aktivierten Plugins)  │    │
│  │                                        │    │
│  │  🟣 MEDIA CONTENT (7)                  │    │
│  │  ○ Galerie (cms-gallery)              │    │
│  │  ○ Video (cms-video-library)          │    │
│  │  ○ Podcast (cms-podcasts)             │    │
│  │  ○ ... (je nach aktivierten Plugins)  │    │
│  └────────────────────────────────────────┘    │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Anmerkung:** 
- Ein Benutzer kann **mehrere Profile** verschiedener Typen anlegen (z.B. sowohl als Experte als auch als Firma)
- Die verfügbaren Profil-Typen hängen von den **aktivierten Plugins** ab
- **4 Required Plugins** (Experts, Companies, Events, Speakers, Blog) sind immer verfügbar
- **56 optionale Plugins** erscheinen nur, wenn sie vom Admin aktiviert wurden
- Die Anzeige-Reihenfolge und Gruppierung kann per Plugin-Hook angepasst werden

---

## 📝 Profil-Erstellung Workflow

### Schritt 1: Profil-Typ Auswahl

Der Benutzer wählt im Dashboard-Bereich "Anlegen" den gewünschten Profil-Typ aus.

### Schritt 2: Plugin-spezifisches Formular

Sobald ein Typ ausgewählt wurde, wird das **entsprechende Plugin** aktiv und liefert:

1. **Frontend-Formular** - Spezifische Eingabefelder
2. **Validierung** - Plugin-eigene Validierungsregeln
3. **Datenbank-Schema** - Plugin-eigene Tabellen
4. **Templates** - Darstellung der Card/Detailseite

#### Beispiel: IT-Experten-Profil (Plugin: cms-experts)

```
┌──────────────────────────────────────────────────┐
│    IT-EXPERTEN PROFIL ERSTELLEN                  │
│    (Bereitgestellt von: cms-experts Plugin)      │
│                                                  │
│  ┌────────────────────────────────────────┐    │
│  │  Vorname: [______________]             │    │
│  │  Nachname: [______________]            │    │
│  │  Position: [______________]            │    │
│  │  Skills: [______________] (Tags)       │    │
│  │  Spezialisierung: [______]             │    │
│  │  Biografie: [_____________]            │    │
│  │  Foto: [Hochladen]                     │    │
│  │  Social Media:                         │    │
│  │    LinkedIn: [______________]          │    │
│  │    XING: [______________]              │    │
│  │    GitHub: [______________]            │    │
│  │                                        │    │
│  │  [Profil Erstellen] [Abbrechen]       │    │
│  └────────────────────────────────────────┘    │
│                                                  │
└──────────────────────────────────────────────────┘
```

### Schritt 3: Daten-Speicherung

Die eingegebenen Daten werden in die **plugin-spezifischen Datenbank-Tabellen** gespeichert:

```sql
-- cms-experts Plugin Tabellen
cms_experts              -- Haupt-Profildaten
cms_expert_meta          -- Zusätzliche Meta-Daten
cms_expert_skills        -- Skills/Kompetenzen
```

### Schritt 4: Verknüpfung mit User-Account

Das erstellte Profil wird mit dem User-Account verknüpft:

```php
// In cms-experts/plugin.php
public function createExpertProfile($user_id, $data) {
    global $db;
    
    // Profil erstellen
    $expert_id = $db->insert('cms_experts', [
        'user_id' => $user_id,
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'position' => $data['position'],
        'bio' => $data['bio'],
        'photo' => $data['photo'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Skills hinzufügen
    foreach ($data['skills'] as $skill) {
        $db->insert('cms_expert_skills', [
            'expert_id' => $expert_id,
            'skill_name' => $skill
        ]);
    }
    
    // Hook ausführen
    CMS\Hooks::doAction('expert_profile_created', $expert_id, $user_id);
    
    return $expert_id;
}
```

---

## 🔌 Plugin-basierte Datenverwaltung

### Prinzip: Plugin = Daten-Owner

Jedes Plugin ist **vollständig verantwortlich** für seine Daten:

| Plugin | Content-Type | Verantwortlich für |
|--------|-------------|-------------------|
| `cms-experts` | IT-Experten | Frontend-Templates, Backend-CRUD, Datenbank-Tabellen, API-Endpunkte |
| `cms-companies` | Unternehmen | Frontend-Templates, Backend-CRUD, Datenbank-Tabellen, API-Endpunkte |
| `cms-events` | Event-Agenturen | Frontend-Templates, Backend-CRUD, Datenbank-Tabellen, API-Endpunkte |
| `cms-speaker` | Speaker | Frontend-Templates, Backend-CRUD, Datenbank-Tabellen, API-Endpunkte |

### Plugin-Struktur (Standardisiert)

```
plugins/cms-experts/
├── cms-experts.php              # Main Plugin File
├── includes/
│   ├── class-frontend.php       # Frontend-Logik
│   ├── class-backend.php        # Admin-Interface
│   ├── class-api.php            # REST API Endpunkte
│   └── class-database.php       # Datenbank-Operations
├── templates/
│   ├── archive-expert.php       # Übersichtsseite (Card-Grid)
│   ├── single-expert.php        # Detailseite
│   ├── expert-card.php          # Card-Component
│   └── form-create-expert.php   # Erstellungs-Formular
├── assets/
│   ├── css/expert-styles.css
│   └── js/expert-scripts.js
└── README.md
```

### Daten-Flow Beispiel

```
User-Aktion (Formular absenden)
         ↓
Plugin-Handler (cms-experts.php)
         ↓
Validierung (class-database.php)
         ↓
Datenbank-Insert (cms_experts Tabelle)
         ↓
Hook-Trigger (expert_profile_created)
         ↓
Dashboard-Update (Neuer Menü-Punkt)
```

---

## 🗂️ Member-Menü Kategorien

Das Member-Menü ist in **maximal 6 Kategorien** unterteilt. Jede Kategorie gruppiert thematisch verwandte Funktionen.

**Wichtig:** Das System verfügt über **60 Plugins in 10 Plugin-Kategorien** (siehe PLUGIN-LIST.MD), die auf **6 Member-Menü-Kategorien** verteilt werden. Plugins registrieren ihre Menü-Einträge dynamisch basierend auf Aktivierung und Nutzer-Berechtigung.

### Mapping: 10 Plugin-Kategorien → 6 Member-Menü-Kategorien

```
Plugin-Kategorien (PLUGIN-LIST.MD)     →  Member-Menü-Kategorien
─────────────────────────────────────────────────────────────────
1. Core Content (8 Plugins)            →  MEINE PROFILE
2. Network & Community (8 Plugins)     →  NETZWERK & COMMUNITY
3. Events & Calendar (6 Plugins)       →  EVENTS & TERMINE
4. Communication (7 Plugins)           →  NETZWERK & COMMUNITY
5. Commerce & Business (6 Plugins)     →  GESCHÄFT & SERVICES
6. Media & Content (7 Plugins)         →  MEINE PROFILE / DASHBOARD
7. SEO & Marketing (6 Plugins)         →  DASHBOARD / EINSTELLUNGEN
8. Security & Privacy (5 Plugins)      →  EINSTELLUNGEN & VERWALTUNG
9. Analytics & Monitoring (4 Plugins)  →  DASHBOARD
10. Integration & API (3 Plugins)      →  EINSTELLUNGEN & VERWALTUNG
```

### Standard-Kategorien (6 Maximum)

```
┌──────────────────────────────────────────────┐
│         MEMBER DASHBOARD - MENÜ              │
├──────────────────────────────────────────────┤
│                                              │
│  📊 1. DASHBOARD                             │
│     Übersicht, Statistiken, Analytics        │
│     ─────────────────────────────────────    │
│     - Dashboard (Übersicht)                  │
│     - Aktivitäten (Activity Feed)            │
│     - Benachrichtigungen                     │
│     - Statistiken (Analytics)                │
│     - Metriken (Monitoring)                  │
│     - Newsletter-Statistiken (SEO/Marketing) │
│     - Blog-Übersicht (Media/Content)         │
│                                              │
│  👤 2. MEINE PROFILE                         │
│     Profile, Content, Portfolio              │
│     ─────────────────────────────────────    │
│     - IT-Experten-Profil (cms-experts) ⭐    │
│     - Firmen-Profil (cms-companies) ⭐       │
│     - Event-Agentur (cms-events) ⭐          │
│     - Speaker-Profil (cms-speakers) ⭐       │
│     - Portfolio (cms-portfolio)              │
│     - Testimonials (cms-testimonials)        │
│     - Blog-Artikel (cms-blog) ⭐             │
│     - Seiten (cms-pages)                     │
│     - Media-Bibliothek (cms-media-library)   │
│                                              │
│  🌐 3. NETZWERK & COMMUNITY                  │
│     Verbindungen, Kommunikation, Social      │
│     ─────────────────────────────────────    │
│     - Verbindungen (cms-connections)         │
│     - Nachrichten (cms-messaging)            │
│     - Gruppen (cms-groups)                   │
│     - Forum (cms-forums)                     │
│     - Member Directory (cms-member-directory)│
│     - Activity Feed (cms-activity-feed)      │
│     - Mentorship (cms-mentorship)            │
│     - Skills Matrix (cms-skills-matrix)      │
│     - Badges (cms-badges)                    │
│     - Chat (cms-chat)                        │
│     - Video-Calls (cms-video-calls)          │
│                                              │
│  📅 4. EVENTS & TERMINE                      │
│     Events, Kalender, Buchungen              │
│     ─────────────────────────────────────    │
│     - Meine Events (cms-events)              │
│     - Event-Kalender (cms-calendar)          │
│     - Buchungen (cms-bookings)               │
│     - Teilnahmen (cms-registrations)         │
│     - Webinare (cms-webinars)                │
│     - Tickets (cms-tickets)                  │
│                                              │
│  💼 5. GESCHÄFT & SERVICES                   │
│     Commerce, Projekte, Finanzen             │
│     ─────────────────────────────────────    │
│     - Marketplace (cms-marketplace)          │
│     - Projekte (cms-projects)                │
│     - Jobs (cms-jobs)                        │
│     - Rechnungen (cms-invoicing)             │
│     - Verträge (cms-contracts)               │
│     - CRM (cms-crm)                          │
│     - Zahlungen (cms-payments)               │
│                                              │
│  ⚙️ 6. EINSTELLUNGEN & VERWALTUNG            │
│     Account, Privacy, Integrations           │
│     ─────────────────────────────────────    │
│     - Account-Einstellungen                  │
│     - Datenschutz (cms-gdpr-compliance)      │
│     - Benachrichtigungs-Einstellungen        │
│     - Mitgliedschaft/Abo (cms-subscriptions) │
│     - Two-Factor Auth (cms-2fa)              │
│     - API-Keys (cms-api-manager)             │
│     - Integrationen (cms-integrations)       │
│     - Backup (cms-backup)                    │
│     - SEO-Einstellungen (cms-seo)            │
│                                              │
└──────────────────────────────────────────────┘
```

**Legende:**
- ⭐ = Required Plugin (muss aktiv sein)
- Andere Einträge erscheinen nur wenn das entsprechende Plugin aktiv ist

### Vollständige Plugin-Zuordnung (60 Plugins)

Die folgende Tabelle zeigt alle 60 Plugins aus der PLUGIN-LIST.MD und ihre Zuordnung zu den 6 Member-Menü-Kategorien:

| # | Plugin Name | Plugin-Kategorie | Member-Menü-Kategorie | Required | Beschreibung |
|---|-------------|------------------|----------------------|----------|--------------|
| **CORE CONTENT (8)** |
| 1 | cms-experts | Core Content | 👤 MEINE PROFILE | ⭐ | IT-Experten-Profile |
| 2 | cms-companies | Core Content | 👤 MEINE PROFILE | ⭐ | Firmen-Profile |
| 3 | cms-events | Core Content | 📅 EVENTS & TERMINE | ⭐ | Event-Verwaltung |
| 4 | cms-speakers | Core Content | 👤 MEINE PROFILE | ⭐ | Speaker-Profile |
| 5 | cms-blog | Core Content | 👤 MEINE PROFILE | ⭐ | Blog-System |
| 6 | cms-pages | Core Content | 👤 MEINE PROFILE | | Statische Seiten |
| 7 | cms-portfolio | Core Content | 👤 MEINE PROFILE | | Portfolio-Showcase |
| 8 | cms-testimonials | Core Content | 👤 MEINE PROFILE | | Kundenbewertungen |
| **NETWORK & COMMUNITY (8)** |
| 9 | cms-member-directory | Network & Community | 🌐 NETZWERK & COMMUNITY | | Mitgliederverzeichnis |
| 10 | cms-groups | Network & Community | 🌐 NETZWERK & COMMUNITY | | User-Gruppen |
| 11 | cms-forums | Network & Community | 🌐 NETZWERK & COMMUNITY | | Diskussionsforum |
| 12 | cms-connections | Network & Community | 🌐 NETZWERK & COMMUNITY | | Netzwerk-Verbindungen |
| 13 | cms-activity-feed | Network & Community | 🌐 NETZWERK & COMMUNITY / 📊 DASHBOARD | | Activity-Stream |
| 14 | cms-mentorship | Network & Community | 🌐 NETZWERK & COMMUNITY | | Mentor-Matching |
| 15 | cms-skills-matrix | Network & Community | 🌐 NETZWERK & COMMUNITY | | Skill-Assessment |
| 16 | cms-badges | Network & Community | 🌐 NETZWERK & COMMUNITY / 📊 DASHBOARD | | Gamification-Badges |
| **EVENTS & CALENDAR (6)** |
| 17 | cms-calendar | Events & Calendar | 📅 EVENTS & TERMINE | | Kalender-Visualisierung |
| 18 | cms-bookings | Events & Calendar | 📅 EVENTS & TERMINE | | Buchungssystem |
| 19 | cms-registrations | Events & Calendar | 📅 EVENTS & TERMINE | | Event-Registrierungen |
| 20 | cms-webinars | Events & Calendar | 📅 EVENTS & TERMINE | | Webinar-Verwaltung |
| 21 | cms-tickets | Events & Calendar | 📅 EVENTS & TERMINE | | Ticket-System |
| 22 | cms-rsvp | Events & Calendar | 📅 EVENTS & TERMINE | | RSVP-Management |
| **COMMUNICATION (7)** |
| 23 | cms-messaging | Communication | 🌐 NETZWERK & COMMUNITY | | Private Nachrichten |
| 24 | cms-notifications | Communication | 📊 DASHBOARD / ⚙️ EINSTELLUNGEN | | Benachrichtigungssystem |
| 25 | cms-email-campaigns | Communication | 💼 GESCHÄFT & SERVICES | | E-Mail-Marketing |
| 26 | cms-newsletter | Communication | 💼 GESCHÄFT & SERVICES / 📊 DASHBOARD | | Newsletter-System |
| 27 | cms-comments | Communication | 🌐 NETZWERK & COMMUNITY | | Kommentar-System |
| 28 | cms-chat | Communication | 🌐 NETZWERK & COMMUNITY | | Live-Chat |
| 29 | cms-video-calls | Communication | 🌐 NETZWERK & COMMUNITY | | Video-Conferencing |
| **COMMERCE & BUSINESS (6)** |
| 30 | cms-marketplace | Commerce & Business | 💼 GESCHÄFT & SERVICES | | Produkt-Marktplatz |
| 31 | cms-projects | Commerce & Business | 💼 GESCHÄFT & SERVICES | | Projektverwaltung |
| 32 | cms-jobs | Commerce & Business | 💼 GESCHÄFT & SERVICES | | Job-Börse |
| 33 | cms-invoicing | Commerce & Business | 💼 GESCHÄFT & SERVICES | | Rechnungsverwaltung |
| 34 | cms-payments | Commerce & Business | 💼 GESCHÄFT & SERVICES | | Zahlungsabwicklung |
| 35 | cms-subscriptions | Commerce & Business | ⚙️ EINSTELLUNGEN | | Abo-Verwaltung |
| **MEDIA & CONTENT (7)** |
| 36 | cms-media-library | Media & Content | 👤 MEINE PROFILE | | Mediathek |
| 37 | cms-file-manager | Media & Content | 👤 MEINE PROFILE | | Dateiverwaltung |
| 38 | cms-gallery | Media & Content | 👤 MEINE PROFILE | | Bildergalerien |
| 39 | cms-video-library | Media & Content | 👤 MEINE PROFILE | | Video-Bibliothek |
| 40 | cms-podcasts | Media & Content | 👤 MEINE PROFILE | | Podcast-Verwaltung |
| 41 | cms-downloads | Media & Content | 👤 MEINE PROFILE / 💼 GESCHÄFT | | Download-Manager |
| 42 | cms-documents | Media & Content | 💼 GESCHÄFT & SERVICES | | Dokumentenverwaltung |
| **SEO & MARKETING (6)** |
| 43 | cms-seo | SEO & Marketing | ⚙️ EINSTELLUNGEN / 📊 DASHBOARD | | SEO-Optimierung |
| 44 | cms-sitemap | SEO & Marketing | ⚙️ EINSTELLUNGEN | | XML-Sitemap |
| 45 | cms-redirects | SEO & Marketing | ⚙️ EINSTELLUNGEN | | URL-Redirects |
| 46 | cms-social-share | SEO & Marketing | 📊 DASHBOARD | | Social-Media-Sharing |
| 47 | cms-meta-tags | SEO & Marketing | ⚙️ EINSTELLUNGEN | | Meta-Tags-Verwaltung |
| 48 | cms-schema-markup | SEO & Marketing | ⚙️ EINSTELLUNGEN | | Schema.org-Markup |
| **SECURITY & PRIVACY (5)** |
| 49 | cms-2fa | Security & Privacy | ⚙️ EINSTELLUNGEN | | Two-Factor-Auth |
| 50 | cms-backup | Security & Privacy | ⚙️ EINSTELLUNGEN | | Backup-System |
| 51 | cms-security-log | Security & Privacy | ⚙️ EINSTELLUNGEN | | Security-Logging |
| 52 | cms-gdpr-compliance | Security & Privacy | ⚙️ EINSTELLUNGEN | | GDPR-Tools |
| 53 | cms-anti-spam | Security & Privacy | ⚙️ EINSTELLUNGEN | | Anti-Spam |
| **ANALYTICS & MONITORING (4)** |
| 54 | cms-analytics | Analytics & Monitoring | 📊 DASHBOARD | | Web-Analytics |
| 55 | cms-user-tracking | Analytics & Monitoring | 📊 DASHBOARD | | User-Tracking |
| 56 | cms-performance-monitor | Analytics & Monitoring | 📊 DASHBOARD | | Performance-Monitoring |
| 57 | cms-error-tracking | Analytics & Monitoring | 📊 DASHBOARD | | Error-Tracking |
| **INTEGRATION & API (3)** |
| 58 | cms-api-manager | Integration & API | ⚙️ EINSTELLUNGEN | | API-Management |
| 59 | cms-webhooks | Integration & API | ⚙️ EINSTELLUNGEN | | Webhook-System |
| 60 | cms-integrations | Integration & API | ⚙️ EINSTELLUNGEN | | Drittanbieter-Integration |

**Hinweis:** Einige Plugins können in mehreren Kategorien erscheinen (z.B. cms-activity-feed in NETZWERK und DASHBOARD). Die Haupt-Kategorie ist fett markiert.

### Kategorien-Registration durch Plugins

Plugins können ihre Menü-Einträge einer Kategorie zuordnen:

```php
// In cms-experts/cms-experts.php
CMS\Hooks::addFilter('member_menu_items', function($items) {
    $items['profiles']['expert'] = [
        'label' => 'IT-Experten-Profil',
        'url' => '/member/expert/profile',
        'icon' => '👨‍💻',
        'category' => 'profiles', // Kategorie: "Meine Profile"
        'order' => 10
    ];
    return $items;
});
```

### Dynamische Kategorien-Anzeige

**Wichtig:** Nicht alle 6 Kategorien müssen immer angezeigt werden. Kategorien werden nur gezeigt, wenn sie mindestens einen aktiven Menü-Eintrag enthalten.

```php
// Pseudo-Code: Kategorie-Rendering
foreach ($categories as $category_key => $category) {
    $menu_items = get_menu_items_for_category($category_key);
    
    // Kategorie nur anzeigen, wenn Items vorhanden
    if (!empty($menu_items)) {
        render_category($category, $menu_items);
    }
}
```

---

## 🔓 Feature-Freischaltung

Features im Member-Bereich werden **dynamisch freigeschaltet** basierend auf:

1. **Partner-Status** (Partner, Top-Partner, Sponsor)
2. **Abo-Typ** (Free, Basic, Premium, Enterprise)
3. **Allgemeiner Account-Status** (Verifiziert, Aktiv, Gesperrt)

### Status-Hierarchie

```
┌─────────────────────────────────────────────┐
│      FEATURE-FREISCHALTUNG MATRIX           │
├─────────────────────────────────────────────┤
│                                             │
│  FREE USER                                  │
│  ├─ Dashboard (nur Ansicht)                 │
│  ├─ 1 Profil erstellen                      │
│  ├─ Nachrichten (limitiert: 5/Tag)          │
│  └─ Events ansehen (keine Erstellung)       │
│                                             │
│  BASIC MEMBER                               │
│  ├─ Alle Free Features +                    │
│  ├─ Dashboard (erweiterte Statistiken)      │
│  ├─ 3 Profile erstellen                     │
│  ├─ Nachrichten (unlimitiert)               │
│  ├─ Events erstellen (max. 2/Monat)         │
│  └─ Marketplace Zugang                      │
│                                             │
│  PREMIUM MEMBER                             │
│  ├─ Alle Basic Features +                   │
│  ├─ Unbegrenzte Profile                     │
│  ├─ Events unlimitiert                      │
│  ├─ Projekte-Verwaltung                     │
│  ├─ Prioritäts-Support                      │
│  └─ Erweiterte Analytics                    │
│                                             │
│  PARTNER (Silber)                           │
│  ├─ Alle Premium Features +                 │
│  ├─ Partner-Badge                           │
│  ├─ Hervorgehobene Listings                 │
│  └─ 10% Rabatt auf Services                 │
│                                             │
│  TOP-PARTNER (Gold)                         │
│  ├─ Alle Partner Features +                 │
│  ├─ Gold-Partner-Badge                      │
│  ├─ Premium-Platzierung in Listen           │
│  ├─ 25% Rabatt auf Services                 │
│  └─ Dedizierter Account-Manager             │
│                                             │
│  SPONSOR (Platin)                           │
│  ├─ Alle Top-Partner Features +             │
│  ├─ Platin-Sponsor-Badge                    │
│  ├─ Homepage-Feature-Slot                   │
│  ├─ 50% Rabatt auf Services                 │
│  ├─ Exklusive Event-Sponsoring              │
│  └─ API-Zugang                              │
│                                             │
└─────────────────────────────────────────────┘
```

### Technische Implementierung

```php
// Beispiel: Feature-Check für Event-Erstellung
class EventFeatureManager {
    
    public static function canCreateEvent($user_id): bool {
        $user_meta = get_user_meta($user_id);
        
        // Free User: Keine Events
        if ($user_meta['subscription'] === 'free') {
            return false;
        }
        
        // Basic: Max. 2 Events pro Monat
        if ($user_meta['subscription'] === 'basic') {
            $events_this_month = count_user_events_this_month($user_id);
            return $events_this_month < 2;
        }
        
        // Premium+: Unlimitiert
        return true;
    }
    
    public static function getFeatureRestriction($user_id, $feature): array {
        // Gibt zurück: ['allowed' => bool, 'limit' => int, 'used' => int]
    }
}
```

### Feature-Freischaltung via Hooks

Plugins können Features dynamisch freischalten:

```php
// In cms-projects/cms-projects.php
CMS\Hooks::addFilter('member_menu_items', function($items, $user) {
    // Nur für Premium+ anzeigen
    if (in_array($user['subscription'], ['premium', 'partner', 'sponsor'])) {
        $items['business']['projects'] = [
            'label' => 'Projekte',
            'url' => '/member/projects',
            'icon' => '📋',
            'category' => 'business'
        ];
    }
    return $items;
}, 10, 2); // Priority 10, 2 Parameter
```

### Visuelle Status-Indikatoren

```
┌──────────────────────────────────────────┐
│  [Username] 🏆 SPONSOR                   │  ← Gold/Platin Badge
│  ─────────────────────────────────────   │
│  📊 Dashboard                            │
│  👤 Meine Profile (5) ✓                  │
│  🌐 Netzwerk (Premium) ⭐                │  ← Premium-Feature
│  📅 Events (Unlimitiert) ✓              │
│  💼 Geschäft (Partner-Zugang) 🔓        │  ← Freigeschaltet
│  ⚙️ Einstellungen                        │
└──────────────────────────────────────────┘
```

---

## 🔧 Technische Integration

### Hook-Registrierung

Plugins registrieren sich beim System-Start:

```php
// In cms-experts/cms-experts.php
class CMS_Experts_Plugin {
    
    private static ?self $instance = null;
    
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Member Menu registrieren
        CMS\Hooks::addFilter('member_menu_items', [$this, 'add_menu_items'], 10, 2);
        
        // Dashboard Widget registrieren
        CMS\Hooks::addAction('member_dashboard_content', [$this, 'render_widget']);
        
        // Profil-Formular Route
        CMS\Hooks::addAction('register_routes', [$this, 'register_routes']);
    }
    
    public function add_menu_items($items, $user) {
        $items['profiles']['expert'] = [
            'label' => 'IT-Experten-Profil',
            'url' => '/member/expert/profile',
            'icon' => '👨‍💻',
            'category' => 'profiles',
            'order' => 10,
            'badge' => $this->get_profile_count($user['id'])
        ];
        return $items;
    }
    
    public function register_routes() {
        global $router;
        
        $router->get('/member/expert/create', [$this, 'show_create_form']);
        $router->post('/member/expert/create', [$this, 'handle_create']);
        $router->get('/member/expert/profile', [$this, 'show_profile']);
    }
}

// Plugin initialisieren
CMS_Experts_Plugin::instance();
```

### Database-Schema Integration

Plugins erstellen ihre Tabellen beim Aktivieren:

```php
// In cms-experts/cms-experts.php
public function activate() {
    global $db;
    
    // Haupttabelle
    $db->query("
        CREATE TABLE IF NOT EXISTS cms_experts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            position VARCHAR(200),
            bio TEXT,
            photo VARCHAR(255),
            linkedin VARCHAR(255),
            xing VARCHAR(255),
            github VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES cms_users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Meta-Tabelle
    $db->query("
        CREATE TABLE IF NOT EXISTS cms_expert_meta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            expert_id INT NOT NULL,
            meta_key VARCHAR(100) NOT NULL,
            meta_value TEXT,
            FOREIGN KEY (expert_id) REFERENCES cms_experts(id) ON DELETE CASCADE,
            INDEX idx_expert_meta (expert_id, meta_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Skills-Tabelle
    $db->query("
        CREATE TABLE IF NOT EXISTS cms_expert_skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            expert_id INT NOT NULL,
            skill_name VARCHAR(100) NOT NULL,
            skill_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
            FOREIGN KEY (expert_id) REFERENCES cms_experts(id) ON DELETE CASCADE,
            INDEX idx_expert_skills (expert_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
```

### API-Endpunkte

Plugins können REST-API Endpunkte registrieren:

```php
// In cms-experts/includes/class-api.php
class CMS_Experts_API {
    
    public function register_endpoints() {
        global $api;
        
        // GET /api/experts - Liste aller Experten
        $api->get('/experts', [$this, 'get_all']);
        
        // GET /api/experts/:id - Einzelner Experte
        $api->get('/experts/:id', [$this, 'get_one']);
        
        // POST /api/experts - Experte erstellen (authentifiziert)
        $api->post('/experts', [$this, 'create'], ['auth' => true]);
        
        // PUT /api/experts/:id - Experte bearbeiten (authentifiziert)
        $api->put('/experts/:id', [$this, 'update'], ['auth' => true]);
        
        // DELETE /api/experts/:id - Experte löschen (authentifiziert)
        $api->delete('/experts/:id', [$this, 'delete'], ['auth' => true]);
    }
}
```

---

## 📊 Workflow-Diagramme

### Gesamt-Workflow (Vereinfacht)

```
┌───────────────────────────────────────────────────────────────┐
│                    REGISTRIERUNGS-WORKFLOW                     │
└───────────────────────────────────────────────────────────────┘

STARTSEITE
    │
    ├─> [Registrieren] Button
    │
    ▼
REGISTRIERUNGS-FORMULAR
    │
    ├─> Username, Email, Passwort eingeben
    │
    ▼
ACCOUNT ERSTELLT (Standard-Member)
    │
    ├─> Email-Verifizierung
    ├─> Automatischer Login
    │
    ▼
MEMBER DASHBOARD (Erstes Login)
    │
    ├─> Willkommens-Screen
    ├─> "Legen Sie Ihr erstes Profil an"
    │
    ▼
PROFIL-TYP AUSWAHL
    │
    ├─> 🔴 REQUIRED (5): Expert, Company, Event, Speaker, Blog
    ├─> 🟢 OPTIONAL (55): je nach aktivierten Plugins
    │   ├─> Content: Portfolio, Testimonials, Pages
    │   ├─> Business: Marketplace, Projects, Jobs
    │   ├─> Events: Webinars, Bookings, Tickets
    │   ├─> Media: Gallery, Video, Podcast
    │   └─> ... (siehe PLUGIN-LIST.MD für alle 60)
    │
    ▼
         ▼
PLUGIN-SPEZIFISCHES FORMULAR
         │
         ├─> Felder ausfüllen
         ├─> Dateien hochladen (Foto, Logo, etc.)
         ├─> Meta-Daten eingeben
         │
         ▼
PROFIL ERSTELLT & GESPEICHERT
         │
         ├─> Daten in Plugin-Tabellen
         ├─> Verknüpfung mit User-Account
         ├─> Hook-Trigger: profile_created
         │
         ▼
DASHBOARD AKTUALISIERT
         │
         ├─> Neuer Menü-Punkt erscheint
         ├─> Widget im Dashboard
         ├─> Profil-Karte anzeigbar
         │
         ▼
FEATURE-FREISCHALTUNG
         │
         ├─> Basierend auf Abo-Status
         ├─> Basierend auf Partner-Status
         ├─> Dynamische Menü-Einträge
         │
         ▼
VOLLSTÄNDIGES MEMBER-PROFIL
    │
    └─> Zugang zu allen freigeschalteten Features
```

### Plugin-Lifecycle

```
┌─────────────────────────────────────────────────┐
│            PLUGIN LIFECYCLE                      │
└─────────────────────────────────────────────────┘

PLUGIN AKTIVIERUNG
    │
    ├─> activate() ausführen
    ├─> Datenbank-Tabellen erstellen
    ├─> Standard-Optionen setzen
    │
    ▼
BOOTSTRAP-PHASE
    │
    ├─> Singleton-Instanz erstellen
    ├─> Hooks registrieren
    ├─> Routes registrieren
    ├─> API-Endpunkte registrieren
    │
    ▼
RUNTIME-PHASE
    │
    ├─> Member-Menü erweitern (Filter)
    ├─> Dashboard-Widgets rendern (Action)
    ├─> Formulare verarbeiten
    ├─> Daten speichern/laden
    │
    ▼
HOOK-INTEGRATION
    │
    ├─> Andere Plugins können interagieren
    ├─> Theme kann Templates überschreiben
    ├─> API steht zur Verfügung
    │
    ▼
PLUGIN DEAKTIVIERUNG
    │
    ├─> deactivate() ausführen
    ├─> Cleanup (optional)
    └─> Tabellen bleiben erhalten (Daten-Sicherheit)
```

### Feature-Freischaltung Flow

```
┌─────────────────────────────────────────────────┐
│        FEATURE-FREISCHALTUNG LOGIC              │
└─────────────────────────────────────────────────┘

USER LOGGED IN
    │
    ├─> Lade User-Meta-Daten
    ├─> Subscription-Status prüfen
    ├─> Partner-Status prüfen
    │
    ▼
MENÜ-GENERIERUNG
    │
    ├─> Filter: 'member_menu_items' triggern
    │
    ▼
PLUGINS ANTWORTEN
    │
    ├─> Plugin prüft User-Status
    ├─> Feature erlaubt? → Menü-Item hinzufügen
    ├─> Feature gesperrt? → Skip oder "Upgrade"-Badge
    │
    ▼
MENÜ RENDERN
    │
    ├─> Items nach Kategorien gruppieren
    ├─> Items nach Order sortieren
    ├─> Badges/Icons hinzufügen
    │
    ▼
DYNAMISCHES MENÜ ANZEIGEN
    │
    └─> Nur freigeschaltete Features sichtbar
```

---

## 📚 Zusammenfassung

### Kernpunkte

1. **Einheitliche Registrierung:** Alle Nutzer registrieren sich initial gleich (ohne Typ-Auswahl)
2. **Nachträgliche Profilwahl:** Im Member Dashboard wählt der Nutzer, welche(s) Profil(e) er anlegen möchte
3. **60-Plugin-Ecosystem:** System besteht aus 60 modularen Plugins in 10 Kategorien (siehe PLUGIN-LIST.MD)
4. **5 Required Plugins:** Experts, Companies, Events, Speakers, Blog sind immer aktiv
5. **55 Optionale Plugins:** Aktivierung durch Admin, erscheinen dann im Member-Dashboard
6. **Plugin-Ownership:** Jedes Plugin verwaltet vollständig seine eigenen Daten, Templates, Backend
7. **Modulare Architektur:** Plugins sind unabhängig und können einzeln aktiviert/deaktiviert werden
8. **6 Menü-Kategorien:** Maximum 6 Haupt-Kategorien im Member-Menü (Dashboard, Profile, Netzwerk, Events, Geschäft, Einstellungen)
9. **10→6 Mapping:** 10 Plugin-Kategorien werden auf 6 Member-Menü-Kategorien abgebildet
10. **Status-basierte Features:** Features werden dynamisch freigeschaltet basierend auf Abo/Partner-Status
11. **Hook-basierte Integration:** Alles läuft über das Actions & Filters System

### Plugin-Ecosystem Highlights

**Gesamt:** 60 Plugins  
**Required:** 5 Plugins (cms-experts, cms-companies, cms-events, cms-speakers, cms-blog)  
**Optional:** 55 Plugins (aktivierbar durch Admin)  
**Plugin-Kategorien:** 10 (Core Content, Network, Events, Communication, Commerce, Media, SEO, Security, Analytics, Integration)  
**Member-Menü-Kategorien:** 6 (Dashboard, Profile, Netzwerk, Events, Geschäft, Einstellungen)  
**Profil-Typen:** 60+ verschiedene Content-Types möglich  

### Vorteile dieser Architektur

✅ **Skalierbarkeit** - Neue Content-Typen via Plugin hinzufügbar (60+ verfügbar)  
✅ **Wartbarkeit** - Jedes Plugin ist in sich geschlossen  
✅ **Flexibilität** - Benutzer können mehrere Profile-Typen haben  
✅ **Performance** - Nur aktive Plugins werden geladen (5-60 je nach Konfiguration)  
✅ **Upgrade-Path** - Features schalten sich automatisch frei bei Status-Änderung  
✅ **Code-Qualität** - Single Responsibility Principle durchgesetzt  
✅ **Modularität** - Admin kann Feature-Set per Plugin-Aktivierung steuern  
✅ **Ecosystem-Ready** - 60 vordefinierte Plugins, beliebig erweiterbar  

---

## 🔗 Verwandte Dokumentation

- **[PLUGIN-LIST.MD](../PLUGIN-LIST.MD)** - ⭐ Vollständige Liste aller 60 Plugins mit Spezifikationen
- [PLUGIN-DEVELOPMENT.md](../PLUGIN-DEVELOPMENT.md) - Plugin-Entwicklungs-Guide
- [HOOKS-REFERENCE.md](../HOOKS-REFERENCE.md) - Alle verfügbaren Hooks
- [ARCHITECTURE.md](../ARCHITECTURE.md) - System-Architektur
- [DATABASE-SCHEMA.md](../DATABASE-SCHEMA.md) - Datenbank-Struktur

---

**Ende der Dokumentation**  
*Für Fragen oder Erweiterungen, siehe: [365CMS Documentation Index](../INDEX.md)*
