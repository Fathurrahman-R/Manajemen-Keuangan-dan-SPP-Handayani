# Billing & Payment API

<cite>
**Referenced Files in This Document**
- [api.php](file://backend/routes/api.php)
- [TagihanController.php](file://backend/app/Http/Controllers/TagihanController.php)
- [PembayaranController.php](file://backend/app/Http/Controllers/PembayaranController.php)
- [JenisTagihanController.php](file://backend/app/Http/Controllers/JenisTagihanController.php)
- [MidtransTransactionController.php](file://backend/app/Http/Controllers/MidtransTransactionController.php)
- [MidtransNotificationController.php](file://backend/app/Http/Controllers/MidtransNotificationController.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [Pembayaran.php](file://backend/app/Models/Pembayaran.php)
- [JenisTagihan.php](file://backend/app/Models/JenisTagihan.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [PembayaranResource.php](file://backend/app/Http/Resources/PembayaranResource.php)
- [TagihanResource.php](file://backend/app/Http/Resources/TagihanResource.php)
- [JenisTagihanResource.php](file://backend/app/Http/Resources/JenisTagihanResource.php)
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransNotificationService.php](file://backend/app/Services/Midtrans/MidtransNotificationService.php)
</cite>

## Table of Contents
1. Introduction
2. Project Structure
3. Core Components
4. Architecture Overview
5. Detailed Component Analysis
6. Dependency Analysis
7. Performance Considerations
8. Troubleshooting Guide
9. Conclusion
10. Appendices

## Introduction
This document provides comprehensive API documentation for billing and payment management endpoints, covering:
- Invoice (Tagihan) lifecycle: creation, updates, status changes, grouped views, and PDF export
- Payment processing: offline payments, online Midtrans payments, batch operations, refund handling via deletion, and status tracking
- Jenis Tagihan (payment type) configuration
- Grouped views for invoices and payments
- Export functionality and PDF generation for receipts
- Practical workflows, reconciliation, auditing, and data integrity considerations

## Project Structure
The billing and payment features are implemented as RESTful endpoints under the authenticated API group with permission-based access control. Key controllers handle business logic, while services encapsulate Midtrans integration and notification processing. Resources standardize JSON responses.

```mermaid
graph TB
Client["Client App"] --> Routes["API Routes<br/>routes/api.php"]
Routes --> TC["TagihanController"]
Routes --> PC["PembayaranController"]
Routes --> JTC["JenisTagihanController"]
Routes --> MTC["MidtransTransactionController"]
Routes --> MNC["MidtransNotificationController"]
TC --> TModel["Tagihan Model"]
PC --> PModel["Pembayaran Model"]
JTC --> JTModel["JenisTagihan Model"]
MTC --> MITService["MidtransInitiationService"]
MNC --> MINService["MidtransNotificationService"]
MITService --> MTModel["MidtransTransaction Model"]
MINService --> MTModel
MINService --> PModel
MINService --> TModel
PC --> PR["PembayaranResource"]
TC --> TR["TagihanResource"]
JTC --> JTR["JenisTagihanResource"]
```

**Diagram sources**
- [api.php:156-194](file://backend/routes/api.php#L156-L194)
- [TagihanController.php:26-567](file://backend/app/Http/Controllers/TagihanController.php#L26-L567)
- [PembayaranController.php:24-496](file://backend/app/Http/Controllers/PembayaranController.php#L24-L496)
- [JenisTagihanController.php:15-179](file://backend/app/Http/Controllers/JenisTagihanController.php#L15-L179)
- [MidtransTransactionController.php:10-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L10-L127)
- [MidtransNotificationController.php:9-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L9-L35)
- [Tagihan.php:8-60](file://backend/app/Models/Tagihan.php#L8-L60)
- [Pembayaran.php:8-53](file://backend/app/Models/Pembayaran.php#L8-L53)
- [JenisTagihan.php:8-48](file://backend/app/Models/JenisTagihan.php#L8-L48)
- [MidtransTransaction.php:7-85](file://backend/app/Models/MidtransTransaction.php#L7-L85)
- [PembayaranResource.php:8-28](file://backend/app/Http/Resources/PembayaranResource.php#L8-L28)
- [TagihanResource.php:8-42](file://backend/app/Http/Resources/TagihanResource.php#L8-L42)
- [JenisTagihanResource.php:8-26](file://backend/app/Http/Resources/JenisTagihanResource.php#L8-L26)
- [MidtransInitiationService.php:22-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L22-L473)
- [MidtransNotificationService.php:16-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L16-L284)

**Section sources**
- [api.php:47-345](file://backend/routes/api.php#L47-L345)

## Core Components
- Invoices (Tagihan): CRUD, grouped listing, student view, PDF export, partial/full payment updates
- Payments (Pembayaran): Offline recording, batch full payment, deletion (refund), grouped listing, student view including pending Midtrans transactions
- Payment Types (Jenis Tagihan): CRUD with period scoping
- Online Payments (Midtrans): Initiate single/batch Snap sessions, fee channels preview, transaction status polling; webhook handler to finalize payments
- Data Models: Tagihan, Pembayaran, JenisTagihan, MidtransTransaction
- Resources: Standardized JSON payloads for Tagihan, Pembayaran, JenisTagihan

Key responsibilities:
- Controllers orchestrate requests, validations, permissions, and resource formatting
- Services implement domain rules and external integrations (Midtrans)
- Models define relationships and casts
- Resources normalize response shapes

**Section sources**
- [TagihanController.php:26-567](file://backend/app/Http/Controllers/TagihanController.php#L26-L567)
- [PembayaranController.php:24-496](file://backend/app/Http/Controllers/PembayaranController.php#L24-L496)
- [JenisTagihanController.php:15-179](file://backend/app/Http/Controllers/JenisTagihanController.php#L15-L179)
- [MidtransTransactionController.php:10-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L10-L127)
- [MidtransNotificationController.php:9-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L9-L35)
- [Tagihan.php:8-60](file://backend/app/Models/Tagihan.php#L8-L60)
- [Pembayaran.php:8-53](file://backend/app/Models/Pembayaran.php#L8-L53)
- [JenisTagihan.php:8-48](file://backend/app/Models/JenisTagihan.php#L8-L48)
- [MidtransTransaction.php:7-85](file://backend/app/Models/MidtransTransaction.php#L7-L85)
- [PembayaranResource.php:8-28](file://backend/app/Http/Resources/PembayaranResource.php#L8-L28)
- [TagihanResource.php:8-42](file://backend/app/Http/Resources/TagihanResource.php#L8-L42)
- [JenisTagihanResource.php:8-26](file://backend/app/Http/Resources/JenisTagihanResource.php#L8-L26)

## Architecture Overview
End-to-end flows for invoice and payment operations, including online payment initiation and webhook-driven settlement.

```mermaid
sequenceDiagram
participant C as "Client"
participant R as "Routes"
participant PC as "PembayaranController"
participant TC as "TagihanController"
participant DB as "Database"
participant MTS as "MidtransInitiationService"
participant MID as "Midtrans API"
participant MNS as "MidtransNotificationService"
C->>R : POST /api/pembayaran/lunas/{kode_tagihan}
R->>PC : lunas()
PC->>DB : validate tagihan exists
PC->>TC : call static lunas() to set status=Paid
TC->>DB : update tagihan tmp/status
PC->>DB : create Pembayaran record
PC-->>C : 200 OK with payment resource
C->>R : POST /api/midtrans/transactions
R->>MTC : initiate()
MTC->>MTS : initiate(user, kode_tagihan, amount, channel)
MTS->>DB : lock tagihan, validate, compute fees
MTS->>MID : createSnapTransaction()
MID-->>MTS : snap token + redirect URL
MTS->>DB : persist MidtransTransaction(pending)
MTS-->>MTC : result
MTC-->>C : {order_id, snap_token, redirect_url, ...}
MID-->>R : POST /api/midtrans/notification
R->>MNC : handle()
MNC->>MNS : process payload
MNS->>DB : lock transaction, map status, guard transitions
MNS->>DB : on success -> create Pembayaran(s), update tagihan tmp/status
MNS-->>MNC : ok
MNC-->>MID : 200 OK
```

**Diagram sources**
- [api.php:167-176](file://backend/routes/api.php#L167-L176)
- [PembayaranController.php:302-340](file://backend/app/Http/Controllers/PembayaranController.php#L302-L340)
- [TagihanController.php:322-334](file://backend/app/Http/Controllers/TagihanController.php#L322-L334)
- [MidtransTransactionController.php:17-41](file://backend/app/Http/Controllers/MidtransTransactionController.php#L17-L41)
- [MidtransInitiationService.php:44-237](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L44-L237)
- [MidtransNotificationController.php:20-33](file://backend/app/Http/Controllers/MidtransNotificationController.php#L20-L33)
- [MidtransNotificationService.php:31-150](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L31-L150)

## Detailed Component Analysis

### Invoice (Tagihan) Management
- Endpoints
  - GET /api/tagihan/grouped — Paginated list grouped by siswa with filters (search, jenjang, kelas_id, status, jatuh_tempo range, per_page)
  - GET /api/tagihan — Paginated flat list with search, jenjang, status, sort, direction, per_page
  - GET /api/tagihan/{kode_tagihan} — Get a single invoice
  - POST /api/tagihan — Create invoices for matching students (auto tahun_ajaran if not provided)
  - PATCH /api/tagihan/{kode_tagihan} — Update jenis_tagihan
  - DELETE /api/tagihan/{kode_tagihan} — Delete if no associated payments
  - GET /api/tagihan/siswa — Student portal view with sibling support
  - GET /api/tagihan/export-pdf — Export filtered invoices to PDF
- Business rules
  - Year period scoping via tahun_ajaran_id or all_periods
  - Branch-scoped visibility; non-admin limited to own siswa
  - Status transitions handled by payment endpoints
  - PDF export supports multiple filters and formats dates
- Responses
  - Uses TagihanResource and TagihanGroupedResource for consistent payloads

**Section sources**
- [api.php:156-165](file://backend/routes/api.php#L156-L165)
- [TagihanController.php:36-527](file://backend/app/Http/Controllers/TagihanController.php#L36-L527)
- [TagihanResource.php:8-42](file://backend/app/Http/Resources/TagihanResource.php#L8-L42)

#### Class Diagram: Tagihan Model Relationships
```mermaid
classDiagram
class Tagihan {
+string kode_tagihan
+int jenis_tagihan_id
+string nis
+float tmp
+string status
+int branch_id
+int tahun_ajaran_id
+jenis_tagihan()
+siswa()
+pembayaran()
+tahunAjaran()
+branch()
}
class JenisTagihan {
+int id
+string nama
+date jatuh_tempo
+float jumlah
+tagihan()
+tahunAjaran()
+branch()
}
class Pembayaran {
+string kode_pembayaran
+string kode_tagihan
+date tanggal
+string metode
+float jumlah
+string pembayar
+int branch_id
+tagihan()
+midtransTransaction()
}
class Siswa {
+string nis
+string nama
}
Tagihan --> JenisTagihan : "belongsTo"
Tagihan --> Siswa : "belongsTo"
Tagihan --> Pembayaran : "hasMany"
```

**Diagram sources**
- [Tagihan.php:8-60](file://backend/app/Models/Tagihan.php#L8-L60)
- [JenisTagihan.php:8-48](file://backend/app/Models/JenisTagihan.php#L8-L48)
- [Pembayaran.php:8-53](file://backend/app/Models/Pembayaran.php#L8-L53)

### Payment Processing (Offline and Online)
- Offline payments
  - POST /api/pembayaran/bayar/{kode_tagihan} — Record partial payment; validates against remaining balance; updates tagihan tmp/status
  - POST /api/pembayaran/lunas/{kode_tagihan} — Mark invoice fully paid; creates a payment record; sets status to Lunas
  - POST /api/pembayaran/batch — Batch full payment for multiple invoices within a transaction
  - DELETE /api/pembayaran/{kode_pembayaran} — Refund/delete payment; recalculates tagihan tmp/status; online_midtrans deletions require additional permissions
  - GET /api/pembayaran/grouped — Paginated grouped by siswa with filters (search, jenjang, kelas_id, metode, tahun_ajaran_id, sort latest/oldest)
  - GET /api/pembayaran — Paginated flat list with search and sorting
  - GET /api/pembayaran/siswa — Student portal view including pending Midtrans transactions when include_pending=true
  - GET /api/pembayaran/kwitansi/{kode_pembayaran} — Receipt data resource
- Online payments (Midtrans)
  - GET /api/midtrans/fee-channels — Available channels and optional fee preview
  - POST /api/midtrans/transactions — Initiate single Snap session
  - POST /api/midtrans/transactions/batch — Initiate batch Snap session settling multiple invoices
  - GET /api/midtrans/transactions/{order_id} — Poll transaction status
  - POST /api/midtrans/notification — Webhook endpoint (public, signature-verified) to finalize payments
- Business rules
  - Amount validation vs sisa (remaining balance)
  - Minimum amount and gross amount invariant checks
  - Pending transaction guards prevent duplicate checkout
  - Idempotent payment recording for webhooks
  - Overpayment blocking and transition guards

**Section sources**
- [api.php:167-176](file://backend/routes/api.php#L167-L176)
- [api.php:326-344](file://backend/routes/api.php#L326-L344)
- [PembayaranController.php:119-496](file://backend/app/Http/Controllers/PembayaranController.php#L119-L496)
- [MidtransTransactionController.php:10-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L10-L127)
- [MidtransNotificationController.php:9-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L9-L35)
- [PembayaranResource.php:8-28](file://backend/app/Http/Resources/PembayaranResource.php#L8-L28)

#### Sequence Diagram: Offline Partial Payment Flow
```mermaid
sequenceDiagram
participant C as "Client"
participant R as "Routes"
participant PC as "PembayaranController"
participant TC as "TagihanController"
participant DB as "Database"
C->>R : POST /api/pembayaran/bayar/{kode_tagihan}
R->>PC : bayar()
PC->>DB : load tagihan + jenis_tagihan
PC->>PC : validate amount <= remaining
PC->>DB : create Pembayaran
PC->>TC : call static bayar() to update tmp/status
TC->>DB : update tagihan tmp and status
PC-->>C : 200 OK with payment resource
```

**Diagram sources**
- [PembayaranController.php:343-397](file://backend/app/Http/Controllers/PembayaranController.php#L343-L397)
- [TagihanController.php:337-357](file://backend/app/Http/Controllers/TagihanController.php#L337-L357)

#### Sequence Diagram: Online Single Payment Flow
```mermaid
sequenceDiagram
participant C as "Client"
participant R as "Routes"
participant MTC as "MidtransTransactionController"
participant S as "MidtransInitiationService"
participant DB as "Database"
participant MID as "Midtrans API"
C->>R : POST /api/midtrans/transactions
R->>MTC : initiate()
MTC->>S : initiate(user, kode_tagihan, amount, channel)
S->>DB : lock tagihan, validate ownership and amount
S->>MID : createSnapTransaction()
MID-->>S : {token, redirect_url}
S->>DB : persist MidtransTransaction(pending)
S-->>MTC : result
MTC-->>C : {order_id, snap_token, redirect_url, ...}
```

**Diagram sources**
- [MidtransTransactionController.php:17-41](file://backend/app/Http/Controllers/MidtransTransactionController.php#L17-L41)
- [MidtransInitiationService.php:44-237](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L44-L237)

#### Sequence Diagram: Webhook Settlement Flow
```mermaid
sequenceDiagram
participant MID as "Midtrans API"
participant R as "Routes"
participant MNC as "MidtransNotificationController"
participant S as "MidtransNotificationService"
participant DB as "Database"
MID->>R : POST /api/midtrans/notification
R->>MNC : handle()
MNC->>S : process(payload)
S->>DB : lock transaction, verify signature, map status
alt status == success
S->>DB : create Pembayaran(s), update tagihan tmp/status
else other status
S->>DB : update transaction status only
end
S-->>MNC : ok
MNC-->>MID : 200 OK
```

**Diagram sources**
- [MidtransNotificationController.php:20-33](file://backend/app/Http/Controllers/MidtransNotificationController.php#L20-L33)
- [MidtransNotificationService.php:31-150](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L31-L150)

### Payment Type (Jenis Tagihan) Management
- Endpoints
  - GET /api/jenis-tagihan — List types scoped by year period
  - POST /api/jenis-tagihan — Create type (auto assign active period if missing)
  - GET /api/jenis-tagihan/{id} — Get type
  - PUT /api/jenis-tagihan/{id} — Update type
  - DELETE /api/jenis-tagihan/{id} — Delete if unused
- Business rules
  - Year period scoping and ownership validation
  - Prevent deletion if referenced by invoices

**Section sources**
- [api.php:187-194](file://backend/routes/api.php#L187-L194)
- [JenisTagihanController.php:15-179](file://backend/app/Http/Controllers/JenisTagihanController.php#L15-L179)
- [JenisTagihanResource.php:8-26](file://backend/app/Http/Resources/JenisTagihanResource.php#L8-L26)

### Grouped Views and Exports
- Grouped invoice view: GET /api/tagihan/grouped
- Grouped payment view: GET /api/pembayaran/grouped
- Invoice PDF export: GET /api/tagihan/export-pdf
- Receipt data: GET /api/pembayaran/kwitansi/{kode_pembayaran}

**Section sources**
- [api.php:156-176](file://backend/routes/api.php#L156-L176)
- [TagihanController.php:438-527](file://backend/app/Http/Controllers/TagihanController.php#L438-L527)
- [PembayaranController.php:36-117](file://backend/app/Http/Controllers/PembayaranController.php#L36-L117)

### Data Models and Relationships
```mermaid
erDiagram
TAGIHANS {
string kode_tagihan PK
int jenis_tagihan_id FK
string nis
float tmp
string status
int branch_id
int tahun_ajaran_id
}
PEMBAYARANS {
string kode_pembayaran PK
string kode_tagihan FK
date tanggal
string metode
float jumlah
string pembayar
int branch_id
string midtrans_order_id
}
JENIS_TAGIHANS {
int id PK
string nama
date jatuh_tempo
float jumlah
int branch_id
int tahun_ajaran_id
}
MIDTRANS_TRANSACTIONS {
string order_id PK
string kode_tagihan FK
array batch_items
string nis
int amount_paid
int fee_amount
int gross_amount
string currency
string status
string payment_type
string snap_token
string snap_redirect_url
datetime expired_at
datetime paid_at
int initiator_user_id
int branch_id
array last_raw_response
}
JENIS_TAGIHANS ||--o{ TAGIHANS : "has many"
TAGIHANS ||--o{ PEMBAYARANS : "has many"
TAGIHANS ||--o{ MIDTRANS_TRANSACTIONS : "has many"
MIDTRANS_TRANSACTIONS ||--o| PEMBAYARANS : "one-to-one (first item)"
```

**Diagram sources**
- [Tagihan.php:8-60](file://backend/app/Models/Tagihan.php#L8-L60)
- [Pembayaran.php:8-53](file://backend/app/Models/Pembayaran.php#L8-L53)
- [JenisTagihan.php:8-48](file://backend/app/Models/JenisTagihan.php#L8-L48)
- [MidtransTransaction.php:7-85](file://backend/app/Models/MidtransTransaction.php#L7-L85)

## Dependency Analysis
- Controllers depend on models and resources
- Midtrans controllers depend on initiation and notification services
- Notification service depends on signature verification, status mapping, transition guards, logging, and fee computation
- All financial mutations use database transactions and locks to ensure consistency

```mermaid
graph LR
PC["PembayaranController"] --> TModel["Tagihan Model"]
PC --> PModel["Pembayaran Model"]
PC --> PR["PembayaranResource"]
TC["TagihanController"] --> TModel
TC --> TR["TagihanResource"]
JTC["JenisTagihanController"] --> JTModel["JenisTagihan Model"]
JTC --> JTR["JenisTagihanResource"]
MTC["MidtransTransactionController"] --> MIT["MidtransInitiationService"]
MNC["MidtransNotificationController"] --> MIN["MidtransNotificationService"]
MIT --> MTModel["MidtransTransaction Model"]
MIN --> MTModel
MIN --> PModel
MIN --> TModel
```

**Diagram sources**
- [PembayaranController.php:24-496](file://backend/app/Http/Controllers/PembayaranController.php#L24-L496)
- [TagihanController.php:26-567](file://backend/app/Http/Controllers/TagihanController.php#L26-L567)
- [JenisTagihanController.php:15-179](file://backend/app/Http/Controllers/JenisTagihanController.php#L15-L179)
- [MidtransTransactionController.php:10-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L10-L127)
- [MidtransNotificationController.php:9-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L9-L35)
- [MidtransInitiationService.php:22-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L22-L473)
- [MidtransNotificationService.php:16-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L16-L284)

**Section sources**
- [api.php:47-345](file://backend/routes/api.php#L47-L345)

## Performance Considerations
- Use pagination and selective field loading in queries to reduce payload size
- Prefer grouped endpoints to minimize N+1 queries
- Leverage database transactions and row-level locks for concurrency safety during payment processing
- Avoid unnecessary eager loads; only include required relationships
- For large exports, consider background jobs and streaming responses where applicable

## Troubleshooting Guide
Common issues and resolutions:
- Invalid signature on webhook: Ensure server key is configured and request body is intact
- Amount mismatch: Verify gross_amount matches expected value before accepting settlement
- Overpayment blocked: Check sisa calculation and ensure amount does not exceed remaining balance
- Forbidden access: Confirm user’s siswa NIS matches target tagihan.nis for online payments
- Duplicate pending transaction: If a pending transaction exists, complete or cancel it before initiating a new one
- Deletion restrictions: Online_midtrans payments require specific permissions to delete; offline payments can be deleted if they exist

Operational checks:
- Validate feature flags (midtrans.enabled, webhook_enabled)
- Inspect logs for outbound/inbound events and error codes
- Review transaction state transitions using admin endpoints

**Section sources**
- [MidtransNotificationService.php:31-150](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L31-L150)
- [MidtransInitiationService.php:44-237](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L44-L237)
- [PembayaranController.php:244-299](file://backend/app/Http/Controllers/PembayaranController.php#L244-L299)

## Conclusion
The billing and payment system provides robust APIs for managing invoices, recording payments (offline and online), configuring payment types, and exporting reports. It enforces strong data integrity through transactions, locks, and validation, while offering practical tools for reconciliation and auditing via detailed transaction records and webhook processing.

## Appendices

### Practical Workflows

- Create an invoice and record a partial offline payment
  - Create invoice: POST /api/tagihan
  - Record partial payment: POST /api/pembayaran/bayar/{kode_tagihan}
  - Verify status: GET /api/tagihan/{kode_tagihan}

- Full offline payment (single)
  - POST /api/pembayaran/lunas/{kode_tagihan}
  - Retrieve receipt data: GET /api/pembayaran/kwitansi/{kode_pembayaran}

- Batch full offline payment
  - POST /api/pembayaran/batch with list of kode_tagihan

- Online payment (single)
  - GET /api/midtrans/fee-channels?amount=...
  - POST /api/midtrans/transactions
  - Poll status: GET /api/midtrans/transactions/{order_id}
  - Finalization occurs automatically via webhook

- Online payment (batch)
  - POST /api/midtrans/transactions/batch with kode_tagihan_list
  - Poll status and await webhook settlement

- Refund (delete payment)
  - DELETE /api/pembayaran/{kode_pembayaran}
  - Note: online_midtrans deletions require additional permissions

### Financial Reporting Queries
- Filter invoices by status, jatuh_tempo range, jenjang, kelas_id, and year period
- Group payments by siswa with method and period filters
- Export invoice report to PDF with selected filters
- Generate receipt data for audit trails

[No sources needed since this section provides general guidance]