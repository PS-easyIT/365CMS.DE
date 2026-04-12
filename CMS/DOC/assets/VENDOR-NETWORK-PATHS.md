# Vendor-/Drittpfad-Netzwerkpfade

> **Stand:** 10.03.2026  
> **Zweck:** Separates Monitoring aller bekannten Drittanbieter-Dateien in `CMS/assets/` und `CMS/vendor/`, die eigene Netzwerklogik mitbringen.  
> **Wichtig:** Diese Pfade sind **nicht** automatisch Teil des zentralen `CMS\Http\Client`-Standards und werden deshalb bewusst getrennt beobachtet.

---

## Warum dieses Dokument existiert

Der Eigencode von 365CMS nutzt für Remote-Zugriffe den zentralen `CMS\Http\Client` mit SSRF- und Timeout-Härtung. Drittanbieter-Code in gebündelten Libraries kann jedoch eigene Netzwerkpfade enthalten, z. B. über:

- `curl_init()`
- `stream_context_create()`
- `fsockopen()`
- direkte URL-Zugriffe via `file_get_contents($url)` / `file_get_contents($uri)`

Diese Pfade werden **nicht still mit dem Core verwechselt**, sondern über eine dedizierte Allowlist und `tests/vendor-network-monitoring/run.php` separat überwacht.

---

## Aktuell überwachte Pfade

| Pfad | Primitive | Zweck / Risiko |
|---|---|---|
| `CMS/assets/webauthn/WebAuthn.php` | `curl_init`, `file_get_contents($url)` | FIDO-Metadaten-Download außerhalb des Core-HTTP-Clients |
| `CMS/assets/simplepiesrc/File.php` | `curl_init`, `file_get_contents($url)`, `fsockopen` | eigener Remote-Fetch in SimplePie |
| `CMS/assets/melbahja-seo/src/Utils/HttpClient.php` | `curl_init` | Bibliotheksinterner SEO-HTTP-Client |
| `CMS/assets/twofactorauth/Providers/Time/HttpTimeProvider.php` | `stream_context_create` | externer Zeitquellenabruf |
| `CMS/assets/twofactorauth/Providers/Qr/BaseHTTPQRCodeProvider.php` | `curl_init` | externer QR-Code-Dienst |
| `CMS/assets/mailer/Transport/Smtp/Stream/SocketStream.php` | `stream_context_create` | SMTP-Transport auf Bundle-Ebene |
| `CMS/assets/elfinder/php/elFinder.class.php` | `curl_init`, `fsockopen`, `get_remote_contents` | generische Remote-Content-Logik in elFinder |
| `CMS/assets/elfinder/php/elFinderVolumeBox.class.php` | `curl_init` | Box-API |
| `CMS/assets/elfinder/php/elFinderVolumeDropbox.class.php` | `curl_init` | Dropbox-API |
| `CMS/assets/elfinder/php/elFinderVolumeDropbox2.class.php` | `curl_init` | Dropbox-v2-API |
| `CMS/assets/elfinder/php/elFinderVolumeFTP.class.php` | `stream_context_create` | FTP-Remote-Streams |
| `CMS/assets/elfinder/php/elFinderVolumeOneDrive.class.php` | `curl_init` | OneDrive-API |
| `CMS/assets/elfinder/php/editors/ZohoOffice/editor.php` | `curl_init` | Zoho-Office-API |
| `CMS/assets/elfinder/php/editors/OnlineConvert/editor.php` | `curl_init` | OnlineConvert-API |
| `CMS/vendor/dompdf/dompdf/dompdf/src/Helpers.php` | `curl_init`, `file_get_contents($uri)` | Remote-Ressourcen im PDF-Kontext |
| `CMS/vendor/dompdf/dompdf/dompdf/src/Options.php` | `stream_context_create` | benutzerdefinierte HTTP-Kontexte für Remote-Ressourcen |

---

## Operative Regel

Bei jedem Bundle-/Vendor-Update gilt:

1. `tests/vendor-network-monitoring/run.php` ausführen.
2. Neue Treffer **nicht einfach ignorieren**.
3. Jeden neuen Drittpfad fachlich bewerten:
   - aktiv genutzt oder nur optionale Upstream-Funktion?
   - externer Dienst, OAuth, Cloud-Storage oder PDF-Remote-Asset?
   - muss der Pfad im Betrieb gesperrt, dokumentiert oder zusätzlich umrahmt werden?
4. Erst dann die Allowlist bewusst aktualisieren.

---

## Verknüpfte Stellen

- `tests/vendor-network-monitoring/allowlist.php`
- `tests/vendor-network-monitoring/run.php`
- `.github/workflows/security-regression.yml`
- `DOC/audit/AUDIT_FACHBEREICHE.md`
- `DOC/audit/NACHARBEIT_AUDIT_ToDo.md`
