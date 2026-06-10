# Symfony Mailer
> **Stand:** 2026-06-10 | **Version:** 3.3.47 | **Status:** Aktuell

`Symfony Mailer` bildet in 365CMS die lokale Versandschicht für SMTP, Queue-Worker und OAuth-/XOAUTH2-nahe Mail-Flows.

## Quellordner

- `CMS/assets/mailer/`

## Verwendung in 365CMS

- Eingebunden in: `CMS/assets/autoload.php`, `CMS/core/Services/MailService.php`, `CMS/core/Services/MailQueueService.php`, `CMS/cron.php`
- Funktion: Versand von E-Mails über SMTP-Transporte, Retry-/Queue-Verarbeitung und Integrationsbasis für Mail-Provider-Konfigurationen
- Produktivstatus: aktiv; es gibt aktuell **keinen** separaten Legacy-Mailer-Ordner unter `CMS/assets/`

## Abhängigkeiten

- Benötigt: `mime/`, `psr/`
- Wird benötigt von: `MailService`, `MailQueueService`, Cron-Mailversand

## Pflegehinweise

- Wegen RFC-/Provider-Kompatibilität nicht als Eigenbau nachbauen, sondern hinter den bestehenden Mail-Services kapseln
- Falls langfristig abstrahiert werden soll, zuerst Transport-Konfiguration, Queue und Fehlerobjekte im Service-Layer stabilisieren
- Siehe Roadmap: [../../ASSETS_OwnAssets.md](../../ASSETS_OwnAssets.md)

## Website / GitHub

- Website: https://symfony.com/components/Mailer
- GitHub: https://github.com/symfony/mailer
