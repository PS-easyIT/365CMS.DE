# Benutzergruppen (Groups)

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `admin/groups.php`

Gruppierung von Benutzern zur effizienten, granularen Rechteverwaltung im 365CMS.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Gruppen erstellen & verwalten](#2-gruppen-erstellen--verwalten)
3. [Mitglieder zuweisen](#3-mitglieder-zuweisen)
4. [Berechtigungen (ACL)](#4-berechtigungen-acl)
5. [Automatische Zuweisung](#5-automatische-zuweisung)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/admin/groups.php`

Gruppen ergänzen das Rollen-System (Admin, Editor, Member) um **feingranulare Zugriffskontrolle**:
- Gruppen können bestimmten Inhalten, Downloads oder Funktionen Zugriff gewähren
- Ein Benutzer kann mehreren Gruppen angehören
- Gruppen können an Subscription-Pläne gekoppelt werden

---

## 2. Gruppen erstellen & verwalten

### Gruppe erstellen

| Feld | Pflicht | Beschreibung |
|---|---|---|
| **Name** | Ja | Anzeigename (z.B. „Premium-Mitglieder", „Redaktion") |
| **Slug** | Ja | Technischer Bezeichner (auto-generiert aus Name) |
| **Beschreibung** | Nein | Interne Beschreibung des Zwecks |
| **Farbe** | Nein | Farbkodierung für Übersichten (Hex-Wert) |

### Gruppen-Übersicht
Tabelle mit: Name, Slug, Mitgliederzahl, verknüpfte Berechtigungen, Aktionen (Bearbeiten, Löschen)

---

## 3. Mitglieder zuweisen

### Manuell
1. Gruppe öffnen → Reiter „Mitglieder"
2. Benutzer per E-Mail/Name suchen und hinzufügen
3. Mehrere Benutzer gleichzeitig per Bulk-Upload (CSV: E-Mail-Adressen)

### Automatisch (bei Registrierung)
- Checkbox „Neue Benutzer automatisch dieser Gruppe zuweisen"
- Nützlich für eine Standard-Einsteiger-Gruppe

### Via Subscription (Kopplung)
- In den Subscription-Plan-Einstellungen: Gruppe auswählen
- Alle aktiven Abonnenten dieses Plans werden automatisch der Gruppe zugewiesen
- Ablauf/Kündigung → automatische Entfernung aus der Gruppe

---

## 4. Berechtigungen (ACL)

Verfügbare Berechtigungen pro Gruppe:

### Inhalts-Zugriff
| Berechtigung | Key | Beschreibung |
|---|---|---|
| Geschützte Seiten | `page_view_restricted` | Zugriff auf `private`-Seiten |
| Download-Berechtigung | `file_download` | Dateien herunterladen |
| Kommentare schreiben | `comment_create` | Kommentare hinterlassen |
| Blog-Inhalte erstellen | `post_create` | Beiträge einreichen (Guest-Author) |

### Feature-Zugriff
| Berechtigung | Key | Beschreibung |
|---|---|---|
| Werbefrei | `no_ads` | Keine Werbebanner anzeigen |
| API-Zugang | `api_access` | REST-API nutzen |
| Erweiterte Suche | `advanced_search` | Suche mit Filtern |
| Export-Funktion | `data_export` | Eigene Daten exportieren |

### Plugin-Berechtigungen (Beispiele)
| Berechtigung | Plugin | Beschreibung |
|---|---|---|
| `expert_profile_create` | `cms-experts` | Experten-Profil anlegen |
| `event_create` | `cms-events` | Veranstaltung einstellen |
| `job_create` | `cms-jobads` | Stellenanzeige schalten |

---

## 5. Automatische Zuweisung

### Regeln-System
Gruppen können mit Regeln verknüpft werden:

```php
// Beispiel-Regel: User mit verifizierter E-Mail bekommen Gruppe 'verified'
$groupManager->addRule('verified_users', [
    'condition' => 'email_verified',
    'operator'  => '=',
    'value'     => true,
]);
```

### Verfügbare Bedingungen
| Bedingung | Beschreibung |
|---|---|
| `email_verified` | E-Mail verifiziert |
| `registration_date` | Registrierung vor/nach Datum |
| `subscription_plan` | Aktiver Abo-Plan |
| `login_count` | Anzahl Logins |
| `meta_field` | Beliebiges user_meta-Feld |

---

## 6. Technische Details

**Service:** `CMS\Services\GroupService`

```php
$groups = GroupService::instance();

// Gruppe eines Benutzers prüfen
$isMember = $groups->userInGroup($userId, 'premium-members');

// Berechtigung prüfen
$canDownload = $groups->userHasPermission($userId, 'file_download');

// Benutzer zu Gruppe hinzufügen
$groups->addUserToGroup($userId, 'premium-members');

// Benutzer aus Gruppe entfernen
$groups->removeUserFromGroup($userId, 'premium-members');

// Alle Gruppen eines Benutzers
$userGroups = $groups->getUserGroups($userId);
```

**Datenbank:**
```sql
CREATE TABLE cms_groups (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    permissions JSON,
    color       VARCHAR(7),
    auto_assign TINYINT(1) DEFAULT 0
);

CREATE TABLE cms_group_users (
    group_id    INT NOT NULL,
    user_id     INT NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT,          -- Null = automatisch
    PRIMARY KEY (group_id, user_id)
);
```

**Hooks:**
```php
do_action('cms_user_added_to_group', $userId, $groupId, $assignedBy);
do_action('cms_user_removed_from_group', $userId, $groupId);
add_filter('cms_user_permissions', 'my_permission_extend', 10, 2);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
