# Raspina Clothing — PHP/MySQL rebuild

Production-oriented wholesale catalogue for `raspinaclothing.com`, rebuilt from the sanitized public product data in the July 2026 cPanel backup.

## What is included

- Dynamic PHP routes for the homepage, shop, 32 collections and 306 product pages.
- Search, category and availability filters, sorting and pagination.
- Product galleries, related products and accessible image placeholders.
- Browser-local inquiry shortlist and wholesale enquiry workflow.
- Contact, catalog, about, privacy, terms and 404 pages.
- JSON catalogue mode that works without database credentials.
- Optional PDO/MySQL tables and a guarded one-time CLI importer.
- Apache redirects for the previous `default.asp` URLs.
- Real Raspina editorial/product assets and the verified 2023 catalog PDF.

No users, orders, comments, email archives, passwords, SSL keys or other private backup records were imported.

## Server requirements

- Linux/cPanel with Apache 2.4 and `mod_rewrite`.
- PHP 7.4 syntax compatibility, PDO and JSON extensions.
- PDO MySQL when database mode is enabled.
- `mbstring` is recommended but the application has a fallback.
- HTTPS enabled for the public domain.

The backed-up host currently reports PHP 7.4. The code remains compatible with it for migration, but PHP 7.4 is obsolete. Upgrade the domain to a currently supported PHP release offered by the hosting provider before production launch, then repeat all tests.

## Important content limitations

- 306 public products and 32 categories were recovered.
- 202 products have at least one recovered image; 104 use an intentional branded placeholder.
- 273 products have recovered USD reference values. These are labelled as reference values and are not guaranteed wholesale prices.
- Wholesale price, stock, sizing, material, minimum quantities and delivery must be confirmed by the Raspina team.
- The privacy and terms pages are implementation placeholders and require legal/business review before public launch.
- Verify the studio address, telephone, order mobile number, email and Instagram link in `app/config.local.php` before launch.

## Deploy to cPanel in JSON mode

JSON mode is the safest first deployment and does not need a database change.

1. Take a new full cPanel backup.
2. Upload the **contents** of this `public_html` directory directly into the domain document root. Do not upload an extra parent directory.
3. Ensure hidden files were uploaded, especially `.htaccess` and the `.htaccess` files under `app`, `database` and `storage`.
4. Standard permissions are normally `755` for directories and `644` for files. The PHP user must be able to write to:
   - `storage/logs`
   - `storage/rate-limit`
5. Keep `app/config.php` unchanged. For local settings, copy `app/config.example.php` to `app/config.local.php` and edit the copy.
6. Leave `database.enabled` set to `false` for JSON mode.
7. Test the routes listed below before changing DNS, redirects or search indexing.

When MySQL is disabled, valid contact/wholesale messages are appended to `storage/logs/inquiries.ndjson`. This file contains personal enquiry data. It is blocked from web access, but it must still be backed up, access-controlled and handled privately. Enable email delivery only after confirming that PHP mail is correctly configured on the host.

## Optional MySQL setup

The site does not require MySQL to render, but database mode is ready for future management and message storage.

1. In cPanel, create a dedicated database and a dedicated database user.
2. Grant that user privileges only on the new database.
3. Copy `app/config.example.php` to `app/config.local.php`.
4. Enter the database host, port, name, user and password in `config.local.php`.
5. Set `database.enabled` to `true`.
6. Keep the default prefix `rc_` unless another validated prefix is required.
7. From cPanel Terminal, change to the deployed site directory and run:

```text
php database/import-catalog.php --dry-run
php database/import-catalog.php --confirm
```

The dry run validates the JSON and database connection without creating tables. The confirmed import creates the schema and imports the sanitized catalogue in one transaction. It refuses to overwrite an existing import.

`--force` deletes and replaces rows in the prefixed catalogue tables. Use it only after taking a database backup and intentionally approving a full catalogue replacement:

```text
php database/import-catalog.php --confirm --force
```

If cPanel Terminal is unavailable, keep JSON mode enabled until the hosting provider can run the importer. Do not expose the importer as a public web page; it intentionally returns 404 outside CLI/phpdbg.

After a successful import, open the website and confirm that the catalogue is loading. The repository automatically falls back to JSON if MySQL is unavailable or contains no products.

## Required launch checks

Open these routes on desktop and mobile:

```text
/
/shop
/shop?q=blouse&stock=instock&sort=az
/collection/collection-2026
/collection/hodies
/product/womens-tie-front-wrap-crop-blouse
/catalog
/about-us
/contact
/wholesale
/wishlist
/search?q=dress
/privacy
/terms
/404
/sitemap.xml
```

Also verify:

- An unknown URL returns HTTP 404 with the branded 404 page.
- Old URLs such as `/shop/default.asp`, `/product/{slug}/default.asp` and category pagination redirect with HTTP 301.
- Search, filters, sorting and pagination preserve valid query parameters.
- Mobile navigation and search restore keyboard focus after closing.
- The gallery closes with Escape and works without pointer hover.
- Reduced-motion settings disable automatic/animated movement.
- Shortlist add/remove/clear works and is included in the wholesale form.
- Invalid CSRF, honeypot and repeated form submissions are rejected safely.
- `app/`, `database/`, `storage/`, JSON, SQL, log and configuration files cannot be downloaded over HTTP.
- No PHP notice, warning, physical path or stack trace is shown publicly.
- HTTPS, canonical URLs, OpenGraph data, sitemap and robots rules use the final domain.

## Search indexing

The included `robots.txt` allows indexing. During private testing, temporarily change it to:

```text
User-agent: *
Disallow: /
```

Restore the included production rules only after content, contact information, prices, legal pages and redirects have been approved.

## Safe update procedure

1. Back up website files and the MySQL database.
2. Test the new package in a separate subdomain or temporary document root.
3. Preserve `app/config.local.php` and `storage/logs` during code updates.
4. Upload code/assets, run the required importer only when catalogue data changed, and test.
5. Switch the production document root only after approval.
6. Keep the previous working release available for rollback.
