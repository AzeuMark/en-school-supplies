# E&N School Supplies Web System — Full Project Plan

A fully planned vanilla PHP/JS/HTML/CSS school supplies ordering and management system for E&N School Supplies, supporting Admin, Staff, and Customer roles with an in-store kiosk mode.

---

## Tech Stack

- **Backend:** Vanilla PHP (no frameworks)
- **Frontend:** Vanilla HTML, CSS, JavaScript (no frameworks)
- **Database:** MySQL via XAMPP
- **PDF:** PHP FPDF library (lightweight, no Composer needed)
- **Encryption:** AES-128/256 via PHP `openssl_encrypt/decrypt` (passwords only)
- **DB Name:** `azeu_en_school_supplies`
- **Config:** `config.json` (DB credentials, AES key, system defaults)

---

## Design System

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
| Default theme | OS preference (`prefers-color-scheme`) + saved per user in DB |
| Force Dark Mode | Admin toggle in System Settings (overrides all) |

**Custom select UI** uses the provided CSS snippet (adapted to green theme variables).

---

## Roles & Access Control

| Role | Created By | Approval | Access Level |
|---|---|---|---|
| Admin | Built-in (`admin@en.com` / `admin123`) | N/A | Full system access |
| Staff | Admin only | Auto-approved | Orders + Pending Accounts |
| Customer | Self-register (pending) OR Admin (auto-approved) | Admin/Staff approval required (if self-registered) | Order + history |

### Flagged Users
- Flagged by Admin only
- Blocked from login immediately
- Shown: *"Your account has been flagged. Please contact us at [store phone] or visit the store."*
- Admin can unflag or permanently delete from Manage Flagged Users

### System Status Effects
| Status | Guest | Customer | Staff | Admin |
|---|---|---|---|---|
| Online | Full access | Full access | Full access | Full access |
| Offline | Landing + Login only | Login blocked | Login blocked | Full access |
| Maintenance | Landing + Login only | View only, no orders | Orders disabled | Full access |

---

## Pages & Features

### Shared (All Roles)
- **Navbar:** Logo, store name, hamburger (collapse sidebar), current date/time + PH flag, dark/light toggle, system status badge, profile section (name, role, avatar → dropdown: Profile / Logout)
- **Sidebar:** Collapsible; sidebar items have badge counts for new orders, pending accounts
- **Profile Page:** Edit full name, email, phone, password; upload custom avatar (max 1MB, stored at `uploads/{role}/profiles/{user_id}.jpg`)
- **Error Display:** Global toast/inline error shown on every page; all errors also logged to `logs/system.log`

---

### Landing Page (`index.php`)
- Hero section: logo, tagline, "Order Now" button (→ kiosk page), "Login" button
- Featured Items section: card preview of in-stock items (pulled from DB, top 8)
- Footer: store name, phone, email (from system settings), system status
- **No sidebar/dashboard nav** — public-facing only

---

### Login Page (`login.php`)
- Email + Password form
- On submit: POST/Redirect/GET pattern (prevents double-submit on refresh)
- Checks: flagged status → show flag message; pending status → show waiting message; wrong credentials → show error
- Redirects to role dashboard on success

### Register Page (`register.php`)
- Fields: Full Name, Email, Phone Number, Password, Confirm Password
- Submitted accounts → status = `pending`
- Admin-created accounts → status = `active` (bypass)

---

### Kiosk Page (`kiosk.php`) — In-Store Monitor
- **Full-screen layout** — no header navbar, no sidebar, no links back to main site
- Item cards (full-screen grid, large text, large cards)
- 20 items per page with pagination
- Selectable cards (quantity input per item, highlight if qty > 0)
- "Place Order" button → confirm modal (list selected items, X to remove)
- Confirm → collect: **Name** (required), **Phone** (required), **Note** (optional)
- Post-order: receipt modal with PDF download + Print options
- If "Disable No-login Orders" is ON in settings → show disabled message on kiosk

---

### Admin Pages (`admin/`)

#### Dashboard (`dashboard.php`)
- Greeting: "Welcome back, [Name]!"
- Quick action cards linking to all sidebar pages
- Mini analytics preview: total orders today, total revenue, pending accounts count
- Recent orders mini-table (latest 5)

#### Manage Orders (`manage_orders.php`)
- Table columns: Order ID, Ordered By (name + role/guest), Order Date, Items (expandable), Category, Total Price, Status, Actions
- Status flow: **Pending → Ready → Claimed** (+ Cancelled)
- Actions: Mark as Ready / Mark as Claimed / Remove Order (icon buttons)
- Staff can present Order ID to trigger "Claim" confirmation modal
- 15 orders per page, pagination bar
- Filter by status (tabs or dropdown)

#### Manage Users (`manage_users.php`)
- Table columns: User ID, Full Name, Email, Phone, Role, Status, Actions
- Actions: Edit (modal), Delete (confirm), Flag (modal with reason input)
- "Add User" button above table → modal form (role selector: Staff/Customer)
- Admin-created customers → auto-approved

#### Manage Pending Accounts (`pending_accounts.php`)
- Table columns: User ID, Full Name, Email, Phone, Registered At, Actions
- Actions: Approve, Delete (icon buttons)
- Badge count on sidebar item

#### Manage Flagged Users (`flagged_users.php`)
- Table columns: User ID, Full Name, Email, Role, Flag Reason (expandable modal if long), Flagged At, Actions
- Actions: Remove Flag, Delete User

#### Inventory (`inventory.php`)
- Table columns: Item Image, Item ID, Item Name, Category, Price, Stock Count, Max Order Qty, Item Status, Actions
- Item Status auto-computed: `No Stock` (0), `Low Stock` (≤10% of max or configurable threshold), `On Stock`
- Actions: Edit (modal), Delete (confirm), Add Stock (modal with qty input)
- "Add Item" button above table → modal with:
  - Item Name: dropdown (from System Settings default names) + "Custom" option
  - Category: dropdown (from System Settings default categories)
  - Price, Stock Count, Max Order Qty, Item Image upload
- Filter/search by name, category, status

#### Staff Statistics (`staff_statistics.php`)
- Table per staff: Name, Total Logins, Total Time Spent (hrs), Last Login, Avg Session Duration, Suspicious Sessions count
- Suspicious session = auto-logged out by system (no manual logout within threshold)
- Threshold configurable in System Settings (default 8 hrs)
- Suspicious sessions flagged red and noted: *"Auto-logout by system — deducted from performance"*
- Charts: Top 5 most active staff (bar chart), Login frequency over time (line chart)

#### Analytics (`analytics.php`)
- Charts/metrics:
  - Total orders over time (line chart)
  - Revenue over time (line chart)
  - Top selling items (bar chart)
  - Orders by status breakdown (pie/donut)
  - Orders by category (bar chart)
  - New customers over time
  - No-login vs registered orders ratio
- Date range filter (today, week, month, custom)

#### System Settings (`system_settings.php`)
- **System Logo:** Upload field → saves to `assets/images/logo.png`
- **Store Name:** Text input
- **Store Phone:** Text input (shown to flagged users, on landing page footer)
- **Store Email:** Text input
- **Force Dark Mode:** Toggle switch
- **System Time & Region:** Dropdown (PHP timezones)
- **Default Item Categories:** Tag/list manager (add, remove, reorder) — populates category dropdown in inventory
- **Default Item Names:** Tag/list manager — populates item name dropdown in inventory
- **Disable No-Login Orders:** Toggle (disables kiosk ordering)
- **Online Payment:** Toggle (disabled by default, placeholder for future)
- **System Status:** Dropdown (Online / Offline / Maintenance)
- **Staff Auto-Logout Threshold:** Number input (hours, default 8)
- All settings persisted in `system_settings` DB table

---

### Staff Pages (`staff/`)

#### Dashboard (`dashboard.php`)
- Same greeting + quick action cards for Staff pages
- Mini preview of pending orders + pending accounts

#### Manage Orders (`manage_orders.php`)
- Identical functionality to Admin's Manage Orders page
- Staff can move orders: Pending → Ready → Claimed
- Staff can also remove orders

#### Manage Pending Accounts (`pending_accounts.php`)
- Identical to Admin's Manage Pending Accounts

---

### Customer Pages (`customer/`)

#### Dashboard (`dashboard.php`)
- Greeting + quick links to Make Order and Order History
- Summary: active orders count, last order date

#### Make Order (`make_order.php`)
- Item cards grid (20/page, pagination)
- Each card: item image, name, category, price, stock count, quantity stepper (0 to max_order_qty)
- Cards with qty > 0 are visually highlighted/selected
- Multiple items selectable simultaneously
- "Place Order" button → confirm modal:
  - List of selected items with qty and subtotal
  - X to remove individual items
  - Total price shown
  - "Confirm" and "Close" buttons
  - Double-click prevention on Confirm
- After confirm → receipt modal (PDF download + Print)
- Items with `No Stock` status are disabled/greyed

#### Order History (`order_history.php`)
- Table: Order ID, Items (expandable), Total, Status, Order Date, Actions
- Status badges: Pending (yellow), Ready (blue), Claimed (green), Cancelled (red)
- Actions: Cancel (only if status is Pending or Ready, opens confirm modal)
- 15 orders per page, pagination

---

## Order Receipt Contents
- Store name + logo
- Order ID
- Date & time
- Customer name (or "Guest: [name]")
- Items table: name, qty, unit price, subtotal
- Grand total
- Status
- Footer: "Thank you for shopping at E&N School Supplies!"
- **Print-friendly HTML** (browser print dialog) — no FPDF dependency needed

---

## Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| full_name | VARCHAR(150) | |
| email | VARCHAR(150) UNIQUE | |
| phone | VARCHAR(20) | |
| password | TEXT | AES encrypted |
| role | ENUM('admin','staff','customer') | |
| status | ENUM('active','pending','flagged') | |
| flag_reason | TEXT NULL | |
| profile_image | VARCHAR(255) NULL | relative path |
| theme_preference | ENUM('light','dark','auto') | DEFAULT 'auto' |
| created_by | INT NULL | FK → users.id |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### `orders`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| order_code | VARCHAR(20) UNIQUE | display ID e.g. ORD-00042 |
| user_id | INT NULL | FK → users; NULL for guest |
| guest_name | VARCHAR(150) NULL | for no-login orders |
| guest_phone | VARCHAR(20) NULL | |
| guest_note | TEXT NULL | |
| status | ENUM('pending','ready','claimed','cancelled') | |
| total_price | DECIMAL(10,2) | |
| processed_by | INT NULL | FK → users (staff/admin) |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### `order_items`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| order_id | INT | FK → orders |
| item_id | INT | FK → inventory |
| item_name_snapshot | VARCHAR(150) | name at time of order |
| quantity | INT | |
| unit_price | DECIMAL(10,2) | price at time of order |

### `inventory`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| item_name | VARCHAR(150) | |
| category_id | INT NULL | FK → item_categories |
| price | DECIMAL(10,2) | |
| stock_count | INT | |
| max_order_qty | INT | per-item per-order limit |
| item_image | VARCHAR(255) NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### `item_categories`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| category_name | VARCHAR(100) UNIQUE | |
| created_at | TIMESTAMP | |

### `default_item_names`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| item_name | VARCHAR(150) UNIQUE | used in inventory add dropdown |

### `staff_sessions`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| user_id | INT | FK → users |
| login_time | TIMESTAMP | |
| logout_time | TIMESTAMP NULL | |
| logout_type | ENUM('manual','auto_system') | |
| duration_minutes | INT NULL | calculated on logout |
| is_suspicious | TINYINT(1) | 1 = auto-logged by system |

### `system_settings`
| Column | Type | Notes |
|---|---|---|
| setting_key | VARCHAR(100) PK | |
| setting_value | TEXT | |
| updated_at | TIMESTAMP | |

### `system_logs`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT PK | |
| level | ENUM('info','warning','error') | |
| message | TEXT | |
| context | JSON NULL | extra data |
| user_id | INT NULL | who triggered it |
| ip_address | VARCHAR(45) NULL | |
| created_at | TIMESTAMP | |

---

## File & Folder Structure

```
en-school-supplies/
│
├── config.json                        # DB creds, AES key, system defaults
├── index.php                          # Public landing page
├── login.php                          # Login page
├── register.php                       # Customer registration page
├── kiosk.php                          # Full-screen in-store kiosk ordering
├── logout.php                         # Logout handler (POST only, PRG)
├── receipt.php                        # Receipt view (print-friendly HTML)
├── setup.php                          # One-click DB setup (delete after use)
├── database.sql                       # Full DB schema SQL file
├── .htaccess                          # Apache security & error docs
├── 403.php                            # Access denied error page
├── 404.php                            # Not found error page
│
├── assets/
│   ├── css/
│   │   ├── global.css                 # CSS variables, reset, dark/light theme
│   │   ├── components.css             # Reusable: buttons, modals, tables, cards
│   │   ├── sidebar.css                # Sidebar + navbar styles
│   │   ├── kiosk.css                  # Full-screen kiosk styles
│   │   └── pages/                     # Per-page specific styles
│   ├── js/
│   │   ├── global.js                  # Utilities, toast notifications, PRG guard
│   │   ├── theme.js                   # Dark/light mode toggle + OS detection
│   │   ├── sidebar.js                 # Hamburger + sidebar collapse
│   │   ├── custom-select.js           # Custom select dropdown component
│   │   ├── pagination.js              # Reusable pagination logic
│   │   ├── kiosk.js                   # Kiosk page interactions
│   │   └── pages/                     # Per-page specific JS
│   └── images/
│       └── logo.png                   # Store logo (user provides this file)
│
├── includes/
│   ├── config.php                     # Loads config.json, creates DB connection ($pdo)
│   ├── auth.php                       # Session start, role checks, redirect guards
│   │                                    NOTE: get_current_user_data() (not get_current_user — PHP built-in conflict)
│   ├── profile_content.php            # Shared profile edit form (included by all role profile pages)
│   ├── aes.php                        # aes_encrypt($str) / aes_decrypt($str)
│   ├── logger.php                     # log_info(), log_warning(), log_error()
│   ├── helpers.php                    # sanitize(), format_price(), generate_order_code()
│   ├── layout_header.php              # Navbar + sidebar HTML partial
│   └── layout_footer.php             # Footer HTML + JS includes partial
│
├── api/                               # AJAX endpoints (return JSON, POST only)
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── orders/
│   │   ├── create.php
│   │   ├── update_status.php
│   │   └── cancel.php
│   ├── inventory/
│   │   ├── get_items.php
│   │   ├── add_item.php
│   │   ├── edit_item.php
│   │   ├── delete_item.php
│   │   └── add_stock.php
│   ├── users/
│   │   ├── add_user.php
│   │   ├── edit_user.php
│   │   ├── delete_user.php
│   │   ├── flag_user.php
│   │   └── approve_user.php
│   ├── analytics/
│   │   └── get_data.php
│   ├── settings/
│   │   └── update.php
│   └── profile/
│       ├── update.php
│       └── upload_avatar.php
│
├── admin/
│   ├── dashboard.php
│   ├── manage_orders.php
│   ├── manage_users.php
│   ├── pending_accounts.php
│   ├── flagged_users.php
│   ├── inventory.php
│   ├── staff_statistics.php
│   ├── analytics.php
│   ├── system_settings.php
│   └── profile.php
│
├── staff/
│   ├── dashboard.php
│   ├── manage_orders.php
│   ├── pending_accounts.php
│   └── profile.php
│
├── customer/
│   ├── dashboard.php
│   ├── make_order.php
│   ├── order_history.php
│   └── profile.php
│
├── cron/
│   └── auto_logout.php                # Staff session auto-close (run via scheduler)
│
├── uploads/
│   ├── admin/profiles/                # Admin avatars: {user_id}.jpg
│   ├── staff/profiles/                # Staff avatars
│   ├── customer/profiles/             # Customer avatars
│   └── inventory/                     # Item images
│
└── logs/
    └── system.log                     # System activity log (append-only)
```

---

## `config.json` Structure

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
    "key": "CHANGE_THIS_KEY_BEFORE_DEPLOY",
    "iv": "CHANGE_THIS_IV_16"
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
    "auto_logout_hours": 8
  }
}
```

---

## Logo File Location

Place your logo file at:
```
/assets/images/logo.png
```
Accepted formats: `.png`, `.jpg`. The system settings page allows replacing it via upload.

---

## Key Technical Rules

- **PRG Pattern:** All form submissions POST → PHP handler → redirect. Prevents double-submit on refresh.
- **AES:** `openssl_encrypt` / `openssl_decrypt` with key from `config.json`. Applied to passwords only.
- **No-login Orders:** If "Disable No-Login Orders" is ON → kiosk shows disabled state.
- **Profile Images:** Max 1MB upload. On update, old image is deleted. A cleanup script checks `uploads/*/profiles/` against DB records and removes orphans.
- **Sidebar Badges:** PHP queries at page load inject counts (pending orders, pending accounts) into sidebar HTML.
- **Logging:** Every auth event, order event, settings change, error, and flagging action is written to `logs/system.log` AND inserted into `system_logs` DB table.
- **Error Display:** Every page has a `<div id="page-error">` shown when PHP sets an error session variable, plus JS toast for AJAX errors.
- **Receipt:** Pure HTML print-friendly page — no FPDF dependency. Browser print dialog handles PDF.
- **Setup:** `setup.php` provides one-click DB table creation with seed data. Delete after use.
- **Function Naming:** `get_current_user_data()` used instead of `get_current_user()` to avoid PHP built-in conflict.

---

## Implementation Phases

| Phase | What Gets Built | Status |
|---|---|---|
| 1 | DB schema, `config.json`, `config.php`, `aes.php`, `logger.php`, `helpers.php` | ✅ Done |
| 2 | Global CSS design system, component styles, dark/light mode JS | ✅ Done |
| 3 | Landing page, Login page, Register page, Auth API (`login.php`, `register.php`, `logout.php`) | ✅ Done |
| 4 | Shared layout (navbar, sidebar), profile page (all roles) | ✅ Done |
| 5 | Kiosk page (full-screen, no-login ordering flow) | ✅ Done |
| 6 | Admin — Dashboard, Manage Orders, Manage Users, Pending Accounts, Flagged Users | ✅ Done |
| 7 | Admin — Inventory, Staff Statistics, Analytics, System Settings | ✅ Done |
| 8 | Staff — Dashboard, Manage Orders, Manage Pending Accounts | ✅ Done |
| 9 | Customer — Dashboard, Make Order (cards), Order History | ✅ Done |
| 10 | Receipt system (HTML print), order cancellation, stock deduction logic | ✅ Done |
| 11 | Profile image upload/cleanup, badge counts, system status enforcement, `setup.php` | ✅ Done |
| 12 | Error pages (403/404), `.htaccess`, `cron/auto_logout.php`, final polish | ✅ Done |

---

## Implementation Notes & Changes

- **Admin credentials** changed from `admin`/`admin` to `admin@en.com`/`admin123`
- **`get_current_user()`** renamed to **`get_current_user_data()`** — PHP has a built-in `get_current_user()` that cannot be redeclared
- **FPDF removed** from plan — receipts use print-friendly HTML instead (zero dependencies)
- **`setup.php`** added for one-click database setup (drops + recreates all tables, seeds data, encrypts admin password)
- **`.htaccess`** added to block direct access to `includes/`, `logs/`, `cron/`, and `config.json`
- **Error pages** (`403.php`, `404.php`) added with themed styling
- **`cron/auto_logout.php`** added for staff session auto-close via system scheduler
- **Component CSS** expanded with: stat cards, action cards, toolbar, status tabs, search box, toggle switch, tag chips, badge variants
