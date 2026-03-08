# Symfony Mime

## Zweck

`CMS/assets/mime/` erzeugt die eigentlichen MIME-Nachrichten für den Mailversand in 365CMS.

## Aktive Verwendung im CMS

- `Symfony\Component\Mime\Email` wird in `CMS/core/Services/MailService.php` direkt instanziiert.
- HTML- und Plain-Text-Teile werden parallel aufgebaut.
- Dateianhänge werden über `attachFromPath()` eingebunden.
- Queue-Jobs aus `CMS/core/Services/MailQueueService.php` landen am Ende ebenfalls in denselben `Email`-Objekten.

## Lokale Anpassungen

Die lokale Integration ist so angepasst, dass sie auch ohne vollständige Composer-Abhängigkeiten im CMS funktioniert:

- `Address.php` validiert E-Mail-Adressen mit `filter_var()` weiter, falls `egulias/email-validator` nicht verfügbar ist.
- `Encoder/IdnAddressEncoder.php` fällt sauber zurück, wenn `idn_to_ascii()` auf dem Zielsystem nicht vorhanden ist.

## Relevante Dateien

- `CMS/assets/mime/Address.php`
- `CMS/assets/mime/Encoder/IdnAddressEncoder.php`
- `CMS/core/Services/MailService.php`
- `CMS/core/Services/MailQueueService.php`
