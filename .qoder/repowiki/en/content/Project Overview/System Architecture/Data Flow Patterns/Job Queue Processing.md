# Job Queue Processing

<cite>
**Referenced Files in This Document**
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [queue.php](file://backend/config/queue.php)
- [2026_05_27_100500_create_jobs_table.php](file://backend/database/migrations/2026_05_27_100500_create_jobs_table.php)
- [2026_05_28_100000_create_import_batches_table.php](file://backend/database/migrations/2026_05_28_100000_create_import_batches_table.php)
- [2026_05_28_100100_create_export_jobs_table.php](file://backend/database/migrations/2026_05_28_100100_create_export_jobs_table.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ImportExportController.php](file://backend/app/Http/Controllers/ImportExportController.php)
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

## Introduction
This document explains the job queue processing system used for background operations in Handayani, focusing on bulk imports and exports. It covers how background jobs handle resource-intensive work, lifecycle management, queue configuration, worker setup, prioritization strategies, dispatching workflows, progress tracking, failure handling, retry mechanisms, timeouts, monitoring, database transactions within jobs, memory management for large datasets, and scaling considerations.

## Project Structure
The queue-driven import/export subsystem is implemented across Jobs, Services, Models, Controllers, and configuration:
- Jobs: ProcessImportJob and ProcessExportJob orchestrate long-running tasks.
- Services: Import and export services validate data, build queries, and decide whether to run synchronously or dispatch a queued job based on thresholds.
- Models: ImportBatch and ExportJob persist job state and results.
- Controller: ImportExportController exposes endpoints for upload, confirm, template download, history, rollback, and job status polling.
- Configuration: queue.php defines default connection and backends; migrations define persistence tables.

```mermaid
graph TB
subgraph "HTTP Layer"
C["ImportExportController"]
end
subgraph "Services"
SISV["SiswaImportService"]
TAGV["TagihanImportService"]
SISE["SiswaExportService"]
TAGE["TagihanExportService"]
end
subgraph "Jobs"
JIMP["ProcessImportJob"]
JEXP["ProcessExportJob"]
end
subgraph "Persistence"
MIB["import_batches"]
MEJ["export_jobs"]
Q["jobs / failed_jobs / job_batches"]
end
C --> SISV
C --> TAGV
C --> SISE
C --> TAGE
SISV --> |dispatch| JIMP
TAGV --> |dispatch| JIMP
SISE --> |dispatch| JEXP
TAGE --> |dispatch| JEXP
JIMP --> MIB
JEXP --> MEJ
JIMP -.-> Q
JEXP -.-> Q
```

**Diagram sources**
- [ImportExportController.php](file://backend/app/Http/Controllers/ImportExportController.php)
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [2026_05_28_100000_create_import_batches_table.php](file://backend/database/migrations/2026_05_28_100000_create_import_batches_table.php)
- [2026_05_28_100100_create_export_jobs_table.php](file://backend/database/migrations/2026_05_28_100100_create_export_jobs_table.php)
- [2026_05_27_100500_create_jobs_table.php](file://backend/database/migrations/2026_05_27_100500_create_jobs_table.php)

**Section sources**
- [ImportExportController.php](file://backend/app/Http/Controllers/ImportExportController.php)
- [queue.php](file://backend/config/queue.php)
- [2026_05_27_100500_create_jobs_table.php](file://backend/database/migrations/2026_05_27_100500_create_jobs_table.php)
- [2026_05_28_100000_create_import_batches_table.php](file://backend/database/migrations/2026_05_28_100000_create_import_batches_table.php)
- [2026_05_28_100100_create_export_jobs_table.php](file://backend/database/migrations/2026_05_28_100100_create_export_jobs_table.php)

## Core Components
- ProcessImportJob: Executes validated import rows, updates ImportBatch status and counts, clears preview cache, and handles failures.
- ProcessExportJob: Generates export files via Excel writers, persists file path and expiration, and marks jobs completed or failed.
- SiswaImportService and TagihanImportService: Validate uploads, compute previews, decide sync vs async based on row count thresholds, and dispatch jobs when needed.
- SiswaExportService and TagihanExportService: Build filtered queries, count records, and either generate files synchronously or dispatch ProcessExportJob for large datasets.
- ImportBatch and ExportJob models: Persist job metadata, status, errors, and provide helpers (e.g., signed URL generation).
- ImportExportController: Orchestrates API endpoints for upload, confirm, templates, history, rollback, and job status polling.

**Section sources**
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)
- [ImportExportController.php](file://backend/app/Http/Controllers/ImportExportController.php)

## Architecture Overview
The system uses Laravel queues with a database backend by default. Heavy operations are offloaded to workers via ProcessImportJob and ProcessExportJob. Services determine whether to run synchronously or asynchronously using thresholds. Results are persisted in import_batches and export_jobs, enabling UI polling and download links.

```mermaid
sequenceDiagram
participant Client as "Client"
participant Ctrl as "ImportExportController"
participant Svc as "Import/Export Service"
participant Queue as "Queue (database)"
participant Worker as "Worker"
participant Job as "Process*Job"
participant DB as "Database"
participant FS as "Storage"
Client->>Ctrl : Upload/Confirm or Export request
Ctrl->>Svc : validate()/confirm()/export()
alt Large dataset
Svc->>Queue : dispatch(ProcessImportJob|ProcessExportJob)
Svc-->>Ctrl : {queued : true, job_reference}
Ctrl-->>Client : 202 Accepted
else Small dataset
Svc->>DB : process synchronously
Svc-->>Ctrl : result or file response
Ctrl-->>Client : 200 OK or file
end
Note over Worker,Job : Worker picks up job from queue
Worker->>Job : handle()
Job->>DB : update batch/job status
Job->>FS : write export file (if export)
Job-->>Worker : complete or fail
```

**Diagram sources**
- [ImportExportController.php](file://backend/app/Http/Controllers/ImportExportController.php)
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [2026_05_27_100500_create_jobs_table.php](file://backend/database/migrations/2026_05_27_100500_create_jobs_table.php)

## Detailed Component Analysis

### ProcessImportJob Lifecycle
- Inputs: previewId, importType, branchId, userId, batchId.
- Validation: Retrieves cached preview; checks active academic period; otherwise marks failed.
- Execution: Delegates to service’s processRows for siswa or tagihan; updates ImportBatch success_count and status to completed; clears preview cache.
- Failure: On exception, marks ImportBatch failed with error_message; rethrows to trigger queue retry.

```mermaid
flowchart TD
Start(["handle()"]) --> GetPreview["Get preview from cache"]
GetPreview --> PreviewExists{"Preview exists?"}
PreviewExists -- No --> MarkFailed["Mark ImportBatch failed<br/>and return"]
PreviewExists -- Yes --> CheckPeriod["Check active academic period"]
CheckPeriod --> PeriodOk{"Active period found?"}
PeriodOk -- No --> MarkFailed
PeriodOk -- Yes --> DispatchService["Call service.processRows(...)"]
DispatchService --> UpdateBatch["Update ImportBatch: success_count, status=completed"]
UpdateBatch --> ClearCache["Clear preview cache"]
ClearCache --> End(["Exit"])
MarkFailed --> End
```

**Diagram sources**
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [2026_05_28_100000_create_import_batches_table.php](file://backend/database/migrations/2026_05_28_100000_create_import_batches_table.php)

**Section sources**
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [2026_05_28_100000_create_import_batches_table.php](file://backend/database/migrations/2026_05_28_100000_create_import_batches_table.php)

### ProcessExportJob Lifecycle
- Inputs: exportType, filters, format, branchId, jobReferenceId.
- Generation: Resolves appropriate export service and writer; writes file to storage; sets expires_at.
- Persistence: Updates ExportJob record with status=completed and file_path; on failure, marks status=failed with error_message.

```mermaid
flowchart TD
Start(["handle()"]) --> GenFile["generateFile(filePath)"]
GenFile --> UpdateRecord["Update ExportJob: status=completed,<br/>file_path, expires_at"]
UpdateRecord --> End(["Exit"])
GenFile -. catch .-> MarkFailed["markFailed(error_message)"]
MarkFailed --> End
```

**Diagram sources**
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [2026_05_28_100100_create_export_jobs_table.php](file://backend/database/migrations/2026_05_28_100100_create_export_jobs_table.php)

**Section sources**
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)
- [2026_05_28_100100_create_export_jobs_table.php](file://backend/database/migrations/2026_05_28_100100_create_export_jobs_table.php)

### Import Services: Validation, Thresholding, and Dispatch
- SiswaImportService and TagihanImportService:
  - Validate uploaded files and produce a preview with valid/error counts and errors list.
  - Cache preview data with TTL for confirmation.
  - If valid rows exceed threshold (500), dispatch ProcessImportJob; otherwise process synchronously.
  - Both paths create an ImportBatch record and use DB transactions around row processing.

```mermaid
classDiagram
class SiswaImportService {
+validate(file, branchId) ImportPreviewDTO
+confirm(previewId, branchId, userId) ImportBatch
+processInBackground(previewId, branchId, userId) ImportBatch
+processRows(validData, branchId, batchRef, tahunAjaranId) int
}
class TagihanImportService {
+validate(file, branchId) ImportPreviewDTO
+confirm(previewId, branchId, userId) ImportBatch
+processInBackground(previewId, branchId, userId) ImportBatch
+processRows(validData, branchId, batchRef, tahunAjaranId) int
}
class ProcessImportJob {
+handle() void
+failed(exception) void
}
class ImportBatch
SiswaImportService --> ProcessImportJob : "dispatch if > 500 rows"
TagihanImportService --> ProcessImportJob : "dispatch if > 500 rows"
SiswaImportService --> ImportBatch : "create/update"
TagihanImportService --> ImportBatch : "create/update"
```

**Diagram sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)

**Section sources**
- [SiswaImportService.php](file://backend/app/Services/ImportExport/SiswaImportService.php)
- [TagihanImportService.php](file://backend/app/Services/ImportExport/TagihanImportService.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)

### Export Services: Query Building and Async Dispatch
- SiswaExportService and TagihanExportService:
  - Build filtered Eloquent queries scoped to branch and optional filters (jenjang, kelas_id, status, tahun_ajaran_id).
  - Count matching records; if above threshold (1000), dispatch ProcessExportJob; otherwise generate file synchronously.
  - For async, create ExportJob record and return job reference for polling.

```mermaid
sequenceDiagram
participant Client as "Client"
participant Ctrl as "ImportExportController"
participant Svc as "Export Service"
participant Queue as "Queue"
participant Job as "ProcessExportJob"
participant DB as "Database"
participant FS as "Storage"
Client->>Ctrl : POST /export/{type}
Ctrl->>Svc : export(filters, format, branchId)
Svc->>Svc : buildQuery(filters, branchId)
Svc->>Svc : getRecordCount()
alt count > 1000
Svc->>DB : create ExportJob(status=processing)
Svc->>Queue : dispatch(ProcessExportJob)
Svc-->>Ctrl : {queued : true, job_reference}
Ctrl-->>Client : 202 Accepted
else count <= 1000
Svc->>FS : Excel : : download(...)
Svc-->>Ctrl : BinaryFileResponse
Ctrl-->>Client : 200 OK + file
end
Note over Queue,Job : Worker executes job
Job->>Svc : buildQuery(...)
Job->>FS : Excel : : store(...)
Job->>DB : update ExportJob(completed, file_path, expires_at)
```

**Diagram sources**
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)
- [2026_05_28_100100_create_export_jobs_table.php](file://backend/database/migrations/2026_05_28_100100_create_export_jobs_table.php)

**Section sources**
- [SiswaExportService.php](file://backend/app/Services/ImportExport/SiswaExportService.php)
- [TagihanExportService.php](file://backend/app/Services/ImportExport/TagihanExportService.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)
- [2026_05_28_100100_create_export_jobs_table.php](file://backend/database/migrations/2026_05_28_100100_create_export_jobs_table.php)

### Controller Integration and Progress Tracking
- Import endpoints:
  - uploadSiswa/uploadTagihan: Validate and return preview details including requires_queue flag.
  - confirmSiswa/confirmTagihan: Confirm import; returns 202 if queued, 200 if synchronous completion.
- Export endpoints:
  - exportSiswa/exportTagihan/exportPembayaran/exportKasHarian/exportRekapBulanan: Return file directly or 202 with job_reference.
- History and rollback:
  - importHistory: Paginated import batches per branch.
  - rollbackImport: Rollback eligible completed imports within time window.
- Job status polling:
  - jobStatus(jobId): Returns type (import/export), status, counts/errors, and signed download URL when available.

```mermaid
sequenceDiagram
participant UI as "Admin UI"
participant Ctrl as "ImportExportController"
participant Model as "ImportBatch/ExportJob"
UI->>Ctrl : GET /import/history?per_page=15
Ctrl->>Model : query(branch_id)
Model-->>Ctrl : paginated batches
Ctrl-->>UI : JSON history
UI->>Ctrl : GET /job-status/{jobId}
Ctrl->>Model : lookup by jobId
alt ExportJob
Model-->>Ctrl : ExportJob record
Ctrl-->>UI : {type : "export", status, download_url?}
else ImportBatch
Model-->>Ctrl : ImportBatch record
Ctrl-->>UI : {type : "import", status, counts, error_message?}
end
```

**Diagram sources**
- [ImportExportController.php](file://backend/app/Http/Controllers/ImportExportController.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

**Section sources**
- [ImportExportController.php](file://backend/app/Http/Controllers/ImportExportController.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

## Dependency Analysis
- Default queue connection is database-backed; retries and after_commit settings are configurable via environment variables.
- Jobs rely on Eloquent models and services; they do not hold heavy objects in memory due to serialization traits.
- Export jobs depend on Excel writers and local storage; import jobs depend on validation and transactional inserts.

```mermaid
graph LR
QConf["config/queue.php"] --> Jobs["Jobs (ShouldQueue)"]
Jobs --> Models["ImportBatch / ExportJob"]
Jobs --> Services["Import/Export Services"]
Jobs --> Storage["Local Storage"]
Jobs --> DB["Database (jobs, import_batches, export_jobs)"]
```

**Diagram sources**
- [queue.php](file://backend/config/queue.php)
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ImportBatch.php](file://backend/app/Models/ImportBatch.php)
- [ExportJob.php](file://backend/app/Models/ExportJob.php)

**Section sources**
- [queue.php](file://backend/config/queue.php)
- [2026_05_27_100500_create_jobs_table.php](file://backend/database/migrations/2026_05_27_100500_create_jobs_table.php)
- [2026_05_28_100000_create_import_batches_table.php](file://backend/database/migrations/2026_05_28_100000_create_import_batches_table.php)
- [2026_05_28_100100_create_export_jobs_table.php](file://backend/database/migrations/2026_05_28_100100_create_export_jobs_table.php)

## Performance Considerations
- Threshold-based routing:
  - Imports: >500 rows triggers background processing.
  - Exports: >1000 rows triggers background processing.
- Database transactions:
  - Import row processing is wrapped in a single transaction to ensure atomicity.
- Memory management:
  - Avoid loading entire datasets into memory; prefer streaming or chunked processing where possible.
  - Use Excel streaming writers for very large exports to reduce memory footprint.
- Timeouts and retries:
  - Jobs define explicit timeout and tries values; tune these according to workload and infrastructure.
- I/O and storage:
  - Ensure storage driver has adequate throughput; consider object storage for large files.
- Indexing:
  - Import/export tables include indexes to support efficient querying by branch and status.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common issues and remedies:
- Preview expired:
  - Cause: Cache TTL elapsed before confirmation.
  - Action: Re-upload the file to regenerate preview.
- Missing active academic period:
  - Cause: No active Tahun Ajaran configured for the branch.
  - Action: Configure the active period before importing.
- Job failures:
  - Inspect ImportBatch.error_message or ExportJob.error_message via job status endpoint.
  - Check failed_jobs table for stack traces and exceptions.
- Long-running jobs:
  - Increase worker timeout and queue retry_after to match job duration.
  - Scale out workers horizontally to increase throughput.
- Download link invalid:
  - Ensure ExportJob.status is completed and expires_at has not passed.

**Section sources**
- [ProcessImportJob.php](file://backend/app/Jobs/ProcessImportJob.php)
- [ProcessExportJob.php](file://backend/app/Jobs/ProcessExportJob.php)
- [ImportExportController.php](file://backend/app/Http/Controllers/ImportExportController.php)
- [2026_05_27_100500_create_jobs_table.php](file://backend/database/migrations/2026_05_27_100500_create_jobs_table.php)

## Conclusion
Handayani’s queue-driven import/export system separates user-facing requests from heavy processing through well-defined jobs and services. Thresholds ensure responsiveness while maintaining reliability via retries, transactions, and persistent job state. With proper worker configuration, indexing, and storage tuning, the system scales effectively for high-volume operations.