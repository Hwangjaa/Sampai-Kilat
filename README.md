# Sampai Kilat

Sampai Tujuan, Secepat Kilat

A courier package website built as the final project for the Secure Programming course at BINUS University

## About the project

The idea is a local delivery company with a public website and an internal staff dashboard

Visitors can track a package by entering the tracking number like `RS-0000000` on the tracking page, check shipping rates, look up warehouse locations and register as a customer

Staff log in to a dashboard that shows every delivery in one table with the package content, the driver, the shipping time and the last known position

From the dashboard staff can create a new delivery in three steps, sender data, receiver data and service choice
At the end the system generates a new tracking number
While the package moves between warehouses staff update its position, the status list is a fixed set like WH Tangerang, WH Jakarta and SAMPAI
The delete feature only exists for the Admin role

The data lives in two MySQL databases
`sampaikilat_account` holds staff accounts and roles
`sampaikilat_operational` holds customers, packages, transit records, drivers and warehouse positions

## How security is handled

Security was the main grading point of this course so every decision below was made on purpose

All database queries use prepared statements, user input never becomes part of the SQL string
Every value printed to the page goes through `htmlspecialchars` to stop XSS
Sensitive forms send a CSRF token and the server checks it with `hash_equals` before doing anything, a request with a bad token gets HTTP 419
Login regenerates the session id so session fixation does not work
The session cookie is HttpOnly, Secure when HTTPS is on and SameSite Lax
Logout destroys the session for real, it empties the session data, expires the cookie and calls session destroy
The dashboard checks the role on every request, hiding the delete button in HTML is not the security boundary because the delete endpoint asks for the Admin role again on the server
Security headers like `X-Frame-Options` and a strict Content Security Policy are sent from one bootstrap file on every request
Tracking numbers are validated with a regex on the server, not just with the HTML pattern attribute
Status updates only accept whitelisted warehouse names so nobody can push an arbitrary status into the database
New tracking numbers are generated inside a transaction with a locked read so two requests at the same moment cannot grab the same number
Database errors are written to the error log and the browser only sees a generic message, internal details never leak to the page
Database credentials come from environment variables, not from the source code

## Known limitations

These are kept on purpose for transparency since this is a course project

Passwords still use the layered MD5 scheme from the course template because the accounts table stores 32 character hashes
The proper fix is `password_hash` with a VARCHAR 255 column and that would be the first thing to change for real deployment
The seed accounts use weak passwords, they exist only for the demo
There is no rate limiting on the login endpoint yet, only a short delay to slow down brute force attempts

## Requirements

PHP 8.0 or newer
MySQL or MariaDB 8 or newer
XAMPP or Laragon for the easiest setup
Any modern browser

No Composer and no framework, the whole thing is plain PHP

## How to run

1 Put the project folder inside the web root, for XAMPP that is the htdocs folder
2 Start Apache and MySQL from the control panel
3 Open phpMyAdmin and import both SQL files from the `scriptDB` folder, `SampaiKilat-AccountDatabase.sql` and `SampaiKilat-OperationalDatabase.sql`
4 Set the environment variables if your MySQL is not the default local setup
   DB_HOST default `127.0.0.1`
   DB_USER default root
   DB_PASS default empty
   DB_ACCOUNT default `sampaikilat_account`
   DB_OPERATIONAL default `sampaikilat_operational`
5 Open `http://localhost/Sampai Kilat/homepage.html` in the browser
6 Open the login page from the site and use one of the seed accounts

| Username | Password | Role |
|---|---|---|
| andi.pratama | password123 | Admin |
| budi.santoso | securepass | Staff |
| citra.dewi | mypassword | Staff |

7 Try the main flow, log in, create a delivery, update its status to the next warehouse, log out, then track the resi from the public page

## Project structure

```
homepage.html
src/cekresi              public tracking page
src/homepageAS           staff dashboard
src/createDelivery       create delivery form, three steps
src/updatingDelivery     update package status
src/login                login page
src/mendaftarPelanggan   customer registration
controller/login         bootstrap, session, CSRF, database connections, all controllers
scriptDB                 the two SQL schemas with seed data
css and assets           stylesheets, images and videos
```

## Group members

Raymond Ivander 2602059550
Muhamad Salman Hakim 2602076443
Vutanto Hendy Wijaya 2602063535
Rafael Satriaprima Yudianto 2602052153
Darren Aditya 2602076153
