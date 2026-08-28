# Appeal Prospect MVP

A **proof-of-concept legal analysis web app**: upload or paste a court judgment (PDF or text) and receive a structured **13-section AI analysis** of its appeal prospects, built for South African law. Stack: vanilla PHP 8.2 + MariaDB/MySQL 8 (UTF8MB4) on XAMPP/LAMPP, Phoenix UI (Bootstrap 5.3), OpenAI GPT-4o-mini, optional Perplexity web research.

> ⚠️ **Demo only — not legal advice.** This is a demonstration application intended for attorneys, legal ops, or stakeholders evaluating AI-assisted judgment review. See `executive-summary.md` and `MVP_PRD.md` for product context.

---

## Features

- **Upload** a PDF judgment (parsed with system `pdftotext`) **or paste** judgment text
- **AI analysis** via OpenAI GPT-4o-mini using the 13-section System Prompt v5.2 (`app/prompts/appeal_prospect_v5_2.md`)
- **Optional web research** via Perplexity API (graceful fallback if the key is missing)
- **Per-user case history** under "My Cases", with search/filter/pagination
- **Admin dashboard**: site stats, view/delete any case, manage API keys (encrypted in DB), test connections
- **Security**: Argon2id password hashing, CSRF protection, session hardening, role-based access, secured uploads

---

## Tech Stack

| Layer       | Choice                                              |
|-------------|-----------------------------------------------------|
| Language    | PHP 8.2+ (vanilla, no framework)                    |
| Database    | MariaDB/MySQL 8+ with UTF8MB4                        |
| Web server  | Apache (LAMPP/XAMPP)                                |
| UI          | Phoenix UI (Bootstrap 5.3) components               |
| AI          | OpenAI `gpt-4o-2024-08-06` (GPT‑4o-mini fallback), Perplexity (optional) |
| PDF parsing | System `pdftotext` (poppler-utils)                  |

---

## Project structure

```
mvp-legal/
├── app/                  # Business logic
│   ├── auth.php          # Sessions, auth, CSRF, Argon2id
│   ├── db.php            # PDO singleton + db_* helpers
│   ├── security.php      # Validation, session security, API key checks
│   ├── settings.php      # Encrypted settings + API keys (AES-256-GCM)
│   ├── gpt_handler.php   # OpenAI integration (System Prompt v5.2)
│   ├── perplexity.php    # Optional web research
│   ├── pdf_parser.php    # PDF → text via pdftotext
│   ├── save_fetch.php    # Case storage / retrieval
│   ├── error_handler.php # Central error handling + 404/500 pages
│   └── prompts/
│       └── appeal_prospect_v5_2.md   # 13-section system prompt
├── public/               # Web root (everything users hit)
│   ├── index.php         # Landing
│   ├── login.php / register.php / logout.php
│   ├── upload.php        # PDF dropzone / pasted text
│   ├── upload-ajax.php   # DropzoneJS endpoint (POST-only)
│   ├── analyze.php       # Runs GPT + Perplexity pipeline
│   ├── results.php       # Renders the 13-section analysis
│   ├── my-cases.php      # Per-user history
│   ├── admin.php         # Admin dashboard + API key management
│   ├── seed.php          # One-time DB seed (key-protected)
│   └── assets/           # CSS/images
├── uploads/{user_id}/    # Secured PDF storage (blocked from web)
├── logs/                 # security.log / error logs
├── schema.sql            # users, cases, settings (+ indexes, FKs)
├── landing.html          # Out-of-repo marketing page reference
├── phoenix/              # Reference UI components only — do not modify
└── vendor/               # PHPUnit (dev only; tests currently removed)
```

All public pages are reached through the root `.htaccess` rewrite into `public/`
(e.g. `/mvp-legal/login.php`, `/mvp-legal/my-cases.php`).

---

## Setup

### 1. Requirements

- XAMPP/LAMPP (Apache + PHP 8.2 + MySQL/MariaDB) or equivalent
- `poppler-utils` for `pdftotext` (PDF extraction)
- PHP extensions: `pdo_mysql`, `openssl`, `curl`, `iconv`

### 2. Start services

```bash
/opt/lampp/lampp start          # starts Apache + MySQL
/opt/lampp/lampp status         # verify (PID-file warnings are cosmetic)
```

### 3. Create the database

Database **must** be named `appeal_prospect_mvp` with UTF8MB4.

```bash
mysql -u root
CREATE DATABASE appeal_prospect_mvp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE appeal_prospect_mvp;
SOURCE schema.sql;
```

Update the credentials in `app/config.php` if you don't use XAMPP defaults.

### 4. Seed initial data

Visit (exact key required):

```
http://localhost/mvp-legal/seed.php?key=appeal_prospect_setup_2025
```

Creates:
- **Admin:** `admin@example.com` / `admin123`
- **Demo user:** `demo@example.com` / `demo123`

> Delete `seed.php` after first run.

### 5. Configure API keys

1. Log in as admin → **Admin** dashboard
2. Add your **OpenAI API key** (required for analysis) and optional **Perplexity key**
3. Use the test-connection buttons

Keys are stored encrypted (AES-256-GCM) in the `settings` table — never plain text.

---

## Usage flow

`Login → Upload a PDF or paste text → Start AI Analysis → Read the 13-section result → Revisit under My Cases`

Analyzing a case runs:

1. **OpenAI GPT** (`analyze_judgment_with_gpt`) → 13-section structured analysis
2. **Perplexity** (optional, if configured) → up to 5 web sources/citations
3. Results are stored on the `cases` row (`structured_analysis` JSON, `citations` JSON, token usage)

Case status flow: `uploaded → analyzing → analyzed` (or `failed` with a friendly message).

---

## Common development notes

- **Never modify files in `phoenix/`** — reference material only.
- All public pages include **both** `app/templates/header.php` and `footer.php`.
- Use `db_execute()`, `db_query()`, `db_query_single()` from `app/db.php` — never raw PDO.
- Use `encrypt_setting()` / `decrypt_setting()` (`app/settings.php`) for API keys.
- Use `check_auth()` / `require_login()` / `require_admin()` from `app/auth.php` — they auto-redirect; don't add manual redirects after calling them.
- User files go under `uploads/{user_id}/`, never the root `uploads/` folder.
- The `.htaccess` in `app/`, `logs/`, and `uploads/` are security-critical — keep them.
- The AI analysis must return **exactly 13 sections** matching `app/prompts/appeal_prospect_v5_2.md`.
- PDF extraction intentionally uses the system `pdftotext` command, not a PHP library.

### Environment tuning

- `APP_ENV` / `APP_DEBUG` in `app/config.php` control error display.
- `APP_URL` in `app/config.php` is the canonical base URL (`http://localhost/mvp-legal`).
- `session.cookie_secure` is **not** forced in the root `.htaccess` so local HTTP logins work — `app/auth.php` auto-enables it under HTTPS. Set it manually in `.htaccess` for production HTTPS-only deployments.

---

## Testing

Tests currently exist as **dev-only dependencies**; the `tests/` directory was removed from the repo. Restore from git history if needed:

```bash
git show a427030^:tests/Unit        # etc., or:
git checkout a427030^ -- tests/
```

Then run the suite with the bundled PHPUnit:

```bash
/opt/lampp/bin/php vendor/bin/phpunit --configuration phpunit.xml
```

---

## Roadmap / status

- MVP phases 1–11 complete; testing (phase 12) and final polish (phase 13) outstanding.
- See `TODO.md` for the full checklist and `ANALYSIS_SUMMARY.md` for a code-quality review.

Key open items before production use:

- [ ] Add real OpenAI/Perplexity API keys via admin panel
- [ ] Restore `tests/` (Phase 12) and pass the suite
- [ ] Remove `seed.php` after setup
- [ ] DB indexes/rate-limiting/caching per `ANALYSIS_SUMMARY.md`

---

## License

Internal / private demonstration project. No public license.
