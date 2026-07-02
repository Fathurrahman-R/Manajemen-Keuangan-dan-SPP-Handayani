# Requirements Document

## Introduction

Fitur ini mengubah tampilan tagihan dari format tabel row-by-row menjadi tampilan card-based yang dikelompokkan per siswa. Setiap card siswa menampilkan daftar tagihan yang belum dibayar dan yang sudah lunas. Mekanisme pembayaran diubah dari pembayaran satu-per-satu menjadi pembayaran batch/rekap, di mana pengguna dapat memilih beberapa tagihan sekaligus untuk dibayar dalam satu transaksi. Halaman ini juga dapat diakses oleh siswa/wali siswa dengan tampilan yang sesuai. Perubahan ini merupakan persiapan untuk integrasi payment gateway di masa depan.

## Glossary

- **Tagihan_Card_View**: Komponen tampilan frontend yang menampilkan tagihan dalam format card yang dikelompokkan per siswa
- **Siswa_Card**: Satu unit card yang merepresentasikan seorang siswa beserta daftar tagihan miliknya
- **Tagihan**: Record kewajiban pembayaran yang dimiliki siswa, memiliki status Belum Dibayar, Belum Lunas, atau Lunas
- **Pembayaran_Batch**: Proses pembayaran yang mencakup lebih dari satu tagihan dalam satu transaksi
- **Backend_API**: Laravel API yang menyediakan endpoint untuk data tagihan dan pembayaran
- **Frontend_App**: Aplikasi Laravel + Livewire + Filament yang menampilkan antarmuka pengguna
- **Siswa**: Entitas murid yang memiliki NIS, nama, jenjang, kelas, dan kategori
- **Jenis_Tagihan**: Tipe tagihan yang memiliki nama, jumlah, dan jatuh tempo
- **Rekap_Pembayaran**: Ringkasan total dari beberapa tagihan yang dipilih untuk dibayar sekaligus
- **Admin_Operator**: Pengguna dengan role admin atau operator yang mengelola tagihan dan pembayaran
- **Siswa_Wali_User**: Pengguna dengan role siswa atau wali yang hanya dapat melihat tagihan milik sendiri
- **Branch**: Cabang sekolah yang memisahkan data antar lokasi

## Requirements

### Requirement 1: Tampilan Tagihan Dikelompokkan Per Siswa (Admin View)

**User Story:** As a Admin_Operator, I want to see bills grouped by student in a card layout, so that I can quickly understand each student's billing status at a glance.

#### Acceptance Criteria

1. WHEN the Tagihan page is loaded by an Admin_Operator, THE Tagihan_Card_View SHALL display a list of Siswa_Card components grouped by siswa, sorted alphabetically by siswa nama in ascending order
2. THE Siswa_Card SHALL display the siswa nama, NIS, jenjang (TK/KB/MI), and kelas nama in the card header
3. WHEN a Siswa_Card is displayed, THE Tagihan_Card_View SHALL show all tagihan belonging to that siswa within the card body, displaying for each tagihan: kode_tagihan, jenis_tagihan nama, jenis_tagihan jumlah, jumlah yang telah dibayarkan (tmp), sisa, status, and jatuh_tempo
4. THE Siswa_Card SHALL separate tagihan into two distinct sections within the card body: an upper section containing tagihan with status "Belum Dibayar" or "Belum Lunas", and a lower section containing tagihan with status "Lunas"
5. WHEN no tagihan exists for a siswa, THE Tagihan_Card_View SHALL exclude that siswa from the displayed list
6. THE Tagihan_Card_View SHALL only display data belonging to the authenticated user's branch
7. WHEN the Tagihan page contains more than 10 siswa with tagihan, THE Tagihan_Card_View SHALL paginate the Siswa_Card list with a maximum of 10 cards per page

### Requirement 2: Informasi Detail Tagihan Dalam Card

**User Story:** As a Admin_Operator, I want to see detailed billing information within each student card, so that I can review amounts, due dates, and payment status without navigating away.

#### Acceptance Criteria

1. THE Siswa_Card SHALL display the jenis_tagihan nama, jumlah tagihan (formatted as "Rp." followed by the amount with no decimal places), jatuh_tempo (date), jumlah yang telah dibayar (tmp), sisa tagihan (calculated as jumlah minus tmp), and status for each tagihan belonging to that siswa
2. THE Siswa_Card SHALL display the total sisa tagihan as the sum of sisa (jumlah minus tmp) for all tagihan with status "Belum Dibayar" or "Belum Lunas" belonging to that siswa
3. WHEN a tagihan has status "Lunas", THE Siswa_Card SHALL display the tagihan with a "success" colored status badge indicating completion
4. WHEN a tagihan has status "Belum Lunas", THE Siswa_Card SHALL display the tagihan with a "warning" colored status badge and show the sisa tagihan (jumlah minus tmp) as the remaining amount to be paid
5. WHEN a tagihan has status "Belum Dibayar", THE Siswa_Card SHALL display the tagihan with a "danger" colored status badge and show the full jumlah tagihan as the remaining balance (sisa equals jumlah because tmp is zero)
6. WHEN a tagihan has jatuh_tempo earlier than the current date and status is not "Lunas", THE Siswa_Card SHALL display a visual indicator that the tagihan is overdue

### Requirement 3: Pencarian dan Filter Pada Card View

**User Story:** As a Admin_Operator, I want to search and filter student cards, so that I can quickly find specific students or filter by billing status.

#### Acceptance Criteria

1. WHEN a search query is entered, THE Tagihan_Card_View SHALL filter Siswa_Card components by performing a case-insensitive substring match against siswa nama or NIS, and SHALL reset the pagination to page 1
2. WHEN a jenjang filter is selected, THE Tagihan_Card_View SHALL display only Siswa_Card components matching the selected jenjang (TK, KB, MI)
3. WHEN a status filter is selected, THE Tagihan_Card_View SHALL display only Siswa_Card components that contain at least one tagihan matching the selected status (Belum Dibayar, Belum Lunas, or Lunas)
4. WHEN multiple filters and/or a search query are active simultaneously, THE Tagihan_Card_View SHALL combine all active criteria using AND logic, displaying only Siswa_Card components that satisfy every active filter and search condition
5. THE Tagihan_Card_View SHALL support pagination of Siswa_Card components with selectable items per page options of 5, 10, and 25, defaulting to 5 items per page
6. WHEN no Siswa_Card matches the active search query and filters, THE Tagihan_Card_View SHALL display an empty state message indicating no students were found

### Requirement 4: Seleksi Multiple Tagihan Untuk Pembayaran Batch

**User Story:** As a Admin_Operator, I want to select multiple bills from a student card to pay them together, so that I can process payments more efficiently.

#### Acceptance Criteria

1. THE Siswa_Card SHALL provide a checkbox for each tagihan with status "Belum Dibayar" or "Belum Lunas", and SHALL NOT provide a checkbox for tagihan with status "Lunas"
2. WHEN one or more tagihan checkboxes are selected, THE Siswa_Card SHALL display the sum of sisa (remaining balance) amounts of all selected tagihan as Rekap_Pembayaran, formatted in Rupiah currency
3. WHEN the selection of tagihan checkboxes changes, THE Siswa_Card SHALL immediately recalculate and update the displayed Rekap_Pembayaran total without requiring a page reload
4. WHEN no tagihan checkbox is selected, THE Siswa_Card SHALL disable the "Bayar" action button and hide the Rekap_Pembayaran display
5. THE Siswa_Card SHALL provide a "Pilih Semua" (select all) checkbox to select all tagihan with status "Belum Dibayar" or "Belum Lunas" at once
6. WHEN "Pilih Semua" is toggled on, THE Siswa_Card SHALL select all unpaid tagihan checkboxes simultaneously, and WHEN "Pilih Semua" is toggled off, THE Siswa_Card SHALL deselect all tagihan checkboxes simultaneously
7. WHEN all individual tagihan checkboxes are manually selected, THE Siswa_Card SHALL reflect the "Pilih Semua" checkbox as checked, and WHEN any individual tagihan checkbox is deselected, THE Siswa_Card SHALL reflect the "Pilih Semua" checkbox as unchecked

### Requirement 5: Proses Pembayaran Batch

**User Story:** As a Admin_Operator, I want to pay multiple selected bills in one transaction, so that I can reduce repetitive payment processing.

#### Acceptance Criteria

1. WHEN the "Bayar" action is triggered with selected tagihan, THE Frontend_App SHALL display a payment form showing the Rekap_Pembayaran containing the jenis_tagihan nama, sisa tagihan amount for each selected tagihan, and the total amount to be paid
2. THE payment form SHALL require the user to input metode pembayaran (Tunai/Non-Tunai) and pembayar (payer name, maximum 100 characters)
3. WHEN the payment form is submitted, THE Frontend_App SHALL send a batch payment request to the Backend_API containing the list of selected kode_tagihan, metode pembayaran, and pembayar
4. WHEN a batch payment request is received, THE Backend_API SHALL create one Pembayaran record for each selected tagihan with jumlah equal to the remaining balance (jenis_tagihan jumlah minus tmp) of that tagihan
5. WHEN a batch payment is processed successfully, THE Backend_API SHALL update the status of each tagihan to "Lunas" and set tmp equal to jenis_tagihan jumlah
6. IF a batch payment request fails for any tagihan, THEN THE Backend_API SHALL rollback all payment records in that batch and return an error message indicating which kode_tagihan caused the failure
7. WHEN a batch payment is completed successfully, THE Frontend_App SHALL refresh the Siswa_Card to reflect updated tagihan statuses
8. WHEN a batch payment is completed successfully, THE Frontend_App SHALL provide an option to download the combined kwitansi as a PDF file
9. WHILE the payment form is displayed, THE Frontend_App SHALL disable the submit button until both metode pembayaran and pembayar fields are filled

### Requirement 6: Tampilan Tagihan Untuk Siswa/Wali (Student View)

**User Story:** As a Siswa_Wali_User, I want to see my bills in a clean card-based layout, so that I can easily understand what I owe and what has been paid.

#### Acceptance Criteria

1. WHEN the Tagihan page is loaded by a Siswa_Wali_User, THE Tagihan_Card_View SHALL display only tagihan belonging to the authenticated siswa
2. THE Tagihan_Card_View SHALL display each tagihan as a card showing jenis_tagihan nama, jumlah tagihan, jatuh tempo, jumlah yang telah dibayar (tmp), sisa tagihan, and status, with each status (Belum Dibayar, Belum Lunas, Lunas) rendered using a distinct color-coded badge
3. THE Tagihan_Card_View SHALL display a summary section showing total jumlah tagihan (sum of all tagihan amounts in Rupiah), total sudah dibayar (sum of all tmp in Rupiah), and total sisa (sum of all remaining balances in Rupiah)
4. WHILE the user is a Siswa_Wali_User, THE Tagihan_Card_View SHALL NOT display actions for adding, deleting, or batch-paying tagihan
5. WHILE the user is a Siswa_Wali_User, THE Tagihan_Card_View SHALL display tagihan sorted by jatuh tempo (nearest due date first)
6. THE Tagihan_Card_View SHALL use a responsive card layout that displays in a single-column stack on viewports below 768px wide
7. WHEN the authenticated siswa has no tagihan records, THE Tagihan_Card_View SHALL display an empty state message indicating no bills are available

### Requirement 7: Backend API Endpoint Untuk Tagihan Grouped By Siswa

**User Story:** As a frontend developer, I want an API endpoint that returns bills grouped by student, so that I can render the card-based view efficiently.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint that returns tagihan data grouped by siswa, where each item in the response represents one siswa with their associated tagihan array
2. WHEN the grouped endpoint is called, THE Backend_API SHALL return for each siswa: nama, NIS, jenjang, kelas name, and an array of tagihan where each tagihan contains kode_tagihan, jenis_tagihan (nama, jumlah, jatuh_tempo), tmp (amount paid), and status
3. THE Backend_API SHALL support the following query parameters on the grouped endpoint: search (partial match on nama or NIS, case-insensitive), jenjang (exact match, valid values: "KB", "TK", "MI"), and status (exact match, valid values: "Lunas", "Belum Lunas")
4. THE Backend_API SHALL paginate the grouped response by number of siswa (not by number of tagihan) with a default page size of 30 siswa per page, configurable via a per_page query parameter with a maximum value of 100
5. IF a non-admin user (user whose role is not "admin") calls the endpoint, THEN THE Backend_API SHALL return only tagihan belonging to the siswa whose NIS matches the authenticated user's username
6. THE Backend_API SHALL scope all queries to the authenticated user's branch_id, returning only siswa and tagihan that belong to the same branch
7. IF no siswa match the applied filters, THEN THE Backend_API SHALL return an empty data array with HTTP status 200 and pagination metadata showing zero total results

### Requirement 8: Backend API Endpoint Untuk Pembayaran Batch

**User Story:** As a frontend developer, I want a batch payment API endpoint, so that I can submit multiple bill payments in a single request.

#### Acceptance Criteria

1. THE Backend_API SHALL provide an endpoint that accepts a JSON request body containing an array of kode_tagihan (minimum 1, maximum 50 items), a metode field with value "Tunai" or "Non-Tunai", and a pembayar field (string, maximum 100 characters) to process full (lunas) payments in one request
2. WHEN a batch payment request is received, THE Backend_API SHALL validate that all provided kode_tagihan exist and belong to the authenticated user's branch_id
3. WHEN a batch payment request is received, THE Backend_API SHALL validate that none of the provided tagihan already have status "Lunas"
4. THE Backend_API SHALL process the batch payment within a single DB::transaction so that if any payment creation fails, all changes within that request are rolled back and no Pembayaran records are persisted
5. WHEN the batch payment is successful, THE Backend_API SHALL return an HTTP 200 response containing the list of created Pembayaran records, each including kode_pembayaran, kode_tagihan, tanggal, metode, jumlah, pembayar, and the associated tagihan details using PembayaranResource
6. IF any validation fails, THEN THE Backend_API SHALL return an HTTP 400 response with an error message indicating which kode_tagihan caused the failure, and SHALL NOT create any Pembayaran records
7. IF the kode_tagihan array is empty or the request body is missing required fields (kode_tagihan, metode, pembayar), THEN THE Backend_API SHALL return an HTTP 400 response with an error message indicating the missing or invalid fields

### Requirement 9: Error Handling Yang Konsisten

**User Story:** As a Admin_Operator, I want clear and consistent error messages, so that I can understand what went wrong and take corrective action.

#### Acceptance Criteria

1. WHEN a Backend_API request returns an HTTP 4xx or 5xx response with a JSON error body, THE Frontend_App SHALL display a danger notification containing the error message extracted from the API response's `message` field or the first value in the `errors` object
2. WHEN a network error occurs (connection timeout, DNS failure, or no response received within 30 seconds), THE Frontend_App SHALL display a danger notification indicating that the server could not be reached
3. IF a batch payment partially fails validation, THEN THE Frontend_App SHALL display a danger notification listing the kode_tagihan and nama_siswa of each tagihan that caused the validation error
4. WHEN a form validation error occurs on the client side, THE Frontend_App SHALL display inline error messages below each relevant form field indicating the specific validation rule that failed
5. THE Frontend_App SHALL prevent duplicate form submissions by disabling the submit button from the moment the API request is initiated until a response is received or the request times out
6. IF a Backend_API request returns an error response that does not contain a parseable JSON error body, THEN THE Frontend_App SHALL display a danger notification with a generic message indicating an unexpected error occurred and suggesting the user retry or contact support
7. THE Frontend_App SHALL display all error notifications using the Filament Notification component with danger severity, and notifications SHALL remain visible until manually dismissed by the user

### Requirement 10: Kompatibilitas Dengan Fitur Existing

**User Story:** As a Admin_Operator, I want the new card view to maintain existing functionality, so that I can still add and delete bills as before.

#### Acceptance Criteria

1. THE Tagihan_Card_View SHALL provide an action to add new tagihan by presenting a form with jenis_tagihan, jenjang, kelas, and kategori selection fields, all of which are required before submission
2. THE Siswa_Card SHALL provide an action to delete individual tagihan that requires a confirmation prompt before executing the deletion
3. IF the Admin_Operator attempts to delete a tagihan that has one or more associated pembayaran records, THEN THE Siswa_Card SHALL prevent the deletion and display a message indicating that the tagihan cannot be deleted because it has associated payments
4. IF the logged-in user does not have the required permission (create-tagihan for add, delete-tagihan for delete, view-tagihan for view), THEN THE Tagihan_Card_View SHALL hide the corresponding action so that it is not visible to the user
5. THE Backend_API SHALL continue to accept requests to the existing single-payment endpoints (POST /pembayaran/lunas/{kode_tagihan} and POST /pembayaran/bayar/{kode_tagihan}) with the same request parameters and response structure as before the card view implementation
6. WHEN the add tagihan action is submitted successfully, THE Tagihan_Card_View SHALL display a success notification and refresh the tagihan list to reflect the new entry
7. IF the add or delete tagihan action fails due to a backend error, THEN THE Tagihan_Card_View SHALL display an error notification indicating the operation was unsuccessful
