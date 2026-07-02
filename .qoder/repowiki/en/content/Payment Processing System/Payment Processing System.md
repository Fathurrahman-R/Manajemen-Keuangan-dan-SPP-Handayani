# Payment Processing System

<cite>
**Referenced Files in This Document**
- [Pembayaran.php](file://backend/app/Models/Pembayaran.php)
- [2025_11_14_102319_create_pembayarans_table.php](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php)
- [2026_06_22_000003_add_midtrans_columns_to_pembayarans_table.php](file://backend/database/migrations/2026_06_22_000003_add_midtrans_columns_to_pembayarans_table.php)
- [GenerateKodePembayaran.php](file://backend/app/Services/GenerateKodePembayaran.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [MidtransClient.php](file://backend/app/Services/Midtrans/MidtransClient.php)
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransNotificationService.php](file://backend/app/Services/Midtrans/MidtransNotificationService.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [MidtransTransactionController.php](file://backend/app/Http/Controllers/MidtransTransactionController.php)
- [MidtransNotificationController.php](file://backend/app/Http/Controllers/MidtransNotificationController.php)
- [PembayaranController.php](file://backend/app/Http/Controllers/PembayaranController.php)
- [BatchPaymentRequest.php](file://backend/app/Http/Requests/BatchPaymentRequest.php)
- [PembayaranRecorded.php](file://backend/app/Events/PembayaranRecorded.php)
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

## Introduction
This document explains the Handayani payment processing system, covering:
- The Pembayaran (payment) model and schema
- Online and offline payment methods
- Payment code generation
- End-to-end workflows from initiation to completion
- Status transitions and reconciliation
- Midtrans integration for online payments
- Fee calculation and channel configuration
- Batch payments and receipt generation
- Guidelines for adding new payment methods

## Project Structure
The payment subsystem spans models, services, controllers, requests, events, config, and migrations. Key areas:
- Models: Pembayaran, MidtransTransaction
- Services: MidtransInitiationService, MidtransNotificationService, MidtransFeeService, GenerateKodePembayaran
- Controllers: PembayaranController, MidtransTransactionController, MidtransNotificationController
- Config: midtrans.php
- Migrations: pembayaran table creation and Midtrans columns addition
- Events: PembayaranRecorded

```mermaid
graph TB
subgraph "Core"
P["Pembayaran Model"]
MTX["MidtransTransaction Model"]
GKP["GenerateKodePembayaran Service"]
end
subgraph "Online Payments"
MIC["MidtransClient Interface"]
MIS["MidtransInitiationService"]
MNS["MidtransNotificationService"]
MFS["MidtransFeeService"]
MTC["MidtransTransactionController"]
MNC["MidtransNotificationController"]
end
subgraph "Offline & Admin"
PC["PembayaranController"]
BPR["BatchPaymentRequest"]
end
CFG["midtrans.php Config"]
PC --> GKP
PC --> P
PC --> MTX
MTC --> MIS
MTC --> MFS
MNC --> MNS
MNS --> P
MNS --> MTX
MIS --> MIC
MIS --> MFS
MIS --> MTX
MIS --> CFG
MNS --> CFG
MFS --> CFG
```

**Diagram sources**
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransInitiationService.php:1-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L1-L473)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [PembayaranController.php:1-496](file://backend/app/Http/Controllers/PembayaranController.php#L1-L496)
- [BatchPaymentRequest.php:1-76](file://backend/app/Http/Requests/BatchPaymentRequest.php#L1-L76)
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)

**Section sources**
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)

## Core Components
- Pembayaran model: Represents a single payment record with fields for payment code, tagihan reference, date, method, amount, payer name, branch, and optional Midtrans order id. It defines relationships to Tagihan, Branch, and MidtransTransaction.
- MidtransTransaction model: Tracks online transactions with order id, amounts, status, payment type, Snap token/redirect, expiry/paid timestamps, batch items, and audit logs.
- GenerateKodePembayaran service: Generates unique payment codes per month with table locking to avoid collisions.
- Midtrans fee service: Computes admin fees per channel (flat or percent+flat), exposes available channels with previews, and asserts gross amount invariants.
- Midtrans initiation service: Orchestrates online payment initiation, validates ownership and amounts, computes fees, persists transaction, calls Midtrans Snap, and returns redirect info.
- Midtrans notification service: Verifies webhook signatures, maps statuses, enforces state transitions, updates transaction, records Pembayaran(s), reconciles tagihan tmp/status, and dispatches events.
- Controllers: Expose APIs for initiating online payments, listing fee channels, handling webhooks, and recording offline payments; also provide grouped/list views and receipts.

**Section sources**
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [MidtransInitiationService.php:1-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L1-L473)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [PembayaranController.php:1-496](file://backend/app/Http/Controllers/PembayaranController.php#L1-L496)

## Architecture Overview
End-to-end flows:
- Offline payments: Admin records one or multiple payments, generating payment codes and updating tagihan totals and status.
- Online payments: Client initiates via API, service calculates fees, creates MidtransTransaction, calls Midtrans Snap, then waits for webhook to reconcile and create Pembayaran records.

```mermaid
sequenceDiagram
participant Client as "Portal/Admin"
participant API as "MidtransTransactionController"
participant Init as "MidtransInitiationService"
participant Fee as "MidtransFeeService"
participant DB as "DB"
participant MT as "MidtransSnap"
participant Webhook as "MidtransNotificationController"
participant Notif as "MidtransNotificationService"
participant Pay as "Pembayaran"
participant Tx as "MidtransTransaction"
Client->>API : POST /api/midtrans/transactions {kode_tagihan, amount_paid, payment_channel}
API->>Init : initiate(user, kode_tagihan, amount_paid, channel)
Init->>Fee : computeFee(amount_paid, channel)
Fee-->>Init : fee_amount
Init->>DB : lockForUpdate(tagihan)
Init->>DB : create MidtransTransaction(status=pending)
Init->>MT : createSnapTransaction(gross=amount+fee)
MT-->>Init : {token, redirect_url}
Init-->>API : {order_id, snap_token, redirect_url, amounts}
API-->>Client : response with client_key + redirect
Note over MT,Webhook : Midtrans sends webhook on status change
MT-->>Webhook : POST /api/midtrans/notification
Webhook->>Notif : handle(payload, rawBody, ip)
Notif->>Notif : verify signature, map status, guard transitions
Notif->>Tx : update status, payment_type, paid_at if success
alt success
Notif->>Pay : create Pembayaran(s) (single or batch)
Notif->>DB : update tagihan tmp/status
Notif-->>Webhook : 200 OK
else invalid
Notif-->>Webhook : error code (e.g., INVALID_SIGNATURE)
end
```

**Diagram sources**
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransInitiationService.php:1-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L1-L473)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)

## Detailed Component Analysis

### Pembayaran Model and Schema
- Primary key is a string payment code generated by GenerateKodePembayaran.
- Fields include tagihan link, date, method enum (offline, online_midtrans), amount, payer, branch, and optional midtrans_order_id for traceability.
- Relationships: belongsTo Tagihan, Branch; hasOne MidtransTransaction via midtrans_order_id.

```mermaid
classDiagram
class Pembayaran {
+string kode_pembayaran
+string kode_tagihan
+date tanggal
+enum metode
+decimal jumlah
+string pembayar
+int branch_id
+string midtrans_order_id
+tagihan()
+branch()
+midtransTransaction()
}
class MidtransTransaction {
+string order_id
+string kode_tagihan
+array batch_items
+int amount_paid
+int fee_amount
+int gross_amount
+string status
+string payment_type
+datetime expired_at
+datetime paid_at
+isBatch() bool
+scopePendingInFlight(query)
+tagihan()
+pembayaran()
+logs()
+initiator()
}
Pembayaran --> MidtransTransaction : "linked by midtrans_order_id"
```

**Diagram sources**
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)

**Section sources**
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)
- [2026_06_22_000003_add_midtrans_columns_to_pembayarans_table.php:1-37](file://backend/database/migrations/2026_06_22_000003_add_midtrans_columns_to_pembayarans_table.php#L1-L37)

### Payment Code Generation
- Generates codes like PAY-YYMM-NNNN using current year/month prefix and zero-padded sequence.
- Uses explicit table write lock to ensure uniqueness across concurrent requests.

```mermaid
flowchart TD
Start(["generate()"]) --> Prefix["Build prefix 'PAY-YYMM'"]
Prefix --> Lock["LOCK TABLES pembayarans WRITE"]
Lock --> Query["Find latest matching prefix"]
Query --> Next{"Found?"}
Next -- "No" --> Inc["increment = 1"]
Next -- "Yes" --> Inc["increment = lastNumber + 1"]
Inc --> Pad["Zero-pad to 4 digits"]
Pad --> Code["Compose 'PAY-YYMM-NNNN'"]
Code --> Unlock["UNLOCK TABLES; autocommit=1"]
Unlock --> Return(["Return code"])
```

**Diagram sources**
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)

**Section sources**
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)

### Online Payment Initiation Flow
- Validates feature flags and configuration.
- Loads and locks target tagihan, verifies user ownership, computes remaining balance, checks minimum amount and no pending transaction.
- Calculates fee based on selected channel, persists MidtransTransaction, builds Snap payload, calls Midtrans Snap, and returns redirect info.

```mermaid
sequenceDiagram
participant C as "Client"
participant Ctrl as "MidtransTransactionController"
participant Svc as "MidtransInitiationService"
participant F as "MidtransFeeService"
participant DB as "DB"
participant MT as "MidtransSnap"
C->>Ctrl : POST /api/midtrans/transactions
Ctrl->>Svc : initiate(user, kode_tagihan, amount_paid, channel)
Svc->>F : computeFee(amount_paid, channel)
F-->>Svc : fee_amount
Svc->>DB : lockForUpdate(tagihan)
Svc->>DB : create MidtransTransaction(pending)
Svc->>MT : createSnapTransaction(gross=amount+fee)
MT-->>Svc : {token, redirect_url}
Svc-->>Ctrl : InitiationResult
Ctrl-->>C : {order_id, snap_token, redirect_url, amounts, client_key}
```

**Diagram sources**
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransInitiationService.php:1-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L1-L473)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)

**Section sources**
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransInitiationService.php:1-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L1-L473)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)

### Webhook Handling and Reconciliation
- Verifies signature, loads transaction, maps external status to internal states, enforces allowed transitions, updates transaction, and on success records Pembayaran(s).
- For batch transactions, creates one Pembayaran per item and updates each tagihan’s tmp and status accordingly.
- Dispatches PembayaranRecorded event for notifications.

```mermaid
sequenceDiagram
participant MT as "Midtrans"
participant NC as "MidtransNotificationController"
participant NS as "MidtransNotificationService"
participant TX as "MidtransTransaction"
participant PY as "Pembayaran"
participant TG as "Tagihan"
MT->>NC : POST /api/midtrans/notification
NC->>NS : handle(payload, rawBody, ip)
NS->>NS : verify signature
NS->>TX : lockForUpdate(order_id)
NS->>NS : map status + transition guard
NS->>TX : update status/payment_type/paid_at
alt success
NS->>PY : create Pembayaran (single or per batch item)
NS->>TG : update tmp and status
NS-->>NC : 200 OK
else invalid
NS-->>NC : error code
end
```

**Diagram sources**
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)

**Section sources**
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)

### Offline Payments and Batch Recording
- Single offline payment: validate tagihan, prevent overpayment, create Pembayaran, update tagihan tmp/status, dispatch event.
- Batch offline payment: validate list, ensure none are already Lunas, loop through tagihan creating Pembayaran entries, set all to Lunas, dispatch events.

```mermaid
flowchart TD
A["Start batchLunas(request)"] --> V["Validate request (BatchPaymentRequest)"]
V --> L["Load tagihan list with jenis_tagihan"]
L --> CheckBranch["Verify branch ownership"]
CheckBranch --> CheckStatus{"Any already Lunas?"}
CheckStatus -- "Yes" --> Err["Return 400 error"]
CheckStatus -- "No" --> Txn["Begin DB transaction"]
Txn --> Loop["For each tagihan"]
Loop --> Calc["jumlah = jenis_tagihan.jumlah - tagihan.tmp"]
Calc --> Create["Create Pembayaran (code, date, method, amount, payer, branch)"]
Create --> UpdateT["Update tagihan.status='Lunas', tmp=jumlah"]
UpdateT --> Next{"More tagihan?"}
Next -- "Yes" --> Loop
Next -- "No" --> Commit["Commit transaction"]
Commit --> Events["Dispatch PembayaranRecorded per entry"]
Events --> Resp["Return collection resource"]
```

**Diagram sources**
- [PembayaranController.php:170-241](file://backend/app/Http/Controllers/PembayaranController.php#L170-L241)
- [BatchPaymentRequest.php:1-76](file://backend/app/Http/Requests/BatchPaymentRequest.php#L1-L76)

**Section sources**
- [PembayaranController.php:170-241](file://backend/app/Http/Controllers/PembayaranController.php#L170-L241)
- [BatchPaymentRequest.php:1-76](file://backend/app/Http/Requests/BatchPaymentRequest.php#L1-L76)

### Receipt Generation
- Kwitansi endpoint retrieves a Pembayaran by code and returns structured data suitable for receipt rendering.

```mermaid
sequenceDiagram
participant U as "User"
participant PC as "PembayaranController"
participant DB as "DB"
participant R as "KwitansiResource"
U->>PC : GET /pembayaran/{kode_pembayaran}/kwitansi
PC->>DB : find Pembayaran with tagihan
DB-->>PC : Pembayaran
PC->>R : build KwitansiResource
R-->>U : receipt data
```

**Diagram sources**
- [PembayaranController.php:400-410](file://backend/app/Http/Controllers/PembayaranController.php#L400-L410)

**Section sources**
- [PembayaranController.php:400-410](file://backend/app/Http/Controllers/PembayaranController.php#L400-L410)

### Midtrans Configuration and Channels
- Feature toggles: enable/disable gateway and webhooks independently.
- Credentials: server_key, client_key, merchant_id.
- Transaction settings: default channel, min amount, expiry hours, order prefix, finish URL, log retention.
- Fee channels: per-channel flat or percent+flat with environment overrides.

```mermaid
classDiagram
class MidtransConfig {
+bool enabled
+bool webhook_enabled
+string environment
+string server_key
+string client_key
+string merchant_id
+int fee_flat
+map fee_channels
+string default_channel
+int min_amount
+int expiry_hours
+string order_prefix
+string finish_url
+int log_retention_days
}
```

**Diagram sources**
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)

**Section sources**
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)

### Practical Examples

- Process an online payment:
  - Call initiation endpoint with kode_tagihan, amount_paid, and optional payment_channel.
  - Use returned snap_token and redirect_url to open Midtrans Snap.
  - On webhook success, check Pembayaran created and tagihan status updated.

- Process an offline payment:
  - Call single payment endpoint with kode_tagihan, jumlah, metode, pembayar.
  - Verify response includes new Pembayaran and updated tagihan status.

- Process batch offline payments:
  - Submit array of kode_tagihan with shared metode and pembayar.
  - All selected tagihan become Lunas and multiple Pembayaran records are created.

- Handle failures:
  - Inspect controller/service exceptions for reasons such as insufficient remaining balance, below minimum amount, forbidden access, or Midtrans unavailability.
  - For webhooks, invalid signature or amount mismatch will return specific error codes.

- Generate a receipt:
  - Request kwitansi by kode_pembayaran to retrieve receipt-ready data.

[No sources needed since this section provides general guidance]

### Adding New Payment Methods
- Offline methods:
  - Extend the metode enum in the pembayaran migration to include the new method.
  - Update validation rules in BatchPaymentRequest and any other request classes that validate metode.
  - Ensure controllers accept and persist the new method value.

- Online methods (Midtrans):
  - Add a new channel entry under fee_channels in midtrans.php with label, type, and fee parameters.
  - If restricting Snap UI to a specific channel, extend resolveEnabledPayments mapping in MidtransInitiationService.
  - Optionally add environment variables for per-channel fee overrides.

**Section sources**
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)
- [BatchPaymentRequest.php:1-76](file://backend/app/Http/Requests/BatchPaymentRequest.php#L1-L76)
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)
- [MidtransInitiationService.php:450-471](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L450-L471)

## Dependency Analysis
Key dependencies and interactions:
- Controllers depend on services for business logic.
- Services depend on config and models.
- Notification service depends on signature verification, status mapping, and transition guards.
- Fee service reads runtime config to compute fees and expose channel metadata.

```mermaid
graph LR
PC["PembayaranController"] --> GKP["GenerateKodePembayaran"]
PC --> P["Pembayaran"]
PC --> MTX["MidtransTransaction"]
MTC["MidtransTransactionController"] --> MIS["MidtransInitiationService"]
MTC --> MFS["MidtransFeeService"]
MNC["MidtransNotificationController"] --> MNS["MidtransNotificationService"]
MNS --> P
MNS --> MTX
MIS --> MIC["MidtransClient"]
MIS --> MFS
MIS --> MTX
MFS --> CFG["midtrans.php"]
MIS --> CFG
MNS --> CFG
```

**Diagram sources**
- [PembayaranController.php:1-496](file://backend/app/Http/Controllers/PembayaranController.php#L1-L496)
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransInitiationService.php:1-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L1-L473)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)

**Section sources**
- [PembayaranController.php:1-496](file://backend/app/Http/Controllers/PembayaranController.php#L1-L496)
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransInitiationService.php:1-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L1-L473)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)

## Performance Considerations
- Use database transactions for batch operations to ensure consistency and reduce round-trips.
- Prefer selective field loading and eager loading only where necessary to minimize query overhead.
- Leverage indexes on frequently filtered columns (e.g., metode, kode_pembayaran, kode_tagihan).
- Avoid unnecessary recomputation by caching or reading config once per request when appropriate.
- For high concurrency, rely on row-level locking (lockForUpdate) and short transactions to reduce contention.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common issues and resolutions:
- Invalid signature on webhook:
  - Ensure server_key matches Midtrans configuration and payload integrity is intact.
- Amount mismatch:
  - Verify gross_amount equals amount_paid + fee_amount and that fee computation aligns with configured channels.
- Overpayment blocked:
  - Confirm sisa_tagihan before recording; adjust payment amount or reverse prior payments if needed.
- Forbidden access:
  - Validate user’s siswa NIS matches the tagihan.nis for online initiation and status queries.
- Pending transaction exists:
  - Wait for existing transaction to complete or expire before initiating a new one.
- Midtrans unavailable:
  - Retry after backoff; inspect logs for outbound charge errors.

**Section sources**
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransInitiationService.php:1-473](file://backend/app/Services/Midtrans/MidtransInitiationService.php#L1-L473)
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)

## Conclusion
The Handayani payment system cleanly separates offline and online flows while maintaining consistent reconciliation and auditability. Online payments integrate with Midtrans via robust initiation, fee calculation, and webhook processing. Offline payments support efficient batch recording. Extensibility is straightforward through configuration and minimal code changes for new methods.