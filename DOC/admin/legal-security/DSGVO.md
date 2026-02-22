# DSGVO – Datenschutz-Grundverordnung

**Dateien:** `admin/data-access.php`, `admin/data-deletion.php`, `admin/legal-sites.php`

---

## Übersicht

Die DSGVO-Suite deckt die wichtigsten datenschutzrechtlichen Anforderungen ab:
- **Art. 15 DSGVO** – Recht auf Auskunft
- **Art. 17 DSGVO** – Recht auf Löschung ("Recht auf Vergessenwerden")
- **Art. 13/14 DSGVO** – Informationspflichten (Cookie-Richtlinie, Datenschutzerklärung)

---

## Datenzugriff (Art. 15 DSGVO) – `data-access.php`

### Funktionsweise
1. Benutzer oder Betroffene stellt Auskunftsantrag
2. Admin lädt Antrag in der Verwaltung
3. System sammelt alle gespeicherten Daten des Benutzerkontos
4. Exportiert alles als JSON oder PDF

### Gesammelte Datenkategorien

| Kategorie | Quelle |
|-----------|--------|
| Profildaten | `cms_users`, `cms_user_meta` |
| Login-Protokoll | `cms_login_attempts`, `cms_failed_logins` |
| Aktivitäten | `cms_activity_log` |
| Bestellungen | `cms_orders` |
| Abonnements | `cms_user_subscriptions` |
| Nachrichten (Metadaten) | `cms_messages` |
| Cookie-Einwilligungen | `cms_cookie_consents` |
| Medien-Uploads | `cms_media` |

### Antrag-Workflow
```
Antrag eingehen → Admin-Prüfung → Datensatz generieren → Versand an Betroffene
(max. 30 Tage Bearbeitungszeit nach DSGVO Art. 12)
```

### Export-Format
```json
{
  "auskunft_erstellt": "2026-02-22T10:00:00Z",
  "betroffene_person": {
    "id": 42,
    "username": "max.muster",
    "email": "max@example.de",
    "registriert_am": "2026-01-15T08:30:00Z"
  },
  "profildaten": { ... },
  "bestellhistorie": [ ... ],
  "login_protokoll": [ ... ]
}
```

---

## Datenlöschung (Art. 17 DSGVO) – `data-deletion.php`

### Lösch-Workflow
1. **Antrag erfassen** – Betroffener Person oder Benutzername eingeben
2. **Datensatz prüfen** – Admin sieht alle zu löschenden Datenpunkte
3. **Bestätigung** – Zweifache Bestätigung (Modal + Texteingabe)
4. **Durchführung** – Kaskadierte Löschung aller verknüpften Daten
5. **Protokollierung** – Löschnachweis wird generiert und aufbewahrt

### Löschverhalten

| Datenpunkt | Aktion |
|------------|--------|
| Benutzerkonto | Vollständige Löschung |
| Profildaten | Vollständige Löschung |
| Bestellungen | Anonymisierung (Pflicht nach §147 AO: 10 Jahre Aufbewahrung!) |
| Nachrichten | Inhalt gelöscht, Metadaten anonymisiert |
| Login-Protokoll | Vollständige Löschung |
| Medien-Uploads | Dateien gelöscht, DB-Einträge entfernt |
| Cookie-Consents | Anonymisierung (Nachweis-Pflicht!) |

### Lösch-Nachweis
Nach jeder Löschung wird ein Protokoll erstellt:
- Zeitstempel
- Admin der die Löschung durchgeführt hat
- Art der gelöschten Daten
- Nicht-löschbare Daten mit Rechtsbegründung

---

## Rechtstexte & Legal-Sites – `admin/legal-sites.php`

### Generierbare Dokumente

| Dokument | Pflicht | Generator |
|----------|---------|-----------|
| Datenschutzerklärung | ✅ | ✅ |
| Impressum | ✅ (DE) | ✅ |
| Cookie-Richtlinie | ✅ | ✅ (via Cookie-Manager) |
| AGB | ⚙️ (bei Verkauf) | ✅ |
| Widerrufsbelehrung | ✅ (bei Verkauf) | ✅ |

### Legal-Sites Verknüpfung
Rechtstexte werden mit CMS-Seiten verknüpft:
- Seite auswählen oder neu erstellen
- Automatische Verlinkung im Footer-Theme
- Abo-System verweist automatisch auf AGB/Widerruf

---

## Fristen-Übersicht (DSGVO)

| Anforderung | Frist | Quelle |
|-------------|-------|--------|
| Auskunftsanfrage beantworten | 30 Tage | Art. 12 DSGVO |
| Löschantrag erfüllen | Unverzüglich | Art. 17 DSGVO |
| Datenpanne melden (Behörde) | 72 Stunden | Art. 33 DSGVO |
| Datenpanne melden (Betroffene) | Ohne unangemessene Verzögerung | Art. 34 DSGVO |

---

## Verwandte Seiten

- [Cookie-Manager](COOKIES.md)
- [Rechtstexte-Generator](LEGAL.md)
- [Firewall & Sicherheit](FIREWALL.md)
