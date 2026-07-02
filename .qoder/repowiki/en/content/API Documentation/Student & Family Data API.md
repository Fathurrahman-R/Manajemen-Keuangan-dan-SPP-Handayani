# Student & Family Data API

<cite>
**Referenced Files in This Document**
- [api.php](file://backend/routes/api.php)
- [SiswaController.php](file://backend/app/Http/Controllers/SiswaController.php)
- [WaliController.php](file://backend/app/Http/Controllers/WaliController.php)
- [ParentSearchController.php](file://backend/app/Http/Controllers/ParentSearchController.php)
- [SiswaRequest.php](file://backend/app/Http/Requests/SiswaRequest.php)
- [SiswaTKRequest.php](file://backend/app/Http/Requests/SiswaTKRequest.php)
- [SiswaKBRequest.php](file://backend/app/Http/Requests/SiswaKBRequest.php)
- [SiswaMIRequest.php](file://backend/app/Http/Requests/SiswaMIRequest.php)
- [SiswaResource.php](file://backend/app/Http/Resources/SiswaResource.php)
- [WaliResource.php](file://backend/app/Http/Resources/WaliResource.php)
- [Siswa.php](file://backend/app/Models/Siswa.php)
- [Wali.php](file://backend/app/Models/Wali.php)
- [Ayah.php](file://backend/app/Models/Ayah.php)
- [Ibu.php](file://backend/app/Models/Ibu.php)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Core Components](#core-components)
4. [Architecture Overview](#architecture-overview)
5. [Detailed Component Analysis](#detailed-component-analysis)
6. [Dependency Analysis](#dependency-analysis)
7. [Performance Considerations](#performance-considerations)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Conclusion](#conclusion)
10. [Appendices](#appendices)

## Introduction
This document provides detailed API documentation for student (Siswa) and family data management endpoints. It covers CRUD operations for students across school levels (TK, MI, KB), parent/guardian management APIs (Wali, Ayah, Ibu), parent search functionality used during student enrollment workflows, data validation rules, relationship mappings, filtering options, practical workflow examples, bulk operations, and privacy/access considerations.

## Project Structure
The student and family data features are implemented under the backend Laravel application:
- Routes define public and authenticated endpoints with permission-based access control.
- Controllers implement business logic for listing, creating, updating, retrieving, and deleting records.
- Request classes enforce validation rules per jenjang (school level).
- Resources shape JSON responses.
- Models define database relationships and attributes.

```mermaid
graph TB
Client["Client App"] --> Routes["API Routes<br/>routes/api.php"]
Routes --> SiswaCtrl["SiswaController"]
Routes --> WaliCtrl["WaliController"]
Routes --> ParentSearchCtrl["ParentSearchController"]
SiswaCtrl --> SiswaModel["Siswa Model"]
SiswaCtrl --> WaliModel["Wali Model"]
SiswaCtrl --> AyahModel["Ayah Model"]
SiswaCtrl --> IbuModel["Ibu Model"]
SiswaCtrl --> SiswaRes["SiswaResource"]
WaliCtrl --> WaliRes["WaliResource"]
```

**Diagram sources**
- [api.php:114-154](file://backend/routes/api.php#L114-L154)
- [SiswaController.php:25-321](file://backend/app/Http/Controllers/SiswaController.php#L25-L321)
- [WaliController.php:15-116](file://backend/app/Http/Controllers/WaliController.php#L15-L116)
- [ParentSearchController.php:19-67](file://backend/app/Http/Controllers/ParentSearchController.php#L19-L67)
- [SiswaResource.php:8-42](file://backend/app/Http/Resources/SiswaResource.php#L8-L42)
- [WaliResource.php:8-30](file://backend/app/Http/Resources/WaliResource.php#L8-L30)
- [Siswa.php:8-117](file://backend/app/Models/Siswa.php#L8-L117)
- [Wali.php:8-37](file://backend/app/Models/Wali.php#L8-L37)
- [Ayah.php:8-33](file://backend/app/Models/Ayah.php#L8-L33)
- [Ibu.php:8-33](file://backend/app/Models/Ibu.php#L8-L33)

**Section sources**
- [api.php:114-154](file://backend/routes/api.php#L114-L154)

## Core Components
- SiswaController: Handles student CRUD by jenjang (TK, MI, KB), including nested parent/guardian creation or linking, class synchronization, and account creation side-effects.
- WaliController: Manages guardian (Wali) entities with list, create, get, update, delete.
- ParentSearchController: Provides unified search for Ayah and Ibu to support student creation workflows.
- Request classes: Validate inputs per jenjang and handle conditional requirements for parent fields.
- Resources: Standardize response payloads for Siswa and Wali.
- Models: Define Eloquent relationships between Siswa and parents/guardians, plus class/category associations.

Key responsibilities:
- Authorization via Sanctum and permissions.
- Branch-scoped queries for multi-branch safety.
- Conditional validation based on jenjang.
- Class period synchronization for active academic year.

**Section sources**
- [SiswaController.php:25-321](file://backend/app/Http/Controllers/SiswaController.php#L25-L321)
- [WaliController.php:15-116](file://backend/app/Http/Controllers/WaliController.php#L15-L116)
- [ParentSearchController.php:19-67](file://backend/app/Http/Controllers/ParentSearchController.php#L19-L67)
- [SiswaRequest.php:25-176](file://backend/app/Http/Requests/SiswaRequest.php#L25-L176)
- [SiswaTKRequest.php:24-106](file://backend/app/Http/Requests/SiswaTKRequest.php#L24-L106)
- [SiswaKBRequest.php:24-106](file://backend/app/Http/Requests/SiswaKBRequest.php#L24-L106)
- [SiswaMIRequest.php:24-143](file://backend/app/Http/Requests/SiswaMIRequest.php#L24-L143)
- [SiswaResource.php:15-40](file://backend/app/Http/Resources/SiswaResource.php#L15-L40)
- [WaliResource.php:15-28](file://backend/app/Http/Resources/WaliResource.php#L15-L28)
- [Siswa.php:50-86](file://backend/app/Models/Siswa.php#L50-L86)
- [Wali.php:31-35](file://backend/app/Models/Wali.php#L31-L35)
- [Ayah.php:28-31](file://backend/app/Models/Ayah.php#L28-L31)
- [Ibu.php:28-31](file://backend/app/Models/Ibu.php#L28-L31)

## Architecture Overview
The API is protected by Sanctum authentication and fine-grained permissions. Endpoints are grouped under /api with route prefixes for domain areas. Student endpoints require specific permissions; parent search endpoints are scoped to the current user’s branch.

```mermaid
sequenceDiagram
participant C as "Client"
participant R as "Routes (/api)"
participant S as "SiswaController"
participant M as "Siswa Model"
participant P as "Parent Search Controller"
participant W as "Wali Model"
participant A as "Ayah Model"
participant B as "Ibu Model"
C->>R : GET /api/siswa/{jenjang}?search=&kelas_id=...
R->>S : index(jenjang)
S->>M : query with filters + pagination
M-->>S : collection
S-->>C : paginated siswa list
C->>R : POST /api/siswa/{jenjang}
R->>S : create(jenjang)
S->>A : create/update ayah if MI
S->>B : create/update ibu if MI
S->>W : create/link wali if TK/KB
S->>M : save siswa
S-->>C : created siswa resource
C->>R : GET /api/ayah?search=...
R->>P : ayah()
P->>A : whereHas('siswa', branch_id) + search
A-->>P : results
P-->>C : {data : [...]}
C->>R : GET /api/ibu?search=...
R->>P : ibu()
P->>B : whereHas('siswa', branch_id) + search
B-->>P : results
P-->>C : {data : [...]}
```

**Diagram sources**
- [api.php:114-154](file://backend/routes/api.php#L114-L154)
- [SiswaController.php:42-174](file://backend/app/Http/Controllers/SiswaController.php#L42-L174)
- [ParentSearchController.php:29-65](file://backend/app/Http/Controllers/ParentSearchController.php#L29-L65)
- [Siswa.php:50-86](file://backend/app/Models/Siswa.php#L50-L86)
- [Wali.php:31-35](file://backend/app/Models/Wali.php#L31-L35)
- [Ayah.php:28-31](file://backend/app/Models/Ayah.php#L28-L31)
- [Ibu.php:28-31](file://backend/app/Models/Ibu.php#L28-L31)

## Detailed Component Analysis

### Student (Siswa) Endpoints
Base path: /api/siswa/{jenjang}
- jenjang values: TK, MI, KB

Authentication and authorization:
- Requires Sanctum token.
- Permissions:
  - GET list: view-siswa
  - POST create: create-siswa
  - GET detail: read-siswa
  - PUT update: update-siswa
  - DELETE: delete-siswa

Endpoints:
- GET /api/siswa/{jenjang}
  - Query parameters:
    - search or q: substring match on nama and nis (nisn for MI)
    - kelas_id: filter by class
    - jenis_kelamin: Laki-laki | Perempuan
    - agama: string
    - status: Aktif | Lulus | Pindah | Keluar
    - sort: nama | nis | kelas_id | created_at
    - direction: asc | desc
    - per_page: integer (default 30)
  - Response: paginated collection of SiswaResource
- POST /api/siswa/{jenjang}
  - Body validated by jenjang-specific request class:
    - TK/KB: SiswaTKRequest or SiswaKBRequest
    - MI: SiswaMIRequest
  - Behavior:
    - For MI: link existing ayah_id/ibu_id or create nested ayah/ibu from provided fields
    - For TK/KB: link existing wali_id or create nested wali from provided fields
    - Sets jenjang and branch_id from authenticated user
    - Syncs current active academic year class record when kelas_id is provided
    - Attempts to create a student account asynchronously (non-blocking)
    - Returns 201 with SiswaResource
- GET /api/siswa/{jenjang}/{id}
  - Returns SiswaResource with loaded relations
- PUT /api/siswa/{jenjang}/{id}
  - Updates main fields and related parent/guardian records depending on jenjang
  - Syncs class record if kelas_id changed
  - Returns 200 with SiswaResource
- DELETE /api/siswa/{jenjang}/{id}
  - Deletes linked user account (username = NIS)
  - Deletes siswa, then deletes related parent/guardian records based on jenjang
  - Returns 200 with boolean success

Validation highlights:
- Common fields:
  - nis: required, numeric, length constraints
  - nama: required, alphabetic with accents/spaces
  - jenis_kelamin: required enum
  - tempat_lahir: required
  - tanggal_lahir: required date, before today, after cutoff
  - agama: required
  - alamat: required
  - kelas_id: required, exists kelas
  - kategori_id: required, exists kategoris
  - status: optional enum
- Jenjang-specific:
  - MI:
    - nisn: required, numeric
    - ayah_id or nested ayah fields (conditional)
    - ibu_id or nested ibu fields (conditional)
    - Optional asal_sekolah, kelas_diterima, tahun_diterima
  - TK/KB:
    - wali_id or nested wali fields (conditional)
    - wali_alamat and wali_no_hp required when wali not linked

Relationship mapping:
- Siswa belongsTo Ayah, Ibu, Wali
- Siswa belongsTo Kelas, Kategori
- Siswa hasMany Tagihan (by nis)
- Siswa hasMany SiswaKelas (for period tracking)
- Siswa hasOne User (account)

Response shaping:
- SiswaResource includes id, personal info, jenjang, kelas, kategori, and nested ayah/ibu/wali when loaded.

Example requests and responses:
- See OpenAPI example payloads and responses in the referenced docs file paths below.

Error handling:
- Validation errors return 400 with field messages
- Duplicate NIS returns 400 with message
- Not found returns 404
- Unauthorized returns 401

Practical enrollment workflow (MI):
1. GET /api/ayah?search=... and GET /api/ibu?search=... to find existing parents
2. POST /api/siswa/MI with either ayah_id/ibu_id or nested ayah/ibu fields
3. System creates siswa, links parents, syncs class, attempts account creation

Practical enrollment workflow (TK/KB):
1. GET /api/wali?search=... to find guardian
2. POST /api/siswa/TK or /api/siswa/KB with wali_id or nested wali fields
3. System creates siswa, links wali, syncs class, attempts account creation

Bulk operations:
- No direct bulk student endpoints are exposed here. Bulk import/export is available under /api/import-export routes (separate feature set).

**Section sources**
- [api.php:114-120](file://backend/routes/api.php#L114-L120)
- [SiswaController.php:42-321](file://backend/app/Http/Controllers/SiswaController.php#L42-L321)
- [SiswaRequest.php:25-176](file://backend/app/Http/Requests/SiswaRequest.php#L25-L176)
- [SiswaTKRequest.php:24-106](file://backend/app/Http/Requests/SiswaTKRequest.php#L24-L106)
- [SiswaKBRequest.php:24-106](file://backend/app/Http/Requests/SiswaKBRequest.php#L24-L106)
- [SiswaMIRequest.php:24-143](file://backend/app/Http/Requests/SiswaMIRequest.php#L24-L143)
- [SiswaResource.php:15-40](file://backend/app/Http/Resources/SiswaResource.php#L15-L40)
- [Siswa.php:50-86](file://backend/app/Models/Siswa.php#L50-L86)

### Guardian (Wali) Endpoints
Base path: /api/wali

Authentication and authorization:
- Requires Sanctum token.
- Permissions:
  - GET list: view-siswa
  - POST create: create-siswa
  - GET detail: read-siswa
  - PUT update: update-siswa
  - DELETE: delete-siswa

Endpoints:
- GET /api/wali
  - Query parameters:
    - search: substring match on nama
    - per_page: integer (default 30)
    - sort and direction supported via trait
  - Response: paginated WaliResource collection
- POST /api/wali
  - Body validated by WaliRequest
  - Creates new wali
  - Returns 201 with WaliResource
- GET /api/wali/{id}
  - Returns WaliResource
- PUT /api/wali/{id}
  - Updates wali fields
  - Returns 200 with WaliResource
- DELETE /api/wali/{id}
  - Prevents deletion if wali is linked to any siswa
  - Returns 200 with boolean success on delete

Validation highlights:
- Required fields include name, gender, religion, education, job, address, phone number
- Email optional and validated if present

Relationship mapping:
- Wali hasMany Siswa (via wali_id)

**Section sources**
- [api.php:142-148](file://backend/routes/api.php#L142-L148)
- [WaliController.php:23-115](file://backend/app/Http/Controllers/WaliController.php#L23-L115)
- [WaliResource.php:15-28](file://backend/app/Http/Resources/WaliResource.php#L15-L28)
- [Wali.php:31-35](file://backend/app/Models/Wali.php#L31-L35)

### Parent Search Endpoints (Ayah, Ibu)
Base paths:
- GET /api/ayah?search=...
- GET /api/ibu?search=...

Authentication and authorization:
- Requires Sanctum token.
- Permission: create-siswa

Behavior:
- Filters parents whose students belong to the current user’s branch
- Limits results to 20
- Returns { data: [...] }

Use cases:
- Pre-populate parent selection during student creation

**Section sources**
- [api.php:150-154](file://backend/routes/api.php#L150-L154)
- [ParentSearchController.php:29-65](file://backend/app/Http/Controllers/ParentSearchController.php#L29-L65)
- [Ayah.php:28-31](file://backend/app/Models/Ayah.php#L28-L31)
- [Ibu.php:28-31](file://backend/app/Models/Ibu.php#L28-L31)

### Data Validation Rules Summary
- Common student fields:
  - nis: required, numeric, min/max length
  - nama: required, alphabetic with accents/spaces
  - jenis_kelamin: required enum
  - tempat_lahir: required
  - tanggal_lahir: required date constraints
  - agama: required
  - alamat: required
  - kelas_id: required, exists kelas
  - kategori_id: required, exists kategoris
  - status: optional enum
- MI-specific:
  - nisn: required, numeric
  - ayah_id or nested ayah fields (conditional)
  - ibu_id or nested ibu fields (conditional)
  - Optional asal_sekolah, kelas_diterima, tahun_diterima
- TK/KB-specific:
  - wali_id or nested wali fields (conditional)
  - wali_alamat and wali_no_hp required when wali not linked

Notes:
- When an existing parent ID is provided, nested fields become optional.
- Email fields are optional but validated if present.

**Section sources**
- [SiswaRequest.php:25-176](file://backend/app/Http/Requests/SiswaRequest.php#L25-L176)
- [SiswaTKRequest.php:24-106](file://backend/app/Http/Requests/SiswaTKRequest.php#L24-L106)
- [SiswaKBRequest.php:24-106](file://backend/app/Http/Requests/SiswaKBRequest.php#L24-L106)
- [SiswaMIRequest.php:24-143](file://backend/app/Http/Requests/SiswaMIRequest.php#L24-L143)

### Relationship Mapping Diagram
```mermaid
erDiagram
SISWAS {
int id PK
string nis
string nisn
string nama
string jenis_kelamin
string tempat_lahir
date tanggal_lahir
string agama
text alamat
int ayah_id FK
int ibu_id FK
int wali_id FK
string jenjang
int kelas_id FK
int kategori_id FK
string asal_sekolah
string kelas_diterima
int tahun_diterima
enum status
text keterangan
int branch_id
}
AYAH {
int id PK
string nama
string pendidikan_terakhir
string pekerjaan
string email
}
IBU {
int id PK
string nama
string pendidikan_terakhir
string pekerjaan
string email
}
WALIS {
int id PK
string nama
string jenis_kelamin
string agama
string pendidikan_terakhir
string pekerjaan
text alamat
string no_hp
text keterangan
string email
}
KELAS {
int id PK
string jenjang
string nama
}
KATEGORIS {
int id PK
string nama
}
SISWAS ||--o{ AYAH : "ayah_id"
SISWAS ||--o{ IBU : "ibu_id"
SISWAS ||--o{ WALIS : "wali_id"
SISWAS }o--|| KELAS : "kelas_id"
SISWAS }o--|| KATEGORIS : "kategori_id"
```

**Diagram sources**
- [Siswa.php:50-86](file://backend/app/Models/Siswa.php#L50-L86)
- [Wali.php:31-35](file://backend/app/Models/Wali.php#L31-L35)
- [Ayah.php:28-31](file://backend/app/Models/Ayah.php#L28-L31)
- [Ibu.php:28-31](file://backend/app/Models/Ibu.php#L28-L31)

### Sequence Diagrams for Key Workflows

#### Create Student (MI)
```mermaid
sequenceDiagram
participant C as "Client"
participant R as "Routes"
participant S as "SiswaController"
participant A as "Ayah Model"
participant B as "Ibu Model"
participant M as "Siswa Model"
C->>R : POST /api/siswa/MI
R->>S : create(MI)
alt ayah_id provided
S->>A : use existing ayah
else nested ayah fields
S->>A : create ayah
end
alt ibu_id provided
S->>B : use existing ibu
else nested ibu fields
S->>B : create ibu
end
S->>M : save siswa with jenjang and branch_id
S->>S : syncSiswaKelas if kelas_id provided
S-->>C : 201 SiswaResource
```

**Diagram sources**
- [SiswaController.php:84-174](file://backend/app/Http/Controllers/SiswaController.php#L84-L174)
- [Siswa.php:50-86](file://backend/app/Models/Siswa.php#L50-L86)
- [Ayah.php:28-31](file://backend/app/Models/Ayah.php#L28-L31)
- [Ibu.php:28-31](file://backend/app/Models/Ibu.php#L28-L31)

#### Delete Student (Cascade Parents/Guardians)
```mermaid
flowchart TD
Start(["DELETE /api/siswa/{jenjang}/{id}"]) --> Find["Find siswa by id and jenjang"]
Find --> Exists{"Found?"}
Exists -- "No" --> NotFound["Return 404"]
Exists -- "Yes" --> Capture["Capture jenjang, ayah_id, ibu_id, wali_id, nis"]
Capture --> DeleteUser["Delete user account if username == nis"]
DeleteUser --> DeleteSiswa["Delete siswa"]
DeleteSiswa --> CheckJenjang{"jenjang == MI?"}
CheckJenjang -- "Yes" --> DeleteParents["Delete ayah and ibu if exist"]
CheckJenjang -- "No" --> CheckTKKB{"jenjang in TK/KB?"}
CheckTKKB -- "Yes" --> DeleteWali["Delete wali if exist"]
CheckTKKB -- "No" --> Done
DeleteParents --> Done(["Return 200 {data:true}"])
DeleteWali --> Done
```

**Diagram sources**
- [SiswaController.php:259-291](file://backend/app/Http/Controllers/SiswaController.php#L259-L291)

## Dependency Analysis
- Route-level dependencies:
  - Siswa endpoints depend on SiswaController and its service/model interactions.
  - Wali endpoints depend on WaliController.
  - Parent search endpoints depend on ParentSearchController and Ayah/Ibu models.
- Permission middleware:
  - All student and wali endpoints are protected by Sanctum and specific permissions.
- Branch scoping:
  - Student listing filters by Auth::user()->branch_id.
  - Parent search filters by students belonging to the same branch.

```mermaid
graph LR
Routes["routes/api.php"] --> SiswaCtrl["SiswaController"]
Routes --> WaliCtrl["WaliController"]
Routes --> ParentSearchCtrl["ParentSearchController"]
SiswaCtrl --> SiswaModel["Siswa"]
SiswaCtrl --> WaliModel["Wali"]
SiswaCtrl --> AyahModel["Ayah"]
SiswaCtrl --> IbuModel["Ibu"]
ParentSearchCtrl --> AyahModel
ParentSearchCtrl --> IbuModel
```

**Diagram sources**
- [api.php:114-154](file://backend/routes/api.php#L114-L154)
- [SiswaController.php:25-321](file://backend/app/Http/Controllers/SiswaController.php#L25-L321)
- [WaliController.php:15-116](file://backend/app/Http/Controllers/WaliController.php#L15-L116)
- [ParentSearchController.php:19-67](file://backend/app/Http/Controllers/ParentSearchController.php#L19-L67)
- [Siswa.php:50-86](file://backend/app/Models/Siswa.php#L50-L86)
- [Wali.php:31-35](file://backend/app/Models/Wali.php#L31-L35)
- [Ayah.php:28-31](file://backend/app/Models/Ayah.php#L28-L31)
- [Ibu.php:28-31](file://backend/app/Models/Ibu.php#L28-L31)

**Section sources**
- [api.php:114-154](file://backend/routes/api.php#L114-L154)

## Performance Considerations
- Pagination:
  - Student and wali listings are paginated; tune per_page appropriately.
- Eager loading:
  - Student listing loads ayah, ibu, wali, kelas, kategori; ensure indexes on foreign keys and commonly filtered columns (jenjang, branch_id, kelas_id).
- Sorting:
  - Use supported sort columns to avoid expensive computations.
- Parent search limit:
  - Parent search caps at 20 results to reduce payload size.
- Transactions:
  - Class synchronization uses DB transactions to maintain consistency.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common issues and resolutions:
- 401 Unauthorized:
  - Ensure valid Sanctum token in Authorization header.
- 403 Forbidden:
  - Verify user has required permission (e.g., view-siswa, create-siswa).
- 400 Validation Error:
  - Check field formats and required conditions (especially jenjang-specific parent fields).
- 404 Not Found:
  - Confirm student or wali IDs exist and jenjang matches.
- Duplicate NIS:
  - Choose a unique NIS value.
- Cannot delete wali:
  - Wali is linked to a student; unlink or delete the student first.

**Section sources**
- [SiswaController.php:90-97](file://backend/app/Http/Controllers/SiswaController.php#L90-L97)
- [SiswaController.php:259-291](file://backend/app/Http/Controllers/SiswaController.php#L259-L291)
- [WaliController.php:89-115](file://backend/app/Http/Controllers/WaliController.php#L89-L115)

## Conclusion
The Student & Family Data API provides robust CRUD capabilities for students across school levels, comprehensive parent/guardian management, and efficient search utilities for enrollment workflows. Strong validation, permission controls, and branch scoping ensure data integrity and security. Follow the documented endpoints, validation rules, and workflows to integrate effectively.

[No sources needed since this section summarizes without analyzing specific files]

## Appendices

### API Reference Tables

#### Students (Siswa)
- Base path: /api/siswa/{jenjang}
- Methods:
  - GET /api/siswa/{jenjang}
    - Query params: search/q, kelas_id, jenis_kelamin, agama, status, sort, direction, per_page
    - Responses: 200 (paginated), 401, 400 (validation)
  - POST /api/siswa/{jenjang}
    - Body: jenjang-specific request schema
    - Responses: 201, 400 (validation/duplicate NIS), 401
  - GET /api/siswa/{jenjang}/{id}
    - Responses: 200, 404, 401
  - PUT /api/siswa/{jenjang}/{id}
    - Body: jenjang-specific request schema
    - Responses: 200, 404, 401
  - DELETE /api/siswa/{jenjang}/{id}
    - Responses: 200, 404, 401

**Section sources**
- [api.php:114-120](file://backend/routes/api.php#L114-L120)
- [SiswaController.php:42-321](file://backend/app/Http/Controllers/SiswaController.php#L42-L321)

#### Guardians (Wali)
- Base path: /api/wali
- Methods:
  - GET /api/wali
    - Query params: search, per_page, sort, direction
    - Responses: 200, 401
  - POST /api/wali
    - Body: WaliRequest schema
    - Responses: 201, 400, 401
  - GET /api/wali/{id}
    - Responses: 200, 404, 401
  - PUT /api/wali/{id}
    - Body: WaliRequest schema
    - Responses: 200, 404, 400, 401
  - DELETE /api/wali/{id}
    - Responses: 200, 404, 400, 401

**Section sources**
- [api.php:142-148](file://backend/routes/api.php#L142-L148)
- [WaliController.php:23-115](file://backend/app/Http/Controllers/WaliController.php#L23-L115)

#### Parent Search (Ayah, Ibu)
- Base paths:
  - GET /api/ayah?search=...
  - GET /api/ibu?search=...
- Responses: 200 with { data: [...] }, 401

**Section sources**
- [api.php:150-154](file://backend/routes/api.php#L150-L154)
- [ParentSearchController.php:29-65](file://backend/app/Http/Controllers/ParentSearchController.php#L29-L65)

### Practical Workflow Examples

- Enroll a new MI student:
  1. GET /api/ayah?search=... and GET /api/ibu?search=...
  2. POST /api/siswa/MI with either ayah_id/ibu_id or nested ayah/ibu fields
  3. Receive 201 with full siswa resource including parents and class

- Enroll a new TK student:
  1. GET /api/wali?search=...
  2. POST /api/siswa/TK with wali_id or nested wali fields
  3. Receive 201 with full siswa resource including wali and class

- Update student class:
  - PUT /api/siswa/{jenjang}/{id} with kelas_id; system syncs current academic year class record

- Delete student:
  - DELETE /api/siswa/{jenjang}/{id}; cascades to related accounts and parents/guardians

**Section sources**
- [SiswaController.php:84-174](file://backend/app/Http/Controllers/SiswaController.php#L84-L174)
- [SiswaController.php:259-291](file://backend/app/Http/Controllers/SiswaController.php#L259-L291)

### Data Privacy and Access Controls
- Authentication:
  - All endpoints require Sanctum tokens.
- Authorization:
  - Fine-grained permissions protect each endpoint (e.g., view-siswa, create-siswa).
- Branch isolation:
  - Student listings are scoped to the authenticated user’s branch.
  - Parent search is limited to parents whose students belong to the same branch.
- Sensitive data:
  - Avoid exposing unnecessary sensitive fields in resources; currently, resources expose standard identifiers and contact details.
- Auditability:
  - Consider logging critical mutations (create/update/delete) for compliance.

**Section sources**
- [api.php:114-154](file://backend/routes/api.php#L114-L154)
- [SiswaController.php:44-46](file://backend/app/Http/Controllers/SiswaController.php#L44-L46)
- [ParentSearchController.php:37-42](file://backend/app/Http/Controllers/ParentSearchController.php#L37-L42)