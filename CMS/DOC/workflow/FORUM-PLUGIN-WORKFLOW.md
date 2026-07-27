# Forum-Plugin Workflow – cms-forum

> **Stand:** 2026-04-07 | **Version:** 2.9.0 | **Status:** Konzept (nicht implementiert)
>
> **Bereich:** Neues Plugin-Konzept · **Status:** Konzept (nicht implementiert)  
> **Referenz:** [NEW-PLUGIN-CONCEPTS.md](../feature/NEW-PLUGIN-CONCEPTS.md)  
> **Entwicklungs-Workflow:** [PLUGIN-DEVELOPMENT-WORKFLOW.md](PLUGIN-DEVELOPMENT-WORKFLOW.md)

---
<!-- UPDATED: 2026-04-07 -->

## Übersicht: Feature-Set

| Phase | Features | Komplexität |
|---|---|---|
| Phase 1 (MVP) | Kategorien, Threads, Antworten, Member-Profil-Verlinkung | 4 Tage |
| Phase 2 | Moderation, Suche, Benachrichtigungen | 2 Tage |
| Phase 3 | Gamification (Punkte), Promoted Threads, Abonnements | 2 Tage |

---

## Workflow 1: Neues Thema erstellen

```
Member-Dashboard → Tab "Forum"
oder: /forum/
    ↓
Kategorie auswählen:
  z.B. "Technische Fragen", "Projektvorstellungen", "Off-Topic"
    ↓
"Neues Thema erstellen"
    ↓
Formular (mit CSRF-Token):
  - Titel: "Wie integriere ich Stripe in 365CMS?"  (max. 255 Zeichen)
  - Inhalt: WYSIWYG oder Markdown (konfigurierbar)
  - Tags: optional (komma-getrennt)
    ↓
Validierung:
  - Angemeldet? → isLoggedIn() Prüfung
  - Rate-Limit: max. 5 neue Themen pro Tag pro User
  - Spam-Filter: Bekannte Spam-Muster
    ↓
DB-INSERT: cms_forum_threads
  - status = 'open'
  - user_id = aktueller User
    ↓
Weiterleitug zum neuen Thread
Benachrichtigung an Kategorie-Abonnenten
```

---

## Workflow 2: Antwort posten

```
Thread-Seite → Antwort-Formular (unten)
    ↓
Inhalt eingeben (Mindestlänge: 10 Zeichen)
    ↓
Validierung:
  - Angemeldet? Pflicht
  - Thread offen? (status !== 'closed' und !== 'archived')
  - Rate-Limit: 20 Antworten pro Stunde
  - Doppeltabsende-Schutz: Gleicher Text in letzten 60 Sekunden?
    ↓
DB-INSERT: cms_forum_posts
  - thread_id: aktiver Thread
  - user_id: aktueller User
    ↓
Thread: last_activity = NOW(), reply_count + 1
    ↓
Benachrichtigung an Thread-Autor (falls Benachrichtigung aktiv)
Weiterleitug zur neuen Antwort (#post-ID)
```

---

## Workflow 3: Moderation

```
AUTOMATISCHE MODERATION:
  - Spam-Score > 0.8 → Status 'pending' (Warteschleife)
  - Zu viele Links im Beitrag → Moderations-Flag
  - Erste 3 Beiträge eines neuen Users → Optional: Moderations-Pflicht

MANUELLE MODERATION (Admin-UI):
  Admin → admin/forum-moderation.php
    ↓
  Aktionen auf Thread:
    [offen] [geschlossen] [archiviert] [gelöscht] [Sticky] [Ankündigung]

  Aktionen auf Post:
    [sichtbar] [versteckt] [gelöscht]
    [bearbeiten] [In anderen Thread verschieben]

MELDEN-FUNKTION (User):
  Post → "Melden" Button
    ↓
  Grund auswählen: Spam | Beleidigung | Falsche Informationen | Sonstiges
    ↓
  DB-INSERT: cms_forum_reports
    ↓
  Admin-Notification: "Neuer gemeldeter Beitrag"
```

---

## Workflow 4: Suche

```
/forum/?q=stripe+integration
    ↓
Volltext-Suche in:
  - cms_forum_threads.title
  - cms_forum_threads.content (Erster Post)
  - cms_forum_posts.content

MySQL FULLTEXT INDEX nutzen:
ALTER TABLE cms_forum_threads ADD FULLTEXT ft_search (title, content);
ALTER TABLE cms_forum_posts ADD FULLTEXT ft_search (content);

Ergebnis-Ranking:
  - Exakter Treffer im Titel > Treffer im Inhalt
  - Neuere, aktive Threads bevorzugt
  - Gelöste Threads als solche markieren
```

---

## Datenbank-Schema

```sql
-- Kategorien
CREATE TABLE cms_forum_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    parent_id   INT UNSIGNED DEFAULT 0,
    sort_order  INT DEFAULT 0,
    is_restricted TINYINT(1) DEFAULT 0,  -- 1 = nur für Member
    thread_count INT DEFAULT 0,
    post_count   INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Threads (Themen)
CREATE TABLE cms_forum_threads (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    title         VARCHAR(255) NOT NULL,
    content       LONGTEXT NOT NULL,
    status        ENUM('open','closed','archived','pending') DEFAULT 'open',
    is_sticky     TINYINT(1) DEFAULT 0,
    is_solved     TINYINT(1) DEFAULT 0,
    view_count    INT DEFAULT 0,
    reply_count   INT DEFAULT 0,
    last_activity DATETIME DEFAULT NOW(),
    created_at    DATETIME DEFAULT NOW(),
    FULLTEXT ft_search (title, content),
    INDEX idx_cat    (category_id),
    INDEX idx_user   (user_id),
    INDEX idx_status (status),
    INDEX idx_last   (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Antworten
CREATE TABLE cms_forum_posts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id  INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    content    LONGTEXT NOT NULL,
    is_solution TINYINT(1) DEFAULT 0,  -- Als Lösung markiert
    is_visible  TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
    FULLTEXT ft_search (content),
    INDEX idx_thread (thread_id),
    INDEX idx_user   (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Abonnements (Benachrichtigungen)
CREATE TABLE cms_forum_subscriptions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    thread_id  INT UNSIGNED,     -- NULL = Kategorie-Abo
    category_id INT UNSIGNED,
    created_at DATETIME DEFAULT NOW(),
    UNIQUE KEY uq_sub (user_id, thread_id),
    UNIQUE KEY uq_cat_sub (user_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Meldungen (Reports)
CREATE TABLE cms_forum_reports (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    reason     ENUM('spam','harassment','misinformation','other'),
    detail     TEXT,
    status     ENUM('open','resolved') DEFAULT 'open',
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Plugin-Struktur

```
plugins/cms-forum/
├── cms-forum.php
├── includes/
│   ├── class-install.php        ← DB-Tabellen
│   ├── class-categories.php     ← Kategorie-CRUD
│   ├── class-threads.php        ← Thread-CRUD
│   ├── class-posts.php          ← Post-CRUD + Moderation
│   ├── class-subscriptions.php  ← Benachrichtigungen
│   ├── class-search.php         ← Fulltext-Suche
│   └── class-admin.php          ← Admin-Seiten
├── admin/
│   ├── categories.php
│   ├── threads.php
│   └── moderation.php
├── templates/
│   ├── archive.php              ← Kategorien-Übersicht
│   ├── category.php             ← Thread-Liste einer Kategorie
│   ├── thread.php               ← Thread-Ansicht + Antworten
│   └── new-thread.php           ← Formular für neues Thema
└── assets/
    ├── css/forum.css            ← Prefix: cms-forum-
    └── js/forum.js
```

---

## Member-Dashboard-Integration

```php
// Thread-Tab im Member-Dashboard registrieren:
\CMS\Hooks::addFilter('member_dashboard_tabs', function(array $tabs) {
    $tabs['forum'] = [
        'label'    => 'Forum',
        'icon'     => 'dashicons-format-chat',
        'callback' => [CmsForumDashboard::class, 'render'],
        'priority' => 70,
    ];
    return $tabs;
});

// Dashboard zeigt:
// - Meine Themen (5 neueste)
// - Ungelesen: Antworten auf meine Themen
// - Link: "Alle Themen ansehen" → /forum/
```

---

## Sicherheits-Anforderungen

```php
// 1. HTML-Sanitierung im Inhalt:
$content = strip_tags($raw_content, '<p><br><strong><em><ul><ol><li><code><pre><a>');
// KEINE iframe, script, style-Tags erlaubt!

// 2. Links in Posts: nofollow + target blank:
$content = preg_replace(
    '/<a\s+href/i',
    '<a rel="nofollow noopener" target="_blank" href',
    $content
);

// 3. Bearbeiten-Zeitlimit:
if (time() - strtotime($post['created_at']) > 1800) { // 30 Minuten
    // Bearbeitung nur noch durch Moderator
}
```

---

## Referenzen

- [NEW-PLUGIN-CONCEPTS.md](../feature/NEW-PLUGIN-CONCEPTS.md) – Konzept-Übersicht
- [PLUGIN-DEVELOPMENT-WORKFLOW.md](PLUGIN-DEVELOPMENT-WORKFLOW.md) – Entwicklungsworkflow
- [SECURITY-HARDENING-WORKFLOW.md](SECURITY-HARDENING-WORKFLOW.md) – XSS-Schutz im User-Content
