---
inclusion: always
---

# Product

**Handayani** — Manajemen Keuangan & SPP. A web application for school financial management, focused on student tuition billing (SPP), payments, and cash flow reporting.

## Audience and roles

- **superadmin / admin** — manage master data, billing, payments, expenses, reports, RBAC
- **siswa (student) / wali (parent/guardian)** — view bills, pay, see payment history through a portal

## Core domain concepts

Use the existing Indonesian domain vocabulary. Do not translate these terms.

- **Siswa** — student
- **Wali / Ayah / Ibu** — guardian / father / mother
- **Kelas / Jenjang** — class / school level (TK, SD, SMP, SMA, etc.)
- **TahunAjaran** — academic year / period
- **Kategori, JenisTagihan** — billing category and bill type (e.g., SPP, registration fee)
- **Tagihan** — bill / invoice issued to a student
- **Pembayaran** — payment recorded against a tagihan (produces a kwitansi / receipt)
- **Pengeluaran / PengeluaranRequest** — expense and expense approval request
- **KasHarian / RekapBulanan** — daily cash and monthly recap reports
- **KenaikanKelas** — class promotion / graduation batch process
- **Branch** — school branch (for multi-branch deployments and approval routing)

## Key features

- Bill issuance per jenjang/kelas, payment recording, receipt (kwitansi) generation as PDF
- Daily and monthly cash reports, import/export (Excel/CSV) of siswa and tagihan
- Approval workflow for pengeluaran with branch-level settings
- Email and in-app notifications (tagihan baru, kwitansi, opt-out support)
- Class promotion / graduation in batch
- RBAC via Spatie permissions, with siswa portal and admin panel separated

## UX language

- Primary UI language is **Bahasa Indonesia**. Match the existing tone in screens, validation messages, and notifications.
- Replies, specs, and design docs should follow the user's language. The repository default is Indonesian for user-facing strings.
