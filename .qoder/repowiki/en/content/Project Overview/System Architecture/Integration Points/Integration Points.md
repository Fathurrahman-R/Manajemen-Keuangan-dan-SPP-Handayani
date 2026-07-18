# Integration Points

<cite>
**Referenced Files in This Document**
- [MidtransClient.php](file://backend/app/Services/Midtrans/MidtransClient.php)
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransNotificationController.php](file://backend/app/Http/Controllers/MidtransNotificationController.php)
- [MidtransNotificationService.php](file://backend/app/Services/Midtrans/MidtransNotificationService.php)
- [MidtransStatusSyncService.php](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [SignatureVerifier.php](file://backend/app/Services/Midtrans/SignatureVerifier.php)
- [StatusMapper.php](file://backend/app/Services/Midtrans/StatusMapper.php)
- [StatusTransitionGuard.php](file://backend/app/Services/Midtrans/StatusTransitionGuard.php)
- [MidtransLogService.php](file://backend/app/Services/Midtrans/MidtransLogService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [MidtransTransactionController.php](file://backend/app/Http/Controllers/MidtransTransactionController.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [NotificationService.php](file://backend/app/Services/Notifications/NotificationService.php)
- [EmailOptOut.php](file://backend/app/Models/EmailOptOut.php)
- [EmailOptOutController.php](file://backend/app/Http/Controllers/EmailOptOutController.php)
- [mail.php](file://backend/config/mail.php)
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
This document explains the external integration points in the Handayani system with a focus on:
- Midtrans payment gateway integration (webhook handling, transaction status synchronization, fee calculation)
- Email notification system with multiple channel support and opt-out management
- Third-party service configuration, API client implementations, error handling strategies
- Security considerations, rate limiting, monitoring approaches
- Concrete examples from the codebase showing integration patterns, retry mechanisms, and fallback strategies
- Troubleshooting common integration issues and debugging techniques

## Project Structure
The integration-related components are primarily located under backend/app/Services/Midtrans, backend/app/Services/Notifications, controllers for webhooks and admin endpoints, and configuration files for Midtrans and mail.

```mermaid
graph TB
subgraph "Payment Integrations"
A["MidtransTransactionController"]
B["MidtransInitiationService"]
C["MidtransClient (interface)"]
D["MidtransSnapClient"]
E["MidtransNotificationController"]
F["MidtransNotificationService"]
G["MidtransStatusSyncService"]
H["MidtransFeeService"]
I["SignatureVerifier"]
J["StatusMapper"]
K["StatusTransitionGuard"]
L["MidtransLogService"]
M["OrderIdGenerator"]
N["MidtransTransaction (Model)"]
end
subgraph "Email Notifications"
O["NotificationService"]
P["RecipientResolver"]
Q["EmailOptOut (Model)"]
R["EmailOptOutController"]
S["Mail Config"]
end
A --> B
B --> C
C --> D
E --> F
F --> I
F --> J
F --> K
F --> L
F --> H
G --> D
G --> F
G --> L
A --> H
A --> M
A --> N
E --> N
O --> P
O --> Q
O --> S
R --> Q
```

**Diagram sources**
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransStatusSyncService.php:1-73](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php#L1-L73)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [SignatureVerifier.php:1-34](file://backend/app/Services/Midtrans/SignatureVerifier.php#L1-L34)
- [StatusMapper.php:1-41](file://backend/app/Services/Midtrans/StatusMapper.php#L1-L41)
- [StatusTransitionGuard.php:1-77](file://backend/app/Services/Midtrans/StatusTransitionGuard.php#L1-L77)
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [OrderIdGenerator.php:1-64](file://backend/app/Services/Midtrans/OrderIdGenerator.php#L1-L64)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)
- [NotificationService.php:1-713](file://backend/app/Services/Notifications/NotificationService.php#L1-L713)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)
- [EmailOptOutController.php:1-48](file://backend/app/Http/Controllers/EmailOptOutController.php#L1-L48)
- [mail.php:1-119](file://backend/config/mail.php#L1-L119)

**Section sources**
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransStatusSyncService.php:1-73](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php#L1-L73)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [SignatureVerifier.php:1-34](file://backend/app/Services/Midtrans/SignatureVerifier.php#L1-L34)
- [StatusMapper.php:1-41](file://backend/app/Services/Midtrans/StatusMapper.php#L1-L41)
- [StatusTransitionGuard.php:1-77](file://backend/app/Services/Midtrans/StatusTransitionGuard.php#L1-L77)
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [OrderIdGenerator.php:1-64](file://backend/app/Services/Midtrans/OrderIdGenerator.php#L1-L64)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)
- [NotificationService.php:1-713](file://backend/app/Services/Notifications/NotificationService.php#L1-L713)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)
- [EmailOptOutController.php:1-48](file://backend/app/Http/Controllers/EmailOptOutController.php#L1-L48)
- [mail.php:1-119](file://backend/config/mail.php#L1-L119)

## Core Components
- Payment initiation and Snap checkout via Midtrans client abstraction
- Webhook ingestion with signature verification, amount checks, state transitions, and idempotent recording of payments
- Manual status sync to reconcile pending transactions
- Fee calculation per channel with preview capabilities
- Email notifications with recipient resolution, opt-out enforcement, rate limiting, and retry helpers
- Comprehensive logging and masking for sensitive payloads

**Section sources**
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransStatusSyncService.php:1-73](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php#L1-L73)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [SignatureVerifier.php:1-34](file://backend/app/Services/Midtrans/SignatureVerifier.php#L1-L34)
- [StatusMapper.php:1-41](file://backend/app/Services/Midtrans/StatusMapper.php#L1-L41)
- [StatusTransitionGuard.php:1-77](file://backend/app/Services/Midtrans/StatusTransitionGuard.php#L1-L77)
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [NotificationService.php:1-713](file://backend/app/Services/Notifications/NotificationService.php#L1-L713)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)
- [EmailOptOutController.php:1-48](file://backend/app/Http/Controllers/EmailOptOutController.php#L1-L48)

## Architecture Overview
The system exposes REST endpoints for initiating payments and receiving Midtrans webhooks. The notification controller delegates to a service that verifies signatures, maps statuses, enforces allowed transitions, logs inbound/outbound traffic, and records payments idempotently. A separate sync service can poll Midtrans Status API for reconciliation. The email subsystem resolves recipients, respects opt-outs and rate limits, and dispatches via Laravel’s Notification facade using configured mailers.

```mermaid
sequenceDiagram
participant Client as "Portal / Admin"
participant TxCtrl as "MidtransTransactionController"
participant InitSvc as "MidtransInitiationService"
participant ClientIF as "MidtransClient"
participant SnapCli as "MidtransSnapClient"
participant DB as "MidtransTransaction (Model)"
participant WebhookCtrl as "MidtransNotificationController"
participant NotifSvc as "MidtransNotificationService"
participant SyncSvc as "MidtransStatusSyncService"
participant LogSrv as "MidtransLogService"
Client->>TxCtrl : POST /api/midtrans/transactions
TxCtrl->>InitSvc : initiate(...)
InitSvc->>ClientIF : createSnapTransaction(SnapPayload)
ClientIF->>SnapCli : call Midtrans Snap API
SnapCli-->>ClientIF : {token, redirect_url}
ClientIF-->>InitSvc : token + redirect
InitSvc->>DB : persist transaction record
InitSvc-->>TxCtrl : result
TxCtrl-->>Client : {order_id, snap_token, redirect_url, gross_amount}
Note over Client,SnapCli : Buyer completes payment on Midtrans page
Midtrans-->>WebhookCtrl : POST /api/midtrans/notification
WebhookCtrl->>NotifSvc : handle(rawPayload, rawBody, remoteIp)
NotifSvc->>LogSrv : recordInbound()
NotifSvc->>NotifSvc : verify signature + amount + transition
NotifSvc->>DB : update MidtransTransaction
NotifSvc->>DB : create Pembayaran(s) if success
NotifSvc-->>WebhookCtrl : ok or error code
Client->>SyncSvc : manual sync request
SyncSvc->>ClientIF : getStatus(orderId)
ClientIF->>SnapCli : call Midtrans Status API
SnapCli-->>ClientIF : status response
ClientIF-->>SyncSvc : status DTO
SyncSvc->>NotifSvc : processVerifiedPayload(trx, payload)
NotifSvc->>DB : update transaction + record pembayaran if needed
```

**Diagram sources**
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransStatusSyncService.php:1-73](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php#L1-L73)
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)

## Detailed Component Analysis

### Midtrans Payment Gateway Integration

#### Webhook Handling Flow
- Controller receives webhook, passes raw body and IP to service
- Service checks webhook enabled flag, records inbound log, verifies signature, validates amount, maps status, enforces transitions, updates transaction, and records payments idempotently
- Returns appropriate HTTP status codes based on processing outcome

```mermaid
flowchart TD
Start(["Receive Webhook"]) --> CheckEnabled["Check webhook_enabled config"]
CheckEnabled --> |Disabled| RejectDisabled["Reject with WebhookDisabledException"]
CheckEnabled --> RecordInbound["Record inbound log"]
RecordInbound --> VerifySig["Verify signature"]
VerifySig --> |Invalid| RejectSig["Return 403 INVALID_SIGNATURE"]
VerifySig --> BeginTx["Begin DB transaction with deadlock retry"]
BeginTx --> LockTrx["Lock MidtransTransaction by order_id"]
LockTrx --> Exists{"Order exists?"}
Exists --> |No| RejectNotFound["Return 404 ORDER_NOT_FOUND"]
Exists --> ValidateAmt["Validate gross_amount"]
ValidateAmt --> |Mismatch| RejectAmt["Return 422 AMOUNT_MISMATCH"]
ValidateAmt --> MapStatus["Map transaction_status + fraud_status"]
MapStatus --> Transition["Check allowed transition"]
Transition --> |Invalid| RejectTransition["Return 409 INVALID_STATUS_TRANSITION"]
Transition --> UpdateTrx["Update MidtransTransaction"]
UpdateTrx --> Success{"Is success?"}
Success --> |Yes| RecordPembayaran["Create Pembayaran(s) idempotently"]
Success --> |No| EndOk["Return 200 OK"]
RecordPembayaran --> EndOk
```

**Diagram sources**
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [SignatureVerifier.php:1-34](file://backend/app/Services/Midtrans/SignatureVerifier.php#L1-L34)
- [StatusMapper.php:1-41](file://backend/app/Services/Midtrans/StatusMapper.php#L1-L41)
- [StatusTransitionGuard.php:1-77](file://backend/app/Services/Midtrans/StatusTransitionGuard.php#L1-L77)
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)

**Section sources**
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [SignatureVerifier.php:1-34](file://backend/app/Services/Midtrans/SignatureVerifier.php#L1-L34)
- [StatusMapper.php:1-41](file://backend/app/Services/Midtrans/StatusMapper.php#L1-L41)
- [StatusTransitionGuard.php:1-77](file://backend/app/Services/Midtrans/StatusTransitionGuard.php#L1-L77)
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)

#### Transaction Status Synchronization
- Manual sync calls Midtrans Status API, logs outbound, then delegates to notification service to apply shared processing logic
- Prevents calling Midtrans when transaction is already terminal

```mermaid
sequenceDiagram
participant Caller as "Caller"
participant SyncSvc as "MidtransStatusSyncService"
participant ClientIF as "MidtransClient"
participant SnapCli as "MidtransSnapClient"
participant LogSrv as "MidtransLogService"
participant NotifSvc as "MidtransNotificationService"
Caller->>SyncSvc : syncManual(MidtransTransaction)
SyncSvc->>SyncSvc : check terminal status
SyncSvc->>ClientIF : getStatus(orderId)
ClientIF->>SnapCli : call Midtrans Status API
SnapCli-->>ClientIF : status response
ClientIF-->>SyncSvc : MidtransStatusResponse
SyncSvc->>LogSrv : recordOutbound(...)
SyncSvc->>NotifSvc : processVerifiedPayload(trx, payload)
NotifSvc-->>SyncSvc : NotificationResult
```

**Diagram sources**
- [MidtransStatusSyncService.php:1-73](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php#L1-L73)
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)

**Section sources**
- [MidtransStatusSyncService.php:1-73](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php#L1-L73)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)

#### Fee Calculation Services
- Computes admin fees per channel with flat or percent+flat types
- Provides available channels metadata with optional fee previews
- Validates gross amount invariant across internal calculations

```mermaid
classDiagram
class MidtransFeeService {
+computeFee(amountPaid, channel) int
+availableChannels(amountPreview) array
+isValidChannel(channel) bool
+assertGrossInvariant(amountPaid, feeAmount, grossAmount) void
-resolveChannelConfig(channel) array|null
-calculateFromConfig(amountPaid, config) int
-formatPercentDescription(percent, flat) string
}
```

**Diagram sources**
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)

**Section sources**
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)

#### API Client Implementations
- MidtransClient interface defines contract for creating Snap transactions and querying status
- MidtransSnapClient implements the contract using Midtrans SDK, handles CA bundle configuration, and maps exceptions to domain-specific errors

```mermaid
classDiagram
class MidtransClient {
<<interface>>
+createSnapTransaction(payload) array
+getStatus(orderId) MidtransStatusResponse
+isConfigured() bool
}
class MidtransSnapClient {
+createSnapTransaction(payload) array
+getStatus(orderId) MidtransStatusResponse
+isConfigured() bool
}
MidtransSnapClient ..|> MidtransClient : "implements"
```

**Diagram sources**
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)

**Section sources**
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)

#### Order ID Generation
- Generates unique order IDs with prefix and epoch timestamp, validates length and character set constraints

```mermaid
flowchart TD
GenStart["Generate orderId(kodeTagihan)"] --> Build["Build 'prefix-kodeTagihan-epochMs'"]
Build --> ValidateLen["Validate max length"]
ValidateLen --> ValidateChars["Validate allowed characters"]
ValidateChars --> Return["Return orderId"]
```

**Diagram sources**
- [OrderIdGenerator.php:1-64](file://backend/app/Services/Midtrans/OrderIdGenerator.php#L1-L64)

**Section sources**
- [OrderIdGenerator.php:1-64](file://backend/app/Services/Midtrans/OrderIdGenerator.php#L1-L64)

#### Configuration
- Midtrans configuration includes toggles for enabling features, environment, credentials, fee settings, default channel, expiry, order prefix, finish URL, and log retention

```mermaid
graph TB
Cfg["config/midtrans.php"] --> Enabled["enabled"]
Cfg --> WebhookEnabled["webhook_enabled"]
Cfg --> Environment["environment"]
Cfg --> ServerKey["server_key"]
Cfg --> ClientKey["client_key"]
Cfg --> MerchantId["merchant_id"]
Cfg --> FeeFlat["fee_flat"]
Cfg --> FeeChannels["fee_channels"]
Cfg --> DefaultChannel["default_channel"]
Cfg --> MinAmount["min_amount"]
Cfg --> ExpiryHours["expiry_hours"]
Cfg --> OrderPrefix["order_prefix"]
Cfg --> FinishUrl["finish_url"]
Cfg --> LogRetention["log_retention_days"]
```

**Diagram sources**
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)

**Section sources**
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)

### Email Notification System

#### Multi-channel Support and Opt-out Management
- NotificationService orchestrates sending via Laravel’s Notification facade using configured mailers
- RecipientResolver determines target email addresses; EmailOptOut enforces opt-outs per type or all
- Rate limiting prevents excessive emails per branch per hour
- RetryFailed re-dispatches previously failed notifications after validation and rate limit checks

```mermaid
sequenceDiagram
participant App as "Application"
participant NotifSvc as "NotificationService"
participant Resolver as "RecipientResolver"
participant OptOut as "EmailOptOut"
participant Mailer as "Laravel Mailer"
participant Log as "NotificationLog"
App->>NotifSvc : sendTagihanBaru(tagihans, siswa)
NotifSvc->>Resolver : resolve(siswa)
Resolver-->>NotifSvc : email
NotifSvc->>OptOut : isOptedOut(email, type)
OptOut-->>NotifSvc : opted_out?
NotifSvc->>NotifSvc : validateEmail(email)
NotifSvc->>NotifSvc : checkRateLimit(branchId)
NotifSvc->>Mailer : notify(Notification)
Mailer-->>NotifSvc : sent or exception
NotifSvc->>Log : logNotification(status, reason, error_message)
```

**Diagram sources**
- [NotificationService.php:1-713](file://backend/app/Services/Notifications/NotificationService.php#L1-L713)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)
- [mail.php:1-119](file://backend/config/mail.php#L1-L119)

**Section sources**
- [NotificationService.php:1-713](file://backend/app/Services/Notifications/NotificationService.php#L1-L713)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)
- [mail.php:1-119](file://backend/config/mail.php#L1-L119)

#### Unsubscribe Flow
- EmailOptOutController renders unsubscribe page and updates opt-out preferences securely via signed tokens

```mermaid
sequenceDiagram
participant User as "User"
participant Ctrl as "EmailOptOutController"
participant Model as "EmailOptOut"
User->>Ctrl : GET /api/unsubscribe/{token}
Ctrl->>Model : find by token
Model-->>Ctrl : opt-out record
Ctrl-->>User : unsubscribe view
User->>Ctrl : PUT /api/unsubscribe/{token} {type}
Ctrl->>Model : update notification_type
Model-->>Ctrl : updated record
Ctrl-->>User : confirmation view
```

**Diagram sources**
- [EmailOptOutController.php:1-48](file://backend/app/Http/Controllers/EmailOptOutController.php#L1-L48)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)

**Section sources**
- [EmailOptOutController.php:1-48](file://backend/app/Http/Controllers/EmailOptOutController.php#L1-L48)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)

## Dependency Analysis
- Controllers depend on services for business logic and third-party interactions
- Services compose smaller utilities (signature verification, status mapping, transition guard, logging)
- Models represent persistent entities and provide relationships and scopes
- Configuration drives behavior at runtime without redeploy

```mermaid
graph LR
TxCtrl["MidtransTransactionController"] --> InitSvc["MidtransInitiationService"]
TxCtrl --> FeeSvc["MidtransFeeService"]
TxCtrl --> OrderGen["OrderIdGenerator"]
TxCtrl --> TxModel["MidtransTransaction"]
WebhookCtrl["MidtransNotificationController"] --> NotifSvc["MidtransNotificationService"]
NotifSvc --> SigVer["SignatureVerifier"]
NotifSvc --> StatMap["StatusMapper"]
NotifSvc --> TransGuard["StatusTransitionGuard"]
NotifSvc --> LogSvc["MidtransLogService"]
NotifSvc --> FeeSvc
SyncSvc["MidtransStatusSyncService"] --> ClientIF["MidtransClient"]
SyncSvc --> NotifSvc
SyncSvc --> LogSvc
ClientIF --> SnapCli["MidtransSnapClient"]
NotifEmail["NotificationService"] --> Resolver["RecipientResolver"]
NotifEmail --> OptOut["EmailOptOut"]
NotifEmail --> MailCfg["mail.php"]
```

**Diagram sources**
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransStatusSyncService.php:1-73](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php#L1-L73)
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [SignatureVerifier.php:1-34](file://backend/app/Services/Midtrans/SignatureVerifier.php#L1-L34)
- [StatusMapper.php:1-41](file://backend/app/Services/Midtrans/StatusMapper.php#L1-L41)
- [StatusTransitionGuard.php:1-77](file://backend/app/Services/Midtrans/StatusTransitionGuard.php#L1-L77)
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [OrderIdGenerator.php:1-64](file://backend/app/Services/Midtrans/OrderIdGenerator.php#L1-L64)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)
- [NotificationService.php:1-713](file://backend/app/Services/Notifications/NotificationService.php#L1-L713)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)
- [mail.php:1-119](file://backend/config/mail.php#L1-L119)

**Section sources**
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [MidtransNotificationController.php:1-35](file://backend/app/Http/Controllers/MidtransNotificationController.php#L1-L35)
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransStatusSyncService.php:1-73](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php#L1-L73)
- [MidtransClient.php:1-27](file://backend/app/Services/Midtrans/MidtransClient.php#L1-L27)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)
- [SignatureVerifier.php:1-34](file://backend/app/Services/Midtrans/SignatureVerifier.php#L1-L34)
- [StatusMapper.php:1-41](file://backend/app/Services/Midtrans/StatusMapper.php#L1-L41)
- [StatusTransitionGuard.php:1-77](file://backend/app/Services/Midtrans/StatusTransitionGuard.php#L1-L77)
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [OrderIdGenerator.php:1-64](file://backend/app/Services/Midtrans/OrderIdGenerator.php#L1-L64)
- [MidtransTransaction.php:1-85](file://backend/app/Models/MidtransTransaction.php#L1-L85)
- [NotificationService.php:1-713](file://backend/app/Services/Notifications/NotificationService.php#L1-L713)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)
- [mail.php:1-119](file://backend/config/mail.php#L1-L119)

## Performance Considerations
- Database locking and transactions: Webhook processing uses lockForUpdate and retries on deadlocks to ensure consistency during concurrent updates
- Idempotency: Payment recording skips duplicates by checking existing midtrans_order_id
- Rate limiting: Email notifications enforce per-branch limits to protect downstream providers
- Logging overhead: Inbound/outbound logs are masked and wrapped in try/catch to avoid impacting core flows
- Fee computation: Reads configuration at call time to reflect runtime changes without container caching side effects

[No sources needed since this section provides general guidance]

## Troubleshooting Guide

### Common Midtrans Issues
- Invalid signature: Ensure server_key matches and signature verification path is used; inspect rejected responses
- Amount mismatch: Confirm gross_amount equals amount_paid + fee_amount; use fee service assertions
- Status unavailable: Handle MidtransStatusUnavailableException and consider retry/backoff strategies
- Transaction not yet processed: When Midtrans returns 404 due to unregistered transaction, surface actionable error
- Overpayment blocked: Validate remaining balance before recording payments; review batch vs single flows

**Section sources**
- [MidtransNotificationService.php:1-284](file://backend/app/Services/Midtrans/MidtransNotificationService.php#L1-L284)
- [MidtransSnapClient.php:1-123](file://backend/app/Services/Midtrans/MidtransSnapClient.php#L1-L123)
- [MidtransFeeService.php:1-144](file://backend/app/Services/Midtrans/MidtransFeeService.php#L1-L144)

### Email Delivery Problems
- Opt-out enforcement: Verify EmailOptOut entries and unsubscribe links; confirm token-based updates
- Rate limiting: Monitor branch-level rate limiter counters; adjust thresholds if necessary
- Mailer configuration: Validate mailers (smtp, ses, postmark, resend, sendmail, log) and failover/roundrobin setups
- Retry failed notifications: Use retryFailed to re-dispatch after fixing transient issues

**Section sources**
- [NotificationService.php:1-713](file://backend/app/Services/Notifications/NotificationService.php#L1-L713)
- [EmailOptOut.php:1-42](file://backend/app/Models/EmailOptOut.php#L1-L42)
- [EmailOptOutController.php:1-48](file://backend/app/Http/Controllers/EmailOptOutController.php#L1-L48)
- [mail.php:1-119](file://backend/config/mail.php#L1-L119)

### Debugging Techniques
- Inspect inbound/outbound logs for exact payloads and HTTP statuses
- Use show endpoint to poll transaction status and compare local state with Midtrans
- Enable detailed logging around signature verification and transition checks
- Validate configuration values for keys, environment, and fee channels

**Section sources**
- [MidtransLogService.php:1-109](file://backend/app/Services/Midtrans/MidtransLogService.php#L1-L109)
- [MidtransTransactionController.php:1-127](file://backend/app/Http/Controllers/MidtransTransactionController.php#L1-L127)
- [midtrans.php:1-130](file://backend/config/midtrans.php#L1-L130)

## Conclusion
The Handayani system integrates Midtrans through a robust, layered architecture that emphasizes security, idempotency, and observability. Webhooks are verified and validated, statuses are synchronized safely, and fees are computed transparently. The email notification system supports multiple mailers, enforces opt-outs, applies rate limits, and provides retry mechanisms. Together, these components deliver reliable external integrations with clear troubleshooting paths and strong operational controls.