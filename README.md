# E&N School Supplies — Ordering & Management System

A vanilla PHP/MySQL/JavaScript/CSS school supplies ordering and management system with Admin, Staff, and Customer roles plus an in-store Kiosk mode.

## Requirements

- **PHP 7.4+** (with PDO MySQL, openssl, fileinfo extensions)
- **MySQL 5.7+** or MariaDB 10.3+
- **Apache** with `mod_rewrite` enabled (or equivalent web server)

## Quick Setup

1. **Clone / Copy** the project into your web server's document root.

2. **Edit `config.json`** — update database credentials and change the AES key/IV before deploying:
   ```json
   {
     "db": { "host": "localhost", "name": "azeu_en_school_supplies", "user": "root", "pass": "" },
     "aes": { "key": "CHANGE_THIS_KEY_BEFORE_DEPLOY_32B!", "iv": "CHANGE_THIS_IV16" }
   }
   ```

3. **Run Setup** — visit `http://localhost/setup.php` in your browser. This will:
   - Create the database and tables
   - Seed the default admin account, categories, and system settings
   - Create required upload directories

4. **Login** with the default admin credentials:
   - **Username / Email:** `admin@en.com`
   - **Password:** `admin123`

5. **Delete or protect `setup.php`** after initial setup.

## Project Structure

```
├── admin/                  # Admin pages (per-page folders)
│   ├── dashboard/
│   ├── manage_orders/
│   ├── manage_users/
│   ├── pending_accounts/
│   ├── flagged_users/
│   ├── inventory/
│   ├── staff_statistics/
│   ├── analytics/
│   ├── system_settings/
│   └── profile/
├── staff/                  # Staff pages
│   ├── dashboard/
│   ├── manage_orders/
│   ├── pending_accounts/
│   └── profile/
├── customer/               # Customer pages
│   ├── dashboard/
│   ├── make_order/
│   ├── order_history/
│   └── profile/
├── api/                    # AJAX API endpoints (grouped by feature)
│   ├── auth/               # login, register
│   ├── orders/             # create, list, update_status, cancel, delete
│   ├── users/              # add, edit, delete, flag, unflag, approve, list
│   ├── inventory/          # get_items, add_item, edit_item, delete_item, categories
│   ├── settings/           # update
│   ├── profile/            # update, upload_avatar
│   └── badges.php
├── assets/                 # Shared CSS & JS
│   ├── css/
│   └── js/
├── includes/               # PHP helpers & partials
│   ├── config.php
│   ├── aes.php
│   ├── csrf.php
│   ├── logger.php
│   ├── helpers.php
│   ├── settings.php
│   ├── auth.php
│   ├── layout_header.php
│   ├── layout_footer.php
│   └── profile_content.php
├── cron/                   # Scheduled tasks
│   └── auto_logout.php
├── uploads/                # User uploads (avatars, inventory images)
├── logs/                   # Application log files
├── config.json             # Application configuration
├── database.sql            # Database schema
├── setup.php               # One-click DB setup
├── index.php               # Public landing page
├── login.php               # Login page
├── register.php            # Registration page
├── logout.php              # Logout handler
├── kiosk.php               # In-store kiosk ordering
├── receipt.php             # Print-friendly receipt
├── 403.php                 # Access denied page
├── 404.php                 # Not found page
└── .htaccess               # Apache rewrite rules & security
```

## Roles

| Role       | Access                                                        |
|------------|---------------------------------------------------------------|
| **Admin**  | Full access: dashboard, orders, users, inventory, analytics, settings |
| **Staff**  | Dashboard, manage orders (process/claim), approve pending accounts    |
| **Customer** | Dashboard, browse & order, order history, view receipts             |
| **Kiosk**  | Public in-store terminal — browse items, place guest orders          |

## Key Features

- **Cart Drawer** — slide-in cart for Kiosk and Customer ordering
- **4-digit Claim PIN** — generated per order, shown on receipt, required for staff to mark as claimed
- **Stock Deduction** — stock deducted on order placement, restored on cancellation
- **Dark/Light Theme** — auto (OS preference), manual toggle, force dark mode (admin setting)
- **Username or Email Login** — users can sign in using either identifier
- **Navbar Country Flag** — configurable country flag shown beside the clock using the included flag image pack
- **Website Logo Upload** — admin can upload a PNG logo in System Settings and it appears in the navbar
- **Role-Aware Navbar** — profile access is available from the navbar dropdown, not the sidebar
- **CSRF Protection** — token per session, validated on all mutations
- **AES-256-CBC Encryption** — for password storage
- **Dual Logging** — file + database
- **Rate Limiting** — login attempts throttled per login identifier
- **Kiosk Idle Timeout** — auto-clears cart after configurable inactivity period
- **Server-rendered Analytics** — revenue charts, top items, category breakdown
- **Responsive Design** — mobile-friendly across all pages

## Cron Job (Optional)

To auto-close stale staff sessions, schedule the cron script:

```bash
# Every 15 minutes
*/15 * * * * php /path/to/cron/auto_logout.php
```

On Windows Task Scheduler, create a task that runs:
```
php.exe "D:\path\to\cron\auto_logout.php"
```

## Security Notes

- **Change AES key/IV** in `config.json` before deploying
- **Change default admin password** after first login
- **Delete `setup.php`** after initial setup
- **Protect `config.json`** — `.htaccess` blocks direct access
- **HTTPS** is recommended for production
- **Upload Limits** — the admin logo upload accepts PNG only and limits files to 2 MB

## License

Internal project — E&N School Supplies.
