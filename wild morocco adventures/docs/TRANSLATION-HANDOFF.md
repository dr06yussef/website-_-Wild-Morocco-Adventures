# French, English and Arabic handoff

The theme and WMA Core use WordPress gettext strings, logical CSS properties and an Arabic RTL stylesheet. Polylang manages translated content and URLs.

## Setup

1. Install Polylang and add English (`en`), French (`fr`) and Arabic (`ar`).
2. Choose the client-approved default language. Do not hide the language code until URL policy is approved.
3. Translate the homepage, core pages, tours, regions, travel styles, interests, FAQs, posts and legal documents.
4. Create and assign one primary/footer/legal menu per language.
5. Connect translations in Polylang instead of creating unrelated duplicates.
6. Translate media titles and alternative text where necessary.
7. Review the Arabic site at mobile, tablet and desktop widths.

## Interface strings

The source is translation-ready. Generate/update POT files with WP-CLI (`wp i18n make-pot`) and have a professional translator produce `fr_FR` and `ar` PO/MO files. Do not publish unreviewed automated translations. The enquiry confirmation emails already select an English, French or Arabic draft based on the visitor’s chosen language; review those drafts before launch.

## Acceptance

- Every language has a complete navigation path and quotation page.
- No untranslated starter copy or draft legal wording remains.
- `lang`, `dir`, canonical and `hreflang` output are correct.
- Arabic forms, cards, galleries, itinerary accordions and sticky actions remain readable.
