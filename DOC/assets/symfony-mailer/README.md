# Symfony Mailer

## Zweck

`CMS/assets/mailer/` stellt den lokalen SMTP-Transport für 365CMS bereit. Die Komponente wird produktiv von `CMS/core/Services/MailService.php` verwendet.

## Aktive Verwendung im CMS

- SMTP-Versand über `Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport`
- Transport-Erzeugung in `CMS/core/Services/MailService.php`
- Asynchrone Queue-Verarbeitung in `CMS/core/Services/MailQueueService.php`, die fällige Jobs über `MailService` zustellt
- Autoload-Mapping in `CMS/assets/autoload.php`

## Zusammenspiel

`mailer/` arbeitet im CMS nicht allein, sondern zusammen mit:

- `CMS/assets/mime/` für Nachrichtenerzeugung (`Symfony\Component\Mime\Email`)
- `CMS/assets/psr/` für die lokal bereitgestellten Minimal-Interfaces `Psr\Log` und `Psr\EventDispatcher`

## Besonderheiten dieser Integration

- Die Quellen liegen bewusst direkt unter `CMS/assets/`, nicht nur im zentralen `/ASSETS/`-Ablagebereich.
- Für den SMTP-Pfad wird kein globales Composer-Setup vorausgesetzt.
- Der `MailService` behält einen nativen `mail()`-Fallback, wenn kein SMTP-Host konfiguriert ist.
- Für Microsoft 365 wird lokal auch XOAUTH2 über `XOAuth2Authenticator` unterstützt.
- `CMS/cron.php` verarbeitet die Mail-Queue, ohne dass ein externer Queue-Server benötigt wird.
- Retry/Backoff und Queue-Fehlerklassifikation ändern nichts an der Asset-Basis: der eigentliche SMTP-Versand läuft weiterhin vollständig über diese lokale Mailer-Komponente.

## Relevante Dateien

- `CMS/core/Services/MailService.php`
- `CMS/core/Services/MailQueueService.php`
- `CMS/cron.php`
- `CMS/assets/autoload.php`
- `CMS/config/app.php`
