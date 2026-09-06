# Kommentarverwaltung

Kurzbeschreibung: Moderation, Statuswechsel, Schnellfilter und Massenaktionen für Kommentare unter `/admin/comments`.

Letzte Aktualisierung: 2026-05-09 · Version 2.9.704

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/comments` |
| Entry Point | `CMS/admin/comments.php` |
| Modul | `CMS/admin/modules/comments/CommentsModule.php` |
| View | `CMS/admin/views/comments/list.php` |
| Service | `CMS/core/Services/CommentService.php` |
| CSRF-Kontext | `admin_comments` |
| Rechte | `comments.view`, `comments.moderate`, `comments.delete` |

---

## Funktionsumfang

### Kommentarliste

Die Übersicht kombiniert Status-Tabs mit serverseitigen Schnellfiltern. Dargestellt werden:

- Kommentartext (gekürzt)
- Autor und E-Mail
- Autorentyp (`Gast`, `Registriert`, `Anonymes Mitglied`)
- Zugehöriger Beitrag
- Status
- Datum
- Hinweise auf verwaiste Kommentare ohne auflösbaren Beitragsdatensatz

### Status-Tabs

| Tab | Wirkung |
|---|---|
| `Alle` | Zeigt alle moderierbaren Kommentare außer `spam` |
| `Ausstehend` | Nur wartende Kommentare |
| `Freigegeben` | Nur veröffentlichte Kommentare |
| `Spam` | Nur Spam-Kommentare |
| `Papierkorb` | Nur Kommentare im Papierkorb |

Die Tabs behalten aktive Schnellfilter bei, damit Moderation nicht nach jedem Statuswechsel wieder auf den Defaultzustand zurückfällt.

### Schnellfilter

| Filter | Query-Parameter | Zweck |
|---|---|---|
| Schnellsuche | `q` | durchsucht Autor, E-Mail, Kommentartext und Beitragstitel |
| Autorentyp | `author_scope` | `all`, `registered`, `guest`, `anonymous` |
| Beitragsbezug | `link_scope` | `all`, `linked`, `orphaned` |

Aktive Filter werden direkt über der Tabelle als Badges angezeigt und lassen sich gesammelt zurücksetzen.

### Statusmodell

| Status | Bedeutung |
|---|---|
| `approved` | Sichtbar im Frontend |
| `pending` | Wartet auf Moderation |
| `spam` | Als Spam markiert |
| `trash` | Gelöscht / Papierkorb |

### Einzelaktionen

| Aktion | Methode |
|---|---|
| Status ändern | `updateStatus(int $id, string $status)` |
| Kommentar löschen | `delete(int $id)` |

### Massenaktionen

`bulkAction(string $action, array $ids)` unterstützt Statusänderungen und Löschung für mehrere Kommentare gleichzeitig.

Unterstützte Aktionen:

- `approve`
- `spam`
- `trash`
- `delete`

Sobald Einträge ausgewählt sind, schaltet die UI in einen sichtbaren Batch-Modus. Zeilenaktionen werden dabei bewusst deaktiviert, damit Bulk- und Einzelaktionen nicht parallel gegeneinander laufen.

### Request- und Redirect-Vertrag

- Schreibende Aktionen laufen per POST und enden per PRG wieder auf derselben Listenansicht.
- Aktive Filter (`status`, `q`, `author_scope`, `link_scope`) werden über den Redirect erhalten.
- Destruktive Bulk-Löschungen verlangen eine zusätzliche Bestätigung.

### Datenvertrag des Services

`CommentService::getComments()` unterstützt für die Admin-Liste neben Status jetzt auch:

- freie Suche über `LIKE`-Filter mit Escape
- Autorentyp-Filter über `user_id` und den anonymen Mitgliedsmodus
- Link-/Orphan-Filter über die Beitragsverknüpfung

Die Kommentarruntime selbst bleibt weiterhin **beitragsbezogen**: öffentliche Kommentare werden nur für veröffentlichte Beiträge mit aktivem `allow_comments` akzeptiert.

---

## Sicherheit

- Zugriffsschutz über `comments.view` / `comments.moderate` / `comments.delete`
- CSRF-Prüfung über den gemeinsamen Section-Shell-Vertrag `admin_comments`
- PRG-Redirect nach jeder schreibenden Aktion
- Such- und Filterwerte werden serverseitig normalisiert und begrenzt
- Bulk-IDs werden dedupliziert und hart limitiert

---

## Verwandte Seiten

- [Seiten](PAGES.md)
- [Beiträge](POSTS.md)
- [Übersicht Seiten & Beiträge](README.md)
