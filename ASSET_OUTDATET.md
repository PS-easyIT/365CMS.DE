# ASSET_OUTDATET

Diese Liste enthält Asset-Bundles oder Unterordner in `CMS/assets/`, für die aktuell **keine aktive Nutzung** oder **keine vollständige Verdrahtung** im 365CMS-Code nachweisbar ist.

## Konkrete Lösch- / Aufräumempfehlung nach Priorität

### Priorität A – zuerst entfernen

Diese Kandidaten haben aktuell die beste Kombination aus **hoher Unnötigkeits-Wahrscheinlichkeit** und **geringem Risiko**.

| Priorität | Pfad | Empfehlung | Risiko |
|---|---|---|---|
| A | `CMS/assets/Monolog/Test/` | löschen | sehr gering |
| A | `CMS/assets/translation/Test/` | löschen | sehr gering |
| A | `CMS/assets/Monolog/` | komplett entfernen oder auslagern | gering |
| A | `CMS/assets/rate-limiter/` | komplett entfernen oder auslagern | gering |

**Begründung:**

- `Monolog/Test/` und `translation/Test/` sind klarer Nicht-Produktivcode.
- `Monolog/` wird von der aktuellen 365CMS-Logging-Implementierung nicht verwendet.
- `rate-limiter/` hat keine nachweisbare Runtime-Nutzung.

### Priorität B – nach Kurztest entfernen

Diese Kandidaten sind sehr gute Aufräumziele, sollten aber nach dem Entfernen einmal im Backend / Upload / Admin kurz geprüft werden.

| Priorität | Pfad | Empfehlung | Risiko |
|---|---|---|---|
| B | `CMS/assets/schema-org/` | entfernen oder auslagern | mittel |
| B | `CMS/assets/filepond/locale/` | alternativ nur auf benötigte Sprachen reduzieren | gering bis mittel |

**Begründung:**

- `schema-org/` ist laut Autoloader ausdrücklich nur Reserve; `SEOService` erzeugt JSON-LD manuell.
- Die vielen `filepond`-Locales sind nur dann sinnvoll, wenn du tatsächlich mehrere UI-Sprachen im Upload-Widget auslieferst.

### Priorität C – nur gezielt und mit Backup anfassen

Diese Kandidaten können Platz sparen, sind aber architektonisch sensibler oder nur teilweise unnötig.

| Priorität | Pfad | Empfehlung | Risiko |
|---|---|---|---|
| C | `CMS/assets/mailer/` | erst nach endgültiger Mail-Strategie entscheiden | mittel bis hoch |
| C | `CMS/assets/tabler/libs/` große Teilbereiche | nur selektiv ausdünnen | hoch |

**Begründung:**

- `mailer/` ist derzeit unvollständig, könnte aber künftig wieder benötigt werden, sobald die fehlende Abhängigkeit sauber ergänzt ist.
- `tabler/` ist aktiv im Einsatz; nur Teile unter `libs/` wirken potenziell überdimensioniert. Hier wäre ein blindes Löschen eher ein Abenteuer mit Nebenwirkungen.

## Empfohlene Reihenfolge in der Praxis

Wenn du schnell und relativ sicher Platz gewinnen willst, würde ich in genau dieser Reihenfolge vorgehen:

1. `CMS/assets/Monolog/Test/`
2. `CMS/assets/translation/Test/`
3. `CMS/assets/Monolog/`
4. `CMS/assets/rate-limiter/`
5. `CMS/assets/schema-org/`
6. `CMS/assets/filepond/locale/`

## Mini-Check nach jeder Löschrunde

Nach jeder A- oder B-Runde kurz prüfen:

- Backend lädt noch ohne PHP-Fehler
- Login / Auth funktioniert
- Admin-Navigation funktioniert
- Medien- / Upload-Bereich öffnet sich fehlerfrei
- SEO-Ausgabe im Frontend ist noch vorhanden
- Suche und Feed-Funktionen laufen weiterhin

## Konservative Empfehlung

Wenn du besonders vorsichtig vorgehen willst:

- **A vollständig umsetzen**
- **B erst nach Backup und kurzem Smoke-Test**
- **C zunächst nur dokumentieren, nicht löschen**

## Wahrscheinlich entfernbar oder auslagerbar

### Top-Level-Bundles

| Pfad | Einstufung | Begründung |
|---|---|---|
| `CMS/assets/Monolog/` | sehr wahrscheinlich unnötig | 365CMS nutzt ein eigenes Logging in `CMS/core/Logger.php`; es gibt keine produktive `Monolog\...`-Nutzung außerhalb des Bundles selbst. |
| `CMS/assets/rate-limiter/` | sehr wahrscheinlich unnötig | Nur Autoloader- und Bundle-Treffer, keine Nutzung in Runtime-Code. |
| `CMS/assets/schema-org/` | wahrscheinlich unnötig | `CMS/assets/autoload.php` dokumentiert selbst, dass `SEOService` Schema.org derzeit **manuell** erzeugt; die Library ist Reserve. |

## Technisch vorhanden, aber derzeit nicht sauber produktiv

| Pfad | Einstufung | Begründung |
|---|---|---|
| `CMS/assets/mailer/` | aktuell nicht produktiv | Das Bundle ist vorhanden, aber die Session-Historie und Codebasis zeigen, dass `symfony/mime` fehlt; damit ist die Mailer-Integration derzeit unvollständig. |

## Mögliche Platzfresser innerhalb von Bundles

Diese Unterordner sind häufig nur für Entwicklung, Tests oder Alternativ-Distributionen relevant. Vor dem Löschen sollte einmal geprüft werden, ob sie für Updates oder Debugging bewusst mitgeführt werden.

| Pfad | Einstufung | Begründung |
|---|---|---|
| `CMS/assets/Monolog/Test/` | Kandidat | Testcode, im Runtime-Betrieb nicht erforderlich. |
| `CMS/assets/translation/Test/` | Kandidat | Testcode, nicht für den Livebetrieb nötig. |
| `CMS/assets/tabler/libs/` große Teilbereiche | Kandidat | Tabler bringt viele zusätzliche Bibliotheken mit; nur ein Teil davon wird im aktuellen Admin tatsächlich gebraucht. |
| `CMS/assets/filepond/locale/` viele Sprachdateien | Kandidat | Wenn 365CMS nur `de`/`en` benötigt, können viele Locale-Dateien entfallen. |

## Nicht als „outdated“ markieren

Diese Einträge belegen Platz, sind aber aktiv in Benutzung oder systemintern notwendig:

- `Carbon/`
- `cookieconsent/`
- `css/`
- `editorjs/`
- `elfinder/`
- `filepond/`
- `gridjs/`
- `htmlpurifier/`
- `images/`
- `js/`
- `ldaprecord/`
- `photoswipe/`
- `php-jwt/`
- `simplepielibrary/`
- `simplepiesrc/`
- `suneditor/`
- `tabler/`
- `tntsearchhelper/`
- `tntsearchsrc/`
- `translation/`
- `twofactorauth/`
- `webauthn/`
- `autoload.php`

## Nachweise

- `CMS/core/Services/FileUploadService.php`, `CMS/admin/media.php` und `CMS/assets/js/admin-media-integrations.js` binden `FilePond` produktiv ein.
- `CMS/core/Services/ElfinderService.php`, `CMS/admin/media.php` und `CMS/admin/views/media/library.php` binden `elFinder` produktiv ein.
- `CMS/admin/users.php`, `CMS/admin/pages.php`, `CMS/admin/posts.php` und `CMS/assets/js/gridjs-init.js` binden `Grid.js` produktiv ein.
- `CMS/core/Logger.php` implementiert eigenes Logging ohne Monolog.
- `CMS/assets/autoload.php` enthält den Hinweis, dass `schema-org/` derzeit nur Reserve ist.
- `CMS/core/Services/SEOService.php` erzeugt JSON-LD manuell via `<script type="application/ld+json">`.