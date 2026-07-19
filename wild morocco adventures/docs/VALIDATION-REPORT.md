# Validation report

Validation was run on 15 July 2026 in a clean WordPress Playground installation using WordPress 7.0.1 and PHP 8.2.31. The production target remains WordPress 6.5+ and PHP 8.2+.

## Passed

- WMA Core and the custom theme activated together with WordPress debugging enabled and no PHP warning, deprecated notice or fatal error appeared on tested pages.
- The tour post type, taxonomies, structured metaboxes, repeater fields and Travel Manager capabilities registered correctly.
- The removable setup tool created pages, primary and legal menus, described taxonomy terms, eight FAQs, three articles, four responsive media-library images and three complete sample journeys; a second run was prevented.
- Removing setup-generated content deleted only tracked starter items in the clean-install test. Test enquiries were retained.
- Tour archives, taxonomy routes, region, style and duration filtering, sorting, clear-filter links and individual tour routes responded correctly.
- Optional tour sections disappeared when their fields were empty.
- A quote request was validated, accepted, stored as a private enquiry, associated with its selected tour and shown with the `New` workflow state.
- Polylang was installed in the disposable test site and English, French and Arabic were configured. Arabic produced `lang="ar"`, `dir="rtl"`, the theme RTL stylesheet loaded once, and the tested layout had no horizontal overflow.
- Visual checks covered 375, 1024, 1200 and 1440 pixel viewports. The mobile menu, mobile quote action and desktop hero/tour layouts were inspected.
- Browser console checks found no JavaScript errors or warnings during the tested flow.
- The enhanced local preview returned HTTP 200 for the homepage, archive, flagship eight-day journey, quote page and FAQ page. The flagship journey rendered its gallery, itinerary, accommodation, budget, practical advice and tour FAQs without PHP warnings or notices.

## Production checks that require client infrastructure

- Deliverability of administrator and visitor emails must be verified after authenticated SMTP, SPF, DKIM and DMARC are configured on staging. The local test exercised the `wp_mail()` notification path but did not have production mail credentials.
- Final French and Arabic content, interface translations, legal text, prices, contact details and claims require client approval and professional review.
- Lighthouse, external schema validation and a full assistive-technology audit should be repeated against the final public domain after licensed media, cache rules and production plugins are installed.
- Backup restore and staging-to-production rollback must be rehearsed inside the client's Cap Connect account because those facilities are hosting-account specific.
