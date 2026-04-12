# 365CMS – Updates

Kurzbeschreibung: Core-, Theme- und Plugin-Updates via GitHub-basierter Update-Logik.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

---

## Überblick

Das Update-Center bündelt Core-, Theme- und Plugin-Updates zentral unter `/admin/updates`.

Standardmäßig werden GitHub- und Metadaten-basierte Quellen genutzt; die konkrete Ausführung läuft über `UpdatesModule` und den integrierten Update-/Staging-Workflow.

- Repository: `PS-easyIT/365CMS.DE`
- API: `https://api.github.com`

Beide Werte können über Einstellungen überschrieben werden.

---

## Unterstützte Bereiche

| Bereich | Beschreibung |
|---|---|
| Core | prüft das neueste Release des Haupt-Repositorys |
| Plugins | wertet plugin-spezifische Update-Metadaten aus |
| Themes | prüft Theme-Metadaten gegen Remote-Stand |
| History | zeigt protokollierte Update-Historie |

---

## Sicherheitsrelevante Punkte

- GitHub-Abfragen laufen per cURL mit TLS-Prüfung
- externe Update-URLs werden per SSRF-Guard abgesichert
- Downloads können per SHA-256 verifiziert werden
- fehlende `zip`- oder `curl`-Extensions schränken Funktionen ein
