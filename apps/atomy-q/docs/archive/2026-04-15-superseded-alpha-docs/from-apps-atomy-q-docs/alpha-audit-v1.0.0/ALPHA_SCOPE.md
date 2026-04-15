# ✅ Atomy-Q Alpha Scope Definition

## 🎯 Alpha Goal

> A user can create RFQ → invite vendors → receive quotations → normalize → compare → choose winner
> using real backend, real database, real AI, real API, no mock data.

If this flow works, Alpha is success.

Everything else is optional.

---

# ✅ CORE FLOW (MUST EXIST)

This is the only flow you must guarantee works.

```
Login
 → Create Company / Workspace
 → Create RFQ / Request
 → Add Items / Specs
 → Invite Vendors
 → Receive Quotations
 → Upload / Input quotation data
 → Normalize quotations (AI)
 → Compare quotations
 → Select winner
 → Save result
```

If this works → Alpha ready.

---

# ✅ REQUIRED MODULES (P0)

These must exist before release.

## 1. Authentication

Must have:

* login
* register (optional if manual user creation)
* API auth (token / sanctum / jwt)
* user roles (admin / user minimum)

Minimum acceptable:

```
User
Company
User belongs to Company
```

Not required yet:

* SSO
* OAuth
* MFA

---

## 2. Tenant / Company Separation

Atomy-Q is SaaS → must separate data.

Must have:

* company_id on tables
* RFQ belongs to company
* quotation belongs to company
* user belongs to company

If missing → fix now.

---

## 3. RFQ Module

Must support:

* create RFQ
* add items
* add specs
* save
* edit
* list RFQ

No need yet:

* workflow engine
* approval chain
* versioning

---

## 4. Vendor / Bidder Module

Must support:

* add vendor
* assign vendor to RFQ
* list vendors per RFQ

Optional for alpha:

* email sending
* portal login for vendor

You can input quotation manually in Alpha.

---

## 5. Quotation Intake

Must support:

* input quotation manually OR upload JSON / CSV / form
* store quotation lines
* link to RFQ
* link to vendor

Must exist:

```
quotation
quotation_items
vendor
rfq_items
```

---

## 6. Normalization Engine (AI)

This is the core feature of Atomy-Q.

Alpha must support:

* send quotation data to AI
* normalize units / names / format
* return structured result
* save normalized data

Must exist:

* provider configured
* API key via env
* prompt exists
* response parser exists
* error handling exists

Optional later:

* multi-model
* cost optimization
* retry queue
* streaming

---

## 7. Comparison Engine

Must support:

* compare multiple quotations
* show item vs item
* show price difference
* show winner per item

UI can be simple table.

No need:

* charts
* advanced scoring
* analytics

---

## 8. Award / Selection

Must support:

* mark winner
* save decision
* show result

Optional later:

* approval
* audit trail UI
* export PDF

---

## 9. API must be real

Frontend must call real API.

Not allowed in alpha:

* mock json
* fake data
* hardcoded responses

This is critical.

---

## 10. Frontend must be usable

Next.js must support:

* login
* RFQ list
* RFQ edit
* quotation input
* normalization trigger
* comparison screen

No need for:

* perfect UI
* animation
* design polish

---

## 11. Database must be stable

Must have:

* migrations complete
* no manual table edits
* seed minimal data
* no missing columns

---

## 12. ENV + Config must be real

Must have:

```
.env.example complete
APP_KEY
DB config
AI API key
QUEUE config
CACHE config
```

---

## 13. Error handling must exist

Must not crash.

Must have:

* try/catch in AI calls
* validation
* API error response
* logs

---

## 14. Deployment must work

Alpha must run on real server.

Minimum:

* VPS / cloud / docker / laravel forge / railway / etc
* database running
* queue running
* storage working

Not required yet:

* auto scaling
* CI/CD
* CDN

---

# ❌ NOT REQUIRED FOR ALPHA

Do NOT waste time on these.

* billing
* subscription
* stripe
* multi region
* mobile app
* analytics dashboard
* audit UI
* notification system
* email templates
* vendor portal
* advanced RBAC
* activity timeline
* fancy UI
* perfect design

These kill releases.

---

# ✅ ALPHA SUCCESS CHECKLIST

Alpha ready if:

✔ login works
✔ RFQ create works
✔ quotation saved
✔ normalization works
✔ comparison works
✔ winner selected
✔ data saved
✔ no mock data
✔ deployable

That is enough.

---

# ✅ Recommended Alpha Feature Cut for Atomy-Q

If time short, keep ONLY:

* 1 company
* 1 user role
* manual quotation input
* single AI provider
* simple comparison
* simple UI

