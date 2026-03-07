# 365CMS – Datenschutz & Privatsphäre

Kurzbeschreibung: Detaildokumentation der Datenschutzseite im Mitgliederbereich mit Export-, Sichtbarkeits- und Löschfunktionen.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

**Route:** `/member/privacy`

---

## Überblick

Die Seite kombiniert drei Funktionsgruppen:

1. Privatsphäre-Einstellungen speichern
2. persönliche Daten exportieren
3. Account-Löschung beantragen

Dafür verwendet die View drei getrennte CSRF-Tokens:

- `privacy_settings`
- `data_export`
- `account_delete`

---

## Aktuell serverseitig gespeicherte Privatsphäre-Felder

Der Handler `handlePrivacyActions()` persistiert im aktuellen Stand verlässlich nur:

- `profile_visibility`
- `show_email`
- `show_activity`

---

## Wichtiger Implementierungshinweis

Die View zeigt zusätzlich weitere Toggle-Felder wie:

- `allow_contact`
- `data_sharing`
- `analytics_tracking`
- `third_party_cookies`

Diese Felder sind in der Oberfläche sichtbar, werden im aktuellen Server-Handler aber **noch nicht vollständig übernommen**. Für technische Dokumentation ist daher entscheidend: sichtbar heißt hier nicht automatisch persistiert.

---

## Datenübersicht

Die Seite zeigt zusammenfassende Zähler aus `dataOverview`, etwa zu:

- Profilinformationen
- Aktivitäten
- Login-Verlauf
- Einstellungen
- Dateien und Gesamtgröße
- Sessions

---

## Datenexport

Die Aktion `export_data` ruft `MemberService::exportUserData()` auf und liefert den Export derzeit direkt als JSON-Download aus.

Die Exportdatei wird mit einem tagesbasierten Dateinamen ausgeliefert.

---

## Account-Löschung

Die Aktion `delete_account` ruft `requestAccountDeletion()` auf und markiert den Account laut aktueller Controller-Logik für eine Löschung mit Karenzzeit.

Die View enthält zusätzlich:

- Passwortfeld zur Bestätigung
- doppelte Bestätigungsdialoge im Browser
- Warnhinweise zur Irreversibilität

---

## Verwandte Dokumente

- [SECURITY.md](SECURITY.md)
- [../SECURITY.md](../SECURITY.md)
- [../../admin/legal/DSGVO.md](../../admin/legal/DSGVO.md)
