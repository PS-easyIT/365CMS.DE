# 365CMS – Plugin-Ökosystem Roadmap

**Bereich:** Plugin-System, Erweiterungen, Plugin-Überlegungen  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## Plugin-Architektur-Überlegungen

### Grundprinzipien (nicht verhandelbar)
- **Single Responsibility:** Ein Plugin = Eine klar abgrenzbare Funktion
- **Hook-First:** Keine direkte Klassen-Abhängigkeit zwischen Plugins
- **Namespace-Isolation:** Eigene Namespace, eigene Datenbank-Tabellen mit Prefix
- **Graceful Degradation:** CMS funktioniert auch ohne optionale Plugins
- **Version-Contract:** Plugin deklariert Mindest-CMS-Version

### Plugin-Typen
| Typ | Beschreibung | Beispiel |
|---|---|---|
| **REQUIRED** | CMS-Core braucht dieses Plugin | cms-users, cms-pages |
| **BUNDLED** | Standardmäßig installiert, aber deaktivierbar | cms-blog, cms-media |
| **OPTIONAL** | Erst auf Bedarf installierbar | cms-shop, cms-booking |
| **ENTERPRISE** | Kommerzielle Erweiterungen, Lizenz-basiert | cms-sso, cms-analytics-pro |
| **COMMUNITY** | Von Dritten entwickelt, Marketplace verfügbar | Beliebig |

---

## 🔴 KRITISCHE PLUGIN-FEATURES

### P-K-01 · Plugin-Marketplace & Registry
**Priorität:** 🔴 Kritisch  
**Beschreibung:** Offizieller Marktplatz für CMS-Plugins

| Stufe | Feature |
|---|---|
| Stufe 1 | Plugin-Registry (JSON-basierte Metadaten-API) |
| Stufe 2 | Admin-UI: Browse, Suche, Filter nach Kategorie |
| Stufe 3 | One-Click-Install direkt aus dem Admin |
| Stufe 4 | Update-Benachrichtigungen für installierte Plugins |
| Stufe 5 | Plugin-Bewertungen und Reviews (Community-Feedback) |
| Stufe 6 | Premium-Plugins mit Lizenz-Validierung |
| Stufe 7 | Entwickler-API zum Einreichen von Plugins |
| Stufe 8 | Automatisierte Security-Scans vor Veröffentlichung |

---

### P-K-02 · Plugin-Sandboxing
**Priorität:** 🔴 Kritisch  
**Beschreibung:** Isolierte Ausführung von Plugins zur Absicherung

| Stufe | Feature |
|---|---|
| Stufe 1 | Datei-System-Permissions (Plugin darf nur eigenes Verzeichnis schreiben) |
| Stufe 2 | Capability-System (Plugin deklariert benötigte Fähigkeiten) |
| Stufe 3 | DB-Tabellen-Whitelist (Plugin darf nur eigene Tabellen lesen/schreiben) |
| Stufe 4 | HTTP-Anfragen-Whitelisting (welche Domains darf Plugin kontaktieren) |
| Stufe 5 | Memory/Time-Limits pro Plugin-Ausführung |

---

## 🟠 HIGH-PRIORITY: KERN-PLUGIN-AUSBAU

### P-H-01 · cms-experts (Ausbaustufen)
**Priorität:** 🟠 High  
**Status:** Production Ready (Basis)

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | ✅ Basis CRUD, Card/Detail-Templates | Abgeschlossen |
| Stufe 2 | Verification-System (verifizierter Experte mit Badge) | 🟠 High |
| Stufe 3 | Verfügbarkeits-Kalender (wann ist Experte buchbar) | 🟠 High |
| Stufe 4 | Skill-Endorsements (andere User bestätigen Skills) | 🟡 Mittel |
| Stufe 5 | Portfolio-Integration (Projekte auf Experten-Profil) | 🟡 Mittel |
| Stufe 6 | Bewertungs-System (1-5 Sterne, nach Buchung) | 🟠 High |
| Stufe 7 | Empfehlungs-Algorithmus (ähnliche Experten) | 🟡 Mittel |
| Stufe 8 | CV-Import von LinkedIn/XING (via API) | 🟢 Low |
| Stufe 9 | KI-generierte Profil-Zusammenfassung | 🟢 Low |
| Stufe 10 | Partner-Status (Sponsor, Top-Partner, Partner) mit visuellen Badges | 🟠 High |
| Stufe 11 | Premium-Experte-Badge (bezahlte Hervorhebung) | 🟡 Mittel |
| Stufe 12 | Experten-Vergleichs-Tool (Side-by-Side bis 3 Profile) | 🟡 Mittel |

---

### P-H-02 · cms-companies (Ausbaustufen)
**Priorität:** 🟠 High  
**Status:** Production Ready (Basis)

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | ✅ Basis CRUD, Experten-Beziehungen | Abgeschlossen |
| Stufe 2 | Branchen-Taxonomie (erweiterbar per Plugin) | 🟠 High |
| Stufe 3 | Firmen-Mediacenter (Bilder, Videos, Downloads) | 🟡 Mittel |
| Stufe 4 | Stellenangebote-Integration (cms-jobs-Verknüpfung) | 🟠 High |
| Stufe 5 | Firmen-Blog (eigener Content-Bereich im Profil) | 🟡 Mittel |
| Stufe 6 | Follower-System (Nutzer folgen Firmen für Updates) | 🟡 Mittel |
| Stufe 7 | Partnerschafts-Badges (Sponsor/Member) | 🟠 High |
| Stufe 8 | Firmen-Analytics (Profilaufrufe, Kontaktanfragen) | 🟡 Mittel |
| Stufe 9 | Verified-Business-Status (amtliche Bestätigung) | 🟢 Low |
| Stufe 10 | Produkt/Dienste-Showcase auf Firmenprofil | 🟡 Mittel |

---

### P-H-03 · cms-events (Ausbaustufen)
**Priorität:** 🟠 High  
**Status:** Production Ready (Basis)

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | ✅ Basis-Events, Kalender-Ansicht, Speaker-Zuordnung | Abgeschlossen |
| Stufe 2 | Event-Typen (Konferenz, Workshop, Webinar, Meetup, Hackathon) | 🟠 High |
| Stufe 3 | Wiederkehrende Events (wöchentlich, monatlich) | 🟡 Mittel |
| Stufe 4 | Online/Hybrid-Event-Unterstützung (Zoom/Teams-Links) | 🟠 High |
| Stufe 5 | Warteliste mit automatischer Nachrück-Funktion | 🟠 High |
| Stufe 6 | Event-Ticket-System (kostenlos & kostenpflichtig) | 🟠 High |
| Stufe 7 | QR-Code Check-In für Vor-Ort-Events | 🟡 Mittel |
| Stufe 8 | Multi-Track-Agenda (parallele Sessions) | 🟡 Mittel |
| Stufe 9 | Event-Bewertungen nach Abschluss | 🟡 Mittel |
| Stufe 10 | Live-Stream-Integration (YouTube, Vimeo Live) | 🟢 Low |
| Stufe 11 | Automatische Erinnerungs-E-Mails (24h, 1h vor Event) | 🟠 High |
| Stufe 12 | iCal-Export / Google-Calendar-Sync | 🟡 Mittel |
| Stufe 13 | Event-Sponsoren-Bereich mit Logo-Präsentation | 🟡 Mittel |

---

### P-H-04 · cms-speakers (Ausbaustufen)
**Priorität:** 🟠 High  
**Status:** Production Ready (Basis)

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | ✅ Speaker-Profile, Themen, Präsentationen | Abgeschlossen |
| Stufe 2 | Booking-Request-System (Speaker buchen für Event) | 🟠 High |
| Stufe 3 | Honorar-Ranges (von/bis) als optionale Info | 🟡 Mittel |
| Stufe 4 | Vortrag-Bewertungen und Feedback | 🟡 Mittel |
| Stufe 5 | Speaker-Reel (Video-Highlights-Sammlung) | 🟡 Mittel |
| Stufe 6 | Verfügbarkeitskalender | 🟠 High |
| Stufe 7 | Regionale Reichweite (lokal, national, international) | 🟡 Mittel |
| Stufe 8 | Reisestatus (bereit für Dienstreisen in diese Länder) | 🟢 Low |

---

## 🆕 NEUE PLUGINS – VOLLSTÄNDIGE PLANUNGEN

### P-NEW-01 · cms-jobs (Stellenmarkt)
**Priorität:** 🟠 High  
**Beschreibung:** Vollständiger Stellenmarkt mit Bewerbungs-Management

| Stufe | Feature |
|---|---|
| Stufe 1 | Custom Post Type `job` mit Feldern (Titel, Beschreibung, Ort, Gehalt, Skills-Required) |
| Stufe 2 | Bewerbungs-Formular (Name, CV-Upload, Anschreiben) |
| Stufe 3 | Bewerbungs-Verwaltung pro Stelle im Admin |
| Stufe 4 | Status-Tracking für Bewerber (Eingegangen, Sichtet, Eingeladen, Abgelehnt) |
| Stufe 5 | E-Mail-Benachrichtigungen an Bewerber und Arbeitgeber |
| Stufe 6 | Bewerbungs-Portale (öffentliche Apply-Page pro Stelle) |
| Stufe 7 | Google Jobs-Schema (structured data) |
| Stufe 8 | Job-Alert E-Mail-Abbonnement (neue Jobs nach Filterkriterien) |
| Stufe 9 | Indeed/StepStone-Import via RSS/API |
| Stufe 10 | KI-Matching (Bewerber vs. Anforderungsprofil) |

**Datenbank-Tabellen:**
- `cms_jobs`
- `cms_job_applications`
- `cms_job_meta`

---

### P-NEW-02 · cms-shop (E-Commerce)
**Priorität:** 🟠 High  
**Beschreibung:** Vollständiger Online-Shop für digitale und physische Produkte

| Stufe | Feature |
|---|---|
| Stufe 1 | Produkt-Katalog (CPT `product`, Kategorien, Attribute) |
| Stufe 2 | Warenkorb (Session-basiert) |
| Stufe 3 | Checkout-Flow (Adresse, Versand, Zahlung) |
| Stufe 4 | Zahlungs-Gateways: Stripe, PayPal, SEPA-Lastschrift |
| Stufe 5 | Digitale Produkte (PDF, Software – sofortiger Download) |
| Stufe 6 | Varianten-Produkte (Größe, Farbe – eigene Preis/Bestand) |
| Stufe 7 | Bestands-Verwaltung mit Niedrigbestand-Alarm |
| Stufe 8 | Bestellungs-Verwaltung und Rechnungs-PDF-Generierung |
| Stufe 9 | Gutscheincodes und Rabatt-Regeln |
| Stufe 10 | Produktbewertungen (nach Kauf) |
| Stufe 11 | Upsells/Cross-Sells auf Produkt- und Warenkorb-Seite |
| Stufe 12 | Abo-Produkte (monatlich/jährlich) |

---

### P-NEW-03 · cms-messaging (Privat-Nachrichten)
**Priorität:** 🟠 High  
**Beschreibung:** Echtzeit-Messaging zwischen CMS-Nutzern

| Stufe | Feature |
|---|---|
| Stufe 1 | Konversationen und Nachrichten (DB-Tabellen) |
| Stufe 2 | Inbox/Sent-Ansicht im Member-Dashboard |
| Stufe 3 | REST-API für Nachrichten (Send, Fetch, Mark-Read) |
| Stufe 4 | Echtzeit-Updates via Server-Sent Events (SSE) |
| Stufe 5 | WebSocket-Server für echtes Echtzeit-Messaging |
| Stufe 6 | Dateianhänge in Nachrichten |
| Stufe 7 | Gruppen-Chats (mehrere Teilnehmer) |
| Stufe 8 | Nachrichtenanfragen-System (nur Verbundene können direkt schreiben) |
| Stufe 9 | Nachrichten-Archivierung und -Export |
| Stufe 10 | E-Mail-Fallback (Benachrichtigung wenn offline) |

---

### P-NEW-04 · cms-subscriptions (Mitgliedschaften)
**Priorität:** 🟠 High  
**Beschreibung:** Verwaltung von Abo-Mitgliedschaften, Zugriffsrechten und Zahlungen

| Stufe | Feature |
|---|---|
| Stufe 1 | Paket-Definition (Gratis, Basic, Pro, Enterprise) |
| Stufe 2 | Feature-Matrix pro Paket (welche Funktionen sind verfügbar) |
| Stufe 3 | Zahlungs-Integration (Stripe Subscriptions, PayPal) |
| Stufe 4 | Billing-Portal (Rechnungen, Zahlungsmethode ändern) |
| Stufe 5 | Paket-Wechsel (Upgrade/Downgrade mit proratierter Abrechnung) |
| Stufe 6 | Testzeitraum-Verwaltung |
| Stufe 7 | Coupon-Codes für Abo-Rabatte |
| Stufe 8 | Webhook von Stripe/PayPal verarbeiten (Zahlungsausfall, Verlängerung) |
| Stufe 9 | Dunning-Management (Mahnwesen bei fehlgeschlagenen Zahlungen) |
| Stufe 10 | Jahresrechnung für Buchhaltung (DATEV-Export) |

---

### P-NEW-05 · cms-newsletter
**Priorität:** 🟡 Mittel  
**Beschreibung:** Eigenes Newsletter-System ohne externe Abhängigkeit

| Stufe | Feature |
|---|---|
| Stufe 1 | Abonnenten-Verwaltung (Opt-In mit Double-Opt-In) |
| Stufe 2 | Listen und Segmente (Abonnenten in Gruppen einteilen) |
| Stufe 3 | Newsletter-Editor (visuelle E-Mail-Erstellung) |
| Stufe 4 | Versand-Engine (über eigenen SMTP oder API-Dienste) |
| Stufe 5 | Öffnungsraten und Klick-Tracking |
| Stufe 6 | Automatisierungs-Sequenzen (Onboarding-Serie) |
| Stufe 7 | A/B-Test für Betreffzeilen |
| Stufe 8 | Abmelde-Center (DSGVO-konform) |

---

### P-NEW-06 · cms-forms (Form-Builder)
**Priorität:** 🟠 High  
**Beschreibung:** Visueller Form-Builder für beliebige Formulare

| Stufe | Feature |
|---|---|
| Stufe 1 | Drag & Drop Form-Builder |
| Stufe 2 | Feld-Typen: Text, E-Mail, Tel, Datum, Auswahl, Checkbox, Datei, Bewertung |
| Stufe 3 | Bedingte Logik (Feld A zeigen, wenn Feld B = X) |
| Stufe 4 | Mehrstufige Formulare (Wizard mit Fortschrittsanzeige) |
| Stufe 5 | Einreichungs-Verwaltung im Admin |
| Stufe 6 | E-Mail-Benachrichtigungen (an Admin und Absender) |
| Stufe 7 | Webhook-Weiterleitung von Einreichungen |
| Stufe 8 | Spam-Schutz (Honeypot, hCaptcha, Turnstile) |
| Stufe 9 | Export aller Einreichungen (CSV, XLSX) |
| Stufe 10 | Datei-Felder mit Virenscanner-Hook |

---

### P-NEW-07 · cms-projects (Projektmanagement)
**Priorität:** 🟡 Mittel  
**Beschreibung:** Integriertes Projektmanagement für Teams

| Stufe | Feature |
|---|---|
| Stufe 1 | Projekt-CRUD (Name, Beschreibung, Deadline, Mitglieder) |
| Stufe 2 | Aufgaben-Verwaltung (Board-/Kanban-Ansicht) |
| Stufe 3 | Zeiterfassung (Start/Stop-Timer, manuelle Buchung) |
| Stufe 4 | Meilensteine und Projektphasen |
| Stufe 5 | Dateiablage pro Projekt |
| Stufe 6 | Kommentare / Aktivitäts-Log pro Aufgabe |
| Stufe 7 | Gantt-Chart-Ansicht |
| Stufe 8 | Rechnungsstellung aus Zeiterfassung |
| Stufe 9 | Integration mit cms-invoicing |
| Stufe 10 | Client-Portal (Kunden sehen Projekt-Status) |

---

### P-NEW-08 · cms-invoicing (Rechnungswesen)
**Priorität:** 🟡 Mittel  
**Beschreibung:** Rechnungs-Erstellung und -Verwaltung

| Stufe | Feature |
|---|---|
| Stufe 1 | Rechnungs-CRUD (Positionen, MwSt., Zahlungsziel) |
| Stufe 2 | PDF-Erstellung aus Vorlage |
| Stufe 3 | USt-ID-Validierung (EU-VIES-API) |
| Stufe 4 | Reverse-Charge-Hinweis für EU-Geschäftskunden |
| Stufe 5 | Zahlungsstatus-Tracking (Offen, Bezahlt, Überfällig) |
| Stufe 6 | Automatische Mahnung (1. und 2. Mahnung) |
| Stufe 7 | SEPA-XML-Export für Bank-Upload |
| Stufe 8 | XRechnung / ZUGFeRD-Format (E-Rechnung ab 2025 Pflicht) |

---

### P-NEW-09 · cms-wiki (Wissensdatenbank)
**Priorität:** 🟡 Mittel  
**Beschreibung:** Internes oder öffentliches Wiki-System

| Stufe | Feature |
|---|---|
| Stufe 1 | Wiki-Artikel mit Kategorien und Tags |
| Stufe 2 | Versionshistorie (Diff-Ansicht) |
| Stufe 3 | Revisions-Rollback |
| Stufe 4 | Zugriffssteuerung (öffentlich/privat/Gruppen) |
| Stufe 5 | Tabellen-of-Contents (automatisch generiert) |
| Stufe 6 | Export als PDF oder EPUB |
| Stufe 7 | KI-gestützte Suche und Zusammenfassung |

---

### P-NEW-10 · cms-helpdesk (Support-System)
**Priorität:** 🟡 Mittel  
**Beschreibung:** Ticket-basiertes Support-System

| Stufe | Feature |
|---|---|
| Stufe 1 | Ticket-CRUD (Öffnen, Kommentieren, Schließen) |
| Stufe 2 | Status-System (Offen, In Bearbeitung, Wartend, Gelöst, Geschlossen) |
| Stufe 3 | Prioritäten (Niedrig, Normal, Hoch, Kritisch) |
| Stufe 4 | SLA-Tracking (wie lange wurde auf Ticket reagiert) |
| Stufe 5 | Kategorien und Tags zur Klassifizierung |
| Stufe 6 | Canned Responses (Vorformulierte Antworten) |
| Stufe 7 | E-Mail-to-Ticket (neues Ticket aus E-Mail) |
| Stufe 8 | Knowledge-Base-Verlinkung (ähnliche Wiki-Artikel vorschlagen) |
| Stufe 9 | Kunden-Zufriedenheits-Rating nach Ticket-Abschluss |
| Stufe 10 | KI-Erstantwort (automatischer Lösungsvorschlag) |

---

### P-NEW-11 · cms-directory (Allgemeines Verzeichnis)
**Priorität:** 🟡 Mittel  
**Beschreibung:** Generisches Verzeichnis-Plugin für beliebige Branchen (nicht IT-spezifisch)

| Stufe | Feature |
|---|---|
| Stufe 1 | Konfigurierbare CPT-Felder (ohne Code via Admin) |
| Stufe 2 | Standort-basierte Suche (Umkreissuche, Karten-Integration) |
| Stufe 3 | Branchen-Filter dynamisch aus Taxonomien |
| Stufe 4 | Eintrags-Claim (Inhaber beansprucht seinen Eintrag) |
| Stufe 5 | Bezahlte Premiumlistung (hervorgehobene Einträge) |
| Stufe 6 | Bewertungs-System mit Antwort-Option |

---

### P-NEW-12 · cms-learning (LMS)
**Priorität:** 🟢 Low  
**Beschreibung:** Learning Management System für Kurse und Lernpfade

| Stufe | Feature |
|---|---|
| Stufe 1 | Kurs-Struktur (Kurs → Kapitel → Lektionen) |
| Stufe 2 | Video-Lektionen mit Fortschritts-Tracking |
| Stufe 3 | Quiz und Tests (Multiple-Choice, Freitext) |
| Stufe 4 | Kurs-Abschluss-Zertifikat (PDF) |
| Stufe 5 | Lernpfade (Kurs A vor Kurs B) |
| Stufe 6 | Instructor-Rollen (Kurs-Autoren) |
| Stufe 7 | SCORM-Import (externe Lernmaterialien) |
| Stufe 8 | Gamification (XP, Badges für abgeschlossene Kurse) |

---

### P-NEW-13 · cms-affiliate (Affiliate-Programm)
**Priorität:** 🟢 Low  
**Beschreibung:** Eigenes Affiliate-und-Referral-Programm

| Stufe | Feature |
|---|---|
| Stufe 1 | Affiliate-Registrierung und Tracking-Links |
| Stufe 2 | Konversions-Tracking (Klick → Kauf) |
| Stufe 3 | Provisions-Modelle (Prozentsatz, Fix-Betrag, CPC) |
| Stufe 4 | Affiliate-Dashboard (Klicks, Conversions, Verdienst) |
| Stufe 5 | Auszahlungs-Verwaltung (Schwellenwert, PayPal/Überweisung) |

---

### P-NEW-14 · cms-surveys (Umfragen)
**Priorität:** 🟢 Low  
**Beschreibung:** Umfragen und Abstimmungstool

| Stufe | Feature |
|---|---|
| Stufe 1 | Umfrage-Erstellung (verschiedene Fragetypen) |
| Stufe 2 | Anonymous- und Auth-Umfragen |
| Stufe 3 | Ergebnis-Auswertung mit Diagrammen |
| Stufe 4 | Abstimmungen (einzelne Frage, zeitlich begrenzt) |
| Stufe 5 | NPS-Abfragen (Net Promoter Score) |

---

### P-NEW-15 · cms-map (Interaktive Karten)
**Priorität:** 🟡 Mittel  
**Beschreibung:** Karten-Integration für standortbezogene Inhalte

| Stufe | Feature |
|---|---|
| Stufe 1 | OpenStreetMap/Leaflet-Integration (datenschutzfreundlich) |
| Stufe 2 | Marker für Experten, Firmen, Events |
| Stufe 3 | Cluster-Ansicht bei vielen Markern |
| Stufe 4 | Umkreissuche (X km vom Standort) |
| Stufe 5 | Route-Anzeige (Anfahrt zu Event/Firma) |
| Stufe 6 | Heat-Map für Nutzer-Dichte (anonymisiert) |

---

## 🔧 PLUGIN-ÜBERGREIFENDE FEATURES

### Cross-Plugin-Überlegungen

| Feature | Beteiligte Plugins | Priorität |
|---|---|---|
| Universeller Aktivitäts-Feed | Alle Plugins | 🟡 Mittel |
| Einheitliche Notification-Engine | Alle Plugins | 🟠 High |
| Globales Tag-System | Blog, Events, Jobs, Portfolio | 🟠 High |
| Einheitliche Zugriffskontrolle (RBAC) | Alle Plugins | 🔴 Kritisch |
| Universeller Shortcode-Builder | Alle Plugins | 🟡 Mittel |
| CMS-übergreifende Volltextsuche | Alle Plugins | 🟠 High |
| Einheitliches Audit-Log | Alle State-ändernden Plugins | 🔴 Kritisch |
| Zentrale Benachrichtigungs-Präferenzen | User-Plugin + alle | 🟠 High |

---

### Plugin-Bundles (Branchenspezifische Pakete)

**IT & Tech Bundle:**
`cms-experts` + `cms-companies` + `cms-events` + `cms-jobs` + `cms-projects` + `cms-wiki`

**Business & Services Bundle:**
`cms-directory` + `cms-booking` + `cms-invoicing` + `cms-helpdesk` + `cms-subscriptions`

**Education Bundle:**
`cms-learning` + `cms-speakers` + `cms-events` + `cms-surveys` + `cms-certificates`

**Commerce Bundle:**
`cms-shop` + `cms-newsletter` + `cms-affiliate` + `cms-subscriptions` + `cms-invoicing`

**Community Bundle:**
`cms-forums` + `cms-messaging` + `cms-groups` + `cms-badges` + `cms-activity-feed`

---

*Letzte Aktualisierung: 19. Februar 2026*
