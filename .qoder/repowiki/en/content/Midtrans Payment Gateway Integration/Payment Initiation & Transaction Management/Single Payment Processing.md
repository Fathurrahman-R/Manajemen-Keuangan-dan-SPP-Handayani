# Single Payment Processing

<cite>
**Referenced Files in This Document**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [InitiationResult.php](file://backend/app/Services/Midtrans/Dto/InitiationResult.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)
- [AmountBelowMinimumException.php](file://backend/app/Exceptions/Midtrans/AmountBelowMinimumException.php)
- [AmountExceedsSisaException.php](file://backend/app/Exceptions/Midtrans/AmountExceedsSisaException.php)
- [TagihanForbiddenException.php](file://backend/app/Exceptions/Midtrans/TagihanForbiddenException.php)
- [TagihanHasPendingTransactionException.php](file://backend/app/Exceptions/Midtrans/TagihanHasPendingTransactionException.php)
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
This document explains the complete workflow for single payment processing in the Handayani system, from invoking initiate() to returning an InitiationResult. It covers feature flag checks, client configuration validation, Tagihan loading with lockForUpdate(), ownership verification, sisa_tagihan calculation, amount validation against minimum and maximum limits, pending transaction checks, fee computation, order ID generation, MidtransTransaction persistence, Snap payload construction, and final API call execution. Practical guidance is included on how to initiate a single payment, handle different exceptions, and process the InitiationResult response.

## Project Structure
The single payment flow spans services, models, DTOs, configuration, and exceptions:
- Service orchestration: MidtransInitiationService
- External integration: MidtransSnapClient (implements MidtransClient)
- Fee logic: MidtransFeeService
- Order ID generation: OrderIdGenerator
- Domain models: MidtransTransaction, Tagihan
- Configuration: config/midtrans.php
- DTOs: InitiationResult, SnapPayload
- Internal status enum: MidtransInternalStatus
- Exceptions: various Midtrans-related exceptions

```mermaid
graph TB
subgraph "Orchestration"
A["MidtransInitiationService"]
end
subgraph "External Integration"
B["MidtransSnapClient"]
end
subgraph "Domain Models"
C["Tagihan"]
D["MidtransTransaction"]
end
subgraph "Support"
E["MidtransFeeService"]
F["OrderIdGenerator"]
G["config/midtrans.php"]
H["InitiationResult"]
I["SnapPayload"]
J["MidtransInternalStatus"]
end
A --> B
A --> C
A --> D
A --> E
A --> F
A --> G
A --> H
A --> I
A --> J
```

**Diagram sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [InitiationResult.php](file://backend/app/Services/Midtrans/Dto/InitiationResult.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

**Section sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [InitiationResult.php](file://backend/app/Services/Midtrans/Dto/InitiationResult.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

## Core Components
- MidtransInitiationService: Orchestrates single payment initiation, including validations, locking, fee computation, persistence, Snap payload creation, and API invocation.
- MidtransSnapClient: Configures Midtrans SDK credentials and performs Snap transaction creation and status retrieval.
- MidtransFeeService: Computes admin fees per channel and validates gross amount invariant.
- OrderIdGenerator: Generates unique, Midtrans-compliant order IDs.
- MidtransTransaction: Represents a Midtrans transaction record with scopes and relations.
- Tagihan: Represents a bill; used to compute remaining balance (sisa_tagihan).
- SnapPayload and InitiationResult: Strongly-typed DTOs for Snap request and initiation response.
- MidtransInternalStatus: Enumerates internal statuses and helpers for terminal/success checks.
- Configuration (config/midtrans.php): Feature flags, credentials, fee rules, min amount, expiry, callbacks, and logging retention.

**Section sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [InitiationResult.php](file://backend/app/Services/Midtrans/Dto/InitiationResult.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

## Architecture Overview
End-to-end sequence for single payment initiation:

```mermaid
sequenceDiagram
participant Client as "Caller"
participant Svc as "MidtransInitiationService"
participant DB as "Database"
participant ModelT as "Tagihan"
participant ModelM as "MidtransTransaction"
participant Fee as "MidtransFeeService"
participant Gen as "OrderIdGenerator"
participant Snap as "MidtransSnapClient"
Client->>Svc : initiate(user, kodeTagihan, amountPaid, paymentChannel?)
Svc->>DB : begin transaction
Svc->>ModelT : load by kode_tagihan + lockForUpdate()
ModelT-->>Svc : Tagihan or null
alt not found
Svc-->>Client : throw TagihanNotFoundException
else found
Svc->>Svc : verify user ownership (user.siswa.nis == tagihan.nis)
alt forbidden
Svc-->>Client : throw TagihanForbiddenException
else ok
Svc->>Svc : calculate sisa = jenis_tagihan.jumlah - tagihan.tmp
alt already paid (<=0)
Svc-->>Client : throw TagihanSudahLunasException
else ok
Svc->>Svc : validate amount >= min_amount and <= sisa
alt invalid amount
Svc-->>Client : throw AmountBelowMinimumException / AmountExceedsSisaException
else ok
Svc->>ModelM : check pending in-flight by kode_tagihan
alt pending exists
Svc-->>Client : throw TagihanHasPendingTransactionException
else ok
Svc->>Fee : computeFee(amountPaid, paymentChannel)
Fee-->>Svc : feeAmount
Svc->>Svc : assertGrossInvariant(amountPaid, feeAmount, gross)
Svc->>Gen : generate(kodeTagihan)
Gen-->>Svc : orderId
Svc->>ModelM : create MidtransTransaction (status=pending)
Svc->>Svc : build SnapPayload (items, customer, expiry, callbacks, enabled_payments)
Svc->>Snap : createSnapTransaction(SnapPayload)
alt success
Svc->>ModelM : update snap_token, redirect_url
Svc-->>Client : return InitiationResult
else unavailable
Svc->>ModelM : update status=failure
Svc-->>Client : throw MidtransUnavailableException
end
end
end
end
end
end
Svc->>DB : commit transaction
```

**Diagram sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [InitiationResult.php](file://backend/app/Services/Midtrans/Dto/InitiationResult.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

## Detailed Component Analysis

### Single Payment Orchestration (initiate)
Key steps performed within a database transaction:
- Feature flag check: midtrans.enabled must be true.
- Client configuration check: server_key, client_key, merchant_id present.
- Load Tagihan with lockForUpdate() to prevent concurrent modifications.
- Ownership verification: user.siswa.nis must match tagihan.nis.
- Calculate sisa_tagihan: jenis_tagihan.jumlah - tagihan.tmp.
- Validate amount: must be between configured min_amount and sisa_tagihan.
- Pending transaction check: reject if any pending in-flight transaction exists for the same tagihan.
- Compute fee and gross amount using MidtransFeeService and assert invariant.
- Generate order ID via OrderIdGenerator.
- Persist MidtransTransaction with status pending and metadata.
- Build SnapPayload with item details, customer details, expiry, callbacks, and enabled payments.
- Call MidtransSnapClient.createSnapTransaction(); on success, persist token and redirect URL; on failure, mark transaction as failure and rethrow.
- Return InitiationResult containing orderId, snapToken, redirectUrl, amounts, and expiredAt.

```mermaid
flowchart TD
Start(["Start initiate"]) --> CheckEnabled["Check midtrans.enabled"]
CheckEnabled --> |false| ThrowDisabled["Throw MidtransDisabledException"]
CheckEnabled --> |true| CheckConfig["Check client configuration"]
CheckConfig --> |invalid| ThrowNotConfigured["Throw MidtransNotConfiguredException"]
CheckConfig --> |valid| BeginTx["Begin DB transaction"]
BeginTx --> LoadTagihan["Load Tagihan with lockForUpdate()"]
LoadTagihan --> Found{"Found?"}
Found --> |no| ThrowNotFound["Throw TagihanNotFoundException"]
Found --> |yes| VerifyOwner["Verify ownership (user.siswa.nis == tagihan.nis)"]
VerifyOwner --> OwnerOk{"Owner OK?"}
OwnerOk --> |no| ThrowForbidden["Throw TagihanForbiddenException"]
OwnerOk --> |yes| CalcSisa["Calculate sisa = jumlah - tmp"]
CalcSisa --> PaidOk{"sisa > 0?"}
PaidOk --> |no| ThrowPaid["Throw TagihanSudahLunasException"]
PaidOk --> |yes| ValidateAmt["Validate min_amount <= amount <= sisa"]
ValidateAmt --> AmtOk{"Valid?"}
AmtOk --> |no| ThrowAmtErr["Throw AmountBelowMinimumException or AmountExceedsSisaException"]
AmtOk --> |yes| CheckPending["Check pending in-flight transactions"]
CheckPending --> PendingOk{"No pending?"}
PendingOk --> |no| ThrowPending["Throw TagihanHasPendingTransactionException"]
PendingOk --> |yes| ComputeFee["Compute fee and gross"]
ComputeFee --> AssertInv["Assert gross == amount + fee"]
AssertInv --> GenOrder["Generate order ID"]
GenOrder --> PersistTrx["Persist MidtransTransaction (pending)"]
PersistTrx --> BuildPayload["Build SnapPayload"]
BuildPayload --> CallSnap["Call MidtransSnapClient.createSnapTransaction"]
CallSnap --> SnapOk{"Success?"}
SnapOk --> |no| MarkFail["Mark transaction failure and log"]
MarkFail --> ThrowUnavailable["Throw MidtransUnavailableException"]
SnapOk --> |yes| UpdateSnap["Update snap_token and redirect_url"]
UpdateSnap --> ReturnResult["Return InitiationResult"]
ReturnTx(["Commit transaction"]) --> End(["End"])
```

**Diagram sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [InitiationResult.php](file://backend/app/Services/Midtrans/Dto/InitiationResult.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

**Section sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [InitiationResult.php](file://backend/app/Services/Midtrans/Dto/InitiationResult.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

### Snap Payload Construction
- Item details include one line for the tagihan and one for the admin fee.
- Customer details are derived from siswa name parts and optional wali email (only added when valid).
- Expiry uses start_time, unit hour, duration 24 hours.
- Callbacks are resolved from configuration; enabled_payments restrict channels based on selected key.

```mermaid
classDiagram
class SnapPayload {
+string orderId
+int grossAmount
+array itemDetails
+array customerDetails
+array expiry
+?array callbacks
+?array enabledPayments
}
```

**Diagram sources**
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)

**Section sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)

### Midtrans Transaction Model and Status
- MidtransTransaction stores all relevant fields, casts numeric values and arrays, and provides scope pendingInFlight().
- MidtransInternalStatus enumerates states and includes helpers for terminal and success checks.

```mermaid
classDiagram
class MidtransTransaction {
+string order_id
+string kode_tagihan
+?array batch_items
+string nis
+int amount_paid
+int fee_amount
+int gross_amount
+string currency
+string status
+?string payment_type
+?string snap_token
+?string snap_redirect_url
+datetime expired_at
+datetime paid_at
+int initiator_user_id
+int branch_id
+bool isBatch()
+scopePendingInFlight(query)
}
class MidtransInternalStatus {
<<enum>>
+Pending
+Settlement
+Capture
+Deny
+Cancel
+Expire
+Failure
+Refund
+PartialRefund
+isTerminal() bool
+isSuccess() bool
}
MidtransTransaction --> MidtransInternalStatus : "uses status value"
```

**Diagram sources**
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

**Section sources**
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

### Fee Computation and Invariants
- MidtransFeeService supports flat and percent-based fees per channel, with fallback to a global flat fee when channel is unknown.
- Gross amount invariant ensures consistency: gross == amount + fee.

```mermaid
flowchart TD
A["Input: amountPaid, channel"] --> Resolve["Resolve channel config"]
Resolve --> HasCfg{"Config found?"}
HasCfg --> |no| UseFlat["Use global fee_flat"]
HasCfg --> |yes| Type{"type=percent?"}
Type --> |yes| PercentCalc["Round((amount*percent/100)+flat)"]
Type --> |no| FlatCalc["Use amount or flat"]
PercentCalc --> Result["feeAmount"]
FlatCalc --> Result
UseFlat --> Result
Result --> Assert["Assert gross == amount + fee"]
```

**Diagram sources**
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [midtrans.php](file://backend/config/midtrans.php)

**Section sources**
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [midtrans.php](file://backend/config/midtrans.php)

### Order ID Generation
- Generates order IDs with prefix, tagihan code, and epoch milliseconds.
- Validates length and allowed characters to comply with Midtrans constraints.

```mermaid
flowchart TD
Start(["generate(kodeTagihan)"]) --> Build["Build 'prefix-kodeTagihan-epochMs'"]
Build --> ValidateLen{"Length <= 50?"}
ValidateLen --> |no| ErrLen["Throw InvalidArgumentException"]
ValidateLen --> |yes| ValidateChars{"Valid chars only?"}
ValidateChars --> |no| ErrChars["Throw InvalidArgumentException"]
ValidateChars --> |yes| Return["Return orderId"]
```

**Diagram sources**
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [midtrans.php](file://backend/config/midtrans.php)

**Section sources**
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [midtrans.php](file://backend/config/midtrans.php)

### API Integration (Snap)
- MidtransSnapClient sets SDK configuration (server/client keys, environment, CA bundle, 3DS).
- createSnapTransaction maps SnapPayload into SDK parameters and returns token and redirect URL.
- getStatus retrieves transaction status and maps specific errors to actionable exceptions.

```mermaid
sequenceDiagram
participant Svc as "MidtransInitiationService"
participant Client as "MidtransSnapClient"
participant SDK as "Midtrans SDK"
Svc->>Client : createSnapTransaction(SnapPayload)
Client->>SDK : Snap : : createTransaction(params)
SDK-->>Client : {token, redirect_url}
Client-->>Svc : {token, redirect_url}
```

**Diagram sources**
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)

**Section sources**
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)

## Dependency Analysis
High-level dependencies for single payment initiation:

```mermaid
graph LR
Init["MidtransInitiationService"] --> Snap["MidtransSnapClient"]
Init --> Fee["MidtransFeeService"]
Init --> Gen["OrderIdGenerator"]
Init --> Tx["MidtransTransaction (model)"]
Init --> Tag["Tagihan (model)"]
Init --> Cfg["config/midtrans.php"]
Init --> Res["InitiationResult (DTO)"]
Init --> Pay["SnapPayload (DTO)"]
Init --> St["MidtransInternalStatus (enum)"]
```

**Diagram sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [InitiationResult.php](file://backend/app/Services/Midtrans/Dto/InitiationResult.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

**Section sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransSnapClient.php](file://backend/app/Services/Midtrans/MidtransSnapClient.php)
- [MidtransFeeService.php](file://backend/app/Services/Midtrans/MidtransFeeService.php)
- [OrderIdGenerator.php](file://backend/app/Services/Midtrans/OrderIdGenerator.php)
- [MidtransTransaction.php](file://backend/app/Models/MidtransTransaction.php)
- [Tagihan.php](file://backend/app/Models/Tagihan.php)
- [midtrans.php](file://backend/config/midtrans.php)
- [InitiationResult.php](file://backend/app/Services/Midtrans/Dto/InitiationResult.php)
- [SnapPayload.php](file://backend/app/Services/Midtrans/Dto/SnapPayload.php)
- [MidtransInternalStatus.php](file://backend/app/Services/Midtrans/MidtransInternalStatus.php)

## Performance Considerations
- Database locking: lockForUpdate() prevents race conditions but may increase contention; ensure minimal work inside the transaction and keep it short.
- Fee computation: pure function with O(1) complexity; no external calls.
- Snap API call: network-bound; consider timeouts and retries at higher layers if needed.
- Logging: outbound logs are recorded synchronously; ensure logging backend is performant.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common exceptions and their meanings:
- MidtransDisabledException: Feature flag disabled.
- MidtransNotConfiguredException: Missing credentials or merchant ID.
- TagihanNotFoundException: Invalid or missing tagihan code.
- TagihanForbiddenException: User does not own the tagihan.
- TagihanSudahLunasException: Tagihan already fully paid.
- AmountBelowMinimumException: amountPaid below configured minimum.
- AmountExceedsSisaException: amountPaid exceeds remaining balance.
- TagihanHasPendingTransactionException: An active pending transaction exists for the tagihan.
- MidtransUnavailableException: Snap API call failed; transaction marked as failure.

Handling recommendations:
- For AmountBelowMinimumException and AmountExceedsSisaException, prompt the user to adjust the amount.
- For TagihanHasPendingTransactionException, reuse existing snap_token and redirect_url from the exception’s pendingData to resume checkout.
- For MidtransUnavailableException, inform the user to retry later; inspect logs for details.

**Section sources**
- [AmountBelowMinimumException.php](file://backend/app/Exceptions/Midtrans/AmountBelowMinimumException.php)
- [AmountExceedsSisaException.php](file://backend/app/Exceptions/Midtrans/AmountExceedsSisaException.php)
- [TagihanForbiddenException.php](file://backend/app/Exceptions/Midtrans/TagihanForbiddenException.php)
- [TagihanHasPendingTransactionException.php](file://backend/app/Exceptions/Midtrans/TagihanHasPendingTransactionException.php)
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)

## Conclusion
Single payment processing in Handayani is implemented as a robust, transactional workflow that enforces business rules, computes accurate fees, persists state, and integrates with Midtrans Snap. The design isolates concerns across services, models, DTOs, and configuration, enabling clear error handling and predictable behavior. By following the documented steps and exception handling strategies, callers can reliably initiate payments and guide users through the Snap checkout experience.