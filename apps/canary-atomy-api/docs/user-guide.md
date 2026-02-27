# Canary atomy API - User Guide

This guide provides practical information for developers and users integrating with the Canary atomy API.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Authentication](#authentication)
3. [API Endpoints](#api-endpoints)
4. [Multi-Tenancy](#multi-tenancy)
5. [Module Management](#module-management)
6. [Working with Users](#working-with-users)
7. [Error Handling](#error-handling)
8. [Code Examples](#code-examples)

---

## Getting Started

### Making Your First API Call

The base URL for all API requests is:

```
http://localhost:8000/api
```

Let's start by listing all available modules:

```bash
curl http://localhost:8000/api/modules
```

Expected response:

```json
{
  "@context": "/api/contexts/Module",
  "@id": "/api/modules",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "id": "AccountingOperations",
      "moduleId": "AccountingOperations",
      "name": "AccountingOperations",
      "description": "Period close, consolidation, statements, ratios",
      "version": "1.0.0",
      "isInstalled": false,
      "installedAt": null,
      "installedBy": null
    },
    ...
  ]
}
```

---

## Authentication

The API uses HTTP Basic Authentication. Include your credentials with every request.

### Basic Authentication Header

Generate a Base64 encoded string of your credentials:

```bash
# Format: username:password
echo -n "admin:password" | base64
# Output: YWRtaW46cGFzc3dvcmQ=
```

Include the header in your request:

```bash
curl -H "Authorization: Basic YWRtaW46cGFzc3dvcmQ=" \
  http://localhost:8000/api/modules
```

### Using curl with -u flag

The simplest way to authenticate:

```bash
curl -u admin:password http://localhost:8000/api/modules
```

### Default Users

| Username | Password | Roles |
|----------|----------|-------|
| admin | (check security.yaml) | ROLE_USER, ROLE_ADMIN |
| user | (check security.yaml) | ROLE_USER |

> **Security Note**: In production, replace HTTP Basic Auth with JWT or OAuth2. Update `config/packages/security.yaml` to configure your authentication method.

---

## API Endpoints

### Modules API

#### List All Modules

```http
GET /api/modules
```

**Response:**

```json
{
  "@context": "/api/contexts/Module",
  "@id": "/api/modules",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "id": "AccountingOperations",
      "moduleId": "AccountingOperations",
      "name": "AccountingOperations",
      "description": "Period close, consolidation, statements, ratios",
      "version": "1.0.0",
      "isInstalled": false
    }
  ],
  "hydra:totalItems": 21
}
```

#### Get Single Module

```http
GET /api/modules/{moduleId}
```

**Example:**

```bash
curl http://localhost:8000/api/modules/AccountingOperations
```

**Response:**

```json
{
  "@context": "/api/contexts/Module",
  "@id": "/api/modules/AccountingOperations",
  "@type": "Module",
  "id": "AccountingOperations",
  "moduleId": "AccountingOperations",
  "name": "AccountingOperations",
  "description": "Period close, consolidation, statements, ratios",
  "version": "1.0.0",
  "isInstalled": false
}
```

#### Install Module

```http
POST /api/modules/{moduleId}/install
```

**Requires:** `ROLE_ADMIN`

**Example:**

```bash
curl -X POST http://localhost:8000/api/modules/AccountingOperations/install \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: tenant-123" \
  -u admin:password
```

**Response (201 Created):**

```json
{
  "@context": "/api/contexts/InstalledModule",
  "@id": "/api/installed_modules/550e8400-e29b-41d4-a716-446655440000",
  "@type": "InstalledModule",
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "moduleId": "AccountingOperations",
  "installedAt": "2024-01-15T10:30:00+00:00",
  "installedBy": "admin",
  "metadata": {
    "name": "AccountingOperations",
    "description": "Period close, consolidation, statements, ratios",
    "version": "1.0.0",
    "installed_version": "1.0.0"
  }
}
```

#### Uninstall Module

```http
DELETE /api/modules/{installedModuleId}
```

**Requires:** `ROLE_ADMIN`

**Example:**

```bash
curl -X DELETE http://localhost:8000/api/modules/550e8400-e29b-41d4-a716-446655440000 \
  -H "X-Tenant-ID: tenant-123" \
  -u admin:password
```

### Users API

#### List Users

```http
GET /api/users
```

**Note:** Returns sample data filtered by the current tenant context.

**Example:**

```bash
curl -H "X-Tenant-ID: tenant-123" \
  http://localhost:8000/api/users
```

**Response:**

```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "id": "user-001",
      "email": "admin@example.com",
      "name": "Admin User",
      "status": "active",
      "roles": ["ROLE_ADMIN", "ROLE_USER"],
      "createdAt": "2024-01-01T00:00:00+00:00"
    },
    {
      "id": "user-002",
      "email": "john.doe@example.com",
      "name": "John Doe",
      "status": "active",
      "roles": ["ROLE_USER"],
      "createdAt": "2024-01-15T00:00:00+00:00"
    }
  ]
}
```

---

## Multi-Tenancy

The API supports multi-tenant operations, ensuring data isolation between tenants.

### Setting the Tenant

Include the `X-Tenant-ID` header in your requests:

```bash
curl -H "X-Tenant-ID: tenant-456" \
  http://localhost:8000/api/modules
```

### Tenant Context Resolution

The system resolves tenant context in this priority order:

1. **Explicitly set** via code (`TenantContext::setTenant()`)
2. **X-Tenant-ID** HTTP header
3. **tenant_id** query parameter
4. **Route attribute** (tenant_id)
5. **Authenticated user** (from JWT token)

### Examples

#### Header-based Tenant

```bash
curl -H "X-Tenant-ID: my-company" \
  -u admin:password \
  http://localhost:8000/api/modules
```

#### Query Parameter Tenant

```bash
curl "?tenant_id=my-company" \
  -u admin:password \
  http://localhost:8000/api/modules
```

### Tenant Context in Code

Access the current tenant in your services:

```php
use App\Service\TenantContext;

class MyService
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    public function doSomething(): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context required');
        }
        
        // Use tenant ID...
    }
}
```

---

## Module Management

### Understanding Modules

Modules in the Canary atomy API represent **orchestrators** from the Nexus system. Each orchestrator provides a specific business function:

| Module | Purpose |
|--------|---------|
| AccountingOperations | Financial accounting, period close, consolidation |
| SalesOperations | Quote-to-cash, opportunity management |
| ProcurementOperations | Purchase-to-pay, supplier management |
| HumanResourceOperations | Employee management, payroll |
| InventoryOperations | Stock management |
| And more... | See full list in [README.md](../apps/canary-atomy-api/README.md) |

### Installing Modules

#### Step 1: List Available Modules

```bash
curl http://localhost:8000/api/modules
```

#### Step 2: Check Module Details

```bash
curl http://localhost:8000/api/modules/SalesOperations
```

#### Step 3: Install the Module

```bash
curl -X POST http://localhost:8000/api/modules/SalesOperations/install \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: my-company" \
  -u admin:password
```

### Checking Installation Status

After installation, the module shows `isInstalled: true`:

```bash
curl http://localhost:8000/api/modules/SalesOperations
```

```json
{
  "id": "SalesOperations",
  "moduleId": "SalesOperations",
  "name": "SalesOperations",
  "description": "Quote-to-cash, opportunity management",
  "version": "1.0.0",
  "isInstalled": true,
  "installedAt": "2024-01-15T14:30:00+00:00",
  "installedBy": "admin"
}
```

### Uninstalling Modules

```bash
# First, get the installed module ID from the response
curl -X DELETE http://localhost:8000/api/modules/{installed-module-id} \
  -H "X-Tenant-ID: my-company" \
  -u admin:password
```

---

## Working with Users

### User Resource Fields

| Field | Type | Description |
|-------|------|-------------|
| id | string | Unique user identifier |
| email | string | User email address |
| name | string | Display name |
| status | string | Account status (active, inactive) |
| roles | array | User roles |
| createdAt | string | Account creation timestamp |

### Example: Get All Users

```bash
curl -H "X-Tenant-ID: tenant-123" \
  -u admin:password \
  http://localhost:8000/api/users
```

### Filtering Users by Tenant

All user queries are automatically filtered by the current tenant context. Make sure to include the `X-Tenant-ID` header:

```bash
# Users for tenant A
curl -H "X-Tenant-ID: tenant-a" \
  http://localhost:8000/api/users

# Users for tenant B
curl -H "X-Tenant-ID: tenant-b" \
  http://localhost:8000/api/users
```

---

## Error Handling

### Common HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created (new resource) |
| 400 | Bad Request |
| 401 | Unauthorized (invalid credentials) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 500 | Internal Server Error |

### Error Response Format

```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "Module \"InvalidModule\" not found"
}
```

### Authentication Errors

**401 Unauthorized:**

```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "Invalid credentials."
}
```

### Authorization Errors

**403 Forbidden:**

```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "Access denied."
}
```

### Handling Errors in Code

```php
$response = $client->request('GET', '/api/modules');

if ($response->getStatusCode() === 200) {
    $data = $response->toArray();
    // Process data
} elseif ($response->getStatusCode() === 401) {
    // Handle authentication error
} elseif ($response->getStatusCode() === 403) {
    // Handle authorization error
}
```

---

## Code Examples

### PHP (Using Symfony HTTPClient)

```php
use Symfony\Component\HttpClient\NativeHttpClient;

$client = new NativeHttpClient();

// List modules
$response = $client->request('GET', 'http://localhost:8000/api/modules', [
    'auth_basic' => ['admin', 'password'],
    'headers' => ['X-Tenant-ID' => 'tenant-123']
]);

$modules = $response->toArray();
foreach ($modules['hydra:member'] as $module) {
    echo $module['name'] . ' - ' . $module['description'] . "\n";
}

// Install a module
$response = $client->request('POST', 'http://localhost:8000/api/modules/AccountingOperations/install', [
    'auth_basic' => ['admin', 'password'],
    'headers' => [
        'X-Tenant-ID' => 'tenant-123',
        'Content-Type' => 'application/json'
    ]
]);

if ($response->getStatusCode() === 201) {
    echo "Module installed successfully!";
}
```

### JavaScript (Fetch API)

```javascript
const API_BASE = 'http://localhost:8000/api';
const AUTH = btoa('admin:password');
const TENANT_ID = 'tenant-123';

const headers = {
    'Authorization': `Basic ${AUTH}`,
    'X-Tenant-ID': TENANT_ID,
    'Content-Type': 'application/json'
};

// List modules
async function listModules() {
    const response = await fetch(`${API_BASE}/modules`, { headers });
    const data = await response.json();
    return data['hydra:member'];
}

// Install module
async function installModule(moduleId) {
    const response = await fetch(`${API_BASE}/modules/${moduleId}/install`, {
        method: 'POST',
        headers
    });
    
    if (!response.ok) {
        throw new Error(`Failed to install: ${response.statusText}`);
    }
    
    return response.json();
}

// Usage
listModules().then(modules => {
    modules.forEach(m => console.log(`${m.name}: ${m.description}`));
});
```

### Python (Requests Library)

```python
import requests

API_BASE = 'http://localhost:8000/api'
AUTH = ('admin', 'password')
HEADERS = {'X-Tenant-ID': 'tenant-123'}

# List modules
def list_modules():
    response = requests.get(f'{API_BASE}/modules', auth=AUTH, headers=HEADERS)
    response.raise_for_status()
    return response.json()['hydra:member']

# Install module
def install_module(module_id):
    response = requests.post(
        f'{API_BASE}/modules/{module_id}/install',
        auth=AUTH,
        headers=HEADERS
    )
    response.raise_for_status()
    return response.json()

# Usage
modules = list_modules()
for m in modules:
    print(f"{m['name']}: {m['description']}")

install_module('AccountingOperations')
```

### cURL Examples

#### List all modules

```bash
curl -H "X-Tenant-ID: tenant-123" \
  http://localhost:8000/api/modules
```

#### Get specific module

```bash
curl -H "X-Tenant-ID: tenant-123" \
  http://localhost:8000/api/modules/AccountingOperations
```

#### Install module

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: tenant-123" \
  -u admin:password \
  http://localhost:8000/api/modules/SalesOperations/install
```

#### List users

```bash
curl -H "X-Tenant-ID: tenant-123" \
  -u admin:password \
  http://localhost:8000/api/users
```

#### Uninstall module

```bash
curl -X DELETE \
  -H "X-Tenant-ID: tenant-123" \
  -u admin:password \
  http://localhost:8000/api/modules/{installed-module-id}
```

---

## Next Steps

- Explore the [API Platform Documentation](https://api-platform.com/docs/)
- Review the [Module Registry Service](../../apps/canary-atomy-api/src/Service/ModuleRegistry.php)
- Understand [Tenant Context](../../apps/canary-atomy-api/src/Service/TenantContext.php)
- Configure production authentication in `config/packages/security.yaml`
