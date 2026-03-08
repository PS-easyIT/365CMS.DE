# Symfony Mailer

## Kurzbeschreibung

`Symfony Mailer` bildet in 365CMS die lokale Versandschicht für SMTP, Queue-Worker und OAuth-/XOAUTH2-nahe Mail-Flows.

## Quellordner

- `CMS/assets/mailer/`

## Verwendung in 365CMS

- Eingebunden in: `CMS/core/Services/MailService.php`, `CMS/core/Services/MailQueueService.php`, `CMS/cron.php`
- Funktion: Versand von E-Mails über SMTP-Transporte, Retry-/Queue-Verarbeitung und Integrationsbasis für Mail-Provider-Konfigurationen

## Abhängigkeiten

- Benötigt: `mime/`, `psr/`
- Wird benötigt von: `MailService`, `MailQueueService`, Cron-Mailversand

## Website / GitHub

- Website: https://symfony.com/components/Mailer
- GitHub: https://github.com/symfony/mailer

## Stand

- Zuletzt geprüft: 2026-03-08
- Version: lokales Bundle in `CMS/assets/` (8.x-Linie laut Asset-Bestand)
