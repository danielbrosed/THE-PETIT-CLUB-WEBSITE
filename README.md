# The Petit Club · Little Explorers Barcelona

Website for **The Petit Club**, tailor-made children's experiences for birthdays, weddings and events in Barcelona.

Production: [thepetitclubbyblanca.com](https://thepetitclubbyblanca.com)

## Stack

- Single-page site with no build step: `index.html` is rendered in the browser by the `support.js` runtime.
- Contact form backed by `enviar.php`, which sends each enquiry over authenticated SMTP with PHPMailer.
  Spam protection: honeypot field, minimum fill time and per-IP rate limiting.
- `.htaccess` sets security headers and image caching, and blocks web access to configuration,
  documentation, the source file, the `.git` folder and the mail library.

## Structure

| Path | Purpose |
| --- | --- |
| `index.html` | Published page |
| `The Petit Club.dc.html` | Editable source; `index.html` is an exact copy of it |
| `support.js` | Runtime that renders the page |
| `enviar.php` | Contact form endpoint |
| `php/phpmailer/` | PHPMailer (bundled, no Composer needed) |
| `assets/img/` | Photography optimised for the web |
| `assets/icons/` | Brand iconography |
| `config.sample.php` | Template for the server-only SMTP configuration |
| `robots.txt`, `sitemap.xml` | Search engine files |

## Deployment (Hostinger)

The repository root is the web root, so the `main` branch deploys as-is through Hostinger's Git
integration.

1. **Empty `public_html`** in hPanel's File Manager (remove Hostinger's default page).
2. **Connect the repository** in hPanel's Git deployment: repository
   `danielbrosed/THE-PETIT-CLUB-WEBSITE`, branch `main`, installed in `public_html`. Enable automatic
   deployment so every push to `main` goes live.
3. **Upload the SMTP configuration once.** Copy `config.sample.php` to `config.php`, fill in the
   mailbox password and the address that receives enquiries, and upload it to the folder that
   *contains* `public_html` (one level above the web root). It is never committed and deployments
   never overwrite it. `enviar.php` also accepts it inside `public_html`, where `.htaccess` blocks it.
4. **Create the `info@thepetitclubbyblanca.com` mailbox** in hPanel → Emails. The form sends through
   it; if SMTP fails, visitors fall back to a pre-filled email.

## Contact

- WhatsApp / phone: +34 633 948 883
- Email: info@thepetitclubbyblanca.com
- Instagram: [@thepetitclubbyblanca](https://instagram.com/thepetitclubbyblanca)
