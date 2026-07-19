# Cap Connect deployment

Cap Connect currently advertises cPanel, multiple PHP versions including PHP 8, phpMyAdmin/databases, LiteSpeed, SSL, SSH, Softaculous and daily backups on its web-hosting plans: https://www.capconnect.com/hebergement-web

Confirm exact PHP minor versions and resource limits inside the purchased cPanel account before production. This project requires PHP 8.2 or newer.

## 1. Prepare hosting

1. In Cap Connect cPanel, open Softaculous and install the latest stable WordPress release on the production domain or a staging subdomain.
2. In MultiPHP Manager or Select PHP Version, choose the newest stable PHP 8 release available, with PHP 8.2 as the minimum.
3. Enable common WordPress extensions: `curl`, `dom`, `exif`, `fileinfo`, `filter`, `gd` or `imagick`, `intl`, `json`, `mbstring`, `mysqli`, `openssl`, `sodium`, `xml`, and `zip`.
4. Use at least 256 MB PHP memory where the plan allows it. Set upload and POST limits high enough for the client’s licensed travel images.
5. Activate the free Let’s Encrypt certificate and force HTTPS only after both `https://domain` URLs work.

## 2. Install the project

1. WordPress > Plugins > Add New > Upload Plugin: upload `packages/wma-core.zip`, then activate it.
2. WordPress > Appearance > Themes > Add New > Upload Theme: upload `packages/wild-morocco-adventures.zip`, then activate it.
3. Install Polylang from WordPress.org. Recommended optional plugins are listed in `REQUIRED-PLUGINS.md`.
4. Open Settings > Permalinks, select Post name, and save.
5. Open Tools > Wild Morocco setup to create the removable starter content on staging.
6. Complete Wild Morocco > Business settings and Appearance > Customize.

## 3. Languages

Follow `TRANSLATION-HANDOFF.md`. Create French, English and Arabic versions of all production pages, tours, taxonomies, menus and legal documents. Assign the Arabic menu and verify right-to-left presentation.

## 4. Email

1. Create a domain mailbox such as the client-approved quotation address.
2. Install an SMTP plugin and enter credentials only in WordPress/cPanel—not in project files.
3. Configure SPF, DKIM and DMARC in cPanel Email Deliverability/DNS.
4. Submit one enquiry in each language and confirm the administrator and visitor messages arrive.

## 5. Performance

1. Install LiteSpeed Cache only after confirming the server uses LiteSpeed.
2. Enable page cache, browser cache, WebP/AVIF delivery if supported, and conservative CSS/JS minification.
3. Do not enable overlapping optimization features in multiple plugins.
4. Compress client images before upload; do not upload original camera files directly.

## 6. Security and backups

1. Use unique administrator accounts and strong passwords; enable cPanel/WordPress 2FA where available.
2. Remove unused themes/plugins, keep one current WordPress default theme for recovery, and apply updates first on staging.
3. Cap Connect advertises daily backups, but also take an independent file/database backup before every deployment.
4. Restrict staging from search engines and protect it with authentication.
5. Verify file permissions: normally `755` for directories and `644` for files. Never use `777`.

## 7. Rollback

Before launch, export the database and archive `wp-content`. If deployment fails, restore both from the same timestamp, clear LiteSpeed/browser caches, save permalinks, then retest forms and languages.

## 8. DNS launch

Lower DNS TTL in advance, point the required A/AAAA or nameserver records using Cap Connect’s instructions, verify HTTPS and email records, then remove staging restrictions only after final acceptance.
