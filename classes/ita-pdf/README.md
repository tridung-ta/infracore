# ITA handover PDF runtime

Isolated mPDF 8.3.1 runtime for F5.4. The root dependency is pinned and all eight packages are locked in composer.lock. The dependency resolver targets host PHP 8.3.33; actual local checks ran on PHP 8.5.10. Do not change the legacy PhpSpreadsheet loader or install Composer on production.

Required PHP extensions: gd, mbstring, zlib. Local GD can be enabled for a single command with `php -d extension=gd`; no global php.ini change is required. The host must independently have these extensions enabled. Missing runtime dependencies return a controlled unavailable message; other application features remain available.

Reproduce in this directory with Composer using `install --no-dev --prefer-dist --no-scripts --no-plugins`, then `audit --locked --no-dev --no-plugins` and `check-platform-reqs --no-dev`. Run Composer with a PHP process that has GD enabled; never use ignore-platform-reqs or disable security-advisory blocking.

vendor is intentionally ignored by Git. It must be included in an offline delivery package, with fonts and licenses, when the user requests deployment. `scripts/package_ita_pdf.py` prepares a local PDF delta from committed source plus the installed locked vendor tree, with inventory/hashes and a source commit. It refuses dirty runtime source; it does not deploy. The delta depends on checkpoint bfe3539 and is not a complete legacy application backup.

Root mPDF license: GPL-2.0-only. Each transitive dependency and font license remains in its vendor directory. No compiled templates, sessions, real PDF files, user data, private config or database dumps belong in this directory or package.

The renderer uses a private per-call temp tree outside ROOT_PATH, denies HTTP/local image asset loading and only renders an application-owned Smarty template with escaped text. Font files are loaded from the installed renderer package; no remote fonts/images or user HTML/CSS are supported. Temp cleanup never follows links. Generated PDFs are not stored publicly.
