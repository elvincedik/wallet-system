## Setup Instructions

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env`
4. Run `php artisan key:generate`
5. Configure your database credentials
6. Add your Paystack test keys:
   - PAYSTACK_PUBLIC_KEY
   - PAYSTACK_SECRET_KEY
7. Run `php artisan migrate`
8. Run `php artisan serve`