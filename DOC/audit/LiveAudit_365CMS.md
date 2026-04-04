# Live Audit – 365CMS.DE – 04.04.2026

## Zielbild

Geprüft wurde der aktuell öffentlich erreichbare Produktionsstand von `https://365cms.de/`.
Der Fokus lag auf dem **tatsächlich ausgelieferten Verhalten** und nicht auf lokalem Quellcode.

Geprüft wurden insbesondere:

- Erreichbarkeit zentraler Start-, Auth- und Blog-Seiten
- Qualität aktiv verlinkter Footer- und Formularziele
- sichtbare Release-/Versionskonsistenz
- grundlegende UX- und Vertrauenssignale im öffentlichen Frontend

## Wichtiger Bewertungsrahmen

`404`-Seiten wurden **nicht pauschal negativ gewertet**, weil einige Routen bewusst fehlen können.
Als echter Befund wurden nur `404`-Fälle bewertet, wenn sie über **aktiv sichtbare Kernlinks** der Site direkt verlinkt werden.

## Kurzfazit

Der Live-Stand ist **grundsätzlich erreichbar und nutzbar**, zeigt aber eine deutliche Lücke bei den öffentlichen Vertrauens- und Legal-Signalen:

1. **Startseite, Login, Registrierung und Passwort-Reset funktionieren öffentlich.**
2. **Der Blog liefert einen sauberen Empty-State statt Fehlerzustand.**
3. **Prominent verlinkte Rechtsziele sind live defekt** (`/impressum`, `/datenschutz`, `/agb`).
4. **Die Live-Seite zeigt noch Version `2.8.2`**, während der lokale Repo-Stand bereits `2.8.3` dokumentiert.

## Live-Snapshot-Score

| Bereich | Score | Kurzbegründung |
|---|---:|---|
| Routing & Erreichbarkeit | 71,00 | Kernseiten sind erreichbar; aktiv verlinkte Legal-Ziele brechen jedoch weg |
| Auth- & Formular-Flows | 84,00 | Login, Register und Passwort-Reset sind öffentlich vorhanden und strukturiert |
| Legal & Trust Signals | 24,00 | Footer- und Formularlinks auf `Impressum`, `Datenschutz` und `AGB` laufen in 404 |
| Content- & Release-Konsistenz | 58,00 | Live-Frontend meldet `2.8.2`, während lokal `2.8.3` dokumentiert ist |
| Navigation & Footer-Integrität | 44,00 | Navigation steht, aber zentrale Vertrauenslinks im Footer sind defekt |
| **Gesamt** | **56,20** | Funktional nutzbar, aber mit klarer Produktionslücke bei Legal-Zielen und Release-Konsistenz |

## Geprüfte URLs

- `https://365cms.de/`
- `https://365cms.de/login`
- `https://365cms.de/register`
- `https://365cms.de/forgot-password`
- `https://365cms.de/blog`
- `https://365cms.de/impressum`
- `https://365cms.de/datenschutz`
- `https://365cms.de/agb`

## Relevante Befunde

| Severity | Befund | Beleg |
|---|---|---|
| hoch | Aktiv verlinktes `Impressum` läuft in 404 | Footer-Link auf Startseite, Login, Register und Passwort-Reset zeigt auf `/impressum`, live jedoch 404 |
| hoch | Aktiv verlinktes `Datenschutz` läuft in 404 | Footer-Link auf mehreren öffentlichen Seiten zeigt auf `/datenschutz`, live jedoch 404 |
| mittel | Aktiv verlinkte `AGB` laufen in 404 | Registrierungsformular verlinkt auf `/agb`, live 404 |
| mittel | Versionsdrift zwischen Live-Site und lokalem Repo | Homepage zeigt `2.8.2`, während `README.md` und `Changelog.md` lokal `2.8.3` dokumentieren |
| niedrig | Blog ist leer, aber UX-seitig sauber behandelt | `/blog` zeigt „Keine Artikel gefunden“ statt Fehlerseite |

## Positive Beobachtungen

- Die Startseite lädt stabil und konsistent.
- Login-Formular inklusive „Vergessen?“-Link ist öffentlich vorhanden.
- Registrierung ist erreichbar und enthält einen strukturierten Formularfluss.
- Passwort-Reset ist als eigenständige Seite erreichbar.
- Blog-Empty-State ist kontrolliert und kein technischer Fehler.

## Erwartetes Zielverhalten

### Rechtliche Ziele

- Die aktiv verlinkten Seiten `/impressum` und `/datenschutz` müssen live erreichbar sein.
- Der Registrierungslink zu den Nutzungsbedingungen darf nicht ins Leere laufen.

### Release-Konsistenz

- Die sichtbare öffentliche Versionsanzeige sollte zum dokumentierten lokalen Release-Stand passen.
- Alternativ muss klar erkennbar sein, dass die Live-Site bewusst noch auf einem älteren Deploy-Stand läuft.

### Footer-Integrität

- Prominent verlinkte Vertrauens- und Pflichtziele dürfen im Footer nicht auf generische 404-Seiten zeigen.

## Empfohlene Remediation-Reihenfolge

1. **`/impressum` live herstellen oder Footer-Link temporär entfernen**
2. **`/datenschutz` live herstellen oder Footer-Link temporär entfernen**
3. **`/agb` entweder bereitstellen oder Registrierungslink auf ein vorhandenes Ziel umstellen**
4. **Release-Stand zwischen Live und Repo synchronisieren oder bewusst kennzeichnen**

## Einordnung

Dieser Report bewertet den **Live-Auslieferungsstand von `365cms.de`**.
Er ergänzt den lokalen Code-/Snyk-Audit um einen Produktions-Snapshot und dient als konkrete Arbeitsliste für öffentlich sichtbare Frontend- und Vertrauensprobleme.