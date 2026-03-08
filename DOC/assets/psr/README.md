# PSR-Minimalkompatibilität

## Zweck

`CMS/assets/psr/` enthält eine kleine lokale Kompatibilitätsschicht für Namespaces, die der lokale Symfony-Mailer-SMTP-Pfad benötigt.

## Enthalten

- `Psr\Log\LoggerInterface`
- `Psr\Log\NullLogger`
- `Psr\EventDispatcher\EventDispatcherInterface`

## Warum lokal?

365CMS betreibt die Asset-Bundles direkt aus `CMS/assets/` ohne vorausgesetzte Composer-Installation im Runtime-System. Für den SMTP-Pfad von Symfony Mailer reicht eine minimale lokale Bereitstellung der benötigten PSR-Typen.

## Relevante Dateien

- `CMS/assets/psr/Log/LoggerInterface.php`
- `CMS/assets/psr/Log/NullLogger.php`
- `CMS/assets/psr/EventDispatcher/EventDispatcherInterface.php`
- `CMS/assets/autoload.php`
