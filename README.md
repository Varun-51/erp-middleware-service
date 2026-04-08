# Odoo ERP Integration Service

A lightweight PHP middleware service that integrates with Odoo via JSON-RPC, featuring dynamic field mapping for sales order creation and customer retrieval.

## Features

- **Odoo Authentication**: Secure JSON-RPC authentication with API keys
- **Customer Retrieval**: Fetch existing customers from `res.partner`
- **Sales Order Creation**: Create orders via `sale.order` with dynamic payload mapping
- **Dynamic Field Mapping**: External JSON schemas map to Odoo fields via configuration (no code changes needed)
- **API Security**: API key authentication via `X-API-KEY` header

## Architecture

### Mapper Pattern
The service uses a configuration-driven mapping system (`config/mapping.php`) to translate external field names to Odoo field names:

```php
// External field => Odoo field
'customer_id' => 'partner_id',
'external_ref' => 'client_order_ref',
```

If your source system changes field names, simply update the mapping configuration—no core code changes required.

## Prerequisites

- PHP 8.1+
- Composer
- MySQL (optional, for Laravel features)
- Odoo 15+ instance with API access

## Installation

1. **Clone the repository:**
   ```bash
   git clone <your-repo-url>
   cd windsurf-project
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

4. **Configure environment variables** (see Configuration section below)

5. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

6. **Start the server:**
   ```bash
   php artisan serve
   ```

   The API will be available at `http://localhost:8000`

## Configuration

### Environment Variables (.env)

```env
APP_NAME="Odoo Integration"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=false

# API Security
API_KEY=your-secure-api-key-here

# Odoo Credentials (from your Odoo trial instance)
ODOO_URL=https://your-instance.odoo.com
ODOO_DB=your-instance-name
ODOO_USERNAME=your-email@example.com
ODOO_API_KEY=your-odoo-api-key
```

### Getting Odoo Credentials

1. Go to [odoo.com](https://www.odoo.com) and start a free trial
2. Select **Sales** and **Inventory** apps
3. Your database name is the prefix of your URL (e.g., `mycompany` in `mycompany.odoo.com`)
4. Go to **User Profile → Account Security → API Keys**
5. Generate a new API key

### Field Mapping Configuration

Edit `config/mapping.php` to customize field mappings:

```php
'sales_order' => [
    'external_ref' => 'client_order_ref',  // Your field => Odoo field
    'customer_id' => 'partner_id',
    'items' => [                           // Nested line items
        'product_id' => 'product_id',
        'quantity' => 'product_uom_qty',
        'price' => 'price_unit',
    ],
],
```

## API Documentation

### Authentication

All protected endpoints require the `X-API-KEY` header:

```
X-API-KEY: your-secure-api-key-here
```

### Endpoints

#### 1. Health Check
```bash
GET /api/health
```

**Response:**
```json
{
  "status": "ok",
  "timestamp": "2026-04-08T10:30:00.000000Z"
}
```

#### 2. List Customers
```bash
GET /api/customers?limit=50
```

**Headers:**
```
X-API-KEY: your-api-key
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "customers": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+1234567890"
      }
    ],
    "count": 1
  }
}
```

#### 3. List Products
```bash
GET /api/products?limit=50
```

**Headers:**
```
X-API-KEY: your-api-key
```

#### 4. Create Sales Order
```bash
POST /api/orders
```

**Headers:**
```
Content-Type: application/json
X-API-KEY: your-api-key
```

**Request Body:**
```json
{
  "external_ref": "ORDER-001",
  "customer_id": 1,
  "notes": "Customer order notes",
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "price": 100.00,
      "description": "Product description"
    }
  ],
  "confirm": false
}
```

**Parameters:**
- `confirm` (optional, boolean): 
  - `false` or omitted: Creates a **draft quotation** (state = draft)
  - `true`: Creates a **confirmed sales order** (state = sale)

**Response (201 Created):**
```json
{
  "status": "success",
  "data": {
    "order_id": 123,
    "state": "draft"
  }
}
```

**Example: Create and Confirm Order**
```json
{
  "external_ref": "ORDER-002",
  "customer_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 1,
      "price": 50.00
    }
  ],
  "confirm": true
}
```

Response:
```json
{
  "status": "success",
  "data": {
    "order_id": 124,
    "state": "sale"
  }
}
```

#### 5. Get Sales Order
```bash
GET /api/orders/{id}
```

**Headers:**
```
X-API-KEY: your-api-key
```

## Testing with cURL

### Test Health Endpoint
```bash
curl http://localhost:8000/api/health
```

### Get Customers
```bash
curl -H "X-API-KEY: your-api-key" \
  http://localhost:8000/api/customers
```

### Create Sales Order
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: your-api-key" \
  -d '{
    "external_ref": "TEST-001",
    "customer_id": 1,
    "items": [
      {
        "product_id": 1,
        "quantity": 1,
        "price": 50.00
      }
    ]
  }' \
  http://localhost:8000/api/orders
```

## Postman Collection

A Postman collection is included at `postman/Odoo_Integration.postman_collection.json`

### Import Steps:
1. Open Postman
2. Click **Import** → **File** → Select the JSON file
3. Create an environment with variables:
   - `base_url`: `http://localhost:8000` (or your hosted URL)
   - `api_key`: Your API key

## Deployment

### Option 1: Heroku (Recommended)

1. **Create Heroku app:**
   ```bash
   heroku create your-app-name
   ```

2. **Set environment variables:**
   ```bash
   heroku config:set APP_KEY=$(php -r "echo base64_encode(random_bytes(32));")
   heroku config:set API_KEY=your-secure-api-key
   heroku config:set ODOO_URL=https://your-instance.odoo.com
   heroku config:set ODOO_DB=your-db-name
   heroku config:set ODOO_USERNAME=your-email
   heroku config:set ODOO_API_KEY=your-odoo-api-key
   ```

3. **Deploy:**
   ```bash
   git push heroku main
   ```

### Option 2: Railway

1. Push code to GitHub
2. Connect Railway to your repository
3. Add environment variables in Railway dashboard
4. Deploy automatically

### Option 3: Render

1. Create a new Web Service
2. Connect your GitHub repository
3. Set build command: `composer install`
4. Set start command: `php artisan serve --host 0.0.0.0 --port 10000`
5. Add environment variables

## Taking Screenshots for Proof of Work

### 1. Local Testing Screenshot

**Terminal showing successful order creation:**
```bash
# Run this and take screenshot of response
curl -X POST -H "Content-Type: application/json" \
  -H "X-API-KEY: your-key" \
  -d '{"external_ref":"PROOF-001","customer_id":1,"items":[{"product_id":1,"quantity":1,"price":100}]}' \
  http://localhost:8000/api/orders
```

Screenshot should show:
- The curl command
- JSON response with `order_id`
- HTTP 201 status

### 2. Odoo Dashboard Screenshot

1. Log into your Odoo instance
2. Go to **Sales → Orders**
3. Find your created order (look for `external_ref` in Reference field)
4. Open the order to show details:
   - Order reference
   - Customer name
   - Product lines with quantities and prices
   - Total amount
5. Take screenshot of the full order form

### 3. Postman Testing Screenshot

1. Open Postman
2. Run the **Create Sales Order** request
3. Capture screenshot showing:
   - Request URL and headers
   - Request body JSON
   - Response body with success status and order_id
   - Status: 201 Created

### Screenshot Checklist
- [ ] Terminal/API client showing successful API call
- [ ] JSON response with `order_id`
- [ ] Odoo dashboard showing the created order
- [ ] Order details visible (customer, products, totals)

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── OrderController.php    # API endpoint handlers
│   └── Middleware/
│       └── ApiKeyMiddleware.php   # API key validation
├── Services/
│   ├── OdooService.php          # Odoo JSON-RPC client
│   └── ERPMapper.php            # Field mapping logic
config/
├── mapping.php                  # Field mapping configuration
└── services.php                 # Odoo connection config
routes/
└── api.php                      # API route definitions
```

## License

This project was created for the Solutech Limited ERP Support Engineer Technical Assessment.
