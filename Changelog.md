﻿﻿# 365CMS.DE  [![Generic badge](https://img.shields.io/badge/VERSION-2.7.313-blue.svg)](https://shields.io/)

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

### v2.7.313 — 27. März 2026 · Audit-Batch 395, Font-Manager-Forms an klaren Pending-Vertrag gehängt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.313** | 🎨 style | Admin/UI | **`CMS/admin/views/themes/fonts.php` bindet Scan-, Download-, Delete- und Save-Formulare jetzt über konsistente `data-font-manager-form`-/Pending-Attribute an denselben UI-Vertrag**: Der Font-Manager signalisiert laufende Aktionen damit sichtbarer und bleibt bei mehreren Buttons pro Seite konsistenter. |

---

### v2.7.312 — 27. März 2026 · Audit-Batch 394, Font-Manager-Asset gegen Doppelaktionen geglättet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.312** | 🔴 fix | Admin/UI | **`CMS/assets/js/admin-font-manager.js` markiert Submit-Buttons jetzt beim Absenden als laufend und sperrt sie bis zum Request-Ende**: Delete-, Scan-, Download- und Save-Aktionen feuern damit nicht mehr so leicht doppelt aus hektischem Mehrfachklicken. |
| **2.7.312** | 🟡 refactor | Admin/UI | **Delete-Confirms und klassische Form-Submits nutzen jetzt denselben Pending-Button-Pfad**: Der Font-Manager hält seine Laufzeitlogik dadurch enger an einem kleinen Asset-Vertrag statt an getrennten Sonderfällen für Confirm und Direktsubmit. |

---

### v2.7.311 — 27. März 2026 · Audit-Batch 393, Theme-Explorer-Asset um Dirty- und Pending-Zustände ergänzt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.311** | 🔴 fix | Admin/UI | **`CMS/assets/js/admin-theme-explorer.js` schützt ungespeicherte Editoränderungen jetzt mit Dirty-State und `beforeunload`-Warnung**: Versehentliches Verlassen des Editors kostet damit seltener stille Änderungen. |
| **2.7.311** | 🔴 fix | Admin/UI | **Ctrl+S und normale Saves sperren den Save-Button jetzt in einen Pending-Zustand statt Mehrfachsubmits zu erlauben**: Der Explorer bleibt dadurch ruhiger, wenn Nutzer mehrfach speichern oder Tastatur-Shortcuts drücken. |
| **2.7.311** | 🎨 style | Admin/UI | **Die Dateisuche blendet Ordner jetzt konsistenter anhand sichtbarer Treffer ein oder aus**: Der Dateibaum bleibt bei Filterung lesbarer und zeigt weniger leere Ordnerhüllen. |

---

### v2.7.310 — 27. März 2026 · Audit-Batch 392, Theme-Explorer-View spiegelt Schutzgrenzen sichtbarer

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.310** | 🎨 style | Admin/UI | **`CMS/admin/views/themes/editor.php` zeigt Dateibaum-Limits, übersprungene Einträge und Schutzwarnungen jetzt direkt im Explorer an**: Begrenzungen bleiben damit nicht mehr nur implizit im Modul verborgen. |
| **2.7.310** | 🔴 fix | Admin/UI | **Die Explorer-View markiert Save-Button und Formular jetzt mit expliziten Pending-/Unsaved-Attributen**: Asset und Markup teilen sich damit einen kleineren, stabileren Save-Vertrag. |

---

### v2.7.309 — 27. März 2026 · Audit-Batch 391, ThemeEditorModule begrenzt Dateibaum und Hotspot-Verzeichnisse klarer

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.309** | 🔴 fix | Admin/Themes | **`CMS/admin/modules/themes/ThemeEditorModule.php` begrenzt Theme-Dateibäume jetzt über Gesamtanzahl, Verzeichnisgröße, Tiefe und ausgesparte Hotspot-Segmente wie `vendor`, `node_modules` oder `dist`**: Der Explorer reduziert dadurch weitere I/O- und Render-Ausreißer bei großen Themes. |
| **2.7.309** | 🟡 refactor | Admin/Themes | **Das Modul liefert Baum-Zusammenfassung und Limits jetzt vorbereitet an die View aus**: Explorer-Markup und Asset müssen Schutzgrenzen nicht mehr implizit kennen. |
| **2.7.309** | 🔴 fix | Admin/Themes | **Bearbeitungs- und Pfadauflösung lehnen nun auch ausgesparte Theme-Segmente konsistenter ab**: Schreibzugriffe bleiben dadurch enger am sicheren Edit-Pfad. |

---

### v2.7.308 — 27. März 2026 · Audit-Batch 390, Theme-Explorer an Section-Shell und kleinen Request-Vertrag gehängt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.308** | 🟡 refactor | Admin/Themes | **`CMS/admin/theme-explorer.php` nutzt jetzt den gemeinsamen Section-Shell-Rahmen statt eigenen Redirect-/Flash-/Layout-Boilerplate zu pflegen**: Der Explorer hängt damit näher an denselben Guard-, CSRF- und Post-Handling-Regeln wie andere modernisierte Admin-Entrys. |
| **2.7.308** | 🔴 fix | Admin/Themes | **Aktion, Datei und Inhalt werden vor dem Modul-Dispatch über einen kleineren Payload-Vertrag normalisiert**: Ungültige Save-POSTs landen damit früher im Entry statt lose im Explorer-Pfad zu versickern. |

---

### v2.7.307 — 27. März 2026 · Audit-Batch 389, Font-Manager-UI und Grenzen sichtbarer gemacht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.307** | 🎨 style | Admin/UI | **`CMS/admin/views/themes/fonts.php` zeigt Scan-Limits, übersprungene Dateien und Schutzwarnungen jetzt explizit im Admin an**: Theme-Scans bleiben dadurch transparenter, statt still im Hintergrund an internen Grenzen abzuscheiden. |
| **2.7.307** | 🔴 fix | Admin/UI | **Typografie- und Direktdownload-Formulare spiegeln Zahlen- und Textgrenzen jetzt direkt in ihren Feldern**: Font-Größe, Zeilenhöhe und Google-Font-Namen werden früher sichtbar begrenzt, bevor fehlerhafte Werte erst spät ins Backend laufen. |

---

### v2.7.306 — 27. März 2026 · Audit-Batch 388, Font-Manager-Theme-Scans und Font-Auswahl weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.306** | 🔴 fix | Admin/Themes | **`CMS/admin/modules/themes/FontManagerModule.php` begrenzt Theme-Scans jetzt über Datei-, Größen- und Gesamtvolumen-Limits und überspringt versteckte bzw. große I/O-Hotspots**: Der Font-Scan bleibt dadurch robuster gegen unnötig teure Theme-Verzeichnisse und große Dateien. |
| **2.7.306** | 🟡 refactor | Admin/Themes | **Der Font-Manager liefert Scan-Zusammenfassung, Warnungen und UI-Constraints jetzt vorbereitet aus dem Modul**: View und Entry müssen Limits nicht länger implizit kennen oder selbst zusammensuchen. |
| **2.7.306** | 🔴 fix | Admin/Themes | **Heading-/Body-Font-Zuweisungen werden jetzt gegen tatsächlich auswählbare Font-Keys validiert**: Persistierte Typografie-Einstellungen bleiben damit näher an real verfügbaren System- und lokalen Schriftarten. |

---

### v2.7.305 — 27. März 2026 · Audit-Batch 387, Font-Manager-Request-Vertrag weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.305** | 🔴 fix | Admin/Themes | **`CMS/admin/font-manager.php` normalisiert Save-, Delete- und Download-Payloads jetzt über einen engeren Request-Vertrag**: Font-ID, Google-Font-Name, Font-Keys, Größe und Zeilenhöhe werden vor dem Modul-Dispatch früher begrenzt und validiert. |
| **2.7.305** | 🟡 refactor | Admin/Themes | **Der Font-Manager reicht `saveSettings()` nicht mehr mit rohem `$_POST`, sondern mit vorbereiteten Settings-Daten an das Modul weiter**: Save-Pfade hängen damit sichtbarer an einem kleineren Entry-/Modulvertrag statt an breiten Formular-Payloads. |

---

### v2.7.304 — 27. März 2026 · Audit-Batch 386, Theme-Editor an den gemeinsamen Shell-Vertrag angenähert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.304** | 🟡 refactor | Admin/Themes | **`CMS/admin/theme-editor.php` nutzt jetzt den gemeinsamen Section-Shell-Rahmen statt einen eigenen Header-/Sidebar-/Footer-Sonderpfad**: Der Theme-Editor hängt damit näher an denselben Guard-, Daten- und View-Regeln wie andere modernisierte Admin-Entrys. |
| **2.7.304** | 🔴 fix | Admin/Themes | **Der Theme-Editor bereitet Customizer-Zustand und Fehlergründe jetzt als expliziten Runtime-State auf**: Nicht lesbare Theme-Pfade oder fehlende `admin/customizer.php`-Dateien landen damit nicht mehr als stiller Sonderfall im Entry. |
| **2.7.304** | 🎨 style | Admin/Themes | **`CMS/admin/views/themes/customizer-missing.php` zeigt den Fallback für fehlende bzw. unsichere Customizer-Dateien jetzt als eigene Admin-View mit klaren Folge-Links**: Theme-Verwaltung und Theme-Explorer bleiben dadurch direkt erreichbar, ohne dass der Entry selbst HTML ausgeben muss. |

---

### v2.7.303 — 27. März 2026 · Audit-Batch 385, Medien-Upload- und Settings-Vertrag weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.303** | 🔴 fix | Admin/Media | **`CMS/admin/media.php` begrenzt Upload-Dateinamen, Dateianzahl und Batch-Größe jetzt enger vor dem Modul-Dispatch**: Inkonsistente Upload-Payloads werden früher verworfen und numerische Settings-Felder robuster auf sichere Bereiche geklemmt. |
| **2.7.303** | 🟡 refactor | Admin/Media | **`CMS/admin/modules/media/MediaModule.php` normalisiert Typauswahlen für allgemeine und Member-Uploads jetzt auf echte Mediengruppen zurück und akzeptiert nur reale Upload-Tempdateien**: Settings- und Upload-Pfade hängen damit sichtbarer an einem kleineren, konsistenteren Modulvertrag. |
| **2.7.303** | 🎨 style | Admin/UI | **`CMS/admin/views/media/settings.php` und `CMS/admin/views/media/library.php` spiegeln die engeren Zahlen- und Textgrenzen jetzt direkt im Formular wider**: Medien-Settings und Bibliotheksfilter geben Grenzwerte früher sichtbar vor, statt Fehler erst spät aus dem Backend zurückzubekommen. |

---

### v2.7.302 — 27. März 2026 · Audit-Batch 384, Legal-Sites-POST-State und Guard-Vertrag weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.302** | 🔴 fix | Admin/Legal | **`CMS/admin/legal-sites.php` normalisiert rechtliche HTML-Felder jetzt bereits im Entry defensiver und hält fehlgeschlagene Save-Payloads für den nächsten Render in Session-State zusammen**: Formularinhalte und Seitenzuordnungen springen damit bei Validierungsfehlern nicht mehr lose auf den alten Persistenzstand zurück. |
| **2.7.302** | 🟡 refactor | Admin/Legal | **Der Legal-Sites-Entry bündelt Aktion, Fehler und Payload jetzt über einen kleinen Request-Vertrag statt mehrfach verteilter Template-Type-Prüfungen**: POST-Fehlerpfade bleiben damit kompakter und näher am Section-Shell-Muster. |
| **2.7.302** | 🔴 fix | Admin/Legal | **`CMS/admin/modules/legal/LegalSitesModule.php` erzwingt Admin- plus `manage_settings`-Capability jetzt auch im Modul selbst**: Der Legal-Sites-Pfad verlässt sich damit nicht nur auf den Entry-Guard, sondern zieht die Berechtigungsgrenze zusätzlich an der Modul-Tür nach. |

---

### v2.7.301 — 27. März 2026 · Audit-Batch 383, Theme-Explorer-Dateimetadaten und Save-Grenzen sichtbarer gemacht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.301** | 🟡 refactor | Admin/Themes | **`CMS/admin/modules/themes/ThemeEditorModule.php` liefert Dateigröße, Erweiterung, Schreibbarkeit und Save-Sperrgründe jetzt als vorbereitete Dateimetadaten an die View**: Der Explorer muss Bearbeitbarkeitsregeln nicht länger stillschweigend nur im Save-Pfad verstecken. |
| **2.7.301** | 🔴 fix | Admin/Themes | **`CMS/admin/views/themes/editor.php` deaktiviert Speichern und setzt den Editor auf `readonly`, wenn eine Datei zwar angezeigt, aber nicht sicher bearbeitet werden kann**: Oversize- oder schreibgeschützte Dateien landen damit nicht mehr erst beim Submit in einem vermeidbaren Fehlpfad. |
| **2.7.301** | 🎨 style | Admin/UI | **Der Theme-Explorer zeigt Erweiterung, Dateigröße und Bearbeitbarkeitsstatus jetzt direkt im Editor-Kopf an**: Dateityp- und Schreibstatus werden damit sichtbarer statt als implizites Backend-Wissen verborgen zu bleiben. |

---

### v2.7.300 — 27. März 2026 · Audit-Batch 382, Marketplace-Quellen und Fallback-Hinweise weiter geglättet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.300** | 🔴 fix | Admin/Marketplace | **`CMS/admin/modules/plugins/PluginMarketplaceModule.php` und `CMS/admin/modules/themes/ThemeMarketplaceModule.php` melden Remote-, Local-Fallback- und Ausfallstatus jetzt explizit an ihre Views zurück**: Marketplace-Fehlerpfade bleiben damit nicht mehr stumm, wenn auf lokalen Index statt auf Remote-Katalog umgeschaltet wird. |
| **2.7.300** | 🟡 refactor | Admin/Marketplace | **Die Module halten Quellenstatus und Quellen-URL jetzt als kleinen ViewModel-Baustein im Datenvertrag**: Registry- und Katalog-Herkunft müssen im Template nicht länger indirekt aus leeren Listen erraten werden. |
| **2.7.300** | 🎨 style | Admin/UI | **`CMS/admin/views/plugins/marketplace.php` und `CMS/admin/views/themes/marketplace.php` zeigen die aktuell genutzte Quelle jetzt als Flash-Hinweis mit Quellenangabe an**: Admin-Nutzer sehen damit sofort, ob Remote-Daten geladen oder ein lokaler Fallback verwendet wurde. |

---

### v2.7.299 — 27. März 2026 · Audit-Batch 381, Theme-Explorer-Interaktionen in dediziertes Asset gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.299** | 🔴 fix | Admin/Themes | **`CMS/admin/theme-explorer.php` bindet den Theme-Explorer jetzt über ein dediziertes Admin-Asset statt über lokales Inline-Script an**: Der Editor-Pfad hält Keyboard-Shortcuts und Laufzeitlogik damit konsistenter am Asset-Vertrag. |
| **2.7.299** | 🟡 refactor | Admin/Themes | **`CMS/admin/views/themes/editor.php` liefert Suche, Dateibaum-Markierungen und Editor-Konfiguration jetzt vorbereiteter an `CMS/assets/js/admin-theme-explorer.js`**: Das Theme-Template reduziert weiteren Laufzeit-Boilerplate und trennt Markup klarer von Interaktionen. |
| **2.7.299** | 🎨 style | Assets/Admin | **`CMS/assets/js/admin-theme-explorer.js` ergänzt Dateifilter, Tab-Einrückung und `Ctrl+S`-Speichern zentral**: Der Theme-Explorer bleibt damit auch bei größeren Dateibäumen fokussierter bedienbar. |

---

### v2.7.298 — 27. März 2026 · Audit-Batch 380, Landing-POST- und Tab-Vertrag weiter gestaffelt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.298** | 🔴 fix | Admin/Landing | **`CMS/admin/landing-page.php` normalisiert Aktion, Feature-ID, aktiven Tab und Plugin-ID jetzt enger in einem gemeinsamen Payload**: Fehler und Redirect-Ziele bleiben dadurch robuster an einem kleinen Entry-Vertrag statt an verstreuten Spezialfällen hängen. |
| **2.7.298** | 🟡 refactor | Admin/Landing | **Der Landing-Entry leitet nach POSTs jetzt deterministisch zurück in den zugehörigen Tab**: Header-, Content-, Footer-, Design- und Plugin-Änderungen springen damit nicht mehr lose auf GET-Fallbacks zurück. |
| **2.7.298** | 🎨 style | Admin/Landing | **`CMS/admin/views/landing/page.php` übergibt den aktiven Tab jetzt in allen Formularen explizit mit**: Die Landing-UI hält ihren Zustand dadurch auch bei Fehlern und Feature-/Plugin-Aktionen sichtbar stabiler. |

---

### v2.7.297 — 27. März 2026 · Audit-Batch 379, Kommentar-KPIs und Zeilenaktionen weiter vorbereitet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.297** | 🟡 refactor | Admin/Comments | **`CMS/admin/modules/comments/CommentsModule.php` liefert KPI-Karten und zeilenabhängige Aktionsmodelle jetzt vorbereitet an die View**: Die Kommentar-Moderation hängt damit weniger an View-seitigen Status- und Rechte-Verzweigungen. |
| **2.7.297** | 🔴 fix | Admin/Comments | **`CMS/admin/views/comments/list.php` rendert KPI-Karten und Dropdown-Aktionen jetzt aus vorbereiteten ViewModels**: Das Template reduziert weiteren Moderations-Boilerplate und bleibt robuster gegen künftige Status-/Rechte-Erweiterungen. |
| **2.7.297** | 🎨 style | Admin/UI | **Die Kommentar-Liste hält Icon-, Badge- und Action-Darstellung klarer an einem kleineren Datenvertrag**: KPI- und Zeilen-UI wirken dadurch konsistenter zwischen Modul und Markup. |

---

### v2.7.296 — 27. März 2026 · Audit-Batch 378, Tabellen-Editor bei Größenlimits und Save-Vertrag gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.296** | 🔴 fix | Admin/Tables | **`CMS/admin/modules/tables/TablesModule.php` validiert Tabellenname, Beschreibung, Spalten- und Zeilenstruktur jetzt restriktiver**: Der Save-Pfad begrenzt Spalten-, Zeilen- und Zellgrößen robuster und normalisiert Editor-Daten vor dem Persistieren. |
| **2.7.296** | 🟠 perf | Admin/Tables | **`CMS/admin/site-tables.php`, `CMS/admin/views/tables/edit.php` und `CMS/assets/js/admin-site-tables.js` ziehen Größenlimits und Redirects enger zusammen**: Übergroße JSON-Payloads werden früher abgefangen, Save-Fehler landen wieder im Editor und die UI verhindert unnötige Überläufe bereits clientseitig. |
| **2.7.296** | 🟡 refactor | Admin/Tables | **Die Tabellen-Bearbeitung liefert Editor-Zusammenfassung, Limits und JSON-Konfiguration jetzt vorbereiteter aus dem Modulvertrag**: View und Asset müssen den Editorzustand dadurch nicht länger ad hoc selbst zusammenstecken. |

---

### v2.7.295 — 27. März 2026 · Audit-Batch 377, Menü-Editor bei Picker- und Item-Vertrag weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.295** | 🔴 fix | Admin/Menus | **`CMS/admin/menu-editor.php` bündelt Aktions-, Menü-ID- und Payload-Fehler jetzt enger in einem gemeinsamen Request-Vertrag**: Der Entry verwirft damit übergroße Item-Payloads und ungültige Kontrollwerte früher, bevor sie in den Modul-Dispatch laufen. |
| **2.7.295** | 🟡 refactor | Admin/Menus | **`CMS/admin/modules/menus/MenuEditorModule.php` validiert Menü-Namen, Theme-Positionen, Item-URLs, Parent-Beziehungen und doppelte Editor-IDs jetzt restriktiver und liefert vorbereitete Page-Picker-/Editor-Konfiguration an die View**: Der Menü-Editor hängt damit weniger an rohen View-Ableitungen und schützt Save-/Sync-Pfade robuster gegen inkonsistente Item-Strukturen. |
| **2.7.295** | 🎨 style | Admin/UI | **`CMS/admin/views/menus/editor.php` und `CMS/assets/js/admin-menu-editor.js` nutzen vorbereitete Page-Picker-Daten, klarere URL-/Titel-Validierung und einen kompakteren Seiten-Übernahme-Flow**: Die Menü-UI bleibt dadurch übersichtlicher, vermeidet lose Seiten-Button-Listen und gibt Fehler früher direkt im Editor zurück. |

---

### v2.7.294 — 27. März 2026 · Audit-Batch 376, Landing-ViewModels weiter in den Modulvertrag gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.294** | 🟡 refactor | Admin/Landing | **`CMS/admin/modules/landing/LandingPageModule.php` bereitet Content-Typen, Feature-Karten und Plugin-Karten jetzt stärker als ViewModels auf**: Der Landing-Admin hängt damit weniger an rohen Service-Strukturen und verstreuten Template-Ableitungen. |
| **2.7.294** | 🔴 fix | Admin/Landing | **`CMS/admin/views/landing/page.php` nutzt vorkonfigurierte Plugin-Karten statt roher Override-Zugriffe und zeigt Plugin-Metadaten konsistenter an**: Der Plugins-Tab hängt damit nicht länger an einer nicht gesicherten `label`-Annahme und reduziert Restlogik im Template. |
| **2.7.294** | 🎨 style | Admin/Landing | **Die Content- und Plugin-Tabs der Landing-Ansicht rendern vorbereitete Auswahl- und Kartenmodelle klarer**: Inhalts-Typen, Feature-Löschzustände und Plugin-Zielbereiche werden damit sichtbarer über kleine View-Daten statt ad hoc im Template zusammengesetzt. |

---

### v2.7.293 — 27. März 2026 · Audit-Batch 375, Theme-Marketplace an den Wrapper-/Feedback-Vertrag angeglichen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.293** | 🔴 fix | Admin/Marketplace | **`CMS/admin/theme-marketplace.php` bündelt Aktion, Theme-Slug und Fehlermeldung jetzt über einen kleinen Payload-Vertrag**: Der Entry reduziert damit weitere verteilte Einzelfallprüfungen und bleibt näher am gemeinsamen Section-Shell-Muster. |
| **2.7.293** | 🟡 refactor | Admin/Themes | **`CMS/admin/modules/themes/ThemeMarketplaceModule.php` liefert vorbereitete Statusmetriken und Statusfilter für die View und schützt das Zielverzeichnis zusätzlich gegen inkonsistente Installationspfade**: Statistik-, Filter- und Installationspfade hängen damit sichtbarer an einem konsistenten Modulvertrag. |
| **2.7.293** | 🎨 style | Admin/UI | **`CMS/admin/views/themes/marketplace.php` und `CMS/assets/js/admin-theme-marketplace.js` nutzen vorbereitete Filterdaten und sperren bestätigte Install-Buttons bis zum Submit**: Die Theme-Marketplace-UI vermeidet doppelte Klicks und trennt Filter-/Feedbacklogik klarer vom Template. |

---

### v2.7.292 — 27. März 2026 · Audit-Batch 374, Plugin-Marketplace-Wrapper und Feedbackpfade verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.292** | 🔴 fix | Admin/Marketplace | **`CMS/admin/plugin-marketplace.php` bündelt Aktion, Slug und Fehlermeldung jetzt über einen kleinen Payload-Vertrag statt mehrfach verteilter Einzelfallprüfungen**: Der Entry bleibt damit näher am gemeinsamen Section-Shell-Muster und hält seine Installationsfehler kompakter. |
| **2.7.292** | 🟡 refactor | Admin/Plugins | **`CMS/admin/modules/plugins/PluginMarketplaceModule.php` liefert vorbereitete Kategorie-/Statusfilter und schützt das Zielverzeichnis zusätzlich vor inkonsistenten Installationspfaden**: Statistik-, Filter- und Installationspfade hängen damit sichtbarer an einem gemeinsamen Modulvertrag. |
| **2.7.292** | 🎨 style | Admin/UI | **`CMS/admin/views/plugins/marketplace.php` und `CMS/assets/js/admin-plugin-marketplace.js` ziehen Filteroptionen aus vorbereiteten View-Daten und sperren den Install-Button nach Bestätigung bis zum Submit**: Die Marketplace-UI vermeidet doppelte Klicks und hält Such-/Status-Logik klarer getrennt vom Template. |

---

### v2.7.291 — 27. März 2026 · Audit-Batch 373, Landing-Badge konfigurierbar gemacht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.291** | 🟢 feat | Admin/Landing | **`CMS/admin/views/landing/page.php` ergänzt im Header-Tab ein frei pflegbares Badge-Feld**: Redakteure können damit statt der starren Versionszahl beliebigen Text wie „Beta“, „Neu“ oder einen Release-Hinweis hinterlegen. |
| **2.7.291** | 🔴 fix | Core/Landing | **`CMS/admin/modules/landing/LandingPageModule.php`, `CMS/core/Services/Landing/LandingHeaderService.php`, `CMS/core/Services/Landing/LandingDefaultsProvider.php` und `CMS/install/InstallerService.php` tragen den Badge-Wert jetzt konsistent durch den Header-Vertrag**: Bestehende Landing-Daten bleiben kompatibel, weil fehlende Badge-Werte zunächst aus der bisherigen Versionsanzeige abgeleitet werden. |
| **2.7.291** | 🎨 style | Frontend/Landing | **`CMS/themes/cms-default/partials/home-landing.php` rendert das Hero-Badge nur noch bei hinterlegtem Text und entfernt den harten `v`-Prefix**: Leere Badge-Felder führen dadurch automatisch zu einem sauberen Hero ohne Badge-Restmarkup. |

---

### v2.7.290 — 27. März 2026 · Audit-Batch 372, Wrapper- und Theme-Editor-Verträge weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.290** | 🔴 fix | Admin/Comments & Landing | **`CMS/admin/comments.php`, `CMS/admin/modules/comments/CommentsModule.php`, `CMS/admin/views/comments/list.php`, `CMS/admin/landing-page.php` und `CMS/admin/views/landing/page.php` ziehen Status-, Tab- und View-Helfer enger an kleine Wrapper-/ViewModel-Verträge**: Kommentar-Statusfilter hängen nicht länger implizit an `$_GET` im Modul, und Landing-Tabs/Formularwerte kommen vorbereiteter in der View an. |
| **2.7.290** | 🟠 perf | Admin/Menus & Tables | **`CMS/admin/modules/menus/MenuEditorModule.php`, `CMS/admin/menu-editor.php`, `CMS/admin/modules/tables/TablesModule.php` und `CMS/admin/site-tables.php` reduzieren Bootstrap-, Such- und Redirect-Sonderpfade**: Theme-Sync läuft nur noch gezielt, neue Menüs springen sauber in den Editor zurück, und Tabellenlisten/Fehlerpfade hängen klarer am Wrapper-/Modulvertrag statt an verstreuten Direktzugriffen. |
| **2.7.290** | 🟡 refactor | Admin/Themes | **`CMS/admin/theme-editor.php`, `CMS/admin/theme-explorer.php`, `CMS/admin/modules/themes/ThemeEditorModule.php` und `CMS/admin/views/themes/editor.php` härten Capability- und Pfadgrenzen weiter**: Der aktive Theme-Customizer wird nur noch über einen verifizierten Theme-Pfad eingebunden, Hidden-Segmente werden aus Dateibaum und Safe-Path-Auflösung ausgeschlossen und der Explorer hält seine Baum-/Link-Helfer kompakter. |

---

### v2.7.289 — 27. März 2026 · Audit-Batch 371, Marketplace-Härtung und View-Restpfade nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.289** | 🔴 fix | Admin/Themes | **`CMS/admin/theme-marketplace.php` und `CMS/admin/modules/themes/ThemeMarketplaceModule.php` normalisieren Theme-Slugs, Registry-/Manifest-Daten und HTTPS-Marketplace-URLs jetzt restriktiver**: Kollidierende bzw. doppelte Theme-Slugs werden robuster verworfen, Asset-/Download-Pfade enger auf erlaubte Hosts begrenzt. |
| **2.7.289** | 🟠 perf | Admin/Marketplace | **`CMS/admin/views/themes/marketplace.php`, `CMS/assets/js/admin-theme-marketplace.js`, `CMS/admin/views/plugins/marketplace.php` und `CMS/assets/js/admin-plugin-marketplace.js` ergänzen KPI-Karten, Such-/Statusfilter und Empty-State-Handling**: Größere Marketplace-Kataloge bleiben damit im Admin fokussierter filterbar und manuelle Kandidaten sichtbarer getrennt. |
| **2.7.289** | 🟡 refactor | Admin/Landing & Views | **`CMS/admin/modules/landing/LandingPageModule.php` initialisiert Defaults nur noch lazy, während Kommentar-, Menü- und Tabellen-Views zusätzliche Helper-/JSON-Vorbereitung erhalten**: Konstruktor-Seiteneffekte sinken, und Template-/Escaping-Grenzen bleiben konsistenter vorbereitet. |

---

### v2.7.288 — 27. März 2026 · Audit-Batch 370, Core-Modulverwaltung für Abointegration ergänzt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.288** | 🔴 fix | Admin/Content | **`CMS/admin/pages.php` und `CMS/admin/posts.php` importieren `CMS\\Security` wieder korrekt**: Der produktive Fatal Error bei der Editor-/Media-Token-Erzeugung entfällt damit in beiden Admin-Entrys. |
| **2.7.288** | 🟢 feat | Core/System | **`CMS/core/Services/CoreModuleService.php` führt eine zentrale Registry für integrierte Kernmodule ein**: Abo-Core, Limits, Member-Abo-Bereich sowie Admin-Unterbereiche können jetzt mit Abhängigkeiten und Legacy-Setting-Sync zentral gesteuert werden. |
| **2.7.288** | 🟡 refactor | Admin/System | **`System -> Module` ergänzt eine echte Core-Modulverwaltung und bindet Sidebar, Admin-Gates, Dashboard sowie Member-Pfade daran an**: Deaktivierte Abo-Bereiche verschwinden sichtbar aus dem Admin und werden in ihren Laufzeitpfaden wirksam abgeschaltet. |

---

### v2.7.287 — 26. März 2026 · Audit-Batch 369, Plugin-Marketplace-Katalog weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.287** | 🔴 fix | Admin/Plugins | **`CMS/admin/modules/plugins/PluginMarketplaceModule.php` verwirft jetzt kollidierende Manifest-Slugs und dedupliziert doppelte Katalogeinträge pro Plugin-Slug**: Der Marketplace hält seinen Remote-Katalogpfad damit konsistenter, bevor Download- und Update-URLs weiter aufgelöst werden. |
| **2.7.287** | 🟠 perf | Admin/Plugins | **Installierte Plugin-Verzeichnisse werden für den Marketplace jetzt als normalisierte Slug-Map aufgebaut statt linear gegen rohe Ordnernamen geprüft**: Die Installationsmarkierung spart dadurch unnötige Mehrfach-Lookups über den Katalog. |
| **2.7.287** | 🟡 refactor | Admin/Plugins | **Manifestdaten fließen nur noch als skalare allowlist-basierte Metadaten in den Katalog ein**: Das hält den Marketplace-Vertrag klarer zwischen Registry, Manifest und finaler Installationsentscheidung getrennt. |

---

### v2.7.286 — 26. März 2026 · Audit-Batch 368, Error-Report-Vertrag weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.286** | 🔴 fix | Admin/System | **`CMS/admin/error-report.php` reicht Fehlerreports jetzt nur noch als gezielt normalisierte Payloads weiter**: Titel, Nachricht, Fehlercode, Source-URL sowie JSON-Felder werden vor dem Service-Dispatch enger begrenzt und von Steuerzeichen bereinigt. |
| **2.7.286** | 🟠 perf | Core/Services | **`CMS/core/Services/ErrorReportService.php` normalisiert Status, Source-URL sowie verschachtelte `error_data`-/`context`-Payloads jetzt zusätzlich selbst**: Der Service bleibt damit robuster, auch wenn künftige Aufrufer nicht aus dem Admin-Wrapper kommen. |
| **2.7.286** | 🟡 refactor | Admin/System | **Der Fehlerreport trennt Wrapper- und Service-Trust-Boundary klarer**: Request-Normalisierung und persistente Report-Sanitierung hängen sichtbarer an einem gemeinsamen Payload-Vertrag statt an lose verteilten Einzelpfaden. |

---

### v2.7.285 — 26. März 2026 · Audit-Batch 367, Roles-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.285** | 🔴 fix | Admin/Users | **`CMS/admin/views/users/roles.php` verzichtet bei Gruppen-Toggles sowie Role-/Capability-Modals auf lokales Inline-Script**: Die View hängt ihre Interaktionen jetzt an Datenattribute und ein gemeinsames Users-Admin-Asset. |
| **2.7.285** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-users.js` übernimmt Role-/Capability-Modalbefüllung und Gruppen-Toggle zentral**: Die Rollenverwaltung hält ihre Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.285** | 🟡 refactor | Admin/Users | **Die Rollen-Ansicht trennt Markup, Modalzustand und Rechte-Interaktionen klarer**: Das hält die Benutzerverwaltung konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.284 — 26. März 2026 · Audit-Batch 366, Roles-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.284** | 🔴 fix | Admin/Users | **`CMS/admin/roles.php` normalisiert erlaubte Aktionen jetzt einmalig und dispatcht über einen gemeinsamen Action-Helper**: Der POST-Pfad validiert Kontrollwerte damit klarer vor Rollen- und Capability-Mutationen. |
| **2.7.284** | 🟠 perf | Admin/Users | **Der Roles-Entry bindet `CMS/assets/js/admin-users.js` sauber über `pageAssets` ein**: Modal- und Toggle-Interaktionen hängen damit sichtbarer am gemeinsamen Admin-Asset-Vertrag. |
| **2.7.284** | 🟡 refactor | Admin/Users | **`cms_admin_roles_handle_action()` arbeitet direkt mit dem vorbereiteten Payload**: Rollen- und Rechte-Aktionen bleiben dadurch klarer an einer kleinen Entry-Logik gebündelt. |

---

### v2.7.283 — 26. März 2026 · Audit-Batch 365, Users-List-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.283** | 🔴 fix | Admin/Users | **`CMS/admin/views/users/list.php` verzichtet bei Rollen-/Status-Filtern auf lokale Inline-`onchange`-Handler und liefert Grid-Konfiguration per JSON**: Die Listenansicht hängt ihre Filter- und Grid-Initialisierung jetzt an ein dediziertes Admin-Asset. |
| **2.7.283** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-users.js` übernimmt Users-Grid und Filter-Redirects zentral**: Die Benutzerliste hält ihre Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.283** | 🟡 refactor | Admin/Users | **Die Benutzer-Liste trennt Markup, Filterzustand und Grid-Konfiguration klarer**: Das hält die Benutzerverwaltung konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.282 — 26. März 2026 · Audit-Batch 364, Users-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.282** | 🔴 fix | Admin/Users | **`CMS/admin/users.php` bündelt Aktion, ID, Bulk-Aktion und Bulk-IDs jetzt einmalig in einem normalisierten Payload**: Der POST-Pfad validiert Kontrollfelder damit klarer vor Save-, Delete- und Bulk-Dispatch. |
| **2.7.282** | 🟠 perf | Admin/Users | **Der Users-Entry baut die Listen-Grid-Konfiguration jetzt über `cms_admin_users_grid_config()` und bindet `CMS/assets/js/admin-users.js` sauber ein**: Grid- und Filter-Interaktionen hängen damit sichtbarer am gemeinsamen Admin-Asset-Vertrag. |
| **2.7.282** | 🟡 refactor | Admin/Users | **Die Benutzerverwaltung trennt Listen-Konfiguration, Payload-Normalisierung und Dispatch klarer**: Das hält Entry-, View- und Asset-Vertrag kompakter und konsistenter. |

---

### v2.7.281 — 26. März 2026 · Audit-Batch 363, Tables-Edit-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.281** | 🔴 fix | Admin/Tables | **`CMS/admin/views/tables/edit.php` verzichtet beim Spalten-/Zeilen-Editor auf lokales Inline-Script und generierte Inline-Handler**: Die View liefert den Editorzustand jetzt per JSON-Konfiguration an ein dediziertes Tabellen-Asset. |
| **2.7.281** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-site-tables.js` übernimmt den Tabellen-Editor für Spalten, Zeilen und JSON-Serialisierung zentral**: Die Bearbeitungsansicht hält ihre Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.281** | 🟡 refactor | Admin/Tables | **Die Tabellen-Bearbeitung trennt Markup, Editorzustand und Mutationslogik klarer**: Das hält die Tabellenverwaltung konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.280 — 26. März 2026 · Audit-Batch 362, Tables-List-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.280** | 🔴 fix | Admin/Tables | **`CMS/admin/views/tables/list.php` verzichtet bei Suche, Duplicate- und Delete-Aktionen auf lokale Inline-Handler**: Die Listenansicht liefert ihre Interaktionen jetzt über Datenattribute an ein gemeinsames Tabellen-Asset. |
| **2.7.280** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-site-tables.js` übernimmt Such-Redirect sowie Duplicate-/Delete-Flow der Tabellenliste zentral**: Die Listenansicht hält ihre Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.280** | 🟡 refactor | Admin/Tables | **Die Tabellen-Liste trennt Markup, Suchzustand und Aktionslogik klarer**: Das hält die Tabellenverwaltung konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.279 — 26. März 2026 · Audit-Batch 361, Site-Tables-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.279** | 🔴 fix | Admin/Tables | **`CMS/admin/site-tables.php` normalisiert Aktion und Tabellen-ID jetzt einmalig in einem gemeinsamen Payload**: Der POST-Pfad validiert Kontrollfelder damit klarer vor Save-/Delete-/Duplicate-Dispatch. |
| **2.7.279** | 🟠 perf | Admin/Tables | **Der Site-Tables-Entry bindet `CMS/assets/js/admin-site-tables.js` für Listen- und Edit-Ansicht sauber über `pageAssets` ein**: Tabellen-Interaktionen hängen damit sichtbarer am gemeinsamen Admin-Asset-Vertrag. |
| **2.7.279** | 🟡 refactor | Admin/Tables | **`cms_admin_site_tables_handle_action()` arbeitet direkt mit dem vorbereiteten Payload**: Save-, Delete- und Duplicate-Pfade bleiben dadurch klarer voneinander getrennt. |

---

### v2.7.278 — 26. März 2026 · Audit-Batch 360, Menu-Editor-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.278** | 🔴 fix | Admin/Menus | **`CMS/admin/views/menus/editor.php` verzichtet bei Modal- und Item-Editor-Aktionen auf lokales Inline-Script und lokale Inline-Handler**: Die View liefert Menüzustand und Trigger jetzt über Datenattribute plus JSON-Konfiguration an ein dediziertes Asset. |
| **2.7.278** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-menu-editor.js` übernimmt Modal-Befüllung, Item-Rendering und Menü-Delete-Confirm zentral**: Der Menü-Editor hält seine Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.278** | 🟡 refactor | Admin/Menus | **Der Menü-Editor trennt Markup, Modalzustand und Item-Struktur klarer**: Das hält die Navigationsverwaltung konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.277 — 26. März 2026 · Audit-Batch 359, Menu-Editor-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.277** | 🔴 fix | Admin/Menus | **`CMS/admin/menu-editor.php` normalisiert Aktion, Menü-ID und Item-JSON jetzt einmalig in einem gemeinsamen Payload**: Der Entry pflegt dadurch keine separate Handler-Map mehr für seinen POST-Pfad. |
| **2.7.277** | 🟠 perf | Admin/Menus | **Der Menu-Editor-Entry bindet `CMS/assets/js/admin-menu-editor.js` sauber über `page_assets` ein**: Modal- und Item-Editor-Pfade hängen damit sichtbarer am gemeinsamen Admin-Asset-Vertrag. |
| **2.7.277** | 🟡 refactor | Admin/Menus | **`cms_admin_menu_editor_handle_action()` dispatcht direkt über den vorbereiteten Payload**: Guard, Menü-ID-Prüfung und Modul-Aufruf bleiben dadurch klarer an einer kleinen Entry-Logik. |

---

### v2.7.276 — 26. März 2026 · Audit-Batch 358, Hub-Templates-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.276** | 🔴 fix | Admin/Hub | **`CMS/admin/views/hub/templates.php` verzichtet bei Duplizieren-/Löschen-Aktionen auf lokale Inline-`onclick`-Handler**: Die View liefert Template-Aktionen jetzt über Datenattribute an ein gemeinsames Hub-Asset. |
| **2.7.276** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-hub-sites.js` übernimmt Duplicate-/Delete-Flow für Hub-Templates zentral**: Die Template-Bibliothek hält ihre Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.276** | 🟡 refactor | Admin/Hub | **Die Hub-Template-Liste trennt Markup, Formularzustand und Template-Aktionen klarer**: Das hält die Hub-Verwaltung konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.275 — 26. März 2026 · Audit-Batch 357, Hub-List-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.275** | 🔴 fix | Admin/Hub | **`CMS/admin/views/hub/list.php` verzichtet bei Suche, Copy-, Duplicate- und Delete-Aktionen auf lokale Inline-Handler**: Die View liefert Listeninteraktionen jetzt über Datenattribute an ein gemeinsames Hub-Asset. |
| **2.7.275** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-hub-sites.js` übernimmt Suche, Clipboard-Flow sowie Site-Duplicate-/Delete-Aktionen zentral**: Die Hub-Liste hält ihre Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.275** | 🟡 refactor | Admin/Hub | **Die Hub-Site-Liste trennt Markup, Suchzustand und Aktionslogik klarer**: Das hält die Routing-Verwaltung konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.274 — 26. März 2026 · Audit-Batch 356, Hub-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.274** | 🔴 fix | Admin/Hub | **`CMS/admin/hub-sites.php` normalisiert Aktion, Hub-Site-ID und Template-Key jetzt einmalig in einem gemeinsamen Payload**: Der Entry validiert Kontrollfelder damit klarer vor Delete-/Duplicate- und Template-Dispatch. |
| **2.7.274** | 🟠 perf | Admin/Hub | **Der Hub-Entry bindet `CMS/assets/js/admin-hub-sites.js` für Listen- und Template-Ansicht jetzt sauber über `pageAssets` ein**: Hub-Sites und Templates hängen damit sichtbarer am gemeinsamen Admin-Asset-Vertrag. |
| **2.7.274** | 🟡 refactor | Admin/Hub | **`cms_admin_hub_sites_handle_action()` arbeitet direkt mit dem vorbereiteten Payload**: Site- und Template-Aktionen bleiben dadurch klarer voneinander getrennt. |

---

### v2.7.273 — 26. März 2026 · Audit-Batch 355, Comments-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.273** | 🔴 fix | Admin/Comments | **`CMS/admin/views/comments/list.php` verzichtet bei Status- und Delete-Aktionen auf lokale Inline-`onclick`-Handler**: Die View hängt Kommentaraktionen jetzt über Datenattribute an ein dediziertes Admin-Asset. |
| **2.7.273** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-comments.js` übernimmt Status-Dispatch, Delete-Confirm sowie Bulk-Bar-/Dropdown-Verhalten zentral**: Die Kommentarverwaltung hält ihre Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.273** | 🟡 refactor | Admin/Comments | **Die Kommentar-Liste trennt Markup, Formularzustand und Moderationslogik klarer**: Das hält die Moderation konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.272 — 26. März 2026 · Audit-Batch 354, Comments-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.272** | 🔴 fix | Admin/Comments | **`CMS/admin/comments.php` bündelt Aktion, Kommentar-ID, Zielstatus, Bulk-Aktion und Bulk-IDs jetzt einmalig in einem normalisierten Payload**: Der POST-Pfad validiert Kontrollfelder damit klarer vor dem Modul-Dispatch. |
| **2.7.272** | 🟠 perf | Admin/Comments | **Der Comments-Entry bindet sein dediziertes UI-Asset jetzt sauber über `page_assets` ein**: Moderations- und Bulk-UI hängen damit sichtbarer am gemeinsamen Admin-Asset-Vertrag. |
| **2.7.272** | 🟡 refactor | Admin/Comments | **`cms_admin_comments_handle_action()` arbeitet direkt mit dem vorbereiteten Payload**: Status-, Delete- und Bulk-Pfade bleiben dadurch klarer voneinander getrennt. |

---

### v2.7.271 — 26. März 2026 · Audit-Batch 353, Groups-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.271** | 🔴 fix | Admin/Users | **`CMS/admin/views/users/groups.php` verzichtet bei Modal- und Delete-Aktionen auf lokale Inline-`onclick`-Handler**: Die View liefert jetzt Datenattribute für Edit-/Delete-Interaktionen statt eigener Script-Inseln. |
| **2.7.271** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-user-groups.js` übernimmt Modal-Befüllung und Delete-Confirm der Gruppenverwaltung zentral**: Die Gruppen-UI hält ihre Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.271** | 🟡 refactor | Admin/Users | **Die Gruppen-Ansicht trennt Kartendarstellung, Modalzustand und Delete-Flow klarer**: Das hält die Benutzerverwaltung konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.270 — 26. März 2026 · Audit-Batch 352, Groups-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.270** | 🔴 fix | Admin/Users | **`CMS/admin/groups.php` normalisiert Aktion und Gruppen-ID jetzt einmalig in einem gemeinsamen Payload**: Der Entry pflegt dadurch keine separate Handler-Map mehr für seinen POST-Pfad. |
| **2.7.270** | 🟠 perf | Admin/Users | **Der Gruppen-Entry bindet sein dediziertes UI-Asset jetzt sauber über `page_assets` ein**: Modal- und Delete-Pfade hängen damit sichtbarer am gemeinsamen Admin-Asset-Vertrag. |
| **2.7.270** | 🟡 refactor | Admin/Users | **`cms_admin_groups_handle_action()` dispatcht direkt über den vorbereiteten Payload**: Guard, ID-Prüfung und Modul-Aufruf bleiben dadurch klarer an einer kleinen Entry-Logik. |

---

### v2.7.269 — 26. März 2026 · Audit-Batch 351, Marketplace-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.269** | 🔴 fix | Admin/Plugins | **`CMS/admin/views/plugins/marketplace.php` pflegt Such-/Filter-Logik und Install-Confirm nicht länger über lokales Inline-Script bzw. Inline-`confirm(...)`**: Die View liefert jetzt JSON-Konfiguration und `data-confirm-*` statt eigener Script-Inseln. |
| **2.7.269** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-plugin-marketplace.js` übernimmt Suche und Kategorie-Filter des Marketplace zentral**: Die Plugin-Karten-UI bleibt dadurch näher an einem dedizierten Admin-Asset statt im PHP-View. |
| **2.7.269** | 🟡 refactor | Admin/Plugins | **Der Marketplace-View trennt Markup, Filterzustand und Installations-Confirm klarer**: Das hält die UI konsistenter zu anderen bereits modernisierten Admin-Ansichten. |

---

### v2.7.268 — 26. März 2026 · Audit-Batch 350, Marketplace-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.268** | 🔴 fix | Admin/Plugins | **`CMS/admin/plugin-marketplace.php` normalisiert Aktion und Slug jetzt gemeinsam in einem Payload**: Der Entry hält seinen POST-Pfad damit kompakter und re-normalisiert Installationsdaten nicht länger im Dispatch. |
| **2.7.268** | 🟠 perf | Admin/Plugins | **Der Marketplace-Entry bindet sein dediziertes UI-Asset jetzt sauber über `page_assets` ein**: Such-/Filter- und Confirm-Pfade hängen damit sichtbarer am gemeinsamen Admin-Asset-Vertrag. |
| **2.7.268** | 🟡 refactor | Admin/Plugins | **`cms_admin_plugin_marketplace_handle_action()` arbeitet direkt mit dem vorbereiteten Payload**: Guard, Aktionsprüfung und Modul-Dispatch bleiben dadurch klarer voneinander getrennt. |

---

### v2.7.267 — 26. März 2026 · Audit-Batch 349, Pages-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.267** | 🔴 fix | Admin/Pages | **`CMS/admin/views/pages/list.php` verzichtet auf lokales Bulk-/Grid-Script und Inline-`onchange`-Handler der Filterform**: Die View liefert stattdessen JSON-Konfiguration und CSS-/JS-Hooks für das dedizierte Asset. |
| **2.7.267** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-pages.js` übernimmt Grid-Initialisierung, Bulk-Bar und Filter-Auto-Submit zentral**: Die Seitenliste hält ihre Laufzeitlogik dadurch nicht länger direkt im PHP-Template. |
| **2.7.267** | 🟡 refactor | Admin/Pages | **Die Pages-Liste trennt Markup, Grid-Konfiguration und Bulk-Laufzeitlogik klarer**: Das hält die Seitenverwaltung konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.266 — 26. März 2026 · Audit-Batch 348, Pages-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.266** | 🔴 fix | Admin/Pages | **`CMS/admin/pages.php` baut die Grid-Konfiguration für die Listenansicht jetzt über `cms_admin_pages_grid_config()` statt über großen Inline-JavaScript-String**: Der Entry hält damit seinen Listenpfad kompakter und weniger fehleranfällig. |
| **2.7.266** | 🟠 perf | Admin/Pages | **Die Pages-List-Ansicht bindet jetzt zusätzlich `CMS/assets/js/admin-pages.js` statt Grid-/Bulk-Initialisierung als Inline-Footer-Script zu bekommen**: Das reduziert weiteren Inline-Asset-Boilerplate im Entry-Vertrag. |
| **2.7.266** | 🟡 refactor | Admin/Pages | **Der Pages-Entry trennt Datenladung, Grid-Konfiguration und Asset-Vertrag klarer**: Listen- und Edit-Pfade bleiben dadurch sichtbarer voneinander getrennt. |

---

### v2.7.265 — 26. März 2026 · Audit-Batch 347, Dokumentations-View weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.265** | 🔴 fix | Admin/System | **`CMS/admin/views/system/documentation.php` zieht Dokument- und Bereichsdaten jetzt stärker über View-Model-Helfer zusammen**: Die Renderpfade greifen dadurch nicht länger wiederholt auf viele kleine Einzelhelper im Hauptfluss zu. |
| **2.7.265** | 🟠 perf | Admin/Views | **Die Dokumentations-Ansicht reduziert weiteren Render-Boilerplate im Listen- und Bereichspfad**: Dokument-Metadaten und Bereichszustände werden kompakter vorbereitet, bevor das Markup sie ausgibt. |
| **2.7.265** | 🟡 refactor | Admin/System | **Der Doku-View bleibt sichtbarer auf Renderblöcke statt auf verteilte Datensammelreste fokussiert**: Das hält die View konsistenter zu den bereits verdichteten Admin-Ansichten. |

---

### v2.7.264 — 26. März 2026 · Audit-Batch 346, Mail-Settings-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.264** | 🔴 fix | Admin/System | **`CMS/admin/mail-settings.php` normalisiert Tab und Aktion jetzt einmalig in einem gemeinsamen Payload**: Der POST-Pfad pflegt damit keine separate Handler-Map und keine doppelte Aktionsauflösung mehr. |
| **2.7.264** | 🟠 perf | Admin/System | **Der Mail-Settings-Entry dispatcht Transport-, Azure-, Graph-, Queue- und Cache-Aktionen jetzt über einen direkten `match`-Helper**: Guard, Tab-Validierung und Redirect-Ziel bleiben dadurch kompakter lesbar. |
| **2.7.264** | 🟡 refactor | Admin/System | **`cms_admin_mail_settings_handle_action()` hält den Aktionspfad klarer an einem kleinen Entry-Vertrag**: Das reduziert weiteren Dispatch-Boilerplate ohne die Fachlogik aus dem Modul zu ziehen. |

---

### v2.7.263 — 26. März 2026 · Audit-Batch 345, Landing-Page-Entry weiter verschlankt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.263** | 🔴 fix | Admin/Landing | **`CMS/admin/landing-page.php` normalisiert Aktion und Feature-ID jetzt einmalig über `cms_admin_landing_page_normalize_payload()`**: Der Entry pflegt dadurch keine Closure-basierte Handler-Map mehr für seine POST-Aktionen. |
| **2.7.263** | 🟠 perf | Admin/Landing | **Der Landing-Page-Entry dispatcht Header-, Content-, Footer-, Design- und Feature-Aktionen jetzt über einen direkten `match`-Pfad**: Validierung und Modul-Dispatch bleiben dadurch kompakter lesbar. |
| **2.7.263** | 🟡 refactor | Admin/Landing | **`cms_admin_landing_page_handle_action()` trennt Kontroll-Payload und Aktionsausführung klarer**: Delete-Feature hängt damit sichtbarer an demselben Entry-Vertrag wie die übrigen Landing-Aktionen. |

---

### v2.7.262 — 26. März 2026 · Audit-Batch 344, Cookie-Manager-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.262** | 🔴 fix | Admin/Legal | **`CMS/admin/views/legal/cookies.php` pflegt Modal- und Delete-Aktionen nicht länger über lokale Inline-`onclick`-/`confirm(...)`-Pfade**: Die View liefert jetzt Datenattribute und Confirm-Metadaten statt eigener Script-Inseln. |
| **2.7.262** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-cookie-manager.js` übernimmt Reset-, Edit- und Modal-Öffnungslogik für Kategorien und Services zentral**: Der Cookie-Manager hält seine Laufzeitlogik damit näher an einem dedizierten Admin-Asset statt direkt im PHP-Template. |
| **2.7.262** | 🟡 refactor | Admin/Legal | **`CMS/admin/cookie-manager.php` bindet das neue Asset konsistent über `page_assets` ein**: Der Cookie-Manager folgt damit sichtbarer demselben Asset-/Confirm-Vertrag wie andere modernisierte Admin-Ansichten. |

---

### v2.7.261 — 26. März 2026 · Audit-Batch 343, Cookie-Manager-Entry weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.261** | 🔴 fix | Admin/Legal | **`CMS/admin/cookie-manager.php` normalisiert Action, ID, Service-Slug und Self-Hosted-Flag jetzt einmalig in einem gemeinsamen Payload**: Delete- und Curated-Import-Pfade ziehen dadurch nicht länger mehrere getrennte kleine Normalisierungsschritte im `post_handler` nach. |
| **2.7.261** | 🟠 perf | Admin/Legal | **Der Cookie-Manager-Entry dispatcht seine Aktionen jetzt über `cms_admin_cookie_manager_handle_action()` mit bereits vorbereitetem Payload**: Guard, Validierung und Modul-Dispatch bleiben damit kompakter lesbar. |
| **2.7.261** | 🟡 refactor | Admin/Legal | **Kontrollfelder des Cookie-Managers hängen klarer an einem kleinen Entry-Vertrag, während Formularinhalte weiterhin im Modul sanitisiert werden**: Der Wrapper reduziert doppelten Kontrollfluss-Boilerplate, ohne den Fachpfad des Moduls zu verwischen. |

---

### v2.7.260 — 26. März 2026 · Audit-Batch 342, Plugins-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.260** | 🔴 fix | Admin/Plugins | **`CMS/admin/views/plugins/list.php` verzichtet bei Toggle- und Delete-Aktionen auf lokale Inline-`onchange`-/`confirm(...)`-Handler**: Die Plugin-Liste hängt damit sichtbarer am gemeinsamen Confirm-Vertrag und einer dedizierten JS-Datei. |
| **2.7.260** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-plugins.js` übernimmt das Auto-Submit der Aktivieren-/Deaktivieren-Switches zentral**: Der Toggle-Pfad muss damit nicht länger direkt im View-Markup gepflegt werden. |
| **2.7.260** | 🟡 refactor | Admin/Plugins | **`CMS/admin/plugins.php` bindet das neue Script über `page_assets` ein**: Die Plugin-Liste folgt dadurch demselben Asset-Vertrag wie andere bereits harmonisierte Admin-Seiten. |

---

### v2.7.259 — 26. März 2026 · Audit-Batch 341, Plugins-Vertrag weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.259** | 🔴 fix | Admin/Plugins | **`CMS/admin/plugins.php` normalisiert Action und Slug jetzt einmalig über `cms_admin_plugins_normalize_payload()`**: Der Entry hält damit seinen POST-Pfad kompakter und validiert keine Einzelteile mehrfach. |
| **2.7.259** | 🟠 perf | Admin/Plugins | **`CMS/admin/modules/plugins/PluginsModule.php` bündelt Slug-, Hauptdatei- und Plugin-Pfadlogik jetzt in kleinen Hilfsmethoden**: Aktivierung, Deaktivierung, Löschung und Active-Plugin-Lookup vermeiden dadurch weitere doppelte Pfad-/String-Reste. |
| **2.7.259** | 🟡 refactor | Admin/Plugins | **Der Plugins-Modulpfad prüft Plugin-Verzeichnisse und Hauptdateien konsistenter über gemeinsame Helper**: Das hält Normalisierung, Pfadauflösung und Delete-Schutz lesbarer an einer Stelle. |

---

### v2.7.258 — 26. März 2026 · Audit-Batch 340, Font-Manager-Vertrag weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.258** | 🔴 fix | Admin/Themes | **`CMS/admin/font-manager.php` normalisiert Action, Font-ID und Google-Font-Familie jetzt einmalig in einem gemeinsamen Payload**: Der POST-Pfad hängt dadurch konsistenter am Shared-Shell-Vertrag statt mehrere kleine Normalisierungsschritte separat zu pflegen. |
| **2.7.258** | 🟠 perf | Admin/Views | **`CMS/admin/views/themes/fonts.php` verzichtet in der Preview auf überflüssige statische `font-family`-Inline-Styles**: Die Vorschau bleibt an der bestehenden JS-Initialisierung statt doppelte Startwerte im Markup zu tragen. |
| **2.7.258** | 🟡 refactor | Admin/Themes | **Der Font-Manager bündelt seinen Dispatch klarer über `cms_admin_font_manager_normalize_payload()`**: Entry und View bleiben dadurch etwas schlanker und konsistenter. |

---

### v2.7.257 — 26. März 2026 · Audit-Batch 339, Media-Settings-Vertrag weiter verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.257** | 🔴 fix | Admin/Media | **`CMS/admin/modules/media/MediaModule.php` liefert Defaultwerte, Dateityp-Optionen und Thumbnail-Metadaten für den Settings-View jetzt zentral aus dem Modul**: Die Media-Einstellungen pflegen dadurch keine zweite lokale Default-/Options-Wahrheit mehr im Template. |
| **2.7.257** | 🟠 perf | Admin/Views | **`CMS/admin/views/media/settings.php` rendert Dateityp- und Thumbnail-Blöcke jetzt aus den vom Modul gelieferten Optionen**: Das reduziert lokale Listen- und Mapping-Duplikate und hält den View schlanker. |
| **2.7.257** | 🟡 refactor | Admin/Media | **Der Media-Settings-Pfad trennt View-Boilerplate sauberer vom Modulvertrag**: Größenwerte werden als MB für das Formular vorbereitet, und interne Feldnamen/Defaults bleiben näher an einer zentralen Stelle. |

---

### v2.7.256 — 26. März 2026 · Audit-Batch 338, Dokumentations-Entry weiter verschlankt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.256** | 🔴 fix | Admin/System | **`CMS/admin/documentation.php` normalisiert POST-Aktionen jetzt einmalig aus dem übergebenen Payload statt direkt mehrfach aus `$_POST`**: Der Entry hängt damit klarer am Shared-Shell-Post-Handler-Vertrag. |
| **2.7.256** | 🟠 perf | Admin/System | **Der Dokumentations-Entry verzichtet auf eine Handler-Map für nur eine Sync-Aktion**: Der direkte Dispatch über einen kleinen Action-Helper hält den Request-Pfad kompakter. |
| **2.7.256** | 🟡 refactor | Admin/System | **`cms_admin_documentation_handle_action()` bündelt den Aktionspfad der Doku-Seite explizit**: Guard, Normalisierung und Dispatch sind dadurch lesbarer getrennt. |

---

### v2.7.255 — 26. März 2026 · Audit-Batch 337, Data-Requests-View weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.255** | 🔴 fix | Admin/Legal | **`CMS/admin/views/legal/data-requests.php` pflegt Lösch- und Ausführungsaktionen nicht länger über lokale Inline-`confirm(...)`-Handler**: Die Formulare hängen jetzt am gemeinsamen `data-confirm-*`-Vertrag statt an verstreuten Button-Sonderpfaden. |
| **2.7.255** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-data-requests.js` übernimmt den Reject-Modal-Pfad der Data-Requests-Ansicht zentral**: Scope, Request-ID und Modal-Titel werden dadurch konsistent über Datenattribute gesetzt statt durch ein lokales Inline-Script. |
| **2.7.255** | 🟡 refactor | Admin/Legal | **`CMS/admin/data-requests.php` bindet das neue Asset sauber über `page_assets` ein**: Der DSGVO-Bereich folgt damit demselben Confirm-/Asset-Vertrag wie andere modernisierte Admin-Seiten. |

---

### v2.7.254 — 26. März 2026 · Audit-Batch 336, Marketplace-Remote-Pfad weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.254** | 🔴 fix | Admin/Plugins | **`CMS/admin/plugin-marketplace.php` reicht Installationsdaten jetzt nur noch minimal normalisiert weiter**: Der Entry begrenzt den POST-Pfad für `install` damit auf den validierten Slug statt rohe Request-Reste in die Aktion mitzunehmen. |
| **2.7.254** | 🟠 perf | Admin/Marketplace | **`CMS/admin/modules/plugins/PluginMarketplaceModule.php` kanonisiert Registry-, Manifest- und Katalog-URLs vor Remote-Zugriffen zentral**: Host, Port, Credentials und Steuerzeichen werden dadurch einheitlicher geprüft, bevor externe Ressourcen geladen oder weitergereicht werden. |
| **2.7.254** | 🟡 refactor | Admin/Plugins | **Der Marketplace-Modulpfad nutzt jetzt gemeinsame Normalisierung für Slugs und Remote-URLs**: Registry-Fallback, relative Marketplace-Pfade und direkte Catalog-URLs hängen dadurch sichtbarer an einem konsistenten Remote-Vertrag. |

---

### v2.7.253 — 26. März 2026 · Audit-Batch 335, Legal-Sites-Cluster weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.253** | 🔴 fix | Admin/Legal | **`CMS/admin/legal-sites.php` normalisiert Legal-Sites-Payloads jetzt allowlist-basiert pro Aktion**: `save` und `save_profile` reichen dadurch nicht länger rohe POST-Komplettpakete in den Modulpfad weiter. |
| **2.7.253** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-legal-sites.js` übernimmt die bisherige DOM-Logik der Legal-Sites-Ansicht zentral**: Requirement-Toggles, Privacy-Feature-Blöcke und Template-Einfügen hängen damit nicht länger als großes lokales Inline-Script im View. |
| **2.7.253** | 🟡 refactor | Admin/Legal | **`CMS/admin/views/legal/sites.php` trennt Markup und Laufzeitlogik klarer**: die View liefert nur noch JSON-Konfiguration und Datenattribute, während das Verhalten konsistent über das eingebundene Admin-Asset läuft. |

---

### v2.7.252 — 26. März 2026 · Audit-Batch 334, Dokumentations-View weiter verschlankt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.252** | 🔴 fix | Admin/System | **`CMS/admin/views/system/documentation.php` ersetzt die verbliebenen Render-Closures durch benannte View-Funktionen**: Metric-Cards, Dokument-Listen, Bereichs-Akkordeons und Dokument-Inhalt pflegen damit keinen verstreuten Inline-Closure-Pfad mehr. |
| **2.7.252** | 🟠 perf | Admin/Views | **Kleine Aufbereitungsreste wie Quellen-Text und Metric-Card-Konfiguration liegen jetzt ebenfalls hinter präfixierten Helpern**: der View hält weniger Logik im Hauptfluss und bleibt dadurch lesbarer wartbar. |
| **2.7.252** | 🟡 refactor | Admin/System | **Die Dokumentations-Ansicht konzentriert sich stärker auf den Renderfluss statt auf lokale Render-Werkzeuge**: der bestehende Flash-/HTML-Vertrag bleibt erhalten, aber die View-Struktur ist konsistenter zu anderen modernisierten Admin-Views. |

---

### v2.7.250 — 26. März 2026 · Audit-Batch 332, Updates-View weiter vereinheitlicht

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.250** | 🔴 fix | Admin/System | **`CMS/admin/views/system/updates.php` pflegt Core- und Plugin-Installationen nicht länger über lokale `confirm(...)`-Sonderpfade**: die Formulare hängen jetzt am gemeinsamen Confirm-Vertrag statt an einem View-lokalen Inline-Handler. |
| **2.7.250** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin.js` unterstützt jetzt wiederverwendbare Formular-Bestätigungen über `form[data-confirm-message]`**: Modal-Confirm und Fallback-Submit müssen dadurch nicht länger pro View separat nachgebaut werden. |
| **2.7.250** | 🟡 refactor | Admin/System | **Der Updates-View trennt Aktions-Markup und Laufzeitlogik klarer**: Core- und Plugin-Installationen nutzen denselben Confirm-Helfer wie andere harmonisierbare Admin-Aktionen. |

---

### v2.7.251 — 26. März 2026 · Audit-Batch 333, Media-Cluster weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.251** | 🔴 fix | Admin/Media | **`CMS/admin/media.php` normalisiert Media-Aktions-Payloads jetzt gezielter pro Aktion und validiert kritische Request-Felder bereits im Wrapper**: Delete-, Rename-, Kategorie- und Settings-Pfade reichen dadurch nicht länger rohe POST-Daten weiter. |
| **2.7.251** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-media-integrations.js` übernimmt jetzt zusätzlich den Kategorie-Löschflow der Media-Kategorien-Ansicht**: Delete-Confirm und Form-Submit müssen dort nicht länger als lokale Script-Insel gepflegt werden. |
| **2.7.251** | 🟡 refactor | Admin/Media | **`CMS/admin/views/media/categories.php` trennt Markup und Laufzeitlogik klarer**: die View liefert für den Delete-Pfad nur noch Datenattribute und JSON-Konfiguration statt Inline-`onclick` plus lokalem `<script>`. |

---

### v2.7.249 — 26. März 2026 · Audit-Batch 331, Media-Library-View weiter entschlackt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.249** | 🔴 fix | Admin/Media | **`CMS/admin/views/media/library.php` pflegt Member-Ordner-Confirm und Delete-Aktionen nicht länger über lokale Inline-`onclick`-Attribute oder ein View-lokales `<script>`**: die View liefert stattdessen Datenattribute und JSON-Konfiguration. |
| **2.7.249** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-media-integrations.js` übernimmt die Media-Library-Aktionslogik jetzt zentral**: Delete-Submit und Member-Folder-Confirm laufen dadurch näher am bestehenden Media-Asset-Vertrag statt verteilt im PHP-View. |
| **2.7.249** | 🟡 refactor | Admin/Media | **Die Media-Library trennt Markup und Laufzeitlogik klarer**: verbleibende Action-Handler sitzen jetzt im bereits geladenen Admin-Media-Script statt als separate kleine Script-Insel im View. |

---

### v2.7.248 — 26. März 2026 · Audit-Batch 330, Font-Manager-View weiter entschlackt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.248** | 🔴 fix | Admin/Themes | **`CMS/admin/views/themes/fonts.php` pflegt Preview- und Lösch-Confirm-Logik nicht länger als lokales Inline-Script**: der View liefert nur noch Konfiguration und Markup, während das Verhalten ausgelagert wird. |
| **2.7.248** | 🟠 perf | Assets/Admin | **`CMS/assets/js/admin-font-manager.js` bündelt Font-Preview und Delete-Confirm für den Font Manager in einer dedizierten Admin-JS-Datei**: derselbe Code muss nicht länger direkt im PHP-View geparkt werden. |
| **2.7.248** | 🟡 refactor | Admin/Themes | **`CMS/admin/font-manager.php` bindet das neue Script konsistent über `pageAssets` ein**: der Font-Manager folgt damit demselben Admin-Asset-Vertrag wie andere modernisierte Bereiche. |

---

### v2.7.247 — 26. März 2026 · Audit-Batch 329, Font-Manager-Entry weiter verschlankt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.247** | 🔴 fix | Admin/Themes | **`CMS/admin/font-manager.php` ersetzt sein Closure-basiertes Action-Handler-Mapping durch einen direkten `match`-Dispatch**: der Entry pflegt keinen separaten Handler-Map-Sonderpfad mehr. |
| **2.7.247** | 🟠 perf | Admin/Themes | **Der POST-Pfad normalisiert Font-ID und Google-Font-Familie jetzt nur noch einmal**: kleine doppelte Dispatch- und Normalisierungsarbeit im Font-Manager-Entry entfällt. |
| **2.7.247** | 🟡 refactor | Admin/Themes | **Der Entry bleibt klarer auf Guard, Normalisierung und Modul-Dispatch fokussiert**: kleine anonyme Action-Closures werden zugunsten eines direkteren, besser lesbaren Request-Flows reduziert. |

---

### v2.7.246 — 26. März 2026 · Audit-Batch 328, Legal-Sites-Entry weiter verschlankt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.246** | 🔴 fix | Admin/Legal | **`CMS/admin/legal-sites.php` ersetzt den Closure-basierten Action-Dispatch durch einen direkten `match`-Dispatch**: der Entry pflegt keinen separaten Handler-Map-Sonderpfad mehr. |
| **2.7.246** | 🟠 perf | Admin/Views | **Der POST-Pfad normalisiert den Vorlagentyp jetzt nur noch einmal**: doppelte kleine Dispatch- und Normalisierungsarbeit im Legal-Sites-Entry entfällt. |
| **2.7.246** | 🟡 refactor | Admin/Legal | **Der Entry bleibt klarer auf Guard, Normalisierung und Dispatch fokussiert**: kleine anonyme Action-Closures werden zugunsten eines direkteren, besser lesbaren Request-Flows reduziert. |

---

### v2.7.245 — 26. März 2026 · Audit-Batch 327, Dokumentations-View weiter verschlankt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.245** | 🔴 fix | Admin/System | **`CMS/admin/views/system/documentation.php` ersetzt mehrere kleine anonyme Dokument- und Status-Helfer durch benannte View-Funktionen**: die Datei hält weniger Inline-Closure-Logik direkt im Kopfbereich. |
| **2.7.245** | 🟠 perf | Admin/Views | **Die Dokumentations-View reduziert weiteren Inline-Helper-Boilerplate, ohne ihren Rendervertrag zu verändern**: Dokumentlisten, Active-State-Checks und Bereichs-Metadaten bleiben dadurch lesbarer und konsistenter wartbar. |
| **2.7.245** | 🟡 refactor | Admin/System | **Der Dokumentations-View konzentriert sich klarer auf Render-Blöcke statt auf verteilte kleine Datentransformations-Closures**: die fachlichen Mini-Helfer sind jetzt sichtbar benannt und lokaler präfixiert. |

---

### v2.7.244 — 26. März 2026 · Audit-Batch 326, Media-Entry-/Modulvertrag weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.244** | 🔴 fix | Admin/Media | **`CMS/admin/media.php` delegiert die Parent-Path-Auflösung jetzt an `CMS/admin/modules/media/MediaModule.php`**: der Entry pflegt keinen separaten String-/`dirname()`-Sonderpfad mehr neben dem Modulvertrag. |
| **2.7.244** | 🟠 perf | Admin/Views | **Upload-Fehler bauen Dateinamen wieder als rohe Textdaten statt vorzeitig HTML-escaped zusammen**: der Media-Flash-Pfad vermeidet damit doppelte Escaping-Arbeit und bleibt konsistenter zum zentralen Alert-Partial. |
| **2.7.244** | 🟡 refactor | Admin/Media | **Der Media-Entry hält Pfadnormalisierung und Fehlermeldungsaufbereitung klarer getrennt**: Parent-Path-Logik liegt näher am Modul, während der View-Vertrag wieder mit rohen Textdaten statt gemischtem HTML-Status arbeitet. |

---

### v2.7.243 — 26. März 2026 · Audit-Batch 325, System-Update-Warnboxen weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.243** | 🔴 fix | Admin/System | **`CMS/admin/views/system/updates.php` nutzt die verbliebenen komplexeren Core- und Theme-Update-Warnboxen jetzt ebenfalls über das gemeinsame `flash-alert.php`**: lokale `alert alert-warning`-Blöcke werden damit weiter aus dem View zurückgebaut. |
| **2.7.243** | 🟠 perf | Admin/Views | **Die Updates-Ansicht spart weiteren Alert-Boilerplate und hält Aktionsbuttons klarer getrennt vom gemeinsamen Hinweisvertrag**: künftige Verbesserungen an Typ-Mapping, Detail-Listen und Dismiss-Verhalten greifen dadurch zentral statt in lokalem Warn-Markup. |
| **2.7.243** | 🟡 refactor | Admin/System | **Der Updates-View bleibt konsistenter zu anderen harmonisierten Shared-Shell-Entrys**: sowohl einfache Statushinweise als auch die bislang komplexeren Update-Warnungen hängen jetzt sichtbarer am selben Partial-Vertrag. |

---

### v2.7.242 — 26. März 2026 · Audit-Batch 324, Media-Library-Alert weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.242** | 🔴 fix | Admin/Media | **`CMS/admin/views/media/library.php` nutzt den verbliebenen elFinder-Infohinweis jetzt ebenfalls über das gemeinsame `flash-alert.php`**: der Finder-Zweig pflegt keinen separaten lokalen `alert alert-info`-Block mehr. |
| **2.7.242** | 🟠 perf | Admin/Views | **Die Media-Library spart weiteren lokalen Alert-Boilerplate und übernimmt künftige Verbesserungen am gemeinsamen Alert-Partial automatisch mit**: Typ-Mapping, Detail-Listen und Dismiss-Verhalten bleiben dadurch zentral statt view-lokal gepflegt. |
| **2.7.242** | 🟡 refactor | Admin/Media | **Der Bibliotheks-View bleibt konsistenter zum bereits standardisierten Media-Entry und den übrigen harmonisierten Admin-Views**: auch der elFinder-Hinweis hängt jetzt sichtbar am Shared-View-Vertrag. |

---

### v2.7.241 — 26. März 2026 · Audit-Batch 323, Legal-Sites-Alerts weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.241** | 🔴 fix | Admin/Legal | **`CMS/admin/views/legal/sites.php` nutzt die verbliebenen lokalen Intro- und Datenschutz-Hinweisboxen jetzt ebenfalls über das gemeinsame `flash-alert.php`**: lokale Alert-Sonderpfade in der Legal-Sites-View werden damit weiter zurückgebaut. |
| **2.7.241** | 🟠 perf | Admin/Views | **Die Legal-Sites-View spart weiteren lokalen Alert-Boilerplate und übernimmt künftige Verbesserungen an Alert-Typen, Detail-Listen und Dismiss-Verhalten zentral mit**: insbesondere die featurebezogenen Datenschutz-Hinweise hängen jetzt sichtbar am Shared-View-Vertrag. |
| **2.7.241** | 🟡 refactor | Admin/Legal | **Der Rechtstext-Generator bleibt im View konsistenter zum bereits standardisierten Entry- und Flash-Vertrag**: Intro- und Feature-Hinweise werden nicht mehr als separate lokale Bootstrap-Alerts gepflegt. |

---

### v2.7.240 — 26. März 2026 · Audit-Batch 322, Font-Manager-Vertrag weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.240** | 🔴 fix | Admin/Themes | **`CMS/admin/modules/themes/FontManagerModule.php` gibt bei Speicher- und Löschfehlern keine rohen Exception-Texte mehr an die Admin-UI weiter**: Fehler werden stattdessen strukturiert über `Logger::instance()->withChannel('admin.font-manager')` protokolliert. |
| **2.7.240** | 🟠 perf | Admin/Views | **`CMS/admin/views/themes/fonts.php` nutzt jetzt auch die verbliebene Self-Hosting-Hinweisbox über das gemeinsame `flash-alert.php`**: der Font-Manager spart weiteren lokalen Alert-Boilerplate und hängt sichtbarer am Shared-View-Vertrag. |
| **2.7.240** | 🟡 refactor | Admin/Themes | **Der hoch priorisierte Font-Manager zieht Fehler- und Hinweispfade näher an denselben Modul-/Partial-Vertrag wie andere modernisierte Admin-Bereiche**: Logging, UI-Meldungen und View-Hinweise bleiben konsistenter getrennt. |

---

### v2.7.239 — 26. März 2026 · Audit-Batch 321, Diagnose- und Member-Hinweise weiter harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.239** | 🔴 fix | Admin/Views | **`CMS/admin/views/system/diagnose.php` und `CMS/admin/views/member/general.php` nutzen verbleibende einfache Hinweisboxen jetzt ebenfalls über das gemeinsame `flash-alert.php`**: lokale Info-/Warning-Blöcke werden damit weiter aus einzelnen Views herausgezogen. |
| **2.7.239** | 🟠 perf | Admin/Views | **Diagnose- und Member-UI sparen weiteren Alert-Boilerplate und hängen sichtbarer am selben Shared-View-Vertrag wie andere modernisierte Admin-Seiten**: künftige Flash-Partial-Anpassungen greifen damit zentral statt dateiweise. |
| **2.7.239** | 🟡 refactor | Admin/Views | **Der Restcluster fachlicher Hinweisboxen schrumpft weiter**: View-spezifische Alert-Sonderpfade werden zugunsten eines einheitlicheren Partial-Vertrags reduziert. |

---

### v2.7.238 — 26. März 2026 · Audit-Batch 320, lokale Admin-Hinweise weiter auf Flash-Partial harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.238** | 🔴 fix | Admin/Views | **`CMS/admin/views/system/updates.php`, `system/documentation.php`, `themes/fonts.php` und `subscriptions/settings.php` nutzen einfache Status- und Hinweisblöcke jetzt über das gemeinsame `flash-alert.php`**: lokale Alert-Sonderpfade für Erfolg, Info, Warning und Error werden damit weiter aus einzelnen Views herausgezogen. |
| **2.7.238** | 🟠 perf | Admin/Views | **Die betroffenen Views sparen weiteren UI-Boilerplate und profitieren zentral von denselben Alert-Typ-Mappings, Detail-Listen und Dismiss-Standards**: spätere Partial-Anpassungen greifen dadurch automatisch in weiteren System-, Theme- und Abo-Oberflächen. |
| **2.7.238** | 🟡 refactor | Admin/Documentation | **`CMS/admin/views/system/documentation.php` pflegt keinen eigenen `$renderAlertBlock`-Renderer mehr**: die View hängt jetzt auch für Verfügbarkeits-, Sync- und Excerpt-Hinweise direkt am Shared-Partial-Vertrag. |

---

### v2.7.237 — 26. März 2026 · Audit-Batch 319, Plugins-/Themes-Entrys auf Shared-Shell verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.237** | 🔴 fix | Admin/Extensions | **`CMS/admin/plugins.php` und `CMS/admin/themes.php` laufen jetzt über die gemeinsame `section-page-shell.php` statt eigene Redirect-/Flash-/POST-Sonderpfade zu pflegen**: Access-Check, CSRF-Flow und Shell-Rendering hängen dadurch an demselben Shared-Vertrag wie die bereits modernisierten Admin-Entrys. |
| **2.7.237** | 🟠 perf | Admin/Extensions | **Die Plugin- und Theme-Verwaltung spart weiteren Entry-Boilerplate und nutzt denselben PRG-/Flash-Vertrag wie Marketplace, Media oder Packages**: Mutationspfade landen damit konsistenter wieder auf derselben Verwaltungsansicht. |
| **2.7.237** | 🟡 refactor | Admin/Extensions | **`CMS/admin/modules/plugins/PluginsModule.php` und `CMS/admin/modules/themes/ThemesModule.php` härten ihre UI-Verträge zusätzlich**: Plugin-Aktivierungs-/Deaktivierungsfehler werden strukturiert geloggt statt rohe Exception-Texte weiterzureichen, und Theme-Slugs/Erfolgsmeldungen werden konsistent normalisiert statt bereits im Modul HTML-escaped zu werden. |

---

### v2.7.236 — 26. März 2026 · Audit-Batch 318, Marketplace-Entrys und Installationspfade gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.236** | 🔴 fix | Admin/Marketplace | **`CMS/admin/plugin-marketplace.php` und `CMS/admin/theme-marketplace.php` laufen jetzt über die gemeinsame `section-page-shell.php` statt eigene Redirect-/Flash-/POST-Sonderpfade zu pflegen**: Access-Checks, CSRF-Flow und Shell-Rendering hängen dadurch an demselben Shared-Vertrag wie andere modernisierte Admin-Entrys. |
| **2.7.236** | 🟠 perf | Admin/Marketplace | **`CMS/admin/modules/plugins/PluginMarketplaceModule.php` und `CMS/admin/modules/themes/ThemeMarketplaceModule.php` normalisieren relative Remote-Manifest-/Downloadpfade jetzt strikter**: nur erlaubte HTTPS-Marketplace-Ziele mit sauberen relativen Pfaden werden noch zu Remote-URLs zusammengesetzt. |
| **2.7.236** | 🟡 refactor | Admin/Marketplace | **Auto-Installationen werden zusätzlich an `requires_cms`- und `requires_php`-Vorgaben gekoppelt**: inkompatible Plugin-/Theme-Pakete bleiben damit im Marketplace sichtbar, werden aber nicht mehr als automatisch installierbar angeboten. |

---

### v2.7.235 — 26. März 2026 · Audit-Batch 317, Flash-Ausgabe in 14 weiteren Admin-Views harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.235** | 🔴 fix | Admin/SEO | **`CMS/admin/views/seo/technical.php`, `social.php`, `meta.php`, `sitemap.php`, `schema.php`, `redirects.php`, `not-found.php`, `dashboard.php` und `audit.php` nutzen ihre Alert-Ausgabe jetzt über das gemeinsame `flash-alert.php`**: Erfolg-/Fehlerhinweise, Dismiss-Verhalten und Detail-Listen werden damit nicht länger lokal pro View gepflegt. |
| **2.7.235** | 🟠 perf | Admin/Views | **`CMS/admin/views/security/audit.php`, `plugins/marketplace.php`, `themes/marketplace.php`, `themes/list.php` und `toc/settings.php` hängen jetzt ebenfalls am selben Flash-Partial-Vertrag**: wiederholtes Alert-Markup entfällt in weiteren Admin-Bereichen, und spätere Partial-Verbesserungen greifen zentral. |
| **2.7.235** | 🟡 refactor | Admin/Core | **`CMS/admin/views/partials/flash-alert.php` unterstützt zusätzlich generische Detail-Listen aus `details`**: Redirect- und 404-Views können ihre zusätzlichen Hinweislisten jetzt über denselben zentralen Alert-Renderer ausgeben statt mit lokalem Sondermarkup. |

---

### v2.7.234 — 26. März 2026 · Audit-Batch 316, Member- und System-Flash-Ausgabe breit harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.234** | 🔴 fix | Admin/Member | **`CMS/admin/views/member/dashboard.php`, `general.php`, `design.php`, `frontend-modules.php`, `widgets.php`, `plugin-widgets.php`, `profile-fields.php`, `notifications.php` und `onboarding.php` nutzen ihre Seiten-Flash-Ausgabe jetzt über das gemeinsame `flash-alert.php`**: Member-Unterseiten pflegen dadurch keine eigenen Alert-Blöcke mehr vor der Subnav. |
| **2.7.234** | 🟠 perf | Admin/System | **`CMS/admin/views/system/email-alerts.php`, `response-time.php`, `cron-status.php`, `disk-usage.php`, `health-check.php`, `scheduled-tasks.php`, `info.php` und `diagnose.php` hängen jetzt ebenfalls am gemeinsamen Flash-Partial**: Monitoring- und Diagnose-Views sparen damit weiteren wiederholten Alert-Boilerplate. |
| **2.7.234** | 🟡 refactor | Admin/Views | **Insgesamt 17 Admin-Views wurden auf denselben Flash-Partial-Vertrag vereinheitlicht**: künftige Änderungen an Dismiss-Verhalten, Alert-Typ-Mapping oder Fehlerreport-Payloads greifen damit zentral statt dateiweise. |

---

### v2.7.233 — 26. März 2026 · Audit-Batch 315, SEO-Subnav auf gemeinsames Section-Partial standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.233** | 🔴 fix | Admin/SEO | **`CMS/admin/views/seo/subnav.php` nutzt jetzt das gemeinsame `section-subnav.php` statt eine lokale Navigationsstruktur zu pflegen**: die aktive Seite und Link-Ausgabe hängen dadurch näher am standardisierten Partial-Vertrag. |
| **2.7.233** | 🟠 perf | Admin/SEO | **Die SEO-Navigation spart eigenen Markup-/Class-Boilerplate und übernimmt künftige Subnav-Anpassungen automatisch mit**: nur die beiden Sitemap-/robots-Aktionsbuttons bleiben als schlanker separater Action-Block zurück. |
| **2.7.233** | 🟡 refactor | Admin/Views | **Ein weiterer Admin-View verlagert seinen Navigationsaufbau in ein gemeinsames Partial**: Subnav-Verhalten bleibt dadurch konsistenter zwischen SEO-, Performance-, System- und Member-Bereich. |

---

### v2.7.232 — 26. März 2026 · Audit-Batch 314, Flash-Ausgabe in Analytics- und Performance-Media-View harmonisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.232** | 🔴 fix | Admin/SEO | **`CMS/admin/views/seo/analytics.php` nutzt jetzt das gemeinsame `flash-alert.php` statt einen lokalen Alert-Block zu pflegen**: Dismiss-Handling und optionale Fehlerreport-Payloads hängen damit näher am standardisierten View-Vertrag. |
| **2.7.232** | 🟠 perf | Admin/Performance | **`CMS/admin/views/performance/media.php` rendert Shell-basierte Alerts jetzt ebenfalls über das gemeinsame Flash-Partial**: die View spart duplizierte Alert-Ausgabe und bleibt konsistenter zu anderen standardisierten Admin-Seiten. |
| **2.7.232** | 🟡 refactor | Admin/Views | **Zwei weitere Admin-Views verlassen ihre lokalen Alert-Sonderpfade**: Flash-Ausgabe und künftige Partial-Erweiterungen greifen dadurch zentral statt dateiweise. |

---

### v2.7.231 — 26. März 2026 · Audit-Batch 313, System-Monitor-Wrapper und Fehlerpfade weiter gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.231** | 🔴 fix | Admin/System | **`CMS/admin/modules/system/SystemInfoModule.php` gibt bei Diagnose-/Monitoring-Fehlern keine rohen Exception-Texte mehr an die Admin-Oberfläche aus**: technische Details werden stattdessen gekürzt im Audit-Log festgehalten, während die UI generische Fehlermeldungen zeigt. |
| **2.7.231** | 🟠 perf | Admin/System | **`CMS/admin/system-monitor-page.php` baut seinen Shell-Vertrag jetzt über einen benannten Helper auf und delegiert den Access-Check an die gemeinsame `section-page-shell.php`**: der Wrapper spart eigenen Header-Redirect-Boilerplate und bleibt näher an verwandten Shared-Entrys. |
| **2.7.231** | 🟡 refactor | Admin/System | **Wrapper- und Modul-Fehlerpfade sind klarer getrennt**: die Datei konzentriert sich stärker auf Section-Registry und Action-Dispatch, während Modulfehler zentral geloggt und UI-sicher aufbereitet werden. |

---

### v2.7.230 — 26. März 2026 · Audit-Batch 312, Performance-Wrapper weiter auf Shared-Shell verdichtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.230** | 🔴 fix | Admin/Performance | **`CMS/admin/performance-page.php` delegiert den Access-Check jetzt an die gemeinsame `section-page-shell.php` statt einen eigenen Header-Redirect-Sonderpfad vorzuhalten**: der Wrapper hängt dadurch enger am bestehenden Shared-Entry-Vertrag. |
| **2.7.230** | 🟠 perf | Admin/Performance | **Die Section-Shell-Konfiguration wird jetzt über einen benannten Helper aufgebaut**: Abschnitts-, Modul- und Datenlade-Kontext bleiben kompakter zusammengefasst und müssen nicht länger als loser Inline-Block gepflegt werden. |
| **2.7.230** | 🟡 refactor | Admin/Performance | **Die Datei konzentriert sich jetzt stärker auf Normalisierung und Aktionsregeln**: Shell-Zusammenbau und Access-Standardverhalten sind sichtbarer vereinheitlicht. |

---

### v2.7.229 — 26. März 2026 · Audit-Batch 311, Member-Dashboard-Overview-Entry weiter verschlankt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.229** | 🔴 fix | Admin/Member | **`CMS/admin/member-dashboard.php` nutzt für Legacy-Sektionen jetzt den gemeinsamen `redirect-alias-shell.php` statt einen eigenen Header-Redirect-Helfer zu pflegen**: der Overview-Entry hängt dadurch näher am vorhandenen Redirect-Wrapper-Vertrag. |
| **2.7.229** | 🟠 perf | Admin/Member | **Der eigentliche Overview-Flow delegiert Guard und Rendering jetzt klarer an `member-dashboard-page.php`**: der Entry spart verteiltes Redirect-Boilerplate und hält seinen Legacy-/Overview-Pfad kompakter. |
| **2.7.229** | 🟡 refactor | Admin/Member | **Die Datei konzentriert sich jetzt auf Legacy-Section-Normalisierung und kanonische Overview-Konfiguration**: Redirect- und Access-Standardverhalten bleiben stärker in den gemeinsamen Wrappers statt in einem zusätzlichen Sonderpfad. |

---

### v2.7.228 — 26. März 2026 · Audit-Batch 310, SEO-Wrapper auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.228** | 🔴 fix | Admin/SEO | **`CMS/admin/seo-page.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: die gemeinsame SEO-/Analytics-Schicht hängt dadurch näher am vorhandenen Shared-Entry-Vertrag. |
| **2.7.228** | 🟠 perf | Admin/SEO | **Section-, Capability-, Redirect- und Datenladepfade laufen jetzt zentral über die Shared-Shell**: der Wrapper spart verteiltes Boilerplate, während der Analytics-Alias automatisch denselben Shell-Flow mitnutzt. |
| **2.7.228** | 🟡 refactor | Admin/SEO | **Die Datei konzentriert sich jetzt auf Section-Registry, Capability-Helfer und Action-Dispatch**: CSRF-, Redirect- und Render-Standardverhalten bleiben sichtbar zentral in der gemeinsamen Shell. |

---

### v2.7.227 — 26. März 2026 · Audit-Batch 309, Users-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.227** | 🔴 fix | Admin/Users | **`CMS/admin/users.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Listen- und Edit-Flow hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.227** | 🟠 perf | Admin/Users | **Die Benutzerverwaltung löst Listen- und Edit-Kontext jetzt zentral über die Shared-Shell auf und kann Save-Fehler inline auf derselben Edit-Ansicht weiter rendern**: GridJS- und Flash-Kontext müssen dadurch nicht länger separat im Entry nachgebaut werden. |
| **2.7.227** | 🟡 refactor | Admin/Users | **Die Datei konzentriert sich jetzt auf Action-Allowlist, View-Auflösung und Dispatch**: zusätzlich leakt der Save-Fehlerpfad im `UsersModule` keine rohen Exception-Texte mehr in die Admin-Oberfläche. |

---

### v2.7.226 — 26. März 2026 · Audit-Batch 308, Posts-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.226** | 🔴 fix | Admin/Posts | **`CMS/admin/posts.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Listen- und Edit-Flow hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.226** | 🟠 perf | Admin/Posts | **Die Posts-Verwaltung löst Listen- und Edit-Kontext jetzt zentral über die Shared-Shell auf und kann Save-Fehler inline auf derselben Edit-Ansicht weiter rendern**: GridJS-, Editor.js- und Flash-Kontext müssen dadurch nicht länger separat im Entry nachgebaut werden. |
| **2.7.226** | 🟡 refactor | Admin/Posts | **Die Datei konzentriert sich jetzt auf Action-Allowlist, View-Auflösung und Dispatch**: die Posts-Views rendern Shell-basiertes Feedback zusätzlich über das gemeinsame Flash-Partial. |

---

### v2.7.225 — 26. März 2026 · Audit-Batch 307, Pages-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.225** | 🔴 fix | Admin/Pages | **`CMS/admin/pages.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Listen- und Edit-Flow hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.225** | 🟠 perf | Admin/Core | **Die Shared-Shell unterstützt jetzt optionales Inline-Rendering nach POST-Fehlern**: komplexe Edit-Seiten wie die Seitenverwaltung können Fehlermeldungen im selben Renderpfad zeigen, ohne dafür wieder einen separaten Entry-Sonderpfad zu behalten. |
| **2.7.225** | 🟡 refactor | Admin/Pages | **Die Datei konzentriert sich jetzt auf Action-Allowlist, View-Auflösung und Dispatch**: die Seiten-Views rendern Shell-basiertes Feedback zusätzlich über das gemeinsame Flash-Partial. |

---

### v2.7.224 — 26. März 2026 · Audit-Batch 306, Packages-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.224** | 🔴 fix | Admin/Subscriptions | **`CMS/admin/packages.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Paket-CRUD und Paket-Einstellungen hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.224** | 🟠 perf | Admin/Subscriptions | **Die Paketverwaltung spart verteiltes Entry-Boilerplate und zeigt Shell-basiertes Feedback im View jetzt ebenfalls über das gemeinsame Flash-Partial**: Save-, Seed-, Toggle-, Delete- und Settings-Pfade müssen ihren Alert-Flow nicht länger separat pflegen. |
| **2.7.224** | 🟡 refactor | Admin/Subscriptions | **Die Datei konzentriert sich jetzt auf Action-Allowlist, ID-Normalisierung und Dispatch**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.223 — 26. März 2026 · Audit-Batch 305, Media-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.223** | 🔴 fix | Admin/Media | **`CMS/admin/media.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Tab-/Redirect-/Flash-/Renderpfad zu pflegen**: Bibliothek, Kategorien und Einstellungen hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.223** | 🟠 perf | Admin/Media | **Die Medienverwaltung spart verteiltes Entry-Boilerplate und reicht Media-Zusatz-Token sowie tab-spezifische View-/Asset-Kontexte zentral an die Views durch**: Upload-, Kategorie- und Settings-Aktionen landen konsistenter wieder auf ihrer Zielansicht. |
| **2.7.223** | 🟡 refactor | Admin/Media | **Die Datei konzentriert sich jetzt auf Action-Allowlist, Pfadnormalisierung und Dispatch**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.222 — 26. März 2026 · Audit-Batch 304, Orders-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.222** | 🔴 fix | Admin/Subscriptions | **`CMS/admin/orders.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: CSRF-Flow, Datenladung und View-Rendering hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.222** | 🟠 perf | Admin/Subscriptions | **Der Orders-Entry spart verteiltes Boilerplate und hält den Statusfilter über den PRG-Flow näher am zentralen Shell-Vertrag**: Bestellstatus- und Paketzuweisungsaktionen landen konsistenter wieder auf derselben Übersicht. |
| **2.7.222** | 🟡 refactor | Admin/Subscriptions | **Die Datei konzentriert sich jetzt auf Action-Allowlist, Normalisierung und Dispatch**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.221 — 26. März 2026 · Audit-Batch 303, Menü-Editor-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.221** | 🔴 fix | Admin/Menus | **`CMS/admin/menu-editor.php` läuft jetzt über die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Zugriffsgate, CSRF-Flow, Datenladung und Redirect-Ziele hängen damit näher am Shared-Entry-Vertrag. |
| **2.7.221** | 🟠 perf | Admin/Menus | **Der Menü-Editor spart verteiltes Entry-Boilerplate und zeigt Shell-basiertes Feedback im View jetzt ebenfalls über das gemeinsame Flash-Partial**: Bearbeitungs- und Löschpfade müssen ihren Alert-Flow nicht länger separat pflegen. |
| **2.7.221** | 🟡 refactor | Admin/Menus | **Die Datei reduziert sich jetzt auf kleine Helper, Action-Dispatch und Redirect-Konfiguration**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.220 — 26. März 2026 · Audit-Batch 302, Hub-Sites-Entry auf die erweiterte Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.220** | 🔴 fix | Admin/Hub | **`CMS/admin/hub-sites.php` nutzt jetzt die erweiterte `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad mit Multi-View-Sonderlogik zu pflegen**: Listen-, Edit- und Template-Views hängen damit näher an einem gemeinsamen Shared-Entry-Vertrag. |
| **2.7.220** | 🟠 perf | Admin/Hub | **Die Shell kann jetzt auch dynamische View-, Titel- und Asset-Kontexte zentral auflösen**: Hub-Sites spart verteiltes Boilerplate selbst bei wechselnden Views und übernimmt künftige Shell-Verbesserungen automatisch mit. |
| **2.7.220** | 🟡 refactor | Admin/Hub | **Der Entry konzentriert sich jetzt auf View-Normalisierung, Action-Dispatch und Redirect-Zielberechnung**: Flash-Ausgabe in Templates/Edit-Views läuft ebenfalls konsistent über das gemeinsame Partial. |

---

### v2.7.219 — 26. März 2026 · Audit-Batch 301, Mail-Settings-Entry auf die erweiterte Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.219** | 🔴 fix | Admin/System | **`CMS/admin/mail-settings.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Tab-/Redirect-/Flash-/Renderpfad zu pflegen**: Guard, CSRF-Flow und Tab-Redirects hängen dadurch näher am Shared-Entry-Vertrag. |
| **2.7.219** | 🟠 perf | Admin/System | **Die Mail-/OAuth2-Verwaltung spart verteiltes Boilerplate und reicht `currentTab` sowie API-CSRF-Token zentral als Template-Kontext durch**: Transport-, Azure-, Graph-, Log- und Queue-Aktionen landen wieder konsistent auf ihrem Zieltab. |
| **2.7.219** | 🟡 refactor | Admin/System | **Die Datei konzentriert sich jetzt auf Tab-/Action-Normalisierung und Handler-Mapping**: Standardverhalten liegt zentral in Shared-Shell und View-Kontext. |

---

### v2.7.218 — 26. März 2026 · Audit-Batch 300, Legal-Sites-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.218** | 🔴 fix | Admin/Legal | **`CMS/admin/legal-sites.php` läuft jetzt über die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Access-Guard, CSRF-Flow, Profil-Roundtrip und Datenladung hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.218** | 🟠 perf | Admin/Legal | **Die Legal-Sites sparen verteiltes Entry-Boilerplate für Profilspeicherung, Generator und Seitenerstellung**: Flash-Ausgabe im View wurde zugleich auf das gemeinsame Partial harmonisiert. |
| **2.7.218** | 🟡 refactor | Admin/Legal | **Der Entry reduziert sich jetzt auf Action-Allowlist, Profil-Helfer und Dispatch-Konfiguration**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.217 — 26. März 2026 · Audit-Batch 299, Landing-Page-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.217** | 🔴 fix | Admin/Landing | **`CMS/admin/landing-page.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Guard, CSRF-Flow, Datenladung und Rendering hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.217** | 🟠 perf | Admin/Landing | **Der tab-basierte Landing-Page-Editor spart verteiltes Entry-Boilerplate und hält den aktiven Tab über den PRG-Flow stabil**: Header-, Content-, Footer-, Design- und Plugin-Aktionen landen wieder konsistent auf ihrem jeweiligen Tab. |
| **2.7.217** | 🟡 refactor | Admin/Landing | **Die Datei konzentriert sich jetzt auf Tab-/Action-Normalisierung und Dispatch**: Standardverhalten sowie Flash-Ausgabe liegen zentral in Shared-Shell und Flash-Partial. |

---

### v2.7.216 — 26. März 2026 · Audit-Batch 298, Gruppen-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.216** | 🔴 fix | Admin/Users | **`CMS/admin/groups.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Admin-Guard, CSRF-Flow, Datenladung und Rendering hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.216** | 🟠 perf | Admin/Users | **Die Gruppenverwaltung spart verteiltes Entry-Boilerplate für Save- und Delete-Aktionen**: künftige Shell-Verbesserungen greifen damit automatisch auch für die Benutzergruppen-Verwaltung. |
| **2.7.216** | 🟡 refactor | Admin/Users | **Die Datei konzentriert sich jetzt auf Action-Allowlist, ID-Normalisierung und Dispatch**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.215 — 26. März 2026 · Audit-Batch 297, Font-Manager-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.215** | 🔴 fix | Admin/Themes | **`CMS/admin/font-manager.php` läuft jetzt über die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Read-/Write-Gates, CSRF-Flow, Datenladung und Rendering hängen damit näher am vorhandenen Shared-Entry-Vertrag. |
| **2.7.215** | 🟠 perf | Admin/Themes | **Der Font-Manager spart verteiltes Boilerplate und nutzt im View jetzt ebenfalls das gemeinsame Flash-Partial**: Theme-Scans, Self-Hosting und Save-Aktionen teilen sich denselben zentralen Feedback- und Request-Flow. |
| **2.7.215** | 🟡 refactor | Admin/Themes | **Der Entry reduziert sich jetzt auf Capability-Helfer, Allowlist und Dispatch-Konfiguration**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.214 — 26. März 2026 · Audit-Batch 296, Firewall-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.214** | 🔴 fix | Admin/Security | **`CMS/admin/firewall.php` läuft jetzt über die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Guard, CSRF-Flow, Datenladung und Rendering hängen damit näher am vorhandenen Shared-Entry-Vertrag. |
| **2.7.214** | 🟠 perf | Admin/Security | **Die Firewall spart verteiltes Entry-Boilerplate und übernimmt Shell-Verbesserungen automatisch mit**: die Datei konzentriert sich wieder auf Action-Allowlist, Capability-Checks und Dispatch. |
| **2.7.214** | 🟡 refactor | Admin/Security | **Der Entry reduziert sich jetzt auf kleine Helper und Shell-Konfiguration**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.213 — 26. März 2026 · Audit-Batch 295, Error-Report-Endpoint auf gemeinsamen POST-Action-Wrapper standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.213** | 🔴 fix | Admin/System | **`CMS/admin/error-report.php` nutzt jetzt den neuen `post-action-shell.php` statt eigenen CSRF-/Redirect-/Flash-Boilerplate**: der POST-only-Endpunkt bleibt damit näher an einem kleinen gemeinsamen Request-Vertrag. |
| **2.7.213** | 🟠 perf | Admin/System | **Der Error-Report-Endpunkt spart wiederkehrende Redirect- und Flash-Helfer**: künftige POST-Wrapper-Verbesserungen greifen automatisch auch für Admin-Aktionsendpunkte ohne eigene View. |
| **2.7.213** | 🟡 refactor | Admin/System | **Die Datei konzentriert sich jetzt auf Payload-Normalisierung und Service-Aufruf**: Standardpfade liegen zentral in der neuen Post-Action-Shell. |

---

### v2.7.212 — 26. März 2026 · Audit-Batch 294, Design-Settings-Alias auf gemeinsamen Redirect-Wrapper standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.212** | 🔴 fix | Admin/Design | **`CMS/admin/design-settings.php` nutzt jetzt denselben kleinen Redirect-Alias-Wrapper statt eigenen Guard-/Redirect-Boilerplate**: Capability-Check und Zielpfad bleiben sichtbar, während der eigentliche Redirect-Flow zentral abgewickelt wird. |
| **2.7.212** | 🟠 perf | Admin/Design | **Der Design-Alias spart redundante Funktionsdefinitionen und Redirect-Pfade pro Request**: künftige Alias-Anpassungen greifen dadurch auf einer kleinen gemeinsamen Schicht statt in Einzeldateien. |
| **2.7.212** | 🟡 refactor | Admin/Design | **Die Datei reduziert sich jetzt auf Zugriffsgate und Zielroute**: Alias-Boilerplate liegt zentral in `redirect-alias-shell.php`. |

---

### v2.7.211 — 26. März 2026 · Audit-Batch 293, Deletion-Requests-Alias auf gemeinsamen Redirect-Wrapper standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.211** | 🔴 fix | Admin/Legal | **`CMS/admin/deletion-requests.php` nutzt jetzt denselben kleinen Redirect-Alias-Wrapper statt eigenen Guard-/Redirect-Boilerplate**: der Alias bleibt damit näher an einem kleinen gemeinsamen Vertrag und pflegt nicht länger eigene Redirect-Helfer. |
| **2.7.211** | 🟠 perf | Admin/Legal | **Der Löschantrags-Alias spart redundante Funktionsdefinitionen und harmoniert mit verwandten Legal-Alias-Seiten**: dieselbe Redirect-Schicht wird im selben Zug auch für `privacy-requests.php` verwendet. |
| **2.7.211** | 🟡 refactor | Admin/Legal | **Die Datei reduziert sich jetzt auf Zugriffsgate und Zielroute**: Alias-Boilerplate liegt zentral in `redirect-alias-shell.php`. |

---

### v2.7.210 — 26. März 2026 · Audit-Batch 292, Data-Requests-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.210** | 🔴 fix | Admin/Legal | **`CMS/admin/data-requests.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt eines eigenen Redirect-/Flash-/Renderpfads**: Admin-Guard, Token-Prüfung, Scope-Dispatch, Datenladung und View-Rendering hängen dadurch näher am vorhandenen Shared-Entry-Vertrag. |
| **2.7.210** | 🟠 perf | Admin/Legal | **Die DSGVO-Arbeitsseite spart verteiltes Entry-Boilerplate für Auskunfts- und Löschanträge**: beide Modulpfade werden konsistent über denselben Shell-Request-Flow bedient. |
| **2.7.210** | 🟡 refactor | Admin/Legal | **Der Entry konzentriert sich jetzt auf Scope-Allowlist und Aktions-Dispatch**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.209 — 26. März 2026 · Audit-Batch 291, Cookie-Manager-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.209** | 🔴 fix | Admin/Legal | **`CMS/admin/cookie-manager.php` läuft jetzt über die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Admin-Guard, CSRF-Flow, Datenladung und Rendering hängen damit näher am vorhandenen Shared-Entry-Vertrag. |
| **2.7.209** | 🟠 perf | Admin/Legal | **Der Cookie-Manager spart verteiltes Boilerplate und zeigt Flash-Feedback jetzt sichtbar im View an**: Scanner-, Import-, Save- und Delete-Aktionen nutzen denselben zentralen Session-Alert-Flow. |
| **2.7.209** | 🟡 refactor | Admin/Legal | **Die Datei reduziert sich jetzt auf Allowlists, Normalisierung und Dispatch**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.208 — 26. März 2026 · Audit-Batch 290, Documentation-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.208** | 🔴 fix | Admin/System | **`CMS/admin/documentation.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt eines eigenen Redirect-/Flash-/Renderpfads**: Admin-Guard, Token-Prüfung, Datenladung und View-Rendering hängen dadurch näher am bestehenden Shared-Entry-Vertrag. |
| **2.7.208** | 🟠 perf | Admin/System | **Die Dokumentationsseite spart verteiltes Entry-Boilerplate und hält die Dokumentauswahl auch nach POST-Redirects stabil**: das ausgewählte Dokument bleibt über denselben query-fähigen Shell-Redirect erhalten. |
| **2.7.208** | 🟡 refactor | Admin/System | **Der Entry konzentriert sich jetzt auf Dokument-Normalisierung und Sync-Dispatch**: Standardpfade liegen sichtbar zentral in der Admin-Shell. |

---

### v2.7.207 — 26. März 2026 · Audit-Batch 289, Comments-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.207** | 🔴 fix | Admin/Content | **`CMS/admin/comments.php` läuft jetzt über die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: View-Guard, CSRF-Flow, Datenladung und Rendering hängen damit näher am vorhandenen Shared-Entry-Vertrag. |
| **2.7.207** | 🟠 perf | Admin/Content | **Die Kommentarverwaltung spart verteiltes Boilerplate und hält den Statusfilter über den PRG-Flow stabil**: Freigabe-, Spam-, Trash- und Delete-Aktionen landen wieder auf derselben gefilterten Listenansicht. |
| **2.7.207** | 🟡 refactor | Admin/Content | **Die Datei reduziert sich jetzt auf Allowlists, Normalisierung und Aktions-Dispatch**: Standardverhalten bleibt zentral in der Shared-Shell. |

---

### v2.7.206 — 26. März 2026 · Audit-Batch 288, Backup-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.206** | 🔴 fix | Admin/System | **`CMS/admin/backups.php` läuft jetzt über die gemeinsame `section-page-shell.php` statt einen eigenen Redirect-/Flash-/Renderpfad zu pflegen**: Read-/Write-Checks, Datenladung und View-Rendering hängen dadurch näher am vorhandenen Shared-Entry-Vertrag. |
| **2.7.206** | 🟠 perf | Admin/System | **Der Backup-Entry spart verteiltes Boilerplate und doppelten Guard-Aufwand**: spätere Shell-Verbesserungen greifen automatisch auch für Backup & Restore. |
| **2.7.206** | 🟡 refactor | Admin/System | **Der Entry ist jetzt auf Aktions- und Zugriffsnormalisierung reduziert**: Redirect-, Flash- und Render-Standardlogik bleibt zentral in der Admin-Shell. |

---

### v2.7.205 — 26. März 2026 · Audit-Batch 287, AntiSpam-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.205** | 🔴 fix | Admin/Security | **`CMS/admin/antispam.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt eines eigenen Redirect-/Flash-/Renderpfads**: der Entry übernimmt dadurch keine lose Standardlogik mehr neben der Shell. |
| **2.7.205** | 🟠 perf | Admin/Security | **Der AntiSpam-Entry spart wiederkehrendes Boilerplate für Guard, Datenladung und Rendering**: künftige Shell-Verbesserungen wirken automatisch auch auf den Security-Bereich. |
| **2.7.205** | 🟡 refactor | Admin/Security | **Die Datei konzentriert sich jetzt auf Aktions-Allowlist und Capability-Gates**: Standardverhalten liegt sichtbar zentral in der Shared-Shell. |

---

### v2.7.204 — 26. März 2026 · Audit-Batch 286, Dashboard-Entry auf die gemeinsame Section-Shell standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.204** | 🔴 fix | Admin/Dashboard | **`CMS/admin/index.php` nutzt jetzt die gemeinsame `section-page-shell.php` statt eines separaten Sonderpfads**: Auth-Guard, Modul-Initialisierung, Datenladung und Rendering laufen dadurch nicht mehr losgelöst vom vorhandenen Shared-Entry-Muster. |
| **2.7.204** | 🟠 perf | Admin/Dashboard | **Der Dashboard-Entry spart doppelten Boilerplate-Overhead und bleibt näher am zentralen Wrapper-Vertrag**: spätere Shell-Verbesserungen greifen damit automatisch auch auf `/admin`. |
| **2.7.204** | 🟡 refactor | Admin/Dashboard | **Das Dashboard hängt jetzt sichtbarer an derselben Admin-Section-Infrastruktur wie andere standardisierte Entrys**: Sonderlogik im Entry wurde auf Konfiguration reduziert. |

---

### v2.7.203 — 26. März 2026 · Audit-Batch 285, Section-Page-Shell bei Access-, Flash- und Asset-Normalisierung nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.203** | 🔴 fix | Admin/Core | **`CMS/admin/partials/section-page-shell.php` kapselt Access-Checks, Flash-Payloads und Asset-Normalisierung jetzt robuster zentral**: Shared-Entrys übernehmen dadurch keine losen Session-, Asset- oder Redirect-Annahmen mehr unterschiedlich pro Wrapper. |
| **2.7.203** | 🟠 perf | Admin/Core | **Die gemeinsame Shell filtert ungültige CSS-/JS-Assets früher und vereinheitlicht Flash-/Redirect-Pfade**: Standard-Entrys müssen diese wiederkehrenden Schritte nicht mehr einzeln nachbauen. |
| **2.7.203** | 🟡 refactor | Admin/Core | **Der Shared-Entry-Vertrag ist klarer geworden**: Access-Checker, Flash-Payload und Asset-Listen liegen sichtbar zentral statt verteiltem Boilerplate in einzelnen Entrys. |

---

### v2.7.202 — 26. März 2026 · Audit-Batch 284, Redirect- und 404-Entries auf section-spezifische Admin-Datensichten umgestellt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.202** | 🔴 fix | Admin/SEO | **`redirect-manager.php` und `not-found-monitor.php` ziehen ihre Daten jetzt über section-spezifische Modulzugriffe statt denselben Voll-Datensatz zu laden**: Redirect-Regeln und 404-Logs übernehmen dadurch keine unnötigen Fremddaten mehr zwischen den beiden SEO-Entrys. |
| **2.7.202** | 🟠 perf | Admin/SEO | **Redirect-Manager und 404-Monitor vermeiden unnötigen Admin-Overfetch vor dem Rendern**: jede Seite erhält nur noch ihren tatsächlich benötigten Redirect-, Log-, Target- und Site-Scope. |
| **2.7.202** | 🟡 refactor | Admin/SEO | **Die Entry-Pfade hängen näher an einem kleinen Section-Datenvertrag**: die Seitenauswahl bleibt sichtbar am Modul statt losem Vertrauen auf einen gemeinsamen Voll-Dump. |

---

### v2.7.201 — 26. März 2026 · Audit-Batch 283, Redirect-Manager-Modul um section-spezifische Datenzugriffe ergänzt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.201** | 🔴 fix | Admin/SEO | **`RedirectManagerModule.php` bietet jetzt getrennte Loader für Redirect-Manager und 404-Monitor**: der Modulvertrag reicht dadurch nicht mehr pauschal denselben Voll-Datensatz an beide SEO-Seiten weiter. |
| **2.7.201** | 🟠 perf | Admin/SEO | **Das Modul kann Redirect- und 404-Views gezielter bedienen**: Seiten fordern nur noch den benötigten Scope statt unnötige Nachbar-Daten mitzuschleppen. |
| **2.7.201** | 🟡 refactor | Admin/SEO | **Der Admin-Vertrag bleibt klarer lesbar**: `getRedirectManagerData()` und `getNotFoundMonitorData()` dokumentieren die Datensicht jetzt explizit am Modul. |

---

### v2.7.200 — 26. März 2026 · Audit-Batch 282, RedirectService auf getrennte Admin-Datensichten und Shared-Helper aufgeteilt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.200** | 🔴 fix | Core/SEO | **`RedirectService.php` liefert Redirect-Manager und 404-Monitor jetzt über getrennte Admin-Datensichten statt nur über `getAdminData()`**: Redirect-Regeln, 404-Logs, Stats und Targets bleiben dadurch näher am jeweils benötigten Seiten-Scope. |
| **2.7.200** | 🟠 perf | Core/SEO | **Die Redirect-Verwaltung vermeidet unnötigen Datenballast zwischen Redirect- und 404-Pfaden**: Entrys transportieren nicht länger zwangsläufig dieselbe Vollmenge aus Regeln, Logs, Targets und Sites. |
| **2.7.200** | 🟡 refactor | Core/SEO | **Gemeinsame Helfer für Redirect-Regeln, 404-Logs, Targets und Stats kapseln die Datensichten jetzt sichtbar zentral**: der Service hängt näher an einem kleinen wiederverwendbaren Admin-Datenvertrag. |

---

### v2.7.199 — 26. März 2026 · Audit-Batch 281, Dashboard-Modul nutzt vorhandene Stats für Attention-Items weiter

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.199** | 🔴 fix | Admin/Dashboard | **`DashboardModule.php` reicht den bereits geladenen Stats-Bestand jetzt direkt an die Attention-Items weiter**: der Dashboard-Renderpfad löst dadurch keine zweite Vollberechnung derselben Kennzahlen mehr aus. |
| **2.7.199** | 🟠 perf | Admin/Dashboard | **Das Dashboard spart eine unnötige Service-Runde pro Request**: KPIs, Highlights und Attention-Items teilen sich dieselbe Stats-Basis statt Doppelarbeit. |
| **2.7.199** | 🟡 refactor | Admin/Dashboard | **Der Modulvertrag wird expliziter**: vorbereitete Dashboard-Stats bleiben sichtbar im selben Renderkontext statt implizit nochmals im Service nachgeladen zu werden. |

---

### v2.7.198 — 26. März 2026 · Audit-Batch 280, DashboardService akzeptiert vorhandene Stats für Attention-Items

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.198** | 🔴 fix | Core/Dashboard | **`DashboardService::getAttentionItems()` kann vorhandene Stats jetzt direkt verwerten**: Attention-Items hängen dadurch nicht mehr zwingend an einer erneuten Komplettberechnung des Stats-Bundles. |
| **2.7.198** | 🟠 perf | Core/Dashboard | **Der Service reduziert unnötige Aggregationsarbeit im selben Request**: bereits vorbereitete Dashboard-Werte werden wiederverwendet statt sofort neu eingesammelt. |
| **2.7.198** | 🟡 refactor | Core/Dashboard | **Die Attention-Logik hängt näher an einem kleinen Datenvertrag**: vorbereitete Stats werden optional explizit hereingereicht statt implizit aus einem zweiten Full-Load zu stammen. |

---

### v2.7.197 — 26. März 2026 · Audit-Batch 279, DashboardService cached Komplett-Stats pro Request

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.197** | 🔴 fix | Core/Dashboard | **`DashboardService.php` cached `getAllStats()` jetzt request-lokal**: wiederholte Stats-Zugriffe im selben Dashboard-Lauf verursachen dadurch keine zweite identische Komplettaggregation mehr. |
| **2.7.197** | 🟠 perf | Core/Dashboard | **Das Dashboard spart doppelte User-, Page-, Media-, Session-, Security-, Performance- und Order-Statistikarbeit**: identische Daten werden pro Request nur einmal aufgebaut. |
| **2.7.197** | 🟡 refactor | Core/Dashboard | **Die Service-Schicht bekommt einen klareren Request-State**: bereits aggregierte Dashboard-Stats bleiben sichtbar zentral am Service statt losem erneuten Vollaufruf. |

---

### v2.7.196 — 26. März 2026 · Audit-Batch 278, Performance-Settings-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.196** | 🔴 fix | Admin/Performance | **`performance-settings.php` übergibt nur noch die kanonische `settings`-Section an den Shared-Wrapper**: der Settings-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Performance-Registry. |
| **2.7.196** | 🟠 perf | Admin/Performance | **Der Settings-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.196** | 🟡 refactor | Admin/Performance | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.195 — 26. März 2026 · Audit-Batch 277, Performance-Sessions-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.195** | 🔴 fix | Admin/Performance | **`performance-sessions.php` übergibt nur noch die kanonische `sessions`-Section an den Shared-Wrapper**: der Sessions-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Performance-Registry. |
| **2.7.195** | 🟠 perf | Admin/Performance | **Der Sessions-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.195** | 🟡 refactor | Admin/Performance | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.194 — 26. März 2026 · Audit-Batch 276, Performance-Media-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.194** | 🔴 fix | Admin/Performance | **`performance-media.php` übergibt nur noch die kanonische `media`-Section an den Shared-Wrapper**: der Medien-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Performance-Registry. |
| **2.7.194** | 🟠 perf | Admin/Performance | **Der Medien-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.194** | 🟡 refactor | Admin/Performance | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.193 — 26. März 2026 · Audit-Batch 275, Performance-Datenbank-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.193** | 🔴 fix | Admin/Performance | **`performance-database.php` übergibt nur noch die kanonische `database`-Section an den Shared-Wrapper**: der Datenbank-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Performance-Registry. |
| **2.7.193** | 🟠 perf | Admin/Performance | **Der Datenbank-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.193** | 🟡 refactor | Admin/Performance | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.192 — 26. März 2026 · Audit-Batch 274, Performance-Cache-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.192** | 🔴 fix | Admin/Performance | **`performance-cache.php` übergibt nur noch die kanonische `cache`-Section an den Shared-Wrapper**: der Cache-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Performance-Registry. |
| **2.7.192** | 🟠 perf | Admin/Performance | **Der Cache-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.192** | 🟡 refactor | Admin/Performance | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.191 — 26. März 2026 · Audit-Batch 273, Monitoring-Cron-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.191** | 🔴 fix | Admin/System | **`monitor-cron-status.php` übergibt nur noch die kanonische `cron`-Section an den Shared-Wrapper**: der Cron-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Monitoring-Registry. |
| **2.7.191** | 🟠 perf | Admin/System | **Der Cron-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.191** | 🟡 refactor | Admin/System | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.190 — 26. März 2026 · Audit-Batch 272, Monitoring-E-Mail-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.190** | 🔴 fix | Admin/System | **`monitor-email-alerts.php` übergibt nur noch die kanonische `email-alerts`-Section an den Shared-Wrapper**: der Monitoring-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Monitoring-Registry. |
| **2.7.190** | 🟠 perf | Admin/System | **Der E-Mail-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.190** | 🟡 refactor | Admin/System | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.189 — 26. März 2026 · Audit-Batch 271, Monitoring-Health-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.189** | 🔴 fix | Admin/System | **`monitor-health-check.php` übergibt nur noch die kanonische `health-check`-Section an den Shared-Wrapper**: der Health-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Monitoring-Registry. |
| **2.7.189** | 🟠 perf | Admin/System | **Der Health-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.189** | 🟡 refactor | Admin/System | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.188 — 26. März 2026 · Audit-Batch 270, Monitoring-Scheduled-Tasks-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.188** | 🔴 fix | Admin/System | **`monitor-scheduled-tasks.php` übergibt nur noch die kanonische `scheduled-tasks`-Section an den Shared-Wrapper**: der Scheduled-Tasks-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Monitoring-Registry. |
| **2.7.188** | 🟠 perf | Admin/System | **Der Scheduled-Tasks-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.188** | 🟡 refactor | Admin/System | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.187 — 26. März 2026 · Audit-Batch 269, Monitoring-Disk-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.187** | 🔴 fix | Admin/System | **`monitor-disk-usage.php` übergibt nur noch die kanonische `disk`-Section an den Shared-Wrapper**: der Disk-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Monitoring-Registry. |
| **2.7.187** | 🟠 perf | Admin/System | **Der Disk-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.187** | 🟡 refactor | Admin/System | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.186 — 26. März 2026 · Audit-Batch 268, Monitoring-Response-Time-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.186** | 🔴 fix | Admin/System | **`monitor-response-time.php` übergibt nur noch die kanonische `response-time`-Section an den Shared-Wrapper**: der Response-Time-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Monitoring-Registry. |
| **2.7.186** | 🟠 perf | Admin/System | **Der Response-Time-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.186** | 🟡 refactor | Admin/System | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.185 — 26. März 2026 · Audit-Batch 267, Performance-Overview-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.185** | 🔴 fix | Admin/Performance | **`performance.php` übergibt nur noch die kanonische `overview`-Section an den Shared-Wrapper**: der Overview-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Performance-Registry. |
| **2.7.185** | 🟠 perf | Admin/Performance | **Der Overview-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.185** | 🟡 refactor | Admin/Performance | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.184 — 26. März 2026 · Audit-Batch 266, Diagnose-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.184** | 🔴 fix | Admin/System | **`diagnose.php` übergibt nur noch die kanonische `diagnose`-Section an den Shared-Wrapper**: der Diagnose-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Monitoring-Registry. |
| **2.7.184** | 🟠 perf | Admin/System | **Der Diagnose-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.184** | 🟡 refactor | Admin/System | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.183 — 26. März 2026 · Audit-Batch 265, Info-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.183** | 🔴 fix | Admin/System | **`info.php` übergibt nur noch die kanonische `info`-Section an den Shared-Wrapper**: der Info-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen Monitoring-Registry. |
| **2.7.183** | 🟠 perf | Admin/System | **Der Info-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.183** | 🟡 refactor | Admin/System | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.182 — 26. März 2026 · Audit-Batch 264, Analytics-Alias auf kanonische Section-Konfiguration reduziert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.182** | 🔴 fix | Admin/SEO | **`analytics.php` übergibt nur noch die kanonische `analytics`-Section an den Shared-Wrapper**: der Analytics-Alias übernimmt damit keine eigene Route-/View-/Titel-Duplikation mehr außerhalb der zentralen SEO-Registry. |
| **2.7.182** | 🟠 perf | Admin/SEO | **Der Analytics-Alias spart redundante Konfigurationsarbeit vor dem Wrapper**: Route-, View- und Active-Page-Werte kommen nur noch aus der kanonischen Section-Matrix. |
| **2.7.182** | 🟡 refactor | Admin/SEO | **Der Alias hängt näher am Shared-Wrapper-Vertrag**: die Datei bleibt ein schlanker Section-Entry statt eigenem Konfigurationsduplikat. |

---

### v2.7.181 — 26. März 2026 · Audit-Batch 263, Performance-Wrapper auf kanonische Section-Seitenregistries gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.181** | 🔴 fix | Admin/Performance | **`performance-page.php` bindet Overview-, Cache-, Datenbank-, Medien-, Session- und Settings-Unterseiten jetzt an eine kanonische Section-Registry für Route, View, Titel und Active-Page**: section-fremde oder divergierende Alias-Konfigurationen werden damit nicht mehr lose in denselben Shared-Wrapper hineingereicht. |
| **2.7.181** | 🟠 perf | Admin/Performance | **Der Performance-Wrapper spart redundante Alias-Konfigurationsarbeit vor dem Renderpfad**: Route-/View-Metadaten kommen nur noch aus einer zentralen Matrix statt aus jedem Unterseiten-Entry separat. |
| **2.7.181** | 🟡 refactor | Admin/Performance | **Der Wrapper hängt näher an einem kleinen Section-Vertrag**: Kanonische Seitenkonfiguration und Section-Normalisierung liegen jetzt sichtbar zentral statt verteiltem Konfigurationsduplikat in Alias-Dateien. |

---

### v2.7.180 — 26. März 2026 · Audit-Batch 262, System-Monitor-Wrapper auf kanonische Section-Seitenregistries gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.180** | 🔴 fix | Admin/System | **`system-monitor-page.php` bindet Info-, Diagnose- und Monitoring-Unterseiten jetzt an eine kanonische Section-Registry für Route, View, Titel und Active-Page**: section-fremde oder divergierende Alias-Konfigurationen werden damit nicht mehr lose in denselben Shared-Wrapper hineingereicht. |
| **2.7.180** | 🟠 perf | Admin/System | **Der Monitoring-Wrapper spart redundante Alias-Konfigurationsarbeit vor dem Renderpfad**: Route-/View-Metadaten kommen nur noch aus einer zentralen Matrix statt aus jedem Unterseiten-Entry separat. |
| **2.7.180** | 🟡 refactor | Admin/System | **Der Wrapper hängt näher an einem kleinen Section-Vertrag**: Kanonische Seitenkonfiguration und Section-Normalisierung liegen jetzt sichtbar zentral statt verteiltem Konfigurationsduplikat in Alias-Dateien. |

---

### v2.7.179 — 25. März 2026 · Audit-Batch 261, gemeinsame Section-Shell bei Route-/View-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.179** | 🔴 fix | Admin/Core | **`section-page-shell.php` normalisiert Shared-Routen jetzt serverseitig, verlangt vorhandene View-Dateien und bündelt Redirects zentral**: gemeinsame Member-, Performance- und System-Pfade übernehmen dadurch keine losen `route_path`- oder `view_file`-Annahmen mehr direkt in der generischen Shell. |
| **2.7.179** | 🟠 perf | Admin/Core | **Die generische Section-Shell verwirft ungültige View-/Route-Konfigurationen früher und billiger**: falsche oder leere Shell-Ziele scheitern vor unnötigen Header-, Sidebar- oder View-Ladevorgängen im Shared-Wrapper. |
| **2.7.179** | 🟡 refactor | Admin/Core | **Die gemeinsame Shell hängt näher an einem kleinen Shared-Vertrag aus Route-, View- und Redirect-Helfern**: Normalisierung und Redirects liegen jetzt sichtbar zentral statt losem Vertrauen auf rohe Wrapper-Konfigurationen. |

---

### v2.7.178 — 25. März 2026 · Audit-Batch 260, SEO-Suite-Modul auf section-spezifische Datensichten umgestellt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.178** | 🔴 fix | Admin/SEO | **`SeoSuiteModule.php` liefert Dashboard-, Audit-, Analytics-, Meta-, Social-, Schema-, Sitemap- und Technical-Unterseiten jetzt nur noch ihren tatsächlich benötigten Datenausschnitt**: SEO-Unterseiten übernehmen damit keine unnötigen Voll-Dumps aus Audit-, Tracking-, Schema-, Sitemap- und Redirect-Pfaden mehr stillschweigend über denselben Sammelaufruf. |
| **2.7.178** | 🟠 perf | Admin/SEO | **SEO-Unterseiten vermeiden teure Sammelabfragen außerhalb ihres eigenen Scopes**: Analytics-, Sitemap-, Schema- oder Technical-Seiten ziehen nur noch ihre jeweiligen Datenpfade und sparen unnötige Audit-, Tracking- oder Redirect-Arbeit bei fremden Bereichen. |
| **2.7.178** | 🟡 refactor | Admin/SEO | **Das Modul hängt näher an einem section-gebundenen Datenvertrag**: `getSectionData()` und ein gemeinsamer Section-Kontext kapseln die abschnittsspezifische Sicht jetzt sichtbar zentral statt losem Vertrauen auf `getData()` für jede Unterseite. |

---

### v2.7.177 — 25. März 2026 · Audit-Batch 259, gemeinsame SEO-Schicht auf section-spezifische Datenladung gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.177** | 🔴 fix | Admin/SEO | **`seo-page.php` lädt pro SEO-Unterseite jetzt section-spezifische Daten statt pauschal den kompletten SEO-Datensatz**: Shared-SEO-Pfade verlassen sich damit nicht länger auf denselben Voll-Dump für Dashboard, Audit, Analytics, Meta, Social, Schema, Sitemap oder Technical. |
| **2.7.177** | 🟠 perf | Admin/SEO | **Der gemeinsame SEO-Wrapper vermeidet unnötige Voll-Ladepfade für Analytics-, Schema-, Sitemap- und Technical-Seiten**: Renderpfade ziehen nur noch ihren jeweiligen Scope und sparen unnötige Audit-, Tracking- oder Redirect-Daten bei fremden Bereichen. |
| **2.7.177** | 🟡 refactor | Admin/SEO | **Der Wrapper hängt näher an einem kleinen Section-Vertrag aus Capability-, Action- und Data-Scope-Gates**: Render- und POST-Pfade bleiben sichtbarer zentral gebündelt statt losem Sammelabruf über dieselbe Full-Data-Schiene. |

---

### v2.7.176 — 25. März 2026 · Audit-Batch 258, Member-Dashboard-Modul auf section-spezifische Datensichten umgestellt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.176** | 🔴 fix | Admin/Member | **`MemberDashboardModule.php` liefert Overview-, General-, Widget-, Profilfeld-, Design-, Frontend-, Notification-, Onboarding- und Plugin-Widget-Unterseiten jetzt nur noch ihren tatsächlich benötigten Datenausschnitt**: Member-Unterseiten übernehmen damit keine unnötigen Voll-Dumps aus Stats-, Widget-, Profil- oder Plugin-Widget-Pfaden mehr stillschweigend über denselben Sammelaufruf. |
| **2.7.176** | 🟠 perf | Admin/Member | **Member-Unterseiten vermeiden teure Sammelabfragen außerhalb ihres eigenen Scopes**: Widgets, Notifications, Profilfelder oder Plugin-Widgets lösen nur noch ihre jeweiligen Hilfsdaten aus und sparen unnötige Stats-/Plugin-/Overview-Arbeit bei fremden Bereichen. |
| **2.7.176** | 🟡 refactor | Admin/Member | **Das Modul hängt näher an einem section-gebundenen Datenvertrag**: `getSectionData()` und kleine Empty-/Overview-Helfer kapseln die abschnittsspezifische Sicht jetzt sichtbar zentral statt losem Vertrauen auf `getData()` für jede Unterseite. |

---

### v2.7.175 — 25. März 2026 · Audit-Batch 257, gemeinsame Member-Dashboard-Schicht auf section-spezifische Datenladung gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.175** | 🔴 fix | Admin/Member | **`member-dashboard-page.php` lädt pro Unterseite jetzt section-spezifische Daten statt pauschal den kompletten Dashboard-Datensatz**: Shared-Member-Pfade verlassen sich damit nicht länger auf denselben Voll-Dump für Overview, General, Widgets, Profilfelder, Design, Notifications, Onboarding oder Plugin-Widgets. |
| **2.7.175** | 🟠 perf | Admin/Member | **Der gemeinsame Member-Dashboard-Wrapper vermeidet unnötige Voll-Ladepfade für alle Unterseiten**: Renderpfade ziehen nur noch ihren jeweiligen Scope und sparen unnötige Stats-, Widget-, Profil- oder Plugin-Widget-Daten bei fremden Bereichen. |
| **2.7.175** | 🟡 refactor | Admin/Member | **Der Wrapper hängt näher an einem kleinen Section-Vertrag aus Capability-, Action- und Data-Scope-Gates**: Render- und POST-Pfade bleiben sichtbarer zentral gebündelt statt losem Sammelabruf über dieselbe Full-Data-Schiene. |

---

### v2.7.174 — 25. März 2026 · Audit-Batch 256, Performance-Modul auf section-spezifische Datensichten umgestellt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.174** | 🔴 fix | Admin/Performance | **`PerformanceModule.php` liefert Cache-, Datenbank-, Medien-, Session- und Settings-Unterseiten jetzt nur noch ihren tatsächlich benötigten Datenausschnitt**: Unterseiten übernehmen damit keine unnötigen Voll-Dumps aus Cache-, Media-, DB-, Session- und PHP-Info-Pfaden mehr stillschweigend über denselben Sammelaufruf. |
| **2.7.174** | 🟠 perf | Admin/Performance | **Performance-Unterseiten vermeiden teure Sammelmetriken außerhalb ihres eigenen Scopes**: Cache-, DB-, Medien- oder Session-Seiten lösen nur noch ihre jeweiligen Metriken aus und sparen unnötige Telemetrie- und Scan-Arbeit bei fremden Bereichen. |
| **2.7.174** | 🟡 refactor | Admin/Performance | **Das Modul hängt näher an einem section-gebundenen Datenvertrag**: `getSectionData()` kapselt die abschnittsspezifische Datensicht sichtbar zentral statt losem Vertrauen auf `getData()` für jede Unterseite. |

---

### v2.7.173 — 25. März 2026 · Audit-Batch 255, gemeinsame Performance-Schicht bei Section-Daten und Read-Gate nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.173** | 🔴 fix | Admin/Performance | **`performance-page.php` bindet jetzt auch die reine Ansicht explizit an `manage_settings` und lädt pro Unterseite nur noch deren section-spezifische Daten**: Shared-Performance-Pfade verlassen sich damit nicht länger bloß auf den generischen Admin-Guard oder pauschale Voll-Datensätze für jede Unterseite. |
| **2.7.173** | 🟠 perf | Admin/Performance | **Der gemeinsame Performance-Wrapper vermeidet unnötige Voll-Ladepfade für Cache-, Medien-, DB-, Session- und Settings-Seiten**: Unterseiten scheitern früher am Read-Gate und laden bei gültigem Zugriff nur noch ihren eigenen Scope. |
| **2.7.173** | 🟡 refactor | Admin/Performance | **Der Wrapper hängt näher an einem kleinen Section-Vertrag aus Read-Gate, Action-Allowlist und Daten-Scope**: Render- und POST-Pfade bleiben sichtbarer zentral gebündelt statt losem Sammelabruf über dieselbe Full-Data-Schiene. |

---

### v2.7.172 — 25. März 2026 · Audit-Batch 254, SystemInfo-Modul auf section-spezifische Datensichten umgestellt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.172** | 🔴 fix | Admin/System | **`SystemInfoModule.php` liefert Info-, Diagnose- und Monitoring-Unterseiten jetzt nur noch ihren tatsächlich benötigten Datenausschnitt**: Info-, Diagnose-, Cron-, Disk-, Response-Time-, Health-, Scheduled-Tasks- und Alert-Pfade übernehmen damit keine unnötigen Voll-Dumps sensibler Runtime-, Query- oder Monitoring-Daten mehr quer über denselben Sammelaufruf. |
| **2.7.172** | 🟠 perf | Admin/System | **System-Unterseiten vermeiden teure Komplett-Scans außerhalb ihres eigenen Scopes**: Cron-, Disk-, Health- und Response-Time-Seiten ziehen nur noch ihre jeweiligen Prüfungen statt pauschal das gesamte Systempaket mitzuladen. |
| **2.7.172** | 🟡 refactor | Admin/System | **Das Modul hängt näher an einem section-gebundenen Datenvertrag**: `getSectionData()` sowie verkleinerte Info-/Diagnose-Sichten kapseln den benötigten Scope sichtbar zentral statt losem Vertrauen auf den Voll-`getData()`-Pfad. |

---

### v2.7.171 — 25. März 2026 · Audit-Batch 253, gemeinsame System-Monitor-Schicht bei Section-/Action-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.171** | 🔴 fix | Admin/System | **`system-monitor-page.php` normalisiert Monitoring-Sections jetzt serverseitig, bindet die Sammelschicht an `manage_settings` und akzeptiert POST-Aktionen nur noch pro passender Unterseite**: Diagnose- und Alert-Pfade übernehmen dadurch keine losen `section`-/`action`-Werte oder pauschalen Admin-Zugriffe mehr direkt im Shared-Wrapper. |
| **2.7.171** | 🟠 perf | Admin/System | **Die gemeinsame System-Monitor-Schicht verwirft section- oder capability-fremde Requests früher und billiger**: unzulässige Render- oder POST-Pfade scheitern vor unnötigen Modulaufrufen in Diagnose-, Cron-, Health- oder Alert-Ansichten. |
| **2.7.171** | 🟡 refactor | Admin/System | **Der System-Sammel-Wrapper hängt näher an einem kleinen Section-Vertrag**: Section-Normalisierung, Action-Allowlist und Capability-Gates liegen jetzt sichtbar zentral statt losem Vertrauen auf die generische Section-Shell. |

---

### v2.7.170 — 25. März 2026 · Audit-Batch 252, gemeinsame SEO-/Analytics-Schicht bei section-spezifischen Capability-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.170** | 🔴 fix | Admin/SEO | **`seo-page.php` bindet SEO-Unterseiten jetzt an section-spezifische Read-/Write-Capabilities**: Analytics akzeptiert Lesezugriffe nur noch über `manage_settings` oder `view_analytics`, während Mutationen pro Section kontrolliert an `manage_settings` gebunden bleiben und capability-fremde Requests nicht mehr lose durch denselben Shared-Wrapper laufen. |
| **2.7.170** | 🟠 perf | Admin/SEO | **Die gemeinsame SEO-Schicht verwirft section-fremde oder capability-fremde Requests früher und billiger**: Render- und POST-Pfade scheitern vor unnötigen Modulaufrufen, wenn Read-/Write-Vertrag und angeforderte Unterseite nicht zusammenpassen. |
| **2.7.170** | 🟡 refactor | Admin/SEO | **Der SEO-Sammel-Wrapper hängt näher an einem kleinen, section-gebundenen Admin-Vertrag**: Read-/Write-Matrix und Capability-Helfer liegen jetzt sichtbar zentral statt losem Vertrauen auf den pauschalen Sammel-Entry. |

---

### v2.7.169 — 25. März 2026 · Audit-Batch 251, Member-Dashboard-Entry bei Legacy-Sections und Overview-Capabilities nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.169** | 🔴 fix | Admin/Member | **`member-dashboard.php` normalisiert Legacy-Sections jetzt wieder serverseitig und bindet die Overview an passende Read-Capabilities**: Legacy-Weiterleitungen übernehmen damit keine losen `section`-Werte mehr, und die Dashboard-Übersicht verlässt sich nicht länger nur auf `isAdmin()`. |
| **2.7.169** | 🟠 perf | Admin/Member | **Der Member-Dashboard-Entry verwirft unzulässige Legacy-Ziele früher und billiger**: section-fremde oder capability-fremde Requests scheitern vor unnötigen Redirects in nachgelagerte Unterseiten. |
| **2.7.169** | 🟡 refactor | Admin/Member | **Der Overview-Entry hängt näher am gemeinsamen Member-Dashboard-Vertrag**: Legacy-Routenmap und Overview-Access liegen jetzt sichtbar zentral statt losem Query-Vertrauen im Alias-Entry. |

---

### v2.7.168 — 25. März 2026 · Audit-Batch 250, Mail-Settings-Entry bei Read-/Write-Capability-Matrix nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.168** | 🔴 fix | Admin/System | **`mail-settings.php` trennt Leserechte und Mutationen jetzt explizit über eine Capability-Matrix**: Transport-, Azure-, Graph-, Logs- und Queue-Bereiche bleiben nur noch für `manage_settings`/`manage_system` lesbar, während Mutationen kontrolliert an `manage_settings` gebunden sind. |
| **2.7.168** | 🟠 perf | Admin/System | **Der Mail-Entry verwirft capability-fremde Mutationen früher und billiger**: POST-Pfade scheitern bereits vor CSRF- und Modul-Dispatch, wenn der Write-Vertrag nicht erfüllt ist. |
| **2.7.168** | 🟡 refactor | Admin/System | **Der Mail-Settings-Entry hängt näher am kleinen Wrapper-Vertrag aus Tab-, Action- und Capability-Gates**: Read-/Write-Helfer liegen jetzt sichtbar im Einstieg statt bloßem Vertrauen auf den breiten Admin-Guard. |

---

### v2.7.167 — 25. März 2026 · Audit-Batch 249, Legal-Sites-Entry bei Capability- und Template-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.167** | 🔴 fix | Admin/Legal | **`legal-sites.php` bindet Read-/Write-Zugriffe jetzt explizit an `manage_settings` und normalisiert Actions früher**: Save-, Profil-, Generate- und Create-Page-Pfade übernehmen damit keine capability-fremden Requests oder losen Action-Werte mehr direkt im Einstieg. |
| **2.7.167** | 🟠 perf | Admin/Legal | **Der Legal-Sites-Entry verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen oder ungültige `template_type`-Werte scheitern vor unnötigen Modulaufrufen im Generator- und Page-Flow. |
| **2.7.167** | 🟡 refactor | Admin/Legal | **Der Rechtstext-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Capability-, Action- und Template-Gates liegen jetzt sichtbar in kleinen Helfern statt losem Vertrauen auf den Modulpfad. |

---

### v2.7.166 — 25. März 2026 · Audit-Batch 248, Font-Manager-Entry bei Read-/Write-Capabilities nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.166** | 🔴 fix | Admin/Font Manager | **`font-manager.php` bindet Ansicht und Mutationen jetzt explizit an `manage_settings`**: Save-, Scan-, Delete- und Google-Font-Download-Pfade übernehmen damit keine pauschalen Admin-Zugriffe mehr nur über `isAdmin()`. |
| **2.7.166** | 🟠 perf | Admin/Font Manager | **Der Font-Manager-Entry verwirft capability-fremde Mutationen früher und billiger**: POST-Pfade scheitern bereits vor CSRF- und Handler-Dispatch, wenn der Settings-Vertrag nicht erfüllt ist. |
| **2.7.166** | 🟡 refactor | Admin/Font Manager | **Der Entry hängt näher am kleinen Wrapper-Vertrag aus Action- und Capability-Gates**: Read-/Write-Helfer liegen jetzt sichtbar zentral statt losem Vertrauen auf den generischen Admin-Guard. |

---

### v2.7.165 — 25. März 2026 · Audit-Batch 247, Backup-Entry bei Read-/Write-Capability-Split nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.165** | 🔴 fix | Admin/System | **`backups.php` trennt Backup-Ansicht und Mutationen jetzt explizit über Read-/Write-Capabilities**: Listen und Restore-nahe Views bleiben an `manage_settings`/`manage_system` gebunden, während Create- und Delete-Pfade nur noch mit passender Write-Capability laufen. |
| **2.7.165** | 🟠 perf | Admin/System | **Der Backup-Entry verwirft capability-fremde Mutationen früher und billiger**: POST-Pfade scheitern bereits vor CSRF- und Handler-Dispatch, wenn der Write-Vertrag nicht erfüllt ist. |
| **2.7.165** | 🟡 refactor | Admin/System | **Der Backup-Wrapper hängt näher am Modulvertrag des `BackupsModule`**: Read-/Write-Helfer spiegeln jetzt sichtbar die vorhandene Capability-Trennung statt bloßem Vertrauen auf `isAdmin()`. |

---

### v2.7.164 — 25. März 2026 · Audit-Batch 246, gemeinsame Member-Dashboard-Schicht bei Section- und Capability-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.164** | 🔴 fix | Admin/Member | **`member-dashboard-page.php` normalisiert Section und Aktion jetzt bereits im Shared-Wrapper und bindet jede Unterseite an section-spezifische Capabilities**: General-, Design-, Widget-, Profilfeld-, Notification-, Onboarding- und Plugin-Widget-Pfade übernehmen dadurch keine bereichsfremden Zugriffe oder losen `action`-Werte mehr über denselben Sammel-Entry. |
| **2.7.164** | 🟠 perf | Admin/Member | **Die gemeinsame Member-Dashboard-Schicht verwirft unzulässige Requests früher und billiger**: section-fremde Zugriffe oder unbekannte Aktionen scheitern vor unnötigen Modulaufrufen und halten den Shared-Wrapper kompakter. |
| **2.7.164** | 🟡 refactor | Admin/Member | **Der Member-Dashboard-Sammel-Wrapper hängt näher an einem kleinen, section-gebundenen Admin-Vertrag**: Capability-Matrix, Section-Normalisierung und Save-Allowlist liegen jetzt sichtbar zentral statt losem Vertrauen auf den generischen Section-Shell-Guard. |

---

### v2.7.163 — 25. März 2026 · Audit-Batch 245, Media-Entry bei Capability- und Action-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.163** | 🔴 fix | Admin/Media | **`media.php` bindet Medien-Mutationen jetzt explizit an `manage_media` und normalisiert Actions serverseitig vor dem Dispatch**: Upload-, Folder-, Delete-, Kategorie- und Settings-Pfade übernehmen dadurch keine losen `action`-Werte oder capability-fremden Admin-Zugriffe mehr direkt im Einstieg. |
| **2.7.163** | 🟠 perf | Admin/Media | **Der Media-Entry verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen oder capability-fremde Requests scheitern vor unnötigen Service- und Modulaufrufen im Medienpfad. |
| **2.7.163** | 🟡 refactor | Admin/Media | **Der Medien-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Capability-Gate und Action-Normalisierung liegen jetzt sichtbar zentral statt nur losem Vertrauen auf `isAdmin()` plus nachgelagerte Modulgrenzen. |

---

### v2.7.162 — 25. März 2026 · Audit-Batch 244, Landing-Page-Entry bei Tab-, Action- und Capability-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.162** | 🔴 fix | Admin/Landing | **`landing-page.php` begrenzt Tabs und POST-Aktionen jetzt explizit über kleine Allowlists, prüft Feature-IDs serverseitig und bindet den Entry an `manage_settings`**: Header-, Content-, Footer-, Design-, Feature- und Plugin-Pfade übernehmen dadurch keine losen `action`-Werte oder pauschalen Admin-Zugriffe mehr direkt im Einstieg. |
| **2.7.162** | 🟠 perf | Admin/Landing | **Der Landing-Page-Entry verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen, ungültige Feature-IDs oder capability-fremde Requests scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.162** | 🟡 refactor | Admin/Landing | **Der Landing-Page-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Tab-/Action-Normalisierung, ID-Gates und Capability-Prüfung liegen jetzt sichtbar zentral statt losem Request-Handling im Einstieg. |

---

### v2.7.161 — 25. März 2026 · Audit-Batch 243, Menü-Editor-Entry bei Action-, ID- und Capability-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.161** | 🔴 fix | Admin/Themes | **`menu-editor.php` begrenzt Menü-Mutationen jetzt explizit über eine kleine Action-Allowlist, normalisiert `menu_id` serverseitig und bindet den Entry an `manage_settings`**: Save-, Delete- und Item-Save-Pfade übernehmen dadurch keine losen `action`-/`menu_id`-Werte oder pauschalen Admin-Zugriffe mehr direkt im Einstieg. |
| **2.7.161** | 🟠 perf | Admin/Themes | **Der Menü-Editor verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen, ungültige Menü-IDs oder capability-fremde Requests scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.161** | 🟡 refactor | Admin/Themes | **Der Menü-Editor hängt näher am gemeinsamen Admin-Wrapper-Muster**: Action-, ID- und Capability-Gates liegen jetzt sichtbar in kleinen Helfern statt losem Vertrauen auf `isAdmin()` und rohe Request-Casts im Einstieg. |

---

### v2.7.160 — 25. März 2026 · Audit-Batch 242, gemeinsame Performance-Wrapper-Schicht auf section-gebundene Action-Gates gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.160** | 🔴 fix | Admin/Performance | **`performance-page.php` normalisiert Section und Action jetzt gemeinsam serverseitig und akzeptiert pro Performance-Unterseite nur noch die tatsächlich erlaubten Mutationen**: Cache-, Datenbank-, Medien- und Session-Pfade übernehmen dadurch keine losen `action`-Werte mehr quer durch den gemeinsamen Sammel-Wrapper. |
| **2.7.160** | 🟠 perf | Admin/Performance | **Die gemeinsame Performance-Schicht verwirft unzulässige Mutationen früher und billiger**: section-fremde oder unbekannte Aktionen scheitern vor unnötigen Modulaufrufen und halten Cache-, DB-, Media- und Session-Pfade kompakter. |
| **2.7.160** | 🟡 refactor | Admin/Performance | **Der Performance-Sammel-Wrapper hängt näher an einem kleinen, section-gebundenen Admin-Vertrag**: Section-/Action-Allowlist und Capability-Gates über `manage_settings` liegen jetzt sichtbar zentral statt losem Sammel-Dispatch im POST-Handler. |

---

### v2.7.159 — 25. März 2026 · Audit-Batch 241, 404-Monitor-Entry bei Action- und Capability-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.159** | 🔴 fix | Admin/SEO | **`not-found-monitor.php` begrenzt 404-Mutationen jetzt wieder explizit über eine kleine Action-Allowlist und bindet den Entry an `manage_settings`**: Save-Redirect- und Log-Clear-Pfade übernehmen dadurch keine losen `action`-Werte oder pauschalen Admin-Zugriffe mehr direkt im Einstieg. |
| **2.7.159** | 🟠 perf | Admin/SEO | **Der 404-Monitor verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen oder capability-fremde Requests scheitern vor unnötigen Modulaufrufen und bleiben im PRG-Flow kompakter. |
| **2.7.159** | 🟡 refactor | Admin/SEO | **Der 404-Monitor hängt näher am gemeinsamen SEO-Wrapper-Muster**: Action-Allowlist, Capability-Gate und Flash-/Redirect-Pfade liegen jetzt sichtbar zentral statt losem POST-Dispatch im Einstieg. |

---

### v2.7.158 — 25. März 2026 · Audit-Batch 240, Users-Entry bei Action-, ID-, Bulk- und View-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.158** | 🔴 fix | Admin/Users | **`users.php` normalisiert Benutzer-Aktionen, positive IDs, Bulk-IDs und View-Ziele jetzt bereits im Wrapper**: Save-, Delete- und Bulk-Pfade übernehmen dadurch keine losen `action`-/`id`-/`ids[]`- oder `action`-Query-Werte mehr direkt in das Modul. |
| **2.7.158** | 🟠 perf | Admin/Users | **Der Users-Entry verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen, ungültige Benutzer-IDs oder leere/unsaubere Bulk-Auswahlen scheitern vor unnötigen Modulaufrufen und bleiben im PRG-Flow kompakter. |
| **2.7.158** | 🟡 refactor | Admin/Users | **Der Benutzer-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Flash-, Redirect-, View- und Bulk-Normalisierung liegen jetzt sichtbar in kleinen Helfern, und der Einstieg ist explizit an `manage_users` gebunden. |

---

### v2.7.157 — 25. März 2026 · Audit-Batch 239, Pages-Entry bei Action-, Bulk-, View- und Capability-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.157** | 🔴 fix | Admin/Pages | **`pages.php` normalisiert Seiten-Aktionen, Bulk-IDs und View-Ziele jetzt bereits im Wrapper und blockt ungültige Delete-/Bulk-Requests früher**: Delete- und Bulk-Pfade übernehmen dadurch keine losen `action`-, `id`-, `ids[]`- oder `bulk_ids`-Werte mehr direkt aus dem Request. |
| **2.7.157** | 🟠 perf | Admin/Pages | **Der Pages-Entry verwirft unzulässige Seiten-Mutationen früher und billiger**: unbekannte Aktionen, ungültige Seiten-IDs oder leere Bulk-Auswahlen scheitern vor unnötigen Modulaufrufen und halten den PRG-Pfad kompakter. |
| **2.7.157** | 🟡 refactor | Admin/Pages | **Der Seiten-Entry hängt näher an einem kleinen Capability-/Wrapper-Vertrag**: View-Normalisierung, Flash-/Redirect-Helfer und der Access-Guard über `manage_pages` liegen jetzt sichtbar zentral statt losem Request-Handling im Einstieg. |

---

### v2.7.156 — 25. März 2026 · Audit-Batch 238, Posts-Entry bei Action-, Bulk-, Kategorie- und View-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.156** | 🔴 fix | Admin/Posts | **`posts.php` normalisiert Beitrags-Aktionen, Bulk-Werte, positive IDs, Kategorie-IDs und View-Ziele jetzt bereits im Wrapper**: Save-, Delete-, Bulk- und Kategoriepfade übernehmen dadurch keine losen `action`-/`id`-/`cat_id`-/`ids[]`- oder `action`-Query-Werte mehr direkt aus dem Request. |
| **2.7.156** | 🟠 perf | Admin/Posts | **Der Posts-Entry verwirft unzulässige Content-Mutationen früher und billiger**: unbekannte Aktionen, ungültige Beitrags-/Kategorie-IDs oder leere/unsaubere Bulk-Auswahlen scheitern vor unnötigen Modulaufrufen und bleiben im PRG-Flow kompakter. |
| **2.7.156** | 🟡 refactor | Admin/Posts | **Der Beitrags-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Flash-, Redirect-, Bulk- und View-Normalisierung liegen jetzt sichtbar in kleinen Helfern statt losem Session- und Request-Handling im Einstieg. |

---

### v2.7.155 — 25. März 2026 · Audit-Batch 237, Privacy-Requests-Alias auf echten Redirect-Zweck zurückgebaut

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.155** | 🔴 fix | Admin/Legal | **`privacy-requests.php` bildet wieder nur noch seinen tatsächlichen Redirect-Zweck auf `/admin/data-requests` ab**: der Alias schleppt damit keinen unerreichbaren Legacy-POST-, Modul- oder View-Code mehr hinter einem sofortigen Redirect mit. |
| **2.7.155** | 🟠 perf | Admin/Legal | **Der Privacy-Requests-Alias bleibt kompakter und vorhersehbarer**: tote Altlogik wird nicht mehr mitgeladen oder mitgepflegt, obwohl der Entry fachlich nur als Weiterleitung dient. |
| **2.7.155** | 🟡 refactor | Admin/Legal | **Der Alias hängt näher am gemeinsamen Redirect-Entry-Muster**: Guard-, Ziel- und Redirect-Pfade liegen jetzt sichtbar klein und explizit im Einstieg statt hinter unerreichbarer Alt-Orchestrierung versteckt. |

---

### v2.7.154 — 25. März 2026 · Audit-Batch 236, Redirect-Manager-Entry bei Action-, ID-, Slug- und Capability-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.154** | 🔴 fix | Admin/SEO | **`redirect-manager.php` normalisiert Redirect-Aktionen, positive IDs und Slug-Filter jetzt bereits im Wrapper und bindet jede Mutation an `manage_settings`**: Save-, Delete-, Toggle- und Slug-Cleanup-Pfade übernehmen dadurch keine losen `action`-/`id`-/`slug_filter`-Werte oder pauschalen Admin-Zugriffe mehr stillschweigend im Einstieg. |
| **2.7.154** | 🟠 perf | Admin/SEO | **Der Redirect-Manager verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen, ungültige Redirect-IDs, leere Slug-Cleanup-Requests oder capability-fremde Zugriffe scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.154** | 🟡 refactor | Admin/SEO | **Der Redirect-Manager hängt näher am gemeinsamen Admin-Wrapper-Muster**: Action-Allowlist, Capability-Gates sowie Flash-/Redirect-Helfer liegen jetzt sichtbar zentral statt losem Switch-Dispatch und direktem Session-Handling im Einstieg. |

---

### v2.7.153 — 25. März 2026 · Audit-Batch 235, Firewall-Entry bei Action-, ID- und Capability-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.153** | 🔴 fix | Admin/Security | **`firewall.php` normalisiert Firewall-Aktionen und positive Regel-IDs jetzt bereits im Wrapper und bindet jede Mutation an eine explizite Capability**: Settings-, Toggle- und Delete-Pfade übernehmen dadurch keine losen `action`-/`id`-Werte oder pauschalen Vollzugriff nur wegen `isAdmin()` mehr stillschweigend im Einstieg. |
| **2.7.153** | 🟠 perf | Admin/Security | **Der Firewall-Entry verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen, ungültige Regel-IDs oder capability-fremde Requests scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.153** | 🟡 refactor | Admin/Security | **Der Firewall-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Action-, ID- und Capability-Gates liegen jetzt sichtbar in kleinen Helfern statt losem Request-Parsing und pauschalem Admin-Check im Einstieg. |

---

### v2.7.152 — 25. März 2026 · Audit-Batch 234, Packages-Entry bei Action-/ID-Gates nachgezogen und Logger-Fatal entschärft

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.152** | 🔴 fix | Admin/Subscriptions | **`packages.php` normalisiert Paket-Aktionen und positive IDs jetzt bereits im Wrapper**: Delete-, Toggle- und Settings-nahe Mutationen übernehmen dadurch keine losen `action`-/`id`-Werte mehr direkt aus `POST` in den Dispatch. |
| **2.7.152** | 🟠 perf | Admin/Subscriptions | **Der Packages-Entry verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen oder ungültige Paket-IDs scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.152** | 🟡 refactor | Admin/Subscriptions | **Der Packages-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Action- und ID-Normalisierung liegen jetzt sichtbar in kleinen Helfern statt losem Request-Parsing im Einstieg. |
| **2.7.152** | 🔴 fix | Admin/Subscriptions | **`PackagesModule.php` nutzt den Core-Logger wieder über den vorhandenen Channel-Vertrag**: Ausnahme-Pfade rufen jetzt `Logger::instance()->withChannel('admin.packages')` auf statt eines statischen Nicht-API-Aufrufs, der sonst bei Fehlerfällen in einen zusätzlichen Fatal laufen konnte. |

---

### v2.7.151 — 25. März 2026 · Audit-Batch 233, Design-Settings-Redirect an manage_settings gebunden

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.151** | 🔴 fix | Admin/Themes | **`design-settings.php` bindet den Redirect auf den Theme-Editor jetzt explizit an `manage_settings` statt bloß an einen pauschalen Admin-Check**: der Design-/Theme-Customizing-Pfad leitet damit nicht mehr jeden Admin stillschweigend weiter, sondern nur noch Nutzer mit der tatsächlich passenden Settings-Berechtigung. |
| **2.7.151** | 🟠 perf | Admin/Themes | **Der Design-Settings-Entry bleibt kompakt und verwirft unzulässige Zugriffe früher**: capability-fremde Requests scheitern vor dem Wechsel in den Theme-Editor und sparen unnötige Weiterleitungen in nachgelagerte Editor-Pfade. |
| **2.7.151** | 🟡 refactor | Admin/Themes | **Der Redirect-Entry hängt näher an einem kleinen, expliziten Access-Vertrag**: Access- und Fallback-Pfade liegen jetzt sichtbar in kleinen Helfern statt nur lose an einem pauschalen `isAdmin()`-Check zu hängen. |

---

### v2.7.150 — 25. März 2026 · Audit-Batch 232, AntiSpam-Entry bei Action-, ID- und Capability-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.150** | 🔴 fix | Admin/Security | **`antispam.php` normalisiert AntiSpam-Aktionen und positive IDs jetzt bereits im Wrapper und bindet jede Mutation an eine explizite Capability**: Settings- und Blacklist-Pfade übernehmen dadurch keine losen `action`-/`id`-Werte oder pauschalen Vollzugriff nur wegen `isAdmin()` mehr stillschweigend im Einstieg. |
| **2.7.150** | 🟠 perf | Admin/Security | **Der AntiSpam-Entry verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen, ungültige IDs oder capability-fremde Requests scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.150** | 🟡 refactor | Admin/Security | **Der AntiSpam-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Action-/ID-Normalisierung, Pull-Alert und Redirect-Helfer liegen jetzt sichtbar zentral statt teils losem Session- und Request-Handling im Einstieg. |

---

### v2.7.149 — 25. März 2026 · Audit-Batch 231, Kommentar-Entry bei Action-, Status- und Bulk-Normalisierung nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.149** | 🔴 fix | Admin/Comments | **`comments.php` normalisiert Kommentar-Aktionen, Statuswerte, positive IDs und Bulk-IDs jetzt bereits im Wrapper**: Moderations-, Delete- und Bulk-Pfade übernehmen dadurch keine losen Request-Werte mehr direkt aus `POST` in das Kommentar-Modul. |
| **2.7.149** | 🟠 perf | Admin/Comments | **Der Kommentar-Entry verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen, rohe IDs, ungültige Statuswerte und ausufernde/unsaubere Bulk-IDs scheitern vor unnötigen Modulaufrufen. |
| **2.7.149** | 🟡 refactor | Admin/Comments | **Der Kommentar-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Action-, Status-, ID- und Bulk-Normalisierung liegen jetzt sichtbar in kleinen Helfern statt losem Request-Parsing direkt im Dispatch. |
| **2.7.149** | 🔴 fix | Admin/Backups | **`BackupsModule.php` nutzt wieder den vorhandenen Core-Logger-Vertrag**: Der produktive Fatal Error durch den nicht existierenden Aufruf `Logger::channel()` ist behoben, weil das Modul den Logger jetzt korrekt über `Logger::instance()->withChannel('admin.backups')` bezieht. |

---

### v2.7.148 — 25. März 2026 · Audit-Batch 230, gemeinsame SEO-Wrapper-Schicht auf Registry- und Action-Gates gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.148** | 🔴 fix | Admin/SEO | **`seo-page.php` akzeptiert für die gemeinsamen SEO-Unterseiten jetzt nur noch kanonische Sections, View-Dateien, Routen und seitengebundene POST-Aktionen**: lose `section`-, `action`-, `view_file`- oder `return_to`-Werte laufen damit nicht mehr quer durch den zentralen Wrapper in falsche Render- oder Redirect-Pfade. |
| **2.7.148** | 🟠 perf | Admin/SEO | **Die gemeinsame SEO-Eintrittsschicht verwirft unzulässige Unterseiten- und Mutationspfade früher und billiger**: unbekannte Actions oder fremde Redirect-Ziele scheitern vor unnötigen Modulaufrufen, View-Loads oder PRG-Sprüngen. |
| **2.7.148** | 🟡 refactor | Admin/SEO | **Der SEO-Sammel-Wrapper hängt jetzt näher an einem expliziten Registry-Vertrag**: Section-Metadaten, Action-Allowlist sowie Flash-/Redirect-Helfer liegen sichtbar zentral statt lose aus variablen Konfigurationswerten zusammengebaut zu werden. |

---

### v2.7.147 — 25. März 2026 · Audit-Batch 229, Orders-Entry bei Action-, Status- und Billing-Normalisierung nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.147** | 🔴 fix | Admin/Orders | **`orders.php` normalisiert POST-Aktionen, positive IDs, Statuswerte und Billing-Cycles jetzt bereits im Wrapper**: Statuswechsel-, Delete- und Zuweisungspfade übernehmen dadurch keine losen Request-Werte mehr direkt in das Orders-Modul. |
| **2.7.147** | 🟠 perf | Admin/Orders | **Der Orders-Entry verwirft unzulässige Bestellmutationen früher und billiger**: unbekannte Aktionen, rohe IDs und ungültige Status-/Billing-Werte scheitern vor unnötigen Modulaufrufen oder tieferen Guard-Pfaden. |
| **2.7.147** | 🟡 refactor | Admin/Orders | **Der Orders-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Action-, ID-, Status- und Billing-Normalisierung liegen jetzt sichtbar in kleinen Helfern statt roher Request-Casts direkt im Dispatch. |

---

### v2.7.146 — 25. März 2026 · Audit-Batch 228, Site-Tables-Entry bei Action-, ID- und View-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.146** | 🔴 fix | Admin/Tables | **`site-tables.php` begrenzt POST-Mutationen jetzt wieder explizit über eine kleine Action-Allowlist und normalisiert IDs bereits im Wrapper**: Delete-, Duplicate- und Save-Redirect-Pfade übernehmen dadurch keine losen Aktionen oder rohen `id`-Werte mehr direkt aus dem Request. |
| **2.7.146** | 🟠 perf | Admin/Tables | **Der Tabellen-Entry verwirft unzulässige Mutationen früher und billiger**: unbekannte Aktionen, ungültige IDs und unsaubere Edit-Redirect-Ziele scheitern vor unnötigen Modulaufrufen oder Redirect-Verzweigungen im PRG-Flow. |
| **2.7.146** | 🟡 refactor | Admin/Tables | **Der Site-Tables-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Flash-, Redirect-, Allowlist- und View-Normalisierung liegen jetzt sichtbar in kleinen Helfern statt losem Switch- und Query-Handling im Einstieg. |

---

### v2.7.145 — 25. März 2026 · Audit-Batch 227, Kommentar-Post-Links auf zentralen Permalink-Service gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.145** | 🔴 fix | Admin/Comments | **`CommentsModule.php` baut Beitrag-Links in der Kommentar-Moderation jetzt über den zentralen `PermalinkService` statt harte `/blog/{slug}`-Pfade zu verwenden**: Die Kommentarliste bleibt damit konsistent zur konfigurierbaren Post-Permalink-Struktur des Cores und läuft bei abweichenden Blog-URLs nicht in stille Fehlverlinkungen. |
| **2.7.145** | 🟠 perf | Admin/Comments | **Die Kommentar-Moderation vermeidet zusätzlichen URL-Fallback-Müll im UI-Pfad**: `CommentService` liefert `published_at` und `created_at` direkt mit, sodass die Listenansicht Beitrag-URLs ohne nachträgliches Raten oder weitere Lookups sauber aufbauen kann. |
| **2.7.145** | 🟡 refactor | Admin/Comments | **Der Kommentarpfad hängt näher am gemeinsamen Routing-/SEO-Vertrag des Cores**: Die Moderationsliste konsumiert jetzt denselben Permalink-Baustein wie Redirect-, SEO- und Post-Module statt einen isolierten Legacy-Linkpfad lokal nachzubauen. |

---

### v2.7.144 — 25. März 2026 · Audit-Batch 226, Error-Report-Entry bei Payload-Gates und Source-URL-Normalisierung nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.144** | 🔴 fix | Admin/System | **`error-report.php` begrenzt Report-Felder und JSON-Payloads jetzt bereits im Wrapper und akzeptiert `source_url` nur noch als interne/same-site Quelle**: ausufernde oder lose Fehlerreport-Daten laufen dadurch nicht mehr direkt aus dem Request in den Service-Pfad. |
| **2.7.144** | 🟠 perf | Admin/System | **Der Error-Report-Entry verwirft auffällige Report-Payloads früher und billiger**: übergroße JSON-Strings und tiefere/ausufernde Strukturen scheitern vor unnötiger Service- und Datenbankarbeit im PRG-Flow. |
| **2.7.144** | 🟡 refactor | Admin/System | **Der Error-Report-Entry hängt näher an einem kleinen, expliziten Request-Vertrag**: Feldbegrenzung, Source-URL-Normalisierung und JSON-Strukturgrenzen liegen sichtbar im Wrapper statt nur implizit über nachgelagerte Service-Limits zu wirken. |

---

### v2.7.143 — 25. März 2026 · Audit-Batch 225, Gruppen-Entry bei Action-Gates und ID-Normalisierung nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.143** | 🔴 fix | Admin/Users | **`groups.php` begrenzt Mutationen jetzt wieder explizit über eine kleine Action-Allowlist und normalisiert Gruppen-IDs bereits im Wrapper**: Delete-Pfade übernehmen dadurch keine losen Aktionen oder rohen IDs mehr direkt in das Gruppen-Modul. |
| **2.7.143** | 🟠 perf | Admin/Users | **Der Gruppen-Entry verwirft unzulässige Delete-Anfragen früher und billiger**: ungültige Aktionswerte oder Gruppen-IDs scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.143** | 🟡 refactor | Admin/Users | **Der Gruppen-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Allowlist und ID-Normalisierung liegen sichtbar im Einstieg statt nur implizit über Handler-Map und rohe Casts zu wirken. |

---

### v2.7.142 — 25. März 2026 · Audit-Batch 224, DSGVO-Module bei Request-Typ-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.142** | 🔴 fix | Admin/Legal | **`PrivacyRequestsModule.php` und `DeletionRequestsModule.php` binden Mutationen jetzt explizit an den passenden Request-Typ**: Auskunfts- und Löschanträge werden damit nicht mehr allein per roher ID verarbeitet, wenn der Datensatz eigentlich zum anderen DSGVO-Pfad gehört. |
| **2.7.142** | 🟠 perf | Admin/Legal | **Die DSGVO-Module blocken bereichsfremde IDs früher und billiger**: unnötige Update-, Delete- oder Hook-Aufrufe entfallen, wenn ein Request nicht zum erwarteten Typ gehört. |
| **2.7.142** | 🟡 refactor | Admin/Legal | **Beide Module hängen näher an einem kleinen, expliziten Request-Vertrag**: `getRequestById()` kapselt den Typ-Guard sichtbar zentral statt dass einzelne Mutationspfade nur implizit oder gar nicht auf `type` achten. |

---

### v2.7.141 — 25. März 2026 · Audit-Batch 223, Cookie-Manager-Entry bei Request-Normalisierung nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.141** | 🔴 fix | Admin/Legal | **`cookie-manager.php` normalisiert POST-Aktionen, positive IDs, `service_slug` und Self-Hosted-Flags jetzt serverseitig bereits im Wrapper**: Delete- und Import-Pfade übernehmen dadurch keine losen oder unsauberen Request-Werte mehr direkt in Kategorie-, Service- oder kuratierte Dienst-Mutationen. |
| **2.7.141** | 🟠 perf | Admin/Legal | **Der Cookie-Manager verwirft unzulässige Delete- und Import-Anfragen früher und billiger**: ungültige IDs oder unbekannte Service-Slugs scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.141** | 🟡 refactor | Admin/Legal | **Der Cookie-Manager-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Action-, ID- und Slug-Normalisierung liegen sichtbar im Einstieg statt nur implizit über Modulfallbacks oder rohe Request-Casts zu wirken. |

---

### v2.7.140 — 25. März 2026 · Audit-Batch 222, Backup-Entry bei Action-Gates und Backup-Namen nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.140** | 🔴 fix | Admin/System | **`backups.php` begrenzt Backup-Mutationen jetzt wieder explizit über eine kleine Allowlist und normalisiert `backup_name` serverseitig vor Delete-Dispatches**: lose Aktionen oder unsaubere Backup-Namen laufen dadurch nicht mehr direkt aus dem Request in den Löschpfad. |
| **2.7.140** | 🟠 perf | Admin/System | **Der Backup-Entry verwirft unzulässige Delete-Anfragen früher und billiger**: ungültige Aktionswerte oder Backup-Namen scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.140** | 🟡 refactor | Admin/System | **Der Backup-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Allowlist und Namensnormalisierung liegen sichtbar im Einstieg statt nur implizit über Handler-Map und Modulfallbacks zu wirken. |

---

### v2.7.139 — 25. März 2026 · Audit-Batch 221, DSGVO-Requests-Entry bei Scope-/Action-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.139** | 🔴 fix | Admin/Legal | **`data-requests.php` begrenzt DSGVO-Mutationen jetzt wieder explizit über scope-gebundene Allowlists und normalisiert `scope`, `action`, `id` sowie Ablehnungsgründe serverseitig**: lose oder bereichsfremde Aktionen laufen dadurch nicht mehr direkt aus dem Request in Auskunfts- oder Löschpfade. |
| **2.7.139** | 🟠 perf | Admin/Legal | **Der Sammel-Entry verwirft unzulässige DSGVO-Mutationen früher und billiger**: ungültige Scope-/Action-Kombinationen und auffällige Request-Werte scheitern vor unnötigen Modulaufrufen im PRG-Flow. |
| **2.7.139** | 🟡 refactor | Admin/Legal | **Der DSGVO-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Allowlist, Scope-/Action-Normalisierung und kleine Request-Helfer liegen sichtbar im Einstieg statt nur implizit über `match`-Fallbacks zu wirken. |

---

### v2.7.138 — 25. März 2026 · Audit-Batch 220, Theme-Marketplace auf zentralen staging-basierten Installationspfad gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.138** | 🔴 fix | Admin/Themes | **`ThemeMarketplaceModule.php` delegiert Marketplace-Theme-Installationen jetzt an den zentralen `UpdateService`**: Theme-Pakete laufen damit nicht mehr über eine separate ZIP-/Download-/Extract-Pipeline direkt im Modul, sondern nutzen denselben staging-basierten Zielpfad-, Integritäts- und Rollback-Vertrag wie reguläre Theme-Updates. |
| **2.7.138** | 🟠 perf | Admin/Themes | **Doppelte Download-, Entpack- und Cleanup-Logik im Theme-Marketplace-Installationspfad entfällt**: der Modulpfad bleibt im Installationsflow kompakter, weil er keinen zweiten Archiv-Lifecycle neben dem Update-Service mehr pflegt. |
| **2.7.138** | 🟡 refactor | Admin/Themes | **Der Theme-Marketplace hängt jetzt näher am gemeinsamen Update-Lifecycle für Zielpfade, Logging und Rollback**: das Modul konzentriert sich wieder stärker auf Katalog- und Marketplace-Logik statt eine eigene zweite Installationspipeline zu unterhalten. |

---

### v2.7.137 — 25. März 2026 · Audit-Batch 219, Documentation-Entry bei Action-Allowlist nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.137** | 🔴 fix | Admin/System | **`documentation.php` prüft Sync-Aktionen jetzt wieder explizit über eine kleine Allowlist und normalisiert den `action`-Wert serverseitig vor dem Dispatch**: lose oder nicht erlaubte Aktionen laufen damit nicht mehr nur implizit deshalb in den PRG-/Fehlerpfad, weil ein Handler nachgeschlagen wird. |
| **2.7.137** | 🟠 perf | Admin/System | **Der Doku-Entry hält seinen Dispatch-Pfad kompakter und vorhersehbarer**: unzulässige Sync-Aktionen werden vor unnötigen Modulaufrufen oder Ergebnisobjekten früh blockiert. |
| **2.7.137** | 🟡 refactor | Admin/System | **Der Documentation-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Allowlist und Action-Normalisierung liegen sichtbar im Einstieg statt nur implizit über die Handler-Map zu wirken. |

---

### v2.7.136 — 25. März 2026 · Audit-Batch 218, Font-Manager-Entry bei Action-Allowlist und Request-Normalisierung nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.136** | 🔴 fix | Admin/Font Manager | **`font-manager.php` prüft POST-Aktionen jetzt explizit über eine kleine Allowlist und normalisiert `font_id` sowie Google-Font-Familien bereits im Wrapper**: lose oder unsaubere Request-Werte laufen dadurch nicht mehr bloß deshalb in Delete- oder Download-Pfade, weil ein Handler existiert. |
| **2.7.136** | 🟠 perf | Admin/Font Manager | **Der Entry verwirft ungültige Mutationen früher und billiger**: auffällige Action-, ID- oder Family-Werte scheitern vor unnötigen Modulaufrufen und bleiben im PRG-Flow kompakter. |
| **2.7.136** | 🟡 refactor | Admin/Font Manager | **Der Font-Manager-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Allowlist und kleine Wrapper-Normalisierer für ID- und Family-Eingaben liegen sichtbar im Einstieg statt nur implizit über Handler und Modul-Fallbacks zu wirken. |

---

### v2.7.135 — 25. März 2026 · Audit-Batch 217, Mail-Settings-Entry bei tab-gebundenem Action-Dispatch nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.135** | 🔴 fix | Admin/System | **`mail-settings.php` bindet POST-Aktionen jetzt explizit an erlaubte Tabs und normalisiert den `action`-Wert serverseitig**: lose oder bereichsfremde Aktionen laufen dadurch nicht mehr bloß deshalb durch den Wrapper, weil irgendwo ein Handler existiert. |
| **2.7.135** | 🟠 perf | Admin/System | **Redirect- und Dispatch-Pfade bleiben kompakter und vorhersehbarer**: der Entry springt nach Mutationen immer in den kanonischen Bereich zurück und vermeidet unnötige Modulaufrufe für tab-fremde Aktionen. |
| **2.7.135** | 🟡 refactor | Admin/System | **Der Mail-Settings-Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Action-Allowlist, Bereichsbindung und kanonischer PRG-Rücksprung liegen sichtbar im Einstieg statt nur implizit über die Handler-Map zu wirken. |

---

### v2.7.134 — 25. März 2026 · Audit-Batch 216, Legal-Sites-Entry bei Action-Gates und Template-Typen nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.134** | 🔴 fix | Admin/Legal Sites | **`legal-sites.php` begrenzt POST-Aktionen jetzt wieder explizit über eine kleine Allowlist und normalisiert `template_type` serverseitig vor dem Modulaufruf**: ungültige Aktionen oder Rechtstext-Typen laufen dadurch nicht mehr lose aus dem Request in die Mutationspfade. |
| **2.7.134** | 🟠 perf | Admin/Legal Sites | **Der Wrapper hält seinen Dispatch-Pfad kompakter und vorhersehbarer**: unnötige Modulaufrufe für unzulässige Aktionen oder Template-Typen werden früher blockiert. |
| **2.7.134** | 🟡 refactor | Admin/Legal Sites | **Der Entry hängt näher am gemeinsamen Admin-Wrapper-Muster**: Request-Gates und Typ-Normalisierung liegen sichtbar im Einstieg statt nur implizit über Handler-Map und Modul-Fallbacks zu wirken. |

---

### v2.7.133 — 25. März 2026 · Audit-Batch 215, Font-Download-Pfad bei Remote-Dateinamen gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.133** | 🔴 fix | Admin/Font Manager | **`FontManagerModule.php` akzeptiert beim Google-Font-Download jetzt nur noch eine kleine Anzahl erlaubter Remote-Fontdateien und normalisiert lokale Dateinamen aus Remote-URLs hart**: auffällige oder überlange Dateinamen laufen damit nicht mehr ungefiltert ins lokale Fonts-Verzeichnis. |
| **2.7.133** | 🟠 perf | Admin/Font Manager | **Ausufernde Remote-CSS-Pakete werden früher abgewiesen**: der Download-Pfad stoppt kontrolliert, wenn ein Remote-Font-Stylesheet ungewöhnlich viele Dateien referenziert. |
| **2.7.133** | 🟡 refactor | Admin/Font Manager | **Die lokale Dateinamenerzeugung hängt jetzt an einem kleinen, expliziten Helfer**: Remote-URL-Bestandteile werden nicht mehr lose inline zu lokalen Dateinamen zusammengesetzt, sondern über einen engeren Namensvertrag gebaut. |

---

### v2.7.132 — 25. März 2026 · Audit-Batch 214, Font-Delete-Pfad auf verwalteten Root begrenzt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.132** | 🔴 fix | Admin/Font Manager | **`FontManagerModule.php` löst lokale Font- und CSS-Dateien vor dem Löschen jetzt nur noch innerhalb von `uploads/fonts/` auf**: DB-basierte Pfadangaben können damit nicht mehr blind in Dateilöschungen außerhalb des verwalteten Fonts-Verzeichnisses durchschlagen. |
| **2.7.132** | 🟠 perf | Admin/Font Manager | **Der Delete-Pfad scheitert früher und kontrollierter bei ungültigen Pfaden**: problematische Dateiangaben werden vor unnötigen Dateisystemzugriffen abgewiesen und nur als Hinweis in die Rückmeldung übernommen. |
| **2.7.132** | 🟡 refactor | Admin/Font Manager | **Der Font-Lifecycle hängt näher an einem expliziten Root-Vertrag**: Pfadauflösung und Fonts-Verzeichnis werden in kleine Hilfsmethoden gebündelt, statt Löschpfade rohe DB-Werte direkt zu absoluten Dateisystempfaden zusammenzusetzen. |

---

### v2.7.131 — 25. März 2026 · Audit-Batch 213, Media-Upload-Wrapper bei Multi-File-Payloads gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.131** | 🔴 fix | Admin/Media | **`media.php` normalisiert Multi-Upload-Payloads jetzt robuster und verwirft unvollständige oder inkonsistente `$_FILES`-Strukturen früh**: Der Upload-Entry läuft damit nicht mehr blind durch vorausgesetzte Array-Formate, wenn ein Request kaputt oder manipuliert ankommt. |
| **2.7.131** | 🟠 perf | Admin/Media | **Upload-Fehlerschwälle bleiben kompakter**: aggregierte Fehlermeldungen werden begrenzt, damit Flash-Nachrichten bei vielen problematischen Dateien nicht unnötig anwachsen. |
| **2.7.131** | 🟡 refactor | Admin/Media | **Der Upload-Wrapper hängt näher an einem klaren Request-Vertrag**: Batch-Normalisierung und Fehlerformatierung sind sichtbar in kleine Helfer ausgelagert, statt die `$_FILES`-Struktur implizit direkt im Upload-Loop vorauszusetzen. |

---

### v2.7.130 — 25. März 2026 · Audit-Batch 212, Plugins-/Themes-Entrys bei Action-Gates nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.130** | 🔴 fix | Admin/Plugins & Admin/Themes | **`plugins.php` und `themes.php` begrenzen POST-Mutationen jetzt wieder explizit über kleine Action-Allowlists**: Ungültige Aktionen laufen dadurch nicht mehr nur implizit in den `match`-Fallback, sondern scheitern kontrolliert schon im Entry-Wrapper. |
| **2.7.130** | 🟠 perf | Admin/Plugins & Admin/Themes | **Die Wrapper halten ihren Dispatch-Pfad kompakter und vorhersehbarer**: der POST-Block vermeidet unnötige Modulaufrufe für unzulässige Aktionen und bleibt näher am strengeren Update-/Marketplace-Muster. |
| **2.7.130** | 🟡 refactor | Admin/Plugins | **`plugins.php` normalisiert Plugin-Slugs jetzt sichtbar vor dem Modulaufruf**: der Entry hängt damit konsistenter am vorhandenen Plugin-Slug-Vertrag, statt rohe Request-Werte direkt in Aktivierungs-, Deaktivierungs- und Löschpfade zu reichen. |

---

### v2.7.129 — 25. März 2026 · Audit-Batch 211, Theme-Marketplace bei Manifest- und Archiv-Gates nachgeschärft

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.129** | 🔴 fix | Admin/Themes | **`ThemeMarketplaceModule.php` validiert lokale Manifestpfade jetzt traversal-sicher innerhalb des Katalog-Roots**: Theme-Manifeste aus lokalen Index-Dateien dürfen damit nicht mehr über rohe Relative-Pfade aus dem erlaubten Katalogbereich ausbrechen oder übergroße JSON-Dateien ungebremst in den Marketplace-Pfad ziehen. |
| **2.7.129** | 🟠 perf | Admin/Themes | **Auffällige Theme-Archive werden früher und billiger verworfen**: die ZIP-Prüfung blockt Pakete mit zu vielen Einträgen, Kontrollzeichen oder übergroßer unkomprimierter Gesamtmenge schon vor dem Entpacken. |
| **2.7.129** | 🟡 refactor | Admin/Themes | **Der Theme-Marketplace hängt näher am strengeren Registry- und Paketvertrag des Plugin-Pendants**: Manifest-Normalisierung und Archiv-Gates sind sichtbarer zentralisiert, statt den lokalen Katalog- und ZIP-Pfad lockerer nebeneinander laufen zu lassen. |

---

### v2.7.128 — 25. März 2026 · Audit-Batch 210, Plugin-Marketplace auf zentralen staging-basierten Installationspfad gezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.128** | 🔴 fix | Admin/Plugins | **`PluginMarketplaceModule.php` delegiert Marketplace-Installationen jetzt an den zentralen `UpdateService`**: Plugin-Pakete laufen damit nicht mehr über einen separaten ZIP-/Extract-Pfad direkt ins Plugins-Verzeichnis, sondern nutzen denselben staging-basierten Installations- und Zielpfadvertrag wie reguläre Update-Installationen. |
| **2.7.128** | 🟠 perf | Admin/Plugins | **Doppelte Download-, ZIP- und Cleanup-Logik im Marketplace-Modul entfällt**: der Plugin-Marketplace baut keinen zweiten Entpackpfad mehr parallel zum Update-Service auf und bleibt im Installationsfluss dadurch kompakter wartbar. |
| **2.7.128** | 🟡 refactor | Admin/Plugins | **Marketplace-Installationen hängen jetzt näher am gemeinsamen Lifecycle für Integrität, Rollback und atomaren Verzeichnis-Swap**: der Modulpfad konzentriert sich wieder stärker auf Registry- und Marketplace-Logik statt auf eine eigene zweite Archiv-Installationspipeline. |

---

### v2.7.127 — 25. März 2026 · Audit-Batch 209, Marketplace-Entrys bei Action-Gates und Slug-Normalisierung nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.127** | 🔴 fix | Admin/Themes & Admin/Plugins | **`theme-marketplace.php` und `plugin-marketplace.php` validieren POST-Aktionen jetzt wieder über explizite Allowlists, und der Plugin-Marketplace normalisiert den angeforderten Slug vor dem Modulaufruf**: Die Entrys lassen damit keine losen Mutationen oder unsauberen Slug-Werte direkt in den Installationspfad laufen. |
| **2.7.127** | 🟠 perf | Admin/Themes & Admin/Plugins | **Die Marketplace-Wrapper halten ihren Request-Flow kompakter und vorhersehbarer**: Aktionsfehler werden früh im Entry abgefangen, statt erst tiefer im Modulpfad auf denselben ungültigen Zustand zu reagieren. |
| **2.7.127** | 🟡 refactor | Admin/Themes & Admin/Plugins | **Beide Marketplace-Entrys bleiben näher am gemeinsamen Admin-Wrapper-Muster**: Allowlist-, Flash- und Dispatch-Verhalten liegen wieder sichtbar im Einstieg statt implizit nur über `match`-Fallbacks zu leben. |

---

### v2.7.126 — 25. März 2026 · Audit-Batch 208, Update-Prüfung ohne direkten Doppelabruf nach Redirect

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.126** | 🔴 fix | Admin/System | **`UpdatesModule.php` hält Core-, Plugin- und Theme-Prüfstände jetzt als kleinen Snapshot zusammen, und `updates.php` übernimmt manuelle Check-Resultate per Session-Snapshot in den Folge-Request**: Eine explizite Update-Prüfung landet damit nach dem PRG-Redirect nicht sofort wieder in denselben Remote-Checks, obwohl der Stand gerade erst ermittelt wurde. |
| **2.7.126** | 🟠 perf | Admin/System | **Doppelte Update-Checks im direkten Prüfen-→-Redirect-→-Rendern-Pfad entfallen**: Core-, Plugin- und Theme-Checks werden pro Modulinstanz wiederverwendet, statt im selben Bedienablauf mehrfach identisch aufgebaut zu werden. |
| **2.7.126** | 🟡 refactor | Admin/System | **Der Update-Entry bleibt näher an einem kleinen Snapshot-Vertrag**: `checkAllUpdates()`, Snapshot-Hydration und PRG-Weitergabe bündeln den Prüffluss sichtbarer, statt Callback- und Folge-Request-Logik lose nebeneinander stehen zu lassen. |

---

### v2.7.125 — 25. März 2026 · Audit-Batch 207, Theme-Verwaltung bei Katalog- und Delete-Pfad nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.125** | 🔴 fix | Admin/Themes | **`ThemeManager.php` cached verfügbare Themes jetzt zentral inklusive `theme.json`- und Screenshot-Metadaten, während `ThemesModule.php` Delete-Aktionen wieder an den gemeinsamen Theme-Manager delegiert**: Theme-Listen und Löschpfade greifen damit näher auf denselben Lifecycle-Vertrag zu, statt Metadaten und Delete-Logik parallel zu duplizieren. |
| **2.7.125** | 🟠 perf | Admin/Themes | **Theme-Listen vermeiden doppelte Dateisystem-Anreicherung im Modulpfad**: verfügbare Themes werden pro Theme-Manager-Instanz einmal aufgebaut und nicht mehr zusätzlich im `ThemesModule` erneut über `theme.json`- und Screenshot-Reads erweitert. |
| **2.7.125** | 🟡 refactor | Admin/Themes | **Theme-Aktivierung und -Löschung halten den zentralen Runtime-Zustand sauberer konsistent**: `switchTheme()` und `deleteTheme()` invalidieren den verfügbaren Theme-Bestand jetzt sichtbar zentral, statt den Cache- und Lifecycle-Zustand implizit auseinanderlaufen zu lassen. |

---

### v2.7.124 — 25. März 2026 · Audit-Batch 206, Plugins-Modul bei Aktiv-Status und Delete-Vertrag nachgezogen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.124** | 🔴 fix | Admin/Plugins | **`PluginsModule.php` cached aktive Plugin-Slugs jetzt pro Modulinstanz und delegiert Löschungen wieder an den zentralen `PluginManager`**: Plugin-Listen und Mutationspfade greifen damit auf denselben Aktivstatus-Index zu, während Delete-Aktionen Uninstall- und Delete-Hooks nicht mehr am Manager vorbei umgehen. |
| **2.7.124** | 🟠 perf | Admin/Plugins | **Der Plugin-Listenpfad vermeidet wiederholte `active_plugins`-Lookups im selben Modul-Lebenszyklus**: `getData()` und Aktivstatus-Prüfungen lesen aktive Plugins nicht mehr pro Plugin erneut aus Settings oder Manager-Zustand zusammen. |
| **2.7.124** | 🟡 refactor | Admin/Plugins | **Fallback-Persistenz und Aktivstatus-Auflösung bleiben näher an einem kleinen Modulvertrag**: `getActivePluginsLookup()` und `persistActivePlugins()` bündeln Lookup- und Fallback-Speicherpfade sichtbar, statt Aktivierungs-/Deaktivierungslogik mehrfach lose um dieselbe Settings-Struktur herumzubauen. |

---

### v2.7.123 — 25. März 2026 · Audit-Batch 205, Hub-Sites-Zusatzdomains ohne wiederholten Vollscan

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.123** | 🔴 fix | Admin/Hub | **`HubSitesModule.php` cached normalisierte Zusatzdomain-Zuordnungen jetzt pro Modulinstanz**: Domain-Prüfungen für Hub-Sites greifen damit auf einen gemeinsamen Zuordnungsindex zu, statt pro Zusatzdomain erneut alle Hub-Sites samt `settings_json` zu durchlaufen. |
| **2.7.123** | 🟠 perf | Admin/Hub | **Mehrere Zusatzdomain-Checks vermeiden wiederholte Tabellen-Vollscans**: Der Save-Pfad kann mehrere Domains gegen denselben Cache prüfen, ohne für jede einzelne Domain die komplette Hub-Site-Liste erneut zu decodieren. |
| **2.7.123** | 🟡 refactor | Admin/Hub | **Der Domain-Validierungspfad bleibt näher an einem kleinen Modulvertrag**: `getHubDomainAssignments()` bündelt Laden und Normalisieren der Domain-Zuordnung sichtbar zentral, während Save-/Delete-/Duplicate-Pfade den Cache nach Mutationen gezielt invalidieren. |

---

### v2.7.122 — 25. März 2026 · Audit-Batch 204, Media-Modul bei Kategorien und Settings-Schlüsseln bereinigt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.122** | 🔴 fix | Admin/Media | **`MediaModule.php` cached Kategorien jetzt pro Modulinstanz und verwendet beim Speichern wieder die kanonischen Service-Keys**: Kategorieprüfungen und Listenpfade greifen damit auf denselben kleinen Kategorienbestand zu, während Dateinamen- und Thumbnail-Settings nicht mehr als Alias-Mix im Save-Pfad landen. |
| **2.7.122** | 🟠 perf | Admin/Media | **Wiederholte Kategorien-Lookups entfallen im selben Modul-Lebenszyklus**: Bibliothek, Kategorien-Tab und Kategorievalidierung müssen den Medienservice nicht mehr mehrfach identisch nach Kategorien fragen. |
| **2.7.122** | 🟡 refactor | Admin/Media | **Die Settings-Persistenz bleibt näher am eigentlichen Medien-Service-Vertrag**: `sanitize_filenames`, `unique_filenames`, `lowercase_filenames` sowie `thumb_*`-Werte werden wieder explizit auf ihre kanonischen Schlüssel abgebildet, statt auf tolerierte Legacy-Aliasse zu vertrauen. |

---

### v2.7.121 — 25. März 2026 · Audit-Batch 203, Plugin-Marketplace-Registry entschlackt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.121** | 🔴 fix | Admin/Plugins | **`PluginMarketplaceModule.php` cached die normalisierte Registry jetzt pro Modulinstanz und nutzt einen wiederverwendeten HTTP-Client**: Registry-Ladevorgänge bleiben konsistenter, ohne Remote- und lokale Katalogdaten bei erneutem Zugriff unnötig neu aufzubauen. |
| **2.7.121** | 🟠 perf | Admin/Plugins | **Registry- und Manifest-Zugriffe vermeiden wiederholte Initialisierungskosten**: Das Modul lädt Marketplace-Daten über denselben HTTP-Client und denselben Cache-Pfad statt mehrere Neuladungen innerhalb desselben Lebenszyklus anzustoßen. |
| **2.7.121** | 🟡 refactor | Admin/Plugins | **Die Registry-Logik bleibt näher an einem kleinen Modulvertrag**: `loadRegistry()` übernimmt das Caching sichtbar zentral, während Remote-JSON-Reads den vorbereiteten Client wiederverwenden statt ihren Transportzugang mehrfach selbst aufzubauen. |

---

### v2.7.120 — 25. März 2026 · Audit-Batch 202, Theme-Marketplace-Katalogpfad entschlackt

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.120** | 🔴 fix | Admin/Themes | **`ThemeMarketplaceModule.php` sucht Installationsziele jetzt direkt im normalisierten Katalog statt über den kompletten angereicherten `getData()`-Pfad**: Die Theme-Suche für Installationen bleibt damit konsistenter und hängt nicht mehr an zusätzlichem Status- und Installations-Anreicherungsaufwand. |
| **2.7.120** | 🟠 perf | Admin/Themes | **Der Marketplace-Katalog wird pro Modulinstanz zwischengespeichert**: Remote-/lokale Katalogdaten werden nicht mehrfach neu aufgebaut, wenn Listen- und Installationspfad nacheinander auf denselben Theme-Bestand zugreifen. |
| **2.7.120** | 🟡 refactor | Admin/Themes | **Die Kataloglogik bleibt näher an einem kleinen Modulvertrag**: `getCatalog()` übernimmt das Caching sichtbar zentral, während `findCatalogTheme()` nur noch den schlanken Katalog durchsucht statt den kompletten View-Datenaufbau anzustoßen. |

---

### v2.7.119 — 25. März 2026 · Audit-Batch 201, Font-Manager-Settings ohne N+1-Existenzchecks

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.119** | 🔴 fix | Admin/Font Manager | **`FontManagerModule.php` lädt vorhandene Setting-Namen jetzt gesammelt vor und persistiert Font-Optionen über einen gemeinsamen Helfer**: Der Save-Pfad für Heading-, Body-, Größen-, Zeilenhöhen- und Local-Font-Settings läuft konsistenter, ohne pro Option erneut die Existenz in `settings` abzufragen. |
| **2.7.119** | 🟠 perf | Admin/Font Manager | **Der Font-Settings-Save-Pfad vermeidet wiederholte Datenbank-Roundtrips**: Statt pro Schlüssel `COUNT(*)`-Existenzchecks plus Mutation auszuführen, wird der bekannte Settings-Bestand einmal vorgeladen und anschließend gezielt `UPDATE` oder `INSERT` genutzt. |
| **2.7.119** | 🟡 refactor | Admin/Font Manager | **Die Persistenz bleibt näher an einem kleinen, klaren Modulvertrag**: `loadExistingSettings()` und `persistSetting()` bündeln Vorladen und Schreiblogik sichtbar, sodass `saveSettings()` stärker beim eigentlichen Font-Input bleibt. |

---

### v2.7.118 — 25. März 2026 · Audit-Batch 200, Cookie-Manager-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.118** | 🔴 fix | Admin/Legal | **`cookie-manager.php` bündelt Ziel-URL, Redirect-, Allowlist- und Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler sowie Cookie-Manager-Aktionsrückgaben laufen konsistenter, ohne Redirect- und Action-Details lose im Einstieg zu verteilen. |
| **2.7.118** | 🟠 perf | Admin/Legal | **Action- und Fehlerpfade bleiben kompakter wartbar**: Ziel-URL, Allowlist-Prüfung und Dispatch-Auflösung müssen nicht mehr in mehreren Request-Zweigen parallel gepflegt werden. |
| **2.7.118** | 🟡 refactor | Admin/Legal | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Ziel-URL-, Allowlist- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Cookie-Verwaltung im Einstieg fokussiert bleibt. |

---

### v2.7.117 — 25. März 2026 · Audit-Batch 199, Legal-Sites-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.117** | 🔴 fix | Admin/Legal Sites | **`legal-sites.php` bündelt Ziel-URL, Redirect-, Profil-Session-State und Template-Aufbau jetzt klarer über kleine Helfer**: Token-Fehler sowie Profil-, Template- und Mutationsrückgaben laufen konsistenter, ohne Session- und Redirect-Details lose im Einstieg zu verteilen. |
| **2.7.117** | 🟠 perf | Admin/Legal Sites | **Profil- und Template-Pfade bleiben kompakter wartbar**: Redirect-Ziel, Profil-State und Template-Aufbau müssen nicht mehr in mehreren Request-Zweigen parallel gepflegt werden. |
| **2.7.117** | 🟡 refactor | Admin/Legal Sites | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Ziel-URL-, Profil-State- und Template-Details sind in kleine Helfer ausgelagert, während die Legal-Sites-Verwaltung im Einstieg fokussiert bleibt. |

---

### v2.7.116 — 25. März 2026 · Audit-Batch 198, Error-Report-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.116** | 🔴 fix | Admin/System | **`error-report.php` bündelt Default-/Redirect-Ziel, JSON-Payload-Normalisierung und Report-Dispatch jetzt klarer über kleine Helfer**: Token-Fehler sowie Fehlerreport-Rückgaben laufen konsistenter, ohne Redirect- und Payload-Details lose im Einstieg zu verteilen. |
| **2.7.116** | 🟠 perf | Admin/System | **Payload- und Redirect-Pfade bleiben kompakter wartbar**: Redirect-Auflösung, JSON-Dekodierung und Report-Payload müssen nicht mehr in mehreren Request-Zweigen parallel gepflegt werden. |
| **2.7.116** | 🟡 refactor | Admin/System | **Der Entry bleibt näher am eigentlichen Service-Aufruf**: Default-URL-, Payload- und Dispatch-Details sind in kleine Helfer ausgelagert, während der Error-Report-Flow im Einstieg fokussiert bleibt. |

---

### v2.7.115 — 25. März 2026 · Audit-Batch 197, Theme-Explorer-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.115** | 🔴 fix | Admin/Themes | **`theme-explorer.php` bündelt Ziel-URL, Action-Allowlist, Redirect-, Flash-/Pull-Alert-Pfade und Save-Dispatch jetzt klarer über kleine Helfer**: Token-Fehler sowie Datei-Save-Rückgaben laufen konsistenter, ohne Session- und Redirect-Details lose im Einstieg zu verteilen. |
| **2.7.115** | 🟠 perf | Admin/Themes | **Datei- und Fehlerpfade bleiben kompakter wartbar**: Redirect-Ziel, Action-Allowlist und Save-Rückmeldungen müssen nicht mehr in mehreren POST-Zweigen parallel gepflegt werden. |
| **2.7.115** | 🟡 refactor | Admin/Themes | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Ziel-URL-, Pull-Alert- und Dispatch-Details sind in kleine Helfer ausgelagert, während der Theme-Explorer im Einstieg fokussiert bleibt. |

---

### v2.7.114 — 25. März 2026 · Audit-Batch 196, Hub-Sites-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.114** | 🔴 fix | Admin/Hub | **`hub-sites.php` bündelt Action-Allowlist, View-Normalisierung, Redirect-Abgänge, Flash-/Pull-Alert-Pfade und Action-Dispatch jetzt klarer über kleine Helfer**: Token-Fehler sowie Listen-, Edit-, Template- und Mutationspfade laufen konsistenter, ohne Redirect- und Session-Details lose im Einstieg zu verteilen. |
| **2.7.114** | 🟠 perf | Admin/Hub | **View- und Render-Pfade bleiben kompakter wartbar**: View-Auflösung, Asset-Konfiguration und Action-spezifische Redirect-Ziele müssen nicht mehr in mehreren `if`-/`switch`-Blöcken parallel gepflegt werden. |
| **2.7.114** | 🟡 refactor | Admin/Hub | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Allowlist-, Pull-Alert-, Dispatch- und Render-Konfiguration sind in kleine Helfer ausgelagert, während die Hub-Sites-Verwaltung im Einstieg fokussiert bleibt. |

---

### v2.7.113 — 25. März 2026 · Audit-Batch 195, Media-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.113** | 🔴 fix | Admin/Media | **`media.php` bündelt Action-Allowlist, Pfadauflösung, Redirect-Parameter, Upload-Rückmeldungen und Action-Dispatch jetzt klarer über kleine Helfer**: Token-Fehler, Member-Bestätigungs-nahe Redirects sowie Library-, Kategorie- und Settings-Aktionen laufen konsistenter, ohne Redirect- und Flash-Details lose im Einstieg zu verteilen. |
| **2.7.113** | 🟠 perf | Admin/Media | **Upload- und Redirect-Pfade bleiben kompakter wartbar**: Redirect-Query-Aufbau, Action-spezifische Zielpfade und Upload-Rückmeldungen müssen nicht mehr in mehreren POST-Zweigen parallel gepflegt werden. |
| **2.7.113** | 🟡 refactor | Admin/Media | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Allowlist-, Pull-Alert-, Pfad- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Medienverwaltung im Einstieg fokussiert bleibt. |

---

### v2.7.112 — 25. März 2026 · Audit-Batch 194, Theme-Editor-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.112** | 🔴 fix | Admin/Themes | **`theme-editor.php` bündelt Layout- und Fallback-Pfade jetzt klarer über kleine Helfer**: Customizer-Einbettung und Fallback-Ansicht laufen konsistenter über denselben kleinen Entry-Pfad, ohne Header-/Sidebar-/Footer-Aufbau lose in zwei Zweigen zu verteilen. |
| **2.7.112** | 🟠 perf | Admin/Themes | **Customizer- und Fallback-Pfade bleiben kompakter wartbar**: Layout-Defaults, Fallback-Links und Render-Aufbau müssen nicht mehr in mehreren Zweigen parallel gepflegt werden. |
| **2.7.112** | 🟡 refactor | Admin/Themes | **Der Entry bleibt näher an seinem eigentlichen Routing-Zweck**: Layout- und Fallback-Details sind in kleine Helfer ausgelagert, während die Theme-Editor-Entscheidung fokussiert bleibt. |

---

### v2.7.111 — 25. März 2026 · Audit-Batch 193, Updates-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.111** | 🔴 fix | Admin/System | **`updates.php` bündelt Redirect-, Flash-, Pull-Alert-, Allowlist- und Action-Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler, Update-Prüfung sowie Core- und Plugin-Installationsrückgaben laufen konsistenter, ohne Session- und Redirect-Details lose im Einstieg zu verteilen. |
| **2.7.111** | 🟠 perf | Admin/System | **Prüf- und Fehlerpfade bleiben kompakter wartbar**: Action-Allowlist, Plugin-Slug-Normalisierung, Flash-Aufbau und Redirect-Abgang müssen nicht mehr in mehreren Verzweigungen parallel gepflegt werden. |
| **2.7.111** | 🟡 refactor | Admin/System | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert-, Pull-Alert- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Update-Verwaltungslogik fokussiert bleibt. |

---

### v2.7.110 — 25. März 2026 · Audit-Batch 192, Themes-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.110** | 🔴 fix | Admin/Themes | **`themes.php` bündelt Redirect-, Flash-, Pull-Alert- und Action-Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler, Theme-Slug-Normalisierung sowie Aktivierungs- und Löschrückgaben laufen konsistenter, ohne Session- und Redirect-Details lose im Einstieg zu verteilen. |
| **2.7.110** | 🟠 perf | Admin/Themes | **Aktivierungs- und Fehlerpfade bleiben kompakter wartbar**: Slug-Normalisierung, Flash-Aufbau und Redirect-Abgang müssen nicht mehr in mehreren Verzweigungen parallel gepflegt werden. |
| **2.7.110** | 🟡 refactor | Admin/Themes | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert-, Pull-Alert- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Theme-Verwaltungslogik fokussiert bleibt. |

---

### v2.7.109 — 25. März 2026 · Audit-Batch 191, Plugins-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.109** | 🔴 fix | Admin/Plugins | **`plugins.php` bündelt Redirect-, Flash-, Pull-Alert- und Action-Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler sowie Aktivierungs-, Deaktivierungs- und Löschrückgaben laufen konsistenter, ohne Session- und Redirect-Details lose im Einstieg zu verteilen. |
| **2.7.109** | 🟠 perf | Admin/Plugins | **Aktivierungs- und Fehlerpfade bleiben kompakter wartbar**: Action-Dispatch, Flash-Aufbau und Redirect-Abgang müssen nicht mehr mehrfach direkt im POST-Block gepflegt werden. |
| **2.7.109** | 🟡 refactor | Admin/Plugins | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert-, Pull-Alert- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Plugin-Verwaltungslogik fokussiert bleibt. |

---

### v2.7.108 — 25. März 2026 · Audit-Batch 190, Theme-Marketplace-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.108** | 🔴 fix | Admin/Themes | **`theme-marketplace.php` bündelt Redirect-, Flash-, Pull-Alert- und Action-Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler und Installationsrückgaben laufen konsistenter, ohne Session- und Redirect-Details lose im Einstieg zu verteilen. |
| **2.7.108** | 🟠 perf | Admin/Themes | **Installations- und Fehlerpfade bleiben kompakter wartbar**: Slug-Normalisierung, Flash-Aufbau und Redirect-Abgang müssen nicht mehr in mehreren Verzweigungen parallel gepflegt werden. |
| **2.7.108** | 🟡 refactor | Admin/Themes | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert-, Pull-Alert- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Marketplace-Seitenlogik fokussiert bleibt. |

---

### v2.7.107 — 25. März 2026 · Audit-Batch 189, Plugin-Marketplace-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.107** | 🔴 fix | Admin/Plugins | **`plugin-marketplace.php` bündelt Redirect-, Flash-, Pull-Alert- und Action-Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler und Installationsrückgaben laufen konsistenter, ohne Session- und Redirect-Details lose im Einstieg zu verteilen. |
| **2.7.107** | 🟠 perf | Admin/Plugins | **Installations- und Fehlerpfade bleiben kompakter wartbar**: Action-Dispatch, Flash-Aufbau und Redirect-Abgang müssen nicht mehr mehrfach direkt im POST-Block gepflegt werden. |
| **2.7.107** | 🟡 refactor | Admin/Plugins | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert-, Pull-Alert- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Marketplace-Seitenlogik fokussiert bleibt. |

---

### v2.7.106 — 25. März 2026 · Audit-Batch 188, Landing-Page-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.106** | 🔴 fix | Admin/Landing Page | **`landing-page.php` bündelt Redirect-, Flash-, Pull-Alert- und Action-Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler und Aktionsrückgaben laufen konsistenter, ohne Handler- und Tab-Details lose im Einstieg zu verteilen. |
| **2.7.106** | 🟠 perf | Admin/Landing Page | **Dispatch- und Fehlerpfade bleiben kompakter wartbar**: die Handler-Map und der tabbezogene Redirect-Abgang müssen nicht mehr in mehreren Verzweigungen parallel gepflegt werden. |
| **2.7.106** | 🟡 refactor | Admin/Landing Page | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert-, Pull-Alert-, Tab- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik fokussiert bleibt. |

---

### v2.7.105 — 25. März 2026 · Audit-Batch 187, Firewall-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.105** | 🔴 fix | Admin/Firewall | **`firewall.php` bündelt Redirect-, Flash-, Pull-Alert- und Action-Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler und Aktionsrückgaben laufen konsistenter, ohne Session- und Dispatch-Details lose im Einstieg zu verteilen. |
| **2.7.105** | 🟠 perf | Admin/Firewall | **Dispatch- und Fehlerpfade bleiben kompakter wartbar**: Flash- und Pull-Alert-Pfade müssen nicht mehr mehrfach im POST-Block parallel gepflegt werden. |
| **2.7.105** | 🟡 refactor | Admin/Firewall | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert-, Pull-Alert- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik fokussiert bleibt. |

---

### v2.7.104 — 25. März 2026 · Audit-Batch 186, Font-Manager-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.104** | 🔴 fix | Admin/Font Manager | **`font-manager.php` bündelt Redirect-, Flash-, Pull-Alert- und Action-Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler und Aktionsrückgaben laufen konsistenter, ohne die Handler-Kette lose im Einstieg zu verteilen. |
| **2.7.104** | 🟠 perf | Admin/Font Manager | **Dispatch- und Fehlerpfade bleiben kompakter wartbar**: die Handler-Map und der Redirect-Abgang müssen nicht mehr in mehreren `if`-/`elseif`-Zweigen parallel gepflegt werden. |
| **2.7.104** | 🟡 refactor | Admin/Font Manager | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert-, Pull-Alert- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik fokussiert bleibt. |

---

### v2.7.103 — 25. März 2026 · Audit-Batch 185, Backups-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.103** | 🔴 fix | Admin/System | **`backups.php` bündelt Redirect-, Flash-, Pull-Alert- und Action-Dispatch-Pfade jetzt klarer über kleine Helfer**: Token-Fehler und Aktionsrückgaben laufen konsistenter, ohne Handler- und Alert-Details lose im Einstieg zu verteilen. |
| **2.7.103** | 🟠 perf | Admin/System | **Dispatch- und Fehlerpfade bleiben kompakter wartbar**: die Handler-Map und der Redirect-Abgang müssen nicht mehr mehrfach im POST-Block parallel gepflegt werden. |
| **2.7.103** | 🟡 refactor | Admin/System | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert-, Pull-Alert- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik fokussiert bleibt. |

---

### v2.7.102 — 25. März 2026 · Audit-Batch 184, Cookie-Manager-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.102** | 🔴 fix | Admin/Cookie Manager | **`cookie-manager.php` bündelt Action-Allowlist und Session-Alert-Pfade jetzt klarer über kleine Helfer**: Token-Fehler und Aktionsrückgaben laufen konsistenter, ohne Allowlist- und Alert-Details lose im Einstieg zu verteilen. |
| **2.7.102** | 🟠 perf | Admin/Cookie Manager | **Alert- und Fehlerpfade bleiben kompakter wartbar**: die Allowlist wird nicht mehr als lose Werteliste direkt im Request-Flow behandelt. |
| **2.7.102** | 🟡 refactor | Admin/Cookie Manager | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert- und Allowlist-Details sind in kleine Helfer ausgelagert, während die Seitenlogik fokussiert bleibt. |

---

### v2.7.101 — 25. März 2026 · Audit-Batch 183, Packages-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.101** | 🔴 fix | Admin/Pakete | **`packages.php` bündelt Action-Dispatch und Session-Alert-Pfade jetzt klarer über kleine Handler-Helfer**: Paket- und Settings-Aktionen laufen konsistenter, ohne Switch-Logik und Redirects lose im Einstieg zu verteilen. |
| **2.7.101** | 🟠 perf | Admin/Pakete | **Dispatch- und Fehlerpfade bleiben kompakter wartbar**: Handler-Map und Redirect-Flow müssen nicht mehr mehrfach im POST-Block gepflegt werden. |
| **2.7.101** | 🟡 refactor | Admin/Pakete | **Der Entry bleibt näher an den eigentlichen Modulen**: Dispatch-Details liegen in kleinen Helfern, während der Seitenaufbau unverändert fokussiert bleibt. |

---

### v2.7.100 — 25. März 2026 · Audit-Batch 182, Orders-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.100** | 🔴 fix | Admin/Orders | **`orders.php` bündelt Action-Allowlist und Session-Alert-Pfade jetzt klarer über kleine Helfer**: Token-Fehler und Aktionsrückgaben laufen konsistenter, ohne Allowlist- und Alert-Details lose im Einstieg zu verteilen. |
| **2.7.100** | 🟠 perf | Admin/Orders | **Alert- und Fehlerpfade bleiben kompakter wartbar**: die Allowlist wird nicht mehr erst im Request-Flow separat aufgebaut. |
| **2.7.100** | 🟡 refactor | Admin/Orders | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Alert- und Allowlist-Details sind in kleine Helfer ausgelagert, während die Seitenlogik fokussiert bleibt. |

---

### v2.7.99 — 25. März 2026 · Audit-Batch 181, Error-Report-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.99** | 🔴 fix | Admin/Error Report | **`error-report.php` bündelt Redirect-, Flash- und JSON-Normalisierung jetzt klarer über kleine Entry-Helfer**: Token-Fehler und Service-Rückgaben laufen konsistenter, ohne Request-Normalisierung lose im Einstieg zu verteilen. |
| **2.7.99** | 🟠 perf | Admin/Error Report | **Payload- und Fehlerpfade bleiben kompakter wartbar**: JSON-Parsing und Alert-Aufbau werden nicht mehr implizit an mehreren Stellen behandelt. |
| **2.7.99** | 🟡 refactor | Admin/Error Report | **Der Entry bleibt näher am eigentlichen Service-Aufruf**: Request-Normalisierung und Flash-Details sind in kleine Helfer ausgelagert, während die Endpoint-Logik fokussiert bleibt. |

---

### v2.7.98 — 25. März 2026 · Audit-Batch 180, Deletion-Requests-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.98** | 🔴 fix | Admin/DSGVO | **`deletion-requests.php` bündelt sein Redirect-Ziel jetzt klarer über einen kleinen Ziel-Helfer**: Guard- und Standard-Redirect nutzen denselben sichtbaren Zielpfad, statt die Ziel-URL lose auszuschreiben. |
| **2.7.98** | 🟠 perf | Admin/DSGVO | **Redirect-Ziele bleiben kompakter wartbar**: Zielpfade müssen nicht mehr an mehreren Stellen parallel gepflegt werden. |
| **2.7.98** | 🟡 refactor | Admin/DSGVO | **Der Entry bleibt näher an seinem eigentlichen Routing-Zweck**: Ziel-Details sind in einen kleinen Helfer ausgelagert, während der Einstieg minimal fokussiert bleibt. |

---

### v2.7.97 — 25. März 2026 · Audit-Batch 179, Design-Settings-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.97** | 🔴 fix | Admin/Design | **`design-settings.php` bündelt sein Redirect-Ziel jetzt klarer über einen kleinen Ziel-Helfer**: Guard- und Standard-Redirect nutzen denselben sichtbaren Zielpfad, statt die Ziel-URL lose auszuschreiben. |
| **2.7.97** | 🟠 perf | Admin/Design | **Redirect-Ziele bleiben kompakter wartbar**: Zielpfade müssen nicht mehr an mehreren Stellen parallel gepflegt werden. |
| **2.7.97** | 🟡 refactor | Admin/Design | **Der Entry bleibt näher an seinem eigentlichen Routing-Zweck**: Ziel-Details sind in einen kleinen Helfer ausgelagert, während der Einstieg minimal fokussiert bleibt. |

---

### v2.7.96 — 25. März 2026 · Audit-Batch 178, Legal-Sites-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.96** | 🔴 fix | Admin/Legal Sites | **`legal-sites.php` bündelt POST-Dispatch, Flash-Meldungen und Redirects jetzt klarer über kleine Entry-Helfer**: Action-Handler und PRG-Redirect laufen konsistenter, ohne die eigentliche Modul-Logik im Einstieg zu verteilen. |
| **2.7.96** | 🟠 perf | Admin/Legal Sites | **Fehler- und Redirect-Pfade bleiben kompakter wartbar**: Session-Alerts, Handler-Map und Redirect-Aufbau werden nicht mehr mehrfach im POST-Block ausgeschrieben. |
| **2.7.96** | 🟡 refactor | Admin/Legal Sites | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik unverändert fokussiert bleibt. |

---

### v2.7.95 — 25. März 2026 · Audit-Batch 177, Documentation-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.95** | 🔴 fix | Admin/Dokumentation | **`documentation.php` bündelt Dokumentauswahl, Aktionshandler, Flash-Meldungen und Redirects jetzt klarer über kleine Entry-Helfer**: Sync-Aktionen und PRG-Redirect laufen konsistenter, ohne die eigentliche Modul-Logik im Einstieg zu verteilen. |
| **2.7.95** | 🟠 perf | Admin/Dokumentation | **Fehler- und Redirect-Pfade bleiben kompakter wartbar**: Dokumentauswahl, Handler und Redirect-Aufbau werden nicht mehr verstreut im Request-Flow definiert. |
| **2.7.95** | 🟡 refactor | Admin/Dokumentation | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Auswahl- und Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik unverändert fokussiert bleibt. |

---

### v2.7.94 — 25. März 2026 · Audit-Batch 176, Mail-Settings-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.94** | 🔴 fix | Admin/System | **`mail-settings.php` koppelt Actions jetzt enger an dieselbe Handler-Map**: Dispatch und unbekannte Aktionen laufen konsistenter, ohne parallele Allowlist- und Handler-Definitionen im Einstieg zu pflegen. |
| **2.7.94** | 🟠 perf | Admin/System | **Handler-Definitionen bleiben kompakter wartbar**: POST-Input und Aktionseinträge werden nicht mehr doppelt zwischen Allowlist und Handlern gepflegt. |
| **2.7.94** | 🟡 refactor | Admin/System | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Dispatch-Details sind auf die kleine Handler-Map konzentriert, während die Seitenlogik unverändert fokussiert bleibt. |

---

### v2.7.93 — 25. März 2026 · Audit-Batch 175, Gruppen-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.93** | 🔴 fix | Admin/Gruppen | **`groups.php` bündelt POST-Dispatch, Flash-Meldungen und Redirects jetzt klarer über kleine Entry-Helfer**: Action-Handler und PRG-Redirect laufen konsistenter, ohne die eigentliche Modul-Logik im Einstieg zu verteilen. |
| **2.7.93** | 🟠 perf | Admin/Gruppen | **Fehler- und Redirect-Pfade bleiben kompakter wartbar**: Session-Alerts, Handler-Map und Redirect-Aufbau werden nicht mehr mehrfach im POST-Block ausgeschrieben. |
| **2.7.93** | 🟡 refactor | Admin/Gruppen | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik unverändert fokussiert bleibt. |

---

### v2.7.92 — 25. März 2026 · Audit-Batch 174, Data-Requests-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.92** | 🔴 fix | Admin/DSGVO | **`data-requests.php` bündelt POST-Dispatch, Flash-Meldungen und Redirects jetzt klarer über kleine Entry-Helfer**: Scope-Aktionen und PRG-Redirect laufen konsistenter, ohne die eigentliche Modul-Logik im Einstieg zu verteilen. |
| **2.7.92** | 🟠 perf | Admin/DSGVO | **Fehler- und Redirect-Pfade bleiben kompakter wartbar**: Session-Alerts und Redirect-Aufrufe werden nicht mehr getrennt zwischen Fehler- und Erfolgsfall behandelt. |
| **2.7.92** | 🟡 refactor | Admin/DSGVO | **Der Entry bleibt näher an den eigentlichen Modulen**: Privacy- und Deletion-Dispatch bleiben fokussiert, während Redirect-Details in kleinen Helfern liegen. |

---

### v2.7.91 — 25. März 2026 · Audit-Batch 173, Settings-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.91** | 🔴 fix | Admin/Settings | **`settings.php` bündelt Tab-Normalisierung, POST-Dispatch, Flash-Meldungen und Redirects jetzt klarer über kleine Entry-Helfer**: Action-Handler und PRG-Redirect laufen konsistenter, ohne die eigentliche Modul-Logik im Einstieg zu verteilen. |
| **2.7.91** | 🟠 perf | Admin/Settings | **Fehler- und Redirect-Pfade bleiben kompakter wartbar**: Tab-Normalisierung, Session-Alerts und Redirect-Aufbau werden nicht mehr mehrfach im POST-Block ausgeschrieben. |
| **2.7.91** | 🟡 refactor | Admin/Settings | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik unverändert fokussiert bleibt. |

---

### v2.7.90 — 25. März 2026 · Audit-Batch 172, Comments-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.90** | 🔴 fix | Admin/Kommentare | **`comments.php` bündelt POST-Dispatch, Flash-Meldungen und Redirects jetzt klarer über kleine Entry-Helfer**: Aktionspfade und PRG-Redirect laufen konsistenter, ohne die eigentliche Modul-Logik im Einstieg zu verteilen. |
| **2.7.90** | 🟠 perf | Admin/Kommentare | **Fehler- und Redirect-Pfade bleiben kompakter wartbar**: Session-Alerts und Redirect-Aufrufe werden nicht mehr mehrfach im POST-Block ausgeschrieben. |
| **2.7.90** | 🟡 refactor | Admin/Kommentare | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik unverändert fokussiert bleibt. |

---

### v2.7.89 — 25. März 2026 · Audit-Batch 171, 404-Monitor-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.89** | 🔴 fix | Admin/SEO | **`not-found-monitor.php` bündelt POST-Dispatch, Flash-Meldungen und Redirects jetzt klarer über kleine Entry-Helfer**: Token-Fehler und Aktionsrückgaben laufen konsistenter über denselben PRG-Pfad, ohne die Modul-Logik im Einstieg zu verteilen. |
| **2.7.89** | 🟠 perf | Admin/SEO | **Fehler- und Redirect-Pfade bleiben kompakter wartbar**: Action-Handler und Redirect-Ziel werden nicht mehr verstreut im POST-Block aufgebaut. |
| **2.7.89** | 🟡 refactor | Admin/SEO | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: kleine Helfer kapseln Dispatch- und Session-Details, während der Seitenaufbau unverändert fokussiert bleibt. |

---

### v2.7.88 — 25. März 2026 · Audit-Batch 170, Menü-Editor-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.88** | 🔴 fix | Admin/Menüs | **`menu-editor.php` bündelt POST-Dispatch, Flash-Meldungen und Redirects jetzt klarer über kleine Entry-Helfer**: Action-Handler und PRG-Redirect laufen konsistenter, ohne die eigentliche Modul-Logik im Einstieg zu verteilen. |
| **2.7.88** | 🟠 perf | Admin/Menüs | **Fehler- und Redirect-Pfade bleiben kompakter wartbar**: Session-Alerts, Handler-Map und Redirect-Aufbau werden nicht mehr mehrfach im POST-Block ausgeschrieben. |
| **2.7.88** | 🟡 refactor | Admin/Menüs | **Der Entry bleibt näher am eigentlichen Modul-Aufruf**: Dispatch-Details sind in kleine Helfer ausgelagert, während die Seitenlogik unverändert fokussiert bleibt. |

---

### v2.7.87 — 25. März 2026 · Audit-Batch 169, Diagnose-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.87** | 🔴 fix | Admin/Monitoring | **Der Diagnose-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/diagnose.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `system-monitor-page.php`. |
| **2.7.87** | 🟠 perf | Admin/Monitoring | **Änderungen an Diagnose-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.87** | 🟡 refactor | Admin/Monitoring | **Der Diagnose-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.86 — 25. März 2026 · Audit-Batch 168, Performance-Overview-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.86** | 🔴 fix | Admin/Performance | **Der Performance-Overview-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/performance.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `performance-page.php`. |
| **2.7.86** | 🟠 perf | Admin/Performance | **Änderungen an Performance-Overview-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.86** | 🟡 refactor | Admin/Performance | **Der Overview-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.85 — 25. März 2026 · Audit-Batch 167, SEO-Schema-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.85** | 🔴 fix | Admin/SEO | **Der SEO-Schema-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/seo-schema.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `seo-page.php`. |
| **2.7.85** | 🟠 perf | Admin/SEO | **Änderungen an SEO-Schema-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.85** | 🟡 refactor | Admin/SEO | **Der Schema-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.84 — 25. März 2026 · Audit-Batch 166, SEO-Sitemap-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.84** | 🔴 fix | Admin/SEO | **Der SEO-Sitemap-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/seo-sitemap.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `seo-page.php`. |
| **2.7.84** | 🟠 perf | Admin/SEO | **Änderungen an SEO-Sitemap-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.84** | 🟡 refactor | Admin/SEO | **Der Sitemap-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.83 — 25. März 2026 · Audit-Batch 165, SEO-Technical-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.83** | 🔴 fix | Admin/SEO | **Der SEO-Technical-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/seo-technical.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `seo-page.php`. |
| **2.7.83** | 🟠 perf | Admin/SEO | **Änderungen an SEO-Technical-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.83** | 🟡 refactor | Admin/SEO | **Der Technical-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.82 — 25. März 2026 · Audit-Batch 164, SEO-Audit-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.82** | 🔴 fix | Admin/SEO | **Der SEO-Audit-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/seo-audit.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `seo-page.php`. |
| **2.7.82** | 🟠 perf | Admin/SEO | **Änderungen an SEO-Audit-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.82** | 🟡 refactor | Admin/SEO | **Der Audit-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.81 — 25. März 2026 · Audit-Batch 163, SEO-Page-Konfigurationspfad weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.81** | 🔴 fix | Admin/SEO | **`seo-page.php` normalisiert Seitenkonfiguration jetzt zentral über einen kleinen Helper**: Fallbacks für Section-, Route-, View-, Titel- und Active-Page-Werte werden nicht mehr lose direkt im Einstieg verteilt, sondern über einen gemeinsamen Normalisierungspfad aufgelöst. |
| **2.7.81** | 🟠 perf | Admin/SEO | **Änderungen an SEO-Seitenmetadaten bleiben zentraler wartbar**: Default- und Wrapper-Werte müssen nicht mehr an mehreren Stellen parallel angepasst werden. |
| **2.7.81** | 🟡 refactor | Admin/SEO | **Das gemeinsame Seitengerüst bleibt näher an seinem eigentlichen Seitenvertrag**: Normalisierung und Fallbacks sind sichtbar gebündelt, während der restliche Request-Flow unverändert fokussiert bleibt. |

---

### v2.7.80 — 25. März 2026 · Audit-Batch 162, SEO-Social-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.80** | 🔴 fix | Admin/SEO | **Der SEO-Social-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/seo-social.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `seo-page.php`. |
| **2.7.80** | 🟠 perf | Admin/SEO | **Änderungen an SEO-Social-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.80** | 🟡 refactor | Admin/SEO | **Der Social-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.79 — 25. März 2026 · Audit-Batch 161, SEO-Meta-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.79** | 🔴 fix | Admin/SEO | **Der SEO-Meta-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/seo-meta.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `seo-page.php`. |
| **2.7.79** | 🟠 perf | Admin/SEO | **Änderungen an SEO-Meta-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.79** | 🟡 refactor | Admin/SEO | **Der Meta-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.78 — 25. März 2026 · Audit-Batch 160, SEO-Dashboard-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.78** | 🔴 fix | Admin/SEO | **Der SEO-Dashboard-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/seo-dashboard.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `seo-page.php`. |
| **2.7.78** | 🟠 perf | Admin/SEO | **Änderungen an SEO-Dashboard-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.78** | 🟡 refactor | Admin/SEO | **Der Dashboard-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.77 — 25. März 2026 · Audit-Batch 159, Analytics-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.77** | 🔴 fix | Admin/SEO | **Der Analytics-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/analytics.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `seo-page.php`. |
| **2.7.77** | 🟠 perf | Admin/SEO | **Änderungen an Analytics-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.77** | 🟡 refactor | Admin/SEO | **Der Analytics-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.76 — 25. März 2026 · Audit-Batch 158, Info-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.76** | 🔴 fix | Admin/System | **Der Info-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/info.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `system-monitor-page.php`. |
| **2.7.76** | 🟠 perf | Admin/System | **Änderungen an Info-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.76** | 🟡 refactor | Admin/System | **Der Info-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.75 — 25. März 2026 · Audit-Batch 157, Monitor-Email-Alerts-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.75** | 🔴 fix | Admin/Monitoring | **Der Monitor-Email-Alerts-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/monitor-email-alerts.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `system-monitor-page.php`. |
| **2.7.75** | 🟠 perf | Admin/Monitoring | **Änderungen an Email-Alerts-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.75** | 🟡 refactor | Admin/Monitoring | **Der Email-Alerts-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.74 — 25. März 2026 · Audit-Batch 156, Monitor-Health-Check-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.74** | 🔴 fix | Admin/Monitoring | **Der Monitor-Health-Check-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/monitor-health-check.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `system-monitor-page.php`. |
| **2.7.74** | 🟠 perf | Admin/Monitoring | **Änderungen an Health-Check-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.74** | 🟡 refactor | Admin/Monitoring | **Der Health-Check-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.73 — 25. März 2026 · Audit-Batch 155, Monitor-Cron-Status-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.73** | 🔴 fix | Admin/Monitoring | **Der Monitor-Cron-Status-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/monitor-cron-status.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `system-monitor-page.php`. |
| **2.7.73** | 🟠 perf | Admin/Monitoring | **Änderungen an Cron-Status-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.73** | 🟡 refactor | Admin/Monitoring | **Der Cron-Status-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.72 — 25. März 2026 · Audit-Batch 154, System-Monitor-Page-Konfigurationspfad weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.72** | 🔴 fix | Admin/Monitoring | **`system-monitor-page.php` normalisiert Seitenkonfiguration jetzt zentral über einen kleinen Helper**: Fallbacks für Section-, Route-, View-, Titel-, Active-Page- und Asset-Werte werden nicht mehr lose direkt im Einstieg verteilt, sondern über einen gemeinsamen Normalisierungspfad aufgelöst. |
| **2.7.72** | 🟠 perf | Admin/Monitoring | **Änderungen an Monitoring-Seitenmetadaten bleiben zentraler wartbar**: Default- und Wrapper-Werte müssen nicht mehr an mehreren Stellen parallel angepasst werden. |
| **2.7.72** | 🟡 refactor | Admin/Monitoring | **Das gemeinsame Seitengerüst bleibt näher an seinem eigentlichen Seitenvertrag**: Normalisierung und Fallbacks sind sichtbar gebündelt, während der restliche Shell-Aufbau unverändert fokussiert bleibt. |

---

### v2.7.71 — 25. März 2026 · Audit-Batch 153, Monitor-Scheduled-Tasks-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.71** | 🔴 fix | Admin/Monitoring | **Der Monitor-Scheduled-Tasks-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/monitor-scheduled-tasks.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `system-monitor-page.php`. |
| **2.7.71** | 🟠 perf | Admin/Monitoring | **Änderungen an Scheduled-Tasks-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.71** | 🟡 refactor | Admin/Monitoring | **Der Scheduled-Tasks-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.70 — 25. März 2026 · Audit-Batch 152, Monitor-Disk-Usage-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.70** | 🔴 fix | Admin/Monitoring | **Der Monitor-Disk-Usage-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/monitor-disk-usage.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `system-monitor-page.php`. |
| **2.7.70** | 🟠 perf | Admin/Monitoring | **Änderungen an Disk-Usage-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.70** | 🟡 refactor | Admin/Monitoring | **Der Disk-Usage-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.69 — 25. März 2026 · Audit-Batch 151, Monitor-Response-Time-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.69** | 🔴 fix | Admin/Monitoring | **Der Monitor-Response-Time-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/monitor-response-time.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `system-monitor-page.php`. |
| **2.7.69** | 🟠 perf | Admin/Monitoring | **Änderungen an Response-Time-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.69** | 🟡 refactor | Admin/Monitoring | **Der Response-Time-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.68 — 25. März 2026 · Audit-Batch 150, Performance-Sessions-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.68** | 🔴 fix | Admin/Performance | **Der Performance-Sessions-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/performance-sessions.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `performance-page.php`. |
| **2.7.68** | 🟠 perf | Admin/Performance | **Änderungen an Performance-Sessions-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.68** | 🟡 refactor | Admin/Performance | **Der Sessions-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.67 — 25. März 2026 · Audit-Batch 149, Performance-Page-Konfigurationspfad weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.67** | 🔴 fix | Admin/Performance | **`performance-page.php` normalisiert Seitenkonfiguration jetzt zentral über einen kleinen Helper**: Fallbacks für Section-, Route-, View-, Titel-, Active-Page- und Asset-Werte werden nicht mehr lose direkt im Einstieg verteilt, sondern über einen gemeinsamen Normalisierungspfad aufgelöst. |
| **2.7.67** | 🟠 perf | Admin/Performance | **Änderungen an Performance-Seitenmetadaten bleiben zentraler wartbar**: Default- und Wrapper-Werte müssen nicht mehr an mehreren Stellen parallel angepasst werden. |
| **2.7.67** | 🟡 refactor | Admin/Performance | **Das gemeinsame Seitengerüst bleibt näher an seinem eigentlichen Seitenvertrag**: Normalisierung und Fallbacks sind sichtbar gebündelt, während der restliche Shell-Aufbau unverändert fokussiert bleibt. |

---

### v2.7.66 — 25. März 2026 · Audit-Batch 148, Performance-Settings-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.66** | 🔴 fix | Admin/Performance | **Der Performance-Settings-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/performance-settings.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `performance-page.php`. |
| **2.7.66** | 🟠 perf | Admin/Performance | **Änderungen an Performance-Settings-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.66** | 🟡 refactor | Admin/Performance | **Der Settings-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.65 — 25. März 2026 · Audit-Batch 147, Performance-Media-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.65** | 🔴 fix | Admin/Performance | **Der Performance-Media-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/performance-media.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `performance-page.php`. |
| **2.7.65** | 🟠 perf | Admin/Performance | **Änderungen an Performance-Media-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.65** | 🟡 refactor | Admin/Performance | **Der Media-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.64 — 25. März 2026 · Audit-Batch 146, Performance-Database-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.64** | 🔴 fix | Admin/Performance | **Der Performance-Database-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/performance-database.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `performance-page.php`. |
| **2.7.64** | 🟠 perf | Admin/Performance | **Änderungen an Performance-Database-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.64** | 🟡 refactor | Admin/Performance | **Der Database-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.63 — 25. März 2026 · Audit-Batch 145, Performance-Cache-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.63** | 🔴 fix | Admin/Performance | **Der Performance-Cache-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/performance-cache.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `performance-page.php`. |
| **2.7.63** | 🟠 perf | Admin/Performance | **Änderungen an Performance-Cache-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.63** | 🟡 refactor | Admin/Performance | **Der Cache-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.62 — 25. März 2026 · Audit-Batch 144, Member-Dashboard-Page-Konfigurationspfad weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.62** | 🔴 fix | Admin/Member | **`member-dashboard-page.php` normalisiert Seitenkonfiguration jetzt zentral über einen kleinen Helper**: Fallbacks für Section-, Route-, View-, Titel-, Active-Page- und Asset-Werte werden nicht mehr lose direkt im Einstieg verteilt, sondern über einen gemeinsamen Normalisierungspfad aufgelöst. |
| **2.7.62** | 🟠 perf | Admin/Member | **Änderungen an Member-Dashboard-Seitenmetadaten bleiben zentraler wartbar**: Default- und Wrapper-Werte müssen nicht mehr an mehreren Stellen parallel angepasst werden. |
| **2.7.62** | 🟡 refactor | Admin/Member | **Das gemeinsame Seitengerüst bleibt näher an seinem eigentlichen Seitenvertrag**: Normalisierung und Fallbacks sind sichtbar gebündelt, während der restliche Shell-Aufbau unverändert fokussiert bleibt. |

---

### v2.7.61 — 25. März 2026 · Audit-Batch 143, Member-Dashboard-Widgets-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.61** | 🔴 fix | Admin/Member | **Der Member-Dashboard-Widgets-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/member-dashboard-widgets.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `member-dashboard-page.php`. |
| **2.7.61** | 🟠 perf | Admin/Member | **Änderungen an Widgets-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.61** | 🟡 refactor | Admin/Member | **Der Widgets-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.60 — 25. März 2026 · Audit-Batch 142, Member-Dashboard-Profile-Fields-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.60** | 🔴 fix | Admin/Member | **Der Member-Dashboard-Profile-Fields-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/member-dashboard-profile-fields.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `member-dashboard-page.php`. |
| **2.7.60** | 🟠 perf | Admin/Member | **Änderungen an Profile-Fields-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.60** | 🟡 refactor | Admin/Member | **Der Profile-Fields-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.59 — 25. März 2026 · Audit-Batch 141, Member-Dashboard-Plugin-Widgets-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.59** | 🔴 fix | Admin/Member | **Der Member-Dashboard-Plugin-Widgets-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/member-dashboard-plugin-widgets.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `member-dashboard-page.php`. |
| **2.7.59** | 🟠 perf | Admin/Member | **Änderungen an Plugin-Widgets-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.59** | 🟡 refactor | Admin/Member | **Der Plugin-Widgets-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.58 — 25. März 2026 · Audit-Batch 140, Member-Dashboard-Onboarding-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.58** | 🔴 fix | Admin/Member | **Der Member-Dashboard-Onboarding-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/member-dashboard-onboarding.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `member-dashboard-page.php`. |
| **2.7.58** | 🟠 perf | Admin/Member | **Änderungen an Onboarding-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.58** | 🟡 refactor | Admin/Member | **Der Onboarding-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.57 — 25. März 2026 · Audit-Batch 139, Member-Dashboard-Notifications-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.57** | 🔴 fix | Admin/Member | **Der Member-Dashboard-Notifications-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/member-dashboard-notifications.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `member-dashboard-page.php`. |
| **2.7.57** | 🟠 perf | Admin/Member | **Änderungen an Notifications-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.57** | 🟡 refactor | Admin/Member | **Der Notifications-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.56 — 25. März 2026 · Audit-Batch 138, Member-Dashboard-General-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.56** | 🔴 fix | Admin/Member | **Der Member-Dashboard-General-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/member-dashboard-general.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `member-dashboard-page.php`. |
| **2.7.56** | 🟠 perf | Admin/Member | **Änderungen an General-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.56** | 🟡 refactor | Admin/Member | **Der General-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.55 — 25. März 2026 · Audit-Batch 137, Member-Dashboard-Frontend-Modules-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.55** | 🔴 fix | Admin/Member | **Der Member-Dashboard-Frontend-Modules-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/member-dashboard-frontend-modules.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `member-dashboard-page.php`. |
| **2.7.55** | 🟠 perf | Admin/Member | **Änderungen an Frontend-Modules-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.55** | 🟡 refactor | Admin/Member | **Der Frontend-Modules-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.54 — 25. März 2026 · Audit-Batch 136, Member-Dashboard-Design-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.54** | 🔴 fix | Admin/Member | **Der Member-Dashboard-Design-Entry nutzt jetzt denselben kleinen Konfigurations-Wrapper wie seine Schwesterseiten**: `CMS/admin/member-dashboard-design.php` übergibt Section-, Route- und View-Metadaten nicht mehr als lose Einzelvariablen, sondern als kompaktes Konfigurationsarray an `member-dashboard-page.php`. |
| **2.7.54** | 🟠 perf | Admin/Member | **Änderungen an Design-Metadaten bleiben zentraler wartbar**: Section-Definitionen lassen sich konsistenter anpassen, ohne dass lose Variablensets pro Wrapper auseinanderdriften. |
| **2.7.54** | 🟡 refactor | Admin/Member | **Der Design-Wrapper bleibt näher an seinem eigentlichen Zweck**: der Entry beschreibt nur noch seine Seitenkonfiguration statt wiederholt dieselbe Variablenstruktur auszuschreiben. |

---

### v2.7.53 — 25. März 2026 · Audit-Batch 135, Member-Dashboard-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.53** | 🔴 fix | Admin/Member | **Der Member-Dashboard-Entry nutzt jetzt denselben kleinen Redirect-Helper wie andere Alias-Entrys**: `CMS/admin/member-dashboard.php` kapselt Legacy-Section-Weiterleitungen und Admin-Redirects nicht mehr als rohe Header-Aufrufe direkt im Entry. |
| **2.7.53** | 🟠 perf | Admin/Member | **Änderungen an Alias-Weiterleitungen bleiben zentraler wartbar**: Redirect-Verhalten für Legacy-Sektionen liegt sichtbar an einer Stelle und folgt dem gleichen Muster wie andere schlanke Admin-Entrys. |
| **2.7.53** | 🟡 refactor | Admin/Member | **Der Member-Dashboard-Entry bleibt näher am eigentlichen Request-Flow**: der Einstieg konzentriert sich nur noch auf Legacy-Routing, Admin-Guard und Redirect statt auf offene Header-/Exit-Strecken. |

---

### v2.7.52 — 25. März 2026 · Audit-Batch 134, Media-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.52** | 🔴 fix | Admin/Media | **Media-POST-Aktionen nutzen jetzt denselben kleinen Redirect-/Flash-Rahmen**: `CMS/admin/media.php` baut Redirects und Session-Alerts für Library-, Category- und Settings-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.52** | 🟠 perf | Admin/Media | **Änderungen an Media-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect- oder Alert-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.52** | 🟡 refactor | Admin/Media | **Der Media-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-Prüfung und Aktionsresultate statt auf wiederholte Redirect- und Alert-Orchestrierung. |

---

### v2.7.51 — 25. März 2026 · Audit-Batch 133, Legal-Sites-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.51** | 🔴 fix | Admin/Legal | **Legal-Site-POST-Aktionen nutzen jetzt denselben kleinen Redirect-/Flash-/Dispatch-Rahmen**: `CMS/admin/legal-sites.php` baut Redirects, Alerts und Aktionsauflösung für Save-, Profile-, Generate- und Page-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.51** | 🟠 perf | Admin/Legal | **Änderungen an Legal-Site-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect-, Alert- oder Dispatch-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.51** | 🟡 refactor | Admin/Legal | **Der Legal-Sites-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-Prüfung und Aktionsauflösung statt auf wiederholte Redirect- und Alert-Orchestrierung. |

---

### v2.7.50 — 25. März 2026 · Audit-Batch 132, Landing-Page-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.50** | 🔴 fix | Admin/Landing | **Landing-Page-POST-Aktionen nutzen jetzt denselben kleinen Redirect-/Flash-/Dispatch-Rahmen**: `CMS/admin/landing-page.php` baut Redirects, Alerts und Aktionsauflösung für Header-, Content-, Footer-, Design-, Feature- und Plugin-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.50** | 🟠 perf | Admin/Landing | **Änderungen an Landing-Page-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect-, Alert- oder Dispatch-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.50** | 🟡 refactor | Admin/Landing | **Der Landing-Page-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-Prüfung und Aktionsauflösung statt auf wiederholte Redirect- und Alert-Orchestrierung. |

---

### v2.7.49 — 25. März 2026 · Audit-Batch 131, Hub-Sites-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.49** | 🔴 fix | Admin/Hub | **Hub-Site-POST-Aktionen nutzen jetzt denselben kleinen Redirect-/Flash-Rahmen**: `CMS/admin/hub-sites.php` baut Redirects und Alerts für Save-, Template- und Duplicate-/Delete-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.49** | 🟠 perf | Admin/Hub | **Änderungen an Hub-Site-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect- oder Alert-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.49** | 🟡 refactor | Admin/Hub | **Der Hub-Sites-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-Prüfung und resultatspezifische Weiterleitungen statt auf wiederholte Redirect- und Alert-Orchestrierung. |

---

### v2.7.48 — 25. März 2026 · Audit-Batch 130, Groups-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.48** | 🔴 fix | Admin/Users | **Gruppen-POST-Aktionen nutzen jetzt denselben kleinen Redirect-/Flash-/Dispatch-Rahmen**: `CMS/admin/groups.php` baut Redirects, Alerts und Aktionsauflösung für Save- und Delete-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.48** | 🟠 perf | Admin/Users | **Änderungen an Gruppen-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect-, Alert- oder Dispatch-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.48** | 🟡 refactor | Admin/Users | **Der Groups-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-Prüfung und Aktionsauflösung statt auf wiederholte Redirect- und Alert-Orchestrierung. |

---

### v2.7.47 — 25. März 2026 · Audit-Batch 129, Firewall-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.47** | 🔴 fix | Admin/Security | **Firewall-POST-Aktionen nutzen jetzt denselben kleinen Redirect-/Flash-/Dispatch-Rahmen**: `CMS/admin/firewall.php` baut Redirects, Alerts und Aktionsauflösung für Settings- und Rule-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.47** | 🟠 perf | Admin/Security | **Änderungen an Firewall-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect-, Alert- oder Dispatch-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.47** | 🟡 refactor | Admin/Security | **Der Firewall-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Supported-Action-/Token-Prüfung statt auf wiederholte Redirect- und Alert-Orchestrierung. |

---

### v2.7.46 — 25. März 2026 · Audit-Batch 128, Error-Report-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.46** | 🔴 fix | Admin/System | **Fehlerreport-POST-Aktionen nutzen jetzt denselben kleinen Redirect-/Flash-Rahmen**: `CMS/admin/error-report.php` baut Redirects und Alerts für Report-Erstellung und Token-Fehler nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.46** | 🟠 perf | Admin/System | **Änderungen an Fehlerreport-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect- oder Alert-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.46** | 🟡 refactor | Admin/System | **Der Error-Report-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Redirect-Normalisierung und Report-Erstellung statt auf wiederholte Redirect- und Alert-Orchestrierung. |

---

### v2.7.45 — 25. März 2026 · Audit-Batch 127, Documentation-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.45** | 🔴 fix | Admin/System | **Dokumentations-POST-Aktionen nutzen jetzt denselben kleinen Alert-/Redirect-Rahmen**: `CMS/admin/documentation.php` baut Alert-Speicherung und Redirect-Aufbau für Doku-Sync-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.45** | 🟠 perf | Admin/System | **Änderungen an Dokumentations-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Alert- oder Redirect-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.45** | 🟡 refactor | Admin/System | **Der Documentation-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-Prüfung und Action-Dispatch statt auf wiederholte Alert- und Redirect-Orchestrierung. |

---

### v2.7.44 — 25. März 2026 · Audit-Batch 126, Backups-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.44** | 🔴 fix | Admin/System | **Backup-POST-Aktionen nutzen jetzt denselben kleinen Redirect-/Flash-Rahmen**: `CMS/admin/backups.php` baut Redirects und Session-Alerts für Full-, DB- und Delete-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.44** | 🟠 perf | Admin/System | **Änderungen an Backup-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect- oder Alert-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.44** | 🟡 refactor | Admin/System | **Der Backups-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-Prüfung und Action-Handler statt auf wiederholte Session-Alert-Orchestrierung. |

---

### v2.7.43 — 25. März 2026 · Audit-Batch 125, AntiSpam-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.43** | 🔴 fix | Admin/Security | **AntiSpam-POST-Aktionen nutzen jetzt dieselben kleinen Redirect-/Flash-/Dispatch-Helfer**: `CMS/admin/antispam.php` baut Redirects, Alerts und Aktionsauflösung für Settings- und Blacklist-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.43** | 🟠 perf | Admin/Security | **Änderungen an AntiSpam-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect-, Alert- oder Dispatch-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.43** | 🟡 refactor | Admin/Security | **Der AntiSpam-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-/Allowlist-Handling statt auf wiederholte Redirect- und Alert-Orchestrierung. |

---

### v2.7.42 — 25. März 2026 · Audit-Batch 124, 404-Monitor-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.42** | 🔴 fix | Admin/SEO | **404-Monitor-POST-Aktionen nutzen jetzt dieselben kleinen Redirect-/Flash-/Dispatch-Helfer**: `CMS/admin/not-found-monitor.php` baut Redirects, Alerts und Aktionsauflösung für Redirect-Speicherung und Log-Bereinigung nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.42** | 🟠 perf | Admin/SEO | **Änderungen an 404-Monitor-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect-, Alert- oder Dispatch-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.42** | 🟡 refactor | Admin/SEO | **Der 404-Monitor-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-Prüfung und Resultatverarbeitung statt auf wiederholte Session-Alert-Orchestrierung. |

---

### v2.7.41 — 25. März 2026 · Audit-Batch 123, Design-Settings-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.41** | 🔴 fix | Admin/Themes | **Der Design-Settings-Entry nutzt jetzt denselben kleinen Redirect-Helper wie andere Alias-Entrys**: `CMS/admin/design-settings.php` kapselt Guard- und Weiterleitungslogik nicht mehr als rohe Header-Aufrufe direkt im Entry. |
| **2.7.41** | 🟠 perf | Admin/Themes | **Änderungen am Alias-Redirect bleiben zentraler wartbar**: Redirect-Verhalten Richtung Theme-Editor liegt sichtbar an einer Stelle und folgt dem gleichen Muster wie andere schlanke Admin-Entrys. |
| **2.7.41** | 🟡 refactor | Admin/Themes | **Der Design-Settings-Entry bleibt näher am eigentlichen Request-Flow**: der Einstieg konzentriert sich nur noch auf Admin-Guard und Redirect statt auf offene Header-/Exit-Strecken. |

---

### v2.7.40 — 25. März 2026 · Audit-Batch 122, Deletion-Requests-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.40** | 🔴 fix | Admin/Legal | **Der Deletion-Requests-Entry bildet jetzt nur noch seinen tatsächlichen Redirect-Zweck ab**: `CMS/admin/deletion-requests.php` schleppt keinen unerreichbaren POST-, Modul- und View-Altpfad mehr hinter einem sofortigen Redirect mit. |
| **2.7.40** | 🟠 perf | Admin/Legal | **Änderungen am Alias-Entry bleiben zentraler wartbar**: Redirect-Verhalten für Löschanträge liegt sichtbar an einer Stelle, statt verdeckt von totem Altcode überlagert zu werden. |
| **2.7.40** | 🟡 refactor | Admin/Legal | **Der Deletion-Requests-Entry bleibt näher am eigentlichen Request-Flow**: der Einstieg konzentriert sich nur noch auf Admin-Guard und Redirect statt auf nie erreichte DSGVO-Orchestrierung. |

---

### v2.7.39 — 25. März 2026 · Audit-Batch 121, Data-Requests-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.39** | 🔴 fix | Admin/Legal | **DSGVO-POST-Aktionen nutzen jetzt dieselben kleinen Alert-/Dispatch-Helfer**: `CMS/admin/data-requests.php` baut Alert-Normalisierung und Scope-Aktionsauflösung für Auskunfts- und Löschanträge nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.39** | 🟠 perf | Admin/Legal | **Änderungen an DSGVO-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Alert- oder Scope-Dispatch-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.39** | 🟡 refactor | Admin/Legal | **Der Data-Requests-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-/Scope-Handling statt auf wiederholte Scope-Verzweigung und Alert-Orchestrierung. |

---

### v2.7.38 — 25. März 2026 · Audit-Batch 120, Cookie-Manager-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.38** | 🔴 fix | Admin/Legal | **Cookie-POST-Aktionen nutzen jetzt dieselben kleinen Redirect-/Flash-/Dispatch-Helfer**: `CMS/admin/cookie-manager.php` baut Redirects, Session-Alerts und Aktionsauflösung für Save-, Delete-, Import- und Scan-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.38** | 🟠 perf | Admin/Legal | **Änderungen an Cookie-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Redirect-, Alert- oder Dispatch-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.38** | 🟡 refactor | Admin/Legal | **Der Cookie-Manager-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-/Allowlist-Handling statt auf wiederholte Redirect- und Alert-Orchestrierung. |

---

### v2.7.37 — 25. März 2026 · Audit-Batch 119, Comments-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.37** | 🔴 fix | Admin/Comments | **Kommentar-Aktionen nutzen jetzt dieselben kleinen Alert-Helfer**: `CMS/admin/comments.php` baut Session-Alerts für unbekannte Aktionen, ungültige CSRF-Tokens und verarbeitete Resultate nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.37** | 🟠 perf | Admin/Comments | **Änderungen an Comment-Alerts bleiben zentraler wartbar**: Anpassungen an gemeinsamer Alert-Struktur müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.37** | 🟡 refactor | Admin/Comments | **Der Comments-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Aktionsverarbeitung statt auf wiederholten Session-Alert-Aufbau. |

---

### v2.7.36 — 25. März 2026 · Audit-Batch 118, Packages-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.36** | 🔴 fix | Admin/Subscriptions | **Packages-POST-Aktionen nutzen jetzt dieselben kleinen Flash-/Redirect-Helfer**: `CMS/admin/packages.php` baut Session-Alerts und Redirect-Abgänge für Save-, Seed-, Delete-, Toggle- und Settings-Aktionen nicht mehr mehrfach direkt im POST-Flow zusammen. |
| **2.7.36** | 🟠 perf | Admin/Subscriptions | **Änderungen an Packages-Aktionen bleiben zentraler wartbar**: Anpassungen an gemeinsamer Alert- oder Redirect-Logik müssen nicht mehr in mehreren Zweigen parallel nachgezogen werden. |
| **2.7.36** | 🟡 refactor | Admin/Subscriptions | **Der Packages-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-/Action-Handling statt auf wiederholte Session- und Redirect-Orchestrierung. |

---

### v2.7.35 — 25. März 2026 · Audit-Batch 117, Orders-Entry weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.35** | 🔴 fix | Admin/Subscriptions | **POST-Aktionen laufen jetzt über denselben kleinen Dispatch-Helfer**: `CMS/admin/orders.php` löst `assign_subscription`, `update_status` und `delete` nicht mehr als gestaffelten Inline-Block mit mehrfach ähnlichem Erfolgsablauf auf. |
| **2.7.35** | 🟠 perf | Admin/Subscriptions | **Änderungen an Orders-Aktionen bleiben zentraler wartbar**: Anpassungen an der Parameterübergabe oder Aktionsauflösung müssen nicht mehr in mehreren Switch-Zweigen parallel nachgezogen werden. |
| **2.7.35** | 🟡 refactor | Admin/Subscriptions | **Der Orders-Entry bleibt näher am eigentlichen Request-Flow**: der POST-Pfad konzentriert sich stärker auf Token-/Allowlist-Handling statt auf wiederholte Aktionsorchestrierung. |

---

### v2.7.34 — 25. März 2026 · Audit-Batch 116, Mail-Settings-View Button-Aktionen weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.34** | 🔴 fix | Admin/System | **Mail-Aktionsbuttons nutzen jetzt denselben kleinen Renderer**: `CMS/admin/views/system/mail-settings.php` baut wiederkehrende Submit-Buttons mit `name="action"` und variierenden Klassen/Attributen nicht mehr mehrfach direkt in Transport-, Azure-, Graph-, Log- und Queue-Bereichen zusammen. |
| **2.7.34** | 🟠 perf | Admin/System | **Änderungen an Aktionsbuttons bleiben zentraler wartbar**: Anpassungen an gemeinsamer Submit-Button-Struktur müssen nicht mehr in mehreren Mail-Settings-Bereichen parallel nachgezogen werden. |
| **2.7.34** | 🟡 refactor | Admin/System | **Die Mail-Settings-View bleibt näher an kleinen Render-Bausteinen**: die betroffenen Formulare konzentrieren sich stärker auf ihre Fachfelder statt auf wiederholtes Button-Markup. |

---

### v2.7.33 — 25. März 2026 · Audit-Batch 115, Dokumentations-View Listen weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.33** | 🔴 fix | Admin/System | **Dokumentlisten nutzen jetzt denselben kleinen Renderer**: `CMS/admin/views/system/documentation.php` rendert Schnellstart- und Bereichslisten nicht mehr mehrfach direkt über identische `foreach`-Blöcke mit demselben `is_array`-Guard. |
| **2.7.33** | 🟠 perf | Admin/System | **Änderungen an Dokumentlisten bleiben zentraler wartbar**: Anpassungen an gemeinsamer Listenlogik müssen nicht mehr in mehreren View-Bereichen parallel nachgezogen werden. |
| **2.7.33** | 🟡 refactor | Admin/System | **Die Dokumentations-View bleibt näher an kleinen Render-Bausteinen**: die betroffenen Karten konzentrieren sich stärker auf ihre Struktur statt auf wiederholte Listeniteration. |

---

### v2.7.32 — 25. März 2026 · Audit-Batch 114, Dokumentations-Sync-Service weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.32** | 🔴 fix | Admin/System | **Sync-Abschlusslogs nutzen jetzt denselben kleinen Ausführungs-Kontext-Helfer**: `CMS/admin/modules/system/DocumentationSyncService.php` baut `mode`- und `capabilities`-Kontext für Erfolgs- und Fehlerpfade nicht mehr mehrfach direkt in `finalizeSyncResult()` zusammen. |
| **2.7.32** | 🟠 perf | Admin/System | **Änderungen an Sync-Kontexten bleiben zentraler wartbar**: Anpassungen an gemeinsamen Abschluss-Metadaten müssen nicht mehr in mehreren Logging-Pfaden parallel nachgezogen werden. |
| **2.7.32** | 🟡 refactor | Admin/System | **Der Dokumentations-Sync-Service bleibt näher an kleinen Orchestrator-Bausteinen**: der Abschluss-Pfad konzentriert sich stärker auf Result-Verarbeitung statt auf wiederholten Kontextaufbau. |

---

### v2.7.31 — 25. März 2026 · Audit-Batch 113, Dokumentations-Downloader weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.31** | 🔴 fix | Admin/System | **Download-Logs nutzen jetzt denselben kleinen URL-Kontext-Helfer**: `CMS/admin/modules/system/DocumentationSyncDownloader.php` baut URL-basierte Kontextdaten für Response-, Validierungs- und Erfolgslogs nicht mehr mehrfach direkt in den jeweiligen Pfaden zusammen. |
| **2.7.31** | 🟠 perf | Admin/System | **Änderungen an Download-Kontexten bleiben zentraler wartbar**: Anpassungen an URL-bezogenen Log-Metadaten müssen nicht mehr in mehreren Downloader-Pfaden parallel nachgezogen werden. |
| **2.7.31** | 🟡 refactor | Admin/System | **Der Dokumentations-Downloader bleibt näher an kleinen Infrastruktur-Bausteinen**: die betroffenen Pfade konzentrieren sich stärker auf Download- und Validierungslogik statt auf wiederholten Kontextaufbau. |

---

### v2.7.30 — 25. März 2026 · Audit-Batch 112, Subscription-Settings-Modul weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.30** | 🔴 fix | Admin/Subscriptions | **Beide Einstellungs-Speicherpfade nutzen jetzt denselben kleinen Save-Helfer**: `CMS/admin/modules/subscriptions/SubscriptionSettingsModule.php` bündelt Admin-Guard, Persistierung und Audit-Logging für allgemeine und paketbezogene Abo-Einstellungen nicht mehr doppelt direkt in beiden Save-Methoden. |
| **2.7.30** | 🟠 perf | Admin/Subscriptions | **Änderungen an Save-Orchestrierungen bleiben zentraler wartbar**: Anpassungen an gemeinsamem Persistier- oder Audit-Verhalten müssen nicht mehr in mehreren Settings-Pfaden parallel nachgezogen werden. |
| **2.7.30** | 🟡 refactor | Admin/Subscriptions | **Das Subscription-Settings-Modul bleibt näher an kleinen Orchestrator-Bausteinen**: die Save-Methoden konzentrieren sich stärker auf ihre Payloads statt auf wiederholte Erfolgsabläufe. |

---

### v2.7.29 — 25. März 2026 · Audit-Batch 111, Orders-View Formular-Kontexte weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.29** | 🔴 fix | Admin/Subscriptions | **Bestellformulare nutzen jetzt denselben kleinen Kontext-Renderer**: `CMS/admin/views/subscriptions/orders.php` rendert die wiederkehrenden Hidden-Felder für `csrf_token`, `action` und optional `id` nicht mehr mehrfach direkt in Statuswechsel-, Delete- und Zuweisungsformularen aus. |
| **2.7.29** | 🟠 perf | Admin/Subscriptions | **Änderungen an Formular-Kontexten bleiben zentraler wartbar**: Anpassungen an gemeinsamen Hidden-Feldern müssen nicht mehr über mehrere Bestellaktionspfade hinweg synchron gehalten werden. |
| **2.7.29** | 🟡 refactor | Admin/Subscriptions | **Die Orders-View bleibt näher an kleinen Formular-Bausteinen**: die betroffenen Aktionen konzentrieren sich stärker auf ihren eigentlichen Zweck statt auf wiederholtes Kontext-Markup. |

---

### v2.7.28 — 25. März 2026 · Audit-Batch 110, Orders-Modul weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.28** | 🔴 fix | Admin/Subscriptions | **Status- und Delete-Mutationen nutzen jetzt denselben kleinen Guard-Pfad**: `CMS/admin/modules/subscriptions/OrdersModule.php` bündelt die wiederkehrenden Vorbedingungen für ID- und Snapshot-Prüfung nicht mehr doppelt direkt in beiden Mutationspfaden. |
| **2.7.28** | 🟠 perf | Admin/Subscriptions | **Audit-Kontexte für Bestellmutationen bleiben zentraler wartbar**: gemeinsame Maskierung und Kontextaufbereitung für Order-Nummer und Kundenmail müssen nicht mehr in mehreren Mutationszweigen separat gepflegt werden. |
| **2.7.28** | 🟡 refactor | Admin/Subscriptions | **Das Orders-Modul bleibt näher an kleinen Orchestrator-Bausteinen**: Status- und Delete-Pfade konzentrieren sich stärker auf ihre eigentliche Mutation statt auf wiederholten Guard- und Audit-Kontext-Aufbau. |

---

### v2.7.27 — 25. März 2026 · Audit-Batch 109, Mail-Settings-Formular-Kontexte weiter standardisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.27** | 🔴 fix | Admin/System | **Mail-Formulare nutzen jetzt denselben kleinen Kontext-Renderer**: `CMS/admin/views/system/mail-settings.php` rendert die wiederkehrenden Hidden-Felder für `csrf_token` und `tab` nicht mehr mehrfach direkt in Transport-, Azure-, Graph-, Log- und Queue-Formularen aus. |
| **2.7.27** | 🟠 perf | Admin/System | **Pflege von Formular-Kontexten bleibt zentraler wartbar**: Änderungen an CSRF- oder Tab-Kontexten müssen nicht mehr an mehreren Stellen im Template synchron gehalten werden. |
| **2.7.27** | 🟡 refactor | Admin/System | **Die Mail-Settings-View bleibt näher an kleinen View-Bausteinen**: die betroffenen Formulare konzentrieren sich stärker auf ihre eigentlichen Felder statt auf wiederholtes Hidden-Input-Markup. |

---

### v2.7.26 — 25. März 2026 · Audit-Batch 108, Settings-Config-Writer gegen globale Runtime-Fehler gehärtet

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.7.26** | 🔴 fix | Admin/Settings | **Generierte `config/app.php`-Dateien werden vor dem Live-Swap validiert**: `CMS/admin/modules/settings/SettingsModule.php` prüft die erzeugte Runtime-Konfiguration jetzt auf erwartete Kernfragmente und gültige PHP-Syntax, bevor sie atomar geschrieben wird. |
| **2.7.26** | 🟠 perf | Admin/Settings | **Fehlerhafte Settings-Generierungen brechen früher und kontrollierter ab**: der Config-Writer schaltet keine kaputte Konfiguration mehr live und reduziert damit teure Debug-/Rollback-Schleifen nach Permalink- oder allgemeinen Einstellungen. |
| **2.7.26** | 🟡 refactor | Admin/Settings | **Der Settings-Writer bleibt enger an seiner Installer-Vorlage**: Log-Pfad- und HTTPS-/HSTS-Defaults laufen konsistenter mit der Installer-Konfiguration zusammen, wodurch Runtime- und Installationspfad weniger auseinanderdriften. |

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
