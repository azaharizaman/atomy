## ADDED Requirements

### Requirement: Ingest Unstructured Quote Files
The system SHALL accept PDF and Excel files as vendor quotes, validate their format, and store them securely while triggering an asynchronous extraction process.

#### Scenario: Successful PDF Upload
- **GIVEN** a valid vendor quote PDF file "quote_123.pdf" (5MB)
- **WHEN** the user uploads the file via the API
- **THEN** the file is stored in `Nexus\Storage`
- **AND** a `DocumentUploaded` event is dispatched with `type: vendor_quote`
- **AND** the system returns a 202 Accepted status with a `job_id`

#### Scenario: Invalid File Format
- **GIVEN** a "quote.exe" executable file
- **WHEN** the user attempts to upload the file
- **THEN** the system rejects the upload with a 400 Bad Request
- **AND** returns an error message "Invalid file format. Allowed: PDF, XLS, XLSX"

### Requirement: Initial Quote State
Upon ingestion, a `VendorQuote` entity SHALL be created in a `processing` state, linked to the uploaded document.

#### Scenario: Quote Entity Creation
- **WHEN** the `DocumentUploaded` event is processed
- **THEN** a new `VendorQuote` record is created
- **AND** its status is set to `processing`
- **AND** it is linked to the `document_id`
