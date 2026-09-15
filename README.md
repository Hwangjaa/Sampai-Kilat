# Sampai Kilat — Package Delivery Information System

**Sampai Kilat** is a courier website built independently for the **Secure
Programming** course at BINUS University. The project has two goals:

1. **Functionality** — a complete delivery business flow: package tracking,
   delivery creation, status updates, customer registration and a staff
   dashboard.
2. **Secure programming** — web application security applied on the server
   side (PHP), in the database design (MySQL) and in the browser (CSP, no
   inline script, CSRF on every state change).

The interface was rebuilt from scratch on a documented design system:
`DESIGN-SYSTEM.md` is the contract, `css/site-system.css` is the
implementation.

---

## The Website Story

Sampai Kilat simulates a local courier company with the tagline
*"Sampai Tujuan, Secepat Kilat!"*. The promise the site actually sells is
narrower and checkable: **every step of a parcel's journey is recorded and
readable**.

### Public side (customers)

| Page | What it really does |
|---|---|
| **Homepage** (`homepage.html`) | Explains the journey (6 recorded positions), the service promise, and puts the tracking form first |
| **Track a Package** (`src/cekresi/cekresi.php`) | Validates the `RS-0000000` format, reads the shipment and its transit history from MySQL, shows a 3-stage progress indicator, the last known position and the full timeline; remembers recent tracking numbers on the device |
| **Rate Check** (`src/cektarif/cektarif.html`) | Real volumetric-weight calculation (`p × l × t ÷ 5000`) against the billed weight, compares three services, and sends the breakdown to customer service through a pre-filled email |
| **Coverage** (`src/ceklokasi/CekLokasi.html`) | Searchable list of the real covered areas taken from the `supir` table plus the 5 warehouses, with an honest note that the map image is illustrative |
| **Help** (`src/help/Help.html`) | Complaint preparation form that composes a real `mailto:` (it never claims to send anything by itself), FAQ accordion, direct contact |
| **Prohibited items** (`src/rules/RulesPage.html`) | All 9 real dangerous-goods categories with search, and the KUHP 479p note |
| **About** (`src/aboutus/AboutUs.html`) | How the service works, with only verifiable facts (no invented founding year, customer count or testimonials) |

### Internal side (admin & warehouse staff)

Protected by login, session-based, CSRF-protected, RBAC-checked:

- **Dashboard** (`src/homepageAS/HomePageAdminStaff.php`) — real statistics,
  live search, status filter with a live result count, one row per tracking
  number with an expandable transit history, and the admin-only delete dialog.
- **Create Delivery** (`src/createDelivery/Create1..3.php`) — three steps with
  a step indicator: sender → receiver (service options come from the real
  `servis` table) → confirmation.
- **Update Delivery** (`src/updatingDelivery/Update3.php`) — look a tracking
  number up first and see its current position plus history, then move it to
  the next position.
- **Register Customer** (`src/mendaftarPelanggan/pelanggan.php`) — creates a
  `PE-0000000` customer that can then be used as a sender ID.

### Database

Two separate databases, following the principle of data separation:

| Database | Contents |
|---|---|
| `sampaikilat_account` | Staff, roles and login accounts |
| `sampaikilat_operational` | Tracking numbers, customers, packages, transit records, drivers, warehouse positions, services |

Full DDL + seed data is in `scriptDB/`.

---

## Interface: Design System

The previous interface was a set of page-by-page stylesheets (11 mostly
duplicated files, a grey link colour on one page, a broken global `*` reset on
another, three different font stacks, inline `<script>` blocks that the CSP
would have blocked). It was replaced by one system.

**Direction.** A logistics control panel a customer is allowed to see:
navy structure, one interactive blue, yellow used only for attention, mono
type for machine values (tracking numbers, timestamps, codes). Deliberately
not a startup landing page: no gradient blobs, no fake statistics, no
invented testimonials ([`DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md)).

**Typography.** Self-hosted, 63 KB total, no third-party requests:

| Role | Family | Where |
|---|---|---|
| Display / UI | Archivo (variable 400–800, `assets/fonts/archivo-latin.woff2`) | Headings, buttons, nav, labels, badges |
| Body | system UI stack | Paragraphs, inputs, table cells |
| Data | IBM Plex Mono (`assets/fonts/ibmplexmono-latin-*.woff2`) | Tracking numbers, timestamps, position codes |

**Structure.** Tokens (colour, type scale, 4-px spacing scale, radius,
elevation, motion) live in `css/site-system.css`; components (header + mobile
drawer, footer, buttons, fields, cards, badges, timeline, steps, progress,
tables that stack into cards on phones, empty states, alerts, dialogs, toasts,
chips, skeletons) are built from those tokens. `css/internal.css` adds the
staff application shell. Page stylesheets contain page-specific layout only.

**Behaviour.** `javascript/site.js` is the shared runtime exposed as
`window.SK`: drawer with focus trap, form validation that reports which field
failed and moves focus to it, copy-to-clipboard, native `<dialog>` handling,
list filtering with live counts and empty states, reveal-on-scroll that
respects `prefers-reduced-motion`, and the recent-tracking-number list. Each
page adds a small module under `javascript/pages/`.

**Responsive.** Verified with zero horizontal overflow at 320, 375, 390, 430,
768, 1280 and 1600 px (84 page loads measured through the DOM, not by eye).
The mobile navigation is a distinct drawer with 48 px tap targets, tables
reflow into labelled cards, and the two-column forms collapse at their own
content-driven breakpoints. Related to that, no layout depends on hover.

**How the 9 usability principles were applied**

1. *Consistency* — one header, footer, button, field, badge and status colour
   across all 14 pages; the previous English/Indonesian mix (`Home`,
   `About Us`, `Help`) and the three different nav lists are gone.
2. *Universal usability* — 48 px targets in the drawer, visible focus rings,
   skip links, `lang="id"`, one `h1` per page, no colour-only status (every
   badge pairs a colour with a text label).
3. *Informative feedback* — every action answers: submitting a form shows a
   spinner on the button, validation reports the count and the first bad
   field, a lookup either renders a result, a real "not found" panel or a real
   error alert, and toasts/success banners confirm completed work.
4. *Dialog closure* — the create-delivery flow always ends on a confirmation
   step, then a success banner naming the generated tracking number; every
   controller redirect with a flash message instead of dumping a plain-text
   error page.
5. *Error prevention* — the tracking input formats itself (`RS-` + digits,
   max 7), the service ID is a real list from the database instead of a code
   typed from memory, position updates require looking the shipment up first,
   and server-side validation stays authoritative.
6. *Easy reversal* — cancel/back links on every step, the receiver data
   survives going back to step 2, reset buttons on the calculator and the
   filter, and a native `<dialog>` that closes with `Escape`.
7. *Internal locus of control* — no automatic redirects beyond the expected
   POST-redirect-GET, no popups, no autoplay video on reduced-motion
   (the decorative loop is hidden and the poster frame stays).
8. *Reduced memory load* — the last five tracking numbers are kept on the
   device, current positions and step counters stay visible, the dashboard
   shows a live "showing X of Y" count, and the rate page recomputes as the
   numbers change.
9. *Aesthetic and minimalist design* — one font pair plus mono for data,
   whitespace instead of boxes, and only the elements that carry information.
   Every visible control does something: there is no `href="#"`, no dead
   button, no placeholder success message and no fake social icons.

**Assets.** The homepage used to autoplay a 72.5 MB 4K clip; it now loads a
0.81 MB 720p loop plus a 66 KB poster, which cut the first load by ~145 MB
(the old clip and its byte-identical copy on the login page are no longer
requested). Eight images were re-encoded in place (4.47 MB → 0.91 MB) with no
file renamed. Details and reproduction commands: `tools/ASSET-REPORT.md`.

---

## Secure Programming: What Was Applied

### 1. SQL injection — fully parameterized queries

Every database query uses prepared statements (`mysqli` + `bind_param`); no
user input is ever concatenated into SQL.

```php
$stmt = $conn->prepare('SELECT username_staff, password_staff, id_role
                        FROM akun_staff WHERE username_staff = ? LIMIT 1');
$stmt->bind_param('s', $username);
```

### 2. Cross-site scripting — output escaping

Every value from the database is escaped with
`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` through the
`e()` helper.

### 3. CSRF — synchronizer token on every state change

Login, create, update, delete, customer registration and logout all post a
`csrf_token` from the session and are checked with `hash_equals()`; a missing
or invalid token returns HTTP `419` and the user gets a redirect back with an
explanation instead of a dead end.

### 4. Session hardening

`controller/login/bootstrap.php`: `HttpOnly` + `Secure` (under HTTPS) +
`SameSite=Lax` cookies, `session_regenerate_id(true)` after login, and a
logout that clears `$_SESSION`, expires the cookie and destroys the session.

### 5. Role-based access control

`require_login()` guards every internal page; Admin (`RL-001`) vs Staff
(`RL-002`) is compared with `hash_equals()`. The delete affordance is only
rendered for Admin **and** re-validated in `deleteData.php` — hiding a button
is never the security boundary. Internal pages are `.php` so they cannot be
opened without passing the session check.

### 6. HTTP security headers

Sent from `bootstrap.php` on every PHP request:

```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; img-src 'self' data:; media-src 'self';
  font-src 'self'; style-src 'self'; script-src 'self'; form-action 'self';
  base-uri 'none'; frame-ancestors 'none'
```

The policy no longer needs `'unsafe-inline'` for either scripts or styles,
because the interface has **no inline `<script>` blocks, no `on*=` attributes
and no `style=` attributes** — all behaviour lives in `javascript/` and all
styling in the stylesheets. That was a deliberate constraint during the
rebuild, and it is checked by grep in the verification step below.

### 7. Input validation and defense in depth

- Tracking numbers must match `^RS-[0-9]{7}$` on the server, not just in HTML.
- Status updates use a whitelist of positions (`PS-001`…`PS-006`).
- Phone numbers and dates are validated before they reach a query.
- New tracking numbers are generated inside a transaction with
  `SELECT ... FOR UPDATE`, so concurrent requests cannot produce duplicates.
- Database errors never leak: details go to `error_log()`, users get a generic
  message.

### 8. No hardcoded secrets

Database credentials come from environment variables (`DB_HOST`, `DB_USER`,
`DB_PASS`, `DB_ACCOUNT`, `DB_OPERATIONAL`). The `controller/` and `scriptDB/`
folders must not be exposed by the web server.

---

## Requirements

- **PHP 8.0+** (`declare(strict_types=1)`, the `mixed` type hint in `e()`)
- **MySQL / MariaDB 8+** (CHECK constraints + REGEXP)
- Apache (XAMPP/Laragon) or the PHP built-in server
- A modern browser (the interface uses native `<dialog>`, `:has()` and
  `details`, all available in current Chrome/Edge/Firefox/Safari)

No Composer dependencies — native PHP + MySQL only.

---

## How to Run

### 1. Prepare the databases

```bash
mysql -u root -p < scriptDB/SampaiKilat-AccountDatabase.sql
mysql -u root -p < scriptDB/SampaiKilat-OperationalDatabase.sql
```

### 2. Set environment variables

```bash
export DB_HOST="127.0.0.1"
export DB_USER="root"
export DB_PASS="your_password"
export DB_ACCOUNT="sampaikilat_account"
export DB_OPERATIONAL="sampaikilat_operational"
```

> Without them the app falls back to `127.0.0.1` / `root` / empty, which is
> fine for a default XAMPP setup but must be set for a real deployment.

### 3. Start the web server

```bash
cd "Sampai Kilat"
php -S localhost:8000
```

Then open `http://localhost:8000/homepage.html`.
(Under XAMPP/Laragon, put the folder in the web root and open it the same way.)

### 4. Log in as staff

| Username | Password | Role |
|---|---|---|
| `andi.pratama` | `password123` | Admin |
| `budi.santoso` | `securepass` | Staff |
| `citra.dewi` | `mypassword` | Staff |

A further two staff accounts (`dedi.suhendra`, `eka.kurniawan`) exist in the
seed data for the dashboard's role checks; their demo passwords were not
recorded in the seed script and are not known, so log in with the three above.

Passwords are stored as **bcrypt** hashes (`$2y$12$…`, generated with
`password_hash()`) and checked with `password_verify()`.

### 5. Try the main flow

1. Log in → the dashboard shows statistics, the delivery table and its filter.
2. **Buat pengiriman** → fill the three steps (sender `PE-0000001`,
   service `SR-0000001`) → the new `RS-` number appears as a success banner.
3. **Update lokasi** → look the tracking number up, see its history, move it.
4. Log out → open **Cek Resi** and enter the tracking number; the same
   position is now publicly visible.

---

## Project Structure

```
Sampai Kilat/
|-- homepage.html                  # Public landing page
|-- DESIGN-SYSTEM.md               # Interface contract (tokens, components, rules)
|-- assets/
|   |-- brand/                     # Inline-SVG mark + favicon
|   |-- fonts/                     # Self-hosted Archivo + IBM Plex Mono (latin)
|   |-- video/                     # hero-loop-720.mp4 + poster used by homepage & login
|   `-- <page>/image/              # Page imagery
|-- css/
|   |-- site-system.css            # The design system (tokens + components)
|   |-- internal.css               # Staff application shell
|   `-- <page>/<Page>.css          # Page-specific layout only
|-- javascript/
|   |-- site.js                    # Shared runtime (window.SK)
|   `-- pages/<page>.js            # Per-page behaviour
|-- src/
|   |-- cekresi/                   # Package tracking (public)
|   |-- cektarif/ ceklokasi/       # Rate & coverage information
|   |-- aboutus/ help/ rules/      # Company, support, prohibited items
|   |-- login/                     # Staff login
|   |-- homepageAS/                # Admin/staff dashboard
|   |-- createDelivery/            # Create delivery (3 steps)
|   |-- updatingDelivery/          # Update delivery status
|   `-- mendaftarPelanggan/        # Customer registration
|-- controller/login/
|   |-- bootstrap.php              # Sessions, CSRF, security headers, auth helpers
|   |-- koneksiDB.php              # Account DB connection (env vars)
|   |-- koneksiDB2.php             # Operational DB connection (env vars)
|   |-- logincontroller.php        # Login processing
|   |-- logout.php                 # Logout + session destruction
|   |-- createcontroller*.php      # Delivery creation processing
|   |-- updateDelivery.php         # Status update processing
|   |-- deleteData.php             # Delete transit (admin only)
|   `-- submitPelanggan.php        # Customer registration processing
|-- scriptDB/                      # MySQL DDL + seed data
`-- tools/ASSET-REPORT.md          # Font/image/video optimisation report
```

---

## Verification Performed

| Check | Result |
|---|---|
| `php -l` on every PHP file | clean |
| End-to-end flow over HTTP with a cookie jar (login → dashboard → 3-step create → update → register → track public page, plus failure paths and CSRF rejection, with test rows removed afterwards and the 12-row seed data left intact) | 40/40 checks pass |
| CSRF token present **inside** each form that posts state (not just somewhere on the page) | verified per form |
| Responsive audit through the DOM at 320/375/390/430/768/1600 px, 14 pages | 91 loads, 0 overflows, exactly one `h1` per page |
| Text-only zoom to 200% at 320/375/1280 px, 14 pages | 38 of 39 loads clean; `src/help/Help.html` at a 320 px viewport (≈160 px effective width) still overflows by 23 px — the one residual, everything else reflows |
| Touch targets in the mobile drawer | 48 px per row, measured |
| Contrast of the muted/on-dark text tokens | computed: 7.3:1 `--ink-soft` on white, 7.8:1 on `--navy-950`, 10.5:1 signal on navy |
| No inline `<script>`, no `on*=` attribute, no `style=` attribute, no `href="#"`, no English nav labels, no em dash in UI copy | grep-verified |
| Screenshots reviewed (desktop + mobile, menu open, tracking result, calculator result) | findings fixed: invisible progress bar (`width` on an inline `span`), a toast covering the rate table, and a 4-px overflow caused by a `<select>` intrinsic width |

---

## Known Limitations (kept on purpose)

- **Static pages cannot send headers.** The CSP and the other security
  headers come from `bootstrap.php`, so they apply to the PHP pages.
  `homepage.html`, `cektarif.html`, `ceklokasi/CekLokasi.html`,
  `help/Help.html`, `aboutus/AboutUs.html`, `rules/RulesPage.html` and the
  login page are static, and under the PHP built-in server they are served
  without those headers. Apache users should repeat them with
  `mod_headers`.
- **No rate limiting on login** — only a `usleep(250ms)` delay to slow brute
  force. A production system would add lockout/backoff.
- **No audit log** of staff actions, so the interface does not claim one.
- **The rate estimator is an estimate.** It uses billed weight
  (max of scale weight and `p × l × t ÷ 5000`) and ignores distance, which the
  page states plainly. The final price is set after weighing and address
  verification.
- **Tracking shows sender, recipient and destination** to anyone holding the
  tracking number. That is what the tracking feature is for, but it is a
  deliberate exposure worth naming.
- **Indonesian only**, no localisation layer.
- **The old 4K source clips are still in the repository** (~215 MB, including a
  byte-identical copy on the login page). They are no longer requested by any
  page; they are kept so the owner can decide what to archive. See
  `tools/ASSET-REPORT.md`.
- Seed passwords are demo credentials and must never be used in production.

---

## License & Context

Built for the **Secure Programming** course at BINUS University. The code is
free to use as a learning reference. The "Sampai Kilat" brand is fictional.

## Author

Rafael Yudianto — Student ID 2602052153
