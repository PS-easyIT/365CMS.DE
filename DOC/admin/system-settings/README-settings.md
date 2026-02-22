# Allgemeine Einstellungen


Zentrale Konfigurationsseite des 365CMS – alle globalen Parameter werden hier verwaltet.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Allgemein](#2-allgemein)
3. [Schreiben & Lesen](#3-schreiben--lesen)
4. [Medien](#4-medien)
5. [Permalinks](#5-permalinks)
6. [E-Mail-Einstellungen](#6-e-mail-einstellungen)
7. [Zahlungsinformationen](#7-zahlungsinformationen)
8. [API-Schlüssel](#8-api-schlüssel)
9. [Technische Details](#9-technische-details)

---

## 2. Allgemein

| Einstellung | Key | Beschreibung |
|---|---|---|
| **Seitentitel** | `site_title` | Name der Website, erscheint in `<title>` |
| **Untertitel/Tagline** | `site_tagline` | Kurzbeschreibung (SEO-relevant) |
| **Website-URL** | `site_url` | Vollständige URL inkl. Protokoll |
| **Admin-E-Mail** | `admin_email` | Empfänger für System-Benachrichtigungen |
| **Sprache** | `site_language` | Website-Sprache (z.B. `de_DE`, `en_US`) |
| **Zeitzone** | `timezone` | Zeitzone (z.B. `Europe/Berlin`) |
| **Datumsformat** | `date_format` | PHP-Datumsformat (z.B. `d.m.Y`) |
| **Zeitformat** | `time_format` | PHP-Zeitformat (z.B. `H:i`) |

---

## 3. Schreiben & Lesen

| Einstellung | Key | Beschreibung |
|---|---|---|
| **Standard-Kategorie** | `default_category` | Kategorie für neue Beiträge ohne Zuweisung |
| **Startseite** | `front_page_type` | `landing_page`, `static_page` oder `blog` |
| **Statische Startseite** | `front_page_id` | Seiten-ID wenn `static_page` gewählt |
| **Blog-Seite** | `blog_page_id` | Seiten-ID für den Blog-Archiv-Einstieg |
| **Beiträge pro Seite** | `posts_per_page` | Anzahl in Listenansichten (Standard: 10) |
| **Volltext in RSS** | `rss_full_content` | Volltext oder nur Auszug im RSS-Feed |

---

## 4. Medien

| Einstellung | Key | Standardwert |
|---|---|---|
| **Thumbnail-Größe** | `thumb_width` / `thumb_height` | 150 × 150 |
| **Mittlere Größe** | `medium_width` / `medium_height` | 300 × 300 |
| **Große Größe** | `large_width` / `large_height` | 1024 × 1024 |
| **Max. Upload-Größe** | `max_upload_size` | 10 MB |
| **WebP automatisch** | `auto_webp` | `false` |
| **EXIF entfernen** | `strip_exif` | `true` (DSGVO) |

---

## 5. Permalinks

URL-Struktur festlegen:

| Option | Muster | Beispiel |
|---|---|---|
| **Standard** | `/?p=123` | Keine schönen URLs |
| **Datum** | `/YYYY/MM/DD/slug/` | `/2026/02/21/artikel/` |
| **Monatsarchiv** | `/YYYY/MM/slug/` | `/2026/02/artikel/` |
| **Kategorie** | `/kategorie/slug/` | `/news/artikel/` |
| **Beitragstitel** | `/slug/` | `/artikel-name/` (empfohlen) |
| **Individuell** | Frei konfigurierbar | Benutzerdefiniert |

**Basis-Pfade:**
- Kategorien-Basis: `/kategorie/` (änderbar)
- Tag-Basis: `/tag/` (änderbar)

---

## 6. E-Mail-Einstellungen

| Einstellung | Key | Beschreibung |
|---|---|---|
| **Absender-Name** | `mail_from_name` | Name für ausgehende E-Mails |
| **Absender-Adresse** | `mail_from_email` | Absender-E-Mail-Adresse |
| **SMTP-Host** | `smtp_host` | SMTP-Server (z.B. `smtp.strato.de`) |
| **SMTP-Port** | `smtp_port` | Port (25, 465, 587) |
| **SMTP-Verschlüsselung** | `smtp_encryption` | `none`, `ssl`, `tls` |
| **SMTP-Benutzer** | `smtp_user` | Benutzername für SMTP-Auth |
| **SMTP-Passwort** | `smtp_password` | Passwort (AES-256 verschlüsselt gespeichert) |
| **Test-E-Mail senden** | — | Button zum Testen der SMTP-Konfiguration |

---

## 7. Zahlungsinformationen

Angezeigt auf der Member-Subscription-Seite:

| Einstellung | Key | Beschreibung |
|---|---|---|
| **Bankdaten** | `payment_info_bank` | IBAN, BIC, Kontoinhaber (Freetext) |
| **PayPal** | `payment_info_paypal` | PayPal-Adresse oder Link |
| **Hinweis** | `payment_info_note` | Zusätzliche Zahlungshinweise |

---

## 8. API-Schlüssel

| Dienst | Key | Beschreibung |
|---|---|---|
| **Google Maps** | `google_maps_api_key` | Für Karten-Embeds |
| **reCAPTCHA v3** | `recaptcha_site_key` / `recaptcha_secret_key` | Anti-Spam für Formulare |
| **GitHub Token** | `github_token` | Für Update-Checks via GitHub API |

---

## 9. Technische Details

**Speicherung:** Alle Einstellungen in `cms_settings` Tabelle als Key-Value-Paare.

```php
// Einstellung lesen
$title = get_cms_option('site_title', 'Mein CMS');

// Einstellung schreiben
update_cms_option('site_title', $newTitle);

// Mehrere auf einmal
$settings = get_cms_options([
    'site_title', 'site_tagline', 'admin_email'
]);
```

**Hooks:**
```php
do_action('cms_settings_saved', $savedSettings);
add_filter('cms_option_site_title', 'my_title_modifier');
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
