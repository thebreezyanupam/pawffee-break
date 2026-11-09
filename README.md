# Pawffee Break

A WordPress site I designed and built for **Pawffee Break**, a fictional cat & dog café in Hamilton, Ontario, where guests can have a coffee with adoptable pets and maybe take one home. It's a block theme (a child of Twenty Twenty-Five) with an editorial design system, a hand-written motion layer and a custom adoptions plugin built on Advanced Custom Fields, and a set of plugin-powered pages: a journal, jobs, a gallery, a contact form and a map.

**[▶ Open the live demo](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/thebreezyanupam/pawffee-break/main/blueprint.json)**: a full WordPress site with the theme, plugins and content installed, running in your browser through WordPress Playground. It takes about 20 seconds to start, and you're logged in as admin so you can look around wp-admin too. The demo is built from [`blueprint.json`](blueprint.json), so it always runs the latest code on `main`.

> The café, its pets, staff, address and copy are made up, and the photos are AI-generated (one puppy photo is from Pexels). The theme code, design and build are mine.

## What's inside

- **Editorial block theme**: a child of Twenty Twenty-Five with its own design tokens (paper, espresso and crema), Fraunces / Manrope / Fira Code type, and custom header, footer, page and journal templates
- **Motion layer in plain JavaScript**, with no libraries: a first-visit intro, two-panel page transitions that show the page you're going to, split-word headlines, scroll-driven image reveals, a scroll-lit manifesto, parallax, count-up stats, magnetic buttons and a custom cursor
- **Live open/closed status** in the header, worked out from the café's hours in Hamilton's time zone, with a "closing soon" state and today's row highlighted on the Hours page
- **Adoptions plugin with Advanced Custom Fields** ([`plugins/pawffee-adoptions`](plugins/pawffee-adoptions)): the adoption profiles were four pets laid out by hand; they're now a **Pet custom post type** with an **ACF field group** (two tabs, conditional logic, required fields, staff instructions) stored as version-controlled local JSON. Two **server-rendered blocks** (an adoption list and a pet profile) and a **plugin-registered Single Pet template** rebuild the page from that data in the original design. Pending pets show a waitlist button; adopted pets leave the list but keep their page. The dashboard gets photo and status columns, a status filter and built-in **Help tabs**, and there's a [staff editing guide](plugins/pawffee-adoptions/docs/EDITING-GUIDE.md)
- **Plugin-powered pages**, all styled to match: Contact Form 7 (contact), FooGallery (gallery) and WP Job Manager (careers). The location map uses WP Go Maps on the original site and a key-free OpenStreetMap embed in the demo
- **Performance**: images served as WebP (about 90% smaller than the originals), YouTube-style embeds swapped for click-to-play previews, WP-Cron moved off the page-render path, pages prefetched on hover, and below-the-fold sections skipped until needed
- **Accessibility**: **Lighthouse Accessibility 100 on every page** (and SEO 100). Real headings, focus styles on every surface, labelled controls, small text at WCAG AA contrast, screen readers hear each animated headline once from a hidden copy, and full support for reduced motion. With it on, the intro, transitions and scroll effects switch off and everything stays readable
- **One-call demo installer** (`inc/demo-content.php`): builds every page, story, job, gallery, form and photo from the bundle in `demo/`

**Built with:** PHP, WordPress 6.7+ (block theme, theme.json, dynamic blocks, block templates), Advanced Custom Fields, JavaScript, CSS

## Install

1. In WordPress, install and activate the plugins: **Advanced Custom Fields** (free or PRO), **Contact Form 7**, **FooGallery** and **WP Job Manager**. Twenty Twenty-Five (the parent theme) ships with WordPress.
2. On this page, click **Code > Download ZIP**, then go to **Appearance > Themes > Add New > Upload Theme**, choose the ZIP and **Activate**.
3. From the same ZIP, copy the `plugins/pawffee-adoptions` folder into `wp-content/plugins/`, then activate **Pawffee Adoptions** under **Plugins**.
4. Set **Settings > Permalinks** to "Post name".
5. Optional: to load the demo content, run the two installers once, for example with WP-CLI: `wp eval "pawffee_seed_demo(); pawffee_adoptions_seed();"`. Each only runs once and won't touch an install where it has already run.

## Editing the site

| What | Where |
|---|---|
| Page text and layout | Pages, in the block editor |
| Adoptable pets | **Pets** (profile fields below the editor; see the [staff guide](plugins/pawffee-adoptions/docs/EDITING-GUIDE.md)) |
| Journal posts | Posts (they appear on Brewed Stories) |
| Job listings | Job Listings |
| Gallery photos | FooGallery |
| Contact form fields | Contact |
| Map location | The map block on the Contact page |
| Header, footer and navigation | Appearance > Editor > Patterns > Template Parts |
| Opening hours used by the live status | `assets/js/pawffee.js`, `initLiveStatus()` |

## Code map

- `style.css`: design tokens and every component style, in numbered sections.
- `theme.json`: palette, type scale, spacing and block defaults.
- `assets/js/pawffee.js`: the motion layer, transitions, live status and video previews.
- `functions.php`: asset loading, the WebP swap, sub-directory link handling, cron deferral and other performance tweaks.
- `parts/`: header and footer. `templates/`: page and journal layouts.
- `inc/demo-content.php` and `demo/`: the demo installer, its content and photos.
- `plugins/pawffee-adoptions/`: the adoptions plugin (post type, ACF field group, blocks, template, admin tools). See its [README](plugins/pawffee-adoptions/README.md).

## License

Copyright (c) 2026 Anupam Pandey. All rights reserved. The code is public to view and evaluate, not to reuse. See [LICENSE](LICENSE). Photos and fonts keep their own terms, listed in [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
