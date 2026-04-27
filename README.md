# E&N School Supplies — Ordering & Management System

A fully-featured vanilla PHP / MySQL / JavaScript / CSS school supplies ordering and management system for **E&N School Supplies**. Supports Admin, Staff, and Customer roles with a dedicated in-store Kiosk mode. No frameworks, no build step, no Composer — runs directly on XAMPP / Apache.

---

## Table of Contents

1. [Requirements](#1-requirements)
2. [Deployment Guide](#2-deployment-guide)
3. [config.json Reference](#3-configjson-reference)
4. [Project Structure](#4-project-structure)
5. [Roles & Access Control](#5-roles--access-control)
6. [Feature Documentation](#6-feature-documentation)
7. [Order Lifecycle](#7-order-lifecycle)
8. [System Settings Reference](#8-system-settings-reference)
9. [API Endpoints Reference](#9-api-endpoints-reference)
10. [Cron Job Setup](#10-cron-job-setup)
11. [Security Checklist](#11-security-checklist)
12. [Troubleshooting](#12-troubleshooting)
13. [License](#13-license)

---

## 1. Requirements

| Requirement | Minimum Version | Notes |
|---|---|---|
| PHP | 8.0+ | Extensions: `pdo_mysql`, `openssl`, `fileinfo`, `mbstring` |
| MySQL | 5.7+ | Or MariaDB 10.3+ |
| Apache | 2.4+ | `mod_rewrite` must be enabled |
| XAMPP (local) | Any recent | Recommended for local development |

> **Windows Hosting:** The project is developed on Windows/XAMPP. All paths use `APP_ROOT` so it is platform-agnostic.

---

## 2. Deployment Guide

### Step 1 — Copy files

Place the entire project folder inside your web server's document root:

- **XAMPP (Windows):** `C:\xampp\htdocs\en-school-supplies-a\`
- **Linux Apache:** `/var/www/html/en-school-supplies-a/`

### Step 2 — Configure `config.json`

Open `config.json` at the project root and update every value before going live:

```json
{
  "database": {
    "host": "localhost",
    "name": "azeu_en_school_supplies",
    "user": "root",
    "password": "",
    "charset": "utf8mb4"
  },
  "aes": {
    "cipher": "aes-256-cbc",
    "key": "CHANGE_THIS_KEY_BEFORE_DEPLOY_32B!",
    "iv":  "CHANGE_THIS_IV16"
  },
  "admin": {
    "default_username": "admin@en.com",
    "default_password": "admin123"
  },
  "system": {
    "store_name": "E&N School Supplies",
    "store_phone": "",
    "store_email": "",
    "logo_path": "assets/images/logo.png",
    "timezone": "Asia/Manila",
    "auto_logout_hours": 8,
    "low_stock_percent": 10,
    "kiosk_idle_seconds": 90
  }
}
```

> See [Section 3](#3-configjson-reference) for a full description of every key.

### Step 3 — Run Setup

Visit the setup page in your browser:

```
http://localhost/en-school-supplies-a/setup.php
```

The setup script will:
- Create the database `azeu_en_school_supplies` (drops and recreates on re-run)
- Create all tables from `database.sql`
- Seed the default admin account (AES-encrypted password)
- Seed default item categories and item names
- Seed all `system_settings` rows with defaults
- Create required upload directories under `uploads/`

### Step 4 — First Login

Navigate to the login page and sign in with the seeded admin account:

| Field | Value |
|---|---|
| Username or Email | `admin@en.com` |
| Password | `admin123` |

After login you will be redirected to the Admin Dashboard.

**Immediately change the admin password** in Profile → Change Password.

### Step 5 — Post-Setup Cleanup

- **Delete `setup.php`** from the server. Running it again will wipe all data.
- Upload your store logo at **Admin → System Settings → Website Logo**.
- Set your store name, phone, and email in **System Settings**.
- Add inventory items under **Admin → Inventory**.

### Step 6 — Cron Job (Optional but Recommended)

See [Section 10](#10-cron-job-setup) for instructions to enable automatic staff session cleanup.

---

## 3. config.json Reference

```json
{
  "database": {
    "host":     "localhost",           // MySQL host
    "name":     "azeu_en_school_supplies", // Database name
    "user":     "root",               // MySQL user
    "password": "",                   // MySQL password
    "charset":  "utf8mb4"             // Always utf8mb4
  },
  "aes": {
    "cipher": "aes-256-cbc",          // Cipher (do not change)
    "key":    "CHANGE_THIS_KEY_BEFORE_DEPLOY_32B!", // Min 32 chars; hashed to 32 bytes
    "iv":     "CHANGE_THIS_IV16"      // Min 16 chars; hashed to 16 bytes
  },
  "admin": {
    "default_username": "admin@en.com", // Used by setup.php seeder only
    "default_password": "admin123"      // Change after first login
  },
  "system": {
    "store_name":        "E&N School Supplies", // Fallback if DB not seeded yet
    "store_phone":       "",           // Shown to flagged users + landing footer
    "store_email":       "",           // Shown on landing footer
    "logo_path":         "assets/images/logo.png", // Fallback logo path
    "timezone":          "Asia/Manila",// PHP date_default_timezone_set value
    "auto_logout_hours": 8,            // Stale staff session threshold (hours)
    "low_stock_percent": 10,           // Below this % of max_order_qty → Low Stock
    "kiosk_idle_seconds": 90           // Kiosk inactivity reset timeout
  }
}
```

> **Important:** `config.json` is blocked from direct web access by `.htaccess`. Never expose it publicly.

---

## 4. Project Structure

```
en-school-supplies-a/
│
├── config.json                    # DB credentials, AES key, system defaults
├── database.sql                   # Full MySQL schema (all tables + indexes)
├── setup.php                      # One-click installer / seeder (delete after use)
├── index.php                      # Public landing page
├── login.php                      # Login + Register tab UI
├── register.php                   # Redirects to login.php?tab=register
├── logout.php                     # POST-only logout handler (CSRF-protected)
├── kiosk.php                      # Full-screen in-store kiosk (self-contained)
├── receipt.php                    # Print-friendly receipt page
├── 403.php                        # Access denied error page
├── 404.php                        # Not found error page
├── .htaccess                      # Apache: blocks sensitive dirs, error docs
│
├── assets/
│   ├── css/
│   │   ├── global.css             # CSS variables, reset, dark/light themes
│   │   ├── components.css         # Buttons, modals, tables, cards, toasts, badges
│   │   ├── layout.css             # Navbar + sidebar layout
│   │   ├── cart-drawer.css        # Shared cart drawer component styles
│   │   ├── kiosk.css              # Kiosk full-screen overrides
│   │   └── print.css              # Receipt print styles
│   ├── js/
│   │   ├── global.js              # window.EN: toast, api fetch, formatPrice, escapeHtml
│   │   ├── theme.js               # Dark/light OS detection + toggle logic
│   │   ├── layout.js              # Sidebar collapse + 30s badge refresh
│   │   ├── custom-select.js       # Custom styled <select> dropdown
│   │   ├── pagination.js          # window.Pagination reusable renderer
│   │   ├── modal.js               # window.Modal.show() / close()
│   │   └── cart-drawer.js         # window.CartDrawer shared cart module
│   └── images/
│       ├── logo.png               # Default store logo
│       └── country_flags/         # PNG flag icons (PH, SG, JP, US, GB, AU, etc.)
│
├── includes/
│   ├── config.php                 # Loads config.json, creates $pdo, defines APP_ROOT/BASE_PATH
│   ├── auth.php                   # Session guards, require_login(), require_role(), get_badge_counts()
│   ├── csrf.php                   # csrf_token(), csrf_field(), csrf_check()
│   ├── aes.php                    # aes_encrypt() / aes_decrypt()
│   ├── logger.php                 # log_info/warning/error() → logs/system.log + DB
│   ├── helpers.php                # e(), sanitize(), format_price(), redirect(), url(), json_response()
│   ├── settings.php               # get_setting() / set_setting() (request-cached)
│   ├── layout_header.php          # Shared HTML head + navbar + sidebar partial
│   ├── layout_footer.php          # Closes shell, loads global + page-specific JS
│   └── profile_content.php        # Shared profile edit form (all roles)
│
├── api/                           # AJAX endpoints (POST, JSON, CSRF-required)
│   ├── auth/
│   │   ├── login.php              # Login handler (PRG)
│   │   └── register.php           # Registration handler (PRG)
│   ├── orders/
│   │   ├── create.php             # Place order — validate, decrement stock, generate PIN
│   │   ├── update_status.php      # Mark Ready / Claim (PIN-verified)
│   │   ├── cancel.php             # Cancel + restore stock
│   │   └── delete.php             # Admin hard-delete
│   ├── inventory/
│   │   ├── get_items.php          # Paginated item feed (kiosk + customer)
│   │   ├── add_item.php
│   │   ├── edit_item.php
│   │   ├── delete_item.php
│   │   └── add_stock.php
│   ├── users/
│   │   ├── add_user.php
│   │   ├── edit_user.php
│   │   ├── delete_user.php
│   │   ├── flag_user.php
│   │   ├── unflag_user.php
│   │   └── approve_user.php
│   ├── settings/
│   │   └── update.php
│   ├── profile/
│   │   ├── update.php
│   │   └── upload_avatar.php
│   ├── analytics/
│   │   └── get_data.php
│   └── badges.php                 # Lightweight badge count endpoint (polled every 30s)
│
├── admin/                         # Each page in its own folder with .php/.css/.js
│   ├── dashboard/
│   ├── manage_orders/             # Includes _claim_modal.php
│   ├── manage_users/              # Includes _add/edit/flag user modals
│   ├── pending_accounts/
│   ├── flagged_users/
│   ├── inventory/                 # Includes _add/edit item + _add_stock modals
│   ├── staff_statistics/
│   ├── analytics/
│   ├── system_settings/
│   └── profile/
│
├── staff/
│   ├── dashboard/
│   ├── manage_orders/
│   ├── pending_users/
│   └── profile/
│
├── customer/
│   ├── dashboard/
│   ├── make_order/
│   ├── order_history/
│   └── profile/
│
├── cron/
│   └── auto_logout.php            # Closes stale staff sessions (run on a schedule)
│
├── uploads/
│   ├── admin/profiles/            # Admin avatars: {user_id}.{ext}
│   ├── staff/profiles/            # Staff avatars
│   ├── customer/profiles/         # Customer avatars
│   ├── inventory/                 # Item images
│   └── system/                    # System logo (logo.png)
│
└── logs/
    └── system.log                 # Append-only activity log
```

---

## 5. Roles & Access Control

### 5.1 Roles

| Role | Created By | Approval | Sidebar Pages |
|---|---|---|---|
| **Admin** | DB seed (`setup.php`) | N/A — built-in | Dashboard, Manage Orders, Inventory, Manage Users, Analytics, System Settings |
| **Staff** | Admin only | Auto-approved | Dashboard, Manage Orders, Pending Users |
| **Customer** | Self-register or Admin-create | Pending if self-registered; auto-active if admin-created | Dashboard, Make Order, Order History |
| **Kiosk** | Public (no login) | N/A | N/A — standalone full-screen page |

### 5.2 Account Statuses

| Status | Login | Shown Message |
|---|---|---|
| `active` | Allowed | — |
| `pending` | Blocked | "Your account is awaiting approval." |
| `flagged` | Blocked | "Your account has been flagged. Please contact us at [phone] or visit the store." |

Only Admin can flag or unflag accounts. Admin accounts cannot be flagged.

### 5.3 System Status Matrix

| System Status | Guest/Public | Customer | Staff | Admin |
|---|---|---|---|---|
| `online` | Full access | Full access | Full access | Full access |
| `maintenance` | Landing + Login pages only | Login allowed; view only, no orders | Login allowed; orders disabled | Full access |
| `offline` | Landing + Login pages only | Login blocked | Login blocked | Full access |

System status is set by Admin in **System Settings** and enforced on every authenticated request.

---

## 6. Feature Documentation

### 6.1 Public Pages

#### Landing Page (`index.php`)
- Shows store branding, hero section with "Order Now" (→ kiosk) and "Login" buttons.
- Displays up to 8 featured items pulled from inventory (most recently added, in-stock).
- Footer shows store name, phone, email, and current system status.
- Has its own dark/light theme toggle (uses OS preference by default).

#### Login Page (`login.php`)
- Combined **Login** and **Register** tab interface.
- **Login tab:** accepts username or email + password. Shows flash error messages for flagged/pending/wrong-credentials/system-offline cases.
- **Register tab:** fields — Full Name, Username, Email, Phone, Password, Confirm Password. Created accounts have `status=pending`. Server-side + client-side validation. Field errors are highlighted inline with the previous input preserved.
- CSRF-protected. PRG pattern (no double-submit on refresh).

#### Kiosk (`kiosk.php`)
- **Full-screen**, public, no sidebar or back-links.
- Disabled automatically if: `disable_no_login_orders=1`, system is `offline`, or system is `maintenance`.
- Shows category tabs + search bar + item grid (12 items per page via API).
- Each item card: image, name, category badge, stock count, price, quantity stepper.
- **Floating cart button** (bottom-right) with item count badge → opens **Cart Drawer**.
- Cart Drawer → "Place Order" → confirm modal → guest info form (Name required, Phone required, Note optional) → POST to `api/orders/create.php` → **Receipt modal** with Claim PIN displayed prominently.
- **Idle reset timer:** after configurable inactivity (default 90 s), shows a 10 s warning toast, then clears cart and resets to home grid.
- Has its own dark/light theme toggle (session-scoped, not saved to DB).

#### Receipt Page (`receipt.php`)
- Accessed via `receipt.php?order=ORD-XXXXX&pin=XXXX`.
- Access control: order owner (logged-in), staff/admin, or PIN-matched URL (for guests).
- Shows: store logo, store name, Order ID, **Claim PIN** (boxed prominently), date/time, customer name (or "Guest: …"), itemized table (name snapshot, qty, unit price, subtotal), grand total, status, thank-you footer.
- Browser **Print** button → PDF via browser print dialog (no external library).

---

### 6.2 Customer Pages

#### Dashboard (`customer/dashboard/`)
- Greeting with first name, quick-action cards to Make Order and Order History.
- Summary: count of active orders (pending + ready), last order date.

#### Make Order (`customer/make_order/`)
- Item grid (same UX as kiosk, 20 items per page).
- Cart state stored in `localStorage` scoped to user ID — survives page refresh.
- Cart Drawer → confirm modal → POST → receipt modal.
- No guest info form (customer is already identified by session).

#### Order History (`customer/order_history/`)
- Table: Order ID, Items (expandable), Total, Status, Date Ordered, Actions.
- **Status badges:** Pending (yellow), Ready (blue), Claimed (green), Cancelled (red).
- **Cancel** button shown only for `pending` or `ready` orders — opens confirm modal — calls `api/orders/cancel.php` → stock is restored.
- 15 orders per page, paginated.

#### Profile (`customer/profile/`)
- Edit: Full Name, Username, Email, Phone.
- Change password (requires current password).
- Upload avatar (JPG/PNG/WebP, max 1 MB). Old avatar deleted on replace.

---

### 6.3 Staff Pages

#### Dashboard (`staff/dashboard/`)
- Greeting, quick-action cards, preview of pending orders and pending accounts.

#### Manage Orders (`staff/manage_orders/`)
- Same full order table as Admin Manage Orders.
- Actions: **Mark Ready** (pending → ready), **Claim** (ready → claimed, requires PIN), **Cancel** (restores stock).
- Staff cannot delete orders.

#### Pending Users (`staff/pending_users/`)
- Table of self-registered customer accounts awaiting approval.
- Actions: **Approve** (sets status=active) or **Delete**.
- Sidebar badge shows count of pending accounts.

#### Profile (`staff/profile/`)
- Same as Customer profile (name, username, email, phone, password, avatar).

---

### 6.4 Admin Pages

#### Dashboard (`admin/dashboard/`)
- Greeting, quick-action cards to all admin pages.
- Mini stats: today's orders, today's revenue, pending accounts count.
- Recent orders table (latest 5).

#### Manage Orders (`admin/manage_orders/`)
- Full order queue with Smart Filters: search (order code, customer, items, notes, PIN, status), order type (all / guest / registered), date range (all / today / 7 days / 30 days), sort presets.
- Status tabs: All / Pending / Ready / Claimed / Cancelled.
- Actions: **Mark Ready**, **Claim** (PIN modal), **Cancel**, **Delete** (admin only).
- Expandable item rows per order.

#### Manage Users (`admin/manage_users/`)
- Table of all users (admin, staff, customer).
- Search by name/email/username, filter by role and status.
- **Add User** modal — creates staff or customer accounts (admin-created = auto-active).
- **Edit** user — full name, username, email, phone, role.
- **Flag** user — requires a flag reason; blocks their login immediately.
- **Delete** user — permanent, cannot be undone.

#### Pending Accounts (`admin/pending_accounts/`)
- Same as Staff Pending Users. Approve or delete self-registered customers.

#### Flagged Users (`admin/flagged_users/`)
- Table of all flagged accounts with flag reason (expandable if long).
- Actions: **Unflag** (restores status=active) or **Delete**.

#### Inventory (`admin/inventory/`)
- Table: image, ID, item name, category, price, stock count, max order qty, stock status, actions.
- **Stock status** auto-computed: `Out of Stock` (0), `Low Stock` (≤ `low_stock_percent`% of `max_order_qty`), `In Stock`.
- **Add Item** — item name dropdown (from default names + Custom option), category dropdown, price, stock, max order qty, image upload (JPG/PNG/WebP, max 2 MB).
- **Edit Item** — all fields editable including image.
- **Add Stock** — modal with quantity input.
- **Delete Item** — only if no pending/ready orders reference it.
- **Manage Categories** — add or delete category entries.
- Smart Filters: search by name, filter by category, filter by stock level, sort presets.

#### Staff Statistics (`admin/staff_statistics/`)
- Per-staff table: total logins, total hours worked, average session length, last login, suspicious session count.
- **Suspicious session** = auto-closed by cron because staff didn't manually logout within the threshold. Highlighted red.
- Charts: top 5 most active staff (bar), login frequency over time (line).

#### Analytics (`admin/analytics/`)
- Date range filter: today, last 7 days, last 30 days, custom.
- Charts and metrics:
  - Total orders over time (line chart)
  - Revenue over time (line chart)
  - Top selling items (bar chart)
  - Orders by status breakdown (pie/donut)
  - Orders by category (bar chart)
  - New customers over time
  - Guest vs. registered orders ratio

#### System Settings (`admin/system_settings/`)
- See [Section 8](#8-system-settings-reference) for all configurable settings.

#### Profile (`admin/profile/`)
- Full Name, Username, Email, Phone, Password, Avatar — same as other role profiles.

---

### 6.5 Shared Components

#### Cart Drawer
- Slide-in drawer used by both Kiosk and Customer Make Order.
- Managed by `window.CartDrawer` (`assets/js/cart-drawer.js`).
- Shows itemized list with quantity steppers, individual remove (×), line subtotals, grand total.
- "Place Order" button → confirm modal → (kiosk only) guest info modal → POST → receipt modal.
- Validates quantity against `stock_count` and `max_order_qty` client-side; server re-validates before committing.

#### Sidebar Badges
- Sidebar link badges are injected at page load via PHP and refreshed every 30 seconds by `layout.js` via `GET /api/badges.php`.
- Admin/Staff: `pending_orders` (pending order count), `pending_accounts` (pending user count).
- Customer: `active_orders` (pending + ready order count).

#### Theme Toggle
- Moon/Sun button in the navbar toggles between light and dark mode.
- Default follows OS `prefers-color-scheme` unless a user preference is saved in the DB.
- Admin **Force Dark Mode** setting overrides all user preferences system-wide.

#### Profile Chip
- Top-right of the navbar shows user avatar (or initial), full name, and role.
- Clicking opens a dropdown with **Profile** and **Logout** links.
- Logout is a POST form (CSRF-protected).

---

## 7. Order Lifecycle

### 7.1 Status Flow

```
Customer/Kiosk places order
        │
        ▼
    [ pending ]  ──────────────────────────────────────────► [ cancelled ]
        │                                                         ▲
        │ Admin/Staff: Mark Ready                                 │
        ▼                                                         │
    [ ready ]  ──────────────────────────────────────────────────┘
        │
        │ Admin/Staff: Claim (PIN required)
        ▼
    [ claimed ]   (terminal — cannot be cancelled or deleted)
```

### 7.2 Who Can Do What

| Action | Customer | Staff | Admin |
|---|---|---|---|
| Place order | ✅ | — | — |
| Mark Ready | — | ✅ | ✅ |
| Claim (requires PIN) | — | ✅ | ✅ |
| Cancel | ✅ own pending/ready | ✅ any non-claimed | ✅ any non-claimed |
| Delete | — | — | ✅ non-claimed only |

### 7.3 Stock Policy

| Event | Stock Change |
|---|---|
| Order placed | Decremented by ordered quantity (transactional, `FOR UPDATE` row lock) |
| Order cancelled | Restored (transactional) |
| Order marked Ready | No change |
| Order claimed | No change |
| Order deleted (pending/ready) | Restored before deletion |
| Order deleted (cancelled) | Already restored at cancel time |
| Order deleted (claimed) | No change (stock already consumed) |

### 7.4 Claim PIN

- Generated at order placement: `random_int(0, 9999)` zero-padded to 4 digits.
- **Registered order display:** `1234`
- **Guest order display:** `GST-1234`
- Printed on the receipt modal and on `receipt.php`.
- Required at the **Claim** modal on the Manage Orders page — verified server-side with `hash_equals`.
- Wrong PIN is rejected and logged as a warning in `system_logs`.

---

## 8. System Settings Reference

All settings are stored in the `system_settings` table as key/value pairs and can be updated by Admin in **System Settings**.

| Setting Key | Default | Description |
|---|---|---|
| `store_name` | E&N School Supplies | Store name shown in navbar, receipts, login page, and landing page |
| `store_phone` | *(empty)* | Contact phone — shown to flagged users and in the landing footer |
| `store_email` | *(empty)* | Contact email — shown in the landing footer |
| `logo_path` | `assets/images/logo.png` | Path to the active logo file (updated on logo upload) |
| `timezone` | `Asia/Manila` | PHP timezone; controls all displayed timestamps |
| `navbar_country_flag` | `PH` | 2-letter country code for the flag image beside the clock |
| `system_status` | `online` | `online` / `maintenance` / `offline` — controls system-wide access |
| `force_dark` | `0` | `1` = force dark mode for all users regardless of their preference |
| `disable_no_login_orders` | `0` | `1` = disables the kiosk and all guest ordering |
| `online_payment` | `0` | Placeholder toggle — no payment integration implemented yet |
| `auto_logout_hours` | `8` | Hours before a stale staff session is auto-closed by the cron job |
| `low_stock_percent` | `10` | Items below this % of `max_order_qty` are labelled "Low Stock" |
| `kiosk_idle_seconds` | `90` | Seconds of inactivity before the kiosk resets (min 30) |

---

## 9. API Endpoints Reference

All endpoints are located under `/api/` and:
- **Accept:** `POST` requests only (except `get_items.php` which accepts `GET`)
- **Return:** JSON `{ ok: true/false, ... }`
- **Require:** valid CSRF token (form field `csrf_token` or header `X-CSRF-Token`)
- **Require:** appropriate login session (except `create.php` for guest orders, `login.php`, `register.php`)

### Authentication

| Endpoint | Auth Required | Description |
|---|---|---|
| `POST api/auth/login.php` | None | Validate credentials, start session, redirect to dashboard |
| `POST api/auth/register.php` | None | Create pending customer account |

### Orders

| Endpoint | Auth Required | Description |
|---|---|---|
| `POST api/orders/create.php` | None (guest) or any role | Validate cart, decrement stock, generate PIN, return order + receipt URL |
| `POST api/orders/update_status.php` | Admin or Staff | Mark order ready (`action=ready`) or claim with PIN (`action=claim`) |
| `POST api/orders/cancel.php` | Any logged-in | Cancel order and restore stock |
| `POST api/orders/delete.php` | Admin only | Hard-delete non-claimed order (restores stock if needed) |

### Inventory

| Endpoint | Auth Required | Description |
|---|---|---|
| `GET api/inventory/get_items.php` | None | Paginated item list (used by kiosk + customer make-order) |
| `POST api/inventory/add_item.php` | Admin | Add new inventory item with optional image |
| `POST api/inventory/edit_item.php` | Admin | Edit existing item |
| `POST api/inventory/delete_item.php` | Admin | Delete item (blocked if active order references it) |
| `POST api/inventory/add_stock.php` | Admin | Increment `stock_count` for an item |

### Users

| Endpoint | Auth Required | Description |
|---|---|---|
| `POST api/users/add_user.php` | Admin | Create staff or customer account (auto-active) |
| `POST api/users/edit_user.php` | Admin | Update user profile fields |
| `POST api/users/delete_user.php` | Admin | Permanently delete user |
| `POST api/users/flag_user.php` | Admin | Flag user with a reason (blocks login) |
| `POST api/users/unflag_user.php` | Admin | Remove flag (restores login) |
| `POST api/users/approve_user.php` | Admin or Staff | Approve pending account (sets status=active) |

### Settings

| Endpoint | Auth Required | Description |
|---|---|---|
| `POST api/settings/update.php` | Admin | Update one or more `system_settings` key/value pairs + handle logo upload |

### Profile

| Endpoint | Auth Required | Description |
|---|---|---|
| `POST api/profile/update.php` | Any logged-in | Update own name, username, email, phone, password |
| `POST api/profile/upload_avatar.php` | Any logged-in | Upload and set own avatar image |

### Analytics & Badges

| Endpoint | Auth Required | Description |
|---|---|---|
| `POST api/analytics/get_data.php` | Admin | Return chart data for the analytics page |
| `GET api/badges.php` | Any logged-in | Return badge counts (pending orders, pending accounts, active orders) |

---

## 10. Cron Job Setup

The cron job closes staff/admin sessions that have been open beyond the `auto_logout_hours` threshold without a manual logout. These are marked as **suspicious** and counted against staff performance in Staff Statistics.

### Linux / macOS (crontab)

```bash
# Open crontab
crontab -e

# Run every 15 minutes
*/15 * * * * php /var/www/html/en-school-supplies-a/cron/auto_logout.php >> /var/www/html/en-school-supplies-a/logs/cron.log 2>&1
```

### Windows (Task Scheduler)

1. Open **Task Scheduler** → **Create Basic Task**.
2. Set trigger: **Daily**, repeat every **15 minutes**.
3. Set action: **Start a program**.
4. Program/script: `C:\xampp\php\php.exe`
5. Arguments: `"D:\path\to\en-school-supplies-a\cron\auto_logout.php"`
6. Start in: `D:\path\to\en-school-supplies-a\`

### Manual Run (Testing)

```bash
php cron/auto_logout.php
```

Output example:
```
Closed 2 stale session(s).
```

---

## 11. Security Checklist

Complete this checklist before going live:

- [ ] **Change AES key and IV** in `config.json` — use random strings of at least 32 and 16 characters respectively
- [ ] **Change default admin password** — log in as admin, go to Profile → Change Password
- [ ] **Delete `setup.php`** — running it again drops and recreates the entire database
- [ ] **Use HTTPS** — configure SSL on your web server; PHP sessions are far safer over TLS
- [ ] **Verify `.htaccess` is active** — `mod_rewrite` must be enabled; test by visiting `http://yoursite/config.json` (should return 403)
- [ ] **Set correct file permissions** — `uploads/` and `logs/` should be writable by the web server; `includes/` and `config.json` should not be directly browsable
- [ ] **Database password** — change from the default empty password to a strong password in `config.json`
- [ ] **Review system_settings** — set store name, phone, and email via System Settings
- [ ] **Upload a real logo** — System Settings → Website Logo (PNG, max 2 MB)
- [ ] **Configure timezone** — System Settings → Timezone (default: Asia/Manila)
- [ ] **Set up cron** — enables auto-logout for idle staff sessions

### What the system already does for you

- CSRF token validation on every POST (form field + AJAX header)
- AES-256-CBC password encryption
- PDO prepared statements on all queries (no SQL injection)
- `.htaccess` blocks direct access to `includes/`, `logs/`, `cron/`, `config.json`
- `session_regenerate_id(true)` on every login and logout
- Rate limiting: 5 failed login attempts per IP + identifier per 5 minutes
- File upload MIME-type sniffing via `finfo` (not just extension)
- Upload size limits: 1 MB for avatars, 2 MB for item images and logo

---

## 12. Troubleshooting

### "Database connection failed"
- Ensure MySQL is running (XAMPP Control Panel or `mysqld` service).
- Check `config.json` → `database.host`, `database.user`, `database.password`.
- Ensure the database exists; if not, re-run `setup.php`.

### Blank page / 500 error after setup
- Check `logs/php_errors.log` in the project root.
- Ensure PHP extensions are enabled: `pdo_mysql`, `openssl`, `fileinfo`, `mbstring`.
- Verify `config.json` is valid JSON (no trailing commas, no comments).

### "Invalid CSRF token" on form submit
- Likely caused by a session expiry or submitting a cached form after a long wait.
- Refresh the page and try again.
- If persistent, check that `session_save_path` is writable on your server.

### Logo not showing in navbar
- Ensure the file exists at the path stored in `system_settings.logo_path`.
- Re-upload via **Admin → System Settings → Website Logo**.
- Check that `uploads/system/` directory is writable by the web server.

### Kiosk shows "Kiosk Ordering Unavailable"
- Check **Admin → System Settings → Disable No-Login Orders** — toggle it off.
- Check **System Status** — kiosk is disabled when status is `maintenance` or `offline`.

### Items not appearing in kiosk / make order
- Items must have `stock_count > 0` to appear.
- Add stock via **Admin → Inventory → Add Stock**.

### Staff Statistics showing no data
- Staff sessions are recorded only for users with `role = 'staff'` or `role = 'admin'`.
- Each login inserts a row into `staff_sessions`. Ensure the table exists (re-run `setup.php` if needed).

### Auto-logout cron not working
- Test manually: `php cron/auto_logout.php` from the project root.
- Ensure the PHP binary is in the system PATH, or use the full path in the cron command.
- Check `logs/system.log` for cron activity entries.

### Uploads failing (avatar / item image)
- Ensure `uploads/` subdirectories exist and are writable: `uploads/admin/profiles/`, `uploads/staff/profiles/`, `uploads/customer/profiles/`, `uploads/inventory/`, `uploads/system/`.
- Check `upload_max_filesize` and `post_max_size` in `php.ini` — set to at least `3M`.
- Only `image/jpeg`, `image/png`, and `image/webp` MIME types are accepted.

---

## 13. License

Internal project — **E&N School Supplies**.  
Not licensed for redistribution.
