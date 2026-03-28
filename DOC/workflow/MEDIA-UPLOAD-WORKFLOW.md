# Medien-Upload Workflow – 365CMS

> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell
>
> **Bereich:** Medien-Verwaltung · **Version:** 2.8.0 RC  
> **Services:** `core/Services/MediaService.php`, `core/Services/Media/UploadHandler.php`, `core/Services/Media/MediaRepository.php`  
> **Admin-Seite:** `admin/media.php`  
> **Member-Seite:** `member/media.php`

---
<!-- UPDATED: 2026-03-28 -->

## Übersicht: aktuelle Medien-Pipeline

```text
Native Upload-Form
    → CSRF / Request-Validierung
    → Upload- und Typ-Grenzen aus Media-Settings
    → Dateinamen-Sanitisierung / Unique-Filename-Logik
    → sichere Speicherung unter uploads/
    → optionale EXIF-Bereinigung / WebP / Thumbnails
    → Metadatenpflege in media-meta.json
    → Anzeige in Admin-/Member-Bibliothek
```

---

## Workflow 1: Admin-Upload

Der Admin lädt Dateien über das Upload-Modal der Bibliothek hoch. Die UI ist bewusst nativ gehalten; die frühere aktive FilePond-Runtime ist kein Produktivpfad mehr.

Wichtige Eckpunkte:

- Einstieg: `/admin/media`
- Upload-Feld: Mehrfachauswahl über natives `<input type="file" multiple>`
- Zielpfad: aktueller Bibliothekspfad
- Token-Scope: `media_action`
- Grenzen: durch Modul vorbereitete Constraints und `config/media-settings.json`

---

## Workflow 2: Member-Upload

Member-Uploads laufen ebenfalls nativ und sind streng an den persönlichen Root-Pfad gebunden.

Grundprinzip:

- persönlicher Root: `member/user-<id>`
- Uploads verlassen diesen Root nie
- Redirects und Breadcrumbs bleiben im aktuellen Ordnerkontext
- Member-spezifische Limits und erlaubte Typen kommen aus den Medien-Settings

---

## Workflow 3: Rename / Move

Sowohl Admin als auch Member nutzen in 2.8.0 RC kompakte Dropdown-Aktionen mit zentralen Modalen.

Technischer Vertrag:

- Trigger-Buttons liefern `data-media-path`, `data-media-name` und optional `data-media-target`
- das Modal wird per `show.bs.modal` befüllt
- ein Pending-Trigger-Fallback sorgt dafür, dass auch Trigger aus schließenden Dropdown-Menüs zuverlässig den richtigen Pfad an das Modal liefern
- serverseitig werden Pfade erneut normalisiert und validiert

---

## Workflow 4: Bulk-Aktionen im Admin

Die Admin-Bibliothek unterstützt Bulk-Löschen und Bulk-Verschieben.

Wesentliche Regeln:

- Auswahl über native Checkboxen mit Formularbindung
- Aktion wird serverseitig nochmals validiert
- Zielordner kommen aus vorbereiteten Move-Targets statt aus freiem Texteingabefeld
- Pfadlisten werden serverseitig normalisiert und dedupliziert

---

## Workflow 5: Schutzlogik für Systempfade

Die Bibliothek kennt geschützte Systempfade.

Aktueller Stand:

- Root-Systemordner bleiben geschützt
- unter `member/` bleibt die direkte User-Root geschützt
- Member-Unterordner darunter sind reguläre Inhalte und nicht mehr pauschal als Systempfad markiert

Das verhindert einerseits ungewollte Mutationen an Infrastrukturpfaden und vermeidet andererseits, dass echte Member-Ordner fälschlich ihre Aktionen verlieren.

---

## Sicherheits-Checkliste

```text
UPLOAD-SICHERHEIT:
[ ] CSRF-Scope korrekt gesetzt (`media_action`)
[ ] Dateityp-/Größenprüfung kommt aus Media-Settings bzw. Validierungsregeln
[ ] Pfade werden serverseitig normalisiert
[ ] Upload-Ziel bleibt innerhalb des erlaubten Root-Pfads

MUTATIONS-SICHERHEIT:
[ ] Rename-/Move-/Delete-Pfade werden nicht aus dem Frontend vertraut übernommen
[ ] Member-Pfade bleiben innerhalb `member/user-<id>`
[ ] Bulk-Aktionen validieren Auswahl und Zielordner erneut

ASSET-ARCHITEKTUR:
[ ] keine aktive Abhängigkeit von FilePond/elFinder im Produktivpfad
[ ] Feed- und Consent-Laufzeiten hängen an nativen Services/Assets
```

---

## Referenzen

- [../admin/media/README.md](../admin/media/README.md)
- [../admin/media/MEDIA.md](../admin/media/MEDIA.md)
- [../member/README.md](../member/README.md)
- [../core/SERVICES.md](../core/SERVICES.md)
