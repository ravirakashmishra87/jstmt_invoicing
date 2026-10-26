# JST Machine Tools: Invoice, Billing, Product Catalogue & Stock Management System

**Document type:** Software Requirements Specification (SRS)
**Client:** JST Machine Tools
**Stack:** PHP 8.5 (latest stable, always the newest patch release) / MySQL 8.4 LTS (InnoDB)
**Version:** 1.0 (Draft)
**Reference documents:** Sample Quotation (Offer No. `MITERSAW330VRPB005-2026`) and Sample Tax Invoice / e-Invoice / e-Way Bill (Invoice No. `2026-2027/691`)

---

## 1. Purpose & Scope

JST Machine Tools sells high-value industrial machinery (e.g. band saw machines) to B2B customers across states. The business needs a web application to:

1. Maintain a **product catalogue** with technical specifications, images and pricing.
2. Issue **quotations** in the company's standard format.
3. Convert quotations into **sales orders, proforma invoices and GST tax invoices**.
4. Track **stock**, including optional **serial-number-level tracking** (switchable per product; normally used for machines).
5. Record **payments** (the sample terms are 100% advance) and customer outstanding.
6. Produce **GST-compliant documents** (e-Invoice IRN/QR, e-Way Bill) and reports.

### Out of scope (Phase 1)
Full accounting / general ledger, payroll, manufacturing / BOM, and e-commerce storefront. The system should export data so it can be imported into Tally or similar tools later.

---

## 2. Users & Roles

| Role | Typical user | Key permissions |
|---|---|---|
| Super Admin | Owner / IT | Everything, including settings, users, audit log |
| Sales Executive | Sales staff | Customers, quotations, sales orders; view catalogue and stock |
| Accounts | Accountant | Invoices, credit/debit notes, payments, GST reports, e-invoice / e-way bill |
| Store / Dispatch | Store keeper | Stock in/out, GRN, delivery challan, serial numbers, dispatch details |
| Purchase | Purchase staff | Suppliers, purchase orders, GRN |
| Viewer / Management | Director | Read-only dashboards and reports |

Permissions must be configurable per role (RBAC) at module + action level (view / create / edit / delete / approve / export / print).

---

## 3. Module Overview

| # | Module | Priority |
|---|---|---|
| M1 | Authentication, Users & Roles | Must |
| M2 | Company & System Settings | Must |
| M3 | Master Data (Customers, Suppliers, Tax, UoM, etc.) | Must |
| M4 | Product Catalogue | Must |
| M5 | Quotation Management | Must |
| M6 | Sales Order Management | Should |
| M7 | Proforma Invoice (advance payment request) | Should |
| M8 | Tax Invoice & Billing | Must |
| M9 | Credit / Debit Notes | Should |
| M10 | Delivery Challan & Dispatch | Should |
| M11 | Stock / Inventory Management (with serial numbers) | Must |
| M12 | Purchase & GRN | Should |
| M13 | Payments & Receivables | Must |
| M14 | GST Compliance (GST logic and reports: Must; e-Invoice / e-Way Bill API integration: **Parked**) | Must / Parked |
| M15 | Warranty & Installation Tracking | Could |
| M16 | Reports & Dashboard | Must |
| M17 | Document Templates & PDF Engine | Must |
| M18 | Notifications & Communication (Email + WhatsApp) | Should |
| M19 | Audit Log, Backup & Data Import / Export | Must |

---

## 4. Functional Requirements

### M1. Authentication, Users & Roles
- FR-1.1 Login with email/username + password; passwords hashed (bcrypt/argon2).
- FR-1.2 Role-based access control as per Section 2.
- FR-1.3 Forgot/reset password by email; optional 2FA (TOTP) for Admin and Accounts.
- FR-1.4 Session timeout, account lockout after repeated failed logins.
- FR-1.5 User CRUD with activate/deactivate (no hard delete).

### M2. Company & System Settings
- FR-2.1 Company profile: name, logo(s), address, GSTIN, PAN, state and state code, Udyam no., phone, email, website.
- FR-2.2 Multiple bank accounts (holder, bank, branch, account no., IFSC); one marked default for printing on documents.
- FR-2.3 **Single warehouse** (one default location). The schema still carries a `warehouse_id` on stock records so multiple locations can be added later without a data migration, but no multi-warehouse screens are built in Phase 1.
- FR-2.4 **Document numbering series** per document type (Quotation, Sales Order, Proforma, Invoice, Credit Note, Challan, PO, GRN). **Fully configurable by an admin from the Settings screen, per document type** (no code change needed): prefix, suffix, starting number, zero-padding, separator, tokens (`{FY}`, `{YYYY}`, `{MM}`, `{MODELCODE}`, `{SEQ}`) and **reset rule** (financial year [India FY: 1 Apr to 31 Mar], calendar year, monthly, or never). A live preview of the next number is shown, and format changes apply only to future documents. Numbers already issued are never altered.
  - Invoice example from sample: `2026-2027/691`
  - Quotation example from sample: `MITERSAW330VRPB005-2026` (model-based). The system may use a configurable pattern with tokens such as `{PREFIX}{MODELCODE}{SEQ}-{YEAR}`.
  - Numbers must be gapless and generated transactionally (no duplicates under concurrent use).
- FR-2.5 Default terms & conditions templates, declaration text, jurisdiction line, footer text, quotation validity days.
- FR-2.6 Tax settings: GST rates, round-off rule, currency (INR in Phase 1), number format (Indian lakh/crore grouping, e.g. `5,16,250.00`). **Multi-currency / export billing may be needed in future** (not in Phase 1). To keep it possible, documents store a currency code and exchange rate (INR, rate 1.00 for now) and keep both foreign-currency and INR amounts, so it can be enabled later without restructuring the data.

### M3. Master Data

**Customers**
- FR-3.1 Fields: customer code, legal name, trade name, GSTIN, PAN, GST registration type (Regular / Composition / Unregistered / SEZ / Export), contact person, mobile, email.
- FR-3.2 **Multiple addresses per customer** with type Billing and Shipping. The sample invoice has distinct *Consignee (Ship to)* and *Buyer (Bill to)* blocks. Each address has: address lines, city, district, state, state code, PIN, country.
- FR-3.3 GSTIN format validation (15 chars, checksum) and state-code auto-derivation from the first two digits. Optional GSTIN lookup via API (Phase 2).
- FR-3.4 Credit limit, **default payment terms** (e.g. 100% advance, X% advance + balance on delivery, Net 15/30/45 days, custom), opening balance, notes, assigned salesperson.

**Suppliers**: Same structure as customers plus supplier-specific bank details.

**Other masters**
- Units of Measure (NOS, SET, KG, MTR, etc.)
- HSN/SAC codes with default GST rate (e.g. `84615011` goods, `998898` freight service, both 18% in the sample)
- Tax rate master (0, 5, 12, 18, 28 percent, plus cess if needed)
- Transporters (name, transporter GSTIN / ID, e.g. "TIME TO TIME", `24AAICT4958R1ZF`)
- Vehicles (optional), States (with GST codes), Payment terms, Payment modes
- Terms & Conditions templates (reusable blocks, selectable per quotation)
- Other-charges master (Freight, Packing, Insurance, Installation) with default HSN/SAC and tax rate

### M4. Product Catalogue
- FR-4.1 **Product types**: Goods (machine, spare, consumable, accessory) and Services (freight, installation, AMC).
- FR-4.2 Core fields: SKU / item code, name, model number, category, brand, HSN/SAC, GST rate, UoM, description, standard list price, purchase price, minimum selling price (optional), status (active/inactive).
- FR-4.3 Category tree (e.g. Band Saw Machines > Semi-Automatic > Miter Saw).
- FR-4.4 **Technical specification sheet** per product, stored as structured key-value groups so it can be rendered in quotations. The sample quotation groups specs as: *Cutting Capacity*, *Cutting Accuracy*, *Advance Features*, *Standard Accessories*, *Component and Brand*. Admin can define spec groups and bullet lines per product and reorder them.
- FR-4.5 **Component & brand list** per product (e.g. Hydraulic valve: Yuken; Bearings: SKF/FAG/NTN).
- FR-4.6 Multiple product images (sample photo), brochure/PDF attachments, and one primary image used in quotations.
- FR-4.7 **Serial number tracking flag (optional per product)**: serial tracking is available for every product (machines, spares, accessories, consumables) and is switched on or off per product. Machines are normally serialised (e.g. `SR NO.1351-2627` on the invoice); items with the flag off are quantity-only.
- FR-4.8 **Price lists**: a default price list plus optional customer-specific or date-effective prices. Track price history.
- FR-4.9 Default discount % and default commercial terms per product (optional).
- FR-4.10 Product variants / options (e.g. optional accessories) and **bundles** (machine plus standard accessories) are Could-have.
- FR-4.11 Clone product, bulk import/export via CSV/Excel, search and filter by category, brand, status, stock.

### M5. Quotation Management
Based on the sample quotation.
- FR-5.1 Header: quotation number (auto), date, customer (select), customer snapshot (name, address, mobile, GSTIN, email copied at creation so later master edits do not alter issued documents), subject line / model heading, salesperson, validity (default 7 days, shows expiry date).
- FR-5.2 Line items: Sr. No., product (pulls full spec description from the catalogue, editable per quote), quantity, list price, **discount % or amount**, net price, line total.
- FR-5.3 Additional charges: freight, packing, insurance (e.g. "0.3% of invoice value"), with tax handling. Allow "At actual / on your account" text instead of a value.
- FR-5.4 Tax calculation: GST 18% shown as separate lines; IGST vs CGST+SGST decided by place of supply (see Section 6).
- FR-5.5 Terms & Conditions editor: select template and edit per quotation. Sample terms: Payment, Prices (ex-works), Delivery (e.g. 45 days), Taxes, Packing, Transport & Insurance, Commissioning, Inspection & Trial, Warranty, Validity.
- FR-5.6 Include product images and bank details on the printout (toggle).
- FR-5.7 **Status workflow:** Draft → Sent → Under Negotiation → Accepted / Rejected / Expired / Revised. Auto-expire after validity date.
- FR-5.8 **Revisions:** create revision (Rev 1, Rev 2) keeping the history; only the latest is active.
- FR-5.9 Generate PDF in the exact company letterhead style; email PDF to customer; share via WhatsApp link.
- FR-5.10 Convert an accepted quotation to Sales Order, Proforma Invoice or directly to Tax Invoice, carrying all lines.
- FR-5.11 Duplicate quotation; follow-up reminders (date, notes); win/loss reason capture.

### M6. Sales Order Management
- FR-6.1 Create from quotation or standalone. Capture customer PO number/reference (sample: *Buyer's Order No.*) and date.
- FR-6.2 Status: Open → Partially Invoiced → Fully Invoiced / Cancelled.
- FR-6.3 **Stock reservation** for serial-tracked items on confirmation (optional setting).
- FR-6.4 Expected delivery date (from quotation delivery terms, e.g. 45 days) and delivery tracking.
- FR-6.5 Advance received against order (links to M13).

### M7. Proforma Invoice
- FR-7.1 Generated from quotation / sales order to request the 100% advance payment (matches the "100% ADVANCE" term).
- FR-7.2 Same layout as the invoice but labelled "PROFORMA INVOICE", with no stock or tax liability posting.
- FR-7.3 On receipt of payment, convert to Tax Invoice.

### M8. Tax Invoice & Billing
Based on the sample tax invoice.

**Header and parties**
- FR-8.1 Invoice number from the FY series (e.g. `2026-2027/691`), invoice date.
- FR-8.2 Seller details from company profile (name, address, Udyam, GSTIN, state/code, contact, email).
- FR-8.3 **Consignee (Ship to)** and **Buyer (Bill to)** each with name, address, GSTIN, state/code, contact; may differ. **Place of Supply** field.
- FR-8.4 Transaction metadata fields, all optional and printed when filled:
  - Delivery Note, Delivery Note Date
  - Reference No. & Date, Other References (sample shows a person's name; make it free text)
  - Buyer's Order No. & Date
  - Dispatch Doc No.
  - Dispatched Through (transporter), Destination
  - Bill of Lading / LR-RR No. & date
  - Motor Vehicle No.
  - Terms of Delivery
  - Mode / Terms of Payment (e.g. "100% ADVANCE")

**Line items**
- FR-8.5 Columns: Sl No., Description of Goods and Services, HSN/SAC, Quantity, Rate, per (UoM), Disc %, Amount.
- FR-8.6 Under a goods line, print **serial number(s)** picked from stock (e.g. `SR NO.1351-2627`) and a secondary description line (e.g. model name).
- FR-8.7 Service/charge lines (e.g. "FREIGHT OTHER CHARGES", HSN `998898`) with their own HSN and tax rate.
- FR-8.8 Totals row with total quantity; subtotal before tax; tax lines (`OUTPUT IGST @18%`, or CGST + SGST); round-off; **grand total**.
- FR-8.9 **Amount in words** (Indian system, e.g. "Indian Rupees Five Lakh Sixteen Thousand Two Hundred Fifty Only") and **Tax amount in words**.
- FR-8.10 **HSN/SAC-wise tax summary table**: Taxable Value, Tax Rate, Tax Amount per HSN, plus totals.
- FR-8.11 Footer: company PAN, bank details, declaration text, "Subject to [city] Jurisdiction", "This is a Computer Generated Invoice", authorised signatory block (signature/stamp image optional), E. & O.E.

**Behaviour**
- FR-8.12 Create from quotation, sales order, proforma, or from scratch.
- FR-8.13 Invoice lifecycle: Draft → Issued (number locked, stock deducted) → Paid / Partially Paid → Cancelled.
- FR-8.14 After issue (or after IRN generation) the invoice is **immutable**. Corrections are made through credit/debit notes or cancellation (see M14 rules).
- FR-8.15 Serial number selection is mandatory for products with serial tracking enabled and skipped for those without; the system only offers in-stock serials.
- FR-8.16 Discount can be line-level and/or invoice-level; **no discount approval workflow** is required; any user with permission to create the document may apply a discount. Every discount is still captured in the audit log and available in the discount analysis report.
- FR-8.17 Print / download PDF (A4), email to customer, duplicate copies labelling (Original for Recipient / Duplicate for Transporter / Triplicate for Supplier).
- FR-8.18 Search and filter invoices by number, customer, date range, status, amount.

### M9. Credit Note / Debit Note
- FR-9.1 Issue against an original invoice (full or partial), with reason (return, rate difference, discount, correction).
- FR-9.2 Return of goods increases stock (including re-instating serial numbers) and reduces receivable.
- FR-9.3 Own numbering series; e-invoice applicable.
- FR-9.4 Included in GST reports with the correct sign.

### M10. Delivery Challan & Dispatch
- FR-10.1 Delivery challan for dispatch before invoicing, job work, or demo/sample movement.
- FR-10.2 Capture transporter, vehicle number, LR number, dispatch date, packaging details ("Wooden packing").
- FR-10.3 Stock deducted on challan; later linked to an invoice without double deduction.
- FR-10.4 Dispatch register and status (Packed, Dispatched, Delivered) with proof-of-delivery upload.

### M11. Stock / Inventory Management
- FR-11.1 **Stock ledger (append-only):** every movement is a row (date, product, warehouse, serial, qty in/out, document type and id, balance). Current stock is derived from, or reconciled with, the ledger.
- FR-11.2 **Serial-number master:** each unit has a unique serial, status (In Stock, Reserved, Sold, Returned, Under Repair, Scrapped), warehouse, purchase/GRN reference, sale/invoice reference, warranty start/end.
- FR-11.3 Stock movements originate from: GRN (in), Sales Invoice / Challan (out), Credit Note / return (in), Debit note / purchase return (out), Stock adjustment, Opening stock.
- FR-11.4 **Stock adjustments** (damage, shrinkage, count correction) with reason and approval.
- FR-11.5 *(Deferred)* Warehouse transfers between locations; not needed while there is a single warehouse.
- FR-11.6 **Negative stock prevention** (configurable per company).
- FR-11.7 **Reorder level** per product with low-stock alerts on the dashboard and by email.
- FR-11.8 **Reserved vs. available stock** (Available = On hand − Reserved).
- FR-11.9 Stock valuation method is a **company-level setting** (Weighted Average or FIFO), with **Weighted Average as the default** since the client has not decided. Items with serial tracking enabled are valued at the **actual purchase cost of the specific serial**, which suits high-value machines. The method can be changed later by an admin (applies to future transactions) and a valuation report is provided.
- FR-11.10 Physical stock-taking: create count sheet, enter counted qty, auto-generate adjustment.
- FR-11.11 Stock summary, stock ledger by product/serial, and aging reports (see M16).
- FR-11.12 Batch/lot tracking for consumables (e.g. blades): Could-have.

### M12. Purchase & GRN
- FR-12.1 Purchase Order to supplier (numbered, with terms, status: Open / Partial / Closed).
- FR-12.2 **Goods Receipt Note (GRN):** receive against PO; capture serial numbers for products with serial tracking enabled; increases stock.
- FR-12.3 Purchase invoice / bill entry with supplier invoice no., date, GST (ITC) details.
- FR-12.4 Purchase returns / debit notes.
- FR-12.5 Landed cost allocation (freight, duty) into item cost: Could-have.

### M13. Payments & Receivables
- FR-13.1 Record **receipts** from customers: date, amount, mode (NEFT/RTGS/UPI/Cheque/Cash), reference no. / UTR, bank account, remarks.
- FR-13.2 Allocate a receipt to one or more invoices, or record as **advance** against a quotation / sales order / proforma, then adjust against the final invoice.
- FR-13.3 Partial payments; invoice payment status (Unpaid / Partial / Paid / Overdue).
- FR-13.4 **Customer statement / ledger** and outstanding aging (0–30, 31–60, 61–90, 90+ days).
- FR-13.5 **Flexible payment terms:** payment terms are defined in a master (advance %, balance %, due-days, milestone such as "on delivery") and are defaulted from the customer, then editable per quotation, sales order and invoice. 100% advance is just one preset, not a fixed rule. The due date is calculated from the selected terms.
- FR-13.5a **Overdue interest is optional and configurable:** an on/off setting and an interest rate (sample declaration: 24% if unpaid by the due date), applicable globally or per customer. When on, interest is computed as a report and can be added to a customer statement or billed as a debit note, but it is never auto-posted.
- FR-13.5b Credit-limit warning (not a hard block by default) when an invoice would take a customer above their limit; the limit check can be switched to a hard block in settings.
- FR-13.6 Payment reminders by email and WhatsApp for overdue invoices.
- FR-13.7 Supplier payments and payables ledger (basic).
- FR-13.8 TDS/TCS fields on receipts: Should-have.

### M14. GST Compliance

**GST logic (Phase 1)**
- FR-14.1 Auto-select **IGST** when seller state ≠ place of supply state; **CGST + SGST** when equal. (Sample: Gujarat → Maharashtra, so IGST 18%.)
- FR-14.2 Tax computed per line and summarised per HSN; rounding rules configurable and consistent with the printed totals.
- FR-14.3 Reverse charge flag, SEZ / export / nil-rated / exempt supply types.
- FR-14.4 Reports: sales register, purchase register, HSN summary, GSTR-1 style data export (B2B, B2CL, B2CS, CDNR, HSN), and GSTR-3B summary values (CSV/JSON/Excel).

**e-Invoice (PARKED: API integration deferred, decision pending)**
- FR-14.5 Generate IRN via the government e-Invoice system through a GST Suvidha Provider (GSP) / API. Applicability depends on the client's aggregate turnover threshold; **confirm with the client's CA** whether JST Machine Tools is liable. The sample shows an e-Invoice.
- FR-14.6 Store and print on the invoice: **IRN, Ack No., Ack Date, signed QR code**.
- FR-14.7 Cancel IRN within the permitted window with reason; block editing once IRN exists.
- FR-14.8 Retry queue and error log for API failures; credentials stored encrypted.

**e-Way Bill (PARKED: API integration deferred, decision pending)**
- FR-14.9 Generate e-Way Bill from the invoice via API (part-A/part-B): supply type, transaction type (Bill To – Ship To), transporter ID/name, mode (Road), approximate distance, vehicle number, and validity.
- FR-14.10 Store EWB number, generated date and validity; print the e-Way Bill page with QR (as in the sample, page 2 of the invoice PDF).
- FR-14.11 Update vehicle number, extend validity, and cancel.
- FR-14.12 Trigger prompt when invoice value for goods exceeds the statutory threshold (currently ₹50,000; keep as a configurable setting).

> The e-Invoice / e-Way Bill API requirements above (FR-14.5 to FR-14.12) are **parked** until the client confirms applicability, turnover threshold and GSP/API provider. Until then, the system must provide fields to **manually enter** IRN, Ack No/Date, QR image and EWB details so users can still print complete documents.

### M15. Warranty & Installation Tracking (Could)
- FR-15.1 Auto-create a warranty record from the invoice and serial number: start date (commissioning/dispatch), end date per the formula in the terms (e.g. 12 months from commissioning or 15 months from dispatch, whichever is earlier).
- FR-15.2 Installation / commissioning record: engineer, date, sign-off, attachments.
- FR-15.3 Service tickets against a serial number (complaint, visit, parts used, resolution); the sample requires complaints be reported within 7 days.
- FR-15.4 Warranty expiry alerts and AMC offers.

### M16. Reports & Dashboard
**Dashboard:** today / month sales, quotations pending, quotation conversion rate, outstanding receivables, low-stock items, recent invoices, top customers, monthly sales chart.

**Reports** (filterable, exportable to PDF / Excel / CSV):
- Sales: sales register, sales by customer / product / category / salesperson / month, discount analysis
- Quotation: pipeline, conversion ratio, expired and lost quotations with reasons
- Stock: current stock, stock ledger, serial number register, stock valuation, aging / slow-moving, reorder report, movement summary
- Receivables: customer outstanding, aging, payment collection, customer statement
- Purchase: purchase register, supplier payables, pending POs
- GST: GSTR-1/3B data, HSN summary, e-invoice / e-way bill status, tax liability
- Audit: user activity, document change history

### M17. Document Templates & PDF Engine
- FR-17.1 Pixel-faithful templates for **Quotation**, **Proforma**, **Tax Invoice (+ e-Way Bill page)**, **Credit Note**, **Delivery Challan**, **PO**, **Receipt**, **Customer Statement**.
- FR-17.2 Letterhead with logos (company + brand logos as in the sample quotation), colours, fonts configurable.
- FR-17.3 Section toggles (images, bank details, T&C, signature).
- FR-17.4 A4 portrait; multi-page support with repeated table headers; page numbers.
- FR-17.5 QR code rendering (IRN signed QR; EWB QR).
- FR-17.6 Support for multiple templates per document type with a default.
- FR-17.7 Unicode support (₹ symbol, Indian-format numbers).

### M18. Notifications & Communication (Should)
Channels: **Email and WhatsApp** (SMS is out of scope).
- FR-18.1 **Email:** send quotations, invoices, receipts, statements and payment reminders as PDF attachments using editable templates; log every email (to, subject, time, status).
- FR-18.2 **WhatsApp (Phase 1):** one-click share of a document via WhatsApp click-to-chat link (`wa.me`) with a pre-filled message and a secure, expiring PDF link; log who shared what and when.
- FR-18.3 **WhatsApp Business API (Phase 3):** automated sending of documents and overdue reminders directly from the system, using approved message templates and delivery status tracking.
- FR-18.4 In-app notifications for approvals, low stock, expiring quotations and overdue payments.

### M19. Audit, Backup & Import/Export
- FR-19.1 **Audit trail** on all business tables: who, when, what changed (old/new values), IP address.
- FR-19.2 Soft-delete for masters and documents; hard delete disallowed for issued documents.
- FR-19.3 Scheduled **database backups** (daily) with retention and off-server copy; restore procedure documented.
- FR-19.4 The client is **starting from scratch**, so no legacy data migration is required. CSV/Excel import templates are still provided as a convenience for fast initial data entry (customers, suppliers, products, and any machines already in the godown at go-live, with their serial numbers). Opening balances default to zero.
- FR-19.5 Export of any list to Excel/CSV in Phase 1. **Tally (or other accounting tool) export is a future requirement**, planned for Phase 3: the client may adopt it later and does not need it at go-live. To keep that option open, documents already store the data an accounting export needs (party GSTIN, HSN-wise tax split, ledger-style tax lines, payment mode and bank), so no redesign will be required.
- FR-19.6 File attachments on any document (PO copy, LR copy, payment proof).

---

## 5. Key Business Workflows

### 5.1 Sales cycle (primary)
```
Customer Enquiry → Quotation (Draft → Sent) → Revisions/Negotiation
   → Accepted → Sales Order → Proforma Invoice → Advance Payment Received
   → Procurement from supplier (PO → GRN) → Stock Available (serial assigned)
   → Tax Invoice (stock out) → e-Invoice (IRN) → e-Way Bill
   → Dispatch (transporter, vehicle, LR) → Delivery → Installation / Commissioning
   → Warranty tracking
```
Shortcuts must be allowed (e.g. Quotation → Tax Invoice directly).

### 5.2 Purchase / stock-in cycle
```
Purchase Order → Goods Receipt Note (serial numbers captured)
   → Stock In → Purchase Bill → Supplier Payment
```

### 5.3 Returns / corrections
```
Issued Invoice → Credit Note (return/discount) → Stock In (if goods returned) → Reduce receivable
Issued Invoice (within allowed window) → Cancel IRN → Cancel Invoice → Reverse stock
```

---

## 6. Business Rules & Calculations

1. **Place of supply:** Default = state of Ship-to/Bill-to per GST rules; user can override with justification. IGST if different from seller state, otherwise CGST + SGST at half the rate each.
2. **Line amount** = Qty × Rate − Discount. Discount may be % or flat amount; store both the entered value and the computed value.
3. **Taxable value** = sum of line amounts including taxable charges (freight is taxable at 18% under SAC 998898 in the sample).
4. **Tax** is computed per HSN/rate group, then summed. The sum of the HSN-wise tax must equal the invoice-level tax.
5. **Round-off** to the nearest rupee, shown as a separate line; store total before and after rounding.
6. **Amount in words** uses the Indian numbering system (Lakh, Crore) with "Only".
7. **Quotation validity** = quotation date + validity days; expired quotations cannot be converted without revalidation.
8. **Stock deduction** happens at invoice issue (or challan if the challan flow is used); never on a draft.
9. **Serial numbers** are unique per product; a serial can be sold only once unless returned.
10. **Insurance** charged as % of invoice value can be calculated automatically as an additional line.
11. **Document locking:** Issued invoices cannot be edited; only the allowed non-statutory fields (e.g. transporter note, vehicle number) can be updated, and each change is audit-logged.
12. **Date control:** Back-dated documents are restricted by role and by a "books locked up to" date setting.
13. **Money handling:** all amounts stored as `DECIMAL(15,2)` (rates and quantities `DECIMAL(15,4)` where needed); never floats.

### Worked test case (from the sample invoice)
| Item | HSN/SAC | Taxable | IGST 18% |
|---|---|---|---|
| Bandsaw Machine (1 NOS) | 84615011 | 4,29,000.00 | 77,220.00 |
| Freight Other Charges | 998898 | 8,500.00 | 1,530.00 |
| **Total** | | **4,37,500.00** | **78,750.00** |

Grand total = **₹5,16,250.00** → "Indian Rupees Five Lakh Sixteen Thousand Two Hundred Fifty Only". The system must reproduce these figures exactly.

---

## 7. Data Model (High-Level)

Main entities and key relationships (all tables with `id`, `created_at`, `updated_at`, `created_by`, `updated_by`, `deleted_at` where applicable):

| Group | Tables |
|---|---|
| Security | `users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `audit_logs`, `login_attempts` |
| Settings | `company_profile`, `bank_accounts`, `warehouses`, `number_series`, `terms_templates`, `settings`, `financial_years` |
| Masters | `customers`, `customer_addresses`, `suppliers`, `supplier_addresses`, `states`, `hsn_sac_codes`, `tax_rates`, `uoms`, `transporters`, `charge_types`, `payment_terms` |
| Catalogue | `product_categories`, `brands`, `products`, `product_spec_groups`, `product_spec_items`, `product_components`, `product_images`, `product_attachments`, `price_lists`, `price_list_items` |
| Quotation | `quotations`, `quotation_items`, `quotation_charges`, `quotation_terms`, `quotation_revisions` |
| Sales | `sales_orders`, `sales_order_items`, `proforma_invoices`, `proforma_items`, `invoices`, `invoice_items`, `invoice_item_serials`, `invoice_charges`, `invoice_tax_summary`, `credit_debit_notes`, `credit_debit_items`, `delivery_challans`, `challan_items`, `dispatch_details` |
| GST | `einvoice_details` (IRN, ack no/date, signed QR, status, response JSON), `eway_bills` (EWB no, dates, vehicle, distance, status), `gst_api_logs` |
| Stock | `stock_ledger`, `stock_balances`, `product_serials`, `stock_adjustments`, `stock_adjustment_items`, `stock_counts`, `stock_count_items`, `stock_reservations` |
| Purchase | `purchase_orders`, `purchase_order_items`, `grns`, `grn_items`, `purchase_bills`, `purchase_bill_items` |
| Payments | `receipts`, `receipt_allocations`, `supplier_payments`, `payment_allocations` |
| Service | `warranties`, `service_tickets`, `installations` |
| Comms | `email_logs`, `notifications`, `reminders` |

**Design notes**
- Use InnoDB, `utf8mb4`, foreign keys, and indexes on document numbers, dates, customer/product IDs, GSTIN, serial numbers.
- Documents store **snapshots** of party names/addresses/GSTIN, item description, HSN and rates at the time of issue.
- `invoice_number` unique per (company, financial year, series).
- Stock ledger rows are never updated or deleted; corrections are made with reversing entries.
- Use database transactions with row-level locking (`SELECT ... FOR UPDATE`) when generating document numbers and deducting stock.

---

## 8. Technical Requirements

| Area | Recommendation |
|---|---|
| Language / Framework | **PHP 8.5 (latest stable release) with the current stable Laravel release that supports it** (recommended for RBAC, migrations, queues, validation, scheduler). A plain PHP + custom MVC is possible but increases effort and risk. **Version policy:** run the latest stable PHP and apply patch releases promptly; never run a PHP branch that is end-of-life. Plan an upgrade to the next PHP release within a few months of its release (PHP ships a new minor version each November), and run `composer audit` in the deployment pipeline to catch vulnerable dependencies. |
| Database | **MySQL 8.4 LTS** or newer (MySQL 8.0 reached end of life on 30 Apr 2026 and no longer gets security fixes), InnoDB. Apply minor/security updates regularly. |
| Frontend | Blade + Bootstrap 5 / Tailwind with Alpine.js or Livewire; DataTables for lists; select2 for lookups. A separate SPA is not required. |
| PDF generation | mPDF or Dompdf (supports Indian fonts and ₹); alternatively wkhtmltopdf/Browsershot for exact layouts |
| Excel / CSV | PhpSpreadsheet / Laravel Excel |
| QR codes | `endroid/qr-code` or `simplesoftwareio/simple-qrcode` |
| Queue / scheduler | Laravel queue (database/Redis) and scheduler/cron for backups, reminders, expiry jobs, API retries |
| Email | SMTP / transactional provider |
| Hosting | Linux (Ubuntu), Nginx/Apache, PHP-FPM, SSL (HTTPS mandatory), daily backups |
| Version control | Git with a staging environment; Composer dependency management |
| Testing | PHPUnit/Pest for tax, numbering and stock logic; manual UAT scripts |

### Non-functional requirements
- **Security:** OWASP Top 10 mitigations (prepared statements, CSRF, XSS escaping, secure file upload validation), encrypted secrets (API keys), HTTPS, least-privilege DB user, rate limiting on login.
- **Performance:** list pages < 2 s for 100k invoices/10k products; PDF generation < 5 s.
- **Availability and recovery:** daily backups, tested restore, RPO ≤ 24 h, RTO ≤ 4 h.
- **Usability:** responsive (desktop first, usable on tablet/mobile), keyboard-friendly entry grid for line items, global search, Indian number/date formats (DD-MMM-YY).
- **Auditability:** complete audit log, immutable stock ledger and issued documents.
- **Data retention:** keep financial records for at least 8 years as per GST/Companies Act norms (confirm with CA).
- **Localisation:** English (Gujarati/Hindi labels optional), INR only in Phase 1; currency-ready data model for possible future multi-currency / export billing.
- **Browser support:** latest Chrome, Edge, Firefox, Safari.
- **Maintainability:** coding standard PSR-12, migrations for all schema changes, seeders for masters (states, GST rates, UoM).

---

## 9. Phased Delivery Plan

| Phase | Scope | Outcome |
|---|---|---|
| **Phase 1 (MVP)** | M1, M2, M3, M4, M5, M8 (with manual IRN/EWB fields), M11 (core stock + serials), M13 (basic receipts), M16 (core reports), M17, M19 (audit, backup, import) | Quote, invoice and stock live with print-ready PDFs matching current formats |
| **Phase 2** | M6, M7, M9, M10, M12, GST returns data, advanced reports, notifications | Full order-to-cash and purchase-to-stock with statutory automation |
| **Phase 3** | M15 warranty/service, Tally export, multi-currency / export invoicing (if needed), batch tracking, customer portal (view quotes/invoices), WhatsApp API, mobile enhancements | Post-sales and ecosystem integration |

---

## 10. Acceptance Criteria (Samples)

1. Creating a quotation for the Miter Saw 330 with 10% discount on ₹5,00,000 yields a net price of ₹4,50,000 and PDF output with the specification groups, terms, images and bank details as in the sample.
2. Creating an invoice replicating the sample (Gujarat seller, Maharashtra buyer, machine plus freight) produces exactly ₹4,37,500 taxable, ₹78,750 IGST, ₹5,16,250 total, with the HSN summary and amounts in words matching.
3. Invoice numbers are sequential and reset on 1 April; two users saving simultaneously never receive the same number.
4. A product with serial tracking enabled (e.g. a machine) cannot be invoiced without choosing an in-stock serial; after issue the serial's status becomes *Sold* and stock decreases by 1.
5. Cancelling an invoice reverses the stock movement and restores the serial to *In Stock* (when permitted).
6. An issued invoice cannot be edited; a credit note can be raised against it.
7. A user with the Sales role cannot view purchase cost or access GST filing data unless granted.
8. Customer outstanding report equals invoices less allocated receipts, with the correct aging buckets.
9. Every create/update/delete on a business record appears in the audit log with the user and timestamp.
10. A full database backup can be restored on a clean server and the application runs without data loss.

---

## 11. Assumptions

- JST Machine Tools is a **trader only** (buys and resells machines and spares; no manufacturing, bill of materials or production tracking).
- **2–4 concurrent users** (a small team). A single modest VPS is sufficient; no clustering or load balancing is needed in Phase 1. Role-based access is still enforced so each user sees only what they need.
- Annual document volume is **not yet known**. Because JST sells high-value machinery, volume is expected to be low, but the system is designed with headroom (indexed tables, paginated lists, performance target of 100k invoices in the non-functional requirements) so no redesign is needed if volume grows. This can be refined once real figures are available.
- **No legacy data:** the client is starting from scratch, so no data migration from an existing system is planned. Document number series start from the configured starting numbers (see FR-2.4).
- **Branding and print formats are not finalised.** The two sample documents (quotation and tax invoice) are the baseline layout. Logo, letterhead, signature and stamp images are uploaded by an admin in Settings (FR-2.1, FR-17.2), so they can be supplied or replaced at any time without development work. Template layout adjustments after the client reviews the first printed samples are expected and covered by the change-request process.
- Single legal entity (JST Machine Tools) with a single warehouse; multi-company support is not required in Phase 1.
- Sample documents show the format and fields to follow; the company name, logo, address, GSTIN and bank details will be replaced by JST Machine Tools' own details.
- The system is hosted on a cloud/VPS server managed by the client or vendor, with internet access for email and GST APIs.
- Product specifications are maintained by the client's staff in the catalogue.

## 12. Open Questions for the Client

_No open questions at present. Items still to be confirmed are listed under Parked Items below._

### Parked Items (to be revisited)

| Item | What is pending | Impact on build |
|---|---|---|
| e-Invoice and e-Way Bill API integration (FR-14.5 to FR-14.12) | Confirm the company's aggregate turnover and whether e-Invoice is mandatory, whether e-Way Bill is needed for most dispatches, and which GSP/API provider to use | None for Phase 1. Manual entry fields for IRN, Ack No./Date, QR and EWB details remain so documents can be printed in full. Tables `einvoice_details`, `eway_bills` and `gst_api_logs` can be created later without affecting other modules. |

---

## 13. Glossary

| Term | Meaning |
|---|---|
| HSN / SAC | Harmonized System of Nomenclature (goods) / Services Accounting Code |
| IGST / CGST / SGST | Integrated / Central / State Goods and Services Tax |
| IRN | Invoice Reference Number issued by the e-Invoice portal |
| EWB | e-Way Bill for goods movement |
| GRN | Goods Receipt Note |
| GSP | GST Suvidha Provider (API gateway for GST systems) |
| LR / RR | Lorry Receipt / Railway Receipt |
| FOC | Free of cost |
| Ex-works | Price excludes transport and delivery from seller's premises |
| E. & O.E. | Errors and omissions excepted |
