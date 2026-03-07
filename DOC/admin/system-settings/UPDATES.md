# 365CMS – Updates

Kurzbeschreibung: Core-, Theme- und Plugin-Updates via GitHub-basierter Update-Logik.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Überblick

Das Update-Center nutzt standardmäßig GitHub als Quelle.

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
