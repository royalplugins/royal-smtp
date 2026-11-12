=== Royal SMTP ===
Contributors: royalpluginsteam
Tags: smtp, email, sendgrid, brevo, mailgun
Requires at least: 5.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple, reliable SMTP and transactional email for WordPress. Supports SendGrid, Brevo, Mailgun, Resend, SMTP.com, and custom SMTP.

== Description ==

Royal SMTP is a lightweight, simple-to-configure email plugin that ensures your WordPress emails are delivered reliably.

= Supported Mailers =

* **SendGrid** - Twilio's powerful email API
* **Brevo** (formerly Sendinblue) - All-in-one marketing platform
* **Mailgun** - Email APIs for developers
* **Resend** - Modern email for developers
* **SMTP.com** - Enterprise email delivery
* **Other SMTP** - Any SMTP server including Gmail

= Features =

* Easy setup with clear configuration options
* From Email and From Name settings with force options
* Test email functionality to verify configuration
* Email logging with configurable retention
* Clean, modern admin interface
* Secure credential storage

= Gmail / Google Workspace =

To use Gmail or Google Workspace, select "Other SMTP" and configure with these settings:

* SMTP Host: smtp.gmail.com
* SMTP Port: 587
* Encryption: TLS
* Username: Your Gmail address
* Password: App Password (create one at myaccount.google.com/apppasswords)

== Installation ==

1. Upload the `royal-smtp` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Royal SMTP > Settings to configure your mailer
4. Send a test email to verify everything is working

== Frequently Asked Questions ==

= Which mailer should I use? =

For most users, we recommend SendGrid, Brevo, or Mailgun as they offer free tiers and reliable delivery. If you already have SMTP credentials from your hosting provider or Google Workspace, use the "Other SMTP" option.

= My test email failed. What should I do? =

1. Double-check your API key or SMTP credentials
2. Ensure your From Email matches a verified sender in your email service
3. For API mailers, verify your account is not in sandbox mode
4. For SMTP, check that your hosting provider allows outgoing SMTP connections

= Are my credentials secure? =

Yes, all sensitive credentials are stored in your WordPress database using WordPress's standard options system. We recommend using strong, unique API keys and never sharing your credentials.

== Screenshots ==

1. Settings page with mailer selection
2. Email log showing sent and failed emails

== Changelog ==

= 1.0.1 =
* New: Delete Data on Uninstall setting controls whether settings and the email log are removed when the plugin is deleted.
* Fix: API mailers now send custom email headers and respect the HTML email format.

= 1.0.0 =
* Initial release
* Support for SendGrid, Brevo, Mailgun, Resend, SMTP.com
* Custom SMTP support (Gmail compatible)
* Email logging with configurable retention
* Test email functionality

== Upgrade Notice ==

= 1.0.1 =
Fixes custom email headers and HTML emails on API mailers.

= 1.0.0 =
Initial release of Royal SMTP. Reliable transactional email delivery for WordPress.
