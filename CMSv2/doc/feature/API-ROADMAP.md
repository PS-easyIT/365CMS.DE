# 365CMS – API & Integrations-Roadmap

**Bereich:** REST-API, GraphQL, Webhooks, Headless, Externe Integrationen  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## 1. REST-API

### 🔴 A-01 · REST-API v2 Fundament
| Stufe | Feature |
|---|---|
| Stufe 1 | Versionierte API-Basis (`/api/v1/`, `/api/v2/`) |
| Stufe 2 | CRUD für: posts, pages, users, media, experts, companies, events |
| Stufe 3 | Pagination (cursor-based für Performance, limit/offset als Alternative) |
| Stufe 4 | Feldauswahl (`?fields=id,name,email` – GraphQL-light) |
| Stufe 5 | Sorting und Filtering (`?sort=name&filter[status]=active`) |
| Stufe 6 | Bulk-Operations (PATCH /posts mit Array von Operationen) |
| Stufe 7 | Conditional-Requests (ETag / If-Modified-Since für Caching) |
| Stufe 8 | Content-Negotiation (JSON, JSON-LD, XML) |

**Response-Envelope-Standard:**
```json
{
  "data": { "id": 1, "name": "Max Muster" },
  "meta": { "total": 250, "page": 1, "per_page": 20 },
  "links": { "self": "...", "next": "...", "prev": "..." }
}
```

---

### 🔴 A-02 · API-Authentifizierung
| Stufe | Feature |
|---|---|
| Stufe 1 | API-Keys (gerätespezifische, widerrufbare Keys) |
| Stufe 2 | JWT Bearer-Token (stateless, für Headless-Applikationen) |
| Stufe 3 | OAuth 2.0 Authorization Code Flow (für Drittanbieter-Apps) |
| Stufe 4 | OAuth 2.0 Client Credentials (Machine-to-Machine) |
| Stufe 5 | Scopes (api:read, api:write, api:admin) |
| Stufe 6 | API-Key-Rotation und Gültigkeitsdauer |
| Stufe 7 | IP-Whitelist pro API-Key |

---

### 🟠 A-03 · API-Dokumentation
| Stufe | Feature |
|---|---|
| Stufe 1 | OpenAPI 3.1 Spec (auto-generiert aus Code) |
| Stufe 2 | Swagger-UI embedded in Admin (`/admin/api-docs`) |
| Stufe 3 | Postman-Collection-Export |
| Stufe 4 | Code-Beispiele in PHP, JS, Python, cURL |
| Stufe 5 | API-Changelog (welche Endpoints änderten sich wann) |
| Stufe 6 | API-Playground (Requests direkt aus Doku testen) |

---

### 🟠 A-04 · API-Monitoring & Analytics
| Stufe | Feature |
|---|---|
| Stufe 1 | Request-Log (Datum, Endpoint, Status-Code, Latenz) |
| Stufe 2 | Fehler-Rate pro Endpoint und API-Key |
| Stufe 3 | Latenz-Histogramm (p50, p95, p99) |
| Stufe 4 | Top-Endpoints nach Volumen |
| Stufe 5 | Quota-Management (monatliche Request-Limits pro API-Key) |
| Stufe 6 | Anomalie-Erkennung (ungewöhnliche Request-Muster) |

---

## 2. GraphQL

### 🟡 A-05 · GraphQL-Endpoint
| Stufe | Feature |
|---|---|
| Stufe 1 | Schema-Definition (Types für alle CMS-Entitäten) |
| Stufe 2 | Query-Resolver (Lesen mit Beziehungen) |
| Stufe 3 | Mutation-Resolver (Schreiben, Aktualisieren, Löschen) |
| Stufe 4 | DataLoader (Batch-Fetching gegen N+1) |
| Stufe 5 | Query-Complexity-Limit (Schutz vor teuren Queries) |
| Stufe 6 | Persisted Queries (Hash-basierte Queries für Production) |
| Stufe 7 | Subscriptions via WebSocket (Echtzeit-Updates) |
| Stufe 8 | GraphQL-Playground im Admin |
| Stufe 9 | Schema-Introspection (deaktivierbar für Production) |

---

## 3. Webhooks

### 🟠 A-06 · Outgoing Webhooks
| Stufe | Feature |
|---|---|
| Stufe 1 | Webhook-Endpoints im Admin verwalten (URL, Secret) |
| Stufe 2 | Event-Subscription (Expert-Created, Post-Published, etc.) |
| Stufe 3 | Payload-Vorschau (welche Daten werden gesendet) |
| Stufe 4 | HMAC-SHA256 Signatur-Header (`X-CMS365-Signature`) |
| Stufe 5 | Retry-Mechanismus (5 Versuche, exponentielles Backoff) |
| Stufe 6 | Delivery-Log (Status, Response, Latenz) |
| Stufe 7 | Webhook-Pause (temporär deaktivieren ohne Löschen) |
| Stufe 8 | Batch-Events (mehrere Events in einem Request bündeln) |

---

### 🟡 A-07 · Incoming Webhooks
| Stufe | Feature |
|---|---|
| Stufe 1 | Konfigurierbare Empfangs-Endpoints |
| Stufe 2 | Payload-Mapping (externes Format → CMS-Daten) |
| Stufe 3 | Signatur-Verifikation eingehender Webhooks |
| Stufe 4 | Trigger von CMS-Aktionen via Webhook |
| Stufe 5 | Webhook-Queue (eingehende Hooks asynchron verarbeiten) |

---

## 4. Externe Service-Integrationen

### 🟠 A-08 · E-Mail-Dienste
| Dienst | Stufe | Priorität |
|---|---|---|
| SMTP (generisch) | Konfigurationsformular | 🔴 Kritisch |
| Mailgun | API-Integration | 🟠 High |
| SendGrid | API-Integration | 🟠 High |
| Brevo (Sendinblue) | API-Integration | 🟠 High |
| Amazon SES | API-Integration | 🟡 Mittel |
| Postmark | API-Integration | 🟡 Mittel |

**Gemeinsame Features:**
- Bounce/Complaint-Handling (webhooks von Mail-Provider)
- Email-Deliverability-Score im Dashboard
- Template-Synchronisation

---

### 🟠 A-09 · Zahlungs-Gateways
| Dienst | Stufe | Priorität |
|---|---|---|
| Stripe | Full-Integration (Payments, Subscriptions, Connect) | 🔴 Kritisch |
| PayPal | Standard + Subscriptions | 🟠 High |
| SEPA-Lastschrift | via Stripe | 🟠 High |
| Klarna | via Stripe oder direkt | 🟡 Mittel |
| Mollie | Beliebte Alternative in DE/NL | 🟡 Mittel |
| Giropay / Sofortüberweisung | DE-spezifisch | 🟡 Mittel |

---

### 🟡 A-10 · Cloud-Storage
| Dienst | Stufe | Priorität |
|---|---|---|
| Amazon S3 | Plugin-Adapter | 🟡 Mittel |
| Cloudflare R2 | Plugin-Adapter (S3-kompatibel) | 🟡 Mittel |
| IONOS Object Storage | Plugin-Adapter | 🟡 Mittel |
| Hetzner Storage Box | SFTP-Adapter | 🟡 Mittel |
| Backblaze B2 | Plugin-Adapter | 🟢 Low |

**Gemeinsame Features:**
- Automatischer Medien-Upload zu Cloud-Storage
- CDN-URL-Rewriting für Medien
- Kosten-Optimierung (infrequent-access Tiers)

---

### 🟡 A-11 · CRM & Marketing-Automation
| Dienst | Priorität |
|---|---|
| HubSpot (Kontakte, Deals) | 🟡 Mittel |
| Salesforce | 🟢 Low |
| ActiveCampaign | 🟡 Mittel |
| Mailchimp | 🟡 Mittel |
| Pipedrive | 🟢 Low |

---

### 🟡 A-12 · Kommunikations-Dienste
| Dienst | Verwendung | Priorität |
|---|---|---|
| Slack | Admin-Benachrichtigungen, Webhook-Weiterleitung | 🟡 Mittel |
| Microsoft Teams | Wie Slack | 🟡 Mittel |
| Twilio | SMS/WhatsApp-Benachrichtigungen | 🟡 Mittel |
| Telegram Bot | Admin-Alerts | 🟢 Low |
| Discord | Community-Events | 🟢 Low |

---

### 🟡 A-13 · Kalender & Produktivität
| Dienst | Verwendung | Priorität |
|---|---|---|
| Google Calendar | Event-Sync | 🟡 Mittel |
| Microsoft 365 Calendar | Event-Sync | 🟡 Mittel |
| Calendly | Buchungs-Widget-Einbettung | 🟡 Mittel |
| Cal.com | Open-Source-Alternative | 🟢 Low |

---

## 5. Headless & Decoupled

### 🟠 A-14 · Headless-CMS-Mode
| Stufe | Feature |
|---|---|
| Stufe 1 | Full-API-Mode (CMS als reines Backend, kein Frontend) |
| Stufe 2 | Preview-API (Draft-Inhalte für Frontend-Frameworks) |
| Stufe 3 | Revalidation-Webhooks (Next.js/Nuxt ISR-Support) |
| Stufe 4 | Content-Delivery-Network-Optimierung für API-Responses |
| Stufe 5 | SDK-Generator (TypeScript-Types aus API-Schema) |
| Stufe 6 | Next.js Starter-Template (offiziell unterstützt) |
| Stufe 7 | Nuxt 3 / Vue Starter-Template |
| Stufe 8 | SvelteKit Starter-Template |

---

## 6. Import & Export

### 🟠 A-15 · Daten-Import
| Stufe | Feature |
|---|---|
| Stufe 1 | CSV-Import für Experten, Firmen, Nutzer |
| Stufe 2 | JSON-Import für strukturierte Daten |
| Stufe 3 | XML-Import (RSS, Atom) |
| Stufe 4 | Import-Mapping (Spalten manuell zuweisen) |
| Stufe 5 | Duplikat-Erkennung und -Behandlung |
| Stufe 6 | Hintergrund-Import (Batch-Jobs für große Dateien) |
| Stufe 7 | WordPress-XML-Import (Migrationshilfe von WP) |

---

*Letzte Aktualisierung: 19. Februar 2026*
