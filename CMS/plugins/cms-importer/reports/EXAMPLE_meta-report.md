# WordPress-Import – Unbekannte Meta-Felder

> **Import-Datei:** `phinitde.WordPress.2026-01-10.xml`
> **Erstellt:** 21.02.2026 (Beispiel-Bericht aus Test-Import)
> **Quelle:** PhinIT.DE (`https://phinit.de`)
> **Anzahl unbekannter Keys:** 8
> **Gesamte Meta-Einträge:** 32+

---

## Hinweis

Die folgenden Meta-Keys aus dem WordPress-Export konnten **nicht automatisch** auf ein CMS-Datenbankfeld gemappt werden. Sie wurden trotzdem in der Tabelle `cms_import_meta` gespeichert und können manuell nachverarbeitet werden.

**Gemappte Keys** (werden NICHT in dieser Liste aufgeführt, da bereits korrekt übertragen):
- `rank_math_title` → `meta_title` in `cms_posts`
- `rank_math_description` → `meta_description` in `cms_posts`
- `_yoast_wpseo_title` → `meta_title` in `cms_posts`
- `_yoast_wpseo_metadesc` → `meta_description` in `cms_posts`
- `_thumbnail_id` → `featured_image_wp_id` (WP-Attachment-ID als Referenz)

---

## Übersicht aller unbekannten Meta-Keys

| # | Meta-Key | Anzahl Vorkommen | Hinweis |
|---|----------|------------------|---------|
| 1 | `_lwpgls_synonyms` | 45 | Lightweight Glossary – Synonyme |
| 2 | `_wpml_word_count` | 45 | WPML Wortanzahl (Technik) |
| 3 | `cmplz_hide_cookiebanner` | 45 | Complianz – Cookie-Banner ausblenden |
| 4 | `litespeed_vpi_list` | 45 | LiteSpeed Cache VPI-Liste (Technik) |
| 5 | `rank_math_analytic_object_id` | 45 | Rank Math Analytics-ID (Technik) |
| 6 | `rank_math_internal_links_processed` | 45 | Rank Math interne Verlinkung (Technik) |
| 7 | `rank_math_seo_score` | 45 | Rank Math SEO-Score (0–100) |
| 8 | `rank_math_focus_keyword` | 12 | Rank Math Fokus-Keyword |

---

## Details der unbekannten Meta-Keys

### `_lwpgls_synonyms`

- **Vorkommen:** 45
- **Hinweis:** Lightweight Glossary – Synonyme; das `lwpgls_term`-Plugin speichert alternative Bezeichnungen hier. **Empfehlung:** Ggf. in ein eigenes Taxonomy/Metafeld im CMS überführen.
- **Beispielwerte:**

  - Post `64100` (**Azure**, Typ: `lwpgls_term`):
    `Microsoft Azure`
  - Post `64124` (**Azure Active Directory (Entra ID)**, Typ: `lwpgls_term`):
    `Entra ID`
  - Post `64129` (**Azure Resource Group**, Typ: `lwpgls_term`):
    `Resource Group`

---

### `_wpml_word_count`

- **Vorkommen:** 45
- **Hinweis:** WPML Mehrsprachigkeit – Wortanzahl je Beitrag. Rein technischer Wert, kein Inhalt. **Empfehlung:** Ignorieren.
- **Beispielwerte:**

  - Post `64100` (**Azure**, Typ: `lwpgls_term`):
    `243`
  - Post `64124` (**Azure Active Directory (Entra ID)**, Typ: `lwpgls_term`):
    `235`
  - Post `64135` (**Azure Virtual Machine**, Typ: `lwpgls_term`):
    `231`

---

### `cmplz_hide_cookiebanner`

- **Vorkommen:** 45
- **Hinweis:** Complianz – Cookie-Consent-Plugin. Steuert ob der Cookie-Banner auf dieser Seite ausgeblendet wird. **Empfehlung:** Im CMS nicht relevant, ignorieren.
- **Beispielwerte:**

  - Post `64100` (**Azure**, Typ: `lwpgls_term`):
    *(leer)*
  - Post `64124` (**Azure Active Directory (Entra ID)**, Typ: `lwpgls_term`):
    *(leer)*

---

### `litespeed_vpi_list`

- **Vorkommen:** 45
- **Hinweis:** LiteSpeed Cache – Technik-Metadaten (kann ignoriert werden). Serialisierter PHP-Array mit gecachten Bild-Referenzen. **Empfehlung:** Ignorieren, rein technisch.
- **Beispielwerte:**

  - Post `64100` (**Azure**, Typ: `lwpgls_term`):
    `a:1:{i:0;s:22:"PhinIT_LOGO2026_V4.jpg";}`
  - Post `64124` (**Azure Active Directory (Entra ID)**, Typ: `lwpgls_term`):
    `a:1:{i:0;s:22:"PhinIT_LOGO2026_V4.jpg";}`

---

### `rank_math_analytic_object_id`

- **Vorkommen:** 45
- **Hinweis:** Rank Math SEO Plugin – interne Analytics-ID (Datenbank-Referenz). **Empfehlung:** Ignorieren, CMS-intern nicht nutzbar.
- **Beispielwerte:**

  - Post `64100` (**Azure**, Typ: `lwpgls_term`):
    `398`
  - Post `64124` (**Azure Active Directory (Entra ID)**, Typ: `lwpgls_term`):
    `399`

---

### `rank_math_internal_links_processed`

- **Vorkommen:** 45
- **Hinweis:** Rank Math SEO Plugin – Flag ob interne Verlinkung analysiert wurde. **Empfehlung:** Ignorieren.
- **Beispielwerte:**

  - Post `64100` (**Azure**, Typ: `lwpgls_term`):
    `1`

---

### `rank_math_seo_score`

- **Vorkommen:** 45
- **Hinweis:** Rank Math SEO-Score (0–100). Nützlich zur Qualitätseinschätzung der Inhalte. **Empfehlung:** Kann manuell genutzt werden, kein CMS-Äquivalent.
- **Beispielwerte:**

  - Post `64100` (**Azure**, Typ: `lwpgls_term`):
    `6`
  - Post `64124` (**Azure Active Directory (Entra ID)**, Typ: `lwpgls_term`):
    `6`
  - Post `64129` (**Azure Resource Group**, Typ: `lwpgls_term`):
    `11`

---

### `rank_math_focus_keyword`

- **Vorkommen:** 12
- **Hinweis:** Rank Math Fokus-Keyword für SEO-Optimierung. **Empfehlung:** Könnte als `meta_title`-Ergänzung genutzt werden oder in `tags` übertragen werden.
- **Beispielwerte:**

  - Post `64100` (**Azure**, Typ: `lwpgls_term`):
    `Azure`

---

## Empfehlungsmatrix

| Meta-Key | Empfehlung | Priorität |
|----------|-----------|-----------|
| `_lwpgls_synonyms` | Manuell als Tags übernehmen oder ignorieren | Mittel |
| `_wpml_word_count` | Ignorieren | Niedrig |
| `cmplz_hide_cookiebanner` | Ignorieren | Niedrig |
| `litespeed_vpi_list` | Ignorieren | Niedrig |
| `rank_math_analytic_object_id` | Ignorieren | Niedrig |
| `rank_math_internal_links_processed` | Ignorieren | Niedrig |
| `rank_math_seo_score` | Optional manuell nutzen | Niedrig |
| `rank_math_focus_keyword` | Ggf. in Tags übertragen | Mittel |

---

*Automatisch generiert vom CMS WordPress Importer v1.0.0*
