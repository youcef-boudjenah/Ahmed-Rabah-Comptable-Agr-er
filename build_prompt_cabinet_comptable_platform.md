# Build Prompt — AI-Powered Practice Management Platform for Accounting (Algeria)


## 1. Product summary

Web platform for accounting (cabinets de comptabilité) in Algeria. Core value is **automation**, not just storage: the platform should calculate cotisations/taxes from data already in the system, auto-draft the official declarations, and only ask a human to review/approve — instead of staff re-typing numbers into forms every month.

2. Tech stack

* *Frontend:* html  Tailwind,  we need advance ui ux i need my client say wow
* **Backend:** php vanila modular: clients / declarations / documents / automation / ai / alerts.
* **DB:** mysql
* **OCR:** Tesseract/docTR/PaddleOCR for raw text → template-based field extractor for fixed-layout gov forms (CNAS, CACOBATPH, G50, G12/G12 Bis) → LLM fallback extraction for anything the template misses.
* **PDF generation/overlay:**`pdf-lib` or `pdfme` — fill blank official form templates with computed values (coordinates per form, see §5).
* **Rule/automation engine:** simple custom event→condition→action table in Postgres (don't over-engineer with a separate rules-engine library for v1).
* **AI/LLM:** openrouter API for doc classification, structured field extraction (JSON schema), and the chat assistant (RAG via pgvector).
* **Jobs/queue:**  — OCR runs, monthly/quarterly auto-draft generation, alert dispatch.
* **Notifications:** (email), local SMS gateway.
*


## 4. Real rate/deadline reference (pulled from actual sample forms — verify against current finance law before going live, these change yearly)

**CNAS (BTP sector example):**

* R22 Régime général: 34.50% of masse salariale
* R98 FNPOS: 0.50%
* R38 OPREBAT: 0.13%
* Filed monthly, or quarterly if firm qualifies for the reduced regime; due in the first 20 days of the month following the period.

**CACOBATPH (BTP only):**

* Congés payés: 12.21% of masse salariale
* Chômage intempéries: 0.75%
* Filed quarterly.

**G50** — covers IRG retenue à la source, IBS acomptes, TVA (9%/19% depending on activity), TLS, droits de timbre. Due first 20 days of month following the period (or quarterly if annual dues < 150,000 DA).

**G12 (IFU prévisionnelle)** — due June 30, rates: 5% production/vente de biens, 12% services, 0.5% auto-entrepreneur. Optional 3-installment payment (50% at filing / 25% by Sept 15 / 25% by Dec 15).

**G12 Bis (IFU définitive)** — due Jan 20 of N+1, reconciles real CA vs forecast, minimum imposition 30,000 DA (10,000 DA for auto-entrepreneur).

Encode all of the above as `CotisationRateTable` + `deadlineRule` rows, not as hardcoded constants — a rate or deadline change should be a data edit, not a deploy.

## 5. Automation engine — this is the part that actually saves staff time

**5.1 Calc pipeline (the core automation loop):**

1. Payroll or sales data lands in `PayrollEntry`/`SalesEntry` — either typed manually once, imported from a payroll export, **or extracted automatically via OCR** from an uploaded payroll register/bank statement (extracted fields map straight onto `PayrollEntry`/`SalesEntry`, no retyping).
2. `AutomationRule` with eventType `PAYROLL_ENTRY_SAVED` fires → background job pulls the right `CotisationRateTable` rows for the client's `secteur` → computes `montant = assiette × taux` for each applicable code (CNAS lines, CACOBATPH lines) → writes a `Declaration` row per type/period with `status = DRAFT_CALCULATED` and `computedFields` filled in.
3. Same pattern for `SALES_ENTRY_SAVED` → IFU (G12/G12 Bis) calc.

**5.2 Auto-fill the actual government form:**

* For each `DeclarationType`, store a `DeclarationPdfTemplate` with the blank official form as background + a `fieldMap` of x/y coordinates (build this once per form by inspecting the real PDF layout).
* A job takes `Declaration.computedFields` + the template → uses `pdf-lib` to draw the values onto the background PDF → saves as `generatedPdfKey`. Output: a print-ready bordereau, not just a number on a dashboard.

**5.3 Review & approval — keep a human in the loop, don't auto-submit blind:**

* `DRAFT_CALCULATED` → collaborateur opens it, sees computed numbers + source data side by side, edits if needed → `APPROVED` → locks the values, stamps `reviewedByUserId`, generates final PDF → `SUBMITTED` once filed, with the receipt/quittance scan attached back to the `Declaration`.

**5.4 Scheduled batch generation:**

* Cron (BullMQ repeatable job) runs at the start of each month/quarter → for every active client, creates the `Declaration` shells for whatever's due that period (based on `secteur`/`regimeFiscal`) → if payroll/sales data for that period already exists, runs the calc pipeline immediately so staff find a ready draft waiting, not a blank task.

**5.5 Alerts tied to automation state, not just dates:**

* `DECLARATION_DUE_SOON` rule → alert escalates differently depending on status: "draft ready, needs review" vs. "no source data yet, can't auto-calculate" — the second case is the one that actually needs a human to chase something down.

**5.6 (v3, evaluate feasibility/ToS first) RPA for portal submission:**

* Some filings ultimately get typed into a government e-portal (e-CNAS, Jibayatic). A Playwright-based script *could* push the computed/approved data into those portals where that's permitted and stable — but government portal UIs change without notice and this may violate the portal's terms, so treat it as optional and fragile. Safer default: the platform's job ends at "generate the ready-to-submit file/PDF," and a human does the final upload/filing; track that submission step manually (`submittedAt` + receipt scan upload) rather than trying to automate the actual portal interaction.

## 6. Feature roadmap

**MVP (ship this first):**

1. Auth + multi-tenant cabinet/user setup
2. Client CRUD incl. `secteur`/`regimeFiscal` (drives which declaration types apply)
3. `PayrollEntry`/`SalesEntry` manual entry forms
4. Calc engine (§5.1) — auto-generate `DRAFT_CALCULATED` declarations
5. Review/approve UI (§5.3)
6. Dashboard: drafts ready for review, missing-data alerts, upcoming deadlines

**V2:** 7. PDF auto-fill (§5.2) 8. OCR pipeline feeding `PayrollEntry`/`SalesEntry` directly (§5.1 step 1) 9. Email/SMS alerts (§5.5) 10. Scheduled batch generation (§5.4)

**V3:** 11. AI chat assistant (RAG over documents/declarations) 12. Document classification AI 13. RPA portal push, only if validated as safe/compliant (§5.6)

## 7. Non-functional requirements

* Encrypt NIF/NIN/salary/SS data at rest; audit-log every read/write on client financial records.
* UI in French; OCR must handle French + Arabic mixed-script source documents.
* Rates/deadlines are data, not code — a finance-law change should never require a deploy.

## 8. Repo structure

```
/apps
  /web   → Next.js frontend
  /api   → NestJS backend
    /src/{clients,declarations,documents,automation,ai,alerts}
/packages
  /shared-types
  /prisma → schema.prisma, migrations
```
---

Build order for the agent: §6 MVP items 1–6 first (get one client's calc loop working end-to-end with manual data entry), then layer OCR (V2.8) and PDF auto-fill (V2.7) on top of an already-working calc engine.
