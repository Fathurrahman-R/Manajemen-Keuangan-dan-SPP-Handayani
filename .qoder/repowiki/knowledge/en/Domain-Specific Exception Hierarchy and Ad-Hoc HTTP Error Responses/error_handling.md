The codebase uses a hybrid error-handling approach with two distinct patterns:

1. Domain-specific exception hierarchy (Midtrans domain)
- A dedicated App\Exceptions\Midtrans\ namespace contains ~20 typed exceptions, all extending an abstract MidtransException base class.
- The base class declares $errorCode (string) and $httpStatus (int) properties; subclasses set these as constants and pass contextual data to the constructor. Examples include TagihanNotFoundException, AmountBelowMinimumException, InvalidSignatureException, OverpaymentBlockedException.
- Services in app/Services/Midtrans/ throw these specific exceptions to signal business-rule violations or external failures.
- In MidtransInitiationService, outbound calls to Midtrans Snap are wrapped in try/catch blocks that mark transactions as failure on MidtransUnavailableException, log the error via MidtransLogService, then re-throw so the controller layer can handle it.

2. Ad-hoc JSON responses in controllers (non-Midtrans paths)
- Controllers outside the payment domain do not use custom exceptions. Instead they return response()->json([...]) directly with inline error_code / message keys.
- Validation failures and other early exits frequently use throw new HttpResponseException(response([ ... ])) rather than throwing typed exceptions.
- There is no centralized Handler override or global exception-to-JSON mapper visible in the repository — each controller decides its own response shape.

3. Frontend-v2 (Filament panel) side
- frontend-v2/app/Services/MidtransApiException.php defines a separate MidtransApiException carrying errorCode, data, and httpStatus, with a getUserMessage() helper that resolves messages from lang/id/midtrans.php translation files.

Conventions observed:
- Business-rule errors -> throw a named App\Exceptions\Midtrans\*Exception.
- External service failures -> catch the specific Midtrans exception, persist failure state, log via MidtransLogService, then re-throw.
- Simple not-found / forbidden checks in controllers -> return response()->json(['error_code' => ..., 'message' => ...], $status) directly.
- No unified error envelope across the whole API; response shapes vary between controllers.