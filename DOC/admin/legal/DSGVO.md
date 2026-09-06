# 365CMS – DSGVO: Auskunft & Löschung

Kurzbeschreibung: Bearbeitung von Datenschutzanfragen nach Art. 15 und Art. 17 DSGVO im aktuellen Admin-Workflow.

Letzte Aktualisierung: 2026-05-11 · Version 2.9.759

---

## Überblick

Die Bearbeitung von Datenschutzanfragen ist im aktuellen Stand auf die zentrale Sammelseite `/admin/data-requests` fokussiert; ergänzend existieren weiterhin route-nahe Privacy-/Deletion-Einstiege für spezifische Verwaltungsfälle.

Der Entry-Point `CMS/admin/data-requests.php` lädt dafür zwei Module:

- `PrivacyRequestsModule`
- `DeletionRequestsModule`

---

## Anfragearten

| Typ | Zweck | Modul |
|---|---|---|
| Auskunftsanfrage | Datenexport und Bearbeitung nach Art. 15 DSGVO | `PrivacyRequestsModule` |
| Löschanfrage | Bearbeitung von Lösch- bzw. Vergessenwerden-Anfragen | `DeletionRequestsModule` |

---

## Serverseitige Aktionen

Die Sammelseite arbeitet mit dem CSRF-Token `admin_data_requests` und wertet `scope` plus `action` aus.

### Auskunftsanfragen

| Aktion | Bedeutung |
|---|---|
| `process` | Anfrage in Bearbeitung setzen |
| `complete` | Anfrage abschließen |
| `reject` | Anfrage ablehnen |
| `delete` | Anfrageeintrag entfernen |

### Löschanfragen

| Aktion | Bedeutung |
|---|---|
| `process` | Anfrage vorbereiten oder in Bearbeitung nehmen |
| `execute` | Löschung ausführen |
| `reject` | Antrag ablehnen |
| `delete` | Anfrageeintrag entfernen |

---

## Typische Datenquellen bei einer Auskunft

Je nach Benutzer und aktivierten Modulen können unter anderem folgende Bereiche relevant sein:

| Bereich | Typische Tabellen |
|---|---|
| Profildaten | `users`, `user_meta` |
| Sitzungen und Login-Historie | `sessions`, `login_attempts`, `failed_logins` |
| Aktivitätsprotokolle | `activity_log`, ggf. `audit_log` |
| Bestellungen und Abos | `orders`, `user_subscriptions`, `subscription_plans` |
| Nachrichten | `messages` |
| Medien | dateisystem- und JSON-basierte Medienpfade plus zugehörige Metadaten |
| Datenschutzanfragen | `privacy_requests` |

---

## Löschlogik und Grenzen

Nicht jede Information kann immer physisch entfernt werden. In der Praxis ist zu unterscheiden zwischen:

- **vollständiger Löschung**, wenn keine Aufbewahrungspflicht entgegensteht
- **Anonymisierung**, wenn Fach- oder Steuerrecht Daten weiterhin erfordert
- **Ablehnung oder Teilablehnung**, wenn Rechtsgründe eine weitere Speicherung notwendig machen

Besonders bei Bestellungen, Rechnungs- und Zahlungsbezug ist die gesetzliche Aufbewahrungspflicht zu berücksichtigen.

---

## Fristen und Bearbeitung

| Vorgang | Richtwert |
|---|---|
| Auskunft beantworten | in der Regel innerhalb von 30 Tagen |
| Löschantrag bearbeiten | unverzüglich, unter Beachtung gesetzlicher Pflichten |
| Ablehnung dokumentieren | mit nachvollziehbarer Begründung |

Seit Version 2.9.759 berechnet `/admin/data-requests` für offene Auskunfts- und Löschanfragen zusätzlich eine konservative 30-Tage-Pflichtfrist ab Eingang der Anfrage. Offene Vorgänge werden als regulär, fristnah oder überfällig markiert; das Warnfenster beginnt sieben Tage vor Ablauf. Der sichtbare Bearbeitungsstatus bildet den Workflow `Eingegangen` → `In Bearbeitung` → `Erledigt` oder `Abgelehnt` ab.

Fristnahe oder überfällige Vorgänge können über eine eigene Admin-Aktion an die konfigurierte Admin-Mail eskaliert werden. Diese Eskalation ist bewusst eine CSRF-geschützte POST-Aktion und wird über die bestehende Mail-Queue eingeplant; GET-Ansichten bleiben rein lesend und erzeugen keine Token-URLs. Wenn keine gültige Admin-Mail oder keine funktionsfähige Queue-Konfiguration vorhanden ist, fällt die Aktion mit einer sichtbaren Fehlermeldung aus, ohne die Übersichtsseite mit einem HTTP-500 zu destabilisieren.

Zusätzlich werden Zustandswechsel im Admin-Workflow im Audit-Log festgehalten. Das betrifft insbesondere das Starten der Bearbeitung, den Abschluss einer Auskunft, das Ausführen einer Löschung, Ablehnungen mit Begründung, Fristeskalationen sowie das endgültige Entfernen abgeschlossener Anträge aus der Liste.

---

## Verknüpfung mit Legal Sites

Die eigentlichen Rechtstexte werden nicht hier, sondern unter `/admin/legal-sites` gepflegt. Der Bereich `/admin/data-requests` ist für operative Datenschutzanfragen zuständig, nicht für die Pflege von Datenschutzerklärung oder Impressum.

---

## Verwandte Dokumente

- [README.md](README.md)
- [COOKIES.md](COOKIES.md)
- [LEGAL.md](LEGAL.md)
