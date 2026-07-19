# Acceptance and launch testing

## Clean activation

- Install on a clean supported WordPress version using PHP 8.2+.
- Activate WMA Core, then the theme, with WordPress debug logging enabled.
- Confirm there are no fatal errors, warnings or deprecated notices.
- Save permalinks and visit home, tours, one tour, one taxonomy, blog, contact, quote and 404 pages.

## Administration

- Create/edit a tour with gallery, itinerary, budget, advice, FAQs and related trips.
- Confirm empty optional fields leave no blank sections.
- Run starter-content creation once, verify it does not duplicate, then remove only setup-tracked content.
- Confirm Travel Manager can manage intended content/enquiries but not site-wide administrator settings.

## Enquiries

- Submit valid English, French and Arabic requests.
- Confirm selected tour, consent, source, language and traveller data are stored privately.
- Confirm admin/visitor emails, workflow statuses, notes and protected CSV export.
- Test missing required fields, invalid nonce, honeypot, rapid submissions and duplicate submissions.
- Confirm enquiries are absent from public REST, search, feeds and sitemaps.

## Frontend

- Test 320, 375, 768, 1024, 1440 and 1920 pixel widths.
- Test keyboard-only navigation, skip link, menu, filters, gallery, itinerary, FAQs and forms.
- Test 200% zoom, visible focus, reduced motion, image alternative text and color contrast.
- Test Arabic right-to-left pages at all breakpoints.
- Run Lighthouse on representative mobile pages and resolve material accessibility, SEO and performance failures.

## Production

- Confirm HTTPS, canonical URLs, Polylang URLs/menus, sitemap, robots policy and structured data.
- Confirm SMTP, SPF, DKIM and DMARC.
- Confirm cache exclusions for logged-in/admin pages and successful form redirects.
- Take independent file/database backups and record the rollback point.
