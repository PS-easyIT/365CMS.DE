# Cron Library (poliander/cron 3.3.1)

Diese Runtime-Library wird in `CMS/assets/cron/` gebuendelt und ueber `CMS/assets/autoload.php` per PSR-4 (`Poliander\Cron\`) geladen.

## Zweck in 365CMS

- Parser/Validator fuer Cron-Expressions (5 Segmente)
- Berechnung faelliger Zeitpunkte fuer den stuedlichen Core-Hook
- Berechnung des naechsten geplanten Laufs fuer Diagnose/Status

## Integration

- Adapter: `CMS/core/Services/CronExpressionAdapter.php`
- Scheduler-Nutzung: `CMS/core/Services/CronRunnerService.php`
- Admin-Status: `CMS/admin/modules/system/SystemInfoModule.php`

## Fallback-Verhalten

Wenn die externe Klasse nicht verfuegbar ist oder eine Expression ungueltig ist, faellt 365CMS auf die bisherige Intervall-Logik (>= 3600 Sekunden) zurueck.

## Quelle

- Upstream: [poliander/cron](https://github.com/poliander/cron)
- Version: `3.3.1`
- Ursprungsablage im Repo: `ASSETS/cron-3.3.1`
