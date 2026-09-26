# PSR-Interfaces

## Kurzbeschreibung

`CMS/assets/psr/` enthält die offiziellen PHP-FIG-Interfacepakete, damit die gebündelten Libraries ohne Composer-Installation funktionieren.

## Quellordner

- `CMS/assets/psr/Log/` – `psr/log` 3.0.2
- `CMS/assets/psr/EventDispatcher/` – `psr/event-dispatcher` 1.0.0
- `CMS/assets/psr/Container/` – `psr/container` 2.0.2
- `CMS/assets/psr/Clock/` – `psr/clock` 1.0.0

## Verwendung in 365CMS

- Eingebunden in: `CMS/assets/autoload.php`
- Wird benötigt von: `mailer/`, `event-dispatcher/`, `symfony-contracts/` (Service), `type-info/`, `clock/`, `Carbon/`, `ai-platform/`, Teile von `ldaprecord/`

## Website / GitHub

- Website: https://www.php-fig.org/psr/
- GitHub: https://github.com/php-fig

## Stand

- Zuletzt geprüft: 2026-09-26
- Version: vollständige Originalpakete (vorher lokale Minimalimplementierung)