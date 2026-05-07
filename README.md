# 🚀 FlashRide — Online Ride Booking Platform

## 📁 Project Structure
```
flashride/
├── index.php                  ← Landing Page
├── database.sql               ← Database schema + seed data
├── .htaccess                  ← Security & URL config
├── config/
│   └── database.php           ← DB config + Database class
├── includes/
│   ├── functions.php          ← All helper functions
│   ├── header.php             ← HTML head + navbar
│   └── footer.php             ← JS includes + closing tags
├── pages/
│   ├── user_login.php         ← Rider login
│   ├── user_register.php      ← Rider registration
│   ├── logout.php             ← Rider logout
│   ├── dashboard.php          ← Rider dashboard
│   ├── book_ride.php          ← Live booking with map
│   ├── track_ride.php         ← Real-time ride tracking
│   ├── ride_history.php       ← Ride history + filter
│   ├── wallet.php             ← FlashWallet management
│   ├── profile.php            ← User profile settings
│   ├── driver_login.php       ← Driver login
│   ├── driver_register.php    ← Driver registration
│   ├── driver_dashboard.php   ← Driver panel (accept/start/complete)
│   └── driver_logout.php      ← Driver logout
├── api/
│   ├── book_ride.php          ← POST: Book new ride
│   ├── ride_status.php        ← GET: Poll ride status
│   ├── apply_promo.php        ← POST: Validate promo code
│   ├── cancel_ride.php        ← POST/GET: Cancel ride
│   ├── driver_action.php      ← POST: Accept/arrive/start/complete
│   ├── update_location.php    ← POST: Update driver GPS
│   ├── rate_ride.php          ← POST: Rate completed ride
│   └── sos.php                ← POST: Send SOS alert
├── admin/
│   ├── login.php              ← Admin login
│   ├── logout.php             ← Admin logout
│   ├── dashboard.php          ← Stats + charts
│   ├── rides.php              ← All rides management
│   ├── users.php              ← Rider management
│   ├── drivers.php            ← Driver management
│   ├── transactions.php       ← Wallet transactions
│   ├── promo_codes.php        ← Promo code management
│   └── sos_alerts.php         ← Emergency alerts
└── assets/
    ├── css/main.css           ← Main stylesheet
    ├── js/main.js             ← Main JavaScript
    └── images/
        ├── logo.svg
        └── uploads/           ← Profile picture uploads
```

## ⚙️ Installation

### Requirements
- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ / MariaDB 10.3+
- Apache with mod_rewrite OR XAMPP/WAMP/MAMP

### Steps

1. **Copy folder** to your web server root:
   ```
   XAMPP → C:/xampp/htdocs/flashride/
   WAMP  → C:/wamp64/www/flashride/
   ```

2. **Create database** and import schema:
   ```bash
   mysql -u root -p
   CREATE DATABASE flashride_db;
   USE flashride_db;
   source database.sql;
   ```
   Or use **phpMyAdmin → Import → database.sql**

3. **Update config** in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');        // Your MySQL username
   define('DB_PASS', '');            // Your MySQL password
   define('DB_NAME', 'flashride_db');
   define('APP_URL', 'http://localhost/flashride');
   ```

4. **Run the app** at: `http://localhost/flashride`

## 🔑 Demo Credentials

| Role   | Email                  | Password  |
|--------|------------------------|-----------|
| Rider  | arjun@example.com      | Test@123  |
| Driver | suresh@example.com     | Test@123  |
| Admin  | admin@flashride.com    | Admin@123 |

## 🎯 Key Features

- **Live Map Booking** — Leaflet.js + OpenStreetMap (no API key needed!)
- **Auto Driver Assignment** — Finds nearest available driver automatically
- **Real-time Tracking** — GPS polling every 5 seconds
- **OTP Verification** — 4-digit OTP to start each ride
- **FlashWallet** — Add money, auto-deduct, transaction history
- **Promo Codes** — Percentage + fixed discounts with limits
- **SOS Alerts** — Emergency button with admin notification
- **Surge Pricing** — Auto peak-hour multiplier (8-10am, 5-8pm)
- **Star Ratings** — Post-ride driver ratings
- **Admin Dashboard** — Charts, user/driver management, full control

## 💡 Notes
- The map uses **OpenStreetMap + Leaflet** (completely free, no API key)
- For production, enable HTTPS and set `display_errors = Off`
- Wallet recharge is demo-mode (no real payment gateway integrated)
