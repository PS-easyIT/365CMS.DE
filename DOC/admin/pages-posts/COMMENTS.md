# Kommentarverwaltung

Kurzbeschreibung: Moderation, Statusänderung und Massenaktion für Kommentare unter `/admin/comments`.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/comments` |
| Entry Point | `CMS/admin/comments.php` |
| Modul | `CMS/admin/modules/comments/CommentsModule.php` |
| View | `CMS/admin/views/comments/list.php` |
| CSRF-Kontext | `admin_comments` |

---

## Funktionsumfang

### Kommentarliste

Die Übersicht zeigt alle Kommentare mit Filter- und Sortieroptionen. Dargestellt werden:

- Kommentartext (gekürzt)
- Autor und E-Mail
- Zugehörige Seite/Beitrag
- Status
- Datum

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

---

## Sicherheit

- Admin-Zugriffsschutz via `Auth::instance()->isAdmin()`
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_comments')`
- Redirect nach jeder schreibenden Aktion

---

## Verwandte Seiten

- [Seiten](PAGES.md)
- [Beiträge](POSTS.md)
