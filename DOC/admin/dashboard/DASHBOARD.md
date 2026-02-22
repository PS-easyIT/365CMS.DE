# Admin-Dashboard

**Datei:** `admin/index.php`

---

## Übersicht

Das Admin-Dashboard ist die Einstiegsseite des CMS-Admincenters. Es bietet auf einen Blick alle wichtigen Kennzahlen, Schnellzugriffe und System-Informationen.

---

## Haupt-Statistiken (Stat-Cards)

| Kachel | Inhalt | DB-Quelle |
|--------|--------|-----------|
| **Benutzer gesamt** | Alle registrierten Accounts | `cms_users` |
| **Mitglieder aktiv** | Aktive Mitgliedschaften | `cms_subscriptions` |
| **Inhalte** | Beiträge + Seiten | `cms_posts` |
| **Kommentare** | Ausstehende Moderation | `cms_comments` |
| **Medien** | Hochgeladene Dateien | `cms_media` |
| **Support-Tickets** | Offene Tickets | `cms_support_tickets` |
| **Umsatz (Monat)** | Monatlicher Umsatz | `cms_orders` |
| **Seitenaufrufe** | Views der letzten 7 Tage | `cms_analytics` |

---

## Widget-Bereiche

### 1. Traffic-Chart (letzte 14 Tage)
Liniendiagramm mit Seitenaufrufen und Unique Visitors.

### 2. Letzte Aktivitäten
Chronologische Liste der letzten Aktionen:
- Neue Benutzer-Registrierungen
- Neue Bestellungen
- Support-Tickets
- Kommentare
- Login-Versuche

### 3. Schnellzugriff
Direktlinks zu häufig genutzten Funktionen:
- ➕ Neuen Beitrag erstellen
- 🖼️ Medien hochladen
- 👤 Benutzer anlegen
- 🎫 Support-Ticket öffnen

### 4. System-Info Widget

| Info | Wert |
|------|------|
| PHP-Version | z.B. 8.3.6 |
| MySQL-Version | z.B. 10.6.18 (MariaDB) |
| CMS-Version | z.B. 1.8.0 |
| Aktives Theme | z.B. 365Network |
| Aktive Plugins | Anzahl |
| Serverspeicher | freier/gesamt |
| Upload-Limit | aus `php.ini` |

### 5. Ausstehende Aufgaben
Aufmerksamkeit erfordernde Elemente:
- Kommentare in Moderation
- Neue Benutzer (nicht verifiziert)
- Update-Hinweise
- SSL-Ablauf (falls Zertifikat läuft ab)

### 6. Letzte Bestellungen
Kleine Tabelle mit den 5 neuesten Transaktionen.

---

## Quick-Stats Leiste

Schnell-Zahlen direkt unterhalb des Page-Headers:

```
[📅 Heute: 142 Besucher] [📈 Monat: 4.231] [💰 Umsatz: 890€] [🎫 Tickets: 3 offen]
```

---

## Benachrichtigungen & Hinweisbox

Hinweise werden oben auf dem Dashboard angezeigt:
- 🔴 **Kritisch:** Sicherheitsupdates, abgelaufene SSL-Zertifikate
- 🟡 **Warnung:** Updates verfügbar, Performance-Probleme
- 🟢 **Info:** Neue Benutzer, neue Bestellungen

---

## Dashboard-Widgets konfigurieren

Admins können im Abschnitt [Dashboard-Widgets](../../admin/DASHBOARD-WIDGETS.md) steuern, welche Widgets angezeigt werden und in welcher Reihenfolge.

---

## Performance

Das Dashboard nutzt serverseitiges Response-Caching:
- Cache-TTL: 5 Minuten für Statistiken
- Sofortige Invalidierung bei: neue Bestellung, neuer Benutzer
- Manuell leeren: Einstellungen → Cache → Dashboard-Cache leeren

---

## Zugriffsrechte

| Rolle | Dashboard-Zugriff | Vollständige Statistiken |
|-------|------------------|--------------------------|
| Super-Admin | ✅ | ✅ |
| Admin | ✅ | ✅ |
| Editor | ✅ | Nur Inhalts-Stats |
| Moderator | ✅ | Nur Moderation-Stats |
| Support | ✅ | Nur Support-Stats |

---

## Verwandte Seiten

- [Dashboard-Widgets konfigurieren](../../admin/DASHBOARD-WIDGETS.md)
- [Analytics](../seo-performance/ANALYTICS.md)
- [System & Diagnose](../system-settings/README.md)
- [Support-Tickets](../support/README.md)
