# Startup Idea Collaboration Portal

A full-stack web platform where students and aspiring entrepreneurs can share
startup ideas, get feedback, and team up with other students to build real
projects — built with **Core PHP 8+, MySQL, Bootstrap 5, and vanilla JS/AJAX**.

---

## Features

- **Authentication** — register, login, logout, forgot/reset password, hashed passwords, secure sessions, CSRF protection
- **Student Dashboard** — my ideas, pending collaboration requests, notifications, saved ideas, recent activity
- **Startup Idea CRUD** — create/edit/delete ideas with cover image + document upload, industry category, required skills, stage, team size
- **Idea Feed** — browse, search, filter (industry / skill / stage / keyword), like, bookmark, share, and view details
- **Discussion System** — threaded comments + replies, comment likes, reporting inappropriate content
- **Collaboration Module** — send/accept/reject collaboration requests, team membership, "who's on the team" view
- **Notifications** — for requests, comments, likes, acceptances, new team members
- **Admin Panel** — manage users (ban/unban/delete), manage ideas (remove/restore/delete), manage categories, review reports, platform statistics
- **Modern UI** — Bootstrap 5, responsive sidebar/topbar layout, light & dark mode, mobile-friendly

---

## Tech Stack

| Layer      | Technology                          |
|------------|--------------------------------------|
| Frontend   | HTML5, CSS3, Bootstrap 5, JavaScript, AJAX (Fetch API) |
| Backend    | Core PHP 8+ (no framework), PDO      |
| Database   | MySQL 5.7+ / MariaDB                 |
| Server     | Apache (XAMPP / Laragon / WAMP)      |

---

## Project Structure

```
/startup-portal
│── index.php               Landing page
│── login.php / register.php / logout.php
│── forgot_password.php / reset_password.php
│── dashboard.php            Student dashboard
│── create_idea.php / edit_idea.php / delete_idea.php
│── ideas.php                 Idea feed (search & filter)
│── idea_details.php          Single idea view, comments, collaboration
│── my_ideas.php / bookmarks.php / collaboration.php
│── notifications.php / report.php / profile.php
│
│── admin/                   Admin panel (users, ideas, categories, reports)
│── ajax/                    AJAX endpoints (like, bookmark, comment, requests)
│── includes/                 Shared PHP partials (header, navbar, sidebar, functions)
│── config/                   Database connection & app config
│── assets/
│   │── css/style.css
│   │── js/script.js
│   └── img/
│── uploads/                  User-uploaded files (ideas, documents, profiles)
└── database/
    │── schema.sql             Full DB schema + seed data
    └── generate_admin_hash.php
```

---

## Setup Instructions

### 1. Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache with `mod_rewrite` and `mod_headers` enabled
- XAMPP / Laragon / WAMP (or any LAMP stack)

### 2. Get the code onto your server
Place the entire `startup-portal` folder inside your server's web root, e.g.:
- XAMPP: `C:\xampp\htdocs\startup-portal`
- Laragon: `C:\laragon\www\startup-portal`

### 3. Create the database
Open phpMyAdmin (or the MySQL CLI) and import the schema:

```sql
-- via CLI
mysql -u root -p < database/schema.sql
```

This creates the `startup_portal` database, all tables with foreign keys,
seed categories, and a placeholder admin account.

### 4. Set your admin password
The seed admin row ships with a placeholder (non-working) password hash for
security. Generate a real one:

```bash
php database/generate_admin_hash.php YourStrongPassword123
```

Copy the printed `UPDATE` statement and run it in phpMyAdmin/MySQL. The
admin login is `admin@startupportal.test` with the password you chose.

### 5. Configure the app
Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'startup_portal');
define('DB_USER', 'root');
define('DB_PASS', '');            // your MySQL password
define('BASE_URL', 'http://localhost/startup-portal'); // match your folder/vhost
```

### 6. File permissions
Make sure the web server can write to the uploads directory:

```bash
chmod -R 755 uploads/
```

### 7. Run it
Start Apache + MySQL (via your XAMPP/Laragon/WAMP control panel) and visit:

```
http://localhost/startup-portal/
```

Register a student account, or log in as admin at `/admin/index.php`.

---

## Security Notes

- All database queries use **PDO prepared statements** — no raw SQL concatenation.
- Passwords are hashed with `password_hash()` (bcrypt) and verified with `password_verify()`.
- Every state-changing form includes a **CSRF token**, verified server-side.
- All user-supplied output is escaped with `htmlspecialchars()` before rendering (XSS protection).
- File uploads are restricted by extension and size, renamed to random filenames, and the `uploads/` directory blocks script execution via `.htaccess`.
- Sessions use `httponly` + `SameSite=Lax` cookies and are regenerated on login.
- Login attempts are rate-limited per session to slow brute-force attempts.

## Notes / Possible Next Steps

- Email delivery (verification emails, real password-reset emails) is stubbed —
  wire up PHPMailer/SMTP in `forgot_password.php` and `register.php` for production use.
- The `email_verified` column and `verification_token` exist in the schema and
  are set on registration, ready for you to enforce verification before login if desired.
- Consider adding full-text search ranking (`ft_search` index is already in the schema) for larger datasets.

---

Built as a starter/reference implementation — feel free to extend the admin
analytics, add real-time notifications (websockets), or convert the AJAX
layer to a REST API for a future mobile app.
