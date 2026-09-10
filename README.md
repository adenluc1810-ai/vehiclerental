# Online Vehicle Rental Management System (PHP + MySQL)

A full-featured vehicle rental platform with three roles: **Customer**, **Vehicle Owner**, and **Admin**.

## Modules Included
1. User Registration/Login (Customer, Owner, Admin) — `register.php`, `login.php`
2. Vehicle Listing (category, model, price, images, availability) — `vehicles/add.php`, `edit.php`, `manage.php`
3. Search & Filter (location, type, price range, dates) — `vehicles/search.php`
4. Booking/Reservation (dates, pickup/drop location) — `booking/create.php`
5. Availability Calendar (real-time status, 30-day view) — `vehicles/view.php`
6. Customer Management (profile, history, KYC upload) — `customer/profile.php`, `customer/dashboard.php`
7. Payment Gateway (simulated) — `payment/checkout.php`
8. Billing & Invoice (rental charge, late fee, damages) — `payment/invoice.php`
9. Vehicle Return & Inspection — `booking/return.php`
10. Rating & Review — `review/add.php`
11. Admin Dashboard (manage vehicles/bookings/users, revenue reports) — `admin/`
12. Notifications (booking confirmation, status updates, reminders) — `notifications/list.php`

## Requirements
- PHP 7.4+ (PHP 8 recommended)
- MySQL 5.7+ / MariaDB
- Apache/Nginx (or PHP's built-in server for local testing)
- PDO MySQL extension enabled

## Setup Instructions

1. **Copy files** to your web server root, e.g. `htdocs/vrms` (XAMPP/WAMP) or your server's document root.

2. **Create the database**: import `sql/schema.sql` into MySQL:
   ```
   mysql -u root -p < sql/schema.sql
   ```
   Or use phpMyAdmin: create database `vrms`, then import `sql/schema.sql`.

3. **Configure database credentials** in `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'vrms');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
   If your app is not at the domain root, also update `BASE_URL` (e.g. `/vrms/`).

4. **Create the first admin account**: visit `setup_admin.php` in your browser once
   (e.g. `http://localhost/vrms/setup_admin.php`), fill the form, then **delete
   `setup_admin.php`** afterward for security.

5. **Set folder permissions** so PHP can write uploaded files:
   ```
   chmod -R 755 uploads/
   ```

6. **Run the app**: visit `http://localhost/vrms/` (or use `php -S localhost:8000` from the
   project folder for quick local testing, then browse to `http://localhost:8000/`).

## Roles & Flow
- **Customer**: registers → searches vehicles → books → pays (simulated gateway) →
  booking is confirmed → owner starts rental → owner processes return/inspection →
  invoice auto-generated (rental + late fee + damage + tax) → customer can leave a review.
- **Owner**: registers as "Vehicle Owner" → lists vehicles with images/pricing →
  manages bookings (confirm / start / return+inspect) → views vehicle-level dashboard stats.
- **Admin**: manages all users (block/unblock), all vehicles, all bookings, verifies
  KYC documents, and views platform-wide revenue reports.

## Notes
- The payment gateway in `payment/checkout.php` is **simulated** for demo purposes
  (no real card processing) — swap in Stripe/PayPal/Razorpay SDK calls there for production.
- Late fee = 20% of daily rate × number of late days (configurable in `booking/return.php`).
- Tax is a flat 5% applied at final invoice generation (configurable in `booking/return.php`).
- Uploaded files are stored in `uploads/vehicles`, `uploads/kyc`. A `.htaccess` blocks
  script execution in that folder for security.
- All queries use PDO prepared statements to prevent SQL injection.
- Passwords are hashed with PHP's `password_hash()` (bcrypt).

## Folder Structure
```
vrms/
├── admin/              # Admin dashboard, users, vehicles, bookings, KYC verification
├── owner/              # Owner dashboard, booking management
├── customer/           # Customer dashboard, profile & KYC upload
├── vehicles/           # Listing, search/filter, view, manage (CRUD)
├── booking/            # Create, my bookings, cancel, return & inspection
├── payment/            # Checkout (gateway), invoice/billing
├── review/             # Rating & review
├── notifications/      # Notification center
├── auth/               # Logout
├── includes/           # header, footer, shared functions
├── config/             # db.php (database config)
├── assets/css/         # styling
├── uploads/            # vehicle images, KYC docs (writable)
├── sql/schema.sql      # database schema
├── setup_admin.php     # one-time admin creation (delete after use)
├── index.php           # homepage
├── register.php / login.php
```
