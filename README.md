# JST Machine Tools — Invoice & Stock Management

Phase 1 MVP for quotations, GST tax invoices, product catalogue, serial-tracked stock, payments, and reports.

## Stack

- PHP 8.5 + Laravel 13
- MySQL (`jstmt`)
- Bootstrap 5 (CDN)
- DomPDF for quotation / invoice PDFs

## Database

| Setting  | Value        |
|----------|--------------|
| Host     | 127.0.0.1    |
| Database | `jstmt`      |
| User     | `root`       |
| Password | `DEV#$jul26` |

Configured in `.env`.

## Setup

```bash
composer install
copy .env.example .env   # or use existing .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open http://127.0.0.1:8000

## Default login

| Field    | Value            |
|----------|------------------|
| Username | `admin`          |
| Email    | `admin@jstmt.com`|
| Password | `Admin@123`      |

## Phase 1 modules

- Authentication & RBAC (roles / permissions)
- Company settings, bank accounts, document number series
- Masters (customers, suppliers, UoM, HSN/SAC, tax rates, payment terms, etc.)
- Product catalogue with specs, components, serial-tracking flag
- Quotations (draft → sent → accepted, PDF, convert to invoice)
- Tax invoices (IGST/CGST+SGST, HSN summary, amount in words, manual IRN/EWB fields, PDF)
- Stock ledger + serial numbers
- Receipts & invoice allocations
- Dashboard & core reports (sales register, stock, outstanding, GST summary)
- Audit log on business records

## Sample acceptance data (seeded)

- Company in Gujarat (state code `24`)
- Customer in Maharashtra (state code `27`) — IGST path
- Product: Band Saw / Miter Saw @ ₹5,00,000, HSN `84615011`, serial tracking on
- Opening serial: `SR NO.1351-2627`

Creating an invoice with machine ₹4,29,000 + freight ₹8,500 @ 18% IGST yields:

- Taxable ₹4,37,500
- IGST ₹78,750
- Grand total ₹5,16,250

## Document numbering

Configurable in **Settings → Number series**:

- Invoice: `{FY}/{SEQ}` → e.g. `2026-2027/1`
- Quotation: `{MODELCODE}{SEQ}-{YEAR}`
