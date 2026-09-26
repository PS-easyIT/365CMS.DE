# Carbon

> **Stand:** 2026-09-26 | **Version:** 3.11.4 | **Status:** Aktiv

## Kurzbeschreibung

`Carbon` ist die zentrale Datums- und Zeitbibliothek in 365CMS.

## Quellordner

- `CMS/assets/Carbon/src/Carbon/` (PSR-4 `Carbon\`, inkl. `Lang/`)
- `CMS/assets/Carbon/lazy/Carbon/` (wird von `Translator`, `CarbonPeriod` und `MessageFormatterMapper` relativ per `__DIR__/../../lazy` geladen – Struktur nicht verändern)

## Verwendung in 365CMS

- relative Zeitangaben in `time_ago()` (`CMS/includes/functions/redirects-auth.php`)
- Lokalisierung über den Carbon-Translator (Symfony Translation)

## Abhängigkeiten

- `symfony/clock` (`CMS/assets/clock/`), `psr/clock` (`CMS/assets/psr/Clock/`)
- `symfony/translation` (`CMS/assets/translation/`), `symfony-contracts`
- `symfony/polyfill-mbstring` nur ohne ext-mbstring
- Nicht übernommen: `Laravel/`, `PHPStan/`, `Cli/` (Framework-/Tooling-Integration)

## Website / GitHub

- Website: https://carbon.nesbot.com/
- GitHub: https://github.com/briannesbitt/Carbon