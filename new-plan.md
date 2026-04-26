# E&N School Supplies — Full Project Plan v2

A vanilla PHP/MySQL/JS/CSS school-supplies ordering & management system for E&N School Supplies, supporting Admin, Staff, Customer roles plus an in-store Kiosk mode, reorganized so every page lives in its own folder with co-located CSS/JS.

---

## 0. Goal of this revision (v2)

This plan supersedes `full-plan.md`. It keeps every decision from v1 and adds:

1. **Per-page folder structure** — each page has its own folder containing `<page>.php`, `<page>.css`, `<page>.js`, plus any page-specific partials.
2. **Cart drawer** for Customer Make-Order and Kiosk (slide-in, no separate page).
3. **4-digit claim PIN** for order pickup verification.
4. **Stock deduction policy** — deduct on place, restore on cancel.
5. **Filled-in gaps** that v1 left ambiguous (validation rules, session lifecycle, file upload handling, route protection, seeding, etc.).

---

## 1. Tech Stack

- **Backend:** Vanilla PHP 8+ (no frameworks, no Composer)
- **Frontend:** Vanilla HTML5, CSS3, JavaScript (ES6+, no build step, no frameworks)
- **Database:** MySQL 5.7+ / MariaDB via XAMPP
- **PDF / Receipts:** Print-friendly HTML (browser print dialog) — no FPDF, no library
- **Encryption:** AES-256-CBC via `openssl_encrypt` / `openssl_decrypt` (passwords only)
- **Database name:** `azeu_en_school_supplies`
- **Config file:** `config.json` (DB creds, AES key + IV, system defaults)
- **Server:** Apache (XAMPP), `.htaccess` for protection + error docs
- **Session:** PHP native sessions, `httponly`, `samesite=Lax`

---

## 2. Design System

| Token | Value |
|---|---|
| Primary | `#2e7d32` (deep green) |
| Primary Light | `#4caf50` |
| Accent | `#81c784` |
| Surface (light) | `#ffffff` |
| Background (light) | `#f1f8f1` |
| Surface (dark) | `#1e2a1e` |
| Background (dark) | `#121a12` |
| Text Primary | `#1b1b1b` / `#f0f0f0` |
| Border | `#c8e6c9` / `#2e4a2e` |
| Danger | `#d32f2f` |
| Warning | `#f9a825` |
| Info | `#1976d2` |
| Success | `#2e7d32` |
| Radius (sm/md/lg) | `4px / 8px / 16px` |
| Shadow (sm/md) | `0 1px 2px rgba(0,0,0,.06)` / `0 4px 12px rgba(0,0,0,.08)` |

- **Default theme:** OS preference (`prefers-color-scheme`) and persisted per user in DB (`theme_preference`).
- **Force Dark Mode:** Admin toggle in System Settings overrides everyone.
- **Custom select UI:** the green-themed JS dropdown component used everywhere instead of native `<select>` (except where native is needed for accessibility).
- **Typography:** system font stack (`-apple-system, Segoe UI, Roboto, sans-serif`), base 16px, scale 0.875/1/1.125/1.5/2 rem.
- **Spacing scale:** 4, 8, 12, 16, 24, 32, 48, 64 px.

---

## 3. Roles, Access Control & System Status

### 3.1 Roles

| Role | Created By | Approval | Access |
|---|---|---|---|
| Admin | Built-in seed (`admin@en.com` / `admin123`) | N/A | Full system |
| Staff | Admin only | Auto-approved | Orders + Pending Accounts |
| Customer | Self-register (pending) OR Admin (auto-approved) | Required if self-registered | Order + history |

### 3.2 Account statuses
- `active` — normal login allowed
- `pending` — waiting Admin/Staff approval; login blocked with message
- `flagged` — blocked, message tells user to contact store
- (deletion is permanent and only Admin can do it)

### 3.3 Flagged Users
- Only Admin can flag/unflag.
- Login attempt → reject with: *"Your account has been flagged. Please contact us at [store phone] or visit the store."*
- Flag reason stored in `users.flag_reason`. Visible in Manage Flagged Users.

### 3.4 System Status enforcement matrix

| Status | Guest | Customer | Staff | Admin |
|---|---|---|---|---|
| Online | Full public access | Full | Full | Full |
| Offline | Landing + Login pages only | Login blocked | Login blocked | Full |
| Maintenance | Landing + Login pages only | View only, no orders | Orders disabled, can still log in | Full |

`includes/auth.php` reads system status and enforces the above on every authenticated request.

### 3.5 Route protection
- `includes/auth.php` exposes `require_role('admin'|'staff'|'customer')` and `require_login()`.
- Any direct hit to a `/admin/*`, `/staff/*`, `/customer/*` page without the right session → redirect to `/login.php?next=...` and log a warning.
- Wrong-role hit → `403.php`.

---

## 4. Pages, Flows & Functionality

### 4.1 Shared elements (every authenticated page)

- **Top Navbar:**
  - Hamburger (toggles sidebar collapse on desktop, off-canvas on mobile)
  - Store logo + name (from settings)
  - Current date/time + 🇵🇭 PH flag (timezone Asia/Manila by default)
  - Dark/Light toggle (disabled if Force Dark Mode is on)
  - System status badge (color coded)
  - Profile chip → dropdown (Profile / Logout)
- **Sidebar:**
  - Role-aware items, badge counts (new orders, pending accounts) injected at page load by PHP, refreshed every 30 s by JS
  - Collapsible (icon-only) state persisted in `localStorage`
- **Profile page:** edit full name, email, phone, password (current password required), avatar upload (≤1 MB, jpg/png/webp). Saved per role under `uploads/{role}/profiles/{user_id}.{ext}`. Old image deleted on replace.
- **Global error display:** `<div id="page-error">` rendered if `$_SESSION['flash_error']` set + global JS toast for AJAX errors. All errors also logged.

### 4.2 Public pages

#### Landing (`index.php`)
- Hero (logo, tagline, "Order Now" → kiosk, "Login" → login)
- Featured items: top 8 by stock or recent (DB driven)
- Footer: store name, phone, email, system status

#### Login (`login.php`)
- Email + password
- Submit handled via `api/auth/login.php` (POST → server validates → redirect = PRG)
- Rejection messages: invalid creds / pending / flagged / system offline (for non-admins)
- "Remember me" not implemented in v1; out of scope for v2

#### Register (`register.php`)
- Full Name, Email, Phone, Password (≥8, mixed), Confirm Password
- Client-side validation + server-side re-validation (never trust client)
- Result → `users.status = 'pending'`, redirect to login with success flash

#### Logout (`logout.php`)
- POST-only, CSRF-token protected
- Records `staff_sessions.logout_time`, `logout_type='manual'`, computes `duration_minutes`

### 4.3 Kiosk (`kiosk.php`) — in-store touchscreen
- Full-screen, **no navbar/sidebar/back-links**
- Item grid: 20/page, large cards, big touch targets
- Each card: image, name, price, stock badge, qty stepper (0…max_order_qty)
- **Floating cart button** (bottom-right) with item count badge → opens **Cart Drawer**
- Cart Drawer:
  - List of selected items, qty stepper, remove (×), subtotal per line, grand total
  - "Place Order" → confirm modal → collects **Name (required), Phone (required), Note (optional)**
- On submit:
  - Stock decremented, order saved, **4-digit Claim PIN generated**
  - Receipt modal: Order ID + Claim PIN prominent, items table, total, "Print" button
- If `disable_no_login_orders = 1` → kiosk shows a friendly disabled state, no order possible.
- Idle timer (configurable, default 90 s) clears the cart and returns to grid.

### 4.4 Admin pages

| Page | Purpose | Key features |
|---|---|---|
| Dashboard | Quick overview | Greeting, quick action cards, today's orders, today's revenue, pending accounts count, latest 5 orders |
| Manage Orders | All orders | Tabs by status, search by order code, expand items, actions: Mark Ready / Claim (PIN-verified) / Cancel / Delete; 15/page |
| Manage Users | All users | Add User modal (Staff/Customer), edit, delete, flag (with reason); search by name/email; filter by role/status |
| Pending Accounts | Approve registrations | Approve / Delete; sidebar badge |
| Flagged Users | Review flagged | Unflag / Delete; expandable reason |
| Inventory | Items CRUD | Auto status (No Stock / Low Stock / On Stock); Add Item (name dropdown from defaults + custom; category dropdown); Add Stock modal; image upload |
| Staff Statistics | Staff session metrics | Total logins, hours, last login, avg session, suspicious sessions count + Top-5 bar chart + login frequency line chart |
| Analytics | KPIs & charts | Orders/revenue over time (line); top items (bar); status pie; category bar; new customers; no-login vs registered ratio; date range filter |
| System Settings | Global config | Logo upload, store name/phone/email, force dark, timezone, default categories list, default item names list, disable no-login orders, online payment toggle (placeholder), system status, auto-logout hours, low-stock threshold % |
| Profile | Edit profile | Shared via `includes/profile_content.php` |

### 4.5 Staff pages

| Page | Purpose |
|---|---|
| Dashboard | Greeting + quick actions + pending orders/accounts preview |
| Manage Orders | Same UX as Admin's Manage Orders (Pending → Ready → Claimed, Cancel) |
| Pending Accounts | Same as Admin |
| Profile | Shared profile content |

### 4.6 Customer pages

| Page | Purpose |
|---|---|
| Dashboard | Greeting + quick links + active orders count + last order date |
| Make Order | Item grid + Cart Drawer (same UX as kiosk minus guest fields) |
| Order History | Table of own orders, status badges, Cancel action (only when Pending or Ready) |
| Profile | Shared profile content |

---

## 5. Cart Drawer (shared component)

Used in both Kiosk and Customer Make Order. Single JS module + CSS file in `/assets/`.

- **Trigger:** floating cart icon (kiosk) or sticky button in toolbar (customer)
- **State:** held in `sessionStorage` (kiosk) or `localStorage` (customer, scoped to user id)
- **Operations:** add, increment/decrement, remove, clear
- **Validation:** qty ≥ 1, qty ≤ stock, qty ≤ max_order_qty; disabled rows for `No Stock`
- **Checkout flow:**
  1. Click "Place Order"
  2. Confirm modal: itemized list with × per item, totals
  3. (Kiosk only) collect Name + Phone + Note
  4. POST `/api/orders/create.php` with cart payload + CSRF token
  5. Server: re-validate every item (price, stock, max_order_qty), compute total server-side, generate `order_code` and 4-digit PIN
  6. Server-side stock decrement inside a transaction
  7. Response: `{ ok: true, order_code, claim_pin, receipt_url }`
  8. Client clears cart, opens receipt modal

---

## 6. Order Lifecycle, Stock & Claim Verification

### 6.1 Statuses and transitions

```
        ┌─────────┐    ┌────────┐    ┌────────┐
place → │ pending │ →  │ ready  │ →  │claimed │
        └────┬────┘    └────┬───┘    └────────┘
             │              │
             └──→ cancelled ←┘  (anytime before claimed)
```

- **Place** (any role / kiosk): create order, items snapshot, decrement stock, generate PIN.
- **Mark Ready** (admin/staff): purely a status flag; stock unchanged.
- **Claim** (admin/staff): opens a "Claim Order" modal that requires the staff to **enter the customer's 4-digit PIN** along with the Order ID. PIN check is server-side. On success → status=`claimed`, `processed_by` set, `updated_at` updated.
- **Cancel** (customer if pending/ready, staff/admin anytime before claimed): status=`cancelled`, **stock restored** transactionally.
- **Delete** (admin only): hard-delete order + items (audit-logged). Does NOT restore stock if already claimed.

### 6.2 Stock deduction policy
- **On Place:** decrement stock by ordered qty (transactional, with row locks `SELECT ... FOR UPDATE`).
- **On Cancel:** increment stock back.
- **On Claim:** no change.
- **On Delete (pending/ready):** restore stock first, then delete.
- **On Delete (claimed):** keep stock as-is; just delete records.

### 6.3 Claim PIN
- 4-digit numeric, generated at order create (`random_int(1000, 9999)`).
- Stored in `orders.claim_pin` (plain — short-lived, low-value secret).
- Printed prominently on the receipt.
- Required at the Claim modal. Wrong PIN → error, audit-logged.
- After claim, PIN remains in DB for traceability but UI hides it.

---

## 7. Receipt
- Pure HTML print-friendly page at `/receipt.php?order=<code>&pin=<pin>` (PIN required for guest orders so URL-sharing alone is not enough; for logged-in users the access is by user_id ownership)
- Contents: store logo + name, Order ID, **Claim PIN** (boxed, big), date/time, customer name (or "Guest: …"), items table (name, qty, unit, subtotal), grand total, status, footer thank-you message.
- Browser print dialog for PDF — no library dependency.

---

## 8. Database Schema

(Same as v1, with these v2 additions/changes — marked **NEW** / **CHG**.)

### `users`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| full_name | VARCHAR(150) | |
| email | VARCHAR(150) UNIQUE | |
| phone | VARCHAR(20) | |
| password | TEXT | AES-256-CBC encrypted |
| role | ENUM('admin','staff','customer') | |
| status | ENUM('active','pending','flagged') | |
| flag_reason | TEXT NULL | |
| profile_image | VARCHAR(255) NULL | relative path |
| theme_preference | ENUM('light','dark','auto') DEFAULT 'auto' | |
| created_by | INT NULL | FK → users.id |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

### `orders`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| order_code | VARCHAR(20) UNIQUE | e.g. `ORD-00042` |
| user_id | INT NULL | NULL for guest |
| guest_name | VARCHAR(150) NULL | |
| guest_phone | VARCHAR(20) NULL | |
| guest_note | TEXT NULL | |
| status | ENUM('pending','ready','claimed','cancelled') | |
| total_price | DECIMAL(10,2) | |
| **claim_pin** | CHAR(4) **NEW** | 4-digit PIN |
| processed_by | INT NULL | FK → users |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### `order_items`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| order_id | INT | FK → orders ON DELETE CASCADE |
| item_id | INT | FK → inventory |
| item_name_snapshot | VARCHAR(150) | |
| quantity | INT | |
| unit_price | DECIMAL(10,2) | |

### `inventory`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| item_name | VARCHAR(150) | |
| category_id | INT NULL | FK → item_categories |
| price | DECIMAL(10,2) | |
| stock_count | INT | |
| max_order_qty | INT | per-order limit |
| item_image | VARCHAR(255) NULL | |
| created_at / updated_at | TIMESTAMP | |

### `item_categories`
| id | category_name UNIQUE | created_at |

### `default_item_names`
| id | item_name UNIQUE |

### `staff_sessions`
| id | user_id (FK) | login_time | logout_time NULL | logout_type ENUM('manual','auto_system') | duration_minutes NULL | is_suspicious TINYINT(1) |

### `system_settings`
| setting_key VARCHAR(100) PK | setting_value TEXT | updated_at |

### `system_logs`
| id | level ENUM('info','warning','error') | message | context JSON NULL | user_id NULL | ip_address NULL | created_at |

### Indexes & FKs
- Index `orders(user_id, created_at)`, `orders(status)`, `orders(order_code)`.
- Index `order_items(order_id)`, `order_items(item_id)`.
- Index `inventory(category_id)`, `inventory(item_name)`.
- All FKs use `ON DELETE RESTRICT` except `order_items(order_id)` which CASCADES.

---

## 9. File & Folder Structure (per-page folders)

> **Rule:** every dashboard/feature page has its own folder. Inside each folder:
> - `<page>.php` — the actual page (named after the folder).
> - `<page>.css` — page-specific styles.
> - `<page>.js` — page-specific scripts.
> - Any partials/modals used only by this page (e.g. `_add_item_modal.php`).
>
> **Shared** styles/scripts/components live in `/assets/`. APIs stay grouped in `/api/`.

```
en-school-supplies/
│
├── config.json
├── index.php                       # Public landing
├── login.php                       # public, has its own .css/.js inline-loaded
├── register.php
├── kiosk.php                       # public full-screen kiosk
├── logout.php                      # POST-only handler
├── receipt.php                     # print-friendly receipt
├── setup.php                       # one-click DB setup (delete after use)
├── database.sql
├── .htaccess
├── 403.php
├── 404.php
│
├── assets/
│   ├── css/
│   │   ├── global.css              # vars, reset, base, themes
│   │   ├── components.css          # buttons, modals, tables, cards, toast, badges
│   │   ├── layout.css              # navbar + sidebar
│   │   ├── cart-drawer.css         # shared cart drawer component
│   │   ├── kiosk.css               # full-screen kiosk overrides
│   │   └── print.css               # receipt print styles
│   ├── js/
│   │   ├── global.js               # toast, fetch wrapper, CSRF, PRG guard
│   │   ├── theme.js                # dark/light + OS detect
│   │   ├── layout.js               # sidebar collapse, badge refresh
│   │   ├── custom-select.js
│   │   ├── pagination.js
│   │   ├── modal.js                # generic modal helpers
│   │   ├── cart-drawer.js          # shared cart logic (kiosk + customer)
│   │   └── chart.min.js            # local copy of Chart.js (no CDN)
│   └── images/
│       └── logo.png
│
├── includes/
│   ├── config.php                  # loads config.json + PDO
│   ├── auth.php                    # session, require_login, require_role, status enforcement
│   ├── csrf.php                    # csrf_token(), csrf_check()
│   ├── aes.php                     # aes_encrypt / aes_decrypt
│   ├── logger.php                  # log_info / log_warning / log_error
│   ├── helpers.php                 # sanitize, format_price, generate_order_code, generate_claim_pin
│   ├── settings.php                # get_setting / set_setting (cached)
│   ├── layout_header.php           # navbar + sidebar partial
│   ├── layout_footer.php           # footer + global JS includes
│   └── profile_content.php         # shared profile form (used by all role profile pages)
│
├── api/                            # AJAX endpoints (POST, JSON, CSRF-checked)
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── orders/
│   │   ├── create.php              # validates cart, decrements stock, generates PIN
│   │   ├── update_status.php       # ready/claimed (PIN-checked)
│   │   ├── cancel.php              # restores stock
│   │   └── delete.php              # admin only
│   ├── inventory/
│   │   ├── get_items.php           # paginated grid feed for kiosk/customer
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
│   ├── analytics/
│   │   └── get_data.php
│   ├── settings/
│   │   ├── update.php
│   │   ├── upload_logo.php
│   │   ├── add_category.php
│   │   ├── delete_category.php
│   │   ├── add_default_item_name.php
│   │   └── delete_default_item_name.php
│   └── profile/
│       ├── update.php
│       └── upload_avatar.php
│
├── admin/
│   ├── dashboard/
│   │   ├── dashboard.php
│   │   ├── dashboard.css
│   │   └── dashboard.js
│   ├── manage_orders/
│   │   ├── manage_orders.php
│   │   ├── manage_orders.css
│   │   ├── manage_orders.js
│   │   └── _claim_modal.php
│   ├── manage_users/
│   │   ├── manage_users.php
│   │   ├── manage_users.css
│   │   ├── manage_users.js
│   │   ├── _add_user_modal.php
│   │   ├── _edit_user_modal.php
│   │   └── _flag_user_modal.php
│   ├── pending_accounts/
│   │   ├── pending_accounts.php
│   │   ├── pending_accounts.css
│   │   └── pending_accounts.js
│   ├── flagged_users/
│   │   ├── flagged_users.php
│   │   ├── flagged_users.css
│   │   └── flagged_users.js
│   ├── inventory/
│   │   ├── inventory.php
│   │   ├── inventory.css
│   │   ├── inventory.js
│   │   ├── _add_item_modal.php
│   │   ├── _edit_item_modal.php
│   │   └── _add_stock_modal.php
│   ├── staff_statistics/
│   │   ├── staff_statistics.php
│   │   ├── staff_statistics.css
│   │   └── staff_statistics.js
│   ├── analytics/
│   │   ├── analytics.php
│   │   ├── analytics.css
│   │   └── analytics.js
│   ├── system_settings/
│   │   ├── system_settings.php
│   │   ├── system_settings.css
│   │   └── system_settings.js
│   └── profile/
│       ├── profile.php
│       ├── profile.css
│       └── profile.js
│
├── staff/
│   ├── dashboard/
│   │   ├── dashboard.php
│   │   ├── dashboard.css
│   │   └── dashboard.js
│   ├── manage_orders/
│   │   ├── manage_orders.php
│   │   ├── manage_orders.css
│   │   ├── manage_orders.js
│   │   └── _claim_modal.php
│   ├── pending_accounts/
│   │   ├── pending_accounts.php
│   │   ├── pending_accounts.css
│   │   └── pending_accounts.js
│   └── profile/
│       ├── profile.php
│       ├── profile.css
│       └── profile.js
│
├── customer/
│   ├── dashboard/
│   │   ├── dashboard.php
│   │   ├── dashboard.css
│   │   └── dashboard.js
│   ├── make_order/
│   │   ├── make_order.php
│   │   ├── make_order.css
│   │   └── make_order.js          # uses /assets/js/cart-drawer.js
│   ├── order_history/
│   │   ├── order_history.php
│   │   ├── order_history.css
│   │   └── order_history.js
│   └── profile/
│       ├── profile.php
│       ├── profile.css
│       └── profile.js
│
├── cron/
│   └── auto_logout.php             # closes stale staff sessions, marks suspicious
│
├── uploads/
│   ├── admin/profiles/
│   ├── staff/profiles/
│   ├── customer/profiles/
│   └── inventory/
│
└── logs/
    └── system.log
```

### URLs
- `/admin/dashboard/dashboard.php`, `/admin/inventory/inventory.php`, etc.
- Sidebar links use these full paths.
- Asset references inside each page: relative `./dashboard.css`, `./dashboard.js`.

---

## 10. `config.json` Structure

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
    "key": "CHANGE_THIS_KEY_BEFORE_DEPLOY",
    "iv":  "CHANGE_THIS_IV_16"
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

---

## 11. Security & Validation Rules

- **CSRF:** every POST (form or AJAX) carries `csrf_token` from session; server rejects mismatch.
- **PRG pattern:** all form submissions POST → handler → 302 redirect (no double-submit).
- **Input:** server-side sanitize+validate every field; never trust client.
- **Passwords:** AES-256-CBC encrypted; min length 8, must contain letter + digit. (Note: hashing with `password_hash` would be safer; AES is per project spec.)
- **File uploads:**
  - Avatars: `≤1 MB`, MIME-type sniffed (`finfo`), only `image/jpeg`, `image/png`, `image/webp`.
  - Item images: `≤2 MB`, same MIME types.
  - Stored under `uploads/{role}/profiles/` or `uploads/inventory/`.
  - Filename sanitized (`{user_id}.<ext>` for avatars, `item_<id>_<rand>.<ext>` for inventory).
  - Old image deleted on replace.
  - Periodic orphan cleanup script (called from `cron/`).
- **Direct access blocked** via `.htaccess` for `includes/`, `logs/`, `cron/`, `config.json`.
- **SQL:** all queries via PDO prepared statements.
- **Sessions:** regenerated on login + logout; idle timeout (default 8 h, configurable) handled by `cron/auto_logout.php` that scans `staff_sessions` and force-closes stale ones, marking `is_suspicious=1`, `logout_type='auto_system'`.
- **Rate limiting:** simple in-memory (DB) attempt counter for login (5 fails → 5-min lock per IP+email).

---

## 12. Logging
- File log `logs/system.log` (append-only) AND `system_logs` DB table.
- Events logged: login (success + fail), logout, register, approve/flag/unflag, user CRUD, item CRUD, stock change, settings change, order lifecycle (place/ready/claim/cancel/delete), claim PIN failures, file upload errors, every PHP error/exception.
- `log_info`, `log_warning`, `log_error` helpers.

---

## 13. Sidebar Badges
- PHP queries on every page load:
  - new orders today (admin/staff)
  - pending accounts (admin/staff)
  - active orders (customer)
- JS refresh: `setInterval(30s)` calls `/api/badges.php` (lightweight) to update without reload.

---

## 14. Setup Script (`setup.php`)

One-click setup for development:
1. Read `config.json`, connect.
2. Drop and recreate database `azeu_en_school_supplies`.
3. Create all tables in order.
4. Seed:
   - admin user (`admin@en.com` / `admin123`, AES-encrypted)
   - default item categories (e.g. Notebooks, Pens, Bags, Art)
   - default item names (e.g. "Spiral Notebook", "Ballpen")
   - system_settings rows (store name/phone/email defaults, status='online', force_dark=0, etc.)
5. Verify and print success page.
6. **Reminder banner:** "Delete `setup.php` before going live."

---

## 15. .htaccess (root)

- `Options -Indexes`
- `ErrorDocument 403 /403.php`
- `ErrorDocument 404 /404.php`
- Deny `<Files config.json>`, `<FilesMatch "\.log$">`
- Deny direct access to `includes/`, `logs/`, `cron/`

---

## 16. Implementation Phases

| Phase | Deliverable |
|---|---|
| 1 | Folder skeleton, `config.json`, `config.php`, `aes.php`, `logger.php`, `helpers.php`, `csrf.php`, `auth.php`, `settings.php`, `database.sql`, `setup.php`, `.htaccess`, 403/404 |
| 2 | Global CSS (`global.css`, `components.css`, `layout.css`, `cart-drawer.css`, `print.css`); Global JS (`global.js`, `theme.js`, `layout.js`, `custom-select.js`, `pagination.js`, `modal.js`, `cart-drawer.js`); local Chart.js |
| 3 | `index.php`, `login.php`, `register.php`, `logout.php`, auth APIs |
| 4 | Shared layout partials (`layout_header.php`, `layout_footer.php`, `profile_content.php`) + role profile pages |
| 5 | `kiosk.php` full-screen + cart drawer + receipt + claim PIN |
| 6 | Admin: dashboard, manage_orders (with PIN claim modal), manage_users, pending_accounts, flagged_users |
| 7 | Admin: inventory, staff_statistics, analytics, system_settings |
| 8 | Staff: dashboard, manage_orders, pending_accounts |
| 9 | Customer: dashboard, make_order (cart drawer), order_history |
| 10 | Order lifecycle wiring: stock deduct/restore transactions, cancel flow, delete flow |
| 11 | Profile avatar upload + cleanup, sidebar badges, system status enforcement, rate limit |
| 12 | `cron/auto_logout.php`, final QA, polish, screenshots, README |

---

## 17. Open Items / Future
- Online payment (toggle exists; no implementation in v2).
- Email/SMS notifications when order is ready.
- Multi-branch / inventory locations.
- Switch from AES-encrypted passwords to `password_hash()` (recommended security upgrade).

---

## 18. Glossary (beginner-friendly)

- **PRG (Post/Redirect/Get):** after a form submit, the server redirects to a GET URL so refreshing the page doesn't resubmit.
- **CSRF token:** a random string the server gives you and expects back on every form submit, so attackers from other sites can't trick the user's browser into submitting on their behalf.
- **AES (`openssl_encrypt`):** symmetric encryption — same key encrypts & decrypts.
- **Transaction (DB):** a group of queries that either all succeed or all roll back. Used for stock + order create/cancel.
- **Snapshot fields** (e.g. `item_name_snapshot`, `unit_price`): copies of values at the moment the order was placed, so renaming/repricing later doesn't change historical orders.
- **Suspicious session:** a staff session that was force-closed by the auto-logout cron because the staff didn't manually log out within the threshold; counted against their performance.
