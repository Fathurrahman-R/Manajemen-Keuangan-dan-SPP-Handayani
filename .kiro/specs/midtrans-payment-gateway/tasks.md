# Implementation Plan: Midtrans Payment Gateway

## Overview

Implementasi integrasi **Midtrans Snap** untuk pembayaran online Tagihan SPP pada Portal Siswa/Wali. Pekerjaan dibagi dua workspace: backend Laravel (`backend/`) untuk konfigurasi, model data baru, service layer, webhook handler, dan endpoint API; serta frontend Filament (`frontend-v2/`) untuk action "Bayar Online" di Portal, halaman Status Pembayaran, badge Online/Offline, dan halaman Admin TransaksiMidtrans. Implementasi dijaga additive (tidak ada breaking change pada alur Pembayaran offline existing), idempotent terhadap webhook duplikat, dan dilindungi feature flag dual-layer (`HANDAYANI_MIDTRANS_ENABLED`, `HANDAYANI_MIDTRANS_WEBHOOK_ENABLED`).

## Tasks

- [ ] 1. Backend configuration, permissions, and feature flag wiring
  - [ ] 1.1 Create `backend/config/midtrans.php` with Snap credentials and feature toggles
    - Define keys `enabled`, `webhook_enabled`, `environment`, `server_key`, `client_key`, `merchant_id`, `fee_flat`, `min_amount`, `expiry_hours`, `order_prefix`, `log_retention_days`
    - Hardcode `min_amount = 10_000` and `expiry_hours = 24` per asumsi A2/A3
    - Read `HANDAYANI_MIDTRANS_ENABLED` (default `false`), `HANDAYANI_MIDTRANS_WEBHOOK_ENABLED` (default `true`), `MIDTRANS_ENVIRONMENT` (default `sandbox`), `HANDAYANI_MIDTRANS_FEE_FLAT` (default `4000`)
    - _Requirements: 1.1, 1.2, 2.1, 2.7, 13.6_

  - [ ] 1.2 Add `InvalidMidtransConfigException` and validate `MIDTRANS_ENVIRONMENT` at boot
    - Create `App\Exceptions\Midtrans\InvalidMidtransConfigException`
    - In `App\Providers\AppServiceProvider::boot()`, throw the exception when `config('midtrans.environment')` is not `sandbox`/`production`, message must include the variable name and offending value
    - _Requirements: 1.3, 1.4_

  - [ ] 1.3 Add Midtrans permission cases and aggregate constant
    - Add cases `PAY_TAGIHAN_ONLINE`, `VIEW_MIDTRANS_TRX`, `SYNC_MIDTRANS_TRX`, `MANAGE_MIDTRANS_CONFIG` in `App\Enum\Permission`
    - Add `MIDTRANS_PERMISSIONS` aggregate (and update `ADMIN_PERMISSIONS`) in `App\Constant\Permissions` / `PermissionBinding`
    - _Requirements: 3.1_

  - [ ] 1.4 Update `RoleAndPermissionSeeder` to grant Midtrans permissions
    - Grant `pay-tagihan-online` to `siswa`
    - Grant `view-midtrans-transactions` and `sync-midtrans-transactions` to `admin` and `superadmin`
    - Grant `manage-midtrans-config` only to `superadmin`
    - Keep seeder idempotent (`firstOrCreate` + `syncPermissions`)
    - _Requirements: 3.2, 3.3, 3.4_

  - [ ] 1.5 Wire feature flag in `frontend-v2/config/handayani.php`
    - Add `features.midtrans_enabled` reading `HANDAYANI_MIDTRANS_ENABLED`
    - Add `midtrans` block: `fee_flat`, `min_amount`, `snap_url` (default sandbox `app.sandbox.midtrans.com/snap/snap.js`), `client_key` (NEVER expose `server_key`)
    - _Requirements: 2.2, 1.7, 1.8_

- [ ] 2. Database schema for midtrans transactions, logs, and pembayarans extension
  - [ ] 2.1 Migration: create `midtrans_transactions` table
    - Columns per design: `id`, `order_id` (unique, varchar 64), `kode_tagihan` (FK + index), `nis`, `amount_paid` (bigint unsigned), `fee_amount`, `gross_amount` (with CHECK `gross_amount = amount_paid + fee_amount` where supported, else enforce in service), `currency` default `IDR`, `status` enum, `payment_type`, `snap_token`, `snap_redirect_url`, `expired_at`, `paid_at`, `initiator_user_id` (FK users nullable), `branch_id`, `last_raw_response` JSON, timestamps
    - Composite indexes: `(kode_tagihan, status)`, `(status, expired_at)`
    - _Requirements: 4.5, 4.9, 7.1, 13.3_

  - [ ] 2.2 Migration: create `midtrans_transaction_logs` table
    - Columns: `id`, `order_id` (nullable+index), `direction` enum (`outbound_charge`,`outbound_status`,`inbound_notification`), `http_status`, `raw_payload` LONGTEXT (masked), `remote_ip`, `created_at` index
    - _Requirements: 11.1_

  - [ ] 2.3 Migration: alter `pembayarans` to add `metode` enum and `midtrans_order_id`
    - Modify `metode` to enum (`offline`,`online_midtrans`) default `offline`
    - Add `midtrans_order_id` varchar(64) nullable + UNIQUE
    - Add index `idx_pembayarans_metode`
    - Backfill existing rows with `metode = 'offline'`
    - _Requirements: 7.2, 12.1, 6.7_

  - [ ] 2.4 Create `App\Models\MidtransTransaction` Eloquent model
    - Fillable, casts (status enum cast, dates, json), scope `pendingInFlight()` (where `status='pending' AND expired_at > now()`)
    - Relations: `tagihan()`, `pembayaran()`, `logs()`, `initiator()`
    - _Requirements: 4.5, 7.5_

  - [ ] 2.5 Create `App\Models\MidtransTransactionLog` Eloquent model
    - Fillable, casts; relation `transaction()` via `order_id`
    - _Requirements: 11.1_

  - [ ] 2.6 Update `App\Models\Pembayaran` to expose new columns
    - Add `metode` and `midtrans_order_id` to `$fillable`/`$casts`; add accessor/relation `midtransTransaction()` if useful
    - _Requirements: 12.1, 6.7_

- [ ] 3. Domain exceptions and centralized HTTP renderer
  - [ ] 3.1 Create `App\Exceptions\Midtrans\*` exception classes
    - One class per error code listed in the design exception table, each carrying `errorCode` and `httpStatus` properties
    - Cover: `MidtransDisabledException`, `MidtransNotConfiguredException`, `WebhookDisabledException`, `TagihanNotFoundException`, `TagihanForbiddenException`, `TagihanSudahLunasException`, `AmountBelowMinimumException`, `AmountExceedsSisaException`, `TagihanHasPendingTransactionException` (carries snap data), `MidtransUnavailableException`, `MidtransStatusUnavailableException`, `TransactionAlreadyFinalException`, `InvalidStatusTransitionException`, `OrderNotFoundException`, `AmountMismatchException`, `OverpaymentBlockedException`, `AmountInternalInconsistentException`, `CannotDeleteOnlinePembayaranException`, `InvalidSignatureException`
    - _Requirements: 1.9, 2.4, 2.5, 2.8, 4.10-4.16, 5.3, 5.4, 5.8, 6.10, 8.4, 8.5, 12.4, 13.3, 13.5_

  - [ ] 3.2 Register Midtrans exception renderer in `App\Exceptions\Handler`
    - Convert any thrown Midtrans exception to JSON `{ error_code, message, data? }` with the carried HTTP status
    - i18n message lookup via `lang/id/midtrans.php` (added in task 8.1)
    - _Requirements: 4.10-4.16, 5.3, 5.4, 8.4, 12.4_

- [ ] 4. Pure-logic services and DTOs
  - [ ] 4.1 Create DTOs and enums under `App\Services\Midtrans\Dto`
    - `SnapPayload`, `InitiationResult`, `NotificationResult`, `MidtransStatusResponse`
    - Enum `MidtransInternalStatus` covering all 9 statuses
    - _Requirements: 4.5, 4.6, 5.5_

  - [ ] 4.2 Implement `OrderIdGenerator`
    - Produce `HDY-{kode_tagihan}-{epoch_ms}`; validate ≤64 chars, alphanumeric + `-_.`
    - _Requirements: 4.5, 7.1_

  - [ ] 4.3 Implement `SignatureVerifier`
    - `compute(orderId, statusCode, grossAmount, serverKey)` returns SHA-512 hex
    - `verify($payload, $serverKey)` constant-time compare
    - _Requirements: 5.2, 5.3_

  - [ ] 4.4 Implement `StatusMapper`
    - Map (`transaction_status`, `fraud_status`) → `MidtransInternalStatus` per Requirement 5 AC 5
    - _Requirements: 5.5_

  - [ ] 4.5 Implement `StatusTransitionGuard`
    - Encode `T_allowed` set defined in design Data Models
    - `isAllowed(current, next)` returns boolean; expose helper `terminal(current)`
    - _Requirements: 5.7, 5.8, 14.6_

  - [ ] 4.6 Checkpoint - verify backend boots with new permissions, migrations, and DTOs
    - Run `php artisan migrate:fresh --seed`, `composer test` (backend) — fix any breakage before continuing
    - Ask user before proceeding if unexpected failures arise

  - [ ]* 4.7 Property test for `OrderIdGenerator` (Property 9 partial, Property 1 partial)
    - **Property: Generated `order_id` is unique across N>=1000 invocations and matches Midtrans charset/length constraints**
    - **Validates: Requirements 4.5, 7.1**

  - [ ]* 4.8 Property test for `SignatureVerifier`
    - **Property 4: Signature invalid tidak memodifikasi state (signature layer)**
    - For random tuples (orderId, statusCode, grossAmount, serverKey), `verify` returns true iff signature equals `compute()`
    - **Validates: Requirements 5.2, 5.3, 14.4**

  - [ ]* 4.9 Property test for `StatusMapper`
    - **Property: Mapping is total over the documented input domain and matches Requirement 5 AC 5 table**
    - **Validates: Requirements 5.5**

  - [ ]* 4.10 Property test for `StatusTransitionGuard` (Property 7)
    - **Property 7: Transition guard accepts iff `(current,next) ∈ T_allowed`**
    - For all 81 status pairs, assert `isAllowed` matches expected set
    - **Validates: Requirements 5.7, 5.8, 14.6_

- [ ] 5. Midtrans HTTP client wrapper and logging service
  - [ ] 5.1 Define `MidtransClient` interface and `MidtransSnapClient` default impl
    - Interface methods: `createSnapTransaction(SnapPayload): array`, `getStatus(string $orderId): MidtransStatusResponse`, `isConfigured(): bool`
    - `MidtransSnapClient` wraps `midtrans/midtrans-php` (`Midtrans\Config`, `Midtrans\Snap`, `Midtrans\Transaction`); config from `config('midtrans.*')`; HTTP error → `MidtransUnavailableException`
    - Bind interface in `AppServiceProvider`
    - _Requirements: 1.5, 1.6, 4.6, 4.15, 8.2, 8.4_

  - [ ] 5.2 Add `FakeMidtransClient` for tests
    - Methods `shouldReturnSnap(...)`, `shouldFail(...)`, `shouldReturnStatus($transactionStatus, $fraudStatus, ...overrides)`
    - Bind in `tests/TestCase::setUp()` via `App::bind(MidtransClient::class, ...)`
    - _Requirements: testing strategy_

  - [ ] 5.3 Implement `MidtransLogService` with masking and safety net
    - `recordInbound($rawBody, $remoteIp)` and `recordOutbound($direction, $orderId, $httpStatus, $rawPayload)`
    - `mask()` always strips `server_key`, `signature_key`, and any literal substring matching current `config('midtrans.server_key')`
    - If post-mask payload still contains `server_key`, reject the log entry, write `Log::critical` incident, and DO NOT propagate the failure to the caller
    - 180-day retention noted (pruning task 9.1)
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

  - [ ] 5.4 Add `composer require midtrans/midtrans-php` (backend) and pin version in `composer.json`
    - Run `composer install` and verify autoload
    - _Requirements: 4.6, 8.2_

  - [ ]* 5.5 Property test for `MidtransLogService::mask`
    - **Property 11: Logging dan masking konsisten**
    - For random payloads with embedded `signature_key`/`server_key`/literal server_key value, the masked output must not contain those literals
    - **Validates: Requirements 5.10, 11.2, 11.3_

- [ ] 6. Initiation, notification, and sync services (DB-aware orchestration)
  - [ ] 6.1 Implement `MidtransFeeService`
    - `computeFee(int $amountPaid): int` returns `config('midtrans.fee_flat')` (snapshot at call time)
    - `assertGrossInvariant(amountPaid, feeAmount, grossAmount)` throws `AmountInternalInconsistentException` on mismatch
    - _Requirements: 13.3, 13.6_

  - [ ] 6.2 Implement `MidtransInitiationService::initiate`
    - Wrap in `DB::transaction()` with `Tagihan` row lock; algorithm steps 1-13 in design (feature flag check → configured check → load Tagihan FOR UPDATE → ownership → Sisa_Tagihan → amount validations → pending in-flight reuse → snapshot fee/gross → persist `MidtransTransaction` → call client → set `snap_token`/`redirect_url` → on Snap failure mark `failure` + throw `MidtransUnavailableException`)
    - Persist `expired_at = now()->addHours(24)`, `currency = IDR`
    - Build `SnapPayload` with exactly 2 line items (kode_tagihan + `FEE_MIDTRANS`) summing to `gross_amount`, customer details (Siswa name, NIS, Wali email if available), `expiry { unit: 'hour', duration: 24 }`
    - Log outbound via `MidtransLogService`
    - _Requirements: 4.5, 4.6, 4.7, 4.8, 4.9, 4.10-4.16, 6.11, 7.5, 13.1, 13.2, 13.3, 13.6_

  - [ ] 6.3 Implement `MidtransNotificationService::handle` (and reusable `processVerifiedPayload`)
    - Steps 1-11 in design: webhook flag check → record inbound log → signature verify → DB transaction with `MidtransTransaction` `FOR UPDATE` → existence check → gross_amount equality → status mapping → transition guard (no-op vs allowed vs rejected) → update transaction → on `settlement`/`capture` call `recordPembayaran` → commit → return `NotificationResult`
    - Refactor: `processVerifiedPayload($trx, $payload)` is callable from sync service, while `handle()` performs signature verification first
    - Retry strategy 2x on MySQL deadlock SQLSTATE 40001
    - _Requirements: 5.1-5.10, 6.1, 6.8, 7.3, 7.4, 13.4, 13.5, 14.3_

  - [ ] 6.4 Implement `recordPembayaran` helper inside notification flow
    - Idempotent guard: skip if `Pembayaran` with same `midtrans_order_id` exists
    - Recompute `Sisa_Tagihan` under lock; if `Sisa_Tagihan < amount_paid`, log `OVERPAYMENT_BLOCKED` and throw `OverpaymentBlockedException`
    - Build Pembayaran with: generated `kode_pembayaran`, `kode_tagihan`, `tanggal = settlement_time ?? now()`, `metode = 'online_midtrans'`, `jumlah = amount_paid` (NOT gross), `pembayar` from initiator user, `branch_id` snapshot from Tagihan, `midtrans_order_id`
    - Dispatch `PembayaranRecorded($pembayaran)` exactly once
    - _Requirements: 6.1-6.12, 7.4, 7.6, 14.1, 14.2, 14.5_

  - [ ] 6.5 Implement `MidtransStatusSyncService::syncManual`
    - Pre-check: if status terminal → throw `TransactionAlreadyFinalException` WITHOUT calling Midtrans
    - Call `MidtransClient::getStatus($orderId)` → on HTTP/network failure throw `MidtransStatusUnavailableException`
    - Synthesize a verified payload and delegate to `MidtransNotificationService::processVerifiedPayload`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

  - [ ] 6.6 Checkpoint - integration smoke for service layer
    - Manually drive `MidtransInitiationService::initiate` using `FakeMidtransClient` and a seeded Tagihan, verify pending in-flight idempotency, success path, and failure path produce expected DB state
    - Drive `handle()` with synthetic webhook payloads for each terminal status
    - Ask the user if unexpected failures arise

  - [ ]* 6.7 Property test for initiation amount contract (Property 1)
    - **Property 1: Inisiasi transaksi mempertahankan kontrak amount**
    - For random valid `(Tagihan, Siswa, amount_paid)` triples, assert all postconditions in design Property 1
    - **Validates: Requirements 4.5, 4.6, 4.7, 4.8, 4.9, 13.2, 13.3, 13.6_

  - [ ]* 6.8 Property test for pending in-flight idempotency (Property 2)
    - **Property 2: Inisiasi idempoten saat ada pending in-flight**
    - For random Tagihan with existing pending transaction, repeat init with arbitrary valid amounts and assert HTTP 409 + identical payload + no new row
    - **Validates: Requirements 4.16, 6.11, 7.5, 9.8_

  - [ ]* 6.9 Property test for initiation error mapping (Property 3)
    - **Property 3: Pemetaan error inisiasi konsisten**
    - For each domain violation (missing tagihan / wrong owner / lunas / below minimum / exceeds sisa / Snap failure / amount inconsistent) random inputs map to expected `(httpStatus, error_code)` pair and DB invariants hold
    - **Validates: Requirements 4.10, 4.11, 4.12, 4.13, 4.14, 4.15, 13.3_

  - [ ]* 6.10 Property test for webhook idempotency and determinism (Property 5)
    - **Property 5: Webhook handler idempoten dan deterministik**
    - For random valid payloads representing allowed transitions, processing N times equals processing once; `PembayaranRecorded` dispatched exactly once for terminal success transitions
    - **Validates: Requirements 5.5, 5.6, 5.7, 5.9, 6.1, 6.8, 6.9, 7.4, 14.3_

  - [ ]* 6.11 Property test for online Pembayaran reflecting transaction (Property 6)
    - **Property 6: Pembayaran online merefleksikan transaksi dengan tepat**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.12, 14.5_

  - [ ]* 6.12 Property test for end-to-end financial invariants (Property 8)
    - **Property 8: Invariant keuangan lintas siklus operasi**
    - Generate random valid sequences `(initiate*, webhook*, sync*, deletePembayaran*)`; after every step assert no overpayment, exactly one or zero Pembayaran per terminal MidtransTransaction, sum-equality property, and `OVERPAYMENT_BLOCKED` rejections do not write Pembayaran
    - **Validates: Requirements 6.10, 7.4, 7.6, 14.1, 14.2_

  - [ ]* 6.13 Property test for uniqueness invariants (Property 9)
    - **Property 9: Uniqueness order_id dan midtrans_order_id**
    - Drive parallel-ish init/webhook sequences and assert DB unique constraints never produce duplicates
    - **Validates: Requirements 7.1, 7.2, 7.3_

  - [ ]* 6.14 Property test for sync ≡ webhook (Property 10)
    - **Property 10: Sinkronisasi manual setara dengan webhook**
    - For random non-terminal transactions and Status API responses, `syncManual` produces same DB diff as a verified webhook with equivalent payload
    - For terminal transactions assert `TRANSACTION_ALREADY_FINAL` and that `MidtransClient::getStatus` is NEVER called (mock spy)
    - **Validates: Requirements 8.1, 8.3, 8.5_

- [ ] 7. HTTP layer: controllers, routes, and existing `PembayaranController` guard
  - [ ] 7.1 Create `MidtransTransactionController` (Portal)
    - `initiate(Request)` validates `kode_tagihan` + integer `amount_paid`, ensures Sanctum user has role `siswa` → calls service; renders 404 when `!config('midtrans.enabled')`
    - `show($order_id)` returns current status snapshot (ownership-checked) for portal polling
    - _Requirements: 2.4, 4.4, 4.5, 4.6, 9.5_

  - [ ] 7.2 Create `MidtransNotificationController` (public webhook)
    - `handle(Request)` checks `config('midtrans.webhook_enabled')`; delegates to `MidtransNotificationService::handle($payload, $rawBody, $remoteIp)`; returns mapped HTTP per `NotificationResult`
    - DOES NOT check `config('midtrans.enabled')` so existing transactions still settle (Requirement 2.5)
    - _Requirements: 2.5, 2.7, 2.8, 5.1, 5.9_

  - [ ] 7.3 Create `MidtransAdminController`
    - `index` paginated list (filters: status, branch_id, created_at range), `show($order_id)`, `logs($order_id)`, `sync($order_id)` (delegates to `MidtransStatusSyncService`)
    - Each method permission-gated via route middleware
    - Mask `signature_key`/`server_key` in serialized log payloads (UI shows `***`)
    - _Requirements: 8.1-8.5, 10.1-10.5, 11.5_

  - [ ] 7.4 Register Midtrans routes in `backend/routes/api.php`
    - Public webhook: `POST /midtrans/notification`
    - Sanctum + permission groups exactly as design section 8 (initiate, show, admin index/show/logs, sync)
    - _Requirements: 3.5, 3.6, 3.7, 5.1_

  - [ ] 7.5 Add online-pembayaran delete guard to `PembayaranController::destroy`
    - When target Pembayaran has `metode = 'online_midtrans'`, throw `CannotDeleteOnlinePembayaranException` unless user has both `delete-pembayaran` AND `manage-midtrans-config`
    - _Requirements: 12.2, 12.3, 12.4, 14_

  - [ ]* 7.6 Property test for permission gates (Property 13)
    - **Property 13: Permission gate menutup semua endpoint dan halaman Midtrans**
    - Generate users with random subsets of permissions; assert 403 iff required permission missing
    - **Validates: Requirements 3.5, 3.6, 3.7_

  - [ ]* 7.7 Property test for feature-flag behavior (Property 12)
    - **Property 12: Feature flag mempengaruhi inisiasi tetapi bukan webhook eksisting**
    - For matrix `(enabled, webhook_enabled) ∈ {true,false}^2` × {init, webhook, admin page} assert correct HTTP and no state mutation when disabled
    - **Validates: Requirements 2.3, 2.4, 2.5, 2.6, 2.8_

  - [ ]* 7.8 Property test for offline coexistence (Property 14)
    - **Property 14: Coexistence dengan jalur Pembayaran offline**
    - For random offline Pembayaran flows assert `metode='offline'`, `midtrans_order_id IS NULL`, no MidtransTransaction touched; for online Pembayaran DELETE assert 409 path
    - **Validates: Requirements 12.3, 12.4_

- [ ] 8. Frontend (`frontend-v2/`) i18n, API service, and Portal pages
  - [ ] 8.1 Add Indonesian message catalog `frontend-v2/lang/id/midtrans.php`
    - Map each error_code from design table to a user-facing Bahasa Indonesia message (e.g., `AMOUNT_EXCEEDS_SISA` → "Nominal melebihi sisa tagihan.")
    - Include continue-payment copy used by `TAGIHAN_HAS_PENDING_TRANSACTION`
    - _Requirements: 9.8, error UX_

  - [ ] 8.2 Create `frontend-v2/app/Services/MidtransApi.php`
    - Sanctum bearer wrapper using existing API base URL
    - Methods: `initiate(string $kodeTagihan, int $amountPaid)`, `show(string $orderId)`, admin `list/sync/logs`
    - Maps backend error responses to typed `MidtransApiException` carrying `errorCode` and `data`
    - _Requirements: 4.4, 8.1, 9.3, 10.1_

  - [ ] 8.3 Modify `PortalTagihanPage`: add "Bayar Online" action
    - Visible only when `config('handayani.features.midtrans_enabled') && Sisa_Tagihan >= 10000`
    - Filament Modal: numeric `amount_paid` input (default `Sisa_Tagihan`, min `10000`, max `Sisa_Tagihan`), reactive preview `Fee_Flat` + `Gross_Amount`
    - Submit → call `MidtransApi::initiate`; on success open Snap (snap.js popup OR redirect) and navigate to `PortalStatusPembayaranPage?order_id=...`
    - On 409 `TAGIHAN_HAS_PENDING_TRANSACTION` open continue-payment dialog using error body's `snap_token`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 9.1, 9.2, 9.3, 9.8_

  - [ ] 8.4 Create new `app/Filament/Portal/Pages/PortalStatusPembayaranPage.php`
    - URL parameter `order_id`; loads transaction via `MidtransApi::show`
    - Polling: `wire:poll.5s` with counter; STOP immediately on terminal status (`success`/`failed`/`expired`/`cancelled`); STOP at 24 polls (120s) showing instructions to check `PortalRiwayatPembayaranPage`
    - Display: `order_id`, status badge, `amount_paid`, `fee_amount`, `gross_amount`, `payment_type`; on success show download-kwitansi button
    - _Requirements: 9.4, 9.5, 9.7_

  - [ ] 8.5 Modify `PortalRiwayatPembayaranPage`
    - Add `Online`/`Offline` badge column derived from `metode`
    - Keep existing kwitansi link
    - _Requirements: 9.6, 9.7_

  - [ ]* 8.6 Property test for Portal UI representation (Property 15)
    - **Property 15: UI portal merepresentasikan state dengan benar**
    - Pest + Eris generate `(midtrans_enabled_flag, Sisa_Tagihan)` and `metode` random combinations, assert action visibility and badge mapping; Livewire test asserts polling stops on terminal status and at 120s
    - **Validates: Requirements 4.1, 9.1, 9.5, 9.6_

- [ ] 9. Frontend (`frontend-v2/`) Admin pages and access control
  - [ ] 9.1 Create new `app/Filament/Pages/TransaksiMidtransPage.php`
    - Permission gate: `view-midtrans-transactions`
    - Override `static::canAccess()` and `shouldRegisterNavigation()` to return false (and abort 404 from `mount()`) when `!config('handayani.features.midtrans_enabled')`
    - Table columns: `order_id`, `kode_tagihan`, `nama_siswa`, `amount_paid`, `fee_amount`, `gross_amount`, `status`, `payment_type`, `created_at`, `updated_at`
    - Filters: status, range `created_at`, `branch_id`
    - _Requirements: 2.6, 10.1, 10.2, 10.3_

  - [ ] 9.2 Create new `TransaksiMidtransDetailPage`
    - Loaded from row click on `TransaksiMidtransPage`
    - Action "Sinkronisasi Status" — visible only when `status === 'pending'` (hidden, NOT disabled, otherwise)
    - Audit log section consuming `/midtrans/admin/transactions/{order_id}/logs`
    - Always render `signature_key`/`server_key` as `***`
    - _Requirements: 8.1, 10.4, 10.5_

  - [ ] 9.3 Checkpoint - run `npm run build` and `composer test` (frontend-v2)
    - Ensure Filament pages compile, Pest suite is green; ask the user before continuing if regressions appear

- [ ] 10. Logging retention command, scheduling, and final verification
  - [ ] 10.1 Create `php artisan midtrans:prune-logs --days=180`
    - Command class `App\Console\Commands\MidtransPruneLogsCommand`
    - Deletes `midtrans_transaction_logs` rows older than `--days` (default from `config('midtrans.log_retention_days')`)
    - Register daily schedule in `routes/console.php` or `app/Console/Kernel.php`
    - _Requirements: 11.4_

  - [ ]* 10.2 Example test for log pruning command
    - Seed mixed-age log rows; run command with `--days=180`; assert correct rows pruned
    - _Requirements: 11.4_

  - [ ] 10.3 Update `.env.example` files
    - `backend/.env.example`: add `HANDAYANI_MIDTRANS_ENABLED`, `HANDAYANI_MIDTRANS_WEBHOOK_ENABLED`, `MIDTRANS_ENVIRONMENT`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_MERCHANT_ID`, `HANDAYANI_MIDTRANS_FEE_FLAT`
    - `frontend-v2/.env.example`: add the public-safe entries (`HANDAYANI_MIDTRANS_ENABLED`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_SNAP_URL`, `HANDAYANI_MIDTRANS_FEE_FLAT`)
    - _Requirements: 1.1, 1.2, 1.7, 2.1, 2.2_

  - [ ] 10.4 Final checkpoint - full backend and frontend test runs
    - `cd backend && composer test`
    - `cd frontend-v2 && composer test`
    - Manual smoke (sandbox): seed siswa with pending Tagihan, perform happy-path init → trigger Midtrans sandbox webhook test → confirm Pembayaran created and kwitansi email queued via existing listener
    - Ask user if any failure surfaces

## Notes

- Tasks marked with `*` are optional property tests; skip for an MVP cut, but they are the canonical machine-checkable validators of the design's correctness properties.
- Each task references the requirement IDs it satisfies for traceability.
- Property tests use Eris (already in `frontend-v2`; add `giorgiosironi/eris` as `require-dev` in `backend/`) at minimum 100 iterations per property.
- Backend tests live under `backend/tests/Feature/Midtrans/{Examples,Properties}`; frontend tests under `frontend-v2/tests/Feature/Midtrans/`.
- All user-facing strings are Bahasa Indonesia; centralize in `frontend-v2/lang/id/midtrans.php`.
- `FakeMidtransClient` is bound in test setUp so no test hits the real Midtrans network.
- Webhook handler must respond <2s to avoid Midtrans retry storms; keep DB queries on the happy path to lookup-FOR-UPDATE + pembayaran insert.
- `Sanctum` middleware protects portal/admin routes; the public webhook intentionally bypasses Sanctum and is protected by `signature_key` + log + IP capture.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.5"] },
    { "id": 1, "tasks": ["1.4", "2.1", "2.2", "2.3"] },
    { "id": 2, "tasks": ["2.4", "2.5", "2.6", "3.1"] },
    { "id": 3, "tasks": ["3.2", "4.1", "4.2", "4.3", "4.4", "4.5"] },
    { "id": 4, "tasks": ["4.6", "4.7", "4.8", "4.9", "4.10"] },
    { "id": 5, "tasks": ["5.1", "5.2", "5.3", "5.4"] },
    { "id": 6, "tasks": ["5.5", "6.1"] },
    { "id": 7, "tasks": ["6.2", "6.3"] },
    { "id": 8, "tasks": ["6.4", "6.5"] },
    { "id": 9, "tasks": ["6.6", "6.7", "6.8", "6.9", "6.10", "6.11", "6.12", "6.13", "6.14"] },
    { "id": 10, "tasks": ["7.1", "7.2", "7.3", "7.5"] },
    { "id": 11, "tasks": ["7.4", "7.6", "7.7", "7.8"] },
    { "id": 12, "tasks": ["8.1", "8.2"] },
    { "id": 13, "tasks": ["8.3", "8.4", "8.5"] },
    { "id": 14, "tasks": ["8.6", "9.1", "9.2"] },
    { "id": 15, "tasks": ["9.3", "10.1", "10.3"] },
    { "id": 16, "tasks": ["10.2", "10.4"] }
  ]
}
```
