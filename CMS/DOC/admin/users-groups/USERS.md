# 365CMS – Benutzerverwaltung

Kurzbeschreibung: Verwaltung von Benutzerkonten, Status, Rollen, Gruppenbezug und Bearbeitungsabläufen im Admin.

Letzte Aktualisierung: 2026-05-13 · Version 2.9.781

---

## Überblick

Die Benutzerverwaltung ist die zentrale Oberfläche für alle registrierten Accounts. Sie bündelt Listenansicht, Filter, Bearbeitung und statusabhängige Aktionen.

---

## Typische Funktionen

| Funktion | Beschreibung |
|---|---|
| Suche | Benutzer schnell nach Name oder Mail finden |
| Rollenfilter | Ansicht nach dynamisch verfügbaren Rollen eingrenzen |
| Bearbeiten | Profil- und Kontodaten ändern |
| Wirkungsvorschau | Rollenwechsel vor dem Speichern read-only auf Capabilities, Member-Bereiche, Plugin-Widgets und Pakete prüfen |
| Support-Kontext | Aktive Direktpakete, Gruppenpakete, Member-Module und Vertragsfristen pro Benutzer bzw. Gruppe sehen |
| Sicherheitsereignisse | Begrenzte Login-/Security-Audit-Einträge direkt im Profil prüfen |
| Statussteuerung | aktivieren, deaktivieren, sperren |
| Gruppenbezug | Mitgliedschaften sichtbar machen |

---

## Aktueller Stand

Wichtige Korrekturen der neueren Releases:

- Rollen-Dropdowns und Filter greifen auf die gleiche dynamische Rollenquelle zu wie die Rechteverwaltung.
- Capability-Prüfungen arbeiten mit den in `role_permissions` gespeicherten Rechten.
- Die Listenansicht zeigt den Gruppenbezug pro Benutzer wieder sichtbar als Gruppenanzahl an.
- Bulk-Aktionen benennen die gewählte Operation jetzt explizit und bleiben ohne valide Auswahl/Aktion gesperrt.
- Benutzererstellung und -bearbeitung erzwingen dieselbe Passwort-Policy wie Registrierung und Passwort-Reset: mindestens 12 Zeichen plus Groß-/Kleinbuchstaben, Ziffer und Sonderzeichen.
- Der Benutzer-Editor zeigt am Rollenfeld eine read-only Wirkungsvorschau für die Zielrolle. Der Vergleich läuft ohne AJAX und ohne neue Schreibroute, wertet die vorhandene Rollenmatrix, Member-Dashboard-Konfiguration, Plugin-Widget-Registry und Paket-/Gruppenbezüge fail-soft aus und weist darauf hin, dass bestehende Abos/Gruppenpakete nicht automatisch verändert werden.
- Die Benutzerliste zeigt pro Benutzer eine kompakte Support-Kontext-Zeile mit Direktpaket, Gruppenpaketen, sichtbaren Member-Bereichen und Vertragsfriststatus. Die Gruppenübersicht zeigt denselben Kontext pro Gruppe inklusive Paketmodulen und fälligen Verträgen der Mitglieder. Beide Pfade sind read-only und ändern keine Pakete oder Laufzeiten.
- Bestehende Benutzerprofile zeigen die letzten relevanten Login- und Sicherheitsereignisse aus `audit_log` read-only an; bei Audit-Log-Problemen fällt die Karte fail-soft auf einen neutralen Hinweis zurück.
- Interne Exception-Texte aus Benutzer-Speichern oder -Löschen werden seit `2.9.731` nur noch serverseitig protokolliert; Admin-Alerts und Fehlerreport-Payloads bleiben generisch.
- Medien- und andere abhängige Bereiche können Benutzerzustände konsistenter auswerten.

---

## Datenbasis

Relevant sind insbesondere:

- `users`
- `user_meta`
- `role_permissions` für Capability-Vergleiche
- `settings` für Member-Dashboard- und Standardpaket-Konfiguration
- `user_subscriptions`, `subscription_plans`, `user_groups`, `user_group_members` für die Paketwirkungsvorschau
- `sessions`
- `login_attempts`
- `failed_logins`
- `audit_log` für zusammenfassende Login-/Sicherheitsereignisse im Profil

---

## Verwandte Dokumente

- [GROUPS.md](GROUPS.md)
- [RBAC.md](RBAC.md)
- [../member/README.md](../member/README.md)

