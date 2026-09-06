# 365CMS – Updates

Kurzbeschreibung: Core-, Theme- und Plugin-Updates via GitHub-basierter Update-Logik.

Letzte Aktualisierung: 2026-05-12 · Version 2.9.769

---

## Überblick

Das Update-Center bündelt Core-, Theme- und Plugin-Updates zentral unter `/admin/updates`.

Standardmäßig werden GitHub- und Metadaten-basierte Quellen genutzt; die konkrete Ausführung läuft über `UpdatesModule` und den integrierten Update-/Staging-Workflow.

Seit `2.9.628` können im zentralen Update-Center nicht nur Core- und Plugin-, sondern auch Theme-Updates direkt installiert werden, sofern Download-URL, SHA-256 und Zielstruktur den abgesicherten Update-Vertrag erfüllen.

Seit `2.9.766` ergänzt `/admin/updates` zusätzlich eine blockierende Vorabprüfung. Vor einer automatischen Installation werden PHP- und Datenbankversion, notwendige PHP-Erweiterungen, freier Speicherplatz sowie Schreibrechte für `cache/`, `backups/`, `logs/`, `assets/` und die eigentlichen Zielpfade für Core-, Theme- und Plugin-Updates sichtbar geprüft. Blockierende Befunde deaktivieren die Installationsbuttons und werden zusätzlich serverseitig vor dem Installationslauf abgefangen.

Seit `2.9.769` wird die Update-Historie zusätzlich mit einer sprechenden Benutzeranzeige angereichert. Benutzer-IDs werden im Update-Center und in `/admin/cms-logs` serverseitig auf `display_name` plus Rollenbezeichnung aufgelöst; bei nicht mehr vorhandenen Konten bleibt der Verlauf fail-soft über `User #ID` nachvollziehbar.

- Repository: `PS-easyIT/365CMS.DE`
- API: `https://api.github.com`

Beide Werte können über Einstellungen überschrieben werden.

---

## Unterstützte Bereiche

| Bereich | Beschreibung |
|---|---|
| Core | prüft das neueste Release des Haupt-Repositorys |
| Plugins | wertet plugin-spezifische Update-Metadaten aus und installiert unterstützte Pakete zentral |
| Themes | prüft Theme-Metadaten gegen Remote-Stand und installiert unterstützte Theme-Pakete zentral |
| History | zeigt protokollierte Update-Historie |

---

## Sicherheitsrelevante Punkte

- GitHub-Abfragen laufen per cURL mit TLS-Prüfung
- externe Update-URLs werden per SSRF-Guard abgesichert
- Downloads können per SHA-256 verifiziert werden
- fehlende `zip`- oder `curl`-Extensions schränken Funktionen ein
- automatische Installationen werden bei Pflichtbefunden der Vorabprüfung blockiert
- die Vorabprüfung führt keine neue GET-Mutation ein; Installationen bleiben POST-/CSRF-geschützt
