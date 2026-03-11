<div align="center">

# Royal SMTP

**Simple, reliable SMTP and transactional email for WordPress**

[![WordPress](https://img.shields.io/badge/WordPress-5.9+-21759B?style=flat-square&logo=wordpress)](https://wordpress.org/plugins/royal-smtp/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-blue?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-C9A227?style=flat-square)](https://wordpress.org/plugins/royal-smtp/)

[Download on WordPress.org](https://wordpress.org/plugins/royal-smtp/) · [Documentation](https://royalplugins.com/royal-smtp/) · [Royal Plugins](https://royalplugins.com)

</div>

---

Royal SMTP is a lightweight email plugin that ensures your WordPress emails are delivered reliably. Connect to SendGrid, Brevo, Mailgun, Resend, SMTP.com, or any custom SMTP server — including Gmail and Google Workspace.

WordPress uses PHP `mail()` by default, which most hosts block or throttle. Royal SMTP routes your emails through a proper mail service so contact forms, password resets, and WooCommerce order notifications actually reach the inbox.

## Supported Mailers

| Provider | Type |
|----------|------|
| **SendGrid** | Twilio's email API |
| **Brevo** | Formerly Sendinblue — all-in-one marketing platform |
| **Mailgun** | Email APIs for developers |
| **Resend** | Modern developer-first email |
| **SMTP.com** | Enterprise email delivery |
| **Custom SMTP** | Any SMTP server including Gmail, Outlook, Google Workspace |

## Features

- **One-minute setup** — Select your mailer, paste your API key, done
- **From Email & Name** — Set and force a consistent sender identity
- **Test email** — Verify your configuration before going live
- **Email logging** — Keep a record of every email sent, with configurable retention
- **Secure storage** — Credentials stored safely in your WordPress database
- **Lightweight** — No bloat, no upsells, no tracking

## Quick Start

1. Install from [WordPress.org](https://wordpress.org/plugins/royal-smtp/) or upload the zip
2. Go to **Royal SMTP > Settings** in your WordPress admin
3. Select your mailer and enter your API key
4. Send a test email to confirm delivery

### Gmail / Google Workspace Setup

Select **Other SMTP** and use these settings:

| Setting | Value |
|---------|-------|
| SMTP Host | `smtp.gmail.com` |
| SMTP Port | `587` |
| Encryption | TLS |
| Username | Your Gmail address |
| Password | [App Password](https://myaccount.google.com/apppasswords) (not your regular password) |

## Requirements

- WordPress 5.9+
- PHP 7.4+

## Installation

### From WordPress.org (Recommended)
1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "Royal SMTP"
3. Click **Install Now**, then **Activate**

### Manual Installation
1. Download the latest release from [WordPress.org](https://wordpress.org/plugins/royal-smtp/)
2. Upload to `/wp-content/plugins/royal-smtp/`
3. Activate through the **Plugins** menu

## About Royal Plugins

Royal SMTP is built by [Royal Plugins](https://royalplugins.com) — lightweight, security-first WordPress plugins built with clean code. Every plugin undergoes multi-engine security analysis before release.

Check out our other plugins:
- [GuardPress](https://royalplugins.com/guardpress/) — WordPress security hardening
- [SEObolt](https://seobolt.io) — Technical SEO toolkit
- [SiteVault](https://royalplugins.com/sitevault/) — Automated backups
- [Royal MCP](https://github.com/royalplugins/royal-mcp) — AI platform integration for WordPress

## License

Royal SMTP is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
