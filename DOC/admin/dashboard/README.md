# 365CMS – Admin-Dashboard

Kurzbeschreibung: Überblick über die Startseite des Admin-Bereichs mit KPI-Karten, Statushinweisen, Schnellzugriffen, rollenbasierten Standardvorlagen und fail-soften Statusblöcken.

Letzte Aktualisierung: 2026-05-12 · Stand: Dashboard ohne Pflichtseitencheck · Release 2.9.772

**Admin-Route:** `/admin`

---

## Überblick

Das Dashboard ist der Standard-Einstieg nach dem Login und fasst Kerninformationen aus Inhalt, Benutzern, Technik und Verwaltung zusammen.

Die konkrete Ausprägung kann sich mit neuen Modulen und Widgets verändern; für die Navigation bleibt die Sidebar maßgeblich.

Im aktuellen Stand bildet das Dashboard vor allem den Überblick über:

- kompakte KPI-Karten für Kernzahlen und Sofortnavigation
- Statusblöcke für System, Sicherheit und Performance
- Aktivitätslisten/Audit-nahe Ereignisse
- Schnellzugriffe in häufig genutzte Admin-Bereiche
- priorisierte Hinweise für offene To-dos oder Sicherheitsauffälligkeiten
- segmentweise Fallbacks, falls einzelne Statistikquellen temporär ausfallen
- benutzerbezogene Sichtbarkeitsprofile für optionale Dashboard-Bereiche
- einzeln schaltbare Widgets innerhalb der zentralen Arbeitsübersicht
- serverseitig gespeicherte Favoriten und eine lokale Liste zuletzt genutzter Admin-Ziele
- rollenbasierte Standardvorlagen für neue oder zurückgesetzte persönliche Dashboard-Ansichten
- persistente Reihenfolge für Arbeits-Widgets und Favoriten per Drag-&-Drop oder Pfeil-Fallback

---

## Typische Inhalte

| Bereich | Inhalt |
|---|---|
| Zentrale Arbeitsübersicht | einzeln schaltbare Widgets für Benutzer, Seiten, Beiträge, Medien, Wachstum, Pipeline, Kommentare, Sessions, Security, System und optional Umsatz – inklusive persistenter Reihenfolge |
| Favoriten & zuletzt genutzt | persönliche Schnellzugriffe aus serverseitig gespeicherten Favoriten plus browserlokale Verlaufsliste zuletzt genutzter Admin-Ziele; Favoriten bleiben ebenfalls sortierbar |
| Prioritäten | offene Bestellungen, fehlgeschlagene Logins oder HTTPS-Hinweise |
| Dashboard-Gesundheit | Warnhinweis, wenn einzelne Statistiksegmente nur mit Fallback-Daten gerendert werden |
| Systemstatus | PHP-, CMS- und MySQL-Version sowie Laufzeit-/Upload-Kontext |
| Sicherheit & Performance | Security-Score, HTTPS, fehlgeschlagene Logins, RAM- und Performance-Score |
| Schnellzugriffe | Header-Aktionen für neue Inhalte, Medien und zentrale Admin-Bereiche |
| Aktivitätsbezug | letzte relevante Änderungen oder Aktionen aus dem Audit-/Aktivitätskontext |

---

## Personalisierung seit 2.9.701 / 2.9.716 / 2.9.717 / 2.9.718 / 2.9.719 / 2.9.720

Admins können ihre Startansicht direkt auf `/admin` über „Dashboard personalisieren“ fokussieren. Die Auswahl wird pro Admin-Benutzer in `settings` als `admin_dashboard_preferences_user_<id>` gespeichert und bewusst mit `autoload = 0` abgelegt.

Sichtbar/ausblendbar sind optionale Blöcke wie:

- Nächste Aufmerksamkeit
- Systemstatus
- Sicherheit & Performance
- Neueste Bestellungen, sofern das Order-Modul aktiv ist
- Letzte Aktivitäten

Die zentrale Arbeitsübersicht und kritische Alerts bleiben absichtlich sichtbar. Dadurch wird Personalisierung nicht zur Sicherheitsblindheit.

Seit `2.9.716` gilt das zusätzlich innerhalb der zentralen Arbeitsübersicht: Dort können Admins einzelne Widgets wie Kommentar-Moderation, Sessions, Security Snapshot oder System-Stack pro Benutzer separat zu- oder abschalten, ohne die gesamte Hauptsektion zu verlieren.

Seit `2.9.717` lassen sich außerdem vordefinierte Favoriten-Ziele pro Admin-Benutzer speichern. Der getrennte Block „Zuletzt genutzt“ arbeitet ergänzend browserlokal via Web Storage und bleibt bewusst nicht Teil des serverseitigen Benutzerprofils.

Seit `2.9.718` bleibt diese Personalisierung nicht mehr auf Sichtbarkeit beschränkt: Arbeits-Widgets und Favoriten können jetzt zusätzlich sortiert werden. Die UI nutzt Drag-&-Drop als Komfortpfad, hält aber Auf/Ab-Buttons als Fallback bereit, damit die Funktion auch ohne gelungene Drag-Interaktion bedienbar bleibt.

Seit `2.9.719` wird der browserlokale Recent-Block zusätzlich nachgehärtet: Beim Lesen und Schreiben werden nur gültige interne Admin-Ziele übernommen, doppelte oder beschädigte Einträge entfernt, URL-/Label-Längen begrenzt und der gespeicherte Verlauf klein gehalten. Außerdem liegt das Dashboard-spezifische CSS nun als cachebares Seiten-Asset vor statt inline in der View.

Seit `2.9.720` erhalten neue oder zurückgesetzte persönliche Ansichten zusätzlich eine **rollenbasierte Standardvorlage**. Diese Vorlage liefert pro Rolle bzw. capability-basierter Rollenfamilie sinnvolle Defaults für sichtbare Bereiche, aktive Arbeits-Widgets, Favoriten und deren Reihenfolge. Persönliche Änderungen bleiben weiterhin benutzerbezogen; über „Rollen-Vorlage wiederherstellen“ kann ein Admin jederzeit sauber auf den Standard seiner Rolle zurückfallen.

Der Speichern-Flow läuft über die gemeinsame Admin-Section-Shell mit CSRF-Prüfung, normalisiert eingereichte Bereichs-, Widget- und Favoriten-Schlüssel sowie ihre Reihenfolgen serverseitig gegen eine Allowlist und schreibt einen Audit-Eintrag `dashboard.preferences.save`. Seit `2.9.705` toleriert die CSRF-Schicht mehrere parallel geöffnete Admin-Formulare derselben Action innerhalb des TTL-Fensters; der tatsächlich verwendete Token wird nach erfolgreicher Prüfung weiterhin verbraucht.

---

## UI-Hinweise

- Arbeits-Widgets sind bewusst kompakt und scan-orientiert aufgebaut, damit auf kleineren Screens mehr Kernsignale gleichzeitig sichtbar bleiben.
- Karten dienen primär als Einstieg in Detailseiten; längere Erklärtexte gehören in die Zielbereiche statt in die Startansicht.
- Warnungen auf der Startseite bleiben auf relevante `warning`-/`danger`-Fälle begrenzt, damit das Dashboard nicht zur Alert-Wand mutiert.
- Quicklinks und Filter-/Sortierlogik gehören außerhalb einzelner Kartenblöcke, damit die Kartensammlung visuell ruhig bleibt.
- Fällt nur eine Datenquelle aus, bleibt das Dashboard insgesamt renderbar; der degradierte Zustand wird über einen Hinweis auf `CMS Logs` transparent gemacht.
- Die Personalisierung ändert nur die Sichtbarkeit optionaler Blöcke bzw. vordefinierter Widgets, nicht die zugrunde liegenden Berechtigungen oder Audit-/Warnlogik.
- „Zuletzt genutzt“ speichert nur nicht-sensitive interne Navigationsziele im Browser; bei deaktiviertem Storage fällt der Block still auf einen leeren Zustand zurück und bereinigt beschädigte oder veraltete Einträge fail-soft.
- Die Sortierung ist progressiv erweitert: Drag-&-Drop ist Komfort, die Pfeilbuttons sind der robuste Fallback. So bleibt die Personalisierung auch dann nutzbar, wenn Browser-DnD nicht ideal funktioniert.

---

## Wichtige Referenzen im aktuellen Stand

- CMS-Versionen dürfen nicht mehr als historische 0.x-Stände dokumentiert werden
- für vertiefte Technikzustände sind heute spezialisierte Unterseiten zuständig, z. B. Diagnose oder Performance
- das Dashboard ist Startpunkt, aber nicht mehr die alleinige Systemübersicht
- Arbeits-Widgets und Attention-Items arbeiten aus derselben Stats-Basis wie Dashboard-Alerts, damit Kennzahlen konsistent bleiben
- Bestellbezogene Blöcke erscheinen nur, wenn die zugehörigen Subscription-/Orders-Module aktiv sind
- Statistiksegmente werden seit `2.9.615` einzeln fail-soft geladen und bei Ausfall mit strukturiertem Logger-Hinweis auf dem Kanal `dashboard` protokolliert
- Dashboard-Sichtbarkeitsprofile werden seit `2.9.701` pro Admin-Benutzer serverseitig gespeichert, CSRF-geschützt geändert und auditierbar protokolliert; seit `2.9.716` umfasst das auch einzelne Arbeitsübersichts-Widgets, seit `2.9.717` zusätzlich serverseitig gespeicherte Favoriten-Ziele, seit `2.9.718` persistente Reihenfolgen für Widgets und Favoriten, seit `2.9.719` eine nachgehärtete browserlokale Recent-Persistenz plus cachebares Dashboard-CSS und seit `2.9.720` rollenbasierte Default-/Reset-Vorlagen für neue oder zurückgesetzte Benutzeransichten. Seit `2.9.705` ist dieser POST-Flow robuster gegen stale Tabs und parallel gerenderte Formulare

---

## Verwandte Dokumente

- [DASHBOARD.md](DASHBOARD.md)
- [../README.md](../README.md)
- [../system-settings/README.md](../system-settings/README.md)
