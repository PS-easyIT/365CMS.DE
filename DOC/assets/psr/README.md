# PSR-Minimalkompatibilität

## Kurzbeschreibung

`CMS/assets/psr/` stellt eine kleine lokale PSR-Kompatibilitätsschicht für Logging und Event-Dispatching bereit, damit abhängige Bundles ohne Composer-Installation funktionieren.

## Quellordner

- `CMS/assets/psr/`

## Verwendung in 365CMS

- Eingebunden in: `CMS/assets/autoload.php`
- Funktion: Bereitstellung von `Psr\Log` und `Psr\EventDispatcher` für `mailer/`, `translation/` und Teile von `ldaprecord/`

## Abhängigkeiten

- Benötigt: –
- Wird benötigt von: `mailer/`, `mime/` (über Mailer-Kette), `translation/`, Teile von `ldaprecord/`

## Website / GitHub

- Website: https://www.php-fig.org/psr/
- GitHub: –

## Stand

- Zuletzt geprüft: 2026-03-08
- Version: lokale Minimalimplementierung
