# TicketShield

TicketShield is an event ticket booking application built with Laravel, React, Inertia, and Tailwind CSS.

## Login Configuration

Login uses the Google reCAPTCHA v2 checkbox. Configure `GOOGLE_RECAPTCHA_SITE_KEY` and `GOOGLE_RECAPTCHA_SECRET_KEY` in `.env` with keys for your domain before logging in.

Successful logins and account registrations are recorded in the `auth_audit_logs` table in the application's configured database.

## Screenshots

![TicketShield event listing](/public/screenshot/img-1.png)

![TicketShield event details](/public/screenshot/img-2.png)

![TicketShield booking interface](/public/screenshot/img-3.jpg)
