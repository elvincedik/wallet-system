# Wallet & Transaction System with Paystack Integration

A simple Laravel API for wallet management and Paystack payment integration.

## Setup Instructions

### 1. Update Environment Variables

Replace the Paystack placeholders in `.env`:

```env
PAYSTACK_PUBLIC_KEY=pk_test_your_test_key_here
PAYSTACK_SECRET_KEY=sk_test_your_test_key_here
PAYSTACK_WEBHOOK_SECRET=sk_test_your_test_key_here
PAYSTACK_CALLBACK_URL=http://localhost:8000/api/paystack/callback
# Optional: where backend callback redirects users after payment
PAYSTACK_FRONTEND_CALLBACK_URL=http://localhost:5173/payment/callback
```

Get your test keys from: https://dashboard.paystack.com/settings/developer

### 2. Install Sanctum (if not already installed)

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 3. Create a User Token for Testing

```bash
php artisan tinker
```

Then in Tinker:
```php
$user = User::first();  // or create a new user
$token = $user->createToken('test-token')->plainTextToken;
echo $token;
```

## API Endpoints

All protected endpoints require the header: `Authorization: Bearer YOUR_TOKEN`

### Get Wallet Balance
```
GET /api/wallet
Headers:
  Authorization: Bearer {token}

Response:
{
  "id": 1,
  "balance": 0,
  "currency": "NGN"
}
```

### Get Wallet Transactions
```
GET /api/wallet/transactions
Headers:
  Authorization: Bearer {token}

Response:
{
  "transactions": [
    {
      "id": 1,
      "type": "topup",
      "amount": 5000,
      "status": "completed",
      "reference": "TXN_uuid",
      "created_at": "2026-02-18T00:00:00Z"
    }
  ]
}
```

### Initiate Top-up Payment
```
POST /api/topup/initiate
Headers:
  Authorization: Bearer {token}
  Content-Type: application/json

Body:
{
  "amount": 5000
}

Response:
{
  "status": true,
  "message": "Payment initialized",
  "data": {
    "authorization_url": "https://checkout.paystack.com/...",
    "access_code": "access_code_here",
    "reference": "TXN_uuid",
    "amount": 5000
  }
}
```

### Verify Payment
```
POST /api/topup/verify
Headers:
  Authorization: Bearer {token}
  Content-Type: application/json

Body:
{
  "reference": "TXN_uuid"
}

Response:
{
  "status": true,
  "message": "Payment verified and wallet credited",
  "data": {
    "amount": 5000,
    "wallet_balance": 5000,
    "reference": "TXN_uuid"
  }
}
```

### Paystack Callback (Public)
```
GET /api/paystack/callback?reference=TXN_uuid
```

### Paystack Webhook (Public)
```
POST /api/paystack/webhook
Headers:
  x-paystack-signature: {hmac_sha512_signature}
```

## Testing with Postman

1. **Create User & Get Token**
   - Register or login to get an auth token
   - For API, use Sanctum tokens

2. **Test Wallet Endpoint**
   - GET `http://localhost:8000/api/wallet`
   - Add header: `Authorization: Bearer your_token`

3. **Initiate Payment**
   - POST `http://localhost:8000/api/topup/initiate`
   - Body: `{ "amount": 5000 }`
   - You'll get a Paystack authorization URL

4. **Complete Payment**
   - Visit the authorization URL provided
   - Use Paystack test card: `4084084084084081`
   - Any future date and any 3-digit CVV

5. **Verify Payment**
   - POST `/api/topup/verify`
   - Body: `{ "reference": "the_transaction_reference" }`

## Database Schema

### Wallets Table
- `id` - Primary key
- `user_id` - Foreign key to users
- `balance` - Decimal(15, 2)
- `timestamps` - created_at, updated_at

### Transactions Table
- `id` - Primary key
- `wallet_id` - Foreign key to wallets
- `type` - topup or debit
- `amount` - Decimal(15, 2)
- `status` - pending, completed, failed
- `reference` - Unique transaction reference
- `timestamps` - created_at, updated_at

## Key Features

✓ Simple wallet system with balance tracking
✓ Transaction history
✓ Paystack integration for payments
✓ Pending transaction handling
✓ Unique payment references
✓ API authentication via Sanctum tokens

## Optional: Set Paystack Test Keys (Replace placeholder keys)

1. Go to https://dashboard.paystack.com/settings/developer
2. Copy your **Public Key** (starts with `pk_test_`)
3. Copy your **Secret Key** (starts with `sk_test_`)
4. Update `.env` file with your keys
5. You're ready to test!
