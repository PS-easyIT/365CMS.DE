# 365CMS – Multi-Site, Multi-Tenant & SaaS Roadmap

**Bereich:** Multi-Tenant-Architektur, SaaS-Betrieb, White-Label, Hosting-Management  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## Architektur-Optionen

| Modell | Beschreibung | Use-Case |
|---|---|---|
| **Single-Tenant** | Eine Installation pro Kunde | Eigenhosting, höchste Isolation |
| **Multi-Site** | Mehrere Sites, eine Installation | Franchise, Konzerntöchter |
| **Multi-Tenant SaaS** | Viele Kunden, eine Code-Basis | SaaS-Geschäftsmodell |
| **White-Label** | Eigenes Branding für Wiederverkäufer | Agenturen, Reseller |

---

## 1. Multi-Site-System

### 🟡 MS-01 · Basis Multi-Site
| Stufe | Feature |
|---|---|
| Stufe 1 | Site-Netzwerk (mehrere Sites unter einer Installation) |
| Stufe 2 | Subdomain-Routing (`site1.domain.de`, `site2.domain.de`) |
| Stufe 3 | Subdirectory-Routing (`domain.de/site1`) |
| Stufe 4 | Custom-Domain pro Site (`site1.de` → Site 1) |
| Stufe 5 | Super-Admin-Rolle (verwaltet alle Sites im Netzwerk) |
| Stufe 6 | Netzwerk-weite Plugin-Aktivierung |
| Stufe 7 | Geteilte Nutzer-Basis oder Site-isolierte Nutzer |
| Stufe 8 | Netzwerk-Dashboard (Übersicht alle Sites, Metriken) |

---

### 🟡 MS-02 · Content-Sharing im Netzwerk
| Stufe | Feature |
|---|---|
| Stufe 1 | Netzwerk-weite Media-Library (geteilte Bilder) |
| Stufe 2 | Cross-Site-Content-Einbettung (Block aus Site B in Site A) |
| Stufe 3 | Content-Syndikation (Artikel auf mehrere Sites verteilen) |
| Stufe 4 | Zentrales SEO-Profil (Schema.org-Daten netzwerkweit) |

---

## 2. Multi-Tenant SaaS

### 🟠 MT-01 · Tenant-Isolation
| Stufe | Feature |
|---|---|
| Stufe 1 | Datenbank-Schema-Trennung (eigene DB pro Tenant) |
| Stufe 2 | Shared-DB mit Row-Level-Security (Tenant-ID-Column) |
| Stufe 3 | Datei-System-Isolation (eigene Upload-Verzeichnisse) |
| Stufe 4 | Cache-Trennung (Tenant-spezifische Cache-Keys) |
| Stufe 5 | Session-Isolation (keine Cross-Tenant-Zugriffe) |
| Stufe 6 | Resource-Limits pro Tenant (Speicher, Uploads, API-Calls) |

---

### 🟠 MT-02 · Tenant-Onboarding
| Stufe | Feature |
|---|---|
| Stufe 1 | Self-Service-Registrierung (neuer Tenant in < 2 Minuten) |
| Stufe 2 | Onboarding-Wizard (Schritt-für-Schritt-Einrichtung) |
| Stufe 3 | Automatische Datenbank-Provisionierung |
| Stufe 4 | Template-Auswahl bei Onboarding (welches Theme/Plugins) |
| Stufe 5 | Demo-Content-Import (sofort sinnvoller Startpunkt) |
| Stufe 6 | Custom-Domain-Setup-Assistent (DNS-Anleitung) |

---

### 🟠 MT-03 · Tenant-Verwaltung (Super-Admin)
| Stufe | Feature |
|---|---|
| Stufe 1 | Alle Tenants auflisten (Name, Domain, Status, Datum) |
| Stufe 2 | Tenant-Details einsehen (Nutzerzahl, Storage, letzter Login) |
| Stufe 3 | Tenant-Impersonation (als Admin in Tenant einloggen) |
| Stufe 4 | Tenant-Suspend/Reaktivieren |
| Stufe 5 | Tenant-Kündigung und Datenlöschung (DSGVO-konform) |
| Stufe 6 | Massen-Update-Management (Updates für alle Tenants ausrollen) |

---

## 3. White-Label

### 🟡 WL-01 · White-Label-Mode
| Stufe | Feature |
|---|---|
| Stufe 1 | CMS-Branding entfernen (kein "365CMS" in UI) |
| Stufe 2 | Eigenes Logo und Farben im Admin |
| Stufe 3 | Custom-Login-Seite (Logo, Hintergrund, URL) |
| Stufe 4 | Eigener Admin-Domain (`admin.meine-agency.de`) |
| Stufe 5 | Branded E-Mails (System-E-Mails mit Custom-Absender) |
| Stufe 6 | API-Endpunkte ohne CMS-Branding-Hinweise |
| Stufe 7 | Eigener Update-Server (keine öffentlichen Versionsnummern sichtbar) |

---

## 4. Betrieb & DevOps

### 🟠 DO-01 · Deployment-Pipeline
| Stufe | Feature |
|---|---|
| Stufe 1 | GitHub Actions Workflow (Test → Build → Deploy) |
| Stufe 2 | Staging-Umgebungs-Konfiguration |
| Stufe 3 | Automatische Migrations auf Deploy |
| Stufe 4 | Zero-Downtime-Deploy (Rolling Updates) |
| Stufe 5 | Feature-Flags für schrittweise Rollouts |
| Stufe 6 | Canary-Deployments (10% Traffic auf neue Version) |
| Stufe 7 | Automatisches Rollback bei kritischem Fehler |

---

### 🟡 DO-02 · Container & Cloud
| Stufe | Feature |
|---|---|
| Stufe 1 | Docker-Image (offiziell gepflegt) |
| Stufe 2 | Docker-Compose für lokale Entwicklung (PHP, MySQL, Redis, Nginx) |
| Stufe 3 | Kubernetes-Manifeste (Deployment, Service, Ingress) |
| Stufe 4 | Helm-Chart für einfaches K8s-Deployment |
| Stufe 5 | Cloud-Run / App Service / Elastic Beanstalk-Unterstützung |
| Stufe 6 | Terraform-Module (Infrastruktur als Code) |

---

### 🟠 DO-03 · Monitoring & Alerting
| Stufe | Feature |
|---|---|
| Stufe 1 | Health-Check-Endpoint (`/api/health`) |
| Stufe 2 | Metriken-Endpoint für Prometheus (`/metrics`) |
| Stufe 3 | Grafana-Dashboard-Template |
| Stufe 4 | Uptime-Monitoring-Integration (Uptime-Kuma, Better Uptime) |
| Stufe 5 | Alerting bei Downtime, hoher Fehlerrate, langsamem Response |
| Stufe 6 | APM-Integration (New Relic, Datadog) |
| Stufe 7 | Error-Tracking (Sentry) |

---

## 5. Lizenzierung & Business-Modell

### 🟡 BM-01 · Lizenz-Management
| Stufe | Feature |
|---|---|
| Stufe 1 | Open-Source-Core (MIT oder GPL) |
| Stufe 2 | Enterprise-Tier mit Lizenz-Key |
| Stufe 3 | Lizenz-Validierung bei Plugin-Aktivierung |
| Stufe 4 | Lizenz-Portal (Kunden verwalten ihre Lizenzen) |
| Stufe 5 | Sitz-basierte Lizenzierung (User-Count) |
| Stufe 6 | Umsatz-basierte Lizenzierung (Revenue-Share) |

---

*Letzte Aktualisierung: 19. Februar 2026*
