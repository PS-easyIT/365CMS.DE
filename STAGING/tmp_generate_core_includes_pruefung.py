import re
from pathlib import Path

base = Path(r'e:\00-WPwork\365CMS.DE')
source = base / 'DOC' / 'audit' / 'BEWERTUNG.md'
text = source.read_text(encoding='utf-8')
start = text.index('## Core & Includes')
end = text.index('## Restliche Bereiche')
section = text[start:end]
rows = []
for line in section.splitlines():
    m = re.match(r"\| `([^`]+)` \| .*? \| .*? \| (\d+) \| (\d+) \| (\d+) \| (\d+) \|$", line)
    if m:
        rows.append((m.group(1), *(int(m.group(i)) for i in range(2, 6))))


def sec_action(path: str, sec: int) -> str:
    p = path.lower()
    if 'contracts/' in p:
        return 'API einfrieren, strikte Typen dokumentieren, Integritäts-Regressionstests ergänzen'
    if p == 'core/version.php':
        return 'Versionsquelle nur readonly halten, Eingaben verweigern, Manipulationspfad per Tests absichern'
    if p == 'core/autoload.php':
        return 'Klassenpfade auf allowlist begrenzen, Path-Traversal hart blockieren, Fehlpfade neutral loggen'
    if p == 'core/bootstrap.php':
        return 'Bootstrap-Schritte fail-closed starten, Konfig-/Pfadfehler ohne Detailleaks behandeln'
    if p == 'core/cachemanager.php':
        return 'Cache-Keys namespace-isolieren, Serialisierung validieren, Dateicache-Pfade härten'
    if p == 'core/container.php':
        return 'Service-Overrides sperren, Factory-Fehler kapseln, nur erwartete Bindings zulassen'
    if p in ('core/api.php', 'core/routing/apirouter.php'):
        return 'Request-Schema validieren, AuthZ je Endpoint erzwingen, Fehler nur als neutrale JSON-Antworten liefern'
    if p in ('core/auth.php', 'core/auth/authmanager.php'):
        return 'Login-/Session-Flows zentral härten, Rate-Limits ergänzen, Redirect-Ziele strikt validieren'
    if 'ldap' in p:
        return 'LDAP-Hosts allowlisten, StartTLS erzwingen, Bind-Fehler ohne Verzeichnisdetails loggen'
    if 'backupcodesmanager' in p:
        return 'Backup-Codes nur gehasht speichern, Einmalnutzung atomar erzwingen, Ausgabe maskieren'
    if 'totpadapter' in p or p.endswith('core/totp.php'):
        return 'Secret-Handling aus Speicher minimieren, Clock-Skew begrenzen, Retry- und Replay-Schutz ergänzen'
    if 'webauthnadapter' in p:
        return 'Challenge origin/rpId strikt prüfen, Counter validieren, Credentials benutzergebunden sperren'
    if p == 'core/database.php':
        return 'Nur Prepared Statements zulassen, Fehlerdetails kapseln, Transaktionen für Schreibpfade standardisieren'
    if p == 'core/debug.php':
        return 'Debug-Ausgabe nur lokal erlauben, sensible Variablen maskieren, Produktionsmodus erzwingen'
    if p == 'core/hooks.php':
        return 'Hook-Namen normalisieren, Callback-Typen prüfen, Plugin-Fehler isoliert ausführen'
    if p == 'core/http/client.php':
        return 'TLS/Host-Prüfung erzwingen, Timeouts+Allowlist setzen, Redirects und Response-Größe begrenzen'
    if p == 'core/json.php':
        return 'JSON-Fehler strikt behandeln, UTF-8 erzwingen, große Payloads begrenzen'
    if p in ('core/logger.php', 'core/auditlogger.php'):
        return 'Log-Inhalte scrubben, Pfadrechte härten, Log-Injection per Normalisierung verhindern'
    if 'plugindashboardregistry' in p:
        return 'Widget-Registrierung auf Rollen prüfen, Plugin-Output vor Ausgabe escapen'
    if p == 'core/migrationmanager.php':
        return 'Migrationen transaktional ausführen, irreversible Schritte explizit sperren, Dry-Run ergänzen'
    if p == 'core/pagemanager.php':
        return 'Template-/Slug-Eingaben validieren, Statuswechsel autorisieren, Fallbacks fail-closed halten'
    if p == 'core/pluginmanager.php':
        return 'Plugin-Pfade allowlisten, Signatur/Manifest vor Aktivierung prüfen, Hook-Isolation ergänzen'
    if p == 'core/router.php' or '/routing/' in p:
        if 'adminrouter' in p:
            return 'Admin-Routen strikt an AuthZ+CSRF koppeln, interne Fehler auf 403/404 mappen'
        if 'memberrouter' in p:
            return 'Member-Routen an Login+Capability binden, Plugin-Routen nur allowlist-basiert laden'
        if 'publicrouter' in p:
            return 'Pfadparameter normalisieren, Canonicals erzwingen, 404-Fallback ohne Informationsleck liefern'
        if 'themearchiverepository' in p:
            return 'Archivfilter validieren, Query-Parameter typisieren, Ergebnisdaten nur escaped ausgeben'
        if 'themerouter' in p:
            return 'Template-Auflösung auf Theme-Pfade begrenzen, Route-Parameter strikt typisieren'
        return 'Routen-Parameter validieren, Zielhandler allowlisten, Fehlerpfade konsistent schließen'
    if p == 'core/schemamanager.php':
        return 'DDL-Änderungen validieren, destructive Ops absichern, Schema-Drift protokollieren'
    if p == 'core/security.php':
        return 'Token-Binding an Session/User ergänzen, Ablaufzeiten verkürzen, Compare-Funktionen konstantzeitlich halten'
    if 'azuremailtokenprovider' in p:
        return 'Secrets nur aus sicherer Konfiguration lesen, Token nie loggen, Scope/Audience strikt prüfen'
    if 'graphapiservice' in p:
        return 'Graph-Requests signiert+timeoutbegrenzt senden, Token-Refresh kapseln, Antwortdaten validieren'
    if 'mailservice' in p or 'mailqueueservice' in p or 'maillogservice' in p or p == 'includes/functions/mail.php':
        return 'Header/Empfänger validieren, Secrets maskieren, Retry-/Bounce-Fehler sauber protokollieren'
    if 'backupservice' in p:
        return 'Backup-Ziele auf sichere Pfade begrenzen, Archive verschlüsseln, Restore nur mit Integritätsprüfung zulassen'
    if 'commentservice' in p:
        return 'Kommentarinput stärker normalisieren, Statuswechsel autorisieren, Spam-/Rate-Limits ergänzen'
    if 'contentlocalizationservice' in p or 'translationservice' in p or p == 'includes/functions/translation.php':
        return 'Locale-Keys allowlisten, Fallbacks deterministisch halten, Übersetzungsdaten vor Ausgabe escapen'
    if 'cookieconsentservice' in p:
        return 'Consent-Version binden, nur whitelisted Cookie-Kategorien setzen, Manipulationen auditieren'
    if 'corewebvitalsservice' in p or 'trackingservice' in p or 'analyticsservice' in p or 'featureusageservice' in p:
        return 'Messdaten anonymisieren, Consent strikt prüfen, ingestierte Payloads schema-validieren'
    if 'editorjs/requestguard' in p:
        return 'Capability-Prüfung zentral halten, Nonce/CSRF strikt verlangen, Missbrauch auditieren'
    if 'editorjs/sanitizer' in p or 'landing/landingsanitizer' in p or 'purifierservice' in p or p == 'includes/functions/escaping.php':
        return 'Allowlist eng halten, neue HTML-Elemente nur testgestützt freigeben, Escape-Regeln zentral erzwingen'
    if 'editorjs/remotemediaservice' in p:
        return 'Nur erlaubte Remote-Hosts laden, MIME/Content-Length vor Download prüfen, SSRF blockieren'
    if 'editorjs/uploadservice' in p or 'fileuploadservice' in p or 'uploadhandler' in p or 'mediaservice' in p or 'imageprocessor' in p or 'imageservice' in p or 'editorjs/mediaservice' in p or 'editorjs/imagelibraryservice' in p:
        return 'Dateitypen serverseitig prüfen, Dateinamen neutralisieren, Speicherpfade und Bildmetadaten härten'
    if 'editorjsassetservice' in p or 'editorjsrenderer' in p or p in ('core/services/editorjsservice.php', 'core/services/editorservice.php'):
        return 'Editor-Konfig nur aus allowlist bauen, Blockdaten vor Rendern validieren, Fallback-HTML escapen'
    if 'elfinderservice' in p:
        return 'Dateioperationen auf Root-Sandbox begrenzen, gefährliche MIME-Typen sperren, Aktionen auditieren'
    if 'errorreportservice' in p:
        return 'Report-Inhalte redigieren, Attachments typprüfen, Meldekanäle rate-limitieren'
    if 'feedservice' in p:
        return 'Externe Feeds nur per Allowlist laden, XML/HTML sicher parsen, Cache gegen Poisoning schützen'
    if 'indexingservice' in p:
        return 'Nur verifizierte Zielhosts ansprechen, Key-Leaks verhindern, Fehlversuche gedrosselt wiederholen'
    if 'jwtservice' in p:
        return 'Algorithmus fixieren, exp/nbf/aud zwingend prüfen, Secret-Rotation vorbereiten'
    if '/landing/' in p or p.endswith('landingpageservice.php'):
        if 'repository' in p:
            return 'Landing-Payloads schema-validieren, Schreibzugriffe autorisieren, Medienreferenzen verifizieren'
        if 'pluginservice' in p:
            return 'Plugin-Module capability-geprüft einbinden, Fremdoutput escapen'
        return 'Section-Daten allowlist-basiert verarbeiten, HTML nur nach Sanitizing persistieren'
    if 'redirectservice' in p or p == 'includes/functions/redirects-auth.php':
        return 'Redirect-Ziele auf interne/erlaubte Hosts begrenzen, Open-Redirects per Canonical-Check verhindern'
    if 'searchservice' in p:
        return 'Suchparameter typisieren, Query-Limits setzen, Treffer-Snippets nur escaped ausgeben'
    if '/seo/' in p or p.endswith('seoservice.php') or 'seoanalysisservice' in p or 'sitemapservice' in p:
        return 'SEO-Eingaben normalisieren, Head/Schema-Ausgabe strikt escapen, externe Pings begrenzen'
    if 'settingsservice' in p or 'options-runtime.php' in p:
        return 'Schreibbare Settings allowlisten, Typen erzwingen, sensible Werte getrennt speichern'
    if '/sitetable/' in p or p.endswith('sitetableservice.php'):
        if 'repository' in p:
            return 'Filter/Sortierung typisieren, Query-Fragmente zentral erlauben, Ausgabe escapen'
        return 'Template-/Renderer-Eingaben validieren, nur registrierte Layouts zulassen'
    if 'statusservice' in p or 'systemservice' in p:
        return 'Statusdaten entprivilegieren, interne Pfade/Secrets aus Reports entfernen'
    if 'themecustomizer' in p:
        return 'Customizer-Keys allowlisten, CSS-Werte validieren, gespeicherte Styles sanitizen'
    if 'updateservice' in p:
        return 'Update-Quelle verifizieren, Pakete vor Entpacken prüfen, Rollback-Pfad absichern'
    if 'userservice' in p or 'memberservice' in p or p == 'includes/functions/roles.php':
        return 'Rechteentscheidungen zentralisieren, Rollenänderungen auditieren, Mass-Assignment verhindern'
    if 'subscriptionmanager' in p or p == 'includes/subscription-helpers.php':
        return 'Abo-Statuswechsel autorisieren, Preis-/Planwerte serverseitig erzwingen, Maildaten minimieren'
    if 'tableofcontents' in p:
        return 'Überschriften vor Parsing normalisieren, generierte Anchors kollisionsfrei escapen'
    if 'thememanager' in p:
        return 'Theme-Pfade auf Registry begrenzen, Template-Fallbacks härten, Asset-URLs kanonisieren'
    if 'vendorregistry' in p:
        return 'Registrierte Assets auf allowlist halten, Versionsquellen unveränderlich laden'
    if 'wp_error' in p or 'wordpress-compat.php' in p:
        return 'Legacy-Fehlerobjekte ohne HTML ausgeben, Codes/Nachrichten typisiert normalisieren'
    if p == 'includes/functions.php':
        return 'Nur kuratierten Helper-Loader behalten, Seiteneffekte beim Include entfernen, Namenskollisionen verhindern'
    if 'admin-menu.php' in p:
        return 'Menüeinträge capability-geprüft erzeugen, Zielpfade nur intern zulassen'
    return 'Eingaben typisieren, Fehlerpfade fail-closed machen, sensible Daten aus Logs und Responses entfernen'


def speed_action(path: str, spd: int) -> str:
    p = path.lower()
    if 'contracts/' in p or p in ('core/version.php', 'core/json.php', 'includes/functions/escaping.php'):
        return 'Leichtgewichtig halten, nur Regression-Tests statt zusätzlicher Laufzeitlogik ergänzen'
    if p in ('core/bootstrap.php', 'includes/functions.php', 'core/autoload.php'):
        return 'Initialisierung entkoppeln, Lazy-Loads ausbauen, Dateisystemzugriffe nur einmal pro Request ausführen'
    if p == 'core/database.php' or 'repository' in p or 'migrationmanager' in p or 'schemamanager' in p:
        return 'Query-/Schema-Aufrufe bündeln, Indizes prüfen, wiederholte DB-Zugriffe cachen'
    if '/routing/' in p or p in ('core/router.php', 'core/pagemanager.php', 'core/thememanager.php', 'core/pluginmanager.php'):
        return 'Route-/Template-Resolution cachen, FS-Scans reduzieren, Hot Paths vorkompilieren'
    if 'http/client' in p or 'graphapiservice' in p or 'azuremailtokenprovider' in p or 'feedservice' in p or 'indexingservice' in p or 'updateservice' in p:
        return 'Timeouts senken, Antworten cachen, Netzwerkzugriffe bündeln und backoff-gesteuert ausführen'
    if 'cachemanager' in p:
        return 'Hit-Rates messen, Key-Aufbau vereinfachen, Dateicache-Schreibzugriffe reduzieren'
    if 'logger' in p or 'debug.php' in p or 'errorreportservice' in p:
        return 'Sync-I/O reduzieren, Batch-Logging nutzen, Stacktraces nur bei Bedarf erfassen'
    if 'auth' in p or 'totp' in p or 'webauthn' in p or 'jwtservice' in p or 'security.php' in p:
        return 'Krypto-/Session-Arbeit nur bei Bedarf ausführen, Wiederholungen pro Request vermeiden'
    if 'editorjs' in p or 'editorservice' in p:
        return 'Block-Parsing cachen, Asset-Auflösung memoizen, Bild-/Remote-Operationen asynchron vorbereiten'
    if 'upload' in p or 'media' in p or 'image' in p or 'pdfservice' in p or 'backupservice' in p or 'elfinderservice' in p:
        return 'Große Dateien streamen, Thumbnail-/Ableitungen in Jobs auslagern, unnötige FS-Scans streichen'
    if '/landing/' in p or p.endswith('landingpageservice.php'):
        return 'Section-Aufbereitung cachen, Default-Merges reduzieren, Medienlookups bündeln'
    if 'mailservice' in p or 'mailqueueservice' in p or 'maillogservice' in p or p == 'includes/functions/mail.php':
        return 'Versand standardmäßig queue-basiert ausführen, Logs kompakt halten, Retry-Batches bündeln'
    if 'searchservice' in p:
        return 'Suchindex vorhalten, Trefferlimit früh setzen, Snippet-Building lazy ausführen'
    if '/seo/' in p or p.endswith('seoservice.php') or 'analyticsservice' in p or 'trackingservice' in p or 'corewebvitalsservice' in p or 'featureusageservice' in p:
        return 'Berechnungen cachen, Aggregationen vorab bilden, externe Ping-/Analysepfade entkoppeln'
    if 'settingsservice' in p or 'translationservice' in p or 'options-runtime.php' in p or 'translation.php' in p:
        return 'Settings/Locales pro Request memoizen, Fallback-Ketten verkürzen, Cache-Invalidierung gezielt halten'
    if '/sitetable/' in p or p.endswith('sitetableservice.php') or 'tableofcontents' in p:
        return 'Renderer-Ergebnisse cachen, Schleifen vereinfachen, Vorberechnung wiederverwenden'
    if 'statusservice' in p or 'systemservice' in p or 'vendorregistry' in p:
        return 'System-/Registry-Scans cachen, teure Prüfungen in Health-Jobs auslagern'
    if 'subscriptionmanager' in p or 'subscription-helpers.php' in p:
        return 'Plan-/Statuslookups bündeln, Mail-/DB-Arbeit trennen, wiederkehrende Berechnungen cachen'
    return 'Hot Paths messen, wiederholte Service-Aufrufe memoizen, I/O und Schleifen im Request reduzieren'


def bp_action(path: str, bp: int) -> str:
    p = path.lower()
    if 'contracts/' in p:
        return 'Interface klein halten, Rückgabetypen dokumentieren, semantische Versionsregeln festschreiben'
    if p in ('core/container.php', 'core/bootstrap.php', 'core/pluginmanager.php', 'core/thememanager.php', 'core/router.php'):
        return 'Verantwortung weiter trennen, Seiteneffekte minimieren, klare Service-Grenzen dokumentieren'
    if 'database.php' in p or 'repository' in p or 'migrationmanager' in p or 'schemamanager' in p:
        return 'Repository-/Transaktionsmuster vereinheitlichen, Exceptions domänenspezifisch kapseln'
    if 'auth' in p or 'security.php' in p or 'jwtservice' in p:
        return 'Auth-Policies zentralisieren, Ergebnisobjekte statt Arrays verwenden, Security-Tests ergänzen'
    if 'http/client' in p or 'graphapiservice' in p or 'azuremailtokenprovider' in p or 'feedservice' in p:
        return 'Externe Clients über Adapter kapseln, Retry/Timeout-Konfiguration zentralisieren'
    if 'editorjs' in p or 'editorservice' in p:
        return 'DTOs für Blockdaten einführen, Validator/Sanitizer strikt trennen, Renderer entkoppeln'
    if 'upload' in p or 'media' in p or 'image' in p or 'backupservice' in p or 'pdfservice' in p or 'elfinderservice' in p:
        return 'I/O in dedizierte Adapter auslagern, Rückgabewerte typisieren, Pfadlogik zentralisieren'
    if '/landing/' in p or p.endswith('landingpageservice.php'):
        return 'Section-Profile, Sanitizing und Persistenz klar separieren, DTOs/Factories nachziehen'
    if 'mailservice' in p or 'mailqueueservice' in p or 'maillogservice' in p or p == 'includes/functions/mail.php':
        return 'Mail-Payload als Value Object führen, Queue/Transport/Logging sauber entkoppeln'
    if '/seo/' in p or p.endswith('seoservice.php'):
        return 'SEO-Renderer von Datenermittlung trennen, Settings über typed store statt Roharrays lesen'
    if 'settingsservice' in p or 'options-runtime.php' in p:
        return 'Typed Settings-API ausbauen, Defaults zentralisieren, Schreibpfade vereinheitlichen'
    if p == 'includes/functions.php' or p.startswith('includes/functions/') or p == 'includes/subscription-helpers.php':
        return 'Globale Helper schlank halten, Logik in Services verschieben, Rückgabetypen konsequent nachziehen'
    if 'translationservice' in p or 'translation.php' in p or 'wordpress-compat.php' in p or 'wp_error.php' in p:
        return 'Legacy-Helfer dokumentieren, Typisierung erhöhen, neue Aufrufer auf Services umstellen'
    return 'Methoden verkleinern, Typisierung erhöhen, Tests für Randfälle und Fehlpfade ergänzen'


def priority(sec: int, spd: int, bp: int, path: str) -> str:
    worst = min(sec, spd, bp)
    p = path.lower()
    if worst < 70:
        return 'kritisch'
    if worst < 76:
        return 'hoch'
    if worst < 85:
        if any(x in p for x in ['auth', 'security', 'upload', 'media', 'http/client', 'graphapiservice', 'azuremailtokenprovider', 'mailservice', 'backupservice', 'elfinderservice', 'router', 'api.php']):
            return 'hoch'
        return 'mittel'
    return 'niedrig'

lines = [
    '## Core & Includes',
    '### Maßnahmenmatrix',
    '| Datei | Security auf 100% | Speed auf 100% | Best Practice auf 100% | Priorität |',
    '|---|---|---|---|---|',
]
for path, sec, spd, bp, total in rows:
    lines.append(f"| `{path}` | {sec_action(path, sec)} | {speed_action(path, spd)} | {bp_action(path, bp)} | {priority(sec, spd, bp, path)} |")

out = '\n'.join(lines) + '\n'
(base / 'DOC' / 'audit' / 'PRUEFUNG.generated.md').write_text(out, encoding='utf-8')
print(f'rows={len(rows)}')
