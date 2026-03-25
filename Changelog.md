# 365CMS.DE  [![Generic badge](https://img.shields.io/badge/VERSION-2.7.25-blue.svg)](https://shields.io/)

# 365CMS Changelog

## 📋 Legende

| Symbol | Typ | Bedeutung |
|--------|-----|-----------|
| 🟢 | `feat` | Neues Feature |
| 🔴 | `fix` | Bugfix |
| 🟡 | `refactor` | Code-Umbau ohne Funktionsänderung |
| 🟠 | `perf` | Performance-Verbesserung |
| 🔵 | `docs` | Dokumentation |
| ⬜ | `chore` | Wartungsarbeit / CI/CD |
| 🎨 | `style` | Design- / UI-Änderungen |

---

## 📜 Vollständige Versionshistorie

---

### v2.7.25 — 25. März 2026 · Audit-Batch 107, Dokumentations-Downloader weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.25** | 🔴 fix | Admin/System | **Response-Fehlerpfade laufen jetzt konsistenter über einen kleinen Downloader-Helfer**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` behandelt Download- und Persistenzfehler nicht mehr separat mit ähnlichem Cleanup- und Failure-Abgang. |
| **2.7.25** | 🟠 perf | Admin/System | **Änderungen an Downloader-Fehlern bleiben zentraler wartbar**: der gemeinsame Response-Failure-Helfer reduziert Duplikate bei Cleanup, Logging und Result-Erzeugung. |
| **2.7.25** | 🟡 refactor | Admin/System | **Der Dokumentations-Downloader bleibt näher an kleinen Failure-Bausteinen**: Remote- und Persistenzpfade konzentrieren sich stärker auf ihre eigentliche Aufgabe statt auf wiederholte Failure-Abgänge. |

---

### v2.7.24 — 25. März 2026 · Audit-Batch 106, Dokumentations-Sync-Service weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.24** | 🔴 fix | Admin/System | **Capability-Fehlerpfade laufen jetzt konsistenter über einen kleinen Service-Helfer**: `CMS/admin/modules/system/DocumentationSyncService.php` behandelt unavailable- und invalid-capabilities nicht mehr separat mit identischem Kontextaufbau im Orchestrator. |
| **2.7.24** | 🟠 perf | Admin/System | **Änderungen an Capability-Fehlern bleiben zentraler wartbar**: der gemeinsame Failure-Helfer reduziert Kontext-Duplikate im Sync-Service und erleichtert spätere Anpassungen an Logging oder Fehlermeldungen. |
| **2.7.24** | 🟡 refactor | Admin/System | **Der Dokumentations-Sync-Service bleibt näher an kleinen Failure-Bausteinen**: der Orchestrator konzentriert sich stärker auf die Sync-Auswahl statt auf wiederholte Failure-Kontexte. |

---

### v2.7.23 — 25. März 2026 · Audit-Batch 105, Mail-Settings-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.23** | 🔴 fix | Admin/System | **KPI-Kartenzeilen laufen jetzt konsistenter über einen kleinen View-Renderer**: `CMS/admin/views/system/mail-settings.php` behandelt Logs- und Queue-Metriken nicht mehr als zwei fast identische Kartenreihen direkt im Template. |
| **2.7.23** | 🟠 perf | Admin/System | **Änderungen an Mail-Metriken bleiben zentraler wartbar**: der gemeinsame Kartenreihen-Renderer reduziert Markup-Duplikate in Logs- und Queue-Bereich und erleichtert spätere KPI-Anpassungen. |
| **2.7.23** | 🟡 refactor | Admin/System | **Die Mail-Settings-View bleibt näher an kleinen Render-Bausteinen**: die Tab-Bereiche konzentrieren sich stärker auf ihre Inhalte statt auf wiederholte KPI-Wrapper. |

---

### v2.7.22 — 25. März 2026 · Audit-Batch 104, Dokumentations-Modul weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.22** | 🔴 fix | Admin/System | **Lese-Vorbedingungen laufen jetzt konsistenter über einen kleinen Modul-Guard**: `CMS/admin/modules/system/DocumentationModule.php` behandelt Zugriff, Repository-Layout und DOC-Verfügbarkeit nicht mehr direkt als gestaffelten Inline-Block im Read-Pfad. |
| **2.7.22** | 🟠 perf | Admin/System | **Änderungen am Dokument-Ladepfad bleiben zentraler wartbar**: der gemeinsame View-Guard reduziert Kopierlogik im Modul und erleichtert spätere Anpassungen an Vorbedingungen oder Fehlermeldungen. |
| **2.7.22** | 🟡 refactor | Admin/System | **Das Dokumentations-Modul bleibt näher an kleinen Orchestrator-Bausteinen**: der Read-Pfad konzentriert sich stärker auf Katalog- und Render-Aufbau statt auf Vorbedingungsprüfungen. |

---

### v2.7.21 — 25. März 2026 · Audit-Batch 103, Dokumentations-Ansicht weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.21** | 🔴 fix | Admin/System | **Die ausgewählte Dokumentenkarte läuft jetzt konsistenter über einen kleinen Renderer**: `CMS/admin/views/system/documentation.php` behandelt Excerpt, Quellenhinweis, Leerzustand und CSV-Hinweis nicht mehr direkt als gewachsenen Inhaltsblock im Hauptlayout. |
| **2.7.21** | 🟠 perf | Admin/System | **Änderungen an der Dokumentenansicht bleiben zentraler wartbar**: der gemeinsame Content-Renderer reduziert Markup-Duplikate im rechten Dokumentenpanel und erleichtert spätere Anpassungen an Hinweise oder Zustände. |
| **2.7.21** | 🟡 refactor | Admin/System | **Die Dokumentations-View bleibt näher an kleinen Render-Bausteinen**: das Hauptlayout konzentriert sich stärker auf die Kartenstruktur statt auf den kompletten Inhaltszustand. |

---

### v2.7.20 — 25. März 2026 · Audit-Batch 102, Bestellübersicht weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.20** | 🔴 fix | Admin/Subscriptions | **Das Bestell-Aktionsmenü läuft jetzt konsistenter über einen kleinen Renderer**: `CMS/admin/views/subscriptions/orders.php` behandelt Statuswechsel, Paketzuweisung und Löschaktion nicht mehr direkt als gewachsenen Dropdown-Block in jeder Tabellenzeile. |
| **2.7.20** | 🟠 perf | Admin/Subscriptions | **Änderungen an Bestellaktionen bleiben zentraler wartbar**: der gemeinsame Menü-Renderer reduziert Markup-Duplikate in der Orders-Tabelle und erleichtert spätere Anpassungen an Aktionen oder Attribute. |
| **2.7.20** | 🟡 refactor | Admin/Subscriptions | **Die Orders-View bleibt näher an kleinen View-Bausteinen**: die Tabellenzeile konzentriert sich stärker auf Daten statt auf das komplette Dropdown-Markup. |

---

### v2.7.19 — 25. März 2026 · Audit-Batch 101, Dokumentations-Downloader weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.19** | 🔴 fix | Admin/System | **Persistenz-Fehlerpfade laufen jetzt konsistenter über einen kleinen Downloader-Helfer**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` behandelt Schreib- und Hash-Fehler für ZIP-Artefakte nicht mehr jeweils separat über ähnliche Cleanup- und Failure-Blöcke. |
| **2.7.19** | 🟠 perf | Admin/System | **Änderungen an Archiv-Fehlerpfaden bleiben zentraler wartbar**: der gemeinsame Persistenz-Helfer reduziert Kopierlogik im Downloader und erleichtert spätere Anpassungen an Logging, Cleanup oder Failure-Metadaten. |
| **2.7.19** | 🟡 refactor | Admin/System | **Der Dokumentations-Downloader bleibt näher an kleinen Infrastruktur-Bausteinen**: Persistenz-Fehler sind jetzt sichtbar standardisiert und halten den Archivpfad kompakter. |

---

### v2.7.18 — 25. März 2026 · Audit-Batch 100, Dokumentations-Sync-Service weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.18** | 🔴 fix | Admin/System | **Capability-Verzweigungen laufen jetzt konsistenter über einen kleinen Service-Helfer**: `CMS/admin/modules/system/DocumentationSyncService.php` baut Verfügbarkeits-, Git- und GitHub-ZIP-Auswahl im Sync-Einstieg nicht mehr direkt als gestaffelten Inline-Block auf. |
| **2.7.18** | 🟠 perf | Admin/System | **Änderungen an der Sync-Auswahl bleiben zentraler wartbar**: der gemeinsame Helfer reduziert Kopierlogik im Orchestrator und erleichtert spätere Anpassungen an Capability- oder Moduspfade. |
| **2.7.18** | 🟡 refactor | Admin/System | **Der Dokumentations-Sync-Service bleibt näher an kleinen Infrastruktur-Bausteinen**: Capability-basierte Sync-Auswahl ist jetzt sichtbar standardisiert und hält den Einstieg kompakter. |

---

### v2.7.17 — 25. März 2026 · Audit-Batch 099, Mail-Settings-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.17** | 🔴 fix | Admin/System | **Status-Kartenköpfe laufen jetzt konsistenter über einen kleinen View-Helfer**: `CMS/admin/views/system/mail-settings.php` baut Azure- und Graph-Header mit Status-Badge nicht mehr zweimal leicht variiert direkt im Template zusammen. |
| **2.7.17** | 🟠 perf | Admin/System | **Änderungen an Konfigurationskarten bleiben zentraler wartbar**: der gemeinsame Status-Header reduziert Kopierlogik in der Mail-UI und erleichtert spätere Anpassungen an Titel oder Badge-Zustände. |
| **2.7.17** | 🟡 refactor | Admin/System | **Die Mail-Settings-View bleibt näher an kleinen wiederverwendbaren Render-Bausteinen**: Status-Kartenköpfe sind jetzt sichtbar standardisiert und halten das Template kompakter. |

---

### v2.7.16 — 25. März 2026 · Audit-Batch 098, Dokumentations-Modul weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.16** | 🔴 fix | Admin/System | **Sync-Vorbedingungen laufen jetzt konsistenter über einen kleinen Modul-Helfer**: `CMS/admin/modules/system/DocumentationModule.php` verteilt Zugriffs- und Layout-Fehler im Sync-Einstieg nicht mehr über leicht doppelte Failure-Pfade, sondern bündelt sie vorab in einem Guard. |
| **2.7.16** | 🟠 perf | Admin/System | **Änderungen am Sync-Einstieg bleiben zentraler wartbar**: der gemeinsame Guard reduziert Kopierlogik im Orchestrator und erleichtert spätere Anpassungen an Zugriff- oder Layout-Voraussetzungen. |
| **2.7.16** | 🟡 refactor | Admin/System | **Das Dokumentations-Modul bleibt näher an kleinen Infrastruktur-Bausteinen**: Sync-Gates sind jetzt sichtbar standardisiert und halten den Einstieg kompakter. |

---

### v2.7.15 — 25. März 2026 · Audit-Batch 097, Dokumentations-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.15** | 🔴 fix | Admin/System | **Accordion-Bereiche laufen jetzt konsistenter über einen kleinen View-Helfer**: `CMS/admin/views/system/documentation.php` baut Header-, Collapse- und Dokumentlisten für Bereichs-Accordions nicht mehr mehrfach direkt im Template zusammen. |
| **2.7.15** | 🟠 perf | Admin/System | **Bereichsänderungen bleiben zentraler wartbar**: der gemeinsame Accordion-Renderer reduziert Kopierlogik in der Dokumentations-UI und erleichtert spätere Anpassungen an Struktur, Beschriftung oder Collapse-Markup. |
| **2.7.15** | 🟡 refactor | Admin/System | **Die Dokumentations-View bleibt näher an kleinen wiederverwendbaren Render-Bausteinen**: Bereichs-Accordions sind jetzt sichtbar standardisiert und halten das Template kompakter. |

---

### v2.7.14 — 25. März 2026 · Audit-Batch 096, Orders-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.14** | 🔴 fix | Admin/Subscriptions | **Statuswechsel- und Delete-Formulare laufen jetzt konsistenter über kleine View-Helfer**: `CMS/admin/views/subscriptions/orders.php` baut Dropdown-Mutationen und das versteckte Delete-Formular nicht mehr mehrfach über leicht variierte Hidden-Field-Strukturen auf. |
| **2.7.14** | 🟠 perf | Admin/Subscriptions | **Aktionsänderungen bleiben zentraler wartbar**: die neuen lokalen Renderer reduzieren Kopierlogik in den Order-Aktionen und erleichtern spätere Anpassungen an Hidden-Felder oder Formularattribute. |
| **2.7.14** | 🟡 refactor | Admin/Subscriptions | **Die Orders-View bleibt näher an kleinen wiederverwendbaren Formular-Bausteinen**: Status- und Delete-Aktionen sind jetzt sichtbar standardisiert und halten das Template kompakter. |

---

### v2.7.13 — 25. März 2026 · Audit-Batch 095, Dokumentations-Downloader weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.13** | 🔴 fix | Admin/System | **Response-basierte Failure-Resultate laufen jetzt konsistenter über einen kleinen Downloader-Builder**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` baut Download-Fehler aus HTTP-Status, Content-Type und Byte-Zahlen nicht mehr mehrfach über leicht variierte `failureResult(...)`-Aufrufe zusammen. |
| **2.7.13** | 🟠 perf | Admin/System | **Fehlerpfad-Änderungen bleiben zentraler wartbar**: der gemeinsame Response-Failure-Builder reduziert Kopierlogik in Download-, Persistenz- und Validierungsfehlern und erleichtert spätere Result-Anpassungen. |
| **2.7.13** | 🟡 refactor | Admin/System | **Der Dokumentations-Downloader bleibt näher an kleinen Result-Helfern**: Response-getriebene Fehlerpfade sind jetzt sichtbar standardisiert und halten den Lifecycle kompakter. |

---

### v2.7.12 — 25. März 2026 · Audit-Batch 094, Dokumentations-Sync-Service weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.12** | 🔴 fix | Admin/System | **Konfigurations-Failure-Arrays laufen jetzt konsistenter über einen kleinen Service-Builder**: `CMS/admin/modules/system/DocumentationSyncService.php` baut Validierungsfehler für Repo-, DOC-, Git- und Integritätsprüfung nicht mehr mehrfach über leicht variierte Inline-Arrays auf. |
| **2.7.12** | 🟠 perf | Admin/System | **Validierungsänderungen bleiben zentraler wartbar**: der gemeinsame Failure-Builder reduziert Kopierlogik im Konfigurationspfad und erleichtert spätere Kontext- oder Meldungsanpassungen. |
| **2.7.12** | 🟡 refactor | Admin/System | **Der Doku-Sync-Service bleibt näher an kleinen Result-/Failure-Helfern**: Konfigurationsfehler sind jetzt sichtbar standardisiert und halten die Validierung kompakter. |

---

### v2.7.11 — 25. März 2026 · Audit-Batch 093, Mail-Settings-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.11** | 🔴 fix | Admin/System | **Einfache Kartenköpfe laufen jetzt konsistenter über einen kleinen View-Helfer**: `CMS/admin/views/system/mail-settings.php` baut Transport-, Runtime-, Queue- und Worker-Karten nicht mehr mehrfach über identische Header-Blöcke direkt im Template. |
| **2.7.11** | 🟠 perf | Admin/System | **Header-Änderungen bleiben zentraler wartbar**: der gemeinsame Kartenkopf-Renderer reduziert Kopierlogik in der Mail-UI und erleichtert spätere Titelanpassungen. |
| **2.7.11** | 🟡 refactor | Admin/System | **Die Mail-Settings-View bleibt näher an kleinen wiederverwendbaren Render-Bausteinen**: einfache Kartenüberschriften sind jetzt sichtbar standardisiert und halten das Template kompakter. |

---

### v2.7.10 — 25. März 2026 · Audit-Batch 092, Dokumentations-Modul weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.10** | 🔴 fix | Admin/System | **Repository-Layout-Warnings laufen jetzt konsistenter über einen kleinen Modul-Helfer**: `CMS/admin/modules/system/DocumentationModule.php` schreibt ungültige Repo-/DOC-Layout-Hinweise nicht mehr über mehrfach leicht variierte Warning-Blöcke direkt in `hasValidRepositoryLayout()`. |
| **2.7.10** | 🟠 perf | Admin/System | **Layout-Prüfungen bleiben zentraler wartbar**: der gemeinsame Warning-Helfer reduziert Kopierlogik in der Repository-Validierung und erleichtert spätere Kontext- oder Log-Anpassungen. |
| **2.7.10** | 🟡 refactor | Admin/System | **Das Dokumentations-Modul bleibt näher an kleinen Infrastruktur-Helfern**: Layout-Fehlerpfade sind jetzt sichtbar standardisiert und halten die Orchestrator-Methode kompakter. |

---

### v2.7.09 — 25. März 2026 · Audit-Batch 091, Dokumentations-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.09** | 🔴 fix | Admin/System | **Kartenköpfe laufen jetzt konsistenter über einen kleinen View-Helfer**: `CMS/admin/views/system/documentation.php` baut Schnellstart-, Bereichs- und Dokumentkarten nicht mehr über mehrfach leicht variierte Header-Blöcke zusammen. |
| **2.7.09** | 🟠 perf | Admin/System | **Header-Änderungen bleiben zentraler wartbar**: der gemeinsame Kartenkopf-Renderer reduziert Kopierlogik im Dokumentations-UI und erleichtert spätere Titel-/Untertitel-Anpassungen. |
| **2.7.09** | 🟡 refactor | Admin/System | **Die Dokumentations-View bleibt näher an kleinen wiederverwendbaren Render-Bausteinen**: Kartenüberschriften sind jetzt sichtbar standardisiert und halten das Template kompakter. |

---

### v2.7.08 — 25. März 2026 · Audit-Batch 090, Orders-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.08** | 🔴 fix | Admin/Subscriptions | **Select-Felder im Zuweisungsmodal laufen jetzt konsistenter über einen kleinen View-Helfer**: `CMS/admin/views/subscriptions/orders.php` baut Benutzer-, Paket- und Abrechnungsintervall-Auswahl nicht mehr als drei leicht variierte Inline-Blöcke auf. |
| **2.7.08** | 🟠 perf | Admin/Subscriptions | **Modal-Optionen bleiben zentraler wartbar**: vorbereitete Optionslisten und der gemeinsame Select-Renderer reduzieren Kopierlogik im Zuweisungsdialog und erleichtern spätere Feldanpassungen. |
| **2.7.08** | 🟡 refactor | Admin/Subscriptions | **Die Orders-View bleibt näher an kleinen wiederverwendbaren Render-Bausteinen**: das Assignment-Modal trägt weniger Template-Duplikate und bleibt klarer für weitere Partial- oder Builder-Schritte. |

---

### v2.7.07 — 25. März 2026 · Audit-Batch 089, Dokumentations-Downloader weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.07** | 🔴 fix | Admin/System | **Audit- und Channel-Logs laufen jetzt konsistenter über einen kleinen Downloader-Helfer**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` schreibt Erfolgs- und Fehlerlogs nicht mehr über zwei fast identische Methodenpfade mit doppeltem Logger-/Audit-Aufbau. |
| **2.7.07** | 🟠 perf | Admin/System | **Logging-Änderungen bleiben zentraler wartbar**: der gemeinsame Downloader-Logger reduziert Kopierlogik zwischen `logFailure()` und `logSuccess()` und erleichtert spätere Channel- oder Severity-Anpassungen. |
| **2.7.07** | 🟡 refactor | Admin/System | **Der Dokumentations-Downloader bleibt näher an kleinen Infrastruktur-Helfern**: wiederkehrende Log-Ausleitung ist jetzt sichtbar standardisiert, während Download-Validierung und Persistenzpfade ihren Fachkontext behalten. |

---

### v2.7.06 — 25. März 2026 · Audit-Batch 088, Dokumentations-Sync-Service weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.06** | 🔴 fix | Admin/System | **Audit- und Channel-Logs laufen jetzt konsistenter über einen kleinen Service-Helfer**: `CMS/admin/modules/system/DocumentationSyncService.php` schreibt Erfolgs- und Fehlerlogs nicht mehr über zwei fast identische Methodenpfade mit doppeltem Logger-/Audit-Aufbau. |
| **2.7.06** | 🟠 perf | Admin/System | **Logging-Änderungen bleiben zentraler wartbar**: der gemeinsame Dokumentations-Logger reduziert Kopierlogik zwischen `logFailure()` und `logSuccess()` und erleichtert spätere Channel- oder Severity-Anpassungen. |
| **2.7.06** | 🟡 refactor | Admin/System | **Der Doku-Sync-Service bleibt näher an kleinen Infrastruktur-Helfern**: wiederkehrende Log-Ausleitung ist jetzt sichtbar standardisiert, während Erfolgs- und Fehlerpfade ihren Fachkontext behalten. |

---

### v2.7.05 — 25. März 2026 · Audit-Batch 087, Mail-Settings-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.05** | 🔴 fix | Admin/System | **Secret-Statushinweise laufen jetzt konsistenter über einen kleinen View-Helfer**: `CMS/admin/views/system/mail-settings.php` rendert den „Aktuell gespeichert“-Hinweis und die Reset-Checkbox für Transport-, Azure- und Graph-Secrets nicht mehr dreifach leicht variiert inline. |
| **2.7.05** | 🟠 perf | Admin/System | **Formularfragmente bleiben leichter wartbar**: der neue lokale Renderer reduziert Kopierlogik in den drei Secret-Bereichen und macht spätere Beschriftungs- oder Zustandsänderungen zentraler. |
| **2.7.05** | 🟡 refactor | Admin/System | **Die Mail-Settings-View bleibt näher an kleinen wiederverwendbaren Render-Bausteinen**: wiederkehrende Secret-Hinweise und Löschoptionen sind jetzt sichtbar standardisiert. |

---

### v2.7.04 — 25. März 2026 · Audit-Batch 086, Dokumentations-Modul weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.04** | 🔴 fix | Admin/System | **Throwable-Warnings laufen jetzt konsistenter über einen kleinen Modul-Helfer**: `CMS/admin/modules/system/DocumentationModule.php` protokolliert Lade- und Sync-Ausnahmen nicht mehr mehrfach über leicht variierte Inline-Logger-Blöcke, sondern nutzt einen gemeinsamen Warning-Helper. |
| **2.7.04** | 🟠 perf | Admin/System | **Default-Payloads für ausgewählte Dokumente werden zentral vorbereitet**: der Orchestrator baut den leeren Read-Zustand nicht mehr ad hoc in `buildSelectedDocumentPayload()`, sondern nutzt einen kleinen Default-Payload-Helfer für stabilere Read-Pfade. |
| **2.7.04** | 🟡 refactor | Admin/System | **Das Dokumentations-Modul bleibt noch näher an seinen Verträgen**: kleine Hilfsmethoden für Throwable-Logging und Initial-Payload reduzieren weitere Orchestrator-Duplikate und erleichtern die nächsten Service- oder Result-Schritte. |

---

### v2.7.03 — 25. März 2026 · Audit-Batch 085, Dokumentations-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.03** | 🔴 fix | Admin/System | **Alert-Blöcke laufen jetzt konsistenter über einen kleinen View-Helfer**: `CMS/admin/views/system/documentation.php` baut Fehler-, Sync- und Hinweisboxen nicht mehr mehrfach mit leicht variierten Inline-Blöcken zusammen, sondern nutzt einen gemeinsamen Alert-Renderer. |
| **2.7.03** | 🟠 perf | Admin/System | **Bereichs-Einleitungen und Quellhinweise werden aus vorbereiteten Renderern/Texten aufgebaut**: Accordion-Intro und Source-Hinweis müssen nicht mehr mehrfach direkt im Template zusammengesetzt werden, wodurch der Informationspfad kompakter bleibt. |
| **2.7.03** | 🟡 refactor | Admin/System | **Die Dokumentations-View bleibt noch näher am eigentlichen Rendern**: kleine Renderer für Alert- und Introblöcke reduzieren weitere Template-Duplikate und erleichtern die nächsten Partial- oder Builder-Schritte. |

---

### v2.7.02 — 25. März 2026 · Audit-Batch 084, Orders-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.02** | 🔴 fix | Admin/Subscriptions | **Status-Badges und Sekundärzeilen laufen jetzt konsistenter über kleine Render-Helfer**: `CMS/admin/views/subscriptions/orders.php` baut Kunden-, Assignment- und Statusanzeige nicht mehr mehrfach mit leicht variierten Inline-Blöcken zusammen, sondern nutzt gemeinsame Renderer für Badge- und Primär-/Sekundärtexte. |
| **2.7.02** | 🟠 perf | Admin/Subscriptions | **Billing-Cycle-Optionen werden aus vorbereiteten Listen gerendert**: das Zuweisungs-Modal hält Monats-, Jahres- und Lifetime-Auswahl nicht mehr als feste Einzeloptionen im Markup verstreut vor, wodurch der Formularpfad kompakter und konsistenter bleibt. |
| **2.7.02** | 🟡 refactor | Admin/Subscriptions | **Die Orders-View bleibt noch näher am eigentlichen Rendern**: kleine Renderer für Badge- und Textgruppen reduzieren weitere Template-Duplikate und erleichtern nächste Partial- oder Builder-Schritte in Tabellen- und Modalbereichen. |

---

### v2.7.01 — 25. März 2026 · Audit-Batch 083, Dokumentations-Downloader weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.01** | 🔴 fix | Admin/System | **Failure-Resultate laufen jetzt konsistenter über einen kleinen Downloader-Helfer**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` baut Download-, Persistenz- und Hash-Fehler nicht mehr an mehreren Stellen separat als Result-Objekte auf, sondern nutzt einen fokussierten Failure-Builder. |
| **2.7.01** | 🟠 perf | Admin/System | **Remote-Fehlerpfade tragen weniger verteilte Result-Erzeugung**: Validierungs-, HTTP- und Schreibfehler laufen über denselben Result-Helfer, wodurch der Download-Lifecycle weniger wiederholte Objektkonstruktion im Fehlerpfad mit sich herumträgt. |
| **2.7.01** | 🟡 refactor | Admin/System | **Der Downloader bleibt näher an seinem Result-Vertrag**: kleine Failure-Helper reduzieren losen Result-Mix und erleichtern weitere Payload- oder Lifecycle-Aufspaltungen im Downloadpfad. |

---

### v2.7.00 — 25. März 2026 · Audit-Batch 082, Dokumentations-Sync-Service weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.00** | 🔴 fix | Admin/System | **Failure-Fallbacks laufen jetzt konsistenter über einen kleinen Service-Helfer**: `CMS/admin/modules/system/DocumentationSyncService.php` baut Konfigurations- und Fail-Responses nicht mehr mehrfach direkt über lose Ergebnis-Arrays auf, sondern nutzt einen fokussierten Failure-Builder für `DocumentationSyncServiceResult`. |
| **2.7.00** | 🟠 perf | Admin/System | **Finalize- und Konfigpfade nutzen denselben Result-Wrapper**: der Orchestrator konvertiert Result-Arrays zentral in einen Service-Result-Vertrag, wodurch Erfolg- und Fehlerpfade weniger doppelte Array-zu-Objekt-Übergänge mit sich herumtragen. |
| **2.7.00** | 🟡 refactor | Admin/System | **Der Doku-Sync-Orchestrator bleibt näher an seinen Verträgen**: kleine Helper für Result-Erzeugung reduzieren losen Array-Mix und erleichtern weitere Objekt- oder Result-Aufspaltungen im Sync-Service. |

---

### v2.6.99 — 25. März 2026 · Audit-Batch 081, Mail-Settings-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.99** | 🔴 fix | Admin/System | **Status-Badges laufen jetzt konsistenter über einen kleinen View-Helfer**: `CMS/admin/views/system/mail-settings.php` rendert Transport-, OAuth2-, Log- und Queue-Status nicht mehr über mehrfach ausgeschriebene Badge-Fragmente, sondern über einen gemeinsamen Badge-Renderer. |
| **2.6.99** | 🟠 perf | Admin/System | **Empty States und Hinweis-Karten werden wiederverwendet aufgebaut**: leere Tabellenzeilen sowie die seitlichen Azure-/Graph-Hinweisboxen laufen über kleine Render-Helfer, wodurch Logs-, Queue- und Sidebar-Markup weniger doppelte UI-Struktur tragen. |
| **2.6.99** | 🟡 refactor | Admin/System | **Die Mail-Settings-View bleibt näher am eigentlichen Rendern**: Badge-, Empty-State- und Info-Card-Helfer reduzieren Template-Duplikate und erleichtern weitere Partial- oder Builder-Schritte. |

---

### v2.6.98 — 25. März 2026 · Audit-Batch 080, Dokumentations-Modul weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.98** | 🔴 fix | Admin/System | **Sync-Resultate werden im Doku-Modul jetzt konsistenter gebaut**: `CMS/admin/modules/system/DocumentationModule.php` bündelt Sanitizing und Fehlererzeugung für Sync-Antworten über fokussierte Helfer, statt dieselbe Result-Logik mehrfach direkt im Modul zu verteilen. |
| **2.6.98** | 🟠 perf | Admin/System | **Ausgewählte Dokumente laufen über klarere Payload-Helfer**: Pfadauflösung und Dokument-Rendering sind in kleine Methoden ausgelagert, wodurch der Read-Pfad für ausgewählte Dateien weniger Inline-Verzweigungen mit sich herumträgt. |
| **2.6.98** | 🟡 refactor | Admin/System | **Der Doku-Orchestrator bleibt näher an seinen Verträgen**: Hilfsmethoden für Payload- und Failure-Aufbau reduzieren losen Lifecycle-Mix und erleichtern weitere Service-Aufspaltungen im Modul. |

---

### v2.6.97 — 25. März 2026 · Audit-Batch 079, Dokumentations-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.97** | 🔴 fix | Admin/System | **Dokumentations-KPI-Karten laufen jetzt über einen kleinen Render-Helfer**: `CMS/admin/views/system/documentation.php` baut Dokument-, Bereichs-, Quellen- und Sync-Karten aus einer vorbereiteten Kartenliste auf, statt dieselben Card-Blöcke mehrfach direkt im Markup auszuschreiben. |
| **2.6.97** | 🟠 perf | Admin/System | **Schnellstart- und Bereichslinks nutzen denselben Dokument-Renderer**: wiederkehrende Listen-Items werden über einen lokalen Helfer gerendert, wodurch Featured-Docs und Bereichslisten weniger doppelte UI-Struktur im Renderpfad tragen. |
| **2.6.97** | 🟡 refactor | Admin/System | **Die Dokumentations-View bleibt näher am eigentlichen Rendern**: kleine Render-Helfer für Metric-Cards und Dokument-Links reduzieren Template-Duplikate und erleichtern weitere Partial- oder Builder-Schritte. |

---

### v2.6.96 — 25. März 2026 · Audit-Batch 078, Orders-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.96** | 🔴 fix | Admin/Subscriptions | **KPI-Karten und Leerzustände laufen jetzt über kleine View-Helfer**: `CMS/admin/views/subscriptions/orders.php` rendert Kennzahlen und leere Tabellenzeilen aus vorbereiteten Datenlisten statt dieselben Card- und Empty-State-Blöcke mehrfach direkt im Markup auszuschreiben. |
| **2.6.96** | 🟠 perf | Admin/Subscriptions | **Statuswechsel und Assignment-Anzeige nutzen vorbereitete Zwischenwerte**: verfügbare Übergänge, JSON-Payloads und Laufzeittexte werden lokal gebündelt, wodurch Dropdown- und Tabellenpfade weniger wiederholte UI-Logik im Renderpfad tragen. |
| **2.6.96** | 🟡 refactor | Admin/Subscriptions | **Die Orders-View bleibt noch näher am eigentlichen Rendern**: kleine Template-Helfer für Metrics, Empty States und Assignment-Felder reduzieren Template-Duplikate und erleichtern weitere Partial- oder Builder-Schritte. |

---

### v2.6.95 — 25. März 2026 · Audit-Batch 077, Dokumentations-Downloader weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.95** | 🔴 fix | Admin/System | **Download-Resultate laufen jetzt über benannte Erfolgs-/Fehlerfabriken**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` baut Fehl- und Erfolgsergebnisse über `DocumentationDownloadResult::failure()` und `::success()` auf, statt wiederholt dieselbe Parameterkette direkt in den Lifecycle zu schreiben. |
| **2.6.95** | 🟠 perf | Admin/System | **Validierte ZIP-Antworten bleiben als kleines Payload-DTO zusammen**: der Downloader reicht Body und Content-Type nach der Prüfung als `DocumentationDownloadPayload` weiter, wodurch Persistenz- und Hash-Pfade weniger lose Response-Fragmente mit sich herumtragen. |
| **2.6.95** | 🟡 refactor | Admin/System | **Der Downloader-Lifecycle spricht schärfere Zwischenverträge**: Result-Factory und Payload-DTO halten Validierung, Persistenz und Fehlerpfade expliziter getrennt und erleichtern weitere Zerlegung im Remote-/Filesystem-Pfad. |

---

### v2.6.94 — 25. März 2026 · Audit-Batch 076, Dokumentations-Sync-Service weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.94** | 🔴 fix | Admin/System | **Capability-Abfragen laufen jetzt über echte Objektmethoden**: `CMS/admin/modules/system/DocumentationSyncService.php` nutzt `canSync()`, `hasGit()` und `hasGithubZip()` direkt am Capability-Vertrag, statt diese Informationen sofort wieder über lose Array-Schlüssel auszulesen. |
| **2.6.94** | 🟠 perf | Admin/System | **Logging und Finalisierung übernehmen vorbereitete Capability-Kontexte**: der Orchestrator reicht Capability-Daten über `toLogContext()` weiter, wodurch Erfolgs-, Fehler- und Unavailable-Pfade weniger eigene Array-Normalisierung mit sich herumtragen. |
| **2.6.94** | 🟡 refactor | Admin/System | **Environment und Sync-Service teilen einen schärferen Objektvertrag**: `DocumentationSyncCapabilities` bietet jetzt Getter plus kleinen Log-Kontext-Helfer, sodass Capability-Normalisierung und Sync-Dispatch klarer voneinander getrennt bleiben. |

---

### v2.6.93 — 25. März 2026 · Audit-Batch 075, Mail-Settings-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.93** | 🔴 fix | Admin/System | **Mail-KPI-Karten laufen jetzt über eine kleine View-Schleife**: `CMS/admin/views/system/mail-settings.php` rendert Log- und Queue-Metriken aus vorbereiteten Kartenlisten statt dieselben Card-Blöcke mehrfach direkt im Markup auszuschreiben. |
| **2.6.93** | 🟠 perf | Admin/System | **Readonly-Felder und Worker-Status werden wiederverwendet aufgebaut**: kleine Helfer bündeln Readonly-Eingaben und den Last-Run-Text, wodurch die View weniger wiederholte UI-Struktur im Renderpfad trägt. |
| **2.6.93** | 🟡 refactor | Admin/System | **Die Mail-Settings-View bleibt näher am eigentlichen Rendern**: vorbereitete Karten-, Feld- und Statusdaten reduzieren Template-Duplikate und erleichtern weitere Partial- oder Builder-Schritte. |

---

### v2.6.92 — 25. März 2026 · Audit-Batch 074, Mail-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.92** | 🔴 fix | Admin/System | **Mail-Aktionen laufen jetzt über eine zentrale Action-Map**: `CMS/admin/mail-settings.php` bündelt den POST-Dispatch in einem kleinen Handler-Register statt die Modulmethoden direkt im Hauptfluss per langem `match` zu verdrahten. |
| **2.6.92** | 🟠 perf | Admin/System | **Session-Alerts werden über einen kleinen Pull-Helfer übernommen**: der Wrapper räumt die Session konsistenter auf und hält den Entry-Fluss kompakter. |
| **2.6.92** | 🟡 refactor | Admin/System | **Der Mail-Entry bleibt näher am eigentlichen Request-Flow**: Action-Map und Alert-Pull-Helfer reduzieren verstreute Dispatch- und Session-Logik und erleichtern weitere Wrapper-Anpassungen. |

---

### v2.6.91 — 25. März 2026 · Audit-Batch 073, Dokumentations-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.91** | 🔴 fix | Admin/System | **Dokument- und Bereichslinks laufen konsistenter über kleine View-Helfer**: `CMS/admin/views/system/documentation.php` bereitet Admin-URLs, GitHub-Links und Bereichs-Slugs jetzt zentral auf, statt diese Werte mehrfach inline im Listen- und Accordion-Markup zusammenzubauen. |
| **2.6.91** | 🟠 perf | Admin/System | **Titel-, Pfad-, Extension- und Count-Ableitungen werden wiederverwendet**: Dokument- und Bereichsmetadaten laufen über lokale Helfer, wodurch die View weniger wiederholte UI-Logik im Renderpfad trägt. |
| **2.6.91** | 🟡 refactor | Admin/System | **Die Dokumentations-View bleibt näher am eigentlichen Rendern**: kleine Helfer sammeln Listen- und Bereichsmetadaten zentral ein und reduzieren Template-Duplikate für weitere Partial- oder Builder-Schritte. |

---

### v2.6.90 — 25. März 2026 · Audit-Batch 072, Orders-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.90** | 🔴 fix | Admin/Subscriptions | **Bestell- und Kundenlabels laufen konsistenter über kleine View-Helfer**: `CMS/admin/views/subscriptions/orders.php` bereitet Bestellnummer, Kundenname und Kundenmail jetzt zentral auf, statt diese Werte mehrfach direkt im Tabellen-Markup zusammenzusetzen. |
| **2.6.90** | 🟠 perf | Admin/Subscriptions | **Filter- und Select-Optionen nutzen wiederverwendete Template-Helfer**: Filterbutton-Klassen sowie Benutzer- und Paketlabels werden zentral aufgebaut, wodurch die View weniger wiederholte UI-Logik im Renderpfad trägt. |
| **2.6.90** | 🟡 refactor | Admin/Subscriptions | **Die Orders-View bleibt näher am eigentlichen Rendern**: kleine lokale Helfer sammeln Anzeige- und Form-Labels ein und reduzieren Template-Duplikate für weitere Partial- oder Modal-Schritte. |

---

### v2.6.89 — 25. März 2026 · Audit-Batch 071, Dokumentations-Downloader weiter zerlegt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.89** | 🔴 fix | Admin/System | **Download-Fehlpfade laufen jetzt über einen gemeinsamen Reject-/Failure-Flow**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` bündelt Host-, Ziel-, Verzeichnis- und HTTP-Fehler über kleine Helfer, statt alle Rückgaben direkt im großen `downloadFile()`-Block zu mischen. |
| **2.6.89** | 🟠 perf | Admin/System | **Payload-Prüfung und Persistenz sind getrennt lesbar**: Response-Validierung, Datei-Persistenz und Cleanup verteilen sich jetzt auf fokussierte Helfer, wodurch der Downloader-Lifecycle klarer und gezielter weiter optimierbar bleibt. |
| **2.6.89** | 🟡 refactor | Admin/System | **Der Download-Result-Vertrag ist expliziter geworden**: `DocumentationDownloadResult` kapselt Metadaten jetzt über Methoden wie `isSuccess()`, `bytes()` und `sha256()`, und `DocumentationGithubZipSync.php` liest diese Werte nicht mehr als lose Public-Properties aus. |

---

### v2.6.88 — 25. März 2026 · Audit-Batch 070, Dokumentations-Sync-Service-Verträge geschärft

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.88** | 🔴 fix | Admin/System | **Der Doku-Sync-Orchestrator liefert jetzt einen kleinen Service-Result-Vertrag**: `CMS/admin/modules/system/DocumentationSyncService.php` kapselt Sync-Ergebnisse über `DocumentationSyncServiceResult`, statt lose Erfolgs-/Fehler-Arrays über mehrere Ebenen durchzureichen. |
| **2.6.88** | 🟠 perf | Admin/System | **Normalisierte Sync-Capabilities bleiben als Objekt erhalten**: der Service arbeitet intern mit `DocumentationSyncCapabilities` weiter, statt das Environment-Ergebnis sofort in lose Arrays zu zerlegen und wieder aufzubauen. |
| **2.6.88** | 🟡 refactor | Admin/System | **Dokumentationsmodul und Sync-Service teilen schärfere Grenzen**: `CMS/admin/modules/system/DocumentationModule.php` konsumiert die Service-Objekte gezielt über `->toArray()`, wodurch Orchestrator und Modul klarer voneinander getrennt bleiben. |

---

### v2.6.87 — 25. März 2026 · Audit-Batch 069, Mail-Settings-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.87** | 🔴 fix | Admin/System | **Status- und Secret-Anzeigen laufen konsistenter über kleine View-Helfer**: `CMS/admin/views/system/mail-settings.php` nutzt vorbereitete Badge-/Label-Helfer für Konfigurationsstände statt verstreuter Inline-Entscheidungen. |
| **2.6.87** | 🟠 perf | Admin/System | **Tabs, Selects und Checkboxen werden über wiederverwendete Template-Helfer bewertet**: wiederkehrende `active`-/`selected`-/`checked`-Logik muss nicht mehr mehrfach direkt im Markup aufgelöst werden. |
| **2.6.87** | 🟡 refactor | Admin/System | **Die Mail-Settings-View bleibt näher am eigentlichen Rendern**: kleine lokale Helfer sammeln UI-Zustände zentral ein und reduzieren Template-Duplikate für die nächsten UI-Schritte. |

---

### v2.6.86 — 25. März 2026 · Audit-Batch 068, Dokumentationsmodul-Verträge geschärft

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.86** | 🔴 fix | Admin/System | **Sync-Antworten des Doku-Moduls laufen jetzt über einen kleinen Result-Vertrag**: `CMS/admin/modules/system/DocumentationModule.php` liefert `syncDocsFromRepository()` als `DocumentationSyncActionResult`, und `CMS/admin/documentation.php` leitet Flash-Typ und Meldung konsistent daraus ab. |
| **2.6.86** | 🟠 perf | Admin/System | **Die Auswahl eines aktiven Dokuments wird über einen fokussierten Payload-Builder zusammengesetzt**: Render-HTML, Rohinhalt und CSV-Status entstehen jetzt in `buildSelectedDocumentPayload()` statt als größerer Inline-Block in `getData()`. |
| **2.6.86** | 🟡 refactor | Admin/System | **Das Dokumentationsmodul spricht explizitere Read-/Write-Grenzen**: `DocumentationViewData` kapselt den View-Vertrag, während Sanitizing und Fehlerrückgaben für den Sync über kleine Helfer vereinheitlicht werden. |

---

### v2.6.85 — 25. März 2026 · Audit-Batch 067, Dokumentations-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.85** | 🔴 fix | Admin/System | **Alert-Kontext wird auch in der Doku-View defensiver und einheitlicher gespiegelt**: `CMS/admin/views/system/documentation.php` übergibt Flash-Daten jetzt konsistent als Array an den gemeinsamen Alert-Partial statt über einen losen Optional-Block. |
| **2.6.85** | 🟠 perf | Admin/System | **Aktive Dokument- und Sync-Zustände laufen über kleine Template-Helfer**: aktive Links, Bereichsstatus, Sync-Alert-Klasse und Default-Pfadlabel müssen nicht mehr mehrfach inline ausgewertet werden. |
| **2.6.85** | 🟡 refactor | Admin/System | **Die Dokumentations-View trägt weniger verstreute Zustandslogik**: vorbereitete Helfer halten das Template näher am eigentlichen Rendern und leichter lesbar für weitere UI-Schritte. |

---

### v2.6.84 — 25. März 2026 · Audit-Batch 066, Subscription-Settings-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.84** | 🔴 fix | Admin/Subscriptions | **Alert-Kontext wird defensiver und einheitlicher in die View gespiegelt**: `CMS/admin/views/subscriptions/settings.php` übernimmt Flash-Daten jetzt konsistent als Array-Kontext für den gemeinsamen Alert-Partial statt über einen losen Optional-Block. |
| **2.6.84** | 🟠 perf | Admin/Subscriptions | **Checkbox- und Select-Zustände laufen über kleine Template-Helfer**: wiederkehrende `checked`-/`selected`-Bedingungen und vorbereitete Notice-Werte müssen nicht mehr mehrfach inline im Markup ausgewertet werden. |
| **2.6.84** | 🟡 refactor | Admin/Subscriptions | **Die Settings-View trägt weniger Inline-Entscheidungslogik**: vorbereitete Default-/Notice-Werte halten das Template dümmer und näher am eigentlichen Rendern. |

---

### v2.6.83 — 25. März 2026 · Audit-Batch 065, GitHub-ZIP-Sync weiter zerlegt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.83** | 🔴 fix | Admin/System | **ZIP-Sync-Arbeitsverzeichnisse laufen jetzt über einen kleinen Workspace-Vertrag**: `CMS/admin/modules/system/DocumentationGithubZipSync.php` bündelt ZIP-, Extract-, Staging- und Backup-Pfade über `DocumentationGithubZipWorkspace`, statt diese quer durch den großen Sync-Block mitzuschleppen. |
| **2.6.83** | 🟠 perf | Admin/System | **Archiv-, Staging- und Aktivierungsschritte sind separat gekapselt**: Extraktion, Snapshot-Staging, Aktivierung und Cleanup liegen jetzt in fokussierten Helfern, wodurch der ZIP-Sync-Lebenszyklus klarer lesbar und gezielter weiter optimierbar wird. |
| **2.6.83** | 🟡 refactor | Admin/System | **Der GitHub-ZIP-Sync trägt weniger Lifecycle-Mix im Top-Level**: `sync()` konzentriert sich jetzt stärker auf den Ablauf, während Detailarbeit in kleine Methoden ausgelagert wurde. |

---

### v2.6.82 — 25. März 2026 · Audit-Batch 064, Mail-Settings-Verträge geschärft

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.82** | 🔴 fix | Admin/System | **Mail-Admin-Mutationen sprechen jetzt einen einheitlicheren Result-Vertrag**: `CMS/admin/modules/system/MailSettingsModule.php` liefert Save-, Test-, Queue- und Cache-Aktionen über `MailSettingsActionResult`, während `CMS/admin/mail-settings.php` Flash-Meldungen zentral daraus ableitet. |
| **2.6.82** | 🟠 perf | Admin/System | **Read-Pfade sind sauberer in kleine Datenbausteine zerlegt**: Transport-, Azure-, Graph- und Queue-Stats werden im Modul über fokussierte Builder zusammengesetzt, statt als großer Inline-Sammelblock in `getData()` zu wohnen. |
| **2.6.82** | 🟡 refactor | Admin/System | **Mail-Settings nutzen jetzt ein kleines View-DTO statt losem Array-Mix**: `MailSettingsViewData` hält den View-Vertrag explizit, sodass Wrapper und Modul weniger implizite Schlüsselannahmen teilen müssen. |

---

### v2.6.81 — 25. März 2026 · Audit-Batch 063, Doku-Sync-Environment weiter entkoppelt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.81** | 🔴 fix | Admin/System | **Doku-Sync-Kommandos sprechen jetzt einen schärferen Shell-Vertrag**: `DocumentationSyncEnvironment.php` liefert Shell-Ausführungen über `DocumentationShellCommandResult`, und `DocumentationGitSync.php` arbeitet damit statt mit losen `output`-/`exitCode`-Arrays. |
| **2.6.81** | 🟠 perf | Admin/System | **Capability-Auflösung ist klarer von den Konsumenten getrennt**: `DocumentationSyncCapabilities` bündelt Git-/ZIP-Modi in einem kleinen Objekt, das `DocumentationSyncService.php` gezielt normalisiert weiterverarbeitet. |
| **2.6.81** | 🟡 refactor | Admin/System | **Shell-/Capability-Layer wurden weiter entkoppelt**: `DocumentationSyncEnvironment.php` kapselt seine Read-Modelle jetzt expliziter, wodurch Doku-Sync-Aufrufer weniger implizite Array-Details kennen müssen. |

---

### v2.6.80 — 25. März 2026 · Audit-Batch 062, Subscription-Settings weiter zerlegt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.80** | 🔴 fix | Admin/Subscriptions | **Subscription-Settings sprechen jetzt einen einheitlicheren Result-Vertrag**: `CMS/admin/modules/subscriptions/SubscriptionSettingsModule.php` liefert Save-Antworten über ein kleines `SubscriptionSettingsActionResult`, während `subscription-settings.php` und `packages.php` Flash-Meldungen daraus konsistent ableiten. |
| **2.6.80** | 🟠 perf | Admin/Subscriptions | **General- und Package-Settings werden über fokussierte Payload-Helfer gebaut**: wiederkehrende ID- und Range-Normalisierung ist aus den großen Save-Methoden herausgezogen und damit leichter weiter zu optimieren. |
| **2.6.80** | 🟡 refactor | Admin/Subscriptions | **Read-Pfade nutzen jetzt ein kleines View-DTO statt losen Array-Mix**: `SubscriptionSettingsViewData` bündelt Settings-, Plan- und Seitenlisten, wodurch Modul- und Entry-Grenzen klarer bleiben. |

---

### v2.6.79 — 25. März 2026 · Audit-Batch 061, Orders-Modul weiter entknotet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.79** | 🔴 fix | Admin/Subscriptions | **Orders-Mutationen sprechen jetzt einen schärferen Fehlervertrag**: `CMS/admin/modules/subscriptions/OrdersModule.php` liefert Zuweisung, Statuswechsel und Löschung über ein kleines `OrdersActionResult`, während `CMS/admin/orders.php` Flash-Meldungen zentral aus genau diesem Result ableitet. |
| **2.6.79** | 🟠 perf | Admin/Subscriptions | **Listen- und Statistik-Ladevorgänge wurden aus dem großen Sammelblock herausgezogen**: fokussierte Fetch-/Stats-Helfer für Orders, Assignments, Plans und Users machen den Datenpfad lesbarer und leichter weiter zu optimieren. |
| **2.6.79** | 🟡 refactor | Admin/Subscriptions | **Dashboard-Daten kommen jetzt über ein kleines DTO statt über losen Array-Mix zurück**: `OrdersDashboardData` bündelt den Read-Pfad, während Modulzugriff und Fehlerbehandlung enger an einen konsistenten Modulkontrakt gezogen wurden. |

---

### v2.6.78 — 25. März 2026 · Audit-Batch 060, Orders-View weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.78** | 🔴 fix | Admin/Subscriptions | **Orders-View nutzt jetzt den gemeinsamen Flash-Alert-Standard**: `CMS/admin/views/subscriptions/orders.php` bindet die vorhandene Alert-Partial ein und übernimmt Session-/UI-Meldungen nicht länger über einen eigenen Inline-Alert-Block. |
| **2.6.78** | 🟠 perf | Admin/Subscriptions | **Wiederkehrende Status-, Datums- und Betragsformatierung liegt jetzt lokal gebündelt vor**: kleine Helper für Status-Metadaten und Ausgabeformate reduzieren Template-Duplikate und halten die Orders-Tabelle lesbarer. |
| **2.6.78** | 🟡 refactor | Admin/Subscriptions | **Inline-Handler wurden in datengetriebene Aktionen gezogen**: Assign-/Delete-Schaltflächen arbeiten nun über `data-*`-Attribute und ein zentrales Script statt über verteilte `onclick`-Fragmente pro Button. |

---

### v2.6.75 — 25. März 2026 · Audit-Batch 057, Doku-Sync-Environment enger gezogen

### v2.6.77 — 25. März 2026 · Audit-Batch 059, Doku-Downloader weiter entkoppelt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.77** | 🔴 fix | Admin/System | **Downloader prüft Response-Header und Dateiintegrität jetzt konsequenter**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` validiert `content-length` und Content-Type enger, schreibt nur konsistente ZIP-Antworten weg und erzeugt direkt eine SHA-256-Checksumme für den weiteren Sync-Pfad. |
| **2.6.77** | 🔴 security | Admin/System | **GitHub-ZIP-Sync vertraut nicht mehr blind auf das gespeicherte Artefakt**: `CMS/admin/modules/system/DocumentationGithubZipSync.php` verifiziert heruntergeladene ZIP-Dateien zusätzlich gegen Größe und Hash des Downloader-Ergebnisses und blockiert inkonsistente Download-Artefakte vor dem Entpacken. |
| **2.6.77** | 🟡 refactor | Admin/System | **Download-Ergebnis aus Array-Mix in kleines DTO gezogen**: `DocumentationDownloadResult` bündelt Status, Content-Type, Bytes und Hash, sodass Downloader- und ZIP-Sync-Pfade weniger lose Rückgabe-Arrays und implizite Nachprüfungen mit sich herumschleppen. |

---

### v2.6.76 — 25. März 2026 · Audit-Batch 058, Mail-Settings-Wrapper & View vereinheitlicht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.76** | 🔴 fix | Admin/System | **Mail-Settings-Entry dispatcht POST-Aktionen jetzt über kleine Standard-Helper**: `CMS/admin/mail-settings.php` nutzt eine explizite Tab-/Action-Allowlist, vereinheitlicht Flash + Redirect und übernimmt Session-Alerts nur noch defensiv als Array. |
| **2.6.76** | 🔴 security | Admin/System | **Mail-Settings-View folgt dem gemeinsamen Flash- und Statusmuster**: `CMS/admin/views/system/mail-settings.php` rendert Meldungen über den zentralen Flash-Partial, hält Tab-Definitionen lokal gebündelt und kapselt Queue-Status-Badges über einen kleinen Helper statt losem Inline-Mix. |
| **2.6.76** | 🟡 refactor | Admin/System | **Mail-UI-Kontrakt bleibt enger am Admin-Standard**: API-/Tab-Konstanten und wiederkehrende View-Helfer liegen jetzt zentral im Template-Kontext, wodurch weniger implizite Sonderlogik zwischen Wrapper und View verteilt bleibt. |

---

### v2.6.75 — 25. März 2026 · Audit-Batch 057, Doku-Sync-Environment enger gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.75** | 🔴 security | Admin/System | **Doku-Sync-Environment akzeptiert nur noch erwartete Git-Kommandos**: `CMS/admin/modules/system/DocumentationSyncEnvironment.php` blockiert jetzt Shell-Aufrufe außerhalb definierter Git-Subcommands sowie auffällige Kommando-Payloads mit Redirect-/Pipe-Spielereien deutlich früher. |
| **2.6.75** | 🔴 fix | Admin/System | **Repository-Root wird vor Capability- und Command-Pfaden früher validiert**: ungültige oder symlinkartige Repo-Roots laufen nicht mehr halb in Capability- oder Git-Pfade hinein, sondern werden kontrolliert als nicht nutzbare Umgebung behandelt. |
| **2.6.75** | 🟡 refactor | Admin/System | **Command-Sanitizing und Root-Normalisierung zentralisiert**: die Environment-Schicht bündelt Command-Längenlimit, Root-Normalisierung und Allowlist-Prüfung nun in kleinen Helpern statt losem Blindvertrauen auf Übergabestrings. |

---

### v2.6.74 — 25. März 2026 · Audit-Batch 056, Subscription-Settings-Wrapper vereinheitlicht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.74** | 🔴 fix | Admin/Subscriptions | **Subscription-Settings-Entry akzeptiert nur noch die erwartete Mutation**: `CMS/admin/subscription-settings.php` nutzt jetzt eine explizite Action-Allowlist und behandelt CSRF-/Aktionsfehler konsistent per Flash + Redirect statt mit losem POST-Sonderpfad. |
| **2.6.74** | 🔴 security | Admin/Subscriptions | **Flash-State wird defensiver übernommen**: Session-Alerts werden nur noch als Array akzeptiert und nicht mehr blind aus der Session in den View-Kontext gespiegelt. |
| **2.6.74** | 🟡 refactor | Admin/Subscriptions | **Settings-View folgt dem gemeinsamen Alert-Partial**: `CMS/admin/views/subscriptions/settings.php` nutzt jetzt den bestehenden Flash-Alert-Baustein und sendet die Mutation explizit als `save_settings`, statt Wrapper-Logik implizit zu erraten. |

---

### v2.6.73 — 25. März 2026 · Audit-Batch 055, Orders-Admin restriktiver gemacht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.73** | 🔴 fix | Admin/Subscriptions | **Orders-Entry akzeptiert nur noch bekannte Mutationen**: `CMS/admin/orders.php` dispatcht POST-Aktionen jetzt über eine explizite Allowlist, normalisiert Statusfilter serverseitig und behandelt CSRF-/Aktionsfehler konsistent per Flash + Redirect statt mit losem Wrapper-Verhalten. |
| **2.6.73** | 🔴 security | Admin/Subscriptions | **Bestell-Mutationen prüfen Status, Existenz und Kontext enger**: `CMS/admin/modules/subscriptions/OrdersModule.php` validiert Billing-/Statuswerte zentral, bricht Statuswechsel und Löschungen bei fehlenden Bestellungen sauber ab und schreibt nur noch maskierte Bestell-/Mailkontexte ins Audit-Log. |
| **2.6.73** | 🟡 refactor | Admin/Subscriptions | **Orders-Modul nutzt gemeinsame Limits und Helper statt losem Array-Mix**: Status-/Billing-Normalisierung, Snapshot-Reads und kompaktere Listenlimits reduzieren Dupplikate und halten die Bestellverwaltung besser auf Linie. |

---

### v2.6.72 — 25. März 2026 · Audit-Batch 054, GitHub-ZIP-Sync nachgeschärft

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.72** | 🔴 security | Admin/System | **GitHub-ZIP-Quellen bleiben jetzt enger auf saubere Archive beschränkt**: `CMS/admin/modules/system/DocumentationGithubZipSync.php` akzeptiert keine ZIP-URLs mehr mit Query-, Fragment- oder Credential-Anteilen und prüft die geladene Archivdatei lokal zusätzlich auf sichere Dateiform und Größe. |
| **2.6.72** | 🔴 fix | Admin/System | **Rollback-Reste werden kontrollierter aufgeräumt**: nach erfolgreich wiederhergestelltem `/DOC`-Stand löscht der ZIP-Sync verbliebene Backup-Verzeichnisse gezielter, statt unnötige Alt-Artefakte im Repo-Root liegen zu lassen. |
| **2.6.72** | 🟡 refactor | Admin/System | **Logpfade werden kompakter relativ zum Repo-Root ausgegeben**: Pfadkontexte zeigen weniger absolute Serverdetails und bleiben für Doku-Sync-Logs trotzdem nachvollziehbar. |

---

### v2.6.71 — 25. März 2026 · Audit-Batch 053, Backup-Service enger gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.71** | 🔴 fix | Core/Backups | **Backup-Zielpfade werden jetzt realpath-basiert gegen den Backup-Root geprüft**: `CMS/core/Services/BackupService.php` akzeptiert Ziel- und Unterverzeichnisse nicht mehr nur per String-Präfix, sondern normalisiert bestehende und neue Pfade über ihren aufgelösten Root-Kontext. |
| **2.6.71** | 🟠 perf | Core/Backups | **Datenbank-Dumps laufen speicherschonender über Tabellenzeilen**: der Dump-Pfad iteriert Tabelleninhalte jetzt zeilenweise statt jede Tabelle per `fetchAll()` vollständig in den Speicher zu ziehen. |
| **2.6.71** | 🔴 security | Core/Backups | **REST-S3-Uploads wurden enger begrenzt**: Uploads akzeptieren nur noch lesbare Dateien innerhalb des Backup-Roots, blockieren auffällige Endpoint-/Bucket-Werte und laden keine übergroßen Backup-Dateien mehr blind komplett in den Request-Pfad. |

---

### v2.6.70 — 25. März 2026 · Audit-Batch 052, Mail-Admin-Operationen bereinigt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.70** | 🔴 fix | Admin/System | **Mail-Admin-Aktionen fangen Unterservice-Ausnahmen konsistenter ab**: `CMS/admin/modules/system/MailSettingsModule.php` kapselt Queue-Läufe, Testmails, Graph-Tests sowie Cache-/Log-Clears jetzt sauber über generische Fehlerpfade, statt sich auf implizit störungsfreie Unterservices zu verlassen. |
| **2.6.70** | 🔴 security | Admin/System | **Unterservice-Rückgaben werden vor der UI-Nutzung sanitisiert**: Test-, Queue- und Graph-Antworten werden auf kompakte, UI-taugliche Message-/Error-Felder reduziert, damit keine ausufernden oder künftig detailreicheren Service-Payloads ungebremst in den Admin-Flow rutschen. |
| **2.6.70** | 🟡 refactor | Admin/System | **Queue-Save-Pfad auditierbarer gemacht**: Queue-Konfiguration und optionale Cron-Token-Rotation laufen nun über einen gemeinsamen Guard-/Try-Catch-Pfad und protokollieren die Token-Neuerstellung explizit im Audit-Kontext mit. |

---

### v2.6.69 — 25. März 2026 · Audit-Batch 051, Doku-Sync-Orchestrator serialisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.69** | 🔴 fix | Admin/System | **Doku-Sync verlangt jetzt intern explizit Admin-Rechte**: `CMS/admin/modules/system/DocumentationSyncService.php` verlässt sich nicht mehr nur auf den äußeren Wrapper, sondern blockiert direkte Service-Aufrufe ohne Admin-Kontext selbstständig. |
| **2.6.69** | 🔴 security | Admin/System | **Parallele Doku-Syncs werden zentral abgefangen**: Git- und ZIP-basierte Läufe teilen sich jetzt ein gemeinsames Lockfile im Orchestrator, sodass gleichzeitige Sync-Starts nicht mehr gegeneinander arbeiten oder denselben `/DOC`-Baum parallel anfassen. |
| **2.6.69** | 🟡 refactor | Admin/System | **Capabilities berücksichtigen Fehlkonfigurationen früher**: Der Service meldet inkonsistente Repo-/DOC-/ZIP-/Integritätsprofile bereits im Statuspfad als „nicht verfügbar“, statt der Oberfläche trotz kaputter Sync-Konfiguration noch einen scheinbar nutzbaren Modus anzuzeigen. |

---

### v2.6.68 — 25. März 2026 · Audit-Batch 050, Paket-Admin restriktiver gemacht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.68** | 🔴 fix | Admin/Subscriptions | **Paket-Entry akzeptiert nur noch bekannte Aktionen**: `CMS/admin/packages.php` prüft POST-Aktionen jetzt per Allowlist, leitet CSRF-/Aktionsfehler konsistent per Redirect + Flash zurück und vermeidet lose Wrapper-Sonderpfade. |
| **2.6.68** | 🔴 security | Admin/Subscriptions | **Paket-Mutationen validieren Slugs und Zugriffe restriktiver**: `CMS/admin/modules/subscriptions/PackagesModule.php` prüft Admin-Zugriff intern, erzwingt valide/unique Slugs und gibt bei Save-/Delete-/Toggle-Fehlern keine rohen Exception-Texte mehr an die UI weiter. |
| **2.6.68** | 🟡 refactor | Admin/Subscriptions | **Paket-Änderungen werden sauberer auditiert**: Erstellen, Aktualisieren, Löschen, Aktivieren und Standard-Seed-Läufe schreiben jetzt strukturierte Audit-Ereignisse statt nur lose Rückgabewerte zu liefern. |

---

### v2.6.67 — 25. März 2026 · Audit-Batch 049, Documentation-Katalog defensiver gemacht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.67** | 🔴 fix | Admin/System | **Doku-Dateien werden kontrollierter gelesen**: `CMS/admin/modules/system/DocumentationCatalog.php` begrenzt Preview- und Vollreads serverseitig auf feste Maximalgrößen und kappt übergroße Dokumente für die Admin-Ansicht kontrolliert statt ungebremst komplette Dateien einzulesen. |
| **2.6.67** | 🔴 security | Admin/System | **Docs-Root- und Symlink-Grenzen nachgezogen**: der Katalog liest nur noch echte Dateien innerhalb des `/DOC`-Roots, überspringt Symlinks im rekursiven Scan und loggt Dateipfade kompakter relativ statt mit rohen absoluten Serverpfaden. |
| **2.6.67** | 🟠 perf | Admin/System | **Metadaten-Scanning mit weniger I/O**: Titel/Excerpts werden nur noch aus begrenzten Preview-Reads aufgebaut, sodass der Doku-Katalog beim Section-Scan weniger unnötige Datei-Last erzeugt. |

---

### v2.6.66 — 25. März 2026 · Audit-Batch 048, Documentation-Wrapper vereinheitlicht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.66** | 🔴 fix | Admin/System | **Doku-Entry normalisiert Dokumentpfade jetzt defensiver**: `CMS/admin/documentation.php` begrenzt `doc`-Parameter auf erwartete Markdown-/CSV-Ziele, verwirft Traversal-artige Segmente und nutzt dieselbe normalisierte Auswahl für Redirect und Render-Aufruf. |
| **2.6.66** | 🔴 security | Admin/System | **POST-Dispatch und Alert-State bleiben enger am Admin-Standard**: unbekannte Aktionen laufen über einen generischen Fallback; Session-Alerts werden nur noch als Array übernommen und nicht lose direkt gerendert. |
| **2.6.66** | 🟡 refactor | Admin/System | **Doku-View nutzt den gemeinsamen Flash-Alert-Partial**: `CMS/admin/views/system/documentation.php` folgt jetzt dem etablierten Admin-Alert-Muster statt eigener Inline-Alert-Ausgabe. |

---

### v2.6.65 — 25. März 2026 · Audit-Batch 047, Backup-Flows vereinheitlicht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.65** | 🔴 fix | Admin/System | **Backup-Entry und DB-Backups greifen jetzt sauber ineinander**: `CMS/admin/backups.php` dispatcht bekannte POST-Aktionen einheitlich; `CMS/admin/modules/system/BackupsModule.php` erzeugt reine Datenbank-Backups jetzt über verwaltbare Container statt als lose Root-Dateien. |
| **2.6.65** | 🔴 security | Admin/System | **Legacy-Dateien bleiben kontrolliert verwaltbar**: `CMS/core/Services/BackupService.php` erkennt alte `database_*.sql(.gz)`-Backups weiter defensiv, listet sie mit Metadaten und erlaubt das Löschen nur innerhalb des Backup-Roots. |
| **2.6.65** | 🟠 perf | Admin/System | **Große Backup-Listen werden früher begrenzt**: der Service priorisiert Verzeichniskandidaten vor dem Manifest-Parsing und lädt für die Admin-Liste nur noch die relevanten neuesten Backups statt stumpf jedes Manifest einzulesen. |

---

### v2.6.64 — 25. März 2026 · Audit-Batch 046, Mail-Settings gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.64** | 🔴 fix | Admin/System | **Mail-Entry und Mail-Settings validieren Aktionen, Hosts, URLs und Empfänger restriktiver**: `CMS/admin/mail-settings.php` akzeptiert nur noch bekannte POST-Aktionen; `CMS/admin/modules/system/MailSettingsModule.php` normalisiert SMTP-Host, Azure-/Graph-Endpunkte und Testempfänger jetzt enger und blockt unsaubere Werte deutlich früher. |
| **2.6.64** | 🔴 security | Admin/System | **Sensible Auditdaten werden maskiert**: Empfängeradressen sowie Tenant-/Client-Kennungen landen nur noch maskiert in Audit-Kontexten; Queue-Läufe protokollieren keine rohen Ergebnis-Arrays mehr. |
| **2.6.64** | 🟡 refactor | Admin/System | **Fehlerpfade generischer und interner abgesichert**: das Modul prüft Admin-Zugriff jetzt intern, gibt bei Save-/Graph-/Queue-/Testpfaden keine rohen Detailfehler mehr an die UI und auditiert Cache-Clears explizit. |

---

### v2.6.63 — 25. März 2026 · Audit-Batch 045, Subscription-Settings gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.63** | 🔴 fix | Admin/Subscriptions | **Abo-Settings validieren IDs und Pflichtwerte restriktiver**: `CMS/admin/modules/subscriptions/SubscriptionSettingsModule.php` akzeptiert Standardpläne sowie AGB-/Widerrufsseiten jetzt nur noch, wenn die referenzierten Datensätze tatsächlich existieren bzw. veröffentlicht sind. |
| **2.6.63** | 🟠 perf | Admin/Subscriptions | **Settings-Laden und -Speichern gebündelt**: allgemeine und Paket-Settings werden gesammelt geladen und über einen gemeinsamen Persistenzpfad geschrieben, statt pro Option wiederholt eigene Existenzabfragen auszulösen. |
| **2.6.63** | 🟡 refactor | Admin/Subscriptions | **Fehler- und Auditpfade vereinheitlicht**: das Modul prüft Admin-Zugriff jetzt auch intern, gibt keine rohen Exception-Texte mehr an die UI und protokolliert Save-Vorgänge strukturiert über Logger und Audit-Log. |

---

### v2.6.62 — 25. März 2026 · Audit-Batch 044, Cookie-Manager nachgeschärft

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.62** | 🔴 fix | Admin/Legal | **Matomo-Self-Hosted-URL jetzt strikt validiert**: `CMS/admin/modules/legal/CookieManagerModule.php` akzeptiert gespeicherte Matomo-URLs nur noch als saubere HTTP(S)-Ziele ohne eingebettete Zugangsdaten und bricht bei ungültigen Werten früh mit einer klaren Admin-Meldung ab. |
| **2.6.62** | 🟠 perf | Admin/Legal | **Scanner- und Settings-Zugriffe stärker gestaffelt**: Low-Value-Pfade wie Cache-, Vendor-, Upload- oder Backup-Verzeichnisse werden im Cookie-Scanner übersprungen; Scan-Metadaten und Settings-Updates werden gebündelt statt in mehreren Einzelpfaden geschrieben. |
| **2.6.62** | 🟡 refactor | Admin/Legal | **Kategorie-/Settings-Lookups entkoppelt**: Default-Kategorien und Setting-Existenzprüfungen nutzen interne Caches, wodurch wiederholte Datenbank-Existenzchecks im Modulpfad sauberer gebündelt werden. |

---

### v2.6.61 — 24. März 2026 · Audit-Batch 043, Documentation-Renderer gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.61** | 🔴 fix | Admin/Documentation | **Link-Resolver-Ausfälle bleiben lokal**: `CMS/admin/modules/system/DocumentationRenderer.php` fängt Resolver-Fehler für Markdown-Links jetzt kontrolliert ab und fällt auf sichere Platzhalter-Links zurück, statt das gesamte Rendering mitzureißen. |
| **2.6.61** | 🔴 security | Admin/Documentation | **Href- und Render-Grenzen defensiver gemacht**: protokollrelative `//`-Links, Backslashes und Steuerzeichen werden verworfen; Linkziele werden gekappt und überlange Codeblöcke nur noch bis zu einem festen Maximalumfang gerendert. |
| **2.6.61** | 🟡 refactor | Admin/Documentation | **Codeblock- und Link-Guards vereinheitlicht**: große Markdown-Codefences laufen jetzt über denselben Guard-/Log-Pfad wie andere Renderer-Limits und halten die Admin-Dokumentation auch bei Sonderfällen stabiler. |

---

### v2.6.60 — 24. März 2026 · Audit-Batch 042, Security-Audit-Modul nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.60** | 🔴 fix | Admin/Security | **Audit-Log-Cleanup auf Security/Auth begrenzt**: `CMS/admin/modules/security/SecurityAuditModule.php` zählt und löscht alte Logeinträge jetzt nur noch innerhalb der vom Modul tatsächlich angezeigten Sicherheits- und Auth-Kategorien. |
| **2.6.60** | 🔴 security | Admin/Security | **Audit-Details und IP-Adressen defensiver gemacht**: Detailtexte werden sanitisiert, IP-Adressen im Audit-Log maskiert und `.htaccess`-Fehlerpfade ohne unnötige absolute Serverpfade protokolliert. |
| **2.6.60** | 🟡 refactor | Admin/Security | **Prüfpfade gezielter auf Runtime-Konfiguration ausgedehnt**: Das Modul bewertet jetzt zusätzlich `config/app.php`-Berechtigungen und verwendet einen gemeinsamen Sanitize-Pfad für Security-Audit-Kontexte. |

---

### v2.6.59 — 24. März 2026 · Audit-Batch 041, Settings-Modul gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.59** | 🔴 fix | Admin/Settings | **Settings-Persistenz ohne N+1-Existenzchecks**: `CMS/admin/modules/settings/SettingsModule.php` lädt vorhandene Setting-Namen jetzt gesammelt vor, statt pro Option zusätzliche `COUNT(*)`-Abfragen auszuführen. |
| **2.6.59** | 🔴 security | Admin/Settings | **Audit- und Mail-Kontexte defensiver gemacht**: Exception-Texte werden sanitisiert protokolliert, Test-Mail-Audits maskieren Empfängeradressen und URL-Migrationen landen mit kompakten Summaries statt roher Detail-Arrays im Audit-Log. |
| **2.6.59** | 🟡 refactor | Admin/Settings | **Konfigurations-Schreibpfad robuster gemacht**: `config/app.php` und `.htaccess` werden mit sichererer Ersatzlogik geschrieben; Tabellen-/Spaltenprüfungen für die URL-Migration nutzen jetzt wiederverwendete Caches statt redundanter Wiederholungsabfragen. |

---

### v2.6.58 — 24. März 2026 · Audit-Batch 040, Plugin-Marketplace gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.58** | 🔴 fix | Admin/Marketplace | **Lokale Manifestpfade und Plugin-Zielverzeichnis restriktiver geprüft**: `CMS/admin/modules/plugins/PluginMarketplaceModule.php` akzeptiert lokale Manifestpfade nur noch ohne Traversal-Segmente und validiert das Plugins-Verzeichnis vor der Auto-Installation gegen Schreibbarkeit und erwarteten Runtime-Root. |
| **2.6.58** | 🔴 security | Admin/Marketplace | **ZIP-Archive gegen auffällige Strukturen begrenzt**: Plugin-Pakete werden jetzt zusätzlich auf maximale Eintragsanzahl, unkomprimierte Gesamtgröße, Kontrollzeichen und segmentierte Pfadmanipulationen geprüft, bevor entpackt wird. |
| **2.6.58** | 🟡 refactor | Admin/Marketplace | **Download-/Entpackpfade sauberer gekapselt**: temporäre Dateien werden kontrollierter aufgeräumt, Schreibfehler beim lokalen Paket-Store liefern klare Fehlpfade und Registry-/Manifest-Downloads nutzen zentrale Größenlimits. |

---

### v2.6.57 — 24. März 2026 · Audit-Batch 039, Performance-Modul nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.57** | 🔴 fix | Admin/Performance | **Performance-Settings ohne N+1-Existenzchecks gespeichert**: `CMS/admin/modules/seo/PerformanceModule.php` lädt vorhandene Setting-Namen jetzt gesammelt vor, statt pro Einzelwert zusätzliche COUNT-Abfragen auszuführen. |
| **2.6.57** | 🔴 security | Admin/Performance | **Session- und Pfadkontexte defensiver gemacht**: Cache-Verzeichnisangaben und Medienpfade werden ohne unnötige Server-Interna ausgegeben; Session-Listen maskieren IP-Adressen und bereinigen User-Agents vor der View-Ausgabe. |
| **2.6.57** | 🟡 refactor | Admin/Performance | **Audit-/Warmup-Kontexte bereinigt**: OPcache-Warmup- und Save-Fehlerpfade loggen nur noch sanitisierte Kurzkontexte statt potenziell ausufernde Detaildaten direkt ins Audit-Log zu kippen. |

---

### v2.6.56 — 24. März 2026 · Audit-Batch 038, SEO-Suite-Modul nachgeschärft

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.56** | 🔴 fix | Admin/SEO | **Submission- und Social-Defaults restriktiver validiert**: `CMS/admin/modules/seo/SeoSuiteModule.php` akzeptiert Submission-Ziele, OG-Typen und Twitter-Card-Werte nur noch über explizite Allowlists; Matomo-Site-IDs werden serverseitig auf positive Ganzzahlen reduziert. |
| **2.6.56** | 🟠 perf | Admin/SEO | **Settings-Persistenz ohne N+1-Existenzchecks**: beim Speichern von SEO-Einstellungen werden vorhandene Setting-Keys jetzt gesammelt vorgeladen, statt pro Einzelwert erst ein zusätzlicher COUNT-Query zu laufen. |
| **2.6.56** | 🟡 refactor | Admin/SEO | **Fehler- und Statusdaten bereinigt**: Audit-Fehlertexte werden sanitisiert protokolliert und Sitemap-Dateistatusdaten liefern keine absoluten Serverpfade mehr an die Admin-Oberfläche weiter. |

---

### v2.6.55 — 24. März 2026 · Audit-Batch 037, Git-Doku-Sync gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.55** | 🔴 fix | Admin/Documentation | **Git-basierter Doku-Sync mit Ref- und Status-Gates nachgeschärft**: `CMS/admin/modules/system/DocumentationGitSync.php` prüft den Remote-Ref jetzt explizit vor dem Checkout und bricht bei nicht prüfbarem oder inkonsistentem `/DOC`-Status kontrolliert ab. |
| **2.6.55** | 🔴 security | Admin/Documentation | **Lokale Änderungen und Parallel-Läufe werden nicht mehr still überfahren**: laufende Git-Syncs werden per Lockfile serialisiert, und uncommittete bzw. untracked Änderungen unter `/DOC` blockieren den Sync mit auditierbarem Fehlerpfad. |
| **2.6.55** | 🟡 refactor | Admin/Documentation | **Git-Aufrufe restriktiver und Log-Kontexte sauberer**: Fetches laufen mit reduzierten Nebeneffekten (`--no-tags --prune --no-recurse-submodules`), Ref-/Pfad-Kontexte werden sanitisiert und Runtime-Fehler landen zuverlässig im generischen Modul-Fehlerpfad. |

---

### v2.6.54 — 24. März 2026 · Audit-Batch 036, Root-Cron gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.54** | 🔴 fix | Core/Cron | **Root-Cron-Entry auf kontrollierte Web-Methoden und normalisierte Parameter begrenzt**: `CMS/cron.php` akzeptiert im Web nur noch `GET` und `HEAD`, normalisiert `task` und `limit` serverseitig und beantwortet `HEAD`-Checks ohne unnötigen Response-Body. |
| **2.6.54** | 🔴 security | Core/Cron | **Token- und Fehlerpfade nachgeschärft**: Cron-Tokens können zusätzlich über Header transportiert werden, parallele Läufe werden per Lockfile abgefangen und rohe Exception-Details leaken nicht mehr direkt in JSON-Antworten. |
| **2.6.54** | 🟡 refactor | Core/Cron | **Operative Schutzgeländer ergänzt**: der Entry verzichtet auf unnötigen Session-Start, setzt `X-Robots-Tag` für Web-Cron-Antworten und protokolliert technische Fehler nur noch intern in sanitierter Form. |

---

### v2.6.53 — 24. März 2026 · Audit-Batch 035, Doku-Downloader gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.53** | 🔴 fix | Admin/Documentation | **Doku-Downloader gegen falsche Ziele und nicht-zipartige Responses gehärtet**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` akzeptiert nur noch dedizierte Temp-Zieldateien und erwartete GitHub-ZIP-URLs. |
| **2.6.53** | 🔴 security | Admin/Documentation | **ZIP-Signatur- und Größenprüfungen ergänzt**: zu kleine, zu große oder nicht mit ZIP-Magic beginnende Responses werden vor dem Schreiben verworfen; Remote-Fehler werden nur noch generisch an die UI gegeben. |
| **2.6.53** | 🟡 refactor | Admin/Documentation | **Download-Pfade auditierbar gemacht**: erfolgreiche und fehlgeschlagene Downloads werden mit sanitierter URL-/Pfad-Kontextinfo geloggt und auditiert. |

---

### v2.6.52 — 24. März 2026 · Audit-Batch 034, GitHub-ZIP-Doku-Sync gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.52** | 🔴 fix | Admin/Documentation | **GitHub-ZIP-Sync gegen fehlerhafte Quellen und Archive gehärtet**: `CMS/admin/modules/system/DocumentationGithubZipSync.php` validiert ZIP-URL und Integritätsprofil jetzt auch intern, statt sich nur auf Vorprüfungen außerhalb des Moduls zu verlassen. |
| **2.6.52** | 🔴 security | Admin/Documentation | **Archivgrenzen und Kontext-Sanitizing nachgeschärft**: ZIP-Dateien mit zu vielen Einträgen oder zu großer Gesamtgröße werden früh verworfen; Audit- und Logger-Kontexte enthalten keine rohen Exception-Texte oder kompletten Fremd-URLs mehr. |
| **2.6.52** | 🟡 refactor | Admin/Documentation | **Erfolgspfad auditiert**: erfolgreiche GitHub-ZIP-Syncs werden explizit protokolliert und liefern strukturierte Dokumentenzahlen statt lose impliziter Seiteneffekte. |

---

### v2.6.51 — 24. März 2026 · Audit-Batch 033, Backup-Modul gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.51** | 🔴 fix | Admin/System | **Backup-Modul mit internen RBAC- und CSRF-Gates abgesichert**: `CMS/admin/modules/system/BackupsModule.php` validiert Lese- und Schreibzugriffe jetzt auch intern und verlässt sich nicht nur auf den äußeren Admin-Entry-Point. |
| **2.6.51** | 🔴 security | Admin/System | **Backup-Metadaten und Löschpfade stärker eingegrenzt**: nur noch erlaubte Backup-Namen, Typen und Dateiendungen gelangen in UI- und Delete-Pfade; lose Manifest-/History-Daten werden vor der Anzeige serverseitig normalisiert. |
| **2.6.51** | 🟡 refactor | Admin/System | **Audit- und Fehlerpfade vereinheitlicht**: erfolgreiche Create-/Delete-Aktionen werden explizit auditiert; technische Fehlerdetails landen gekürzt im Logger statt roh im UI-Kontext. |

---

### v2.6.50 — 24. März 2026 · Audit-Batch 032, Member-Dashboard-Modul gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.50** | 🔴 fix | Admin/Member | **Member-Dashboard-Modul mit internen RBAC- und CSRF-Gates abgesichert**: `CMS/admin/modules/member/MemberDashboardModule.php` prüft Schreibzugriffe jetzt pro Bereich intern gegen Capability und Sicherheitstoken statt sich nur auf die äußere Admin-Shell zu verlassen. |
| **2.6.50** | 🟠 perf | Admin/Member | **Settings- und KPI-Zugriffe gebündelt**: Member-Settings, Plugin-Widget-Metadaten und Dashboard-Statistiken werden deutlich kompakter geladen, wodurch wiederholte Einzelqueries im Modul entfallen. |
| **2.6.50** | 🟡 refactor | Admin/Member | **Auditierbare Save-Pfade**: erfolgreiche Konfigurationsänderungen an Member-Dashboard-Bereichen werden explizit auditiert; Fehlerpfade loggen nur gekürzte technische Details statt rohe Exception-Texte zu streuen. |

---

### v2.6.49 — 24. März 2026 · Audit-Batch 031, Documentation-Sync-Dateisystem gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.49** | 🔴 fix | Admin/Documentation | **Dateisystem-Grenzen des Doku-Syncs nachgeschärft**: `CMS/admin/modules/system/DocumentationSyncFilesystem.php` erlaubt Copy-, Rename-, Delete-, Count- und Integrity-Pfade nur noch innerhalb explizit verwalteter Repo-, DOC- und Temp-Roots. |
| **2.6.49** | 🔴 security | Admin/Documentation | **Staging-, Backup- und Cleanup-Pfade isoliert**: auch noch nicht existierende Zielpfade werden über ihren aufgelösten Elternpfad gegen die erlaubten Arbeitsbereiche geprüft, bevor Dateisystem-Mutationen stattfinden. |
| **2.6.49** | 🟡 refactor | Admin/Documentation | **Root-Kontext explizit verdrahtet**: `DocumentationSyncService` instanziiert den Filesystem-Dienst jetzt mit Repository-, DOC- und Temp-Root, sodass Guard-Logik nicht mehr implizit oder kontextfrei arbeiten muss. |

---

### v2.6.48 — 24. März 2026 · Audit-Batch 030, EditorJs Remote-Media-Service gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.48** | 🔴 fix | Core/EditorJs | **Remote-Media-Fetches für Editor.js deutlich restriktiver gemacht**: `CMS/core/Services/EditorJs/EditorJsRemoteMediaService.php` akzeptiert nur noch normalisierte HTTPS-URLs ohne eingebettete Credentials und blockt überlange oder zeilenumbruchhaltige Remote-URLs frühzeitig ab. |
| **2.6.48** | 🔴 security | Core/EditorJs | **Remote-Metadaten und Preview-Bilder gehärtet**: fremdes HTML wird größenbegrenzt verarbeitet, Metadaten werden sauber gekürzt und bereinigt, Preview-Bilder nur noch als validierte sichere Remote-URLs übernommen. |
| **2.6.48** | 🟡 refactor | Core/EditorJs | **Fehlerpfade bereinigt**: Netzwerk- und Remote-Fehler werden intern geloggt, aber gegenüber Editor.js nur noch generisch und UI-tauglich ausgegeben; der libxml-Fehlerzustand wird nach DOM-Verarbeitung wiederhergestellt. |

---

### v2.6.47 — 24. März 2026 · Audit-Batch 029, Mail-Service gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.47** | 🔴 fix | Core/Mail | **Mail-Service gegen Header-Injection und rohe Transportfehler gehärtet**: `CMS/core/Services/MailService.php` validiert Header, Adresslisten, Empfänger, Absender und Betreff restriktiver und blockiert kritische Header-Overrides wie `To`, `Subject` oder `Return-Path`. |
| **2.6.47** | 🔴 security | Core/Mail | **TLS-Enforcement für SMTP verschärft**: nicht-lokale SMTP-Hosts sowie OAuth2-basierte Mailtransporte laufen nicht mehr still ohne Verschlüsselung, sondern werden im Service auf TLS gehoben. |
| **2.6.47** | 🟡 refactor | Core/Mail | **Fehlerpfade bereinigt**: UI- und API-Rückgaben aus den Detailed-Send-Pfaden verwenden klassifizierte, generische Fehlermeldungen statt roher Provider- oder Exception-Texte; interne Fehlertexte werden gekürzt und bereinigt geloggt. |

---

### v2.6.46 — 24. März 2026 · Audit-Batch 028, Landing-Page-Modul gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.46** | 🔴 fix | Admin/Landing | **Landing-Page-Modul gegen freie POST-Payloads und rohe Fehlerausgaben gehärtet**: `CMS/admin/modules/landing/LandingPageModule.php` prüft Admin-Zugriff jetzt auch intern, normalisiert Tabs serverseitig und akzeptiert bei Header-, Content-, Footer-, Design-, Feature- und Plugin-Mutationen nur noch explizit erlaubte Felder. |
| **2.6.46** | 🟠 perf | Admin/Landing | **Kleinere Mutations-Payloads**: unnötige oder fremde POST-Felder werden vor den Service-Aufrufen verworfen, wodurch die Landing-Verwaltung weniger lose Daten weiterreicht und deterministischer speichert. |
| **2.6.46** | 🟡 refactor | Admin/Landing | **Fehlerpfade vereinheitlicht**: statt roher Exception-Meldungen an die Oberfläche werden Fehler intern kanalisiert geloggt und generisch an die UI zurückgegeben. |

---

### v2.6.45 — 24. März 2026 · Audit-Batch 027, Legal-Sites-Modul gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.45** | 🔴 fix | Admin/Legal | **Legal-Sites-Modul gegen lose Seitenzuordnungen und ungebremste Payloads gehärtet**: `CMS/admin/modules/legal/LegalSitesModule.php` prüft Admin-Zugriff jetzt auch intern, validiert zugewiesene Rechtstext-Seiten serverseitig gegen veröffentlichte Seiten und begrenzt HTML- sowie Profilwerte deutlich strenger. |
| **2.6.45** | 🟠 perf | Admin/Legal | **Settings-Zugriffe gebündelt**: Inhalte, Seiten-IDs und Profilwerte werden bei Lese- und Speicherpfaden stärker gesammelt verarbeitet statt über viele Einzelabfragen. |
| **2.6.45** | 🟡 refactor | Admin/Legal | **Persistenz- und Fehlerpfade vereinheitlicht**: generierte Rechtstexte, Profilwerte und Seitensynchronisierungen nutzen konsistente Settings-Writer; Audit-Logs führen keine rohen Exception-Texte mehr. |

---

### v2.6.44 — 24. März 2026 · Audit-Batch 026, Dokumentations-Modul gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.44** | 🔴 fix | Admin/System | **Dokumentations-Modul gegen lose Pfad- und Zugriffsannahmen gehärtet**: `CMS/admin/modules/system/DocumentationModule.php` prüft Admin-Zugriff jetzt auch intern, validiert Repository-/`/DOC`-Layout vor Datenaufbau und Sync-Aufruf und akzeptiert ausgewählte Dokumente nur noch in erwarteten Längen und Dateitypen. |
| **2.6.44** | 🔴 fix | Admin/System | **Render- und Sync-Fehler laufen kontrollierter**: unerwartete Ausnahmen werden intern gekürzt geloggt und nach außen nur noch mit generischen, UI-tauglichen Meldungen beantwortet. |
| **2.6.44** | 🟡 refactor | Admin/System | **Fehlerzustände liefern konsistente View-Daten**: das Modul gibt auch bei Fehlkonfigurationen strukturierte Antwortpayloads zurück, damit die Doku-Oberfläche stabil und ohne lose Spezialfälle rendern kann. |

---

### v2.6.43 — 24. März 2026 · Audit-Batch 025, Feed-Service gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.43** | 🔴 fix | Core/Feeds | **Feed-Service gegen unsichere Remote-Ziele und rohe Fehlerpfade gehärtet**: `CMS/core/Services/FeedService.php` validiert Feed-URLs jetzt auf erlaubte HTTP(S)-Schemes, blockiert Hosts mit Credentials sowie private/reservierte Zielnetze und begrenzt Batch-Listen auf eine kontrollierte Anzahl valider Feed-Quellen. |
| **2.6.43** | 🔴 fix | Core/Feeds | **Feed-Metadaten und Items werden defensiver normalisiert**: Titel, Kategorien, Autoren, GUIDs sowie Link-/Bild-Ziele werden serverseitig bereinigt, während Feed-Beschreibungen und -Inhalte über `PurifierService` sanitisiert werden. |
| **2.6.43** | 🟡 refactor | Core/Feeds | **Cache- und Logging-Pfade vereinheitlicht**: Cache-Dateien werden nur noch innerhalb des echten Feed-Cache-Roots gelöscht, Parser-/Remote-Fehler werden gekürzt geloggt und nach außen nur noch generisch beantwortet. |

---

### v2.6.42 — 24. März 2026 · Audit-Batch 024, Azure-Mail-Token-Provider gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.42** | 🔴 fix | Core/Integration | **Azure-Mail-Token-Provider gegen Konfigurationsdrift und unsaubere Endpunkte gehärtet**: `CMS/core/Services/AzureMailTokenProvider.php` validiert Tenant-/Client-/Mailbox-/Scope-Werte restriktiver, akzeptiert nur noch sichere Microsoft-Login-Tokenpfade und verwirft Query-/Fragment-Anteile an benutzerdefinierten Token-Endpunkten. |
| **2.6.42** | 🔴 fix | Core/Integration | **Token-Cache und Remote-Antworten defensiver gemacht**: gecachte Tokens werden nur noch bei sauberer Form und ausreichender Restlaufzeit wiederverwendet; kaputte oder abgelaufene Cache-Einträge werden aktiv entfernt, während Remote-JSON und Fehlermeldungen serverseitig begrenzt und bereinigt werden. |
| **2.6.42** | 🟡 refactor | Core/Integration | **Azure-OAuth2-Fehlerpfade vereinheitlicht**: Token-Typen werden konsistent normalisiert und Response-/Remote-Fehler laufen über kleine zentrale Helper statt über lose Einzelprüfungen. |

---

### v2.6.41 — 24. März 2026 · Audit-Batch 023, Cookie-Manager-Modul gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.41** | 🔴 fix | Admin/Legal | **Cookie-Manager-Modul gegen unsaubere Mutationen und lose Zugriffsannahmen gehärtet**: `CMS/admin/modules/legal/CookieManagerModule.php` prüft Admin-Zugriff jetzt auch intern, validiert Kategorie-/Service-Payloads strenger und blockiert doppelte Slugs sowie das Löschen noch verwendeter Kategorien serverseitig. |
| **2.6.41** | 🔴 fix | Admin/Legal | **Cookie-Scanner begrenzt und normalisiert**: Datei- und DB-Scans lesen nur noch begrenzte Größen/Mengen, kürzen Quellen/Resultate und normalisieren gespeicherte Treffer auf bekannte kuratierte Services zurück. |
| **2.6.41** | 🟡 refactor | Admin/Legal | **Settings- und Audit-Pfade vereinheitlicht**: Cookie-Settings werden gesammelt persistiert statt per wiederholtem Existenz-Check, während Mutationen und Scanner-Läufe zusätzlich nachvollziehbar im Audit-Log landen. |

---

### v2.6.40 — 24. März 2026 · Audit-Batch 022, Security-Audit-Modul gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.40** | 🔴 fix | Admin/Security | **Security-Audit-Modul gegen rohe Audit-Daten und Detail-Leaks gehärtet**: `CMS/admin/modules/security/SecurityAuditModule.php` liest nur noch relevante Security-/Auth-Logfelder, begrenzt Audit-Details und Check-Texte serverseitig und schützt den Modulzugriff zusätzlich gegen unberechtigte Aufrufe ab. |
| **2.6.40** | 🔴 fix | Admin/Security | **Log-Bereinigung und Teilfehler liefern nur noch generische UI-Meldungen**: Fehlschläge bei `clearLog()`, Passwort-Hash-Checks oder Audit-Log-Ladevorgängen werden intern geloggt und auditierbar protokolliert, ohne rohe Exception-Texte in die Oberfläche zu leaken. |
| **2.6.40** | 🟡 refactor | Admin/Security | **.htaccess-Inspektion und Audit-Checks defensiver normalisiert**: Header-Fallback-Prüfung liest die Root-`.htaccess` nur noch begrenzt ein und das Modul bündelt Status-/Textnormalisierung zentral über kleine Helper. |

---

### v2.6.39 — 24. März 2026 · Audit-Batch 021, Dokumentations-Renderer gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.39** | 🔴 fix | Admin/System | **Dokumentations-Renderer gegen ausufernde Render-Payloads gehärtet**: `CMS/admin/modules/system/DocumentationRenderer.php` begrenzt Markdown-/CSV-Größe, Zeilenanzahl sowie Tabellen- und Zellumfang, bevor Inhalte in HTML für den Admin-Bereich überführt werden. |
| **2.6.39** | 🔴 fix | Admin/System | **Linkziele im Doku-HTML enger validiert**: erzeugte `href`-Werte werden auf saubere Anchors, interne Pfade oder valide HTTP(S)-URLs begrenzt, sodass keine losen Sonderziele im Admin-Rendering landen. |
| **2.6.39** | 🟡 refactor | Admin/System | **Render-Grenzen werden nachvollziehbar geloggt**: begrenzte Dokumente, Tabellen und CSV-Ansichten schreiben jetzt Guard-Logs statt ungebremst oder still in große HTML-Ausgaben zu laufen. |

---

### v2.6.38 — 24. März 2026 · Audit-Batch 020, Kommentar-Service gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.38** | 🔴 fix | Core/Comments | **Öffentliche Kommentarerstellung gegen Missbrauch und geschlossene Posts gehärtet**: `CMS/core/Services/CommentService.php` akzeptiert nur noch valide Autor-/Mail-/Content-Payloads, blockiert Kommentare auf nicht veröffentlichten oder kommentargesperrten Beiträgen und normalisiert IP-Adressen defensiver. |
| **2.6.38** | 🔴 fix | Core/Comments | **Kommentar-Flood-Limit und Logging-/Audit-Pfade ergänzt**: der Service begrenzt Kommentarfluten pro Mail/IP/User in einem Zeitfenster und protokolliert verworfene bzw. erfolgreiche Pending-Kommentare intern nachvollziehbar. |
| **2.6.38** | 🟡 refactor | Core/Comments | **Öffentliche Ausgabe und Listenabrufe entschärft**: freigegebene Kommentarlisten leaken keine Autor-Mailadressen mehr ins Frontend und Admin-List-Reads werden zusätzlich auf sinnvolle Grenzen geklemmt. |

---

### v2.6.37 — 24. März 2026 · Audit-Batch 019, Microsoft-Graph-Service gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.37** | 🔴 fix | Core/Integration | **Microsoft-Graph-Service gegen Konfigurationsdrift und Response-Leaks gehärtet**: `CMS/core/Services/GraphApiService.php` validiert Tenant-/Client-/Scope-/Endpoint-Werte restriktiver, akzeptiert nur sichere Graph-/Token-Pfade und gibt bei Verbindungstests nur noch generische Fehlermeldungen nach außen. |
| **2.6.37** | 🟡 refactor | Core/Integration | **Graph-Tokenabruf auf sauberen Form-Request umgestellt**: Client-Credentials werden jetzt als `application/x-www-form-urlencoded` über den HTTP-Client gesendet, inklusive fester Größen- und Content-Type-Grenzen für Antworten. |
| **2.6.37** | 🟠 perf | Core/Integration | **Graph-Antworten defensiver normalisiert**: Organisationsdaten und Remote-Fehler werden gekürzt, bereinigt und auf ein erwartbares Schema reduziert, wodurch Folgepfade weniger Sonderfälle behandeln müssen. |

---

### v2.6.36 — 24. März 2026 · Audit-Batch 018, Hub-Template-Profile gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.36** | 🔴 fix | Admin/Hub | **Hub-Template-Profilmanager gegen unsaubere Payloads und stille Persistenzfehler gehärtet**: `CMS/admin/modules/hub/HubTemplateProfileManager.php` begrenzt Link-/Section-/Starter-Card-Payloads, normalisiert URL-Ziele restriktiver und behandelt fehlgeschlagene Settings-Speicherungen sowie Template-Mutationen nur noch generisch mit internem Logging/Audit. |
| **2.6.36** | 🟠 perf | Admin/Hub | **Template-Nutzungszähler ohne N+1-Abfragen berechnet**: das Hub-Template-Listing holt Usage-Counts gesammelt per Aggregatabfrage statt für jedes Profil separat. |
| **2.6.36** | 🟡 refactor | Admin/Hub | **Vererbte Hub-Sites werden nur noch bei echten Template-Änderungen nachgezogen**: der Profilmanager erkennt unveränderte Link-/Starter-Card-Vererbungen früher und protokolliert fehlschlagende Sync-Updates kontrolliert. |

---

### v2.6.35 — 24. März 2026 · Audit-Batch 017, Firewall-Flow gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.35** | 🔴 fix | Admin/Security | **Firewall-Entry auf Action-Whitelist gebracht**: `CMS/admin/firewall.php` akzeptiert nur noch bekannte POST-Aktionen und behandelt CSRF-/Aktionsfehler konsistent über Redirect + Flash-Alert. |
| **2.6.35** | 🔴 fix | Admin/Security | **Firewall-Modul gegen unvalidierte Regeln und Fehlerdetail-Leaks gehärtet**: `CMS/admin/modules/security/FirewallModule.php` validiert IP-/CIDR-/Country-/UA-Regeln strenger, blockiert Dubletten, prüft Delete-/Toggle-Ziele serverseitig und beantwortet Save-/Mutationsfehler im UI nur noch generisch mit internem Logging/Audit. |
| **2.6.35** | 🟡 refactor | Admin/Security | **Firewall-View an gemeinsamen Security-UI-Standard angenähert**: `CMS/admin/views/security/firewall.php` nutzt Flash-Alerts, rendert Ablaufdaten ohne unescaped Inline-HTML und bestätigt Löschaktionen über `cmsConfirm(...)` statt Browser-`confirm()`. |

---

### v2.6.34 — 24. März 2026 · Audit-Batch 016, Doku-Sync-Orchestrator gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.34** | 🔴 fix | Admin/System | **Doku-Sync-Orchestrator gegen Konfigurationsdrift gehärtet**: `CMS/admin/modules/system/DocumentationSyncService.php` validiert Repository-Root, `/DOC`-Ziel, Branch-/Remote-Werte, GitHub-ZIP-Quelle und Integritätsprofil jetzt zentral, bevor Unterservices den eigentlichen Sync starten. |
| **2.6.34** | 🟡 refactor | Admin/System | **Capability- und Ergebnisfluss vereinheitlicht**: Nicht verfügbare oder inkonsistente Sync-Modi laufen jetzt über einen generischen, auditierbaren Fehlerpfad, während erfolgreiche Git-/GitHub-ZIP-Synchronisationen zusätzlich zentral geloggt und im Audit-Log festgehalten werden. |

---

### v2.6.33 — 24. März 2026 · Audit-Batch 015, Kommentar-Moderation gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.33** | 🔴 fix | Admin/Comments | **Kommentar-Entry an RBAC-Capabilities ausgerichtet**: `CMS/admin/comments.php` nutzt jetzt `comments.view` für den Zugriff, akzeptiert nur noch bekannte POST-Aktionen und hält Redirects enger am validierten Statusfilter. |
| **2.6.33** | 🔴 fix | Admin/Comments | **Kommentar-Modul gegen unvalidierte Mutationen und stille Bulk-Fehler gehärtet**: `CMS/admin/modules/comments/CommentsModule.php` prüft IDs, Zielstatus, Kommentar-Existenz und Rechte serverseitig, begrenzt Bulk-Mengen und protokolliert Teil-/Fehlschläge intern per Logging und Audit-Log. |
| **2.6.33** | 🟡 refactor | Admin/Comments | **Kommentar-View an Rechtezustand gekoppelt**: `CMS/admin/views/comments/list.php` rendert Bulk-Bar, Checkboxen und Row-Actions jetzt capability-basiert und nutzt vorbereitete Post-Ziele aus dem Modul statt roher Slug-Verkettung im Template. |

---

### v2.6.32 — 24. März 2026 · Audit-Batch 014, FileUploadService-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.32** | 🔴 fix | Core/Uploads | **Zentralen Upload-Endpunkt enger abgesichert**: `CMS/core/Services/FileUploadService.php` akzeptiert nur noch echte `POST`-Uploads, prüft Dateipayloads auf Einzeldatei-Form und Pflichtfelder und validiert Zielpfade jetzt segmentweise gegen Traversal-, Dotfile- und Steuerzeichen-Pfade. |
| **2.6.32** | 🔴 fix | Core/Uploads | **Upload-Fehlerpfade gegen Detail-Leaks vereinheitlicht**: Validierungs- und Persistenzfehler aus dem Media-Stack werden im Client nur noch generisch beantwortet, während technische Details intern geloggt und auditierbar protokolliert werden. |

---

### v2.6.31 — 24. März 2026 · Audit-Batch 013, Hub-Sites-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.31** | 🔴 fix | Admin/Hub | **Hub-Sites-Entry auf Whitelist-Flow gebracht**: `CMS/admin/hub-sites.php` akzeptiert nur noch bekannte POST-Aktionen und Views und behandelt Fehlfälle mit konsistenten Fallback-Meldungen statt losem Sonderverhalten. |
| **2.6.31** | 🔴 fix | Admin/Hub | **Hub-Sites-Modul gegen Detail-Leaks und unsaubere Linkziele gehärtet**: `CMS/admin/modules/hub/HubSitesModule.php` normalisiert Suche, Plaintext-, CTA-, Card-, Bild- und Linkwerte zentraler, fällt bei unsicheren URLs auf sichere Defaults zurück und behandelt Save-/Delete-/Duplicate-Fehler im UI nur noch generisch mit internem Logging/Audit. |

---

### v2.6.30 — 24. März 2026 · Audit-Batch 012, Theme-Editor-Resthärtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.30** | 🔴 fix | Admin/Themes | **Theme-Explorer-Wrapper enger begrenzt**: `CMS/admin/theme-explorer.php` verarbeitet den Save-Flow jetzt nur noch über eine explizite Allowlist bekannter Aktionen. |
| **2.6.30** | 🔴 fix | Admin/Themes | **Theme-Editor gegen Rest-Leaks und Binär-/Oversize-Inhalte gehärtet**: `CMS/admin/modules/themes/ThemeEditorModule.php` beantwortet unsichere Dateianfragen kontrolliert, blockiert Binärdaten und zu große neue Inhalte vor dem Schreiben und behandelt Syntax-/Schreibfehler nur noch generisch mit internem Logging/Audit. |
| **2.6.30** | 🟡 refactor | Admin/Themes | **Theme-Editor-View defensiver gemacht**: `CMS/admin/views/themes/editor.php` schützt den Tree-Renderer gegen Redeclare-/Datentyp-Randfälle und escaped den Basis-Link stringenter. |

---

### v2.6.29 — 24. März 2026 · Audit-Batch 011, Pages-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.29** | 🔴 fix | Admin/Content | **Pages-Entry auf Whitelist-Flow gebracht**: `CMS/admin/pages.php` akzeptiert nur noch bekannte POST-Aktionen und Views, leitet CSRF-/Aktionsfehler konsistent per Redirect + Flash zurück und typisiert Bulk-Parameter defensiver vor der Modulübergabe. |
| **2.6.29** | 🔴 fix | Admin/Content | **Pages-Modul gegen Detail-Leaks und unnormalisierte Eingaben gehärtet**: `CMS/admin/modules/pages/PagesModule.php` normalisiert Listenfilter und Bulk-Aktionen zentral, sanitisiert Titel-/Meta-/Medienfelder vor Persistenz und behandelt Save-/Delete-/Bulk-Fehler im UI nur noch generisch mit internem Logging/Audit. |

---

### v2.6.28 — 24. März 2026 · Audit-Batch 010, Posts-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.28** | 🔴 fix | Admin/Content | **Posts-Entry auf Whitelist-Flow gebracht**: `CMS/admin/posts.php` akzeptiert nur noch bekannte POST-Aktionen und Views, typisiert Bulk-/Kategorie-Parameter defensiver und behandelt ungültige Mutationen konsistent über Redirect + Flash-Alert. |
| **2.6.28** | 🔴 fix | Admin/Content | **Posts-Modul gegen Detail-Leaks und versteckte Request-Kopplung gehärtet**: `CMS/admin/modules/posts/PostsModule.php` normalisiert Listenfilter, Bulk-Aktionen sowie mehrere Text-/Meta-/Medienfelder zentral, entkoppelt Kategorie-/Tag-Löschpfade von direkten `$_POST`-Reads und gibt Fehler im UI nur noch generisch aus, während Details intern geloggt und auditierbar protokolliert werden. |

---

### v2.6.27 — 24. März 2026 · Audit-Batch 009, Update-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.27** | 🔴 fix | Admin/System | **Update-Entry auf Aktions-Whitelist gebracht**: `CMS/admin/updates.php` akzeptiert nur noch bekannte POST-Aktionen, normalisiert Plugin-Slugs vor der Übergabe und behandelt ungültige Mutationen konsistent über Redirect + Flash-Alert. |
| **2.6.27** | 🔴 fix | Admin/System | **Updates-Modul gegen Detail-Leaks gehärtet**: `CMS/admin/modules/system/UpdatesModule.php` normalisiert Plugin-Slugs zentral, trennt manuelle von direkt installierbaren Plugin-Updates und gibt Prüf-/Installationsfehler im UI nur noch generisch aus, während Details intern geloggt und auditierbar protokolliert werden. |
| **2.6.27** | 🔴 fix | Core/Updates | **Update-Service enger an erlaubte Roots und sichere Downloads gebunden**: `CMS/core/Services/UpdateService.php` verlangt für Downloads jetzt zusätzlich den SSRF-/DNS-Sicherheitscheck, begrenzt Installationsziele auf erlaubte Core-/Plugin-/Theme-Pfade, verwirft leere Download-Bodies und beantwortet Installationsfehler nach außen generisch. |

---

### v2.6.26 — 24. März 2026 · Audit-Batch 008, Media-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.26** | 🔴 fix | Admin/Media | **Media-Entry defensiver gemacht**: `CMS/admin/media.php` akzeptiert nur noch bekannte POST-Aktionen und normalisiert Redirect-Parameter wie `path`, `view`, `category` und `q`, bevor sie zurück in den Admin-Flow gespiegelt werden. |
| **2.6.26** | 🔴 fix | Admin/Media | **Media-Modul gegen unnormalisierte Eingaben und Detail-Leaks gehärtet**: `CMS/admin/modules/media/MediaModule.php` bereinigt Pfade, Tabs, Views, Suchbegriffe, Datei-/Ordnernamen und Kategorie-Slugs zentral, blockiert System-Kategorien serverseitig und gibt Service-Fehler im UI nur noch generisch aus, während Details intern geloggt und auditierbar protokolliert werden. |
| **2.6.26** | 🟡 refactor | Admin/Media | **Media-Settings enger begrenzt**: Uploadgrößen sowie Qualitäts- und Dimensionsfelder werden vor dem Persistieren konsistenter gekappt, damit das Modul weniger ungültige oder ausreißende Settings an den Service weiterreicht. |

---

### v2.6.25 — 24. März 2026 · Audit-Batch 007, Doku-Sync-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.25** | 🔴 fix | Admin/System | **Git-basierter Doku-Sync defensiver gemacht**: `CMS/admin/modules/system/DocumentationGitSync.php` validiert Repository-/DOC-Ziele und Git-Ref-Teile vor dem Lauf, begrenzt Shell-Fehlerdetails auf interne Logs und liefert im Admin-UI nur noch generische Fehlermeldungen zurück. |
| **2.6.25** | 🔴 fix | Admin/System | **GitHub-ZIP-Sync gegen Pfad- und Link-Fallen gehärtet**: `CMS/admin/modules/system/DocumentationGithubZipSync.php`, `DocumentationSyncDownloader.php` und `DocumentationSyncFilesystem.php` begrenzen Arbeits- und Downloadpfade auf erlaubte Roots, blockieren symbolische Links defensiver, verwerfen leere Download-Bodies und propagieren Cleanup-/Filesystem-Fehler sauberer. |
| **2.6.25** | 🟡 refactor | Admin/System | **Dokumentations-Entry vereinheitlicht**: `CMS/admin/documentation.php` akzeptiert jetzt nur noch die erwartete Aktion `sync_docs`, und `DocumentationSyncService.php` prüft das erlaubte `/DOC`-Layout vor dem eigentlichen Sync zusätzlich vor. |

---

### v2.6.24 — 24. März 2026 · Audit-Batch 006, Member-/Legal-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.24** | 🔴 fix | Admin/Member | **Member-Dashboard-Settings gehärtet**: `CMS/admin/modules/member/MemberDashboardModule.php` normalisiert Dashboard-Logo und Onboarding-CTA-URLs defensiver und führt Speicherroutinen bei Fehlern über einen zentralen, auditierbaren Generic-Error-Pfad statt rohe Exception-Texte an die UI weiterzugeben. |
| **2.6.24** | 🔴 fix | Admin/Legal | **Cookie-Manager robuster gemacht**: `CMS/admin/modules/legal/CookieManagerModule.php` begrenzt Policy-URLs, Slugs, Matomo-Site-IDs und Bannertexte strenger, hält Dateisystem-Scans von Symlinks fern und behandelt Persistenzfehler nur noch generisch im UI. |
| **2.6.24** | 🔴 fix | Admin/Legal | **Legal-Sites-Fehlerpfade vereinheitlicht**: `CMS/admin/modules/legal/LegalSitesModule.php` leakt in Save-, Profil- und Seitengenerierungs-Pfaden keine rohen Exceptions mehr, sondern protokolliert Fehler zentral auditierbar. |

---

### v2.6.23 — 24. März 2026 · Audit-Batch 005, SEO-/Performance-/Settings-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.23** | 🔴 fix | Admin/SEO | **SEO-Suite defensiver gemacht**: `CMS/admin/modules/seo/SeoSuiteModule.php` validiert Indexing-URLs jetzt hostgebunden gegen die eigene Site, dedupliziert Submission-Listen, normalisiert Sitemap-Prioritäten/-Frequenzen und nutzt für Broken-Link-Prüfungen die konfigurierbare Permalink-Struktur statt harter `/blog/`-Annahmen. |
| **2.6.23** | 🔴 fix | Admin/Performance | **Performance-Dateipfade robuster abgesichert**: `CMS/admin/modules/seo/PerformanceModule.php` überspringt Symlinks in Cache-, Session- und Medienläufen, begrenzt numerische Settings sauberer und leakt bei Settings-Fehlern keine rohen Exceptions mehr ins UI. |
| **2.6.23** | 🔴 fix | Admin/Settings | **Allgemeine Einstellungen und Config-Writer gehärtet**: `CMS/admin/modules/settings/SettingsModule.php` normalisiert Logo-/Favicon-Referenzen strenger, schreibt `config/app.php` und `config/.htaccess` kontrollierter über temporäre Dateien und reduziert Fehlerdetail-Leaks in Save-, Migrations- und Slug-Repair-Pfaden. |
| **2.6.23** | 🟡 refactor | Admin/Settings | **Settings-Entry vereinheitlicht**: `CMS/admin/settings.php` akzeptiert nur noch bekannte POST-Aktionen und `CMS/admin/views/settings/general.php` nutzt jetzt den gemeinsamen Flash-Alert-Partial statt eigener Alert-Duplikate. |

---

### v2.6.22 — 24. März 2026 · Audit-Batch 001, Antispam-Härtung & Versions-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.22** | 🔴 fix | Admin/Security | **AntiSpam-Flow gehärtet**: `CMS/admin/antispam.php`, `CMS/admin/modules/security/AntispamModule.php` und `CMS/admin/views/security/antispam.php` verarbeiten Mutationen jetzt mit Action-Allowlist, konsistentem Redirect-/Flash-Flow, generischeren Fehlerantworten und ohne vorbelegtes reCAPTCHA-Secret im Formular. |
| **2.6.22** | 🔴 fix | Core/Backups | **Backup-Pfade und I/O-Failsafes gehärtet**: `CMS/core/Services/BackupService.php` akzeptiert Zielverzeichnisse nur noch innerhalb des Backup-Roots, validiert Backup-Namen, liest Manifeste defensiver ein, folgt beim Löschen keinen Symlinks blind und beseitigt den Mail-Backup-Fehler mit `filesize()` nach dem Löschen der Temp-Datei. |
| **2.6.22** | 🔴 fix | Admin/System | **Backup-Modul leakt keine Rohfehler mehr**: `CMS/admin/modules/system/BackupsModule.php` gibt in der UI nur noch generische Fehlermeldungen aus, statt interne Exception-Texte direkt durchzureichen. |
| **2.6.22** | 🔴 fix | Admin/Themes | **Theme-Dateieditor gegen Traversal und Oversize-Dateien gehärtet**: `CMS/admin/theme-explorer.php`, `CMS/admin/modules/themes/ThemeEditorModule.php` und `CMS/admin/views/themes/editor.php` begrenzen Aktionen, normalisieren Pfade, erzwingen Theme-Root + Größenlimit, ignorieren Symlinks und schreiben Dateien mit `LOCK_EX`. |
| **2.6.22** | 🟡 refactor | Admin/Marketplace | **Marketplace-Entrypoints vereinheitlicht**: `CMS/admin/plugin-marketplace.php` und `CMS/admin/theme-marketplace.php` erlauben nur noch die erwartete Installationsaktion und behandeln CSRF-/Aktionsfehler jetzt konsistent über Redirect + Flash-Alert; die bereits vorhandenen SHA-256-/Allowlist-Gates der Module wurden dabei erneut verifiziert. |
| **2.6.22** | 🔵 docs | Audit | **Inkrementelles Prüfprotokoll eingeführt**: `DOC/audit/ToDoPrüfung.md` dokumentiert die Abarbeitung von `PRÜFUNG.MD` ab jetzt schrittweise; `DOC/audit/BEWERTUNG.md` enthält zusätzlich eine Delta-Sektion für bereits umgesetzte Audit-Batches. |
| **2.6.22** | ⬜ chore | Versionierung | **Release-Quellen wieder synchronisiert**: `CMS/core/Version.php`, `CMS/update.json` und der Changelog-Badge wurden auf denselben Release-Stand gezogen, damit Laufzeit-, Updater- und Doku-Version nicht länger auseinanderlaufen. |

---

### v2.6.21 — 24. März 2026 · Backup-Admin-Fix & Changelog-Konsolidierung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.21** | 🔴 fix | Admin/System | **Leere Backup-Seite behoben**: `CMS/admin/backups.php` setzt vor dem Rendern jetzt denselben `CMS_ADMIN_SYSTEM_VIEW`-Guard wie die übrigen Systemseiten, sodass `/admin/backups` nicht mehr per sofortigem View-`exit` in einer weißen/leeren Seite endet. |
| **2.6.21** | 🎨 style | Admin/UX | **Backup-Alerts wieder sichtbar**: `CMS/admin/views/system/backups.php` rendert Session- und Statusmeldungen jetzt über den gemeinsamen Partial `admin/views/partials/flash-alert.php`, damit Erstellen/Löschen/CSRF-Fehler im UI klar sichtbar werden. |
| **2.6.21** | 🔵 docs | Changelog/Release | **Changelog vollständig vereinheitlicht**: Die gemischten oberen Release-Blöcke wurden auf dasselbe tabellarische Format wie die Historie darunter umgebaut, damit alle Versionen konsistent lesbar und gleich aufgebaut sind. |

---

### v2.6.19 — 24. März 2026 · Device-Cookie-Bindung für Logins

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.19** | 🔴 fix | Core/Auth | **Sessions an Device-Cookie gebunden**: `CMS/core/Auth.php` bindet eingeloggte Sessions jetzt zusätzlich an ein signiertes Device-Cookie `cms_device` mit maximal zwei Stunden TTL; fehlt das Cookie oder passt Signatur/Sitzungsbindung nicht mehr, wird die Session beim nächsten Check sauber invalidiert. |
| **2.6.19** | 🔴 fix | Core/Auth/MFA | **Passkey- und MFA-Logins ziehen mit**: `CMS/core/Auth/AuthManager.php` setzt dieselbe Gerätebindung auch für Passkey- und MFA-abgeschlossene Logins, und Logout räumt Session-Cookie plus Device-Cookie gemeinsam ab. |

---

### v2.6.18 — 24. März 2026 · Upload-Beispiele von Runtime getrennt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.18** | ⬜ chore | Uploads/Docs | **Beispielgrafik aus Runtime-Pfad entfernt**: Die versionierte Datei `SidebarRahmenThumnail_V5_CopilotLizenzen.png` liegt nicht mehr unter `CMS/uploads/`, sondern unter `DOC/assets/examples/`; damit bleibt der Upload-Baum für echte Laufzeitdaten reserviert. |
| **2.6.18** | 🔵 docs | Docs/Assets | **Trennlinie dokumentiert**: `DOC/assets/examples/README.md` hält jetzt explizit fest, dass versionierte Demo-/Referenzdateien in den Doku-/Beispielpfad und nicht in produktive Upload-Verzeichnisse gehören. |

---

### v2.6.17 — 24. März 2026 · Große Editor-Views modularisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.17** | 🟡 refactor | Admin/Views | **Seiten- und Beitragseditor entschlackt**: `CMS/admin/views/posts/edit.php` und `CMS/admin/views/pages/edit.php` delegieren die wiederkehrenden Lesbarkeits-, Vorschau-, SEO-Score- und erweiterten SEO-Blöcke jetzt an gemeinsame Partials unter `CMS/admin/views/partials/`. |
| **2.6.17** | 🟡 refactor | Admin/Partials | **Gemeinsame SEO-/Preview-Bausteine extrahiert**: `content-readability-card.php`, `content-preview-card.php`, `content-seo-score-panel.php` und `content-advanced-seo-panel.php` kapseln die gemeinsamen Admin-Blöcke, ohne bestehende IDs, Form-Felder oder Frontend-Hooks der Editor-/SEO-Logik zu verbiegen. |

---

### v2.6.16 — 24. März 2026 · Media-Delivery mit Range-Streaming

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.16** | 🟠 perf | Core/Media | **Byte-Range-Requests sauber unterstützt**: `CMS/core/Services/MediaDeliveryService.php` verarbeitet jetzt `206 Partial Content`, `416 Range Not Satisfiable`, `Accept-Ranges` und passendes `Content-Range`, damit größere Medien und Resume-/Preview-Clients nicht mehr auf einen Alles-oder-nichts-Download festgenagelt sind. |
| **2.6.16** | 🟠 perf | Core/Streaming | **Auslieferung streamt chunkweise**: Mediendateien werden nun über einen kontrollierten File-Handle in Chunks statt per `readfile()` in einem Rutsch ausgegeben; `HEAD`-Requests liefern die Header ohne Response-Body. |

---

### v2.6.15 — 24. März 2026 · Routing- und Admin-Hotspots verkleinert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.15** | 🟡 refactor | Core/Routing | **`ThemeRouter` delegiert Archivlogik**: Kategorie-/Tag-Archivdaten, Legacy-Tag-Normalisierung und veröffentlichte Archiv-Overviews liegen jetzt im neuen `ThemeArchiveRepository`, wodurch der große Routing-Pfad deutlich schmaler und gezielter testbar bleibt. |
| **2.6.15** | 🟡 refactor | Admin/Posts | **Kategorien-ViewModel ausgelagert**: `CMS/admin/modules/posts/PostsModule.php` nutzt für Kategorienäume, Optionslabels und Admin-Row-Metadaten jetzt den neuen `PostsCategoryViewModelBuilder`, statt diese Logik weiter direkt im Modul zu halten. |
| **2.6.15** | 🟡 refactor | Admin/Hub | **Template-Katalog separiert**: `CMS/admin/modules/hub/HubTemplateProfileManager.php` bezieht Template-Optionen, Presets und Default-Profile jetzt aus `HubTemplateProfileCatalog`; umfangreiche Inline-Kataloge und Default-Helfer sind damit aus dem Hotspot herausgezogen. |

---

### v2.6.14 — 23. März 2026 · Importer weiter zerlegt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.14** | 🟡 refactor | Plugins/Importer | **Importer-Preview und Reporting ausgelagert**: `CMS/plugins/cms-importer/includes/class-importer.php` delegiert Preview-/Planungslogik und Meta-/Reporting jetzt an `trait-importer-preview.php` und `trait-importer-reporting.php`, statt diese Blöcke weiter im Service-Monolithen zu halten. |
| **2.6.14** | 🟡 refactor | Plugins/Importer/Admin | **Admin-Cleanup aus Entry-Point gelöst**: `CMS/plugins/cms-importer/includes/class-admin.php` nutzt Cleanup-/Backfill-/Reporting-Helfer nun über `trait-admin-cleanup.php`; UI-Flows bleiben stabil, während die bislang sehr großen Bereinigungs- und Verlaufsroutinen separat wart- und testbarer werden. |

---

### v2.6.13 — 23. März 2026 · Global-Helper thematisch gesplittet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.13** | 🟡 refactor | Core/Bootstrap | **`includes/functions.php` auf Loader-Rolle reduziert**: `CMS/includes/functions.php` ist jetzt nur noch der kanonische Bootstrap für globale Helfer und lädt die bisherige Sammellogik thematisch getrennt aus `CMS/includes/functions/*.php` nach. |
| **2.6.13** | 🟡 refactor | Core/Helpers | **Helper-Gruppen getrennt wartbar**: Escaping/String-Helfer, Optionen/Archiv-/Runtime, Redirect/Auth, Rollen, Admin-Menüs, Übersetzungen, WP-Kompatibilität und Mail bleiben API-stabil, sind aber jetzt deutlich getrennt wart- und prüfbar. |

---

### v2.6.12 — 23. März 2026 · Installer-Monolith aufgespalten

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.12** | 🟡 refactor | Core/Installer | **`install.php` auf Bootstrap reduziert**: `CMS/install.php` delegiert den mehrstufigen Installer-Ablauf jetzt an einen dedizierten `InstallerController`, statt UI, Datenbank, Konfigurationsschreibzugriffe und Success-Flow weiter in einer Datei zu mischen. |
| **2.6.12** | 🟡 refactor | Core/Installer/Views | **Setup- und View-Logik sauber getrennt**: `InstallerService` kapselt Setup-, Lock-, Config-, Schema- und Datenbanklogik zentral, während die HTML-Schritte unter `CMS/install/views/` als getrennte Views gerendert werden. |

---

### v2.6.11 — 23. März 2026 · HTTPS-/HSTS-Linie vereinheitlicht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.11** | 🟡 refactor | Security/HTTPS | **Redirect-Verantwortung klargezogen**: Die HTTPS-Strategie ist jetzt verbindlich auf Redirects durch Reverse-Proxy/Webserver ausgerichtet; der ausgelieferte Apache-Fallback normalisiert nur noch Proxy-HTTPS für dieselbe Sicherheitslinie. |
| **2.6.11** | 🟡 refactor | Security/HSTS | **HSTS folgt zentraler HTTPS-Erkennung**: `Security` und die Systemdiagnose weisen die aktive Redirect-Verantwortung jetzt explizit aus und erzeugen HSTS nur noch über eine zentrale HTTPS-/HSTS-Konfiguration mit demselben HTTPS-Erkennungsmodell wie der Apache-Fallback. |
| **2.6.11** | 🔵 docs | Audit/Security | **Device-Cookie als offener Backlogpunkt dokumentiert**: Audit und ToDo führten zusätzlich einen neuen Security-Punkt für ein signiertes, kurzlebiges Login-/Device-Cookie mit, damit Browser-/Gerätebindung nicht als lose Randnotiz hängen bleibt. |

---

### v2.6.10 — 23. März 2026 · Updates atomar gemacht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.10** | 🔴 fix | Core/Updates | **Updates landen zuerst im Staging**: Core-Updates werden nicht mehr direkt in das Live-Ziel entpackt, sondern zuerst in ein benachbartes Staging-Verzeichnis extrahiert und erst danach per atomarem Verzeichnis-Swap oder rollback-fähigem Inhalts-Swap übernommen. |
| **2.6.10** | 🔴 fix | Core/Rollback | **Halbfertige Installationen verhindert**: Abgebrochene oder fehlschlagende Installationen hinterlassen keine inkonsistenten Update-Zustände mehr; bestehende Inhalte werden vor dem Umschalten in ein temporäres Backup verschoben und bei Fehlern wiederhergestellt. |

---

### v2.6.9 — 23. März 2026 · `session.cookie_secure` an HTTPS gekoppelt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.9** | 🔴 fix | Core/Sessions | **Secure-Flag nur noch bei echtem HTTPS**: `Security::startSession()`, `index.php` und `cron.php` setzen `session.cookie_secure` jetzt nur noch bei tatsächlich erkanntem HTTPS bzw. Proxy-HTTPS statt pauschal immer auf `1`. |
| **2.6.9** | 🔴 fix | Betrieb/Staging | **HTTP-Setups bleiben funktionsfähig**: HTTP-Staging-Setups und CLI-nahe Cron-Läufe verlieren damit nicht mehr unnötig ihre Session-Cookies durch eine erzwungene Secure-Flag auf Nicht-HTTPS-Anfragen. |

---

### v2.6.8 — 23. März 2026 · SSRF-DNS-Fallback gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.8** | 🔴 fix | Security/HTTP | **Ungelöste Remote-Hosts werden standardmäßig blockiert**: `CMS\Http\Client` versucht vorab eine echte IPv4/IPv6-Auflösung und lässt ungelöste Hosts nur noch per explizitem `allowUnresolvedHosts`-Opt-in zu. |
| **2.6.8** | 🔴 fix | Core/Updates | **`UpdateService` folgt derselben DNS-Härte**: Sensible Remote-Ziele werden bei fehlender Host-Auflösung nicht mehr stillschweigend durchgewunken. |

---

### v2.6.6 — 23. März 2026 · ZIP-Einträge vor `extractTo()` validiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.6** | 🔴 fix | Admin/System | **GitHub-Doku-Sync validiert ZIP-Inhalte vor dem Entpacken**: ZIP-Einträge werden jetzt vor `extractTo()` auf Traversals, absolute Pfade, NUL-/Steuerzeichen sowie leere oder punktbasierte Segmente geprüft. |

---

### v2.6.5 — 23. März 2026 · Debug-Logs aus Release-Baum herausgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.5** | 🔴 fix | Core/Logging | **Logs liegen standardmäßig außerhalb des Release-Baums**: Debug-Logs landen nicht mehr im `CMS/logs/`-Verzeichnis, sondern über `LOG_PATH`/`CMS_ERROR_LOG` in einem externen Logpfad; Konfig-Writer und `SystemService` nutzen denselben aktiven Pfad. |

---

### v2.6.4 — 23. März 2026 · Audit-Scope sauber konsolidiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.4** | 🔵 docs | Audit/Scope | **`FILEINVENTAR.md` als kanonische Quelle verankert**: Die Audit-Dokumentation nutzt `FILEINVENTAR.md` jetzt konsequent als Scope-Quelle; konkurrierende eingebettete Inventarstände und alte 444-Dateien-Referenzen wurden aus Audit und ToDo entfernt. |

---

### v2.6.3 — 23. März 2026 · Importer-Fetch auf Core-HTTP-Härtung umgestellt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.3** | 🔴 fix | Plugins/Importer | **Remote-Bilder laufen über den zentralen HTTP-Client**: Der WordPress-Importer lädt Remote-Bilder jetzt mit aktivierter TLS-Prüfung, SSRF-Schutz sowie Größen- und Image-Content-Type-Limits statt über einen ungehärteten Direkt-Fetch. |

---

### v2.6.2 — 23. März 2026 · Audit-Welle, SEO-Ausbau & Release-Abgleich

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.2** | 🟢 feat | Core/SEO | **IndexNow und Archivlogik erweitert**: Die SEO-Linie erweitert die IndexNow-Integration um eine dynamische Keydatei-Auslieferung; Kategorien und Tags unterstützen jetzt mehrsprachige Archivbasen sowie Ersatzkategorien/-tags beim Löschen. |
| **2.6.2** | 🟢 feat | Plugins/Importer | **Importer deutlich ausgebaut**: Der Core bringt einen erweiterten Importer unter `CMS/plugins/cms-importer` mit Meta-Report, Admin-Oberfläche, Styles, JavaScript und Importlogik für größere Importpfade mit. |
| **2.6.2** | 🟡 refactor | Release/Runtime | **`CMS\Version` wieder zentrale Release-Quelle**: Runtime, Installer, Update-Metadaten und sichtbare Versions-Badges wurden auf den konsistenten Stand `2.6.2` nachgezogen. |
| **2.6.2** | 🟡 refactor | Routing/Content | **Routing und Inhaltsauflösung nachgeschärft**: Kategorie-/Tag-Archive, Slug-Validierung in Seiten/Beiträgen sowie die allgemeine Inhaltsauflösung im Frontend wurden in mehreren Wellen weiter konsolidiert. |
| **2.6.2** | 🟡 refactor | Marketplace/Updates | **Update- und Marketplace-Pfade erweitert**: Theme-/Plugin-Verwaltung, Update-Ansichten und die zugrunde liegende `UpdateService`-Logik unterstützen den jüngsten Ausbauzustand deutlich umfangreicher als im Stand `2.6.1`. |
| **2.6.2** | 🟡 refactor | Member/Header | **Admin-Einstieg im Memberbereich eingeblendet**: Der Mitgliederbereich zeigt im Header jetzt gezielt einen Admin-Einstieg an, wenn der aktuelle Nutzer entsprechende Rechte besitzt. |
| **2.6.2** | 🔴 fix | Core/Installer | **Installer hart abgesichert**: `install.php` sperrt bestehende Installationen jetzt per Install-Lock und Admin-Guard für öffentliche Zugriffe; zusätzlich wird das Datenbank-Passwort im Reinstall-Pfad nicht mehr aus der vorhandenen Konfiguration vorbefüllt. |
| **2.6.2** | 🔴 fix | Routing/Archive | **Löschen von Kategorien/Tags robuster gemacht**: Das Löschverhalten bricht bei verknüpften Beiträgen nicht mehr stumpf weg, sondern kann Ziele auf Ersatzkategorien/-tags umlenken; Archiv- und Routingpfade verhalten sich in der mehrsprachigen CMS-Linie robuster. |
| **2.6.2** | 🔵 docs | Audit | **Neuer Audit-Stand 23.03.2026 dokumentiert**: `DOC/audit/AUDIT_23032026_CMS_PHINIT-LIVE.md` hält den CMS- und Live-Site-Prüfstand inklusive öffentlicher PhinIT-Stichprobe fest. |
| **2.6.2** | 🔵 docs | Audit/ToDo | **Nacharbeiten und Scope-Abdeckung nachgezogen**: `DOC/audit/NACHARBEIT_AUDIT_ToDo.md` sowie `DOC/audit/ToDo_Audit_23032026.md` dokumentieren den offenen Release-/Versionsabgleich, Proxy-/CDN-/Tracking-Verifikation und die vollständige First-Party-Dateiabdeckung explizit. |
| **2.6.2** | 🔵 docs | README/Betrieb | **Auditstatus direkt in README verankert**: `README.md` beschreibt den Auditstatus vom `23.03.2026` jetzt direkt im Betriebsabschnitt, damit offene Betriebs- und Sicherheitsbaustellen nicht nur im Audit-Ordner versteckt bleiben. |

---

### v2.6.1 — 17. März 2026 · Redirects, Theme-Polish & Frontend-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.1** | 🟡 refactor | SEO/Redirects | **Redirect- und `404`-Admin getrennt**: Der SEO-Admin trennt Weiterleitungen und erkannte `404` jetzt in zwei eigenständige Bereiche; neue Redirects lassen sich wieder direkt anlegen und Übernahmen aus dem `404`-Monitor können die passende Site-/Host-Zuordnung mitspeichern. |
| **2.6.1** | 🟡 refactor | Core/RedirectService | **Redirect-Regeln site-spezifisch bewertet**: `RedirectService` arbeitet jetzt host- bzw. pfadbezogen über `site_scope`, protokolliert den anfragenden Host in `404`-Logs mit und verhindert Dubletten nur noch innerhalb desselben Site-Scope statt global über alle Sites hinweg. |
| **2.6.1** | 🟡 refactor | Admin/Content | **Beiträge und Seiten teilen sich Kategorienbasis**: Zusätzlich werden Microsoft-365-Standardkategorien wie Copilot, Teams, SharePoint Online, Exchange Online, Intune, Defender oder Power Platform automatisch zur Auswahl vorgehalten. |
| **2.6.1** | 🟡 refactor | Theme/cms-phinit | **`cms-phinit` modernisiert Header und Assets**: Header, Dark-Mode-Init, Customizer-Logik, Analytics-Loader und Consent-Eventing laufen jetzt deutlich stärker über zentrale, cachebare Assets statt über Inline-Blöcke. |
| **2.6.1** | 🟡 refactor | Theme/365Network | **`365Network`-Customizer und Directory-Templates geglättet**: Admin-Customizer nutzt ausgelagerte Assets; Filter-Selects, Reset-/Listen-Stile und 404-Aktionen hängen an zentralen Klassen/Data-Attributen statt an Inline-Handlern. |
| **2.6.1** | 🔴 fix | Routing/Public | **`HEAD`-Requests für Public-Routen korrigiert**: Monitoring-, Header-Checks und SEO-Tools laufen für Pfade wie `/feed`, `/forgot-password` oder `/.well-known/security.txt` nicht mehr fälschlich in `404`. |
| **2.6.1** | 🔴 fix | Auth/Recovery | **Recovery-Seiten senden private Cache-Header**: Sensible Pfade wie `/forgot-password` verwenden jetzt dieselbe private/no-store-Cache-Strategie wie Login- und Registrierungsseiten. |
| **2.6.1** | 🔴 fix | Feed/RSS | **RSS-Descriptions liefern robusten Plaintext**: Editor.js-Inhalte werden nicht mehr als rohe oder abgeschnittene JSON-Blockpayloads an Feed-Reader gereicht; auch unvollständige JSON-Fragmente liefern wieder lesbaren Text. |
| **2.6.1** | 🔴 fix | Cron/Feeds | **`cms_cron_hourly` wird wieder wirklich ausgelöst**: `CMS/cron.php` stößt den bislang nur registrierten Hook kompatibel an und drosselt ihn intern auf höchstens einen echten Lauf pro Stunde, sodass `cms-feed`-Fetch-Queue und Feed-Digests wieder automatisch nachziehen. |
| **2.6.1** | 🔴 fix | Plugins/cms-contact | **Verbleibende Admin-Views auf zentrale Assets umgestellt**: Filter, Template-Auswahl, Modale, Statuswechsel und Sammelaktionen bleiben funktional, kommen aber ohne zusätzliche Inline-Styles/-Scripts aus. |
| **2.6.1** | 🔴 fix | Plugins/cms-feed | **Feed-Pfade und Admin-UI inline-frei gemacht**: Public-JavaScript lädt jetzt auf allen echten Feed-Routen inklusive Consent-Sperrseite, und der große Admin-View `page-admin.php` kommt ohne direkte `onclick`-/`confirm`-Handler oder `javascript:void(0)`-Links aus. |
| **2.6.1** | 🔴 fix | Plugins/cms-events | **Admin-, Meta-Box-, Member- und Kalenderpfade entinline-ifiziert**: Bestätigungen, Modalsteuerung, Preview-Syncs, Formular-Toggles und Monatsnavigation hängen nun an zentralen Assets bzw. echten Navigationslinks. |
| **2.6.1** | 🔴 fix | Theme/cms-phinit | **Customizer und Tracking ohne Inline-Skripte**: Font-Preview-Styles, Analytics-Loader sowie verbleibende `onclick`-/`oninput`-Handler wurden in zentrale Admin-/Theme-Assets überführt. |
| **2.6.1** | 🔴 fix | Admin/Bulk-Editing | **Bulk-Bearbeitung für Seiten und Beiträge erweitert**: Kategorien lassen sich jetzt setzen oder entfernen; Seiten unterstützen erstmals auch eine eigene Einzelbearbeitung per Kategorieauswahl und Listenfilter. |
| **2.6.1** | 🟢 feat | Core/Routing | **`security.txt` unter zwei Standardpfaden verfügbar**: `ThemeRouter` liefert jetzt `security.txt` sowohl unter `/security.txt` als auch unter `/.well-known/security.txt` mit Kontakt, Canonical, Sprachenhinweis und Ablaufdatum aus. |
| **2.6.1** | 🔵 docs | Audit/Theme/Security | **Doku auf PhinIT-Live-Nacharbeit nachgezogen**: Audit-, Sicherheits- und Theme-Dokumentation spiegeln jetzt `security.txt`, Forgot-Password-Recovery, Feed-Härtung, Redirect-/404-Admin, site-spezifische Redirect-Scopes, Tabellen-Darkmode und bereinigte `cms-contact`-Views wider. |

---

### v2.6.0 — 16. März 2026 · Permalink-, Error-Report- und Redaktionsausbau

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.6.0** | 🟢 feat | Core/Routing | **`PermalinkService` zentralisiert Beitrags-URL-Strukturen**: Slug-Extraktion, URL-Schemata und Migrationspfade für beitragsbezogene Router- und Theme-Pfade laufen jetzt über einen dedizierten Service. |
| **2.6.0** | 🟢 feat | Admin/Error-Reporting | **Persistente Admin-Fehlerreports eingeführt**: `ErrorReportService` und `/admin/error-report` führen Audit-Log, Kontextdaten und einen CSRF-geschützten Redirect-Flow für nachvollziehbare Fehlerreports ein. |
| **2.6.0** | 🟢 feat | Admin/Editorial | **Neue Redaktions-Einstiege ergänzt**: Eigenständige CRUD-Ansichten für Beitrags-Kategorien, Beitrags-Tags und Tabellen-Display-Defaults erweitern den Redaktionsbereich. |
| **2.6.0** | 🟡 refactor | Theme/Rendering | **Theme-Dateien rendern in isoliertem Scope**: Werte aus einem Render-Kontext sickern nicht mehr unbeabsichtigt in andere Templates durch. |
| **2.6.0** | 🟡 refactor | Routing/Schema | **Archiv-, Sitemap- und Hub-Pfade erweitert**: Routing-, Redirect- und Hub-/Schema-Pfade wurden für Archiv- und Sitemap-Routen, URL-Nachmigrationen und robustere Flag-Verwaltung in `SchemaManager` und `MigrationManager` nachgeschärft. |
| **2.6.0** | 🔴 fix | Kommentare/Admin-JSON | **Kommentar- und Admin-JSON-Pfade stabilisiert**: Eingeloggte Nutzer füllen Kommentarformulare zuverlässiger vor, Moderation meldet Erfolg verlässlich zurück und Admin-/AJAX-Endpunkte für Posts, Seiten, Nutzer und Medien reagieren konsistenter. |

### v2.5.30 — 11. März 2026 · Standard-Theme-Home-Split, Partials & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.30** | 🟡 refactor | Theme/Standard-Theme | **Startseiten-Orchestrator drastisch verkleinert**: `CMS/themes/cms-default/home.php` lädt nur noch Daten und delegiert anschließend an die spezialisierten Partials `partials/home-landing.php` und `partials/home-blog.php`. |
| **2.5.30** | 🟡 refactor | Theme/Frontend | **Landing- und Blog-Markup sauber getrennt**: Die frühere Mischdatei wurde in `partials/home-landing.php` (Landing-Logik, CTA, Footer-Callout) und `partials/home-blog.php` (Hero, Listen, Sidebar) aufgeteilt, wodurch Theme-Anpassungen deutlich lokalere Änderungen erlauben. |
| **2.5.30** | 🟢 feat | Core/Quality Gates | **Architektur-Suite bestätigt den Theme-Split**: `php tests/architecture/run.php` läuft nach dem Split erfolgreich durch; `home.php` liegt jetzt bei 131 LOC statt als weiterer großer Theme-Monolith im Laufzeitpfad zu bleiben. |
| **2.5.30** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Standard-Homepage gilt nicht mehr als dominanter Restblock; `AUDIT_FACHBEREICHE.md`, `AUDIT_BEWERTUNG.md` und `AUDIT_09032026.md` verschieben den Restdruck nun stärker auf große CSS-/Admin-Dateien und Proxy-/CDN-Realvalidierung. |



### v2.5.29 — 11. März 2026 · Release-Smoke-Disziplin, Beta-Pflichtpfade & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.29** | 🟢 feat | Core/Quality Gates | **Verbindliche Release-Smoke-Suite ergänzt**: `tests/release-smoke/manifest.php` und `tests/release-smoke/run.php` halten jetzt Public-, Auth-, Member-, Admin- und Fehlpfade inklusive historischer Retests als reproduzierbaren Repo-Standard fest. |
| **2.5.29** | ⬜ chore | CI/Release | **CI prüft die Release-Disziplin automatisch mit**: `.github/workflows/security-regression.yml` führt die neue Release-Smoke-Suite jetzt zusammen mit Security-, Architektur-, Vendor- und Doku-Sync-Checks aus. |
| **2.5.29** | 🔵 docs | Workflow/Release | **Deployment-Leitfaden enthält jetzt feste Beta-Stichprobe**: `DOC/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md` definiert eine verbindliche Phase „Beta-Smoke nach Deployment“ mit Pflichtbefehl `php tests/release-smoke/run.php`, Pflichtpfaden und zusätzlichen Browser-/Log-Prüfungen. |
| **2.5.29** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Beta-Smoke-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` hebt die Betriebs-/Release-Reife an und der nächste offene Schwerpunkt verschiebt sich auf große Theme-/Admin-Dateien sowie Proxy-/CDN-Realvalidierung. |

---

### v2.5.28 — 11. März 2026 · Marketplace-Integrität, SHA-256-Gates & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.28** | 🔴 fix | Marketplace/Supply Chain | **Auto-Installationen erzwingen jetzt Integritätsmetadaten**: `PluginMarketplaceModule` und `ThemeMarketplaceModule` erlauben automatische Installationen nur noch bei vorhandener Paket-URL, erlaubtem Zielhost und gültiger SHA-256-Prüfsumme statt allein aufgrund eines Download-Links. |
| **2.5.28** | 🔴 fix | Marketplace/Updates | **ZIP-Downloads werden vor dem Entpacken aktiv verifiziert**: Marketplace-Pakete werden über `UpdateService::verifyDownloadIntegrity()` gegen ihre erwartete Prüfsumme geprüft; bei fehlender oder falscher SHA-256 wird die Installation sauber abgebrochen. |
| **2.5.28** | 🎨 style | Admin/Marketplace | **Marketplace-UI trennt verifizierte von manuellen Paketen sichtbar**: Plugin- und Theme-Ansichten zeigen jetzt explizite Prüfsummen-/Warnhinweise und markieren Einträge ohne Integritätsmetadaten als „Nur manuell“, statt ihnen still denselben Installationspfad zu geben. |
| **2.5.28** | 🟢 feat | Core/Quality Gates | **Security-Suite sichert SHA-256-Gates regressionsseitig ab**: `tests/security/run.php` prüft jetzt zusätzlich, dass Plugin- und Theme-Marketplace Auto-Installationen ohne gültige 64-stellige SHA-256 nicht freigeben. |
| **2.5.28** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Marketplace-/Supply-Chain-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` hält die Integritätsprüfung mit **99/100 Punkten** fest und der nächste offene Schwerpunkt rückt auf feste Beta-Smoke-Disziplin sowie verbleibende Proxy-/CDN-Realtests. |

---

### v2.5.27 — 11. März 2026 · Bootstrap-Profil-Messung, Cold-Path-Transparenz & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.27** | 🟠 perf | Core/Bootstrap | **Cold-Path-Profil startet jetzt vor dem Dependency-Load**: `Debug::resetRuntimeProfile()` läuft bereits vor `loadDependencies()`, sodass Dependency-Load, Plattformprüfung und Migrationslauf erstmals sauber in der Bootstrap-Zeit landen statt unsichtbar vor dem Messstart zu verschwinden. |
| **2.5.27** | 🟢 feat | Admin/System | **Diagnose zeigt aktives Bootstrap-Profil pro Modus**: `/admin/diagnose` wertet das neue Profil jetzt als eigene Ansicht mit Modus, Kaltstart bis `bootstrap.ready`, Post-Bootstrap-Zeit, Cold-Path-Anteil und teuersten Bootstrap-Phasen für CLI/API/Admin/Web aus – auch ohne aktiviertes `CMS_DEBUG`. |
| **2.5.27** | 🟢 feat | Core/Quality Gates | **Runtime- und Architektur-Suiten sichern Profilierung ab**: `tests/runtime-telemetry/run.php` prüft jetzt das leichtgewichtige Bootstrap-Profil explizit auch ohne Debug-Modus, und `tests/architecture/run.php` hält frühe Messung sowie Diagnose-Sichtbarkeit regressionsseitig fest. |
| **2.5.27** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die offene Messwelle für Bootstrap-Profile gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **99/100 Punkte** und der nächste offene Schwerpunkt rückt auf Marketplace-/Supply-Chain-Härtung sowie Proxy-/CDN-Realtests. |

---

### v2.5.26 — 11. März 2026 · Registry-Diagnose, Bundle-Transparenz & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.26** | 🟢 feat | Core/Diagnose | **`VendorRegistry` als Diagnosequelle erweitert**: `getDiagnostics()` exportiert jetzt Assets-Autoloader-Kandidaten, registrierte Produktivpakete, gebündelte Runtime-Libraries und die Symfony-Manifest-/PHP-Plattformprüfung zentral statt diese Informationen nur implizit in Bootstrap- und Servicepfaden zu verstecken. |
| **2.5.26** | 🟢 feat | Admin/System | **Diagnose macht Registry-/Bundle-Status sichtbar**: `SystemInfoModule` speist die Registry-Daten in `/admin/diagnose` ein; `admin/views/system/diagnose.php` zeigt Autoloader, Produktivpakete, Asset-Bundles und Plattformwarnungen direkt im Admin an. |
| **2.5.26** | 🟢 feat | Core/Quality Gates | **Architekturregel für Diagnose-Sichtbarkeit ergänzt**: `tests/architecture/run.php` prüft jetzt regressionsseitig, dass `VendorRegistry`, `SystemInfoModule` und die Diagnose-View die Vendor-/Asset-Registry weiterhin sichtbar anbinden. |
| **2.5.26** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Registry-/Dependency-/Asset-Diagnosewelle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **98/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf Marketplace-/Supply-Chain-Härtung sowie Proxy-/CDN-Realtests. |

---

### v2.5.25 — 11. März 2026 · Layout-Shell-Reuse, Subnav-Zentralisierung & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.25** | 🟡 refactor | Admin/Layout | **Gemeinsame Admin-Section-Shell eingeführt**: `admin/partials/section-page-shell.php` bündelt den gemeinsamen Auth-/CSRF-/Alert-/Render-Ablauf für `performance-page.php`, `member-dashboard-page.php` und `system-monitor-page.php`, statt diese Wrapper separat mit nahezu identischem Seiten-Skelett zu pflegen. |
| **2.5.25** | 🟡 refactor | Admin/Views | **Button-Subnav zentralisiert**: `admin/views/partials/section-subnav.php` rendert jetzt die wiederkehrende Navigation für Performance-, Member- und System-Unterseiten; die drei bisherigen Subnav-Partials liefern nur noch ihre Konfiguration statt eigenes Markup. |
| **2.5.25** | 🟢 feat | Core/Quality Gates | **Architekturregel für Layout-Reuse ergänzt**: `tests/architecture/run.php` prüft jetzt regressionsseitig, dass die zentralen Section-Seiten und Subnavs die gemeinsamen Layout-Bausteine weiterverwenden. |
| **2.5.25** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Layout-/Shell-Wiederverwendungswelle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **97/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf Registry-/Diagnose-Ausbau, Marketplace-Härtung und echte Proxy-/CDN-Realtests. |

---

### v2.5.24 — 10. März 2026 · Content-/Hub-View-Glättung, Asset-Split & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.24** | 🟡 refactor | Admin/Views | **Seiten-, Beitrags- und Hub-Editoren entlastet**: `admin/views/pages/edit.php`, `admin/views/posts/edit.php` und `admin/views/hub/edit.php` liefern ihre Bedienlogik nicht mehr als große Inline-Skriptblöcke, sondern nur noch als Konfiguration + Markup für zentrale Admin-Assets. |
| **2.5.24** | 🟢 feat | Core/Quality Gates | **Zentrale Admin-Assets + Architekturregel ergänzt**: `admin-content-editor.js` und `admin-hub-site-edit.js` bündeln die Editor-/Hub-Interaktionen, während `tests/architecture/run.php` regressionsseitig erzwingt, dass diese Views inline-scriptfrei bleiben und die Entry-Points die Assets weiter anbinden. |
| **2.5.24** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Hub-/Content-View-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **96/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf Layout-/Shell-Wiederverwendung, Registry-/Diagnose-Ausbau und Proxy-/CDN-Realtests. |

---

### v2.5.23 — 10. März 2026 · Legacy-Cache-Pfade, Header-Härtung & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.23** | 🔴 fix | Performance/Headers | **Standalone-Entry-Points an zentrale Cache-Policy angeglichen**: `config.php`, `install.php`, `cron.php` und der Installer-Redirect in `orders.php` senden jetzt ebenfalls private/no-store-Header über `CacheManager::sendResponseHeaders('private')`. |
| **2.5.23** | 🟢 feat | Core/Quality Gates | **Architektur-Suite überwacht Legacy-Header mit**: `tests/architecture/run.php` prüft jetzt zusätzlich, dass die verbliebenen Standalone-Entry-Points ihre privaten Cache-Header nicht wieder verlieren. |
| **2.5.23** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Cache-/Legacy-Randpfad-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **95/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf Hub-/Content-Views, Registry-/Diagnose-Ausbau und Proxy-/CDN-Realtests. |

---

### v2.5.22 — 10. März 2026 · Host-Allowlisten, Remote-Härtung & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.22** | 🔴 fix | Security/Remote | **Sensible Remote-Ziele enger begrenzt**: `UpdateService`, `PluginMarketplaceModule`, `ThemeMarketplaceModule` und `DocumentationSyncDownloader` akzeptieren für Update-, Marketplace- und Doku-Downloads jetzt nur noch explizite Zielhosts wie `365network.de`, GitHub-, GitHubusercontent- und Codeload-Ziele. |
| **2.5.22** | 🟢 feat | Core/Quality Gates | **Security-Suite um Host-Allowlists erweitert**: `tests/security/run.php` prüft jetzt zusätzlich, dass fremde Update-, Marketplace- und Doku-Hosts blockiert werden, während legitime Zielräume funktionsfähig bleiben. |
| **2.5.22** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Host-Allowlist-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **94/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf restliche Cache-/Legacy-Randpfade sowie Supply-Chain-Feinschliff. |

---

### v2.5.21 — 10. März 2026 · Vendor-Netzwerkmonitoring, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.21** | 🟢 feat | Core/Quality Gates | **Vendor-/Drittpfad-Netzwerkmonitor ergänzt**: `tests/vendor-network-monitoring/run.php` prüft bekannte Remote-Primitiven in `CMS/assets/` und `CMS/vendor/` jetzt gegen eine explizite Allowlist und macht neue Drittpfade sofort sichtbar. |
| **2.5.21** | ⬜ chore | CI/Monitoring | **Security-Workflow erweitert**: `.github/workflows/security-regression.yml` führt den Vendor-Monitor jetzt automatisch mit aus; `DOC/assets/VENDOR-NETWORK-PATHS.md` dokumentiert die beobachteten Drittpfade getrennt vom Eigencode. |
| **2.5.21** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Vendor-/Drittpfad-Monitoring gilt als erledigter Restblock, `AUDIT_BEWERTUNG.md` steigt auf **93/100 Punkte** und der nächste offene Schwerpunkt liegt klar auf engen Host-Allowlisten für sensible Remote-Ziele. |

---

### v2.5.20 — 10. März 2026 · Security-Regressionssuite, ZIP-Pakethärtung & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.20** | 🔴 fix | Marketplace/Updates | **ZIP-Pakete gegen Pfad-Traversal gehärtet**: `PluginMarketplaceModule`, `ThemeMarketplaceModule` und `UpdateService` validieren Archiv-Einträge jetzt vor dem Entpacken und blockieren unsichere `../`- bzw. absolute Pfade, bevor ein Paket ins Dateisystem greifen darf. |
| **2.5.20** | 🟢 feat | Core/Quality Gates | **Security-Suite deutlich verbreitert**: `tests/security/run.php` prüft jetzt zusätzlich GitHub-API-Host-Disziplin, localhost-Blockaden für Remote-Ziele sowie ZIP-Traversal in Marketplace-/Update-Paketen. |
| **2.5.20** | 🔵 docs | Audit/Release | **Audit-Bewertung und Nacharbeitsstand nachgezogen**: Die Security-Regressionssuite gilt als abgearbeiteter Restblock, `AUDIT_BEWERTUNG.md` steigt auf **92/100 Punkte** und die verbleibenden offenen Themen fokussieren sich jetzt stärker auf Vendor-/Allowlist-/Legacy-Ränder. |

---

### v2.5.19 — 10. März 2026 · Sonderpfad-Härtung, Audit-Konsolidierung & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.19** | 🔴 fix | Core/Admin I/O | **Verbleibende Sonderpfade explizit gehärtet**: `FeedService`, `ElfinderService`, `PurifierService`, `ImageProcessor`, `SeoSitemapService`, `FontManagerModule` und `PerformanceModule` behandeln Datei-, Temp-, GD- und Verzeichnisfehler jetzt explizit statt sie still per `@...` wegzudrücken. |
| **2.5.19** | 🟢 feat | Core/Quality Gates | **Security-Regression für ungültige Bildpfade ergänzt**: `tests/security/run.php` prüft zusätzlich, dass `ImageProcessor` kaputte Bilddateien sauber als `WP_Error` zurückweist. |
| **2.5.19** | 🔵 docs | Audit/Release | **Audit-Berichte auf sechs Kern-Dateien konsolidiert**: Die früheren Einzelberichte für Core, Feature, Performance und Security wurden in `DOC/audit/AUDIT_FACHBEREICHE.md` zusammengeführt; historische Testblocker sind jetzt in `AUDIT_TESTS_ToDo.md` konsolidiert und die Release-/Bewertungsdoku spiegelt den Stand mit **91/100 Punkten** wider. |

---

### v2.5.18 — 10. März 2026 · Media-Delivery-Härtung, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.18** | 🟡 refactor | Core/Media | **Private Medienauslieferung zentralisiert**: `MediaDeliveryService` bündelt jetzt die kontrollierte Auslieferung privater Member-Dateien über `GET /media-file`, normalisiert lokale Upload-URLs delivery-aware und versorgt Preview-/Download-Pfade mit passender Cache-Policy, `Last-Modified` und Rollen-/Owner-Prüfung. |
| **2.5.18** | 🔴 fix | Uploads/UX+Security | **Upload-Auslieferung sauber balanciert**: `MediaService::syncUploadsProtection()` hält Attachment + `nosniff` weiter als Standard, erlaubt sichere Bildtypen aber gezielt wieder inline; Media-Library, EditorJS sowie Featured-Image-Previews nutzen dafür jetzt delivery-aware Preview-/Access-URLs statt roher Upload-Links. |
| **2.5.18** | 🟢 feat | Core/Quality Gates | **Neue Media-Delivery-Regression ergänzt**: `tests/media-delivery/run.php` prüft Route, URL-Normalisierung, Member-Schutz und `.htaccess`-Header-Strategie; der Security-Workflow führt die Suite jetzt automatisch mit aus. |
| **2.5.18** | 🔵 docs | Audit/Release | **Audit- und Release-Spiegel nachgezogen**: Nacharbeitsliste, Fach-Audits, Bewertungsmatrix, Test-Checkliste, Changelog und Versions-Fallbacks spiegeln die abgeschlossene Medien-/Bild-/Proxy-Härtung jetzt konsistent wider. |

---

### v2.5.17 — 10. März 2026 · DocumentationSyncService-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.17** | 🟡 refactor | Admin/System | **`DocumentationSyncService` weiter entschärft**: Der frühere 545-LOC-Doku-Sync-Block ist jetzt ein schlanker Orchestrator (`71 LOC`) über `DocumentationSyncEnvironment`, `DocumentationSyncFilesystem`, `DocumentationSyncDownloader`, `DocumentationGitSync` und `DocumentationGithubZipSync`; Environment-Probing, Git-Sync, GitHub-ZIP-Download und Dateisystem-Swap sind sauber getrennt. |
| **2.5.17** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für den Doku-Sync**: `tests/documentation-sync-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und der frühere Sync-Monolith bleibt damit unter Dauerbeobachtung. |
| **2.5.17** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten, Audit-Spiegel und Core-Konstanten wurden auf `2.5.17` angehoben. |

---

### v2.5.16 — 10. März 2026 · media-proxy-Abbau, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.16** | 🟡 refactor | Core/Routing | **`media-proxy.php` vollständig abgebaut**: Die physische Legacy-Datei wurde entfernt; `GET /media-proxy.php` leitet jetzt zentral über `PublicRouter` auf `/member/media`, `POST /media-proxy.php` delegiert an `FileUploadService::handleUploadRequest()` und die Apache-Sonderbehandlung in `.htaccess` ist verschwunden. |
| **2.5.16** | 🟢 feat | Core/Quality Gates | **Neue Regression für den Legacy-Abbau**: `tests/media-proxy/run.php` prüft das Entfernen der Datei, die zentrale Router-Übernahme und den fehlenden Apache-Bypass; der Workflow führt den Check automatisch mit aus. |
| **2.5.16** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.16` angehoben. |

---

### v2.5.15 — 10. März 2026 · EditorJsMedia-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.15** | 🟡 refactor | Core/EditorJs | **`EditorJsMediaService` weiter entschärft**: Der frühere 545-LOC-Medienkern ist jetzt ein schlanker Orchestrator (`87 LOC`) über `EditorJsRequestGuard`, `EditorJsUploadService`, `EditorJsRemoteMediaService` und `EditorJsImageLibraryService`; Guard-, Upload-, Remote-Fetch- und Bibliothekslogik sind sauber getrennt. |
| **2.5.15** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für EditorJs-Media**: `tests/editorjs-media-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und der EditorJs-Medienpfad bleibt damit dauerhaft unter Monolithenaufsicht. |
| **2.5.15** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.15` angehoben. |

---

### v2.5.14 — 10. März 2026 · LandingSection-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.14** | 🟡 refactor | Core/Landing | **`LandingSectionService` weiter entschärft**: Der frühere 674-LOC-Landing-Kern ist jetzt ein schlanker Orchestrator (`129 LOC`) über `LandingDefaultsProvider`, `LandingHeaderService`, `LandingFeatureService` und `LandingSectionProfileService`; Defaults, Header/Farben, Feature-Migrationen sowie Footer-/Content-/Settings-/Design-Logik sind sauber getrennt. |
| **2.5.14** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für Landing-Sections**: `tests/landing-section-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und der Landing-Kern bleibt damit dauerhaft unter Monolith-Verdacht statt wieder darunter begraben zu werden. |
| **2.5.14** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.14` angehoben. |

---

### v2.5.13 — 10. März 2026 · SeoMeta-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.13** | 🟡 refactor | Core/SEO | **`SeoMetaService` weiter entschärft**: Der frühere 678-LOC-Meta-Kern ist jetzt ein schlanker Orchestrator (`89 LOC`) über `SeoSettingsStore`, `SeoMetaRepository`, `SeoSchemaRenderer`, `SeoAnalyticsRenderer` und `SeoHeadRenderer`; Settings, Persistenz, Schema, Analytics und Head-Rendering sind sauber getrennt. |
| **2.5.13** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für SEO-Meta**: `tests/seo-meta-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und die generische Architektur-Suite grandfathert `SeoMetaService.php` nicht länger als Ausnahme. |
| **2.5.13** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.13` angehoben. |

---

### v2.5.12 — 10. März 2026 · SiteTable-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.12** | 🟡 refactor | Core/SiteTable | **`SiteTableService` konsequent entschärft**: Der frühere 1065-LOC-Großservice ist jetzt ein schlanker Orchestrator (`128 LOC`) über `SiteTableRepository`, `SiteTableTemplateRegistry`, `SiteTableHubRenderer` und `SiteTableTableRenderer`; Rendering-, Persistenz-, Hub- und Exportlogik sind sauberer voneinander getrennt. |
| **2.5.12** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für SiteTable**: `tests/site-table-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und die generische Architektur-Suite grandfathert `SiteTableService.php` nicht länger als Ausnahme. |
| **2.5.12** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.12` angehoben; nebenbei wurde auch ein veralteter `2.5.4`-Fallback im API-Router beseitigt. |

---

### v2.5.11 — 10. März 2026 · Audit-Härtung, Runtime-Fixes & Release-Stabilisierung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.11** | 🔴 fix | Core/Auth+Security | **Mehrfachformular-CSRF und Login-Fehlerpfade stabilisiert**: wiederverwendbare CSRF-Tokens für identische Actions verhindern falsche Ablehnungen auf Multi-Form-Seiten; Login-/Passkey-Logging ruft `cms_log()` nun mit korrekter Signatur auf. |
| **2.5.11** | 🔴 fix | Member/Media | **Member-Medienpfade und Seitenbild-Uploads gehärtet**: persönliche Upload-Basispfade werden bei Bedarf automatisch erzeugt, und Upload-Metadaten greifen korrekt auf `Auth::getCurrentUser()` statt auf eine nicht existierende Methode zu. |
| **2.5.11** | 🔴 fix | Frontend/Kommentare | **Kommentarfluss Ende-zu-Ende repariert**: `POST /comments/post` ist sauber im Public-Router registriert, Frontend-Kommentare landen wieder in der Moderation, die Admin-Einzelfreigabe sendet nun den korrekten Status `approved`, und das Aktionsmenü scrollt nicht mehr im Tabellen-Overflow fest. |
| **2.5.11** | 🔴 fix | Admin/System+Users | **Mehrere produktive Testblocker beseitigt**: TOC-Speichern (`HY093`), Gruppenverwaltung (`execute()` statt falschem `query()`), Benutzeranlage ohne `CMS\Services\is_wp_error()`-Fatal sowie fehlendes `site_tables`-Schema inklusive Runtime-Nachzug sind bereinigt. |
| **2.5.11** | 🔴 fix | Admin/Runtime | **Leere und fatale Admin-/Theme-Pfade bereinigt**: Sidebar-/Dashboard-Nullwerte, 404-Headerwarnungen sowie früher leere Admin-Views wie `redirect-manager`, `mail-settings`, `updates` und `documentation` rendern wieder robust und kontextsicher. |
| **2.5.11** | 🟡 refactor | Core/Architektur | **Audit-Refactor-Welle umgesetzt**: Router-, Media-, SEO-, Landing-, EditorJs-, Hub-, Theme-Customizer-, Theme-Functions- und zuletzt Documentation-Module wurden weiter in kleinere Verantwortungsbereiche zerlegt; `DocumentationModule` delegiert nun an Katalog-, Render- und Sync-Services statt selbst alles zu schleppen. |
| **2.5.11** | 🟠 perf | Core/Performance | **Bootstrap-, Cache- und Diagnosepfade ausgebaut**: proxy-freundliche Cache-Header (`s-maxage`, `stale-if-error`, `Surrogate-Control`), robustes `Vary`-Merging, OPcache-Warmup der 30 größten PHP-Dateien, echte Core-Web-Vitals-Felddaten sowie Debug-Runtime-/Query-Telemetrie verbessern Messbarkeit und Kaltstartverhalten. |
| **2.5.11** | 🟢 feat | Core/Observability | **Nutzungs- und Runtime-Metriken erweitert**: Admin-/Member-Funktionsnutzung wird datensparsam erfasst, SEO-Analytics zeigt echte Feature-Nutzung, und die Diagnoseschiene liefert Query-Zähler, langsame SQLs und Runtime-Checkpoints. |
| **2.5.11** | 🟢 feat | Core/Quality Gates | **Regressions- und Architekturtests deutlich verbreitert**: neue Checks für Architekturregeln, Contract-Grenzen, HTTP-Cache-Profile, Runtime-Telemetrie, Rollen/Capabilities, Router-Fallbacks, Admin-View-Guards, Medien-Defaults und Kommentarstatus laufen jetzt reproduzierbar im Workflow mit. |
| **2.5.11** | 🎨 style | Admin/UX | **Wiederkehrende Admin-Muster vereinheitlicht**: Flash-Alerts, leere Tabellenzustände und mehrere Liste-/Moderationsansichten verhalten sich konsistenter, robuster und weniger „Überraschungsparty im Backoffice“. |
| **2.5.11** | 🔵 docs | Audit/Release | **Audit-Stand konsolidiert**: Audit-Berichte, Nacharbeitslisten und Release-Doku spiegeln jetzt den gehärteten Stand mit **88/100 Punkten** Gesamtbewertung, deutlich robusterer Release-Basis und klarerem Rest-Backlog. |
| **2.5.11** | ⬜ chore | Versionierung | **CMS-Versionlinie angehoben**: Core-Konstanten, Installer, Update-Metadaten, API-/Dashboard-Fallbacks, Landing-Defaults und Changelog wurden auf `2.5.11` synchronisiert. |

---

### v2.5.4 — 08. März 2026 · Sitemap-Live-Fixes, SEO-Admin-Härtung & Doku-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.4** | 🔴 fix | Core/Sitemap | **Live-Sitemap-Generierung auf Webservern gehärtet**: Die eingebundene `melbahja/seo`-Sitemap-Engine wurde für den Web-Kontext abgesichert, sodass kein undefiniertes `STDOUT` mehr die Generierung von `sitemap.xml`, `pages.xml`, `posts.xml`, `images.xml` oder `news.xml` blockiert. |
| **2.5.4** | 🔴 fix | Admin/SEO | **CSRF-Token-Flow im SEO-Subnav stabilisiert**: Globale Aktionen wie „Sitemaps generieren“ und „robots.txt schreiben“ verwenden jetzt denselben gültigen `admin_seo_suite`-Token wie die Zielseite und erzeugen keine versehentlichen „Sicherheitstoken ungültig.“-Fehler mehr. |
| **2.5.4** | 🔴 fix | Auth/Passkeys | **Passkey-Schema dauerhaft integriert**: `passkey_credentials` ist jetzt offizieller Bestandteil von `SchemaManager` und `MigrationManager`; neue Installationen und bestehende Deployments erhalten die WebAuthn-Tabelle regulär, und fehlende Passkey-Migrationen reißen die Member-Sicherheitsseite nicht mehr in einen Fatal Error. |
| **2.5.4** | 🔴 fix | SchemaManager | **fehlende Tabellen ergänzt**: `cms_favorites` Tabelle (v15→v16) und `cms_security_log` Tabelle in SchemaManager ergänzt (v16→v17) |

---

### v2.5.3 — 08. März 2026 · melbahja/seo integriert, Sitemaps modularisiert, Admin ausgebaut

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.3** | 🟢 feat | Core/SEO | **`melbahja/seo` produktiv integriert**: Das lokale Asset-Bundle unter `CMS/assets/melbahja-seo/` ist jetzt per Autoloader eingebunden; `SEOService` rendert Schema.org über `Melbahja\Seo\Schema` und `Thing` statt über manuell gebaute JSON-LD-Strings. |
| **2.5.3** | 🟢 feat | Core/Sitemap | **Sitemap-Architektur modularisiert**: Neuer `SitemapService` erzeugt `pages.xml`, `posts.xml`, `images.xml`, `news.xml` und den Index `sitemap.xml` im sicheren TEMP-Modus; ergänzend steuert `IndexingService` IndexNow- und Google-Submissions. |
| **2.5.3** | 🎨 style | Admin/SEO | **SEO-Adminbereich erweitert**: Die Sitemap-/Schema-Ansichten zeigen jetzt den neuen Bundle-Status, die modulare Dateistruktur, News-Defaults sowie Formulare für manuelle URL-Submissions an IndexNow und Google. |
| **2.5.3** | 🔵 docs | Release | **Versionierung nachgezogen**: Changelog, `CMS/update.json` und die Core-Versionskonstante wurden auf den neuen Stand der SEO-Migration synchronisiert. |

---

### v2.5.2 — 08. März 2026 · Asset-Cleanup finalisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.2** | ⬜ chore | Assets/Cleanup | **Runtime-Bereinigung**: Die ungenutzte Reserve-Library `schema-org/` sowie sämtliche ungenutzten Sub-Libs unter `CMS/assets/tabler/libs/` wurden endgültig aus dem Runtime-Baum entfernt. |
| **2.5.2** | 🔵 docs | Docs/Assets | **Asset-Dokumentation auf Löschstand synchronisiert**: `DOC/ASSET.md`, `DOC/ASSET_OUTDATET.md` und die Bundle-Referenzen dokumentieren jetzt den bereinigten Ist-Zustand ohne `schema-org/` und ohne `tabler/libs/`. |
| **2.5.2** | 🔴 fix | Assets/Autoload | **Autoloader nach Bereinigung konsistent gehalten**: Verweise auf entfernte Bundles wurden aus `CMS/assets/autoload.php` entfernt, während die FilePond-Locales bewusst unangetastet blieben. |

---

### v2.5.1 — 08. März 2026 · Asset-Inventar & Bundle-Doku konsolidiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.1** | 🔵 docs | Docs/Assets | **Asset-Inventar vollständig neu abgeglichen**: Die Runtime-Nutzung von `CMS/assets/` wurde systematisch geprüft und in `DOC/ASSET.md` mit aktiven, transitiven und reservierten Bundles sauber nachgezogen. |
| **2.5.1** | 🔵 docs | Docs/Bundles | **Bundle-Dokumentation vereinheitlicht**: Neue bzw. überarbeitete Detaildokus für `mailer/`, `mime/`, `psr/` und offene Migrationshinweise wie `melbahja-seo/` wurden im Doku-Baum verankert. |
| **2.5.1** | ⬜ chore | Assets/Autoload | **Stale Loader vorab bereinigt**: Nicht mehr vorhandene Pfade wie `image/` und `rate-limiter/` wurden aus dem Asset-Autoloader entfernt und die Mailer-/Mime-/PSR-Reihenfolge konsistent gezogen. |

---

### v2.5.0 — 08. März 2026 · Full Sync, Mail-Infrastruktur & Doku-Konsolidierung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.0** | 🟢 feat | Core/Mail | **Mail-Infrastruktur produktiv ausgebaut**: `MailQueueService` verarbeitet E-Mails asynchron über `CMS/cron.php`, inklusive Retry-Strategie, Backoff, Queue-Management und Diagnose-Hooks. |
| **2.5.0** | 🟢 feat | Core/Integrations | **Microsoft-365-/Transport-Bausteine ergänzt**: `AzureMailTokenProvider`, `GraphApiService`, `MailLogService` und `SettingsService` erweitern den Transport-Stack um Token-Caching, Graph-Zugriff, Laufzeit-Settings und nachvollziehbare Mail-Logs. |
| **2.5.0** | 🟢 feat | Auth/LDAP | **Authentifizierung erweitert**: LDAP-Provider, Admin-Statusansichten und ein initialer LDAP-Sync für lokale CMS-Konten wurden in die Benutzer-/Authentifizierungsverwaltung integriert. |
| **2.5.0** | 🟢 feat | Admin/API | **Admin und API robuster gemacht**: Neue API-Routen für Seiten und Medien, härtere CSRF-Verifizierung sowie eine Grid-basierte Benutzerlistenansicht modernisieren zentrale Verwaltungsabläufe. |
| **2.5.0** | 🔵 docs | Docs/Assets | **`/CMS/assets` und `/DOC` vollständig synchronisiert**: Asset-Mapping, Workflow-Dokumente, Service-Referenzen und lokale Bundle-Dokus wurden zusammengeführt und auf den aktuellen Runtime-Stand gehoben. |
| **2.5.0** | ⬜ chore | Repo/Cleanup | **Repository bereinigt**: Veraltete Admincenter-Bilder, alte To-do-Dokumente und überholte Asset-Aufräumhinweise wurden entfernt; zusätzliche Parser-Klassen wurden mit dem Asset-Sync übernommen. |

---

### v2.4.1 — 08. März 2026 · Workflows, SEO-Medienlogik & Betriebsdoku

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.4.1** | 🟢 feat | SEO/Media | **Medienverarbeitung im SEO-Kontext verfeinert**: Bestimmte Pfade werden gezielt aus Berechnungen ausgeschlossen, Verzeichnisgrößen robuster ermittelt und Medien-Scans betriebssicherer ausgewertet. |
| **2.4.1** | 🔵 docs | Workflow | **Operative Workflow-Doku deutlich ausgebaut**: Neue Leitfäden für Marketplace, Media-Upload, Update/Deployment sowie Forum- und Newsletter-Plugin dokumentieren reale Betriebs- und Entwicklungsabläufe. |
| **2.4.1** | 🔵 docs | Assets | **Asset-Nutzung präziser dokumentiert**: Empfehlungen zur aktiven Nutzung lokaler Bundles, Mail-/LDAP-Verdrahtung und Runtime-Pfade wurden zentral in der Asset-Dokumentation ergänzt. |
| **2.4.1** | 🟡 refactor | Admin/UI | **Benutzer- und Verwaltungsoberflächen modernisiert**: Listen, Medien- und API-nahe Admin-Flows wurden strukturell aufgeräumt und besser auf die aktuelle Admin-Architektur abgestimmt. |
| **2.4.1** | ⬜ chore | Repo | **Nicht mehr benötigte Assets und Alt-Dokumente bereinigt**: Unbenutzte Artefakte und veraltete Hinweise wurden entfernt, um Doku- und Repository-Struktur klarer zu halten. |

---

### v2.4.0 — 08. März 2026 · Mailer, Auth-Settings & Integrationsbasis

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.4.0** | 🟢 feat | Core/Mail | **MailService auf lokale Symfony-Komponenten umgestellt**: Versand nutzt nun klar dokumentiert `Symfony Mailer` und `Symfony Mime`, inklusive verbesserter Fehlerbehandlung und konsistenter Transportbasis. |
| **2.4.0** | 🟢 feat | Admin/Auth | **Benutzer- und Authentifizierungs-Einstellungen erweitert**: Neue Verwaltungsflächen bündeln Status und Konfiguration für Login-, Provider- und LDAP-bezogene Einstellungen. |
| **2.4.0** | 🟢 feat | Auth/LDAP | **LDAP-Authentifizierung implementiert**: Externe Verzeichnisdienste lassen sich anbinden; ergänzend wurde der Admin-Erstsync für Benutzerkonten vorbereitet. |
| **2.4.0** | 🟢 feat | Core/Services | **Neue Integrations-Services ergänzt**: `SettingsService`, `GraphApiService`, `AzureMailTokenProvider` und `MailLogService` schaffen die Basis für moderne Mail- und Provider-Anbindungen. |
| **2.4.0** | 🔵 docs | Docs/Release | **Release-Dokumentation nachgezogen**: Mail-, Auth-, Asset- und Service-Dokumente wurden auf die neue Integrationslinie ausgerichtet und im Doku-Baum verankert. |

---

### v2.3.1 — 07. März 2026 · WebP-Automation, Font-Self-Hosting & Audit-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.3.1** | 🟢 feat | Admin/Performance | **WebP-Massenkonvertierung produktiv ergänzt**: Geeignete Bilder in `uploads/` können gesammelt in WebP umgewandelt werden; bekannte Referenzen in Medien-, Seiten-, Beitrags- und SEO-Daten werden automatisch auf die neue Datei aktualisiert. |
| **2.3.1** | 🔴 fix | Admin/Fonts | **Font-Manager robuster gemacht**: Download externer Google-Fonts nutzt zusätzliche Fallbacks für `css`/`css2`, toleriert typische SSL-/CA-Probleme auf Shared-Hosting/Windows-Setups besser und speichert erfolgreiche Self-Hosting-Aktionen nachvollziehbar. |
| **2.3.1** | 🔴 fix | Admin/SEO | **SEO-Audit defensiv stabilisiert**: Audit-Ansicht und Modul normalisieren fehlende Score-/Issue-Daten jetzt zuverlässig und vermeiden Notice-/Warning-Folgen bei unvollständigen Datensätzen. |
| **2.3.1** | 🟡 refactor | Audit/Logging | **Admin-Aktionen stärker protokolliert**: Firewall-, AntiSpam-, Plugin-, Font-, Performance- und Sicherheits-Audit-Aktionen schreiben strukturierte Einträge ins zentrale `audit_log`. |
| **2.3.1** | 🔵 docs | Docs/Release | **README, Changelog und Doku-Indizes auf 2.3.1 ausgerichtet**: Release-Linie, Monitoring-/SEO-Ausbau sowie WebP-/Font-Funktionen wurden zentral dokumentiert. |

---

### v2.3.0 — 07. März 2026 · SEO Suite & Editor-Optimierung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.3.0** | 🟢 feat | Admin/SEO | **SEO-Suite deutlich erweitert**: Neue Bereiche für Audit, Meta-Daten, Social Media, Schema, Sitemap und technisches SEO wurden als eigene Admin-Unterseiten ergänzt. |
| **2.3.0** | 🟢 feat | Editor/SEO | **Seiten- und Beitragseditoren ausgebaut**: Drei SEO-/Readability-/Preview-Karten unter dem Editor, Live-Scoring, Social-Preview und erweiterte SEO-Felder verbessern den Redaktions-Workflow. |
| **2.3.0** | 🟢 feat | Core/SEO | **Sitemap-Bundle erweitert**: XML-Sitemaps für Standard-Inhalte, Bilder und News können zentral regeneriert und überwacht werden. |

---

### v2.2.0 — 07. März 2026 · Performance Center & System-Monitoring

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.2.0** | 🟢 feat | Admin/Performance | **Performance als eigener Hauptbereich**: Cache, Medien, Datenbank, Einstellungen und Sessions wurden in eigenständige, datengetriebene Admin-Unterseiten überführt. |
| **2.2.0** | 🟢 feat | Admin/System | **Info, Diagnose und Monitoring ausgebaut**: Response-Time, Cron-Status, Disk-Usage, Scheduled Tasks, Health-Check und E-Mail-Alerts ergänzen die Systemwerkzeuge. |
| **2.2.0** | 🟡 refactor | Admin/Navigation | **System- und SEO-Navigation neu strukturiert**: Hauptmenüs für SEO, Performance, System, Info und Diagnose wurden klarer aufgeteilt und konsistent sortiert. |

---

### v2.1.2 — 07. März 2026 · Legal-Sites, Lösch-Workflow & Sicherheits-Layout

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.1.2** | 🔴 fix | Admin/Pages+Posts | **Löschen von Seiten und Beiträgen stabilisiert**: Single-Delete nutzt wieder einen robusten Formular-Submit mit direkter Bestätigung; die Delete-Logik in den Modulen liefert bei Fehlern jetzt saubere Rückmeldungen statt stillem Nichtstun. |
| **2.1.2** | 🟢 feat | Admin/Legal | **Legal Sites um Profiltyp erweitert**: Rechtstexte können jetzt gezielt für `Firma` oder `Privat` gepflegt werden. Die Pflichtfelder passen sich server- und clientseitig an den gewählten Profiltyp an. |
| **2.1.2** | 🟢 feat | Admin/Legal+Cookie | **Legal-Sites synchronisieren Folgeeinstellungen**: Beim Erstellen oder Zuordnen von Datenschutz-, AGB- und Widerrufsseiten werden abhängige Felder in anderen Admin-Bereichen automatisch befüllt, z. B. `cookie_policy_url` im Cookie-Manager sowie rechtliche Seiten-IDs für Abo-/Checkout-Einstellungen. |
| **2.1.2** | 🎨 style | Admin/Security | **Firewall- und AntiSpam-Layouts repariert**: KPI-Cards, Formulare und Listen werden wieder korrekt innerhalb des Admin-Containers gerendert und sauber nebeneinander ausgerichtet. |
| **2.1.2** | 🔵 docs | Docs/Release | **README und Changelog erweitert**: Die neue Legal-Sites-Logik, die Auto-Verknüpfung mit Cookie-/Abo-Einstellungen sowie die stabilisierten Lösch-Workflows wurden in der Projektdokumentation nachgetragen. |

---

### v2.1.1 — 07. März 2026 · Medienverwaltung, Rollenrechte & Release-Dokumentation

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.1.1** | 🔴 fix | Admin/Media | **Medienbibliothek funktional vervollständigt**: Standardmäßig Listenansicht, Suche, Kategorien-Filter, Datei-/Ordner-Löschung, robustere Redirects nach Aktionen und korrekt URL-encodierte Vorschaupfade für Bilder mit Leerzeichen oder Umlauten. |
| **2.1.1** | 🟢 feat | Admin/Media | **Geschützter Member-Medienbereich**: Der Ordner `member` verlangt vor dem Öffnen eine zusätzliche Bestätigung, wird als geschützter Systembereich behandelt und Member-Bilder werden in der Vorschaubild-Auswahl für Seiten/Beiträge ausgeblendet. |
| **2.1.1** | 🟡 refactor | Admin/Navigation | **Medien-Navigation aufgeräumt**: Doppelte Tab-Navigation entfernt, aktive Sidebar-Zustände für Medien-Unterseiten korrigiert und der Medien-Menübereich bleibt bei Unterpunkten zuverlässig geöffnet. |
| **2.1.1** | 🟢 feat | Admin/RBAC | **Rollen & Rechte erweiterbar gemacht**: In `Benutzer & Gruppen -> Rollen & Rechte` können jetzt neue Rollen und neue Rechte direkt angelegt werden; die Matrix verarbeitet dynamische Rollen und Capabilities. |
| **2.1.1** | 🔴 fix | Admin/Users | **Benutzerverwaltung an dynamische Rollen angebunden**: Rollen-Dropdowns und Filter in Listen- und Bearbeitungsansichten nutzen nun dieselbe dynamische Rollenquelle wie die Rechteverwaltung. |
| **2.1.1** | 🔴 fix | Core/Auth | **Capability-Prüfung DB-basiert erweitert**: `Auth::hasCapability()` berücksichtigt gespeicherte Rollenrechte aus `role_permissions`, damit neu angelegte Rollen sofort wirksam sind. |
| **2.1.1** | 🔵 docs | Docs/Release | **README, Changelog und Release-Metadaten synchronisiert**: Versionsstände auf `2.1.1` angehoben, neue Medien- und RBAC-Funktionen dokumentiert und die Patch-Version ohne Versionssprung in die Historie aufgenommen. |

---

### v2.1.0 — 07. März 2026 · Editor.js, Routing, Services & System-Tools

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.1.0** | 🟢 feat | Core/Editor | **Editor.js zusätzlich integriert**: Neben SunEditor steht jetzt auch Editor.js für moderne, blockbasierte Inhalte zur Verfügung. |
| **2.1.0** | 🟢 feat | Core/Services | **Neue Services ergänzt**: Comment-Management, Cookie-Consent, File-Uploads, PDF-Generierung, Site-Tables und Translation-Services wurden ausgebaut bzw. neu integriert. |
| **2.1.0** | 🟢 feat | Core/Router | **Mitglieder- und Dashboard-Routing erweitert**: Eigene Dashboard-Routen, Theme-Overrides, POST-Routen für den Member-Bereich und zusätzliche Seitennamen-Prüfungen ergänzen das Routing-System. |
| **2.1.0** | 🟢 feat | Admin/System | **DB-Tools in System-Info & Diagnose**: Neue Aktionen zum Erstellen fehlender Tabellen und zur Tabellen-Reparatur direkt im Admin. Die Diagnose deckt jetzt das vollständige Core-Schema mit 30 Tabellen inkl. `posts`, `comments`, `messages`, `audit_log` und `custom_fonts` ab. |
| **2.1.0** | 🟢 feat | Admin/RBAC | **Benutzer-, Rollen- und Berechtigungsverwaltung erweitert**: Neue Verwaltungsansichten und überarbeitete Admin-Oberflächen erleichtern Rollen- und Rechtemanagement. |
| **2.1.0** | 🟢 feat | Admin/Theme | **Schriften & Theme-Assets erweitert**: Brand-Schriften wurden in Download- und Ladefunktion integriert; Google Fonts lassen sich DSGVO-konform lokal speichern und im Frontend einbinden. Neue Tabelle `custom_fonts`, Schema-Version `v9`. |
| **2.1.0** | 🔴 fix | Admin/Security | **CSRF-Token-Flows korrigiert**: Die Token-Reihenfolge in Admin-Formularen wurde bereinigt, fehlende Token-Erzeugung auf normalen GET-Loads ergänzt und fehleranfällige Formularabläufe stabilisiert. |
| **2.1.0** | 🔴 fix | Admin/System | **Diagnose-Ansichten stabilisiert**: Berechtigungsanzeige und System-Info verarbeiten Rückgabedaten wieder korrekt und vermeiden TypeErrors in der Ausgabe. |
| **2.1.0** | 🟡 refactor | Admin/UI | **Admin-UI modernisiert**: Theme-Seiten, Dashboard, Posts- und User-Oberflächen wurden aufgeräumt, stärker auf Tabler Icons ausgerichtet und strukturell vereinheitlicht. |
| **2.1.0** | 🔴 fix | Theme/Navigation | **Defensive Verarbeitung für Menüs und Themes**: Ungültige Einträge in Theme- und Menü-Arrays werden robuster abgefangen und übersprungen. |
| **2.1.0** | 🔵 docs | Docs | **README & Changelog komplett aktualisiert**: Release-Version angehoben, System-/Schema-Dokumentation korrigiert und vollständige Übersicht der gebündelten Drittanbieter-Assets mit Autor, Website und GitHub-Links ergänzt. |

---

### v2.0.9 — 07. März 2026 · Rollenverwaltung & Release-Vorbereitung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.9** | 🟢 feat | Admin/RBAC | **Rollen- und Berechtigungsansicht erweitert**: Neue Verwaltungsoberfläche für Benutzerrollen und Berechtigungen vorbereitet bzw. eingebunden. |
| **2.0.9** | ⬜ chore | Assets | **Vendor-/Asset-Bestand bereinigt**: Größere Asset-Bestände wie `remark42` wurden in der Arbeitsbasis überarbeitet bzw. ausgeräumt, um das Repository zu konsolidieren. |
| **2.0.9** | 🔵 docs | Project | **Release-Vorbereitung für 2.1.0**: Versionspflege und Projekt-Metadaten wurden auf den nächsten Major-Patch-Zwischenschritt vorbereitet. |

---

### v2.0.8 — 06. März 2026 · Services-Ausbau

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.8** | 🟢 feat | Core/Services | **Neue Service-Bausteine ergänzt**: Comment-Management, Cookie-Consent, File-Uploads, PDF-Generierung, Site-Tables und Translation wurden als Services ergänzt bzw. deutlich erweitert. |
| **2.0.8** | 🟢 feat | Core/Docs | **Infrastruktur für weitere Core-Integrationen**: Die Service-Schicht wurde als Grundlage für zusätzliche Admin- und Frontend-Funktionen ausgebaut. |

---

### v2.0.7 — 05. März 2026 · Admin-UI, Editor.js & Asset-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.7** | 🟢 feat | Core/Editor | **Editor.js integriert**: Editor.js wurde zusätzlich zu SunEditor eingebunden, um blockbasierte Inhaltsbearbeitung zu ermöglichen. |
| **2.0.7** | 🟡 refactor | Admin/UI | **Admin-Oberflächen überarbeitet**: Dashboard, Posts, Users und Theme-Seiten wurden strukturell modernisiert, aufgeräumt und stärker auf Tabler abgestimmt. |
| **2.0.7** | 🟢 feat | Admin/Layout | **Layout-Funktionen für Dashboard/Seiten ausgebaut**: HTML-Strukturen wurden in zentrale Layout-Helfer überführt und Script-Verknüpfungen vereinheitlicht. |
| **2.0.7** | ⬜ chore | Assets | **Asset-Bestand aktualisiert**: Zusätzliche Vendor-Assets wie Tabler-Libs wurden in die Arbeitsbasis übernommen; veraltete Test-/Import-Verzeichnisse wurden entfernt. |

---

### v2.0.6 — 04. März 2026 · Fonts & Dashboard-Routing

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.6** | 🟢 feat | Core/Router | **Dashboard-Routen ergänzt**: Themes können jetzt ein eigenes Dashboard ausspielen; andernfalls greift sauber der Fallback auf `/member/dashboard`. |
| **2.0.6** | 🟢 feat | Admin/Theme | **Brand-Schriftarten erweitert**: Brand-Fonts wurden sowohl in die Ladefunktion als auch in die Downloadfunktion für den Font-Workflow aufgenommen. |

---

### v2.0.5 — 03. März 2026 · Member-Routing & defensive Theme-Menüs

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.5** | 🟢 feat | Core/Router | **Memberbereich auf `/member/dashboard` umgestellt**: Routing für den Mitgliederbereich wurde vereinheitlicht und Theme-Overrides für Seitenimplementierungen ergänzt. |
| **2.0.5** | 🔴 fix | Theme/Navigation | **Defensive Menü-/Theme-Verarbeitung**: Ungültige Einträge in Menü- und Theme-Arrays werden jetzt robuster erkannt und übersprungen. |

---

### v2.0.4 — 02. März 2026 · Member-POST-Routen & Kontaktseite

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.4** | 🟢 feat | Core/Router | **POST-Routen für den Mitgliederbereich**: Formulare und Aktionen im Memberbereich erhielten eigene POST-Routen inklusive zusätzlicher Prüfung erlaubter Seitennamen. |
| **2.0.4** | 🟢 feat | Frontend/Kontakt | **Kontaktseite im Routing berücksichtigt**: Kontaktformulare bekamen die nötige Sonderbehandlung im Routing, damit Frontend-POSTs sauber verarbeitet werden. |

---

### v2.0.3 — 01. März 2026 · Legal-Generator & Abo-Zuweisungen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.3** | 🔴 fix | Admin/Abo | **Benutzer-Abos in Zuweisungen abgesichert**: Die Anzeige und Zuordnung aktiver Benutzer-Abos wurde nach dem Split der Abo-Ansichten weiter stabilisiert. |
| **2.0.3** | 🟢 feat | Admin/Legal | **Impressum-Generator nachgeschärft**: Der Generator wurde weiter erweitert und für den produktiven Einsatz in den Rechtstexten verfeinert. |

---

### v2.0.2 — 01. März 2026 · Admin-Fixes, SEO-Frontend, Abo-Split

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.2** | 🔴 fix | Admin/Legal | **Legal Pages Posts->Pages**: `cms_posts` durch `cms_pages` ersetzt; `type`-Spalte entfernt (existiert nicht). DSGVO-Texte erweitert (Art. 13/14, EU-Streitschlichtung, SSL/TLS). Unicode-Quotes durch HTML-Entities ersetzt. |
| **2.0.2** | 🟢 feat | Admin/Legal | **Impressum Generator erweitert**: Neue Abschnitte: Haftung fuer Inhalte, Haftung fuer Links, Urheberrechtshinweis. Neue Formularfelder: Website-Name, Registergericht, verbundene Domains, Datenschutzbeauftragter. Kontaktzeile zeigt Telefon nur wenn ausgefuellt. HTML-Entities statt Unicode-Sonderzeichen. |
| **2.0.2** | 🔴 fix | Admin/Media | **CSRF Auto-Retry**: `cmsPost()` erkennt CSRF-Fehler und wiederholt den Request automatisch mit neuem Token. Behebt "Sicherheitsueberprüfung fehlgeschlagen" bei Ordner-Navigation. |
| **2.0.2** | 🟢 feat | Core/SEO | **SEO Frontend-Integration**: 5 neue public Getter in `SEOService` (`getHomepageTitle`, `getHomepageDescription`, `getMetaDescription`, `getSiteTitleFormat`, `getTitleSeparator`). Theme-Header nutzt SEO-Titel-Prioritaetskette. |
| **2.0.2** | 🟢 feat | Admin/Theme | **Multi-Rolle Editor-Zugriff**: Einzelauswahl-Dropdown durch Mehrfach-Checkboxen ersetzt (`theme_editor_roles`, kommasepariert). Marketplace-Sektion komplett entfernt. |
| **2.0.2** | 🔴 fix | Admin/Settings | **Aktive Module Mock entfernt**: Hardcodierte "Blog Modul: Aktiv / Shop System: Inaktiv"-Karte entfernt. |
| **2.0.2** | 🔴 fix | Core/UpdateService | **Update-URL konfigurierbar**: GitHub Repo/API-URL per DB-Setting (`update_github_repo`, `update_github_api`) konfigurierbar mit Fallback auf Defaults. Behebt HTTP 404 bei falscher API-URL. |
| **2.0.2** | 🟢 feat | Admin/Abo | **Zuweisungen gesplittet**: Neuer Tab "Uebersicht" (`?tab=overview`) mit Statistiken, Benutzer-Abo-Tabelle und Gruppen-Paketzuordnung (read-only). Tab "Zuweisungen" nur noch fuer Formulare. Neuer Sidebar-Menuepunkt. |
| **2.0.2** | 🔴 fix | Admin/Abo | **Benutzer-Abos in Zuweisungen**: Aktive Benutzer-Abos-Tabelle zurueck in den Tab "Zuweisungen" (war nach Overview-Split verloren gegangen). |
| **2.0.2** | 🔴 fix | Plugins/JPG | **Installer Column-Fix**: `setting_key`/`setting_value` auf `option_name`/`option_value` korrigiert fuer Zugriff auf Core-Tabelle `cms_settings`. |

---

### v2.0.1 — 01. März 2026 · Admin-Panel Audit & Bugfixes

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.1** | 🔴 fix | Admin/Media | **CSRF-Token-Rotation**: `query()` → `execute()` für AJAX-Aufrufe; neuer Token wird nach jeder Verifizierung zurückgegeben und im JS aktualisiert (`const` → `let` für `CMS_MEDIA_NONCE`). |
| **2.0.1** | 🔴 fix | Admin/Users+Groups | **Dynamische Rollen**: Hardcodierte Rollen-Arrays (`['admin','member','editor']`) durch DB-Abfragen aus `cms_roles` ersetzt; Rollendropdowns, Filter-Tabs und Validierung nutzen jetzt alle CMS-Rollen. Auto-Migration für `sort_order`/`member_dashboard_access`-Spalten in `groups.php`. |
| **2.0.1** | 🔴 fix | Admin/Subscriptions | **SQL-Fehler behoben**: `$db->query()` (kein Param-Support) → `$db->execute()` für Prepared Statements in `update_settings`, `assign_group_plan` und `delete_plan`. In `subscription-settings.php`: nicht-existierende `fetch()`/`fetchColumn()` → `get_row()`/`get_var()`; CSRF Action-Slug ergänzt. |
| **2.0.1** | 🔴 fix | Admin/Theme | **Editor-Rolle dynamisch**: Dropdown in `theme-settings.php` zeigt jetzt alle DB-Rollen statt nur `admin`/`editor`. |
| **2.0.1** | 🔴 fix | Admin/Legal | **type=page bei INSERT**: Impressum und Datenschutz-Posts erhalten jetzt `'type' => 'page'` wie Cookie-Richtlinie. |
| **2.0.1** | 🔴 fix | Admin/Settings | **Rollen-Validierung dynamisch**: `$allowedRoles` wird aus DB geladen statt hardcodiert (`['admin','editor','author','member','subscriber']`). |
| **2.0.1** | 🔴 fix | Admin/System | **Tabellenzahl nicht mehr hardcoded**: `/ 22` aus CMS-Tabellen-Anzeige entfernt (tatsächlich 29+ Tabellen). |
| **2.0.1** | 🟡 refactor | Admin/Updates | `window.confirm()` durch eigenes Bestätigungs-Modal ersetzt (Konventions-konform). |
| **2.0.1** | 🟡 refactor | Admin/Layout | `theme-marketplace.php`, `plugin-marketplace.php`, `support.php`, `updates.php`: Manuelles HTML-Boilerplate durch `renderAdminLayoutStart()`/`renderAdminLayoutEnd()` ersetzt. |

---

### v2.0.0 — 28. Februar 2026 · Nachrichten-System & Security-Audit

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.0** | 🟢 feat | Core/Member | **Nachrichten-System**: Vollständige Member-to-Member-Messaging-Funktion mit Posteingang, Gesendet-Ansicht, Thread-Konversationen, Empfänger-Autocomplete und Soft-Delete. Neue `cms_messages`-Tabelle (SchemaManager v8). Neuer `MessageService` (Singleton, Inbox/Sent/Thread/Send/Delete/UnreadCount). Member-Dashboard mit Two-Panel-Layout (Konversationsliste + Detail/Thread/Compose). **Security-Audit**: 10 CRITICAL- und 9 HIGH-Priority-Fixes implementiert (XSS-Escaping, CSRF-Schutz, Path-Traversal, Admin-Passwort, SQL-Injection-Prävention). **Installer**: CMS_VERSION → 2.0.0, PHP-Mindestversion → 8.2, Messages-Tabelle hinzugefügt. |

---

### v1.9.x — 27.–28. Februar 2026 · Security Hardening & UI-Improvements

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **1.9.5** | 🔴 fix | Admin/Security | **Media**: XSS-Escaping mit `escHtml()`/`escAttr()` für Dateinamen und Alt-Texte; Custom Delete-Modal statt `window.confirm()`. **Pages**: `wp_kses_post` für Seiteninhalt-Sanitierung; Custom Lösch-Modal. **Backup**: Path-Traversal-Schutz mit `basename()`+Regex. **Updates**: Core-Update-Button mit AJAX-Handler. **Security-Audit**: Inline-CSS + admin-page-header Fix. **Plugin-UI**: 10px Menü-Spacing-Fix. |
| **1.9.4** | 🔴 fix | Admin | `ABSPATH`-Guard in `users.php` hinzugefügt; dupliziertes HTML entfernt; `last_login`-Spalte und Delete-Button ergänzt; Site-URL-Definition sichergestellt. |
| **1.9.3** | 🎨 style | Admin | Plugin-Management-UI komplett überarbeitet für besseres Layout und Responsiveness. |
| **1.9.2** | 🟡 refactor | Theme | ThemeCustomizer: `prepare/execute` durch `execute` ersetzt für korrekte NULL-Wert-Behandlung; Error-Logging verbessert. |
| **1.9.1** | 🔴 fix | Admin/Plugin | Unbenutzte Feature-Widgets aus Widget-Dashboard entfernt; Output-Buffering für Plugin-Admin-Bereich korrigiert. |
| **1.9.0** | 🟢 feat | Member | Accordion-Navigation für Member-Sidebar mit ausklappbaren Plugin-Bereichen und verbessertem Styling. |

---

### v1.8.x — 22.–26. Februar 2026 · Security, Themes & Blog

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **1.8.5** | 🟢 feat | Member | Plugin-Navigation im Member-Bereich mit Sub-Items und verbessertem Styling. |
| **1.8.4** | ⬜ chore | Theme | TechNexus-Theme und zugehörige Unit-Tests entfernt. |
| **1.8.3** | 🟢 feat | Theme | 365Network Theme Customizer: Konfigurierbare Einstellungen für Farben, Typografie, Layout, Header, Footer, Buttons und erweiterte Optionen. |
| **1.8.2** | 🔴 fix | Core | `Security::escape()` akzeptiert nun `string|int`. EditorService nutzt `setContents()` statt `set()` um WYSIWYG-Double-Encoding zu verhindern. `fetchGitHubData` von `file_get_contents` auf cURL umgestellt. |
| **1.8.1** | 🔴 fix | Router/Admin | **Öffentliche Seiten 404-Bug**: Neue CMS-Seiten wurden standardmäßig als `draft` angelegt. `admin/pages.php` – Default-Status auf `published` geändert. Router-Debug-Logging verbessert. |
| **1.8.0** | 🟢 feat | Security | CMS-Firewall mit IP-Blocking, Geo-Filtering und Request-Analyse sowie AntiSpam und Security-Audit vollständig überarbeitet. |

---

### v1.7.x — 22. Februar 2026 · Theme & Plugin Marketplace

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.7.9 | 🟢 feat | Admin | RBAC-Verwaltung vollständig neu implementiert mit granularen Capabilities |
| 1.7.8 | 🟢 feat | Admin | Support-Ticket-System mit Prioritäten und Status-Tracking in Admin integriert |
| 1.7.7 | 🟢 feat | Theme | Theme-Marketplace mit 10 fertigen Themes und Vorschau-Funktion |
| 1.7.6 | 🟢 feat | Plugin | Plugin-Marketplace mit Kategorie-Browser und Such-Filter |
| 1.7.5 | 🟢 feat | Theme | Lokaler Fonts Manager mit Upload, Verwaltung und Theme-Integration |
| 1.7.4 | 🟡 refactor | Theme | Theme-Customizer auf 50+ Optionen in 8 Kategorien erweitert |
| 1.7.3 | 🟢 feat | Admin | Update-Manager für Core, Plugins und Themes direkt via GitHub API |
| 1.7.2 | 🟢 feat | Admin | Benutzerdefinierte Site-Tables mit CRUD, Import/Export CSV/JSON erweitert |
| 1.7.1 | 🟢 feat | Member | Member-Dashboard Admin-Verwaltung mit Übersichts- und Statusseite überarbeitet |
| 1.7.0 | 🟢 feat | Admin | README-Dokumentation vollständig mit Screenshots und Feature-Übersicht aktualisiert |

---

### v1.6.x — 21.–22. Februar 2026 · Cookie-Manager & Legal-Suite

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.6.9 | 🟢 feat | Cookie | Cookie-Verwaltung mit Dienstbibliothek und Sicherheitsprüfungen erweitert |
| 1.6.8 | 🔵 docs | Core | Dokumentation und Skripte für 365CMS aktualisiert |
| 1.6.7 | ⬜ chore | Docs | Veraltete Sicherheitsarchitektur-Dokumentation entfernt |
| 1.6.6 | 🔵 docs | README | README-Dateien mit neuen Versionsinformationen und verbesserten Beschreibungen aktualisiert |
| 1.6.5 | 🟢 feat | Admin | Site-Tables-Management mit CRUD-Operationen und Import/Export; neue Menüeinträge |
| 1.6.4 | 🟡 refactor | Legal | Generierung von Rechtstexten bereinigt und optimiert; Menübezeichnung aktualisiert |
| 1.6.3 | 🟢 feat | Cookie | Cookie-Richtlinie mit dynamischem Zustimmungsstatus und optimierter Darstellung |
| 1.6.2 | 🟢 feat | Cookie | Cookie-Richtlinie-Generierung in Rechtstexte-Generator integriert |
| 1.6.1 | 🟢 feat | Legal | AntiSpam-Einstellungsseite und Rechtstexte-Generator implementiert |
| 1.6.0 | 🟢 feat | Cache | Cache-Clearing-Funktionalität und Asset-Regenerierung hinzugefügt |

---

### v1.5.x — 21. Februar 2026 · Support-System & DSGVO

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.5.9 | 🔴 fix | Database | Tabellenbezeichnungen von `usermeta` zu `user_meta` in mehreren Dateien aktualisiert |
| 1.5.8 | 🔴 fix | SEO | Einstellungsname für benutzerdefinierten robots.txt-Inhalt korrigiert |
| 1.5.7 | 🟢 feat | GDPR | DSGVO-konforme Datenlöschung und Security-Audit-Seite hinzugefügt |
| 1.5.6 | 🔵 docs | Docs | INDEX.md in Dokumentationsliste priorisiert; Dokumentationsindex bereinigt |
| 1.5.5 | 🔵 docs | Docs | Dokumentation für Content-Management, SEO, Performance, Backup und User-Management |
| 1.5.4 | 🟡 refactor | Support | Übersichtsseiten je Bereich mit GitHub-Links statt Markdown-Rendering |
| 1.5.3 | 🔴 fix | Support | Timeout auf 4/6s reduziert; 5-min-Datei-Cache für Dok-Liste; Refresh-Link |
| 1.5.2 | 🔴 fix | Support | fetchDocContent auf GitHub Contents-API umgestellt; CDN entfernt, Markdown serverseitig gerendert |
| 1.5.1 | 🔴 fix | Support | cURL-basierter GitHub-API-Client; Debug-Modus; DOC/admin-Ordner umbenannt |
| 1.5.0 | 🟡 refactor | Support | Support.php komplett neu: Docs ausschließlich via GitHub API + raw.githubusercontent.com |

---

### v1.4.x — 21. Februar 2026 · Admin-Erweiterungen & Logging

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.4.9 | 🟢 feat | Docs/Support | Dokumentationsabruf mit rekursivem Directory-Traversal; Sidebar-Gruppierung |
| 1.4.8 | 🟡 refactor | Core | File-Struktur bereinigt; Code-Struktur für bessere Lesbarkeit optimiert |
| 1.4.7 | 🟢 feat | Admin | Plugin- und Theme-Marketplace-Seiten mit Settings-Management hinzugefügt |
| 1.4.6 | 🟢 feat | Landing | Landing-Page-Management erweitert |
| 1.4.5 | 🔴 fix | Logging | Logs werden nur noch bei `CMS_DEBUG=true` in `/logs` geschrieben |
| 1.4.4 | 🎨 style | Orders | Admin-Design für Bestellverwaltung vereinheitlicht (Benutzer & Gruppen) |
| 1.4.3 | 🔵 docs | Changelog | Versionierung auf 0.x umgestellt; Changelog + README aktualisiert |
| 1.4.2 | 🟢 feat | Subscriptions | Admin-Subscriptions-UI mit verbesserter Navigation und Labels |
| 1.4.1 | 🟡 refactor | Subscriptions | Pakete-Editor in Übersicht integriert; neue Einstellungen-Seite; Sub-Tabs entfernt |
| 1.4.0 | 🟢 feat | Dashboard | Version-Badge im Admin Dashboard-Header |

---

### v1.3.x — 20. Februar 2026 · Public Release & Blog/Subscriptions

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.3.6 | ⬜ chore | CI/CD | PHP-Composer-Workflow-Konfiguration hinzugefügt |
| 1.3.5 | 🟢 feat | Subscriptions | Subscription- und Checkout-System implementiert |
| 1.3.4 | 🟢 feat | Pages | Page-Management-UI mit Success/Error-Messages und verbessertem Layout |
| 1.3.3 | 🔴 fix | Security | CSRF-Token-Handling in User- und Post-Management-Formularen verbessert |
| 1.3.2 | 🟢 feat | Blog | Blog-Routen für Post-Listing und Single-Post-Detailansicht hinzugefügt |
| 1.3.1 | 🟢 feat | Database | Datenbankschema auf Version 3 aktualisiert; Blog-Post-Tabellen hinzugefügt |
| **1.3.0** | 🟢 feat | **Release** | **First Public Release – 365CMS.DE veröffentlicht** |

---

### v1.2.x — 18.–20. Februar 2026 · Media & Member-Erweiterungen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.2.7 | 🔵 docs | Projekt | Initial Commit 365CMS.DE Repository; README mit CMS-Beschreibung und Website-Link |
| 1.2.6 | 🟢 feat | Subscriptions | Zahlungsarten-Update implementiert; Benutzerabonnements-Abfrage verbessert |
| 1.2.5 | 🟢 feat | Member | Member-Menü überarbeitet; Favoriten- und Nachrichten-Funktionalität hinzugefügt |
| 1.2.4 | 🟡 refactor | Error | Fehlerbehandlung überarbeitet; Media-Upload-Struktur für mehr Robustheit verbessert |
| 1.2.3 | 🟡 refactor | Media | Media-View und AJAX-Handling für bessere UX und Fehlerbehandlung überarbeitet |
| 1.2.2 | 🔴 fix | AJAX | AJAX-URL-Handling für mehr Robustheit und Debugging verbessert |
| 1.2.1 | 🟢 feat | Media | Media-Proxy und AJAX-Handling für verbesserte Medienoperationen implementiert |
| 1.2.0 | 🟢 feat | Media | Medien-AJAX-Handling und Authentifizierung verbessert; robustere Fehlerbehandlung |

---

### v1.1.x — 10.–18. Februar 2026 · Member-System & Plugins

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.1.9 | 🟢 feat | Member | Member-Medien-Management implementiert (Upload, Verwaltung) |
| 1.1.8 | 🟢 feat | Admin | Dashboard-Funktionalität um Logo-Upload erweitert; Widget-Anzahl auf 4 erhöht |
| 1.1.7 | 🔵 docs | Themes | Umfassende Dokumentation für Theme-Entwicklung in CMSv2 erstellt |
| 1.1.6 | 🟢 feat | Member | Member-Service hinzugefügt; CMS-Speakers-Plugin refaktoriert |
| 1.1.5 | 🟢 feat | Events | CMS-Experts und Events-Management erweitert |
| 1.1.4 | 🟢 feat | Experts | Expert-Management: Status-Updates, Skill-Presets und Plugin-Einstellungen |
| 1.1.3 | 🟡 refactor | Core | Code-Struktur für bessere Lesbarkeit und Wartbarkeit refaktoriert |
| 1.1.2 | 🟢 feat | Landing | Landing-Page-Service um Footer-Management erweitert |
| 1.1.1 | 🟢 feat | Cookie | Cookie-Scanning-Funktionalität mit serverseitigen und Content-Heuristik-Prüfungen |
| 1.1.0 | 🟢 feat | Admin | Landing-Page und Theme-Management-Funktionalität im Admin hinzugefügt |

---

### v1.0.x — 01.–09. Februar 2026 · Stabilisierung & AJAX-Architektur

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.0.9 | 🔴 fix | Dashboard | Escaped Backslash-Dollar in SQL-Prefix-Interpolation entfernt |
| 1.0.8 | 🔴 fix | Subscriptions | Fehlendes PHP-Schlusstag `?>` in create-plan-Modal (Zeile 521) ergänzt |
| 1.0.7 | 🔴 fix | Subscriptions | Price-Felder zu Float gecastet vor `number_format()` |
| 1.0.6 | 🔴 fix | Core | Sicherheits-Fixes in Core-Klassen |
| 1.0.5 | 🔴 fix | Core | Datenbank-Prefix-Methoden und Session-Logout-Handling verbessert |
| 1.0.4 | 🟢 feat | Admin | Vollständiger Admin-Bereich: AJAX-Architektur für 12 Dateien (Services + AJAX + Views-Trennung) |
| 1.0.3 | 🔵 docs | Core | Core-Bereich vollständig dokumentiert |
| 1.0.2 | 🟡 refactor | Services | Prefix-Property + hardkodierte Tabellennamen eliminiert |
| 1.0.1 | 🟠 perf | Core | `createTables()` Performance Guards in Database + SubscriptionManager |
| 1.0.0 | 🔴 fix | Admin | Konsistenz + Performance-Fixes im Admin-Bereich |

---

### v0.9.x — Januar 2026 · Member-Bereich & Admin-Neugestaltung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.9.9 | 🔴 fix | Admin | Kritische Sicherheits-Fixes – groups.php, subscriptions.php, theme-editor.php |
| 0.9.8 | 🔵 docs | Admin | README.md aktualisiert und ADMIN-FILESTRUCTURE.md zur vollständigen Dokumentation erstellt |
| 0.9.7 | 🔴 fix | Subscriptions | Redundante statusBadges in subscription-view entfernt |
| 0.9.6 | 🔴 fix | Member | Critical Bug Fixes: Method-Visibility, Config-Loading, XSS, Escaping |
| 0.9.5 | 🟢 feat | Member | Member-Profil, Security, Subscription und Datenschutz-Views und Controller hinzugefügt |
| 0.9.4 | 🟢 feat | Subscriptions | Subscription-Management Admin-Seite mit Plan-Erstellung und Zuweisung |
| 0.9.3 | 🟢 feat | Admin | Updates-, Backup- und Tracking-Services hinzugefügt |
| 0.9.2 | 🟢 feat | Admin | Backup-Management-Seite mit Backup-Funktionalitäten implementiert |
| 0.9.1 | 🟢 feat | Admin | Komplett neuer Admin-Bereich – Modern & Friendly |
| 0.9.0 | 🔴 fix | Assets | CSS/JS-Pfade auf absolute Server-Root-Pfade geändert + Test-Datei |

---

### v0.8.x — Januar 2026 · Sicherheits-Patches & Dashboard

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.8.9 | 🔴 fix | Admin | Admin-CSS- und JS-Pfade korrigiert (global → admin/assets) |
| 0.8.8 | 🟢 feat | Dashboard | Dashboard mit moderner AJAX-Architektur ersetzt; DashboardService-Datenbankfehler behoben |
| 0.8.7 | 🟢 feat | Cache | Umfassende Cache-Clearing-Funktion implementiert |
| 0.8.6 | 🔴 fix | Services | Service-Fehler behoben; fehlende `use CMS\Security` in landing-get.php ergänzt |
| 0.8.5 | 🔴 fix | Settings | Settings-Tabelle Spaltennamen korrigiert (`setting_key/value` → `option_name/value`) |
| 0.8.4 | 🟢 feat | Database | Automatische DB-Bereinigung bei Neuinstallation implementiert |
| 0.8.3 | 🔴 fix | Install | install-schema.php HTTP 500 durch falsche Database-Methoden behoben |
| 0.8.2 | 🔴 fix | Namespaces | Namespace-Regressionen in Services und Datenbank-Schema behoben |
| 0.8.1 | 🔴 fix | Core | Session-Management in autoload.php zentralisiert; `Auth::getCurrentUser()` hinzugefügt |
| **0.8.0** | 🔴 **fix** | **Core** | **KRITISCH: 7 Sicherheitsprobleme behoben** |

---

### v0.7.x — Januar 2026 · Sicherheit, E-Mail & PWA

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.7.8 | 🔴 fix | Security | CORS-Konfiguration und SEO-External-Code-Embedding gesichert |
| 0.7.7 | 🔴 fix | Security | 5 kritische Sicherheitsprobleme im Core-System behoben |
| 0.7.6 | 🟢 feat | Admin | Phase 1.1: Admin-Core-Migration – Admin.php mit erweiterten Features erstellt |
| 0.7.5 | 🟢 feat | Core | Phase 1.3: Job-Queue-System mit Scheduling, Worker-Management und Monitoring |
| 0.7.4 | 🟢 feat | Email | Phase 1.2: E-Mail-System mit Templates, Queue und Tracking vollständig implementiert |
| 0.7.3 | 🔴 fix | Security | SQL-Injection- und Credential-Exposure-Schwachstellen behoben |
| 0.7.2 | 🟢 feat | Cache | LiteSpeed-Cache-Integration und Performance-Optimierungen implementiert |
| 0.7.1 | 🟢 feat | PWA | Phase 1.5 PWA-Support implementiert – Phase 1 Implementierung 100 % abgeschlossen |
| **0.7.0** | 🟢 feat | Security | Phase 1.4 Sicherheits-Enhancements: MFA, OAuth, Social Login, Intrusion Detection, GDPR |

---

### v0.6.x — Januar 2026 · Bugfixes, Bookings & Multi-Tenancy

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.6.9 | 🟢 feat | Core | Multi-Tenancy-Foundation (Tenant.php) implementiert – Phase 2 Core-Start |
| 0.6.8 | 🟠 perf | Bookings | Datenbankindex-Optimierung für 75 % Abfrage-Performance-Verbesserung |
| 0.6.7 | 🟢 feat | Bookings | Konflikt-Erkennung mit Pufferzeiten, Urlaubssperrung und Concurrency-Limits erweitert |
| 0.6.6 | 🔴 fix | Admin | Admin-Panel Plugin-Management gefixt; Subdirectory-Support hinzugefügt |
| 0.6.5 | 🔴 fix | Database | Merge-Konflikte, Schema-Doppelpräfix und Konfig-Struktur behoben |
| 0.6.4 | 🔴 fix | Core | Fehlende Helper-Funktionen ergänzt: `has_action`, `has_filter`, `trailingslashit` |
| 0.6.3 | 🔴 fix | Database | Schema.sql bereinigt: Plugin-Tabellen entfernt, cms_users-Felder korrigiert |
| 0.6.2 | 🟡 refactor | Core | Modulare Architektur: index.php von 258 auf 72 Zeilen reduziert |
| 0.6.1 | 🔴 fix | Database | Datenbank-Prefix-Doppelpräfix-Bugs im gesamten Codebase behoben |
| 0.6.0 | 🔴 fix | Core | Kritische Routing- und Datenbank-Prefix-Bugs im CMS-Core behoben |

---

### v0.5.x — Januar 2026 · CMSv2 Initial · Interner Release

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.5.9 | 🔵 docs | Docs | ADMIN-GUIDE.md in `doc/admin/`-Unterverzeichnis reorganisiert |
| 0.5.8 | 🔵 docs | Admin | Umfassende ADMIN-GUIDE.md + Security/Performance-Admin-Seiten erstellt |
| 0.5.7 | 🟢 feat | Core | PluginManager: getActivePlugins angepasst; getCurrentTheme; time_ago erweitert; clear-cache.php |
| 0.5.6 | 🟢 feat | Admin | System-Status-Seite hinzugefügt; User-Erstellungsformular verbessert |
| 0.5.5 | 🟢 feat | Admin | User-Management mit CRUD-Operationen, Rollenverwaltung und Bulk-Aktionen |
| 0.5.4 | 🟢 feat | Admin | Vollständiger Admin-Bereich implementiert |
| 0.5.3 | 🔵 docs | Docs | Vollständige Dokumentation für CMS365-Phasen und Security-Audit hinzugefügt |
| 0.5.2 | 🟢 feat | Security | Security-Layer implementiert; 5 kritische Sicherheitsprobleme im Core behoben |
| 0.5.1 | 🟢 feat | Core | Install.php, Updater.php und erweitertes index.php mit Full-Routing hinzugefügt |
| **0.5.0** | 🟢 feat | **Core** | **CMSv2 Initial: Core-System mit Hooks, Datenbank, Auth und Routing implementiert** |

---

> *CMSv1 (0.1.xx – 0.4.99) – Interne Entwicklungsphase 2024-2025, nicht öffentlich verfügbar*
