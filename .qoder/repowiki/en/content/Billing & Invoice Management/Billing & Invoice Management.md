# Billing & Invoice Management

<cite>
**Referenced Files in This Document**
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [JenisTagihan.php](file://backend/app/Models/JenisTagihan.php)
- [Pembayaran.php](file://backend/app/Models/Pembayaran.php)
- [TagihanController.php](file://backend/app/Http/Controllers/TagihanController.php)
- [JenisTagihanController.php](file://backend/app/Http/Controllers/JenisTagihanController.php)
- [PembayaranController.php](file://backend/app/Http/Controllers/PembayaranController.php)
- [GenerateKodeTagihan.php](file://backend/app/Services/GenerateKodeTagihan.php)
- [GenerateKodePembayaran.php](file://backend/app/Services/GenerateKodePembayaran.php)
- [GenerateSejumlahKwitansi.php](file://backend/app/Services/GenerateSejumlahKwitansi.php)
- [GenerateKeteranganKwitansi.php](file://backend/app/Services/GenerateKeteranganKwitansi.php)
- [KwitansiResource.php](file://backend/app/Http/Resources/KwitansiResource.php)
- [2025_11_14_093831_create_jenis_tagihans_table.php](file://backend/database/migrations/2025_11_14_093831_create_jenis_tagihans_table.php)
- [2025_11_14_094745_create_tagihans_table.php](file://backend/database/migrations/2025_11_14_094745_create_tagihans_table.php)
- [2025_11_14_102319_create_pembayarans_table.php](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php)
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
This document explains the billing and invoice management system in Handayani, focusing on:
- Tagihan (invoice) model structure and lifecycle
- JenisTagihan (payment type) configuration and scoping by academic year and branch
- Automated invoice generation for student groups
- Invoice coding system and status tracking
- Batch operations for mass invoice creation and payments
- Integration with student data and academic years
- Receipt generation (kwitansi) and customization options
- Reconciliation processes and guidelines to extend billing functionality

## Project Structure
The billing domain is implemented across models, controllers, services, resources, and database migrations:
- Models define entities and relationships (Tagihan, JenisTagihan, Pembayaran)
- Controllers expose API endpoints for CRUD, batch operations, and views
- Services generate unique codes and receipt content
- Resources format responses including receipt details
- Migrations define schema evolution and constraints

```mermaid
graph TB
subgraph "Models"
T["Tagihan"]
JT["JenisTagihan"]
P["Pembayaran"]
end
subgraph "Controllers"
TC["TagihanController"]
JTC["JenisTagihanController"]
PC["PembayaranController"]
end
subgraph "Services"
GKT["GenerateKodeTagihan"]
GKP["GenerateKodePembayaran"]
GSK["GenerateSejumlahKwitansi"]
GKK["GenerateKeteranganKwitansi"]
end
subgraph "Resources"
KR["KwitansiResource"]
end
subgraph "Migrations"
MJT["create_jenis_tagihans_table"]
MT["create_tagihans_table"]
MP["create_pembayarans_table"]
end
JT --> T
T --> P
TC --> T
JTC --> JT
PC --> P
TC --> GKT
PC --> GKP
KR --> GSK
KR --> GKK
MJT --> JT
MT --> T
MP --> P
```

**Diagram sources**
- [Tagihan.php:1-60](file://backend/app/Models/Tagihan.php#L1-L60)
- [JenisTagihan.php:1-48](file://backend/app/Models/JenisTagihan.php#L1-L48)
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [TagihanController.php:1-567](file://backend/app/Http/Controllers/TagihanController.php#L1-L567)
- [JenisTagihanController.php:1-179](file://backend/app/Http/Controllers/JenisTagihanController.php#L1-L179)
- [PembayaranController.php:1-496](file://backend/app/Http/Controllers/PembayaranController.php#L1-L496)
- [GenerateKodeTagihan.php:1-46](file://backend/app/Services/GenerateKodeTagihan.php#L1-L46)
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)
- [GenerateSejumlahKwitansi.php:1-96](file://backend/app/Services/GenerateSejumlahKwitansi.php#L1-L96)
- [GenerateKeteranganKwitansi.php:1-35](file://backend/app/Services/GenerateKeteranganKwitansi.php#L1-L35)
- [KwitansiResource.php:1-31](file://backend/app/Http/Resources/KwitansiResource.php#L1-L31)
- [2025_11_14_093831_create_jenis_tagihans_table.php:1-31](file://backend/database/migrations/2025_11_14_093831_create_jenis_tagihans_table.php#L1-L31)
- [2025_11_14_094745_create_tagihans_table.php:1-33](file://backend/database/migrations/2025_11_14_094745_create_tagihans_table.php#L1-L33)
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)

**Section sources**
- [Tagihan.php:1-60](file://backend/app/Models/Tagihan.php#L1-L60)
- [JenisTagihan.php:1-48](file://backend/app/Models/JenisTagihan.php#L1-L48)
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [TagihanController.php:1-567](file://backend/app/Http/Controllers/TagihanController.php#L1-L567)
- [JenisTagihanController.php:1-179](file://backend/app/Http/Controllers/JenisTagihanController.php#L1-L179)
- [PembayaranController.php:1-496](file://backend/app/Http/Controllers/PembayaranController.php#L1-L496)
- [GenerateKodeTagihan.php:1-46](file://backend/app/Services/GenerateKodeTagihan.php#L1-L46)
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)
- [GenerateSejumlahKwitansi.php:1-96](file://backend/app/Services/GenerateSejumlahKwitansi.php#L1-L96)
- [GenerateKeteranganKwitansi.php:1-35](file://backend/app/Services/GenerateKeteranganKwitansi.php#L1-L35)
- [KwitansiResource.php:1-31](file://backend/app/Http/Resources/KwitansiResource.php#L1-L31)
- [2025_11_14_093831_create_jenis_tagihans_table.php:1-31](file://backend/database/migrations/2025_11_14_093831_create_jenis_tagihans_table.php#L1-L31)
- [2025_11_14_094745_create_tagihans_table.php:1-33](file://backend/database/migrations/2025_11_14_094745_create_tagihans_table.php#L1-L33)
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)

## Core Components
- Tagihan (Invoice): Represents an individual invoice per student for a payment type. It tracks total amount due via related JenisTagihan, accumulated paid amount (tmp), and current status.
- JenisTagihan (Payment Type): Defines a chargeable item (e.g., tuition, activity fee) with a fixed amount and due date, scoped to a branch and academic year.
- Pembayaran (Payment): Records each payment against an invoice, including method, amount, payer name, and date.

Key behaviors:
- Status transitions are driven by accumulated payments vs. total amount.
- Invoices are created in bulk for selected student groups.
- Payments can be recorded individually or in batches.
- Receipts (kwitansi) are generated from payment records with Indonesian text formatting.

**Section sources**
- [Tagihan.php:1-60](file://backend/app/Models/Tagihan.php#L1-L60)
- [JenisTagihan.php:1-48](file://backend/app/Models/JenisTagihan.php#L1-L48)
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)

## Architecture Overview
The billing flow spans controllers orchestrating business logic, services generating identifiers and receipts, and models persisting state.

```mermaid
sequenceDiagram
participant Admin as "Admin UI/API"
participant TC as "TagihanController"
participant GKT as "GenerateKodeTagihan"
participant DB as "Database"
participant PC as "PembayaranController"
participant KR as "KwitansiResource"
participant GSK as "GenerateSejumlahKwitansi"
participant GKK as "GenerateKeteranganKwitansi"
Admin->>TC : Create invoices for group
TC->>GKT : Generate unique kode_tagihan
GKT-->>TC : kode_tagihan
TC->>DB : Persist Tagihan rows
TC-->>Admin : Created invoices
Admin->>PC : Record payment (single/batch)
PC->>DB : Create Pembayaran row(s)
PC->>DB : Update Tagihan tmp/status
PC-->>Admin : Payment record(s)
Admin->>PC : Request kwitansi
PC->>KR : Build receipt resource
KR->>GSK : Convert amount to words
KR->>GKK : Build description
KR-->>Admin : Receipt payload
```

**Diagram sources**
- [TagihanController.php:220-275](file://backend/app/Http/Controllers/TagihanController.php#L220-L275)
- [GenerateKodeTagihan.php:1-46](file://backend/app/Services/GenerateKodeTagihan.php#L1-L46)
- [PembayaranController.php:170-241](file://backend/app/Http/Controllers/PembayaranController.php#L170-L241)
- [KwitansiResource.php:1-31](file://backend/app/Http/Resources/KwitansiResource.php#L1-L31)
- [GenerateSejumlahKwitansi.php:1-96](file://backend/app/Services/GenerateSejumlahKwitansi.php#L1-L96)
- [GenerateKeteranganKwitansi.php:1-35](file://backend/app/Services/GenerateKeteranganKwitansi.php#L1-L35)

## Detailed Component Analysis

### Data Model Relationships
```mermaid
classDiagram
class JenisTagihan {
+int id
+string nama
+date jatuh_tempo
+decimal jumlah
+int branch_id
+int tahun_ajaran_id
+tagihan()
+tahunAjaran()
+branch()
}
class Tagihan {
+string kode_tagihan
+int jenis_tagihan_id
+string nis
+decimal tmp
+enum status
+int branch_id
+int tahun_ajaran_id
+jenis_tagihan()
+siswa()
+pembayaran()
+tahunAjaran()
+branch()
}
class Pembayaran {
+string kode_pembayaran
+string kode_tagihan
+date tanggal
+enum metode
+decimal jumlah
+string pembayar
+int branch_id
+tagihan()
+branch()
}
JenisTagihan "1" --> "many" Tagihan : "hasMany"
Tagihan "1" --> "many" Pembayaran : "hasMany"
```

**Diagram sources**
- [JenisTagihan.php:1-48](file://backend/app/Models/JenisTagihan.php#L1-L48)
- [Tagihan.php:1-60](file://backend/app/Models/Tagihan.php#L1-L60)
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)

**Section sources**
- [JenisTagihan.php:1-48](file://backend/app/Models/JenisTagihan.php#L1-L48)
- [Tagihan.php:1-60](file://backend/app/Models/Tagihan.php#L1-L60)
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)

### Invoice Coding System
- Kode tagihan: Generated with a time-based prefix and sequential number; thread-safe via table lock.
- Kode pembayaran: Similar approach for payment receipts.

```mermaid
flowchart TD
Start(["Generate Code"]) --> Prefix["Build prefix from year/month"]
Prefix --> Lock["Lock target table WRITE"]
Lock --> QueryLatest["Query latest code with same prefix"]
QueryLatest --> NextNum{"Found?"}
NextNum --> |No| Inc1["Start at 1"]
NextNum --> |Yes| IncN["Increment last 4 digits"]
Inc1 --> Pad["Zero-pad to 4 digits"]
IncN --> Pad
Pad --> Assemble["Assemble final code"]
Assemble --> Unlock["Unlock table and restore autocommit"]
Unlock --> End(["Return code"])
```

**Diagram sources**
- [GenerateKodeTagihan.php:1-46](file://backend/app/Services/GenerateKodeTagihan.php#L1-L46)
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)

**Section sources**
- [GenerateKodeTagihan.php:1-46](file://backend/app/Services/GenerateKodeTagihan.php#L1-L46)
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)

### Invoice Lifecycle and Status Tracking
- Initial status: Belum Dibayar (unpaid).
- Partial payments update tmp and set status to Belum Lunas if not fully paid.
- Full payment sets status to Lunas and updates tmp to total amount.
- Deletion guards prevent removing invoices with existing payments.

```mermaid
stateDiagram-v2
[*] --> Belum_Dibayar
Belum_Dibayar --> Belum_Lunas : "Partial payment recorded"
Belum_Lunas --> Lunas : "Accumulated equals total"
Belum_Dibayar --> Lunas : "Full payment recorded"
Lunas --> [*]
```

**Diagram sources**
- [TagihanController.php:322-357](file://backend/app/Http/Controllers/TagihanController.php#L322-L357)
- [PembayaranController.php:302-397](file://backend/app/Http/Controllers/PembayaranController.php#L302-L397)

**Section sources**
- [TagihanController.php:322-357](file://backend/app/Http/Controllers/TagihanController.php#L322-L357)
- [PembayaranController.php:302-397](file://backend/app/Http/Controllers/PembayaranController.php#L302-L397)

### Automated Invoice Generation for Student Groups
- Bulk creation endpoint accepts filters (jenjang, kelas_id, kategori_id) to select students within a branch.
- For each matched student, a new Tagihan is created with a unique code and assigned to the active academic year (or explicitly provided).
- Notifications are dispatched upon creation.

```mermaid
sequenceDiagram
participant Admin as "Admin"
participant TC as "TagihanController.create"
participant Siswa as "Siswa query"
participant GKT as "GenerateKodeTagihan"
participant DB as "Database"
Admin->>TC : POST create {jenjang, kelas_id, kategori_id, jenis_tagihan_id}
TC->>Siswa : Filter by branch + criteria
Siswa-->>TC : List of students
loop For each student
TC->>GKT : Generate kode_tagihan
GKT-->>TC : kode_tagihan
TC->>DB : Insert Tagihan row
TC-->>Admin : Created invoice
end
```

**Diagram sources**
- [TagihanController.php:220-275](file://backend/app/Http/Controllers/TagihanController.php#L220-L275)
- [GenerateKodeTagihan.php:1-46](file://backend/app/Services/GenerateKodeTagihan.php#L1-L46)

**Section sources**
- [TagihanController.php:220-275](file://backend/app/Http/Controllers/TagihanController.php#L220-L275)

### Batch Operations for Mass Invoice Creation and Payments
- Mass invoice creation: Use the create endpoint with group filters to generate invoices for many students at once.
- Batch payment: Endpoint creates multiple Pembayaran records and updates corresponding Tagihan statuses atomically within a transaction.

```mermaid
sequenceDiagram
participant Admin as "Admin"
participant PC as "PembayaranController.batchLunas"
participant DB as "Database"
Admin->>PC : POST batchLunas {kode_tagihan[], metode, pembayar}
PC->>DB : Begin transaction
loop For each tagihan
PC->>DB : Create Pembayaran
PC->>DB : Update Tagihan status=tmp=total
end
PC->>DB : Commit transaction
PC-->>Admin : Payment records
```

**Diagram sources**
- [PembayaranController.php:170-241](file://backend/app/Http/Controllers/PembayaranController.php#L170-L241)

**Section sources**
- [PembayaranController.php:170-241](file://backend/app/Http/Controllers/PembayaranController.php#L170-L241)

### Integration with Student Data and Academic Years
- Invoices are linked to students via NIS and scoped by branch.
- Academic year scoping ensures invoices and payment types belong to a specific period; controllers auto-resolve active period when not provided.

```mermaid
flowchart TD
A["User selects periode"] --> B{"Periode provided?"}
B --> |No| C["Resolve Periode_Aktif by branch"]
B --> |Yes| D["Validate ownership by branch"]
C --> E["Use resolved tahun_ajaran_id"]
D --> E
E --> F["Create/Filter Tagihan and JenisTagihan"]
```

**Diagram sources**
- [TagihanController.php:220-275](file://backend/app/Http/Controllers/TagihanController.php#L220-L275)
- [JenisTagihanController.php:40-78](file://backend/app/Http/Controllers/JenisTagihanController.php#L40-L78)

**Section sources**
- [TagihanController.php:220-275](file://backend/app/Http/Controllers/TagihanController.php#L220-L275)
- [JenisTagihanController.php:40-78](file://backend/app/Http/Controllers/JenisTagihanController.php#L40-L78)

### Receipt Generation (Kwitansi) and Customization
- Kwitansi resource builds receipt data including:
  - Amount in Indonesian words
  - Description combining payment type, student name, and month/year
  - Application settings for branding/layout context
- Customization points:
  - Number-to-words conversion service
  - Description generator service
  - Resource transformation to include additional fields or layout hints

```mermaid
sequenceDiagram
participant Admin as "Admin"
participant PC as "PembayaranController.kwitansi"
participant KR as "KwitansiResource"
participant GSK as "GenerateSejumlahKwitansi"
participant GKK as "GenerateKeteranganKwitansi"
Admin->>PC : GET /pembayaran/{id}/kwitansi
PC->>KR : Build KwitansiResource
KR->>GSK : Convert jumlah to words
KR->>GKK : Generate keterangan
KR-->>Admin : Receipt payload
```

**Diagram sources**
- [PembayaranController.php:400-410](file://backend/app/Http/Controllers/PembayaranController.php#L400-L410)
- [KwitansiResource.php:1-31](file://backend/app/Http/Resources/KwitansiResource.php#L1-L31)
- [GenerateSejumlahKwitansi.php:1-96](file://backend/app/Services/GenerateSejumlahKwitansi.php#L1-L96)
- [GenerateKeteranganKwitansi.php:1-35](file://backend/app/Services/GenerateKeteranganKwitansi.php#L1-L35)

**Section sources**
- [PembayaranController.php:400-410](file://backend/app/Http/Controllers/PembayaranController.php#L400-L410)
- [KwitansiResource.php:1-31](file://backend/app/Http/Resources/KwitansiResource.php#L1-L31)
- [GenerateSejumlahKwitansi.php:1-96](file://backend/app/Services/GenerateSejumlahKwitansi.php#L1-L96)
- [GenerateKeteranganKwitansi.php:1-35](file://backend/app/Services/GenerateKeteranganKwitansi.php#L1-L35)

### Practical Examples

- Creating custom payment types:
  - Use JenisTagihanController to add a new type with nama, jatuh_tempo, jumlah, and assign it to a branch and academic year.
  - Reference: [JenisTagihanController.create:40-78](file://backend/app/Http/Controllers/JenisTagihanController.php#L40-L78)

- Generating invoices for specific student groups:
  - Call TagihanController.create with filters such as jenjang, kelas_id, kategori_id to target a subset of students within a branch.
  - Reference: [TagihanController.create:220-275](file://backend/app/Http/Controllers/TagihanController.php#L220-L275)

- Managing invoice lifecycles:
  - Record partial/full payments using PembayaranController.bayar or lunas; status updates are automatic based on accumulated amounts.
  - Delete invoices only if no payments exist.
  - References:
    - [PembayaranController.bayar:343-397](file://backend/app/Http/Controllers/PembayaranController.php#L343-L397)
    - [PembayaranController.lunas:302-340](file://backend/app/Http/Controllers/PembayaranController.php#L302-L340)
    - [TagihanController.delete:295-319](file://backend/app/Http/Controllers/TagihanController.php#L295-L319)

**Section sources**
- [JenisTagihanController.php:40-78](file://backend/app/Http/Controllers/JenisTagihanController.php#L40-L78)
- [TagihanController.php:220-275](file://backend/app/Http/Controllers/TagihanController.php#L220-L275)
- [PembayaranController.php:302-397](file://backend/app/Http/Controllers/PembayaranController.php#L302-L397)
- [TagihanController.php:295-319](file://backend/app/Http/Controllers/TagihanController.php#L295-L319)

## Dependency Analysis
```mermaid
graph LR
JTC["JenisTagihanController"] --> JT["JenisTagihan"]
TC["TagihanController"] --> T["Tagihan"]
PC["PembayaranController"] --> P["Pembayaran"]
T --> P
JT --> T
TC --> GKT["GenerateKodeTagihan"]
PC --> GKP["GenerateKodePembayaran"]
KR["KwitansiResource"] --> GSK["GenerateSejumlahKwitansi"]
KR --> GKK["GenerateKeteranganKwitansi"]
```

**Diagram sources**
- [JenisTagihanController.php:1-179](file://backend/app/Http/Controllers/JenisTagihanController.php#L1-L179)
- [TagihanController.php:1-567](file://backend/app/Http/Controllers/TagihanController.php#L1-L567)
- [PembayaranController.php:1-496](file://backend/app/Http/Controllers/PembayaranController.php#L1-L496)
- [JenisTagihan.php:1-48](file://backend/app/Models/JenisTagihan.php#L1-L48)
- [Tagihan.php:1-60](file://backend/app/Models/Tagihan.php#L1-L60)
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [GenerateKodeTagihan.php:1-46](file://backend/app/Services/GenerateKodeTagihan.php#L1-L46)
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)
- [KwitansiResource.php:1-31](file://backend/app/Http/Resources/KwitansiResource.php#L1-L31)
- [GenerateSejumlahKwitansi.php:1-96](file://backend/app/Services/GenerateSejumlahKwitansi.php#L1-L96)
- [GenerateKeteranganKwitansi.php:1-35](file://backend/app/Services/GenerateKeteranganKwitansi.php#L1-L35)

**Section sources**
- [JenisTagihanController.php:1-179](file://backend/app/Http/Controllers/JenisTagihanController.php#L1-L179)
- [TagihanController.php:1-567](file://backend/app/Http/Controllers/TagihanController.php#L1-L567)
- [PembayaranController.php:1-496](file://backend/app/Http/Controllers/PembayaranController.php#L1-L496)
- [JenisTagihan.php:1-48](file://backend/app/Models/JenisTagihan.php#L1-L48)
- [Tagihan.php:1-60](file://backend/app/Models/Tagihan.php#L1-L60)
- [Pembayaran.php:1-53](file://backend/app/Models/Pembayaran.php#L1-L53)
- [GenerateKodeTagihan.php:1-46](file://backend/app/Services/GenerateKodeTagihan.php#L1-L46)
- [GenerateKodePembayaran.php:1-48](file://backend/app/Services/GenerateKodePembayaran.php#L1-L48)
- [KwitansiResource.php:1-31](file://backend/app/Http/Resources/KwitansiResource.php#L1-L31)
- [GenerateSejumlahKwitansi.php:1-96](file://backend/app/Services/GenerateSejumlahKwitansi.php#L1-L96)
- [GenerateKeteranganKwitansi.php:1-35](file://backend/app/Services/GenerateKeteranganKwitansi.php#L1-L35)

## Performance Considerations
- Unique code generation uses explicit table locks to avoid collisions under concurrency; ensure database engine supports LOCK TABLES and that autocommit toggling does not interfere with application transactions.
- Batch payment operations wrap multiple inserts and updates in a single transaction to maintain consistency and reduce round-trips.
- Grouped queries use eager loading and selective columns to minimize payload size and improve response times.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common issues and resolutions:
- Cannot delete invoice: If any Pembayaran exists, deletion is blocked to preserve auditability. Remove payments first or adjust workflow permissions.
  - Reference: [TagihanController.delete:295-319](file://backend/app/Http/Controllers/TagihanController.php#L295-L319)
- Overpayment validation: Payment amount must not exceed remaining balance; controller enforces checks before recording.
  - Reference: [PembayaranController.bayar:343-397](file://backend/app/Http/Controllers/PembayaranController.php#L343-L397)
- Online payment deletion restrictions: Deleting online_midtrans payments requires specific permissions; otherwise, an exception is raised.
  - Reference: [PembayaranController.delete:244-299](file://backend/app/Http/Controllers/PembayaranController.php#L244-L299)
- Academic year scoping errors: Ensure Periode_Aktif is configured for the branch or provide a valid tahun_ajaran_id owned by the user’s branch.
  - References:
    - [TagihanController.create:220-275](file://backend/app/Http/Controllers/TagihanController.php#L220-L275)
    - [JenisTagihanController.create:40-78](file://backend/app/Http/Controllers/JenisTagihanController.php#L40-L78)

**Section sources**
- [TagihanController.php:295-319](file://backend/app/Http/Controllers/TagihanController.php#L295-L319)
- [PembayaranController.php:244-397](file://backend/app/Http/Controllers/PembayaranController.php#L244-L397)
- [JenisTagihanController.php:40-78](file://backend/app/Http/Controllers/JenisTagihanController.php#L40-L78)

## Conclusion
Handayani’s billing system provides a robust foundation for managing invoices and payments:
- Clear separation of concerns between models, controllers, services, and resources
- Safe and deterministic invoice/payment code generation
- Flexible grouping and scoping by academic year and branch
- Comprehensive receipt generation with localization support
- Strong safeguards around deletions and overpayments

To extend functionality:
- Add new payment types via JenisTagihan
- Introduce new invoice categories by extending Tagihan filters and exports
- Customize receipt content through services and resources
- Implement additional reconciliation reports by leveraging existing grouped queries and export utilities

[No sources needed since this section summarizes without analyzing specific files]