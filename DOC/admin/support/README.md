# Support-Ticket-System

**Datei:** `admin/support.php`

---

## Übersicht

Das integrierte Support-Ticket-System ermöglicht Kommunikation zwischen Mitgliedern und Administratoren. Tickets werden priorisiert, kategorisiert und mit Status-Tracking verwaltet.

---

## Ticket-Übersicht

### Dashboard-Statistiken

| Kachel | Inhalt |
|--------|--------|
| **Offen** | Tickets mit Status `open` |
| **In Bearbeitung** | Tickets mit Status `in_progress` |
| **Gelöst** | Tickets dieser Woche |
| **Ø Antwortzeit** | Durchschnittliche Reaktionszeit |

### Ticket-Tabelle

| Spalte | Beschreibung |
|--------|--------------|
| **#** | Ticket-ID |
| **Betreff** | Kurzbeschreibung des Problems |
| **Benutzer** | Ersteller des Tickets |
| **Kategorie** | Technisch, Abrechnung, Account, Allgemein |
| **Priorität** | Niedrig / Mittel / Hoch / Kritisch |
| **Status** | Offen / In Bearbeitung / Wartend / Gelöst / Geschlossen |
| **Erstellt** | Datum und Uhrzeit |
| **Letzte Aktivität** | Letzter Kommentar-Zeitstempel |

---

## Ticket-Status

| Status | Bedeutung | Wer setzt ihn? |
|--------|-----------|----------------|
| `open` | Neues Ticket, unbearbeitet | System (automatisch) |
| `in_progress` | Admin arbeitet daran | Admin |
| `waiting` | Warte auf Benutzer-Antwort | Admin |
| `resolved` | Lösung bereitgestellt | Admin |
| `closed` | Endgültig geschlossen | Admin oder Benutzer |
| `escalated` | An höhere Ebene weitergeleitet | Admin |

---

## Prioritäten

| Priorität | Farbe | Reaktionszeit (SLA) |
|-----------|-------|---------------------|
| **Kritisch** | 🔴 Rot | < 2 Stunden |
| **Hoch** | 🟠 Orange | < 8 Stunden |
| **Mittel** | 🟡 Gelb | < 24 Stunden |
| **Niedrig** | 🟢 Grün | < 72 Stunden |

---

## Ticket-Detailansicht

### Konversations-Thread
- Chronologische Darstellung aller Nachrichten
- Unterscheidung Benutzer vs. Admin (Farbe, Seite)
- Zeitstempel für jede Nachricht
- Datei-Anhänge möglich (Bilder, PDFs)

### Admin-Aktionen im Detail
- **Antworten** – Nachricht im Thread hinzufügen
- **Status ändern** – Schnell-Dropdown
- **Priorität ändern** – Schnell-Dropdown
- **Kategorie ändern** – Umklassifizierung
- **Zuweisen** – Ticket einem anderen Admin zuweisen
- **Interne Notiz** – Nur für Admins sichtbar
- **Schließen** – Ticket als gelöst markieren

---

## Kategorien verwalten

Standard-Kategorien (erweiterbar):
- Technisches Problem
- Abrechnung / Zahlungen
- Account & Profil
- Feature-Anfrage
- Allgemeine Frage

---

## E-Mail-Benachrichtigungen

| Ereignis | Empfänger |
|----------|-----------|
| Neues Ticket erstellt | Alle Admins |
| Admin antwortet | Ticket-Ersteller |
| Benutzer antwortet | Zuständiger Admin |
| Ticket gelöst | Ticket-Ersteller |
| SLA-Überschreitung | Alle Admins |

---

## Datenbank-Tabellen

| Tabelle | Inhalt |
|---------|--------|
| `cms_support_tickets` | Ticket-Grunddaten |
| `cms_support_messages` | Nachrichten-Thread |

### `cms_support_tickets`

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `user_id` | INT | Ersteller |
| `subject` | VARCHAR | Betreff |
| `category` | VARCHAR | Kategorie |
| `priority` | ENUM | niedrig/mittel/hoch/kritisch |
| `status` | ENUM | open/in_progress/waiting/resolved/closed |
| `assigned_to` | INT | Zuständiger Admin |
| `created_at` | TIMESTAMP | Erstellungszeitpunkt |
| `updated_at` | TIMESTAMP | Letzte Aktualisierung |

---

## Member-Bereich Integration

Mitglieder können im Member-Dashboard:
- Tickets erstellen
- Eigene Tickets einsehen und beantworten
- Ticket-Status verfolgen
- Tickets schließen

---

## Verwandte Seiten

- [Member-Dashboard](../member/README.md)
- [Benachrichtigungen](../member/NOTIFICATIONS.md)
