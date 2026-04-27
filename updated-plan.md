# E&N School Supplies Web System — Updated Project Plan v3

This document is the current source-of-truth plan for the project after the latest implementation work.
It supersedes the earlier plan notes and reflects the actual codebase behavior, file structure, database design, shared shell layout, and the newer logo / username / system settings changes.

---

## 1. Project Overview

E&N School Supplies is a vanilla PHP / MySQL / JavaScript / CSS ordering and management system for school supplies.
It supports four runtime experiences:

1. Public visitor experience for browsing the landing page and logging in.
2. Customer experience for placing orders, viewing order history, and managing the user profile.
3. Staff experience for processing orders, approving pending accounts, and viewing profile information.
4. Admin experience for managing users, inventory, settings, analytics, staff statistics, and system controls.

The application also includes an in-store kiosk mode for guest ordering on a large touch display.

The app is structured around a shared shell with a top navbar and role-aware sidebar, plus per-page CSS and JS files for feature-specific behavior.

---

## 2. Goals of the Current Version

The current implementation aims to provide:

- A clean multi-role ordering workflow.
- A consistent shared layout across dashboard pages.
- A system settings panel that can control branding, status, and operational behavior.
- A safer order lifecycle with claim verification and stock restoration.
- A simpler authentication model using username or email login.
- A more usable installer and a more visual admin branding flow.

The recent changes focused on:

- Moving the website logo to the navbar.
- Removing the sidebar profile shortcut and keeping profile access in the navbar dropdown only.
- Adding a real country flag image in the navbar using the bundled flag pack.
- Adding system logo upload in System Settings.
- Adding helper descriptions to critical system settings.
- Making the sidebar work better on mobile as an off-canvas drawer.
- Allowing login by username or email.
- Improving the setup workflow and its output documentation.

---

## 3. Technology Stack

### 3.1 Backend

- Vanilla PHP 8+
- PDO for MySQL access
- Native PHP sessions
- AES encryption helpers for stored passwords
- CSRF token protection for all mutations
- File-based and database-based logging

### 3.2 Frontend

- Vanilla HTML5
- Vanilla CSS3
- Vanilla JavaScript (ES6+)
- No framework or build step
- Page-specific CSS and JS files per feature folder

### 3.3 Database

- MySQL / MariaDB
- Database name: `azeu_en_school_supplies`
- Schema source: `database.sql`
- Settings persisted in a key/value table

### 3.4 Server / Deployment

- Apache / XAMPP style local deployment
- `.htaccess` used for rewrite and access protection
- PHP sessions configured with `httponly` and `samesite=Lax`

---

## 4. Design System

The UI uses a green school-oriented theme with light and dark variants.

| Token | Value |
|---|---|
| Primary | `#2e7d32` |
| Primary Light | `#4caf50` |
| Accent | `#81c784` |
| Surface Light | `#ffffff` |
| Surface Dark | `#1e2a1e` |
| Background Light | `#f1f8f1` |
| Background Dark | `#121a12` |
| Text Light | `#1b1b1b` |
| Text Dark | `#f0f0f0` |
| Border Light | `#c8e6c9` |
| Border Dark | `#2e4a2e` |
| Danger | `#d32f2f` |
| Warning | `#f9a825` |
| Success | `#2e7d32` |

### 4.1 Theme Rules

- Default theme follows OS preference unless a user preference exists.
- User theme preference is stored in the database.
- Admin can force dark mode from System Settings.
- The theme toggle reflects the active mode.

### 4.2 Shared UI Principles

- Buttons, cards, tables, modals, badges, and toasts are reused across pages.
- The navbar, sidebar, and profile chip are shared through layout partials.
- Mobile screens use a drawer-based sidebar instead of a cramped rail.

---

## 5. Roles and Access Control

### 5.1 Roles

| Role | Created By | Access |
|---|---|---|
| Admin | Seeded during setup | Full access to the system |
| Staff | Admin only | Orders, pending accounts, profile |
| Customer | Self-register or admin-create | Order, history, profile |

### 5.2 Account Statuses

| Status | Meaning |
|---|---|
| active | Account can log in normally |
| pending | Account awaits approval |
| flagged | Account is blocked until unflagged |

### 5.3 Flagging Rules

- Only admins can flag and unflag users.
- Flagged accounts are blocked from login.
- Flag reason is stored and displayed in the flagged users module.
- Admin accounts cannot be flagged.

### 5.4 System Status Rules

| Status | Guest | Customer | Staff | Admin |
|---|---|---|---|---|
| online | Full access | Full access | Full access | Full access |
| maintenance | Landing page visible; login available | View only / no orders | Orders disabled | Full access |
| offline | Landing page visible; login available | Login blocked | Login blocked | Full access |

The status is read from `system_settings` and enforced through shared auth logic.

### 5.5 Authentication Behavior

- Login accepts either username or email.
- Login attempts are rate-limited.
- Wrong-role access returns a 403 page.
- Direct page access to protected areas is blocked unless the session is valid.

---

## 6. Shared Layout and Navigation

The entire authenticated area uses a shared layout partial system.

### 6.1 Navbar

The navbar currently contains:

- Website logo.
- Store name.
- Current date and time.
- Configurable country flag image.
- System status badge with friendly text.
- Theme toggle.
- Profile chip with dropdown.

### 6.2 Sidebar

The sidebar is role-aware and contains navigation links relevant to the current role.

Current behavior:

- Desktop: sidebar collapses between icon-only and expanded states.
- Mobile: sidebar behaves like a proper off-canvas drawer.
- The sidebar profile link has been removed.
- Profile access is now available only from the navbar profile chip dropdown.

### 6.3 Sidebar Behavior

- Hamburger is shown in the sidebar brand on desktop.
- A mobile toggle is shown in the navbar on smaller screens.
- Clicking a sidebar page link closes the drawer.
- The open state uses a backdrop and blur overlay.

### 6.4 Branding Behavior

- The website logo is shown in the navbar only.
- The sidebar stays cleaner and text-only.
- Country flags are rendered using the bundled image pack under `assets/images/country_flags/`.

---

## 7. Public Pages

### 7.1 Landing Page (`index.php`)

Purpose:

- Public-facing marketing and entry page.

Functional behavior:

- Shows store branding and hero content.
- Displays featured or in-stock items.
- Shows store contact details in the footer.
- Provides entry links to login and kiosk ordering.

### 7.2 Login Page (`login.php`)

Purpose:

- Primary access point for authenticated users.

Functional behavior:

- Accepts username or email plus password.
- Blocks pending and flagged accounts.
- Honors system status rules.
- Uses PRG behavior after submit.

### 7.3 Register Page (`register.php`)

Purpose:

- Self-service customer registration.

Functional behavior:

- Creates customer accounts as pending by default.
- Validates required profile fields.
- Stores username for login use.

### 7.4 Logout (`logout.php`)

Purpose:

- Ends the current session safely.

Functional behavior:

- POST-only.
- CSRF-protected.
- Records session logout type and duration.

### 7.5 Kiosk (`kiosk.php`)

Purpose:

- Touch-friendly in-store ordering experience.

Functional behavior:

- Full-screen and public.
- No dashboard sidebar or normal navbar controls.
- Uses the shared cart drawer workflow.
- Supports guest ordering when disabled-login orders are allowed.
- Shows a receipt modal after checkout.

---

## 8. Admin Pages

### 8.1 Dashboard (`admin/dashboard/dashboard.php`)

Purpose:

- Quick operational overview for admins.

Features:

- Greeting with current user name.
- Quick actions.
- Today’s orders and revenue summary.
- Pending counts.
- Recent orders table.

### 8.2 Manage Orders (`admin/manage_orders/manage_orders.php`)

Purpose:

- Review and process all orders.

Features:

- Filter by status.
- Search by order code.
- Expand order items.
- Mark ready.
- Claim verification with 4-digit PIN.
- Cancel and delete actions depending on status and role.

### 8.3 Manage Users (`admin/manage_users/manage_users.php`)

Purpose:

- Admin CRUD for users.

Features:

- Search and filtering.
- Add staff/customer.
- Edit user profile fields including username.
- Delete user.
- Flag user with a reason.

### 8.4 Pending Accounts (`admin/pending_accounts/pending_accounts.php`)

Purpose:

- Review self-registered accounts waiting for approval.

Features:

- Approve or delete pending accounts.
- Sidebar badge count.

### 8.5 Flagged Users (`admin/flagged_users/flagged_users.php`)

Purpose:

- Review and manage blocked accounts.

Features:

- View flag reason.
- Unflag or delete user.

### 8.6 Inventory (`admin/inventory/inventory.php`)

Purpose:

- Maintain products, stock, categories, and pricing.

Features:

- Search/filter items.
- Add, edit, delete items.
- Add stock.
- Upload item images.
- Auto-compute stock status.

### 8.7 Staff Statistics (`admin/staff_statistics/staff_statistics.php`)

Purpose:

- Show staff session and login metrics.

Features:

- Total logins.
- Total time spent.
- Average session length.
- Suspicious session count.
- Login activity charts.

### 8.8 Analytics (`admin/analytics/analytics.php`)

Purpose:

- Visual business analysis.

Features:

- Orders over time.
- Revenue over time.
- Top items.
- Orders by status.
- Orders by category.
- New customer trends.

### 8.9 System Settings (`admin/system_settings/system_settings.php`)

Purpose:

- Global store, branding, and system configuration.

Current features:

- Website logo upload (PNG only, max 2 MB).
- Store name.
- Store phone.
- Store email.
- Timezone.
- Country selection.
- System status.
- Force dark mode.
- Disable no-login orders.
- Online payment toggle.
- Auto-logout threshold.
- Low stock threshold.
- Kiosk idle timeout.

The most important settings include helper descriptions so admins understand their impact.

### 8.10 Profile (`admin/profile/profile.php`)

Purpose:

- Edit the current admin profile.

Features:

- Full name.
- Email.
- Phone.
- Username.
- Password change.
- Avatar upload.

---

## 9. Staff Pages

### 9.1 Dashboard

Purpose:

- Staff landing page with quick actions and current workload previews.

### 9.2 Manage Orders

Purpose:

- Staff processing of orders.

Features are aligned with admin order processing, minus admin-only destructive actions.

### 9.3 Pending Accounts

Purpose:

- Staff approval queue for pending customer accounts.

### 9.4 Profile

Purpose:

- Update staff profile and credentials.

---

## 10. Customer Pages

### 10.1 Dashboard

Purpose:

- Customer home page with order shortcuts and status summary.

### 10.2 Make Order

Purpose:

- Browse catalog and place orders with the shared cart drawer.

Features:

- Item grid.
- Quantity stepper.
- Cart drawer.
- Confirm modal.
- Receipt modal.

### 10.3 Order History

Purpose:

- View and manage past orders.

Features:

- Order table.
- Status badges.
- Cancel action for eligible orders.

### 10.4 Profile

Purpose:

- Customer profile management.

---

## 11. Shared Components and Utilities

### 11.1 Cart Drawer

Used in:

- Customer ordering.
- Kiosk ordering.

Capabilities:

- Add/remove items.
- Quantity control.
- Total calculation.
- Validation against stock and maximum order quantity.

### 11.2 Receipt Page

Purpose:

- Print-friendly order receipt.

Contents:

- Store logo and name.
- Order code.
- Claim PIN.
- Itemized breakdown.
- Totals.

### 11.3 Global Toasts and Modals

- Errors and confirmations are surfaced consistently.
- AJAX responses use shared helper methods.

### 11.4 Theme Toggle

- Swaps between moon and sun icon based on the active mode.
- Respects force dark mode.

### 11.5 Profile Chip Dropdown

- Contains Profile and Logout.
- The sidebar no longer duplicates the profile entry.

---

## 12. Order Lifecycle

### 12.1 Status Flow

`pending -> ready -> claimed`

`pending -> cancelled`

`ready -> cancelled`

### 12.2 Claim Verification

- Every order gets a 4-digit PIN.
- Staff must enter the correct PIN to claim the order.
- Wrong PIN is rejected and logged.

### 12.3 Stock Policy

- Stock is deducted when the order is placed.
- Stock is restored if the order is cancelled.
- Claiming does not change stock.
- Deletion behavior depends on status.

### 12.4 Guest Orders

- Allowed only when no-login orders are enabled.
- Guest orders use name, phone, and optional note.

---

## 13. Database Design

### 13.1 `users`

Purpose:

- Stores all user accounts and login identity.

Important fields:

- `username` for login and display.
- `email` for login and notifications.
- `role` for access control.
- `status` for account state.
- `flag_reason` for moderation.
- `profile_image` for avatars.
- `theme_preference` for appearance persistence.

### 13.2 `orders`

Purpose:

- Stores order header records.

Important fields:

- `order_code`
- `user_id`
- `guest_name`
- `guest_phone`
- `guest_note`
- `status`
- `total_price`
- `claim_pin`
- `processed_by`

### 13.3 `order_items`

Purpose:

- Stores the item snapshot of each order.

### 13.4 `inventory`

Purpose:

- Stores catalog items.

Important fields:

- `item_name`
- `category_id`
- `price`
- `stock_count`
- `max_order_qty`
- `item_image`

### 13.5 `item_categories`

Purpose:

- Stores inventory categories.

### 13.6 `default_item_names`

Purpose:

- Stores predefined item names for easier inventory entry.

### 13.7 `staff_sessions`

Purpose:

- Tracks staff login/logout lifecycle and suspicious sessions.

Important fields:

- `login_time`
- `logout_time`
- `logout_type`
- `duration_minutes`
- `is_suspicious`

### 13.8 `system_settings`

Purpose:

- Key/value configuration storage for the whole system.

Examples:

- Store name.
- Store phone.
- Store email.
- Logo path.
- Timezone.
- Country.
- System status.
- Force dark mode.
- Auto-logout hours.
- Low stock threshold.
- Kiosk timeout.

### 13.9 `system_logs`

Purpose:

- Audit and diagnostic logging.

### 13.10 `login_attempts`

Purpose:

- Tracks login throttling and attempts.

---

## 14. API Structure

### 14.1 Authentication

- `api/auth/login.php` — username/email login
- `api/auth/register.php` — customer registration

### 14.2 Orders

- `api/orders/create.php` — create order, validate cart, decrement stock, generate claim PIN
- `api/orders/update_status.php` — ready/claimed flow
- `api/orders/cancel.php` — restore stock on cancel
- `api/orders/delete.php` — admin delete

### 14.3 Inventory

- `api/inventory/get_items.php`
- `api/inventory/add_item.php`
- `api/inventory/edit_item.php`
- `api/inventory/delete_item.php`
- `api/inventory/add_stock.php`

### 14.4 Users

- `api/users/add_user.php`
- `api/users/edit_user.php`
- `api/users/delete_user.php`
- `api/users/flag_user.php`
- `api/users/unflag_user.php`
- `api/users/approve_user.php`

### 14.5 Settings

- `api/settings/update.php`
- `api/settings/upload_logo.php` (documented intent; current logo flow is handled in the settings update flow)

### 14.6 Profile

- `api/profile/update.php`
- `api/profile/upload_avatar.php`

### 14.7 Analytics / Badges

- `api/analytics/get_data.php`
- `api/badges.php`

---

## 15. File and Folder Structure

The project uses a per-page folder structure where each page has its own PHP, CSS, and JS files.

### 15.1 Root files

- `config.json` — database, AES, and system defaults
- `database.sql` — schema source
- `setup.php` — one-click installer / seeder
- `index.php` — landing page
- `login.php` — login page
- `register.php` — registration page
- `logout.php` — logout handler
- `kiosk.php` — kiosk entry page
- `receipt.php` — printable receipt page
- `403.php` / `404.php` — error pages

### 15.2 Shared assets

- `assets/css/global.css`
- `assets/css/components.css`
- `assets/css/layout.css`
- `assets/css/cart-drawer.css`
- `assets/css/kiosk.css`
- `assets/css/print.css`
- `assets/js/global.js`
- `assets/js/theme.js`
- `assets/js/layout.js`
- `assets/js/modal.js`
- `assets/js/pagination.js`
- `assets/js/custom-select.js`
- `assets/js/cart-drawer.js`

### 15.3 Shared includes

- `includes/config.php`
- `includes/auth.php`
- `includes/csrf.php`
- `includes/aes.php`
- `includes/logger.php`
- `includes/helpers.php`
- `includes/settings.php`
- `includes/layout_header.php`
- `includes/layout_footer.php`
- `includes/profile_content.php`

### 15.4 API folders

- `api/auth/`
- `api/orders/`
- `api/inventory/`
- `api/users/`
- `api/settings/`
- `api/profile/`
- `api/analytics/`

### 15.5 Role folders

- `admin/`
- `staff/`
- `customer/`

Each page folder contains:

- `<page>.php`
- `<page>.css`
- `<page>.js`
- Optional page-only modals or partials

### 15.6 Uploads and logs

- `uploads/admin/profiles/`
- `uploads/staff/profiles/`
- `uploads/customer/profiles/`
- `uploads/inventory/`
- `uploads/system/logo.png`
- `logs/system.log`

### 15.7 Country flag assets

- `assets/images/country_flags/` contains PNG flag icons used in the navbar.

---

## 16. Setup Flow

`setup.php` is a one-time database bootstrapper.

### What it does

- Drops and recreates the database.
- Recreates tables from `database.sql`.
- Seeds system settings.
- Seeds categories, default item names, users, inventory, and optional sample orders.
- Creates the data used by the initial demo environment.

### Current setup behavior

- Uses PRG behavior to avoid repeat submissions on refresh.
- Stores setup feedback in session flash.
- Renders result messages above the form.
- Default seed data now focuses on admin plus a small starter dataset.
- Setup is visually aligned with the rest of the site.

### Important setup defaults

- Admin account is seeded.
- Country flag default is set.
- Logo path default exists in config, but the logo can now be uploaded later from System Settings.

---

## 17. System Settings Details

The settings page now includes descriptions for the most important values.

### Branding and identity

- Logo upload: PNG only, max 2 MB.
- Store name: appears in navbar, sidebar, login page, and receipts.
- Store phone: used in contact surfaces and flagged-user instructions.
- Store email: used in contact surfaces.
- Timezone: controls displayed timestamps.
- Country: chooses the icon shown beside the clock.

### Operational controls

- System status: controls access behavior.
- Force dark mode: forces dark theme for all users.
- Disable no-login orders: blocks guest/kiosk ordering.
- Online payment: placeholder toggle for future payment features.
- Auto-logout hours: closes stale staff sessions automatically.
- Low stock threshold: marks inventory as low stock when under the threshold.
- Kiosk idle timeout: resets the kiosk after inactivity.

---

## 18. Current Implementation Notes

These are not just planned behaviors; they match the current codebase state.

- The navbar contains the logo, country flag image, system status, theme toggle, and profile dropdown.
- The sidebar is role-aware and no longer includes a profile shortcut.
- The mobile sidebar now opens as a drawer instead of a tiny collapsed rail.
- The theme toggle swaps between moon and sun icons based on the active mode.
- The profile chip has a visible dropdown cue.
- Username and email are both valid login identifiers.
- Admin can upload a PNG logo from System Settings, and that logo becomes the navbar logo.
- The current logo file path is stored in `system_settings`.

---

## 19. Known Follow-Up Ideas

Possible next improvements after this plan:

1. Add a visible logo reset-to-default action in System Settings.
2. Add preview/reposition controls for the logo upload.
3. Continue refining mobile spacing on dashboard and analytics pages.
4. Expand helper descriptions if new settings are added later.
5. Add a dedicated admin audit page for settings and branding changes.

---

## 20. Final Notes

- This plan reflects the current behavior of the application, not only the original design intent.
- The project remains framework-free and intentionally simple to deploy on XAMPP / Apache.
- Future changes should keep shared layout, settings, and upload rules consistent across all roles.