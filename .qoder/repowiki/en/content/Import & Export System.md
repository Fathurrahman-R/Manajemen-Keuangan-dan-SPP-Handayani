# Import & Export System

<cite>
**Referenced Files in This Document**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ImportBatchService.php](file://backend/app/Services/ImportExport/ImportBatchService.php)
- [SiswaImportValidator.php](file://backend/app/Imports/SiswaImportValidator.php)
- [TagihanImportValidator.php](file://backend/app/Imports/TagihanImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [SiswaExport.php](file://backend/app/Exports/SiswaExport.php)
- [TagihanExport.php](file://backend/app/Exports/TagihanExport.php)
- [SiswaImportTemplate.php](file://backend/app/Exports/SiswaImportTemplate.php)
- [TagihanImportTemplate.php](file://backend/app/Exports/TagihanImportTemplate.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)
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
This document explains the Handayani platform’s import and export system for bulk operations on Siswa (student) and Tagihan (invoice) entities. It covers validation, data mapping, conflict resolution, background job processing, progress tracking, error reporting, rollback mechanisms, and performance optimization strategies. The system supports both synchronous and asynchronous workflows depending on dataset size, with robust integrity checks and clear audit trails.

## Project Structure
The import/export subsystem is organized by domain services, Excel validators/templates, jobs, and models:
- Services orchestrate validation, transformation, persistence, and export generation.
- Validators parse and normalize uploaded files using Maatwebsite Excel readers.
- Jobs handle long-running imports and exports asynchronously.
- Models track batch progress and export job status.

```mermaid
graph TB
subgraph "Services"
SIS["SiswaImportService"]
TIS["TagihanImportService"]
SES["SiswaExportService"]
TES["TagihanExportService"]
IBS["ImportBatchService"]
end
subgraph "Validators"
SIV["SiswaImportValidator"]
TIV["TagihanImportValidator"]
end
subgraph "Jobs"
PIJ["ProcessImportJob"]
PEJ["ProcessExportJob"]
end
subgraph "Exports"
SE["SiswaExport"]
TE["TagihanExport"]
end
subgraph "Templates"
SIT["SiswaImportTemplate"]
TIT["TagihanImportTemplate"]
end
subgraph "Models"
IB["ImportBatch"]
EJ["ExportJob"]
end
SIS --> SIV
TIS --> TIV
SIS --> IB
TIS --> IB
SES --> SE
TES --> TE
SES --> EJ
TES --> EJ
PEJ --> SES
PEJ --> TES
PEJ --> EJ
SIT --> SIS
TIT --> TIS
```

**Diagram sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ImportBatchService.php](file://backend/app/Services/ImportExport/ImportBatchService.php)
- [SiswaImportValidator.php](file://backend/app/Imports/SiswaImportValidator.php)
- [TagihanImportValidator.php](file://backend/app/Imports/TagihanImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [SiswaExport.php](file://backend/app/Exports/SiswaExport.php)
- [TagihanExport.php](file://backend/app/Exports/TagihanExport.php)
- [SiswaImportTemplate.php](file://backend/app/Exports/SiswaImportTemplate.php)
- [TagihanImportTemplate.php](file://backend/app/Exports/TagihanImportTemplate.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

**Section sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ImportBatchService.php](file://backend/app/Services/ImportExport/ImportBatchService.php)
- [SiswaImportValidator.php](file://backend/app/Imports/SiswaImportValidator.php)
- [TagihanImportValidator.php](file://backend/app/Imports/TagihanImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [SiswaExport.php](file://backend/app/Exports/SiswaExport.php)
- [TagihanExport.php](file://backend/app/Exports/TagihanExport.php)
- [SiswaImportTemplate.php](file://backend/app/Exports/SiswaImportTemplate.php)
- [TagihanImportTemplate.php](file://backend/app/Exports/TagihanImportTemplate.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

## Core Components
- Import services: SiswaImportService and TagihanImportService provide validate, confirm, processInBackground, and processRows methods. They parse files via validators, cache previews, enforce business rules, create ImportBatch records, and either process synchronously or dispatch ProcessImportJob for large datasets.
- Export services: SiswaExportService and TagihanExportService build queries, count results, generate files synchronously, or dispatch ProcessExportJob for large datasets. They return file responses or queued job references.
- Batch management: ImportBatchService tracks history, eligibility, rollback, and status updates.
- Validators: SiswaImportValidator and TagihanImportValidator normalize headers and rows from Excel uploads.
- Jobs: ProcessImportJob and ProcessExportJob execute long-running tasks with retries and timeouts, updating ImportBatch and ExportJob records accordingly.
- Exports: SiswaExport and TagihanExport define headings, mappings, chunking, and class relationships for output formatting.
- Templates: SiswaImportTemplate and TagihanImportTemplate provide downloadable templates with dropdown validations and reference sheets.

**Section sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ImportBatchService.php](file://backend/app/Services/ImportExport/ImportBatchService.php)
- [SiswaImportValidator.php](file://backend/app/Imports/SiswaImportValidator.php)
- [TagihanImportValidator.php](file://backend/app/Imports/TagihanImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [SiswaExport.php](file://backend/app/Exports/SiswaExport.php)
- [TagihanExport.php](file://backend/app/Exports/TagihanExport.php)
- [SiswaImportTemplate.php](file://backend/app/Exports/SiswaImportTemplate.php)
- [TagihanImportTemplate.php](file://backend/app/Exports/TagihanImportTemplate.php)

## Architecture Overview
The system uses a layered architecture:
- Controllers (not analyzed here) call services to initiate imports/exports.
- Services coordinate parsing, validation, caching, transactions, and job dispatching.
- Jobs perform heavy lifting off the request thread.
- Models persist batch/job state and support signed download URLs for completed exports.

```mermaid
sequenceDiagram
participant Client as "Client"
participant Service as "Import/Export Service"
participant Cache as "Cache"
participant DB as "Database"
participant Queue as "Queue"
participant Job as "Process*Job"
participant Storage as "Storage"
Client->>Service : Upload file / Request export
Service->>DB : Load reference data (Kelas, JenisTagihan, Siswa)
Service->>Service : Validate rows and map fields
Service->>Cache : Store preview (import) or filters (export)
alt Large dataset
Service->>Queue : Dispatch ProcessImportJob / ProcessExportJob
Queue-->>Job : Execute job
Job->>DB : Read preview/filters and reference data
Job->>DB : Write records (transactions) or Generate file
Job->>DB : Update ImportBatch / ExportJob status
Job->>Storage : Store export file (if applicable)
else Small dataset
Service->>DB : Write records (transactions) or Generate file
Service->>DB : Update ImportBatch / ExportJob status
end
Service-->>Client : Return result or job reference
```

**Diagram sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

## Detailed Component Analysis

### Siswa Import Flow
- Validation: Parses rows via SiswaImportValidator, enforces required fields, formats, allowed values, duplicate NIS checks (in-file and existing), and kelas existence within branch/jenjang.
- Preview: Caches valid rows and counts; returns ImportPreviewDTO including requiresQueue flag based on threshold.
- Confirmation: Creates ImportBatch, validates active academic period, then either processes synchronously or dispatches ProcessImportJob.
- Processing: Within a transaction, creates Ayah/Ibu/Wali if provided, resolves Kelas/Kategori, creates Siswa and SiswaKelas, increments success count.

```mermaid
flowchart TD
Start(["Validate Uploaded File"]) --> Parse["Parse Rows<br/>SiswaImportValidator"]
Parse --> BuildRefs["Load Existing NIS, Kelas, Kategori"]
BuildRefs --> LoopRows{"For each row"}
LoopRows --> |Required fields| CheckReq["Check nis, nama, jenis_kelamin, jenjang"]
CheckReq --> FormatChecks["Format checks<br/>NIS/NISN, date, agama, gender, jenjang"]
FormatChecks --> DupChecks["Duplicate checks<br/>Existing NIS + In-file NIS"]
DupChecks --> KelasCheck["Kelas exists for jenjang?"]
KelasCheck --> ValidRow{"Valid?"}
ValidRow --> |Yes| CacheValid["Append to validData"]
ValidRow --> |No| CollectErr["Collect errors"]
CacheValid --> NextRow["Next row"]
CollectErr --> NextRow
NextRow --> DoneRows{"All rows processed?"}
DoneRows --> |No| LoopRows
DoneRows --> Preview["Create previewId and cache validData"]
Preview --> Confirm["Confirm import"]
Confirm --> Threshold{"Rows > threshold?"}
Threshold --> |No| SyncProc["processRows() in transaction"]
Threshold --> |Yes| Dispatch["Dispatch ProcessImportJob"]
SyncProc --> UpdateBatch["Update ImportBatch status"]
Dispatch --> JobRun["Job runs processRows()"]
JobRun --> UpdateBatch
UpdateBatch --> End(["Completed"])
```

**Diagram sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [SiswaImportValidator.php](file://backend/app/Imports/SiswaImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)

**Section sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [SiswaImportValidator.php](file://backend/app/Imports/SiswaImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)

### Tagihan Import Flow
- Validation: Parses rows via TagihanImportValidator, ensures NIS exists, JenisTagihan exists for active period, and prevents duplicates (existing and intra-file).
- Preview and confirmation: Similar to Siswa flow; caches preview and decides sync vs async.
- Processing: Resolves JenisTagihan, generates kode_tagihan, creates Tagihan records within a transaction.

```mermaid
sequenceDiagram
participant Client as "Client"
participant TIS as "TagihanImportService"
participant TIV as "TagihanImportValidator"
participant Cache as "Cache"
participant DB as "Database"
participant Job as "ProcessImportJob"
Client->>TIS : Upload tagihan file
TIS->>TIV : Parse rows
TIV-->>TIS : Normalized rows
TIS->>DB : Load Siswa NIS, JenisTagihan, existing combinations
TIS->>TIS : Validate rows and collect errors
TIS->>Cache : Store preview
Client->>TIS : Confirm import
alt Large dataset
TIS->>Job : Dispatch ProcessImportJob
Job->>DB : processRows() in transaction
else Small dataset
TIS->>DB : processRows() in transaction
end
DB-->>TIS : Success count
TIS->>DB : Update ImportBatch status
TIS-->>Client : Result
```

**Diagram sources**
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [TagihanImportValidator.php](file://backend/app/Imports/TagihanImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)

**Section sources**
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [TagihanImportValidator.php](file://backend/app/Imports/TagihanImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)

### Siswa Export Flow
- Query building: Scopes to branch, optionally filters by tahun_ajaran_id, jenjang, kelas_id, status; joins siswa_kelas when needed.
- Generation: If record count exceeds threshold, dispatches ProcessExportJob; otherwise generates file synchronously and returns BinaryFileResponse.
- Output mapping: SiswaExport maps fields including resolved kelas name and parent/wali info.

```mermaid
sequenceDiagram
participant Client as "Client"
participant SES as "SiswaExportService"
participant DB as "Database"
participant Job as "ProcessExportJob"
participant Storage as "Storage"
Client->>SES : Export siswa (filters, format)
SES->>DB : Count matching records
alt Large dataset
SES->>Job : Dispatch ProcessExportJob
Job->>DB : Build query and generate file
Job->>Storage : Store file
Job->>DB : Update ExportJob status
SES-->>Client : {queued, job_reference}
else Small dataset
SES->>DB : Build query and generate file
SES-->>Client : BinaryFileResponse
end
```

**Diagram sources**
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [SiswaExport.php](file://backend/app/Exports/SiswaExport.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

**Section sources**
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [SiswaExport.php](file://backend/app/Exports/SiswaExport.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

### Tagihan Export Flow
- Query building: Scopes to branch, filters by tahun_ajaran_id, status, jenjang, kelas_id; joins siswa and siswa_kelas when needed.
- Generation: Same threshold-based decision as Siswa export.
- Output mapping: TagihanExport computes totals and sisa, resolves kelas name via siswa relations.

```mermaid
sequenceDiagram
participant Client as "Client"
participant TES as "TagihanExportService"
participant DB as "Database"
participant Job as "ProcessExportJob"
participant Storage as "Storage"
Client->>TES : Export tagihan (filters, format)
TES->>DB : Count matching records
alt Large dataset
TES->>Job : Dispatch ProcessExportJob
Job->>DB : Build query and generate file
Job->>Storage : Store file
Job->>DB : Update ExportJob status
TES-->>Client : {queued, job_reference}
else Small dataset
TES->>DB : Build query and generate file
TES-->>Client : BinaryFileResponse
end
```

**Diagram sources**
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [TagihanExport.php](file://backend/app/Exports/TagihanExport.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

**Section sources**
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [TagihanExport.php](file://backend/app/Exports/TagihanExport.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

### Import Batch Management and Rollback
- History: Lists batches per branch with user details.
- Eligibility: Only completed batches within 48 hours can be rolled back; additional constraints prevent rollback if dependent records exist (e.g., siswa with tagihan, tagihan with pembayaran).
- Rollback: Deletes related records (SiswaKelas, Siswa or Tagihan) and marks batch as rolled_back with auditor info.

```mermaid
flowchart TD
StartRB(["Rollback Request"]) --> Fetch["Fetch ImportBatch by reference"]
Fetch --> CheckStatus{"Status == 'completed'?"}
CheckStatus --> |No| Deny["Deny: not completed"]
CheckStatus --> |Yes| CheckAge{"Within 48h?"}
CheckAge --> |No| DenyAge["Deny: time limit exceeded"]
CheckAge --> |Yes| DepCheck["Check dependent records"]
DepCheck --> HasDep{"Has dependencies?"}
HasDep --> |Yes| DenyDep["Deny: depends on payments/tagihan"]
HasDep --> |No| Delete["Delete related records"]
Delete --> MarkRB["Mark batch as rolled_back"]
MarkRB --> EndRB(["Done"])
Deny --> EndRB
DenyAge --> EndRB
DenyDep --> EndRB
```

**Diagram sources**
- [ImportBatchService.php](file://backend/app/Services/ImportExport/ImportBatchService.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)

**Section sources**
- [ImportBatchService.php](file://backend/app/Services/ImportExport/ImportBatchService.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)

### Data Mapping and Validation Rules
- Siswa mapping:
  - Required: nis, nama, jenis_kelamin, jenjang.
  - Formats: NIS numeric up to 20 chars; NISN exactly 10 digits; tanggal_lahir YYYY-MM-DD; agama from allowed list; jenis_kelamin L/P; jenjang TK/MI/KB.
  - Duplicates: NIS must not exist in DB or within file.
  - Kelas: Must exist for given jenjang within branch.
  - Parent/wali optional creation; kategori optional resolution.
- Tagihan mapping:
  - Required: nis, jenis_tagihan.
  - References: NIS must exist; jenis_tagihan must exist for active period.
  - Duplicates: Combination (nis + jenis_tagihan) must not exist in DB or within file.

**Section sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaImportValidator.php](file://backend/app/Imports/SiswaImportValidator.php)
- [TagihanImportValidator.php](file://backend/app/Imports/TagihanImportValidator.php)

### Background Jobs and Progress Tracking
- Import jobs:
  - ProcessImportJob reads cached preview, validates active period, calls service processRows(), updates ImportBatch status, clears cache.
  - Retries and timeout configured; failed method persists error_message.
- Export jobs:
  - ProcessExportJob builds queries via services, stores files to local storage, updates ExportJob status and file_path, provides signed URL via model helper.
  - Supports multiple export types (siswa, tagihan, pembayaran, kas_harian, rekap_bulanan).

```mermaid
classDiagram
class ProcessImportJob {
+int tries
+int timeout
+handle()
+failed(exception)
}
class ProcessExportJob {
+int tries
+int timeout
+handle()
+failed(exception)
}
class ImportBatch {
+string batch_reference
+string status
+int success_count
+string error_message
+isRollbackEligible() bool
}
class ExportJob {
+string job_reference
+string status
+string file_path
+getSignedUrl() string
}
ProcessImportJob --> ImportBatch : "updates"
ProcessExportJob --> ExportJob : "updates"
```

**Diagram sources**
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

**Section sources**
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

### Custom Import/Export Templates
- Siswa template:
  - Provides column headers and example row.
  - Adds dropdown validations for jenis_kelamin, agama, jenjang, kelas, status.
- Tagihan template:
  - Multi-sheet structure with main import sheet and reference sheet listing available jenis_tagihan names for the active period.

Practical usage:
- Download templates to ensure correct column names and allowed values.
- Populate data according to validation rules before uploading.

**Section sources**
- [SiswaImportTemplate.php](file://backend/app/Exports/SiswaImportTemplate.php)
- [TagihanImportTemplate.php](file://backend/app/Exports/TagihanImportTemplate.php)

### Handling Large Datasets
- Imports:
  - Threshold: 500 rows triggers queue dispatch via ProcessImportJob.
  - Preview caching allows safe confirmation later.
- Exports:
  - Threshold: 1000 records triggers queue dispatch via ProcessExportJob.
  - Chunked reading reduces memory footprint during export.

Best practices:
- Use templates to minimize validation errors.
- Monitor job status via ImportBatch/ExportJob records.
- For very large datasets, prefer async flows and scheduled workers.

**Section sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [SiswaExport.php](file://backend/app/Exports/SiswaExport.php)
- [TagihanExport.php](file://backend/app/Exports/TagihanExport.php)

### Data Transformation Logic
- Siswa:
  - Optional creation of Ayah/Ibu/Wali records from imported fields.
  - Resolution of kelas_id and kategori_id by name lookup.
  - Creation of SiswaKelas entry linked to current academic year.
- Tagihan:
  - Generation of unique kode_tagihan.
  - Assignment of default status and tmp values.

Implementation paths:
- Siswa transformation logic resides in SiswaImportService.processRows().
- Tagihan transformation logic resides in TagihanImportService.processRows().

**Section sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)

## Dependency Analysis
Key dependencies and relationships:
- Services depend on validators for parsing, models for persistence, and jobs for async execution.
- Jobs depend on services to build queries and perform transformations.
- Export models implement Maatwebsite interfaces for headings, mapping, and chunking.
- ImportBatch and ExportJob models store operational metadata and support signed downloads.

```mermaid
graph LR
SIS["SiswaImportService"] --> SIV["SiswaImportValidator"]
TIS["TagihanImportService"] --> TIV["TagihanImportValidator"]
SIS --> IB["ImportBatch"]
TIS --> IB
SES["SiswaExportService"] --> SE["SiswaExport"]
TES["TagihanExportService"] --> TE["TagihanExport"]
SES --> EJ["ExportJob"]
TES --> EJ
PIJ["ProcessImportJob"] --> SIS
PIJ --> TIS
PEJ["ProcessExportJob"] --> SES
PEJ --> TES
```

**Diagram sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [SiswaImportValidator.php](file://backend/app/Imports/SiswaImportValidator.php)
- [TagihanImportValidator.php](file://backend/app/Imports/TagihanImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [SiswaExport.php](file://backend/app/Exports/SiswaExport.php)
- [TagihanExport.php](file://backend/app/Exports/TagihanExport.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

**Section sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [SiswaImportValidator.php](file://backend/app/Imports/SiswaImportValidator.php)
- [TagihanImportValidator.php](file://backend/app/Imports/TagihanImportValidator.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [SiswaExport.php](file://backend/app/Exports/SiswaExport.php)
- [TagihanExport.php](file://backend/app/Exports/TagihanExport.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

## Performance Considerations
- Asynchronous processing:
  - Imports above 500 rows are queued to avoid blocking requests.
  - Exports above 1000 records are queued to reduce server load.
- Chunked reading:
  - Export classes use chunk sizes to manage memory efficiently.
- Transactional writes:
  - Import processing wraps inserts in transactions to maintain consistency.
- Reference data loading:
  - Preload reference sets (kelas, jenis_tagihan) to minimize repeated queries.
- Signed URLs:
  - ExportJob provides secure temporary links for downloads.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common issues and resolutions:
- Preview expired:
  - Cause: Cache TTL elapsed before confirmation.
  - Action: Re-upload the file to regenerate preview.
- Active period missing:
  - Cause: No active TahunAjaran for branch.
  - Action: Configure active academic period before importing.
- Duplicate NIS or combination:
  - Cause: Existing or intra-file duplicates detected.
  - Action: Correct or remove duplicates in the file.
- Invalid kelas or jenis_tagihan:
  - Cause: Mismatched names or missing references.
  - Action: Use templates and ensure exact matches.
- Rollback denied:
  - Cause: Not completed, time limit exceeded, or dependent records exist.
  - Action: Resolve dependencies or wait until eligible window.
- Job failures:
  - Cause: Exceptions during processing.
  - Action: Check ImportBatch/ExportJob error_message and retry after fixing inputs.

**Section sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ImportBatchService.php](file://backend/app/Services/ImportExport/ImportBatchService.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

## Conclusion
The Handayani import/export system provides robust, scalable bulk operations for Siswa and Tagihan data. It combines strict validation, clear mapping rules, and resilient background processing with comprehensive auditability through ImportBatch and ExportJob records. By leveraging templates, queues, and transactions, it ensures data integrity, performance, and a smooth user experience for large-scale operations.