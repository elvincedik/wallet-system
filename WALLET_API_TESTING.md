# Wallet API - Quick Test Guide

## Using cURL to Test the API

### Step 1: Create a Test User Token

```bash
php artisan tinker
```

In tinker:
```php
$user = \App\Models\User::first(); // or create one
$token = $user->createToken('api-test')->plainTextToken;
echo $token; // Copy this token
exit
```

### Step 2: Test Wallet Balance (replace TOKEN with your actual token)

```bash
curl -X GET http://localhost:8000/api/wallet \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

### Step 3: Get Wallet Transactions

```bash
curl -X GET http://localhost:8000/api/wallet/transactions \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

### Step 4: Initiate Top-up

```bash
curl -X POST http://localhost:8000/api/topup/initiate \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 5000}'
```

Response will include:
- `authorization_url` - Visit this URL to complete payment
- `reference` - Save this for verification
- `amount` - The amount you're topping up

### Step 5: Go to Paystack and Complete Payment

1. Click the `authorization_url` from the response
2. Use test card: **4084084084084081**
3. Use any future expiry date (MM/YY)
4. Use any 3-digit CVV (e.g., 123)
5. Use any name
6. Accept and complete

### Step 6: Verify Payment

```bash
curl -X POST http://localhost:8000/api/topup/verify \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"reference": "TXN_xxxxx"}'
```

Replace `TXN_xxxxx` with the reference from Step 4.

### Step 7: Check Updated Balance

```bash
curl -X GET http://localhost:8000/api/wallet \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

Your balance should now show the topped-up amount!

---

## Using Postman

1. **Create a new Postman collection**
2. **Set up Authorization**
   - Get your bearer token from Step 1 above
   - In Postman, go to Authorization tab
   - Select "Bearer Token"
   - Paste your token

3. **Get Wallet**
   - Method: GET
   - URL: `http://localhost:8000/api/wallet`

4. **Initiate Payment**
   - Method: POST
   - URL: `http://localhost:8000/api/topup/initiate`
   - Body (JSON): `{"amount": 5000}`

5. **Verify Payment**
   - Method: POST
   - URL: `http://localhost:8000/api/topup/verify`
   - Body (JSON): `{"reference": "TXN_xxxxx"}`

---

## Important Notes

- All amounts are in **Naira (NGN)**
- Minimum top-up: 100 NGN
- The system uses test Paystack keys by default
- Replace placeholder keys in `.env` with your actual Paystack test keys
- Transactions are stored with statuses: `pending`, `completed`, `failed`
- Each wallet is automatically created when first accessed
- Multiple transactions are tracked under each wallet
