# Wallet & Payment API - Laravel Backend Assessment

A Laravel-based wallet and payment system with transaction integrity, double-entry bookkeeping, and race condition prevention.

## Features

- **Wallet Management**: Create, view, fund, withdraw, and delete wallets
- **Payment/Transfer System**: Transfer funds between wallets with atomic transactions
- **Transaction Ledger**: Complete transaction history with double-entry consistency
- **Security**: Token-based authentication middleware, balance validation, and concurrent write prevention
- **Transaction Integrity**: Database transactions with row locking to prevent race conditions
- **Enterprise Architecture**: Laravel Actions pattern for clean, maintainable code
- **Queue System**: Reserved for future virtual account creation with payment providers
- **Production Ready**: Comprehensive error handling, logging, and exception management
- **FinTech-Grade Security**: 
  - Rate limiting on all endpoints
  - Comprehensive audit logging system
  - Ledger integrity verification
  - Complete audit trail for compliance
- **Performance Optimized**: 
  - Bulk database operations (no loops)
  - Database aggregation queries
  - Eager loading relationships
  - Optimized seeders

## Technology Stack

- PHP 8.2+
- Laravel 12.x
- MySQL/PostgreSQL/SQLite
- Repository/Service Pattern
- Laravel Actions Pattern
- Queue System (Database/Redis)
- MVC Architecture

## Prerequisites

- PHP >= 8.2
- Composer
- MySQL/PostgreSQL (or SQLite for development)
- Node.js & NPM (for frontend assets if needed)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/sage-rsc/gopaddi-backend-assesment.git
   cd gopaddi-backend-assesment
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   
   Edit `.env` file and set your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=wallet_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   For SQLite (development):
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
   ```
   
   Note: Create the SQLite database file if it doesn't exist:
   ```bash
   touch database/database.sqlite
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

   This will create tables for:
   - users
   - wallets
   - transactions
   - transfers
   - jobs (for queue system)

6. **Seed the database**
   ```bash
   php artisan db:seed
   ```

   This will create:
   - 3 test users (user 1, 2, and 3)
   - Wallets for users 2 and 3 with balances calculated from transactions
   - User 1 has no wallet (for testing wallet creation)
   - Transactions automatically generated for users 2 and 3

7. **Start the development server**
   ```bash
   php artisan serve
   ```

   The API will be available at `http://localhost:8000`

## API Endpoints

All endpoints require authentication via a custom token middleware. Include the token in the request header:

```
token: VG@123
```

### Wallet Management APIs

#### 1. Create Wallet
- **Endpoint**: `POST /api/wallets`
- **Headers**: `token: VG@123`
- **Body**:
  ```json
  {
    "user_id": 1
  }
  ```
- **Response**: 201 Created
  ```json
  {
    "success": true,
    "message": "Wallet created successfully",
    "data": {
      "wallet_id": 1,
      "user_id": 1,
      "balance": 0.00,
      "created_at": "2025-12-12T10:00:00.000000Z"
    }
  }
  ```

#### 2. View Wallet Balance
- **Endpoint**: `GET /api/wallets/{id}`
- **Headers**: `token: VG@123`
- **Response**: 200 OK
  ```json
  {
    "success": true,
    "data": {
      "wallet_id": 1,
      "user_id": 1,
      "balance": 1500.00,
      "transaction_summary": {
        "credit_total": 2000.00,
        "debit_total": 500.00
      }
    }
  }
  ```

#### 3. Fund Wallet (Credit)
- **Endpoint**: `POST /api/wallets/{id}/fund`
- **Headers**: `token: VG@123`
- **Body**:
  ```json
  {
    "amount": 500.00,
    "description": "Wallet funding"
  }
  ```
- **Response**: 200 OK
  ```json
  {
    "success": true,
    "message": "Wallet funded successfully",
    "data": {
      "transaction_id": 1,
      "reference": "uuid-here",
      "amount": 500.00,
      "type": "credit",
      "status": "completed"
    }
  }
  ```

#### 4. Withdraw from Wallet (Debit)
- **Endpoint**: `POST /api/wallets/{id}/withdraw`
- **Headers**: `token: VG@123`
- **Body**:
  ```json
  {
    "amount": 100.00,
    "description": "Withdrawal"
  }
  ```
- **Response**: 200 OK
- **Validation**: Ensures balance >= withdrawal amount

#### 5. Delete Wallet
- **Endpoint**: `DELETE /api/wallets/{id}`
- **Headers**: `token: VG@123`
- **Response**: 200 OK
- **Validation**: Only allowed if wallet balance is zero

### Transfer/Payment APIs

#### 1. Initiate Transfer
- **Endpoint**: `POST /api/transfers`
- **Headers**: `token: VG@123`
- **Body**:
  ```json
  {
    "sender_wallet_id": 1,
    "receiver_wallet_id": 2,
    "amount": 200.00,
    "description": "Transfer payment"
  }
  ```
- **Response**: 201 Created
- **Validations**:
  - Sender != Receiver
  - Sufficient balance
  - Atomic transaction (both debit & credit must succeed)

#### 2. View Transfer Details
- **Endpoint**: `GET /api/transfers/{id}`
- **Headers**: `token: VG@123`
- **Response**: 200 OK
  ```json
  {
    "success": true,
    "data": {
      "transfer_id": 1,
      "reference": "uuid-here",
      "sender": {
        "wallet_id": 1,
        "user_id": 1,
        "user_name": "John Doe"
      },
      "receiver": {
        "wallet_id": 2,
        "user_id": 2,
        "user_name": "Jane Smith"
      },
      "amount": 200.00,
      "description": "Transfer payment",
      "status": "completed",
      "created_at": "2025-12-12T10:00:00.000000Z",
      "updated_at": "2025-12-12T10:00:00.000000Z"
    }
  }
  ```

#### 3. View All Transfers for Wallet
- **Endpoint**: `GET /api/wallets/{walletId}/transfers`
- **Headers**: `token: VG@123`
- **Response**: 200 OK
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "reference": "uuid-here",
        "type": "outgoing",
        "amount": 200.00,
        "sender": "John Doe",
        "receiver": "Jane Smith",
        "description": "Transfer payment",
        "status": "completed",
        "created_at": "2025-12-12T10:00:00.000000Z"
      }
    ]
  }
  ```

### Ledger & Audit APIs

#### 1. Verify Wallet Integrity
- **Endpoint**: `GET /api/ledger/wallets/{walletId}/verify`
- **Headers**: `token: VG@123`
- **Response**: 200 OK
  ```json
  {
    "success": true,
    "message": "Ledger integrity verified",
    "data": {
      "valid": true,
      "wallet_id": 1,
      "actual_balance": 1500.00,
      "calculated_balance": 1500.00,
      "difference": 0.00,
      "credit_total": 2000.00,
      "debit_total": 500.00
    }
  }
  ```

#### 2. Verify Transfer Integrity
- **Endpoint**: `GET /api/ledger/transfers/{transferId}/verify`
- **Headers**: `token: VG@123`
- **Response**: 200 OK
  ```json
  {
    "success": true,
    "message": "Transfer integrity verified",
    "data": {
      "valid": true,
      "transfer_id": 1,
      "transfer_reference": "uuid-here",
      "transfer_amount": 200.00,
      "debit_transaction": {
        "id": 1,
        "amount": 200.00,
        "reference": "uuid-here"
      },
      "credit_transaction": {
        "id": 2,
        "amount": 200.00,
        "reference": "uuid-here"
      },
      "errors": []
    }
  }
  ```

#### 3. Get Ledger Audit Trail
- **Endpoint**: `GET /api/ledger/wallets/{walletId}/audit-trail`
- **Headers**: `token: VG@123`
- **Response**: 200 OK
  ```json
  {
    "success": true,
    "message": "Ledger audit trail retrieved",
    "data": {
      "wallet": {
        "id": 1,
        "user_id": 1,
        "balance": 1500.00
      },
      "transactions": [
        {
          "id": 1,
          "type": "credit",
          "amount": 500.00,
          "reference": "uuid-here",
          "status": "completed",
          "created_at": "2025-12-12T10:00:00.000000Z"
        }
      ],
      "transfers": [
        {
          "id": 1,
          "reference": "uuid-here",
          "amount": 200.00,
          "direction": "outgoing",
          "status": "completed",
          "created_at": "2025-12-12T10:00:00.000000Z"
        }
      ],
      "integrity_check": {
        "valid": true,
        "wallet_id": 1,
        "actual_balance": 1500.00,
        "calculated_balance": 1500.00
      }
    }
  }
  ```

## Authentication

All API endpoints are protected by a custom token middleware. The token must be included in the request header:

```
token: VG@123
```

If the token is missing or invalid, the API will return a 401 Unauthorized response:

```json
{
  "success": false,
  "message": "Unauthorized. Invalid or missing token."
}
```

## Database Schema

### Users Table
- Standard Laravel users table

### Wallets Table
- `id` (primary key)
- `user_id` (foreign key, unique - one wallet per user)
- `balance` (decimal 15,2, default 0)
- `timestamps`

### Transactions Table
- `id` (primary key)
- `wallet_id` (foreign key)
- `type` (enum: credit, debit, transfer_in, transfer_out)
- `amount` (decimal 15,2)
- `reference` (UUID, unique)
- `description` (nullable text)
- `status` (enum: pending, completed, failed)
- `transfer_id` (nullable foreign key)
- `timestamps`

### Transfers Table
- `id` (primary key)
- `sender_wallet_id` (foreign key)
- `receiver_wallet_id` (foreign key)
- `amount` (decimal 15,2)
- `reference` (UUID, unique)
- `description` (nullable text)
- `status` (enum: pending, completed, failed)
- `timestamps`

### Audit Logs Table
- `id` (primary key)
- `event_type` (string) - transaction, transfer, wallet_operation, ledger
- `action` (string) - created, updated, deleted, funded, withdrawn, transferred, etc.
- `entity_type` (string) - Wallet, Transaction, Transfer
- `entity_id` (unsigned big integer, nullable)
- `reference` (string, nullable) - Transaction/Transfer reference
- `user_id` (unsigned big integer, nullable)
- `wallet_id` (unsigned big integer, nullable)
- `old_values` (JSON, nullable)
- `new_values` (JSON, nullable)
- `metadata` (JSON, nullable) - IP, user agent, request details
- `ip_address` (string, nullable)
- `user_agent` (string, nullable)
- `status` (enum: success, failed)
- `error_message` (text, nullable)
- `timestamps`

## Performance Optimizations

### Database Optimization
- **Bulk Operations**: All multi-record operations use bulk insert/update
- **Database Aggregation**: Transaction summaries use SQL aggregation instead of fetching all records
- **Eager Loading**: Relationships loaded with `with()` to prevent N+1 queries
- **Optimized Seeders**: Bulk inserts instead of loops
- **No Query Loops**: All operations use batch processing

### Examples
- **Transaction Summary**: Uses `SUM()` with `CASE` statements instead of fetching all transactions
- **Bulk Transaction Creation**: Transfers create both transactions in a single bulk insert
- **Seeder Optimization**: All test data created using bulk inserts

## Architecture

### Enterprise Patterns

#### Laravel Actions Pattern
Single-responsibility action classes for clean, testable code:
- `CreateWalletAction`: Handles wallet creation logic
- `FundWalletAction`: Handles wallet funding
- `WithdrawWalletAction`: Handles wallet withdrawals
- `DeleteWalletAction`: Handles wallet deletion
- `GetWalletDetailsAction`: Retrieves wallet information
- `InitiateTransferAction`: Handles transfer operations
- `GetTransferDetailsAction`: Retrieves transfer information
- `GetWalletTransfersAction`: Retrieves wallet transfer history

#### Repository Pattern
- `WalletRepository`: Handles wallet data operations
- `TransactionRepository`: Handles transaction data operations
- `TransferRepository`: Handles transfer data operations

#### Service Layer
- `WalletService`: Orchestrates wallet operations using Actions
- `TransferService`: Orchestrates transfer operations using Actions

#### Queue System
- `CreateWalletJob`: Reserved for future virtual account creation with payment providers
  - Placeholder for integration with external payment gateways
  - Automatic retry on failure (3 attempts)
  - Exponential backoff (60 seconds)

### Security Features

#### Rate Limiting
- **Global Rate Limit**: 60 requests per minute per IP
- **Endpoint-Specific Limits**:
  - Wallet Creation: 10 requests/minute
  - Fund/Withdraw: 30 requests/minute
  - Transfers: 20 requests/minute
  - Wallet Deletion: 5 requests/minute
- **Rate Limit Headers**: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset

#### Audit Logging
- **Complete Audit Trail**: All transactions, transfers, and wallet operations logged
- **Compliance Ready**: IP address, user agent, timestamps, and metadata captured
- **Bulk Logging**: Optimized bulk insert for high-volume operations
- **Sensitive Data Protection**: Headers sanitized (tokens, cookies redacted)

#### Ledger Integrity
- **Wallet Integrity Verification**: Validates balance against transaction history
- **Transfer Integrity Verification**: Ensures double-entry bookkeeping compliance
- **Automatic Integrity Checks**: Runs on critical operations
- **Audit Trail API**: Complete ledger history for compliance and debugging

#### Database Security
- **Row Locking**: Uses `lockForUpdate()` to prevent race conditions
- **Database Transactions**: All critical operations wrapped in DB transactions
- **Balance Validation**: Prevents negative balances
- **Amount Validation**: Validates decimal amounts (minimum 1.00, maximum 999999999999.99, max 2 decimal places)
- **SQL Injection Prevention**: Parameterized queries only

## Transaction Integrity

The system implements double-entry bookkeeping for transfers:

1. **Transfer Creation**: Creates a transfer record with status "pending"
2. **Debit Transaction**: Creates a "transfer_out" transaction for the sender
3. **Credit Transaction**: Creates a "transfer_in" transaction for the receiver
4. **Status Update**: Updates transfer status to "completed"

If any step fails, the entire transaction is rolled back, ensuring data consistency.

## Testing with Postman

### Setup

1. **Import the collection**: Import `postman_collection.json` into Postman
2. **Configure variables**: Click on the collection → **Variables** tab
3. **Default variables** (ready to use):
   - `base_url`: `http://localhost:8000`
   - `api_token`: `VG@123`
   - `user_id`: `1` (for wallet creation - user 1 has no wallet initially)
   - `wallet_id`: `2` (for viewing/funding existing wallets - refers to user 2's wallet)
   - `sender_wallet_id`: `1`
   - `receiver_wallet_id`: `2`
   - `transfer_id`: `1`

### Testing Workflow

All requests automatically use the collection variables. Update them in the **Variables** tab as needed.

1. **Create Wallet** (POST /api/wallets)
   - Uses `{{user_id}}` variable (default: 1)
   - Returns 201 Created with wallet object

2. **View Wallet Balance** (GET /api/wallets/{{wallet_id}})
   - Uses `{{wallet_id}}` variable (default: 1)
   - View seeded wallet from database

3. **Fund Wallet** (POST /api/wallets/{{wallet_id}}/fund)
   - Uses `{{wallet_id}}` variable

4. **Withdraw from Wallet** (POST /api/wallets/{{wallet_id}}/withdraw)
   - Uses `{{wallet_id}}` variable

5. **Initiate Transfer** (POST /api/transfers)
   - Uses `{{sender_wallet_id}}` and `{{receiver_wallet_id}}` variables

6. **View Transfer Details** (GET /api/transfers/{{transfer_id}})
   - Uses `{{transfer_id}}` variable

7. **View Wallet Transfers** (GET /api/wallets/{{wallet_id}}/transfers)
   - Uses `{{wallet_id}}` variable

8. **Verify Ledger Integrity** (GET /api/ledger/wallets/{{wallet_id}}/verify)
   - Uses `{{wallet_id}}` variable

**Note**: All requests automatically include the `token: VG@123` header via collection-level authentication. No need to add headers manually.

## Error Handling

The API returns consistent JSON error responses:

```json
{
  "success": false,
  "message": "Error message here"
}
```

Common HTTP status codes:
- `200`: Success
- `201`: Created
- `400`: Bad Request (business logic errors, insufficient balance, etc.)
- `401`: Unauthorized (invalid/missing token)
- `422`: Unprocessable Entity (validation errors)
- `404`: Not Found
- `429`: Too Many Requests (rate limit exceeded)
- `500`: Internal Server Error

## Validation Rules

- **Amount**: Required, numeric, minimum 1.00, maximum 999999999999.99, decimal format (max 2 decimal places)
- **User ID**: Required, must exist in users table, unique (one wallet per user)
- **Wallet ID**: Required, must exist in wallets table
- **Transfer**: Sender and receiver wallets must be different
- **Description**: Optional, string, maximum 500 characters

## Development

### Running Migrations
```bash
php artisan migrate
```

### Running Seeders
```bash
php artisan db:seed
```

### Queue Management (Optional - For Future Features)

The queue system is set up but not currently required for wallet operations. It's reserved for future virtual account creation with payment providers.

**Start Queue Worker (If Needed)**
```bash
php artisan queue:work
```

**Process Specific Queue**
```bash
php artisan queue:work --queue=default
```

**Monitor Queue Jobs**
```bash
php artisan queue:monitor default --max=1000
```

**Retry Failed Jobs**
```bash
php artisan queue:retry all
```

**Clear Failed Jobs**
```bash
php artisan queue:flush
```

### Clearing Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan queue:clear
```

### Logging
All actions and operations are logged. Check `storage/logs/laravel.log` for:
- Action execution logs
- Error traces
- Queue job status
- Transaction details

## Project Structure

```
app/
├── Actions/
│   ├── Action.php (Base Action Class)
│   ├── Wallet/
│   │   ├── CreateWalletAction.php
│   │   ├── FundWalletAction.php
│   │   ├── WithdrawWalletAction.php
│   │   ├── DeleteWalletAction.php
│   │   └── GetWalletDetailsAction.php
│   └── Transfer/
│       ├── InitiateTransferAction.php
│       ├── GetTransferDetailsAction.php
│       └── GetWalletTransfersAction.php
├── Exceptions/
│   ├── WalletException.php
│   └── TransferException.php
├── Http/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── WalletController.php
│   │   ├── TransferController.php
│   │   └── LedgerController.php
│   ├── Middleware/
│   │   ├── TokenMiddleware.php
│   │   └── RateLimitMiddleware.php
│   └── Requests/
│       ├── CreateWalletRequest.php
│       ├── FundWalletRequest.php
│       ├── WithdrawWalletRequest.php
│       └── InitiateTransferRequest.php
├── Jobs/
│   └── CreateWalletJob.php
├── Models/
│   ├── User.php
│   ├── Wallet.php
│   ├── Transaction.php
│   └── Transfer.php
├── Repositories/
│   ├── WalletRepository.php
│   ├── TransactionRepository.php
│   └── TransferRepository.php
└── Services/
    ├── WalletService.php
    └── TransferService.php

database/
├── migrations/
│   ├── create_wallets_table.php
│   ├── create_transactions_table.php
│   └── create_transfers_table.php
├── factories/
│   ├── WalletFactory.php
│   ├── TransactionFactory.php
│   └── TransferFactory.php
└── seeders/
    └── WalletSeeder.php

routes/
└── api.php
```

## Production Deployment

### Environment Configuration

1. **Set APP_ENV to production**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Configure Queue Connection**
   ```env
   QUEUE_CONNECTION=redis
   ```

3. **Set up Supervisor for Queue Workers**
   - Install Supervisor: `sudo apt-get install supervisor`
   - Create config file: `/etc/supervisor/conf.d/laravel-worker.conf`
   - Start supervisor: `sudo supervisorctl reread && sudo supervisorctl update`

4. **Optimize for Production**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   composer install --optimize-autoloader --no-dev
   ```

### Monitoring

- Monitor queue jobs: `php artisan queue:monitor`
- Check failed jobs: `php artisan queue:failed`
- View logs: `tail -f storage/logs/laravel.log`

### Error Handling

The application uses custom exceptions for better error handling:
- `WalletException`: Wallet-specific errors
- `TransferException`: Transfer-specific errors

All exceptions are logged with full context for debugging.

## License

This project is part of a backend developer assessment.

## Author

James Favour

Developed as part of the GoPaddi Backend Developer Assessment.
