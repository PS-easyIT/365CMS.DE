# 365CMS – System-Status

---

## Versions-Info

| Eigenschaft | Wert |
|-------------|------|
| **CMS-Version** | 0.27.1 |
| **PHP-Mindestanforderung** | 8.1 (empfohlen: 8.3) |
| **DB-Version** | MySQL 8.0+ / MariaDB 10.6+ |
| **Letztes Update** | 22. Februar 2026 |
| **Status** | Aktiv in Entwicklung |

---

## Implementierungsstand

### Core-System

| Komponente | Status | Notiz |
|------------|--------|-------|
| Bootstrap | ✅ Vollständig | Singleton, Lifecycle, Error-Handling |
| Database (PDO) | ✅ Vollständig | 22 Tabellen, WP-compat. API |
| Security | ✅ Vollständig | CSRF, XSS, Rate-Limiting, Headers |
| Auth | ✅ Vollständig | Login, Session, Rollen |
| Router | ✅ Vollständig | GET/POST/PUT/DELETE, Plugin-Routen |
| Hooks | ✅ Vollständig | Actions & Filters, Prioritäten |
| PluginManager | ✅ Vollständig | Laden, Aktivieren, Deaktivieren |
| ThemeManager | ✅ Vollständig | Templates, Assets, Settings |
| CacheManager | ✅ Vollständig | Datei- & DB-Cache |
| PageManager | ✅ Vollständig | Seiten, Meta-Tags |
| SubscriptionManager | ✅ Vollständig | Pläne, Abos, Feature-Gating |

### Service-Layer (11/11)

| Service | Status |
|---------|--------|
| AnalyticsService | ✅ Implementiert |
| BackupService | ✅ Implementiert |
| DashboardService | ✅ Implementiert |
| EditorService | ✅ Implementiert |
| LandingPageService | ✅ Implementiert |
| MediaService | ✅ Implementiert |
| MemberService | ✅ Implementiert |
| SEOService | ✅ Implementiert |
| StatusService | ✅ Implementiert |
| ThemeCustomizer | ✅ Implementiert |
| UserService | ✅ Implementiert |

### Admin-Panel (36 Seiten)

| Seite | Datei | Status |
|-------|-------|--------|
| Dashboard | `index.php` | ✅ |
| Benutzer | `users.php` | ✅ |
| Gruppen | `groups.php` | ✅ |
| Seiten | `pages.php` | ✅ |
| Beiträge | `posts.php` | ✅ |
| Medien | `media.php` | ✅ |
| Menüs | `menus.php` | ✅ |
| Landing Page | `landing-page.php` | ✅ |
| Inhaltsverzeichnis | `table-of-contents.php` | ✅ |
| Seitentabellen | `site-tables.php` | ✅ |
| Themes | `themes.php` | ✅ |
| Theme-Editor | `theme-editor.php` | ✅ |
| Theme-Customizer | `theme-customizer.php` | ✅ |
| Theme-Einstellungen | `theme-settings.php` | ✅ |
| Theme-Marktplatz | `theme-marketplace.php` | ✅ |
| Dashboard-Widgets | `design-dashboard-widgets.php` | ✅ |
| Lokale Fonts | `fonts-local.php` | ✅ |
| Plugins | `plugins.php` | ✅ |
| Plugin-Marktplatz | `plugin-marketplace.php` | ✅ |
| SEO | `seo.php` | ✅ |
| Analytics | `analytics.php` | ✅ |
| Performance | `performance.php` | ✅ |
| Updates | `updates.php` | ✅ |
| Abos (Admin) | `subscriptions.php` | ✅ |
| Abo-Einstellungen | `subscription-settings.php` | ✅ |
| Bestellungen | `orders.php` | ✅ |
| System | `system.php` | ✅ |
| Einstellungen | `settings.php` | ✅ |
| Backup | `backup.php` | ✅ |
| Support | `support.php` | ✅ |
| AntiSpam | `antispam.php` | ✅ |
| DSGVO – Cookies | `cookies.php` | ✅ |
| DSGVO – Datenzugang | `data-access.php` | ✅ |
| DSGVO – Datenlöschung | `data-deletion.php` | ✅ |
| Rechtliche Seiten | `legal-sites.php` | ✅ |
| Sicherheits-Audit | `security-audit.php` | ✅ |

### Plugins (5 verfügbar)

| Plugin | Slug | Status |
|--------|------|--------|
| CMS Companies | `cms-companies` | ✅ Verfügbar |
| CMS Events | `cms-events` | ✅ Verfügbar |
| CMS Experts | `cms-experts` | ✅ Verfügbar |
| CMS Job Ads | `cms-jobads` | ✅ Verfügbar |
| CMS Speakers | `cms-speakers` | ✅ Verfügbar |

### Themes (8 verfügbar, 1 aktiv)

| Theme | Slug | Status |
|-------|------|--------|
| 365 Network | `365Network` | ✅ Verfügbar |
| Academy 365 | `academy365` | ✅ Verfügbar |
| Build Base | `buildbase` | ✅ Verfügbar |
| Business | `business` | ✅ Verfügbar |
| LogiLink | `logilink` | ✅ Verfügbar |
| MedCarePro | `medcarepro` | ✅ Verfügbar |
| PersonalFlow | `personalflow` | ✅ Verfügbar |
| TechNexus | `technexus` | ✅ Verfügbar |
| **CMS Default** | `cms-default` | ✅ **AKTIV** |

---

## Bekannte Einschränkungen

| # | Einschränkung | Priorität |
|---|---------------|-----------|
| 1 | Kein integriertes E-Mail-System (SMTP muss manuell konfiguriert werden) | Mittel |
| 2 | Kein Multi-Language-Support in Core | Niedrig |
| 3 | REST-API ohne OAuth2 (nur Session-Auth) | Mittel |

---

## Roadmap (geplante Features)

- [ ] SMTP-E-Mail-Service Integration
- [ ] API-Key-System für REST-API
- [ ] Zwei-Faktor-Authentifizierung (2FA)
- [ ] Multi-Site-Unterstützung
- [ ] GraphQL-API-Endpunkt
- [ ] Automatische Updates für Core
