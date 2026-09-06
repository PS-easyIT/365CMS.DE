# 365CMS - Rollen und Berechtigungen

**Stand:** 2026-09-06 | **Dokumentationsversion:** 3.4.00 | **Route:** `/admin/roles`

## Zweck

Die Rollenverwaltung pflegt die dynamische Capability-Matrix des CMS. Die Benutzerverwaltung und die Laufzeitprüfung verwenden dieselbe Rollenquelle; Rollenfilter und Rollenauswahl driften dadurch nicht auseinander.

## Rollen

Die Standardrollen sind:

- `admin`
- `editor`
- `author`
- `member`

Zusätzliche Rollen können über die Oberfläche angelegt werden. Ein neuer Rollen-Slug wird normalisiert und kann Rechte aus einer vorhandenen Vorlage übernehmen. Die tatsächliche Rollenauswahl in der Benutzerverwaltung stammt immer aus der aktuell geladenen Rollenquelle.

## Capability-Gruppen

Die aktuelle Rechte-Matrix umfasst unter anderem:

| Bereich | Beispiele |
|---|---|
| Pages | `pages.view`, `pages.create`, `pages.edit`, `pages.publish`, `manage_pages` |
| Posts | `posts.view`, `posts.create`, `posts.edit`, `edit_all_posts`, `edit_own_posts` |
| Medien | `media.view`, `media.upload`, `media.delete`, `manage_media` |
| Benutzer | `users.view`, `users.create`, `users.edit`, `users.delete`, `users.roles`, `manage_users` |
| Themes/Plugins | `themes.*`, `plugins.*` |
| Einstellungen | `settings.*`, `manage_settings`, `manage_system` |
| AI | `manage_ai_services`, `use_ai_translation`, `use_ai_rewrite`, `use_ai_summary`, `use_ai_seo_meta` |
| Analytics/Kommentare | `view_analytics`, `comments.view`, `comments.moderate`, `comments.delete` |

Legacy-Core-Capabilities bleiben produktiv und dürfen bei Migrationen nicht ungeprüft entfernt werden.

## Verfügbare Aktionen

Der Entry-Point akzeptiert ausschließlich:

- `save_permissions`
- `add_role`, `update_role`, `delete_role`
- `add_capability`, `update_capability`, `delete_capability`

Änderungen an der Matrix werden transaktional geschrieben. Für die Rolle `admin` werden die vorgesehenen Rechte systemseitig vollständig gewährt. Löschen und Umbenennen von Rollen oder Capabilities muss vorab auf Benutzer- und Plugin-Abhängigkeiten geprüft werden.

## Rollenvergleich

Der read-only Vergleich nutzt `compare_from` und `compare_to` als GET-Parameter. Beide Rollen werden gegen die bekannte Rollenliste normalisiert. Angezeigt werden:

- gemeinsame Capabilities,
- nur in der Ausgangsrolle vorhandene Rechte,
- nur in der Zielrolle vorhandene Rechte,
- Gruppierung nach Fachbereich,
- Anzahlwerte für die schnelle Prüfung.

Ungültige oder identische Werte fallen auf bekannte Rollen zurück. Der Vergleich führt keine Schreibaktion aus und benötigt keinen zusätzlichen Token-Pfad. Er ist für Least-Privilege-Reviews und Freigaben gedacht, nicht als Ersatz für einen Test mit einem echten Benutzerkonto.

## Datenmodell und Laufzeit

- `roles` kann als fachliche Rollenquelle vorhanden sein.
- `role_permissions` speichert Rolle, Capability und Grant-Status.
- `UsersModule` und `UserService` lesen die verfügbaren Rollen aus derselben Laufzeitquelle.
- `Auth::hasCapability()` wertet die Matrix für Nicht-Administratoren aus.

Nach einer Änderung sollte ein Testkonto die betroffenen Admin- und Frontend-Wege prüfen. Caches oder laufende Sessions werden nicht pauschal durch die Dokumentation oder den Rollenvergleich verändert.

## Sicherheit

Die Seite erfordert Admin-Status und `manage_users`. Schreibende Requests werden per `admin_roles`-CSRF-Token geschützt und danach per PRG weitergeleitet. Interne Exception-Texte werden nicht als Admin-Alert oder Fehlerreport-Payload ausgegeben.

## Verwandte Dokumente

- [USERS.md](USERS.md)
- [GROUPS.md](GROUPS.md)
- [../../core/SECURITY.md](../../core/SECURITY.md)
