# Sampai Kilat — Package Delivery Information System

**Sampai Kilat** is a courier delivery website built independently for the
**Secure Programming** course at BINUS University. The project
focuses on two things:

1. **Functionality** — a complete delivery business flow: package tracking,
   delivery creation, status updates, and a staff dashboard.
2. **Secure programming** — applying web application security principles on
   the server side (PHP) as well as configuration (MySQL, sessions, HTTP
   headers).

---

## The Website Story

Sampai Kilat simulates a local courier company with the tagline
*"Sampai Tujuan, Secepat Kilat!"* ("Arrive at Your Destination, Lightning
Fast!"). The website has two sides:

### Public Side (customers)

- **Homepage** — service profile: global delivery (air & land), COD,
  24-hour customer service.
- **Track a Package** — customers enter a tracking number in the
  `RS-0000000` format and see the package's latest status plus its travel
  history (which warehouses it has passed through).
- **Rate Check**, **Location Check**, **About Us**, **Help** — supporting
  service information pages.
- **Customer Registration** — a form to register as a sending customer.

### Internal Side (admin & warehouse staff)

Protected by login. Menu:

- **Dashboard** — a table of every delivery: tracking number, package
  contents, driver, shipping time, and the package's last known position.
- **Create Delivery** — a three-step form: sender data → receiver data →
  service & confirmation. It generates a new tracking number in the
  database.
- **Update Delivery** — staff update the package's position as it arrives
  at each next warehouse (WH Tangerang → WH Jakarta → ... → DELIVERED).
- **Delete** — only for the Admin role (`RL-001`); removes transit data.

### Database

Two separate databases, following the principle of data separation:

| Database                  | Contents                                             |
|---------------------------|------------------------------------------------------|
| `sampaikilat_account`     | Staff, roles, and login accounts                     |
| `sampaikilat_operational` | Tracking numbers, customers, packages, transit records, drivers, warehouse positions |

Full scripts are in `scriptDB/`, including seed data for testing.

---

## Secure Programming: What Was Applied

Every finding from our internal security audit has been addressed. A
summary of the security practices in this project:

### 1. SQL Injection — Fully Parameterized Queries

Every database query uses prepared statements (`mysqli` +
`bind_param`) — no user input is ever concatenated into SQL. Example:

```php
$stmt = $conn->prepare('SELECT username_staff, password_staff, id_role
                        FROM akun_staff WHERE username_staff = ? LIMIT 1');
$stmt->bind_param('s', $username);
```

### 2. Cross-Site Scripting (XSS) — Output Escaping

Every value from the database that gets rendered into HTML is escaped
with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` — including tracking
numbers, customer names, addresses, and staff usernames on the
dashboard.

### 3. Cross-Site Request Forgery (CSRF)

Authenticated state-changing forms (create, update, delete, customer registration, and logout)
use the **synchronizer token pattern**:

- A random 32-byte token (`random_bytes(32)`) is stored in the session.
- It is sent as a hidden input `<input type="hidden" name="csrf_token">`.
- The server verifies it with `hash_equals()` (a timing-safe comparison).
- Requests with a missing or invalid token are rejected with HTTP `419`.

### 4. Session Hardening

`controller/login/bootstrap.php`:

- Cookies are `HttpOnly` + `Secure` (automatically active under HTTPS) +
  `SameSite=Lax`.
- **Session regeneration** after a successful login
  (`session_regenerate_id(true)`) — prevents session fixation.
- Logout truly destroys the session: clears `$_SESSION`, expires the
  cookie, then calls `session_destroy()`.

### 5. Role-Based Access Control (RBAC)

- A `require_login()` helper forces every internal page to check the
  session; without a login the request gets HTTP `401`.
- The Admin (`RL-001`) vs Staff (`RL-002`) roles are checked with
  `hash_equals()`. The Delete button is only rendered for Admins, and
  `deleteData.php` re-validates the role server-side (hiding the button
  is never the security boundary).
- Internal static pages were moved to `.php` so they cannot be accessed
  without passing the session check.

### 6. HTTP Security Headers

Sent from `bootstrap.php` on every request:

```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; ... frame-ancestors 'none'
```

The CSP restricts script/style/image sources to the site's own origin and
forbids framing — mitigating clickjacking, MIME-sniffing, and referrer
leakage.

### 7. Input Validation & Defense in Depth

- Tracking numbers are validated with the regex `^RS-[0-9]{7}$` on the
  server (not just the HTML `pattern` attribute).
- Status updates use a **whitelist** of allowed warehouse names — users
  cannot push arbitrary status values into the database.
- Phone numbers and dates are validated with regex before entering a
  query.
- New tracking numbers are generated inside a transaction with
  `SELECT ... FOR UPDATE` to avoid race conditions / duplicate tracking
  numbers under concurrent requests.
- Database errors never leak to the browser; details go to `error_log()`
  and users only see a generic message plus HTTP `503`.

### 8. No Hardcoded Secrets

Database credentials are read from **environment variables**
(`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_ACCOUNT`, `DB_OPERATIONAL`) — never
planted in the source code. The `controller/` and `scriptDB/` folders must
not be exposed directly by the web server.

### Known Limitations (Kept on Purpose)

For academic transparency, this project still has:

- **Legacy layered-MD5 password hashing**
  (`md5(md5(md5($pass) . 'SampaiKilat'))`) — it follows the `CHAR(32)`
  schema that came with the course template. A modern implementation
  would use `password_hash()` / `password_verify()` with a
  `VARCHAR(255)` column. `hash_equals()` is still used so the hash
  comparison is timing-safe.
- Seed accounts use weak passwords (`password123`, etc.) — for demo
  purposes only, not production.
- No rate limiting on the login endpoint yet (only a `usleep(250ms)`
  delay to slow down brute force).

---

## Requirements

- **PHP 8.0+** (uses `declare(strict_types=1)`, the `mixed` type hint in
  `e()`)
- **MySQL / MariaDB 8+** (uses CHECK constraints + REGEXP)
- Web server: **Apache** (XAMPP/Laragon) or the **PHP built-in server**
- A modern browser

No Composer dependencies — pure native PHP + MySQL.

---

## How to Run

### 1. Prepare the Databases

Import both SQL scripts into MySQL (order does not matter since the
databases are separate):

```bash
mysql -u root -p < scriptDB/SampaiKilat-AccountDatabase.sql
mysql -u root -p < scriptDB/SampaiKilat-OperationalDatabase.sql
```

### 2. Set Environment Variables

```bash
export DB_HOST="127.0.0.1"
export DB_USER="root"
export DB_PASS="your_password"
export DB_ACCOUNT="sampaikilat_account"
export DB_OPERATIONAL="sampaikilat_operational"
```

> If they are not set, the app falls back to the local defaults
> `127.0.0.1` / `root` / empty — fine for a default XAMPP setup, but
> **required** for real deployment.

### 3. Start the Web Server

**Option A — XAMPP / Laragon:**
put the project folder in `htdocs/` (XAMPP) or Laragon's web root, then
start Apache + MySQL. Open
`http://localhost/Sampai Kilat/homepage.html`.

**Option B — PHP built-in server (quick test):**

```bash
cd "Sampai Kilat"
php -S localhost:8000
```

Then open `http://localhost:8000/homepage.html`.

### 4. Log In as Staff

Open the login page from the navbar. Seed accounts:

| Username       | Password      | Role  |
|----------------|---------------|-------|
| `andi.pratama` | `password123` | Admin |
| `budi.santoso` | `securepass`  | Staff |
| `citra.dewi`   | `mypassword`  | Staff |

### 5. Try the Main Flow

1. Log in → the dashboard shows the delivery table.
2. Click **Create** → fill in 3 steps → the new tracking number appears
   on the dashboard.
3. Click **Update** → pick a tracking number → move its status to the
   next warehouse.
4. Log out → open **Track a Package** on the homepage → enter the
   tracking number → the status is now publicly visible.

---

## Project Structure

```
Sampai Kilat/
|-- homepage.html                  # Public landing page
|-- assets/                        # Images & videos
|-- css/                           # Stylesheets per page
|-- src/
|   |-- cekresi/                   # Package tracking (public)
|   |-- cektarif/ ceklokasi/       # Rate & location info
|   |-- login/                     # Staff login page
|   |-- homepageAS/                # Admin/staff dashboard
|   |-- createDelivery/            # Create delivery (3 steps)
|   |-- updatingDelivery/          # Update delivery status
|   `-- mendaftarPelanggan/        # Customer registration
|-- controller/login/
|   |-- bootstrap.php              # Sessions, CSRF, headers, auth helpers
|   |-- koneksiDB.php              # Account DB connection (env vars)
|   |-- koneksiDB2.php             # Operational DB connection (env vars)
|   |-- logincontroller.php        # Login processing
|   |-- logout.php                 # Logout + session destruction
|   |-- createcontroller*.php      # Delivery creation processing
|   |-- updateDelivery.php         # Status update processing
|   |-- deleteData.php             # Delete transit (admin only)
|   `-- submitPelanggan.php        # Customer registration processing
`-- scriptDB/                      # MySQL DDL + seed data
```

---

## License & Context

This project was built for the **Secure Programming** course at BINUS
University. The code is free to use as a learning reference. The "Sampai
Kilat" brand is fictional, used for the assignment.

---

## Author

Rafael Yudianto
Student ID: 2602052153
