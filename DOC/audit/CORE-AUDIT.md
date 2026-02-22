# CORE AUDIT – 365CMS (`/CMS`)

## Scope
- Verzeichnis: `CMS/core/`
- Stichproben: `Bootstrap.php`, `Database.php`, `Router.php`, `Hooks.php`, `PluginManager.php`, `ThemeManager.php`, Services
- Fokus: Architektur, Verantwortlichkeiten, Erweiterbarkeit, Stabilität

## Positiv bewertet
- **Klare Kernkomponenten** (DB, Security, Auth, Router, Hooks, Theme/Plugin-Manager).
- **Singleton-Muster** konsistent für zentrale Runtime-Services.
- **Hook-System** (`Actions`/`Filters`) als Erweiterungspunkt ähnlich WordPress.
- **Service-Schicht** in `core/Services/` strukturiert Domänenlogik von Views.
- **Strict Types** in Core-Dateien für robustere Laufzeit.

## Kritische / relevante Findings
1. **Bootstrap koppelt viele Verantwortlichkeiten**
   - `Bootstrap::initializeCore()` initialisiert nahezu alle Subsysteme direkt.
   - Risiko: geringere Testbarkeit und schwierigere Teilstarts (z. B. CLI/Worker).

2. **Core-Initialisierung führt Side-Effects aus**
   - Plugin-/Theme-Loading und DB-Table-Checks sind eng an Request-Start gekoppelt.
   - Risiko: schwer kontrollierbares Verhalten bei Sonderpfaden.

3. **Fehlende explizite Modulgrenzen für Infrastruktur vs. Domain**
   - Einige Services mischen I/O, Präsentationsbezug und Business-Regeln.
   - Risiko: steigende Wartungskosten bei Feature-Wachstum.

## Empfehlungen (priorisiert)
### P1 – kurzfristig
- Core-Initialisierung dokumentiert in klaren Phasen (Config, Security, Storage, Extensions, Routing).
- Side-Effects (Schema-Check, Plugin-Scan) über Feature-Flags/Boot-Profile steuerbar machen.

### P2 – mittelfristig
- Leichtgewichtigen Dependency-Container oder Factory-Layer zur Entkopplung einführen.
- Services entlang klarer Boundaries trennen: Domain-Service, Repository, Presenter.

### P3 – langfristig
- Definierte Kernel-Modi (`web`, `admin`, `cli`, `maintenance`) für gezielte Startpfade.
- Architekturtests (z. B. Layer-Regeln) zur langfristigen Konsistenz ergänzen.

## Ergebnis (Ampel)
- **Gesamtstatus:** 🟢 **Solider Kern mit guter Erweiterbarkeit**
- **Nächster Schritt:** Bootstrapping weiter modularisieren, um Skalierung & Testbarkeit zu erhöhen.
