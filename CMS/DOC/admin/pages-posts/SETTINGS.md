# 365CMS – Inhalte: Einstellungen für Seiten, Beiträge & Archive

Kurzbeschreibung: Dokumentiert den tatsächlichen Admin-Vertrag für den Unterbereich **Seiten & Beiträge → Einstellungen**.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.508

---

## Route & Scope

- Aufruf im Admin: `/admin/settings?tab=content`
- Sidebar-Slug: `content-settings`
- Implementierung:
  - Controller: `CMS/admin/settings.php`
  - Persistenz: `CMS/admin/modules/settings/SettingsModule.php`
  - View: `CMS/admin/views/settings/general.php`

Der Bereich bündelt alle inhaltsnahen Defaults, die sich direkt auf neue Seiten, Beiträge und die öffentlichen Archivpfade auswirken.

---

## Enthaltene Einstellungen

### Editor-Defaults

- globaler Standard-Editor (`Editor.js` oder `SunEditor`)
- Beitrags-Editorbreite
- Seiten-Editorbreite

Diese Werte werden in den jeweiligen Editoren direkt zur Laufzeit verwendet:

- Beiträge: `CMS/admin/views/posts/edit.php`
- Seiten: `CMS/admin/views/pages/edit.php`
- Editor-Service: `CMS/core/Services/EditorService.php`

### Speicher-Defaults

- Standardstatus für neue Beiträge (`draft` / `published`)
- Standardstatus für neue Seiten/Sites (`draft` / `published` / `private`)

Bestehende Inhalte behalten ihren bereits gespeicherten Status.

### URL-Struktur & Archive

- Beitrags-Permalink-Preset oder benutzerdefinierte Struktur
- Kategorie-Basen für DE und EN
- Tag-Basen für DE und EN
- manueller Reparatur-Trigger für importierte Slugs

Die Permalink-Normalisierung läuft zentral über `CMS\Services\PermalinkService`.

---

## Validierungs- und UX-Vertrag

- fehlgeschlagene Saves bleiben nach Redirect im selben Tab sichtbar
- zuletzt eingegebene Werte werden bei Validierungsfehlern wieder ins Formular zurückgelegt
- problematische Felder werden per `aria-invalid` / `aria-describedby` markiert
- Kategorie- und Tag-Basis dürfen je Sprache nicht identisch sein
- bei `Benutzerdefiniert` ist eine echte Permalink-Struktur erforderlich

Damit folgt der Bereich nun dem gleichen robusteren Redirect-/Form-State-Muster wie andere modernisierte Admin-Editoren.

---

## Hinweise für Themes & Routing

- Die Blog-Übersicht bleibt weiterhin unter `/blog` erreichbar.
- Kategorie- und Tag-Archive landen weiter auf den Theme-Templates `category.php` und `tag.php`.
- Englische Archivpfade laufen über `/en/<basis>/...`.

Beispiele:

- DE Kategorie: `/kategorie/azure`
- EN Kategorie: `/en/category/azure`
- DE Tag: `/tag/security`
- EN Tag: `/en/tag/security`

---

## Audit-Notizen

Mit Version `2.9.508` wurde der Bereich nachgeschärft:

- die UI beschreibt den Content-Tab endlich als eigenen Unterbereich statt pauschal als „Allgemeine Einstellungen“
- Permalink-/Archiv-Einstellungen wurden aus dem allgemeinen Systemblock in den Content-Tab verschoben
- Validierungsfehler verlieren Eingaben nicht mehr beim Redirect
- der Custom-Permalink wird nur noch dann bearbeitbar, wenn das Preset wirklich auf `Benutzerdefiniert` steht
