# Database Schema Design

<cite>
**Referenced Files in This Document**
- [2025_11_06_103229_create_users_table.php](file://backend/database/migrations/2025_11_06_103229_create_users_table.php)
- [2025_11_07_170206_create_ayahs_table.php](file://backend/database/migrations/2025_11_07_170206_create_ayahs_table.php)
- [2025_11_07_170456_create_ibus_table.php](file://backend/database/migrations/2025_11_07_170456_create_ibus_table.php)
- [2025_11_08_085831_create_walis_table.php](file://backend/database/migrations/2025_11_08_085831_create_walis_table.php)
- [2025_11_08_090937_create_siswas_table.php](file://backend/database/migrations/2025_11_08_090937_create_siswas_table.php)
- [2025_11_08_083401_create_kategoris_table.php](file://backend/database/migrations/2025_11_08_083401_create_kategoris_table.php)
- [2025_11_08_084002_create_kelas_table.php](file://backend/database/migrations/2025_11_08_084002_create_kelas_table.php)
- [2025_11_14_093831_create_jenis_tagihans_table.php](file://backend/database/migrations/2025_11_14_093831_create_jenis_tagihans_table.php)
- [2025_11_14_094745_create_tagihans_table.php](file://backend/database/migrations/2025_11_14_094745_create_tagihans_table.php)
- [2025_11_14_102319_create_pembayarans_table.php](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php)
- [2026_05_25_100000_create_tahun_ajarans_table.php](file://backend/database/migrations/2026_05_25_100000_create_tahun_ajarans_table.php)
- [2026_05_25_100200_create_siswa_kelas_table.php](file://backend/database/migrations/2026_05_25_100200_create_siswa_kelas_table.php)
- [2026_05_25_100500_create_batch_promosis_table.php](file://backend/database/migrations/2026_05_25_100500_create_batch_promosis_table.php)
- [2026_05_25_100600_create_batch_promosi_details_table.php](file://backend/database/migrations/2026_05_25_100600_create_batch_promosi_details_table.php)
- [2026_05_26_220000_create_pengeluaran_requests_table.php](file://backend/database/migrations/2026_05_26_220000_create_pengeluaran_requests_table.php)
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
This document provides comprehensive database schema documentation for the Handayani system, focusing on core entities such as Users, Students, Parents (Ayah/Ibu), Guardians (Wali), Invoices (Tagihan), and Payments (Pembayaran). It details entity relationships, field definitions, data types, constraints, indexes, primary and foreign key relationships, validation rules enforced at the database level, and practical examples including common queries and Eloquent relationship patterns. It also covers migration strategy, version management, data seeding procedures, performance optimization, backup strategies, maintenance procedures, and data retention/archival policies for compliance.

## Project Structure
The database schema is defined using Laravel migrations under backend/database/migrations. The project uses a relational model with clear separation between student master data, academic periods, billing/invoicing, payments, and administrative features like branches and approval workflows.

```mermaid
graph TB
subgraph "Core Entities"
U["users"]
S["siswas"]
A["ayah"]
B["ibu"]
W["walis"]
K["kelas"]
C["kategoris"]
end
subgraph "Academic Periods"
T["tahun_ajarans"]
SK["siswa_kelas"]
end
subgraph "Billing & Payments"
JT["jenis_tagihans"]
TG["tagihans"]
PB["pembayarans"]
end
subgraph "Operations & Audit"
BP["batch_promosis"]
BPD["batch_promosi_details"]
PR["pengeluaran_requests"]
end
S --> A
S --> B
S --> W
S --> K
S --> C
SK --> S
SK --> K
SK --> T
TG --> JT
TG --> S
PB --> TG
BP --> T
BP --> K
BP --> U
BPD --> BP
BPD --> S
BPD --> K
PR --> U
```

[No sources needed since this diagram shows conceptual workflow, not actual code structure]

## Core Components
This section summarizes the core tables and their roles:
- users: System users with authentication and role attributes.
- siswas: Student master records with personal and enrollment information.
- ayah, ibu: Parent profiles linked to students.
- walis: Guardian profiles linked to students.
- kelas, kategoris: Class and category reference data.
- tahun_ajarans: Academic year/period per branch.
- siswa_kelas: Many-to-many mapping of students to classes within an academic period.
- jenis_tagihans: Invoice type definitions (e.g., tuition, fees).
- tagihans: Per-student invoices referencing invoice types and students.
- pembayarans: Payment records linked to invoices.
- batch_promosis, batch_promosi_details: Batch promotion/graduation operations and audit details.
- pengeluaran_requests: Expenditure request workflow entries.

Key business constraints visible at the database level include unique identifiers (e.g., NIS/NISN), referential integrity via foreign keys, and enumerated status fields that constrain allowed values.

**Section sources**
- [2025_11_06_103229_create_users_table.php:1-32](file://backend/database/migrations/2025_11_06_103229_create_users_table.php#L1-L32)
- [2025_11_08_090937_create_siswas_table.php:1-47](file://backend/database/migrations/2025_11_08_090937_create_siswas_table.php#L1-L47)
- [2025_11_07_170206_create_ayahs_table.php:1-31](file://backend/database/migrations/2025_11_07_170206_create_ayahs_table.php#L1-L31)
- [2025_11_07_170456_create_ibus_table.php:1-31](file://backend/database/migrations/2025_11_07_170456_create_ibus_table.php#L1-L31)
- [2025_11_08_085831_create_walis_table.php:1-33](file://backend/database/migrations/2025_11_08_085831_create_walis_table.php#L1-L33)
- [2025_11_08_084002_create_kelas_table.php:1-30](file://backend/database/migrations/2025_11_08_084002_create_kelas_table.php#L1-L30)
- [2025_11_08_083401_create_kategoris_table.php:1-29](file://backend/database/migrations/2025_11_08_083401_create_kategoris_table.php#L1-L29)
- [2026_05_25_100000_create_tahun_ajarans_table.php:1-38](file://backend/database/migrations/2026_05_25_100000_create_tahun_ajarans_table.php#L1-L38)
- [2026_05_25_100200_create_siswa_kelas_table.php:1-34](file://backend/database/migrations/2026_05_25_100200_create_siswa_kelas_table.php#L1-L34)
- [2025_11_14_093831_create_jenis_tagihans_table.php:1-31](file://backend/database/migrations/2025_11_14_093831_create_jenis_tagihans_table.php#L1-L31)
- [2025_11_14_094745_create_tagihans_table.php:1-33](file://backend/database/migrations/2025_11_14_094745_create_tagihans_table.php#L1-L33)
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)
- [2026_05_25_100500_create_batch_promosis_table.php:1-46](file://backend/database/migrations/2026_05_25_100500_create_batch_promosis_table.php#L1-L46)
- [2026_05_25_100600_create_batch_promosi_details_table.php:1-42](file://backend/database/migrations/2026_05_25_100600_create_batch_promosi_details_table.php#L1-L42)
- [2026_05_26_220000_create_pengeluaran_requests_table.php:1-33](file://backend/database/migrations/2026_05_26_220000_create_pengeluaran_requests_table.php#L1-L33)

## Architecture Overview
The database architecture centers around student lifecycle and financial operations:
- Student master data references parents/guardians and class/category.
- Academic periods (tahun_ajarans) govern class assignments through siswa_kelas.
- Billing (jenis_tagihans -> tagihans) ties invoices to students; payments (pembayarans) settle invoices.
- Operational tables support batch promotions and expenditure requests with auditability.

```mermaid
erDiagram
USERS {
bigint id PK
string username UK
string password
string role
string token UK
timestamp created_at
timestamp updated_at
}
SISWAS {
bigint id PK
string nis UK
string nisn UK
string nama
enum jenis_kelamin
string tempat_lahir
date tanggal_lahir
string agama
text alamat
bigint ayah_id FK
bigint ibu_id FK
bigint wali_id FK
enum jenjang
bigint kelas_id FK
bigint kategori_id FK
string asal_sekolah
string kelas_diterima
year tahun_diterima
enum status
text keterangan
timestamp created_at
timestamp updated_at
}
AYAH {
bigint id PK
string nama
string pendidikan_terakhir
string pekerjaan
timestamp created_at
timestamp updated_at
}
IBU {
bigint id PK
string nama
string pendidikan_terakhir
string pekerjaan
timestamp created_at
timestamp updated_at
}
WALIS {
bigint id PK
string nama
string pekerjaan
text alamat
string no_hp
text keterangan
timestamp created_at
timestamp updated_at
}
KELAS {
bigint id PK
enum jenjang
string nama
timestamp created_at
timestamp updated_at
}
KATEGORIS {
bigint id PK
string nama
timestamp created_at
timestamp updated_at
}
TAHUN_AJARANS {
bigint id PK
string nama
date tanggal_mulai
date tanggal_selesai
enum status
bigint branch_id FK
timestamp created_at
timestamp updated_at
}
SISWA_KELAS {
bigint id PK
bigint siswa_id FK
bigint kelas_id FK
bigint tahun_ajaran_id FK
timestamp created_at
timestamp updated_at
}
JENIS_TAGIHANS {
bigint id PK
string nama
date jatuh_tempo
decimal jumlah
timestamp created_at
timestamp updated_at
}
TAGIHANS {
char kode_tagihan PK
bigint jenis_tagihan_id FK
string nis UK
decimal tmp
enum status
timestamp created_at
timestamp updated_at
}
PEMBAYARANS {
char kode_pembayaran PK
char kode_tagihan FK
date tanggal
enum metode
decimal jumlah
string pembayar
timestamp created_at
timestamp updated_at
}
BATCH_PROMOSIS {
char id PK
enum batch_type
bigint source_tahun_ajaran_id FK
bigint target_tahun_ajaran_id FK
bigint kelas_id FK
bigint processed_by FK
timestamp processed_at
enum status
bigint branch_id FK
timestamp created_at
timestamp updated_at
}
BATCH_PROMOSI_DETAILS {
bigint id PK
char batch_id FK
bigint siswa_id FK
enum action
bigint source_kelas_id FK
bigint target_kelas_id FK
string previous_status
string previous_jenjang
timestamp created_at
timestamp updated_at
}
PENGELUARAN_REQUESTS {
bigint id PK
string uraian
decimal jumlah
date tanggal_kebutuhan
string kategori_pengeluaran
string lampiran
enum status
bigint requester_id FK
bigint branch_id FK
timestamp created_at
timestamp updated_at
}
SISWAS ||--o{ AYAH : "has parent"
SISWAS ||--o{ IBU : "has parent"
SISWAS ||--o{ WALIS : "has guardian"
SISWAS }o--|| KELAS : "current class"
SISWAS }o--|| KATEGORIS : "category"
SISWAS ||--o{ TAGIHANS : "has invoices"
SISWAS ||--o{ SISWA_KELAS : "enrolled in"
KELAS ||--o{ SISWA_KELAS : "assigned to"
TAHUN_AJARANS ||--o{ SISWA_KELAS : "period"
JENIS_TAGIHANS ||--o{ TAGIHANS : "defines"
TAGIHANS ||--o{ PEMBAYARANS : "receives payments"
BATCH_PROMOSIS ||--o{ BATCH_PROMOSI_DETAILS : "contains"
BATCH_PROMOSIS }o--|| TAHUN_AJARANS : "source/target"
BATCH_PROMOSIS }o--|| KELAS : "target class"
BATCH_PROMOSIS }o--|| USERS : "processed by"
PENGELUARAN_REQUESTS }o--|| USERS : "requested by"
```

**Diagram sources**
- [2025_11_06_103229_create_users_table.php:1-32](file://backend/database/migrations/2025_11_06_103229_create_users_table.php#L1-L32)
- [2025_11_08_090937_create_siswas_table.php:1-47](file://backend/database/migrations/2025_11_08_090937_create_siswas_table.php#L1-L47)
- [2025_11_07_170206_create_ayahs_table.php:1-31](file://backend/database/migrations/2025_11_07_170206_create_ayahs_table.php#L1-L31)
- [2025_11_07_170456_create_ibus_table.php:1-31](file://backend/database/migrations/2025_11_07_170456_create_ibus_table.php#L1-L31)
- [2025_11_08_085831_create_walis_table.php:1-33](file://backend/database/migrations/2025_11_08_085831_create_walis_table.php#L1-L33)
- [2025_11_08_084002_create_kelas_table.php:1-30](file://backend/database/migrations/2025_11_08_084002_create_kelas_table.php#L1-L30)
- [2025_11_08_083401_create_kategoris_table.php:1-29](file://backend/database/migrations/2025_11_08_083401_create_kategoris_table.php#L1-L29)
- [2026_05_25_100000_create_tahun_ajarans_table.php:1-38](file://backend/database/migrations/2026_05_25_100000_create_tahun_ajarans_table.php#L1-L38)
- [2026_05_25_100200_create_siswa_kelas_table.php:1-34](file://backend/database/migrations/2026_05_25_100200_create_siswa_kelas_table.php#L1-L34)
- [2025_11_14_093831_create_jenis_tagihans_table.php:1-31](file://backend/database/migrations/2025_11_14_093831_create_jenis_tagihans_table.php#L1-L31)
- [2025_11_14_094745_create_tagihans_table.php:1-33](file://backend/database/migrations/2025_11_14_094745_create_tagihans_table.php#L1-L33)
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)
- [2026_05_25_100500_create_batch_promosis_table.php:1-46](file://backend/database/migrations/2026_05_25_100500_create_batch_promosis_table.php#L1-L46)
- [2026_05_25_100600_create_batch_promosi_details_table.php:1-42](file://backend/database/migrations/2026_05_25_100600_create_batch_promosi_details_table.php#L1-L42)
- [2026_05_26_220000_create_pengeluaran_requests_table.php:1-33](file://backend/database/migrations/2026_05_26_220000_create_pengeluaran_requests_table.php#L1-L33)

## Detailed Component Analysis

### Users
- Purpose: Authentication and authorization base table.
- Primary Key: id (auto-increment).
- Unique Constraints: username, token.
- Notable Fields: role, password.
- Indexes: Unique indexes on username and token.
- Business Rules: Username must be unique; token optional but unique if provided.

Common Queries
- Find user by username: SELECT * FROM users WHERE username = ?;
- Validate token: SELECT id FROM users WHERE token = ? AND token IS NOT NULL;

Eloquent Relationships
- User hasMany Siswa (if implemented via foreign key on users.id referenced by other models).
- User hasMany PengeluaranRequests (requester_id).

Data Validation Rules
- Enforced via unique constraints on username and token.

Indexes
- Unique index on username.
- Unique index on token.

**Section sources**
- [2025_11_06_103229_create_users_table.php:1-32](file://backend/database/migrations/2025_11_06_103229_create_users_table.php#L1-L32)

### Students (siswas)
- Purpose: Master record for each student.
- Primary Key: id.
- Unique Constraints: nis, nisn.
- Foreign Keys: ayah_id -> ayah.id, ibu_id -> ibu.id, wali_id -> walis.id, kelas_id -> kelas.id, kategori_id -> kategoris.id.
- Enumerations: jenis_kelamin, jenjang, status.
- Timestamps: created_at, updated_at.

Business Constraints
- NIS/NISN uniqueness ensures identity integrity.
- Status restricted to predefined values.
- Referential integrity enforced for parents/guardian/class/category.

Common Queries
- Active students in a class: SELECT * FROM siswas WHERE kelas_id = ? AND status = 'Aktif';
- Student by NIS: SELECT * FROM siswas WHERE nis = ?;

Eloquent Relationships
- Siswa belongsTo Ayah, Ibu, Wali, Kelas, Kategori.
- Siswa hasMany Tagihan.
- Siswa hasMany Pembayaran via Tagihan.

Indexes
- Unique indexes on nis and nisn.

**Section sources**
- [2025_11_08_090937_create_siswas_table.php:1-47](file://backend/database/migrations/2025_11_08_090937_create_siswas_table.php#L1-L47)

### Parents (ayah, ibu)
- Purpose: Store parent profiles.
- Primary Key: id.
- Fields: nama, pendidikan_terakhir, pekerjaan.
- Timestamps: created_at, updated_at.

Business Constraints
- Optional fields allow flexible parent data capture.

Common Queries
- List all fathers: SELECT * FROM ayah;
- Parent by ID: SELECT * FROM ibu WHERE id = ?;

Eloquent Relationships
- Siswa belongsTo Ayah and Ibu.

**Section sources**
- [2025_11_07_170206_create_ayahs_table.php:1-31](file://backend/database/migrations/2025_11_07_170206_create_ayahs_table.php#L1-L31)
- [2025_11_07_170456_create_ibus_table.php:1-31](file://backend/database/migrations/2025_11_07_170456_create_ibus_table.php#L1-L31)

### Guardians (walis)
- Purpose: Guardian profile with contact info.
- Primary Key: id.
- Fields: nama, pekerjaan, alamat, no_hp, keterangan.
- Timestamps: created_at, updated_at.

Business Constraints
- Required fields ensure basic contact information.

Common Queries
- Get guardian by phone: SELECT * FROM walis WHERE no_hp = ?;

Eloquent Relationships
- Siswa belongsTo Wali.

**Section sources**
- [2025_11_08_085831_create_walis_table.php:1-33](file://backend/database/migrations/2025_11_08_085831_create_walis_table.php#L1-L33)

### Classes and Categories (kelas, kategoris)
- Purpose: Reference data for student classification and grouping.
- Primary Keys: id.
- Fields: kelas.jenjang, kelas.nama; kategoris.nama.
- Timestamps: created_at, updated_at.

Business Constraints
- Jenjang enumeration restricts class levels.

Common Queries
- List classes by jenjang: SELECT * FROM kelas WHERE jenjang = ?;
- Category lookup: SELECT * FROM kategoris WHERE nama = ?;

Eloquent Relationships
- Siswa belongsTo Kelas, Kategori.

**Section sources**
- [2025_11_08_084002_create_kelas_table.php:1-30](file://backend/database/migrations/2025_11_08_084002_create_kelas_table.php#L1-L30)
- [2025_11_08_083401_create_kategoris_table.php:1-29](file://backend/database/migrations/2025_11_08_083401_create_kategoris_table.php#L1-L29)

### Academic Periods and Enrollment (tahun_ajarans, siswa_kelas)
- Purpose: Define academic years per branch and map students to classes within those periods.
- Primary Keys: id (tahun_ajarans), id (siswa_kelas).
- Unique Constraints: (nama, branch_id) on tahun_ajarans; (siswa_id, tahun_ajaran_id) on siswa_kelas.
- Foreign Keys: siswa_kelas.siswa_id -> siswas.id; siswa_kelas.kelas_id -> kelas.id; siswa_kelas.tahun_ajaran_id -> tahun_ajarans.id.
- Indexes: (branch_id, status) on tahun_ajarans; (siswa_id, tahun_ajaran_id) unique.

Business Constraints
- One class per student per academic period.
- One academic period name per branch.

Common Queries
- Current class for student in period: SELECT kelas_id FROM siswa_kelas WHERE siswa_id = ? AND tahun_ajaran_id = ?;
- Active periods per branch: SELECT * FROM tahun_ajarans WHERE branch_id = ? AND status = 'Aktif';

Eloquent Relationships
- Siswa hasMany SiswaKelas.
- Kelas hasMany SiswaKelas.
- TahunAjaran hasMany SiswaKelas.

**Section sources**
- [2026_05_25_100000_create_tahun_ajarans_table.php:1-38](file://backend/database/migrations/2026_05_25_100000_create_tahun_ajarans_table.php#L1-L38)
- [2026_05_25_100200_create_siswa_kelas_table.php:1-34](file://backend/database/migrations/2026_05_25_100200_create_siswa_kelas_table.php#L1-L34)

### Invoices and Payments (jenis_tagihans, tagihans, pembayarans)
- Purpose: Define invoice types, create per-student invoices, and record payments.
- Primary Keys: id (jenis_tagihans), kode_tagihan (tagihans), kode_pembayaran (pembayarans).
- Foreign Keys: tagihans.jenis_tagihan_id -> jenis_tagihans.id; tagihans.nis -> siswas.nis; pembayarans.kode_tagihan -> tagihans.kode_tagihan.
- Enumerations: tagihans.status, pembayarans.metode.
- Indexes: kode_pembayaran indexed; kode_tagihan indexed; tanggal indexed; nis unique on tagihans.

Business Constraints
- Each invoice tied to one student and one invoice type.
- Payment method constrained to offline or online_midtrans.
- Status transitions governed by application logic; DB enforces allowed values.

Common Queries
- Unpaid invoices for student: SELECT * FROM tagihans WHERE nis = ? AND status != 'Lunas';
- Payments for invoice: SELECT * FROM pembayarans WHERE kode_tagihan = ?;

Eloquent Relationships
- JenisTagihan hasMany Tagihan.
- Siswa hasMany Tagihan.
- Tagihan hasMany Pembayaran.

**Section sources**
- [2025_11_14_093831_create_jenis_tagihans_table.php:1-31](file://backend/database/migrations/2025_11_14_093831_create_jenis_tagihans_table.php#L1-L31)
- [2025_11_14_094745_create_tagihans_table.php:1-33](file://backend/database/migrations/2025_11_14_094745_create_tagihans_table.php#L1-L33)
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)

### Batch Promotions and Details (batch_promosis, batch_promosi_details)
- Purpose: Record batch promotion/graduation operations and detailed actions per student.
- Primary Keys: id (char UUID) on batch_promosis; id on batch_promosi_details.
- Foreign Keys: batch_promosis.source_tahun_ajaran_id, target_tahun_ajaran_id, kelas_id, processed_by, branch_id; batch_promosi_details.batch_id, siswa_id, source_kelas_id, target_kelas_id.
- Enumerations: batch_type, status; action.
- Indexes: (branch_id, status), (branch_id, processed_at) on batch_promosis; batch_id, siswa_id on details.

Business Constraints
- Tracks source/target academic periods and target class.
- Maintains audit trail with previous state snapshots.

Common Queries
- Completed batches per branch: SELECT * FROM batch_promosis WHERE branch_id = ? AND status = 'completed';
- Details for batch: SELECT * FROM batch_promosi_details WHERE batch_id = ?;

Eloquent Relationships
- BatchPromosis hasMany BatchPromosiDetails.
- BatchPromosis belongsTo TahunAjaran (source/target), Kelas, User.
- BatchPromosiDetails belongsTo Siswa, Kelas.

**Section sources**
- [2026_05_25_100500_create_batch_promosis_table.php:1-46](file://backend/database/migrations/2026_05_25_100500_create_batch_promosis_table.php#L1-L46)
- [2026_05_25_100600_create_batch_promosi_details_table.php:1-42](file://backend/database/migrations/2026_05_25_100600_create_batch_promosi_details_table.php#L1-L42)

### Expenditure Requests (pengeluaran_requests)
- Purpose: Capture and track expenditure requests with workflow states.
- Primary Key: id.
- Foreign Keys: requester_id -> users.id; branch_id -> branches.id.
- Enumerations: status (draft, submitted, approved, rejected, disbursed).
- Indexes: (branch_id, status), requester_id.

Business Constraints
- Workflow states enforce progression.
- Branch-scoped requests.

Common Queries
- Pending requests per branch: SELECT * FROM pengeluaran_requests WHERE branch_id = ? AND status IN ('submitted', 'approved');

Eloquent Relationships
- PengeluaranRequest belongsTo User (requester).

**Section sources**
- [2026_05_26_220000_create_pengeluaran_requests_table.php:1-33](file://backend/database/migrations/2026_05_26_220000_create_pengeluaran_requests_table.php#L1-L33)

## Dependency Analysis
The following dependency graph highlights direct foreign key relationships across core tables:

```mermaid
graph LR
SISWAS --> AYAH
SISWAS --> IBU
SISWAS --> WALIS
SISWAS --> KELAS
SISWAS --> KATEGORIS
SISWAS --> TAGIHANS
SISWAS --> SISWA_KELAS
SISWA_KELAS --> KELAS
SISWA_KELAS --> TAHUN_AJARANS
TAGIHANS --> JENIS_TAGIHANS
PEMBAYARANS --> TAGIHANS
BATCH_PROMOSIS --> TAHUN_AJARANS
BATCH_PROMOSIS --> KELAS
BATCH_PROMOSIS --> USERS
BATCH_PROMOSI_DETAILS --> BATCH_PROMOSIS
BATCH_PROMOSI_DETAILS --> SISWAS
BATCH_PROMOSI_DETAILS --> KELAS
PENGELUARAN_REQUESTS --> USERS
```

**Diagram sources**
- [2025_11_08_090937_create_siswas_table.php:1-47](file://backend/database/migrations/2025_11_08_090937_create_siswas_table.php#L1-L47)
- [2025_11_07_170206_create_ayahs_table.php:1-31](file://backend/database/migrations/2025_11_07_170206_create_ayahs_table.php#L1-L31)
- [2025_11_07_170456_create_ibus_table.php:1-31](file://backend/database/migrations/2025_11_07_170456_create_ibus_table.php#L1-L31)
- [2025_11_08_085831_create_walis_table.php:1-33](file://backend/database/migrations/2025_11_08_085831_create_walis_table.php#L1-L33)
- [2025_11_08_084002_create_kelas_table.php:1-30](file://backend/database/migrations/2025_11_08_084002_create_kelas_table.php#L1-L30)
- [2025_11_08_083401_create_kategoris_table.php:1-29](file://backend/database/migrations/2025_11_08_083401_create_kategoris_table.php#L1-L29)
- [2026_05_25_100000_create_tahun_ajarans_table.php:1-38](file://backend/database/migrations/2026_05_25_100000_create_tahun_ajarans_table.php#L1-L38)
- [2026_05_25_100200_create_siswa_kelas_table.php:1-34](file://backend/database/migrations/2026_05_25_100200_create_siswa_kelas_table.php#L1-L34)
- [2025_11_14_093831_create_jenis_tagihans_table.php:1-31](file://backend/database/migrations/2025_11_14_093831_create_jenis_tagihans_table.php#L1-L31)
- [2025_11_14_094745_create_tagihans_table.php:1-33](file://backend/database/migrations/2025_11_14_094745_create_tagihans_table.php#L1-L33)
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)
- [2026_05_25_100500_create_batch_promosis_table.php:1-46](file://backend/database/migrations/2026_05_25_100500_create_batch_promosis_table.php#L1-L46)
- [2026_05_25_100600_create_batch_promosi_details_table.php:1-42](file://backend/database/migrations/2026_05_25_100600_create_batch_promosi_details_table.php#L1-L42)
- [2026_05_26_220000_create_pengeluaran_requests_table.php:1-33](file://backend/database/migrations/2026_05_26_220000_create_pengeluaran_requests_table.php#L1-L33)

**Section sources**
- [2025_11_08_090937_create_siswas_table.php:1-47](file://backend/database/migrations/2025_11_08_090937_create_siswas_table.php#L1-L47)
- [2025_11_14_094745_create_tagihans_table.php:1-33](file://backend/database/migrations/2025_11_14_094745_create_tagihans_table.php#L1-L33)
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)

## Performance Considerations
- Indexing Strategy
  - Ensure frequent filter columns are indexed: tagihans.nis, pembayarans.kode_tagihan, pembayarans.tanggal, siswa_kelas.siswa_id, siswa_kelas.tahun_ajaran_id, tahun_ajarans.branch_id/status, batch_promosis.branch_id/status and processed_at.
  - Composite indexes for multi-column filters (e.g., branch_id + status).
- Query Optimization
  - Use selective joins and avoid selecting unnecessary columns.
  - Prefer exact matches on enums and unique identifiers (nis, kode_tagihan).
- Data Volume Management
  - Partition large tables (e.g., pembayarans, tagihans) by date ranges if growth is significant.
  - Archive historical payment/invoice data to cold storage while maintaining referential links.
- Concurrency and Locking
  - Apply transactions for payment recording to prevent race conditions when updating invoice statuses.
- Monitoring
  - Track slow queries and adjust indexes accordingly.
  - Monitor replication lag if using read replicas for reporting.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
- Constraint Violations
  - Duplicate NIS/NISN: Check unique constraints on siswas.nis and siswas.nisn before insert/update.
  - Orphaned Records: Ensure foreign key cascades are configured appropriately; verify deletion order for dependent tables.
- Status Transitions
  - Validate application-level state machines against DB enum constraints to avoid invalid updates.
- Payment Reconciliation
  - Cross-check pembayarans.kode_tagihan with tagihans.kode_tagihan; use indexes to speed up lookups.
- Batch Operations
  - Inspect batch_promosis.status and batch_promosi_details.action for failed steps; rollback or reprocess as needed.

**Section sources**
- [2025_11_08_090937_create_siswas_table.php:1-47](file://backend/database/migrations/2025_11_08_090937_create_siswas_table.php#L1-L47)
- [2025_11_14_102319_create_pembayarans_table.php:1-34](file://backend/database/migrations/2025_11_14_102319_create_pembayarans_table.php#L1-L34)
- [2026_05_25_100500_create_batch_promosis_table.php:1-46](file://backend/database/migrations/2026_05_25_100500_create_batch_promosis_table.php#L1-L46)

## Conclusion
The Handayani database schema provides a robust foundation for managing student data, academic periods, invoicing, and payments with strong referential integrity and clear enumerations. Proper indexing and transactional handling will ensure performance and consistency. Operational tables support auditability and workflow tracking. Adhering to the outlined maintenance and archival practices will help maintain compliance and scalability.

[No sources needed since this section summarizes without analyzing specific files]

## Appendices

### Migration Strategy and Version Management
- Migrations are timestamp-prefixed for ordering and executed sequentially.
- Use Laravel’s migration commands to apply changes across environments consistently.
- For destructive changes, prefer additive migrations (add columns/indexes) and backfill data where necessary.

**Section sources**
- [2025_11_06_103229_create_users_table.php:1-32](file://backend/database/migrations/2025_11_06_103229_create_users_table.php#L1-L32)
- [2026_05_25_100000_create_tahun_ajarans_table.php:1-38](file://backend/database/migrations/2026_05_25_100000_create_tahun_ajarans_table.php#L1-L38)

### Data Seeding Procedures
- Seeders populate reference data (e.g., categories, classes, users, sample students/invoices/payments).
- Use factories to generate realistic datasets for testing.
- Ensure seeders respect unique constraints and foreign key dependencies.

**Section sources**
- [2025_11_08_083401_create_kategoris_table.php:1-29](file://backend/database/migrations/2025_11_08_083401_create_kategoris_table.php#L1-L29)
- [2025_11_08_084002_create_kelas_table.php:1-30](file://backend/database/migrations/2025_11_08_084002_create_kelas_table.php#L1-L30)

### Common Eloquent Relationship Patterns
- Siswa relationships:
  - belongsTo Ayah, Ibu, Wali, Kelas, Kategori.
  - hasMany Tagihan.
  - hasMany Pembayaran via Tagihan.
- Tagihan relationships:
  - belongsTo JenisTagihan, Siswa.
  - hasMany Pembayaran.
- Pembayaran relationships:
  - belongsTo Tagihan.
- SiswaKelas relationships:
  - belongsTo Siswa, Kelas, TahunAjaran.
- BatchPromosis relationships:
  - belongsTo TahunAjaran (source/target), Kelas, User.
  - hasMany BatchPromosiDetails.
- BatchPromosiDetails relationships:
  - belongsTo BatchPromosis, Siswa, Kelas.
- PengeluaranRequest relationships:
  - belongsTo User.

[No sources needed since this section provides general guidance]

### Backup Strategies and Maintenance Procedures
- Backups
  - Schedule regular full backups and incremental backups for transaction logs.
  - Test restore procedures periodically.
- Maintenance
  - Optimize tables and rebuild indexes periodically.
  - Purge old logs and temporary data.
- Compliance
  - Retain financial records per policy; archive older records securely.
  - Maintain audit trails for critical operations (payments, promotions).

[No sources needed since this section provides general guidance]

### Data Retention Policies and Archival Rules
- Financial Records
  - Keep invoices and payments indefinitely or per legal requirements; archive inactive records.
- Student Records
  - Retain active and graduated student records; consider anonymization for long-term archives.
- Audit Logs
  - Retain operational logs for a defined period; purge after compliance window.

[No sources needed since this section provides general guidance]