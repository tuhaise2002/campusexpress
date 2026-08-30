# CampusExpress

CampusExpress is a PHP and MySQL campus marketplace where students discover nearby products, vendors manage listings, and administrators approve vendor accounts.

## Features

- Public marketplace with search, category, and availability filters
- Student passwordless sign-in using a six-digit one-time code
- Vendor registration, approval workflow, login, and listing management
- Administrator dashboard for vendor approval and rejection
- Image upload validation for JPG, PNG, and WEBP files
- CSRF protection, prepared SQL statements, password hashing, and role-based authorization
- Responsive layouts for desktop and mobile

## Requirements

- XAMPP with Apache, PHP 8.0 or newer, and MySQL
- PHP extensions: `mysqli` and `fileinfo` (enabled by default in XAMPP)
- A modern web browser

## Local setup with XAMPP

1. Clone or copy the project into:

   ```text
   C:\xampp\htdocs\campusexpress
   ```

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Open [phpMyAdmin](http://localhost/phpmyadmin), create a database named `campusexpress`, and import `setup.sql`.

4. Open the application:

   ```text
   http://localhost/campusexpress/
   ```

The default database configuration in `db.php` is suitable for a standard XAMPP installation:

```text
Host: localhost
User: root
Password: (empty)
Database: campusexpress
```

You can override these values with the `DB_HOST`, `DB_USER`, `DB_PASS`, and `DB_NAME` environment variables.

## Test accounts and login flows

### Administrator

Create or update the administrator from a terminal after setting temporary environment variables:

```powershell
$env:ADMIN_EMAIL="owner@example.com"
$env:ADMIN_PASSWORD="use-a-long-unique-password"
$env:ADMIN_NAME="Owner"
C:\xampp\php\php.exe scripts\create_admin.php
```

Then open `http://localhost/campusexpress/admin_login.php`. The setup script does not ship a default production password.

### Vendor

1. Register at `register.php`.
2. Sign in as the administrator and approve the new vendor.
3. Sign in at `login.php`.
4. Add, edit, mark sold out, or delete marketplace items from the vendor dashboard.

Only approved vendors can sign in.

### Student

1. Open `user_login.php`.
2. Enter a valid email address.
3. During local development, the six-digit code appears on `verify_otp.php`.
4. Enter the code to sign in.

Student codes expire after 10 minutes. A new code can be requested after 60 seconds, and verification is limited to five attempts.

## Local and production OTP behavior

CampusExpress displays OTP codes in the browser by default to make local testing possible. This must be disabled in production.

Set this environment variable in production:

```text
APP_SHOW_OTP=0
```

For Apache, environment variables can be configured in the virtual-host configuration using `SetEnv`, followed by an Apache restart. Production OTP delivery uses the Resend API and requires `RESEND_API_KEY` plus a verified `MAIL_FROM` sender.

## Important URLs

| Area | URL |
|---|---|
| Marketplace | `http://localhost/campusexpress/` |
| Student sign in | `http://localhost/campusexpress/user_login.php` |
| Vendor sign in | `http://localhost/campusexpress/login.php` |
| Vendor registration | `http://localhost/campusexpress/register.php` |
| Admin sign in | `http://localhost/campusexpress/admin_login.php` |
| phpMyAdmin | `http://localhost/phpmyadmin` |

## Project structure

```text
campusexpress/
├── index.php              Public marketplace
├── user_login.php         Student OTP request
├── verify_otp.php         Student OTP verification
├── login.php              Vendor login
├── register.php           Vendor registration
├── dashboard.php          Vendor dashboard
├── save_item.php          Create listing action
├── edit_item.php          Listing edit form
├── update_item.php        Update listing action
├── delete_item.php        Delete listing action
├── admin_login.php        Administrator login
├── admin_dashboard.php    Vendor approval dashboard
├── logout.php             Session logout
├── db.php                 Database, session, CSRF, and upload helpers
├── setup.sql              Database schema and default admin
├── style.css              Shared responsive styles
└── uploads/               Vendor product images
```

## Staging deployment

Follow DEPLOYMENT.md for the Docker, managed MySQL, Resend, secret, persistent-upload, health-check, and acceptance-test procedure.

## Security checklist before production

- Serve the application over HTTPS.
- Create a unique administrator password of at least 12 characters with `scripts/create_admin.php`.
- Set a password for the MySQL account and provide it through environment variables.
- Set `APP_SHOW_OTP=0`, `RESEND_API_KEY`, and a verified `MAIL_FROM` sender.
- Move production secrets outside the repository.
- Configure Apache to prevent script execution inside `uploads/`.
- Retain the included database-backed authentication rate limits and add host-level limits as a second layer.
- Back up the database and uploaded images.
- Disable detailed PHP error output in production and log errors securely.

## Verification

Check the PHP syntax of every application file from PowerShell:

```powershell
Get-ChildItem C:\xampp\htdocs\campusexpress -Filter *.php |
  ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
```

A successful check prints `No syntax errors detected` for each file.

## Troubleshooting

### Apache does not start

Another program may already be using ports 80 or 443. Check the XAMPP logs or change the Apache port.

### Database connection fails

Confirm MySQL is running, the `campusexpress` database exists, and the credentials in `db.php` or the database environment variables are correct.

### Vendor cannot sign in

The vendor must first be approved through the administrator dashboard.

### Images do not upload

Confirm the `uploads` directory is writable and the file is JPG, PNG, or WEBP and no larger than 5 MB.

### Student code does not appear

For local development, make sure `APP_SHOW_OTP` is not set to `0`. In production, verify that the configured email or SMS provider sent the code.

## License

No license has been specified yet. Add a `LICENSE` file before distributing the project publicly.