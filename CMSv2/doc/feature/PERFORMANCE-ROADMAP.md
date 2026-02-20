# 365CMS – Performance-Optimierung Roadmap

**Bereich:** Caching, Optimierung, Skalierung, Core Web Vitals  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## Performance-Zielwerte (Core Web Vitals)

| Metrik | Ziel | Messmethode |
|---|---|---|
| LCP (Largest Contentful Paint) | < 2.5s | Google PageSpeed Insights |
| FID / INP (Interaction to Next Paint) | < 200ms | CrUX |
| CLS (Cumulative Layout Shift) | < 0.1 | Lab + Field |
| Time to First Byte (TTFB) | < 800ms | Synthetics |
| Total Page Size | < 300KB (ohne Bilder) | Bundle-Analyzer |
| JavaScript Initial Bundle | < 50KB gzip | Webpack Bundle Analyzer |

---

## 1. Caching-System

### 🔴 P-01 · Page-Cache
| Stufe | Feature |
|---|---|
| Stufe 1 | Statischer HTML-Page-Cache (Datei-basiert) |
| Stufe 2 | Cache-Invalidierung bei Content-Änderungen (präzise, nicht alles) |
| Stufe 3 | Cache-Varianten (eingeloggte vs. ausgeloggte Nutzer) |
| Stufe 4 | Cookie-basierte Cache-Ausschlüsse |
| Stufe 5 | Nginx/Apache-FastCGI-Cache-Integration |
| Stufe 6 | Redis/Memcached als Page-Cache-Backend |
| Stufe 7 | Cache-Preloading bei Deployment |
| Stufe 8 | Edge-Caching-Header (Cloudflare/Fastly-Direktiven) |

---

### 🟠 P-02 · Object-Cache
| Stufe | Feature |
|---|---|
| Stufe 1 | Datenbank-Query-Cache (häufige Queries im Speicher) |
| Stufe 2 | PHP-Object-Cache (serialisierte PHP-Objekte) |
| Stufe 3 | Redis-Integration (Produktionsempfehlung) |
| Stufe 4 | APCu-Integration (Single-Server-Fallback) |
| Stufe 5 | Cache-Gruppen (zusammengehörige Einträge gemeinsam invalidieren) |
| Stufe 6 | Cache-Statistiken (Trefferrate, Speicherbelegung) |

---

## 2. Asset-Optimierung

### 🔴 P-03 · CSS/JS-Optimierung
| Stufe | Feature |
|---|---|
| Stufe 1 | CSS-Minifizierung (Whitespace, Kommentare entfernen) |
| Stufe 2 | JavaScript-Minifizierung und Tree-Shaking |
| Stufe 3 | CSS/JS-Kombinieren (weniger HTTP-Requests) |
| Stufe 4 | Critical-CSS-Extraktion (Above-the-fold CSS inline) |
| Stufe 5 | CSS-Deferral (nicht-kritisches CSS asynchron laden) |
| Stufe 6 | JavaScript-Deferral (non-blocking JS-Loading) |
| Stufe 7 | Vite-basierter Build-Prozess für Plugins und Themes |
| Stufe 8 | Code-Splitting (Bundles nach Route aufteilen) |
| Stufe 9 | HTTP/2-Push für kritische Assets |

---

### 🟠 P-04 · Bild-Performance
| Stufe | Feature |
|---|---|
| Stufe 1 | Automatische WebP/AVIF-Konvertierung (s. Media-Roadmap) |
| Stufe 2 | Responsive Images (`srcset`, `sizes`) |
| Stufe 3 | Lazy Loading für alle Off-Screen-Bilder |
| Stufe 4 | Blur-Up-Technik (LQIP → Full-Resolution) |
| Stufe 5 | Dimensionen im HTML (`width`/`height` setzen gegen CLS) |
| Stufe 6 | Dekodierungs-Hint (`decoding="async"`) |
| Stufe 7 | Fetchpriority für LCP-Bilder (`fetchpriority="high"`) |

---

### 🟡 P-05 · Font-Optimierung
| Stufe | Feature |
|---|---|
| Stufe 1 | Lokaler Font-Hosting (kein CDN-Request bei Google Fonts) |
| Stufe 2 | Font-Subsetting (nur benötigte Zeichen laden) |
| Stufe 3 | FontFace-Observer mit Fallback (verhindert FOUT) |
| Stufe 4 | Variable Fonts (ein Font für alle Gewichte) |
| Stufe 5 | `font-display: optional` für nicht-kritische Fonts |

---

## 3. Datenbank-Performance

### 🔴 P-06 · Datenbank-Optimierung
| Stufe | Feature |
|---|---|
| Stufe 1 | Index-Analyse und automatische Index-Vorschläge |
| Stufe 2 | Slow-Query-Log-Analyse im Admin |
| Stufe 3 | N+1-Query-Detektor (Development-Mode) |
| Stufe 4 | Query-Explain-Analyse im Admin |
| Stufe 5 | Tabellen-Optimierung (OPTIMIZE TABLE automatisch) |
| Stufe 6 | Connection-Pooling (PgBouncer/ProxySQL für High-Load) |
| Stufe 7 | Read-Replica-Support (Schreibanfragen → Primary, Lesen → Replica) |
| Stufe 8 | Database-Sharding-Vorbereitung (für extreme Skalierung) |

---

## 4. PHP-Performance

### 🟠 P-07 · PHP-Optimierung
| Stufe | Feature |
|---|---|
| Stufe 1 | OPcache-Konfigurationsempfehlung (Dokumentation) |
| Stufe 2 | Preloading (PHP 8.0, kritische Klassen in OPcache laden) |
| Stufe 3 | Fibers/Async (PHP 8.1, für parallele I/O-Operationen) |
| Stufe 4 | JIT-Compiler-Nutzung (PHP 8.0, numerische Operationen) |
| Stufe 5 | Xhprof/SPX-Profiling-Integration (Dev-Mode) |
| Stufe 6 | Memory-Profiling und Memory-Leak-Detektor |

---

## 5. Infrastruktur-Skalierung

### 🟡 P-08 · Horizontale Skalierung
| Stufe | Feature |
|---|---|
| Stufe 1 | Session-Storage via Redis (Shared zwischen Servern) |
| Stufe 2 | File-Storage-Abstraktion (SharedFS oder Objekt-Storage) |
| Stufe 3 | Distributed Cache (Redis-Cluster) |
| Stufe 4 | Load-Balancer-Ready (Sticky-Sessions vermeiden) |
| Stufe 5 | Docker-Compose-Setup für lokale Entwicklung |
| Stufe 6 | Kubernetes-Helm-Chart für Produktions-Deployment |

---

### 🟡 P-09 · Performance-Monitoring
| Stufe | Feature |
|---|---|
| Stufe 1 | PHP-Execution-Time pro Request (Dev-Mode-Anzeige) |
| Stufe 2 | Datenbank-Query-Counter pro Seite |
| Stufe 3 | Memory-Usage-Anzeige im Admin-Footer |
| Stufe 4 | Real-User-Monitoring (Core-Web-Vitals aus dem Browser) |
| Stufe 5 | Synthetic Monitoring (automatische Messungen per Cron) |
| Stufe 6 | Grafana/Prometheus-Integration für Infrastruktur-Metriken |
| Stufe 7 | Alerting bei Performance-Regression (über Schwellenwert) |

---

## 6. Search Engine Optimization (Performance-Aspekte)

### 🟠 P-10 · SEO-Performance-Features
| Stufe | Feature |
|---|---|
| Stufe 1 | XML-Sitemap (automatisch generiert, < 50ms Generierung) |
| Stufe 2 | robots.txt-Editor |
| Stufe 3 | Canonicals (automatisch + manuelle Überschreibung) |
| Stufe 4 | Strukturierte Daten (Schema.org, JSON-LD für alle CPTs) |
| Stufe 5 | Open-Graph-Tags (Facebook, LinkedIn) |
| Stufe 6 | Twitter-Cards |
| Stufe 7 | Breadcrumb-Schema (automatisch aus Hierarchie) |
| Stufe 8 | Hreflang für mehrsprachige Sites |
| Stufe 9 | Core-Web-Vitals-Bericht im Admin (aus Google API) |
| Stufe 10 | Internal-Link-Analyse (verwaiste Seiten finden) |

---

*Letzte Aktualisierung: 19. Februar 2026*
