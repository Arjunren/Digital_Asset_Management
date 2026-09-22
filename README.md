# Digital Asset Management System

A native PHP 8 and MySQL digital asset library designed for local XAMPP use. It includes secure multi-file uploads, metadata and search, role-based access, previews, version history, sharing, collections, favorites, reporting, notifications, audit logs, user administration, and a soft-delete Recycle Bin.

## Requirements

- Windows with XAMPP (PHP 8.0+ and MySQL/MariaDB)
- PHP extensions enabled: `pdo_mysql`, `fileinfo`, `mbstring`, `session`
- Apache `mod_headers` and `AllowOverride All` are recommended so the included security rules apply
- A modern browser and internet access for the Bootstrap, Bootstrap Icons, and Chart.js CDNs

## Install with XAMPP

1. Install XAMPP from Apache Friends.
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Copy this entire folder to:

   ```text
   C:\xampp\htdocs\digital_asset_management
   ```

4. Open [phpMyAdmin](http://localhost/phpmyadmin).
5. Select the **Import** tab and import:

   ```text
   C:\xampp\htdocs\digital_asset_management\database\digital_asset_management.sql
   ```

   The script creates the `digital_asset_management` database, all tables, indexes, foreign keys, starter categories and settings, and the development administrator.

6. Open [http://localhost/digital_asset_management/](http://localhost/digital_asset_management/).
7. Sign in with:

   ```text
   Email:    admin@dam.local
   Password: Admin123!
   ```

8. Immediately use **Profile → Change password** to replace the development password.

The SQL seed stores only a bcrypt hash. It never stores `Admin123!` as plain text.

## Database configuration

The default XAMPP connection is configured in `config/database.php`:

```text
Host:     localhost
Database: digital_asset_management
Username: root
Password: (empty)
```

You can either edit those defaults or set the environment variables `DAMS_DB_HOST`, `DAMS_DB_NAME`, `DAMS_DB_USER`, and `DAMS_DB_PASS` for Apache.

## PHP upload limits

The application has its own administrator-configurable upload limit, but PHP and Apache must permit at least that amount. In `C:\xampp\php\php.ini`, adjust these values if needed:

```ini
upload_max_filesize=50M
post_max_size=55M
max_file_uploads=20
max_execution_time=120
```

Restart Apache after changing `php.ini`. The effective application limit is the smaller of the server limit and the value under **Settings → Maximum file size**.

## Roles and access

- **Administrator**: all assets, users, reports, settings, logs, categories, restore, and permanent deletion.
- **Asset Manager**: upload and edit their assets, versions, sharing, categories/tags, archive, collections, and restore their deleted assets.
- **Viewer**: browse authorized organization/public assets and collections, preview, favorite, and download when their collection permission allows it.

Every protected page performs a server-side session and role check. Hiding a navigation item is never used as the authorization control.

## Main modules

- Dashboard analytics for uploads, downloads, file types, categories, storage, recent events, largest assets, and popular assets
- AJAX drag-and-drop multi-file upload with per-file progress and status
- Server-side extension, MIME, size, upload-error, unsafe-name, and double-extension checks
- Grid/list asset library with full-text-style search, advanced filters, sorting, and pagination
- Direct image, SVG, PDF, audio, and video previews through an authorization-aware endpoint
- Metadata editing, reusable tags, parent/child categories, favorites, and collections
- Version history with previous-version downloads
- Cryptographically random share tokens with expiration, optional passwords, view-only/download permissions, activation state, and access counts
- Permission-checked download tracking with user, time, and IP address
- Recycle Bin soft deletion and explicit permanent deletion of every stored version
- Administrator user lifecycle, failed-login throttling, password resets, and account status
- Activity log, internal notifications, storage dashboard, date-filtered reports, and CSV export

## Security design

- PDO prepared statements with native prepares
- `password_hash()` and `password_verify()` for passwords
- CSRF tokens on every state-changing form and AJAX upload
- Session ID regeneration after authentication and protected share unlock
- Output escaping with `htmlspecialchars()`
- Role checks and per-asset ownership/visibility checks to prevent URL-based access and IDOR
- Random server filenames; original names are metadata only
- `finfo` MIME detection matched against a fixed extension/MIME allowlist
- Direct web access to `uploads/` is denied; previews and downloads resolve and validate canonical paths inside the upload root
- Uploaded SVG is sent with a restrictive sandbox Content Security Policy
- Sensitive configuration, include, SQL, and upload directories contain Apache deny rules
- Soft deletion by default and explicit, audited permanent deletion

For production deployment, serve over HTTPS, move uploads outside the web root, configure a real email transport for password resets, remove the default account, use a least-privilege database user, pin/vendor frontend dependencies, and set secure PHP session/cookie policies at the server level.

## Local password reset behavior

Because this is a self-contained XAMPP build without an email provider, **Forgot password** generates a one-hour, single-use reset link and displays it locally after a matching email is submitted. The stored database value is a SHA-256 token hash, not the reset token itself. Connect the same flow to your mail provider before production use.

## Writable folders

Apache must be able to write to `uploads/images`, `uploads/documents`, `uploads/videos`, `uploads/audio`, `uploads/archives`, `uploads/thumbnails`, and `uploads/profiles`. Standard Windows XAMPP installs normally allow this. If an upload reports that storage is unavailable, check folder permissions and antivirus/Controlled Folder Access.

## Project layout

```text
config/             Database and application configuration
includes/           Authentication, helpers, and shared layout
assets/css/         Application styling
assets/js/          Shared browser behavior
ajax/               AJAX upload endpoint
database/           Importable MySQL schema and seed data
uploads/            Denied-from-web file storage by asset group
*.php               Page controllers and secure media endpoints
```

## Quick verification

After importing the database:

1. Sign in as the development administrator.
2. Create one Asset Manager and one Viewer under **Users**.
3. Upload a JPG and PDF from **Upload Asset** and watch both progress indicators.
4. Search by title and tag, then test grid/list views.
5. Open an asset, upload a new version, create a password-protected share, and download an older version.
6. Add the asset to a collection, share that collection with the Viewer, and confirm permissions.
7. Move the asset to the Recycle Bin, restore it, then inspect **Activity Logs** and **Reports**.

