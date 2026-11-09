# Pawffee Break

A WordPress site I designed and built for **Pawffee Break**, a fictional cat & dog café in Hamilton, Ontario, where guests can have a coffee with adoptable pets and maybe take one home. It's a block theme (a child of Twenty Twenty-Five) with an editorial design system, a hand-written motion layer and a set of plugin-powered pages: adoption profiles, a journal, jobs, a gallery, a contact form and a map.

**[▶ Open the live demo](https://playground.wordpress.net/#%7B%22landingPage%22%3A%22%2F%22%2C%22preferredVersions%22%3A%7B%22php%22%3A%228.3%22%2C%22wp%22%3A%22latest%22%7D%2C%22features%22%3A%7B%22networking%22%3Atrue%7D%2C%22steps%22%3A%5B%7B%22step%22%3A%22login%22%7D%2C%7B%22step%22%3A%22installPlugin%22%2C%22pluginData%22%3A%7B%22resource%22%3A%22wordpress.org%2Fplugins%22%2C%22slug%22%3A%22contact-form-7%22%7D%2C%22options%22%3A%7B%22activate%22%3Atrue%7D%7D%2C%7B%22step%22%3A%22installPlugin%22%2C%22pluginData%22%3A%7B%22resource%22%3A%22wordpress.org%2Fplugins%22%2C%22slug%22%3A%22foogallery%22%7D%2C%22options%22%3A%7B%22activate%22%3Atrue%7D%7D%2C%7B%22step%22%3A%22installPlugin%22%2C%22pluginData%22%3A%7B%22resource%22%3A%22wordpress.org%2Fplugins%22%2C%22slug%22%3A%22wp-job-manager%22%7D%2C%22options%22%3A%7B%22activate%22%3Atrue%7D%7D%2C%7B%22step%22%3A%22installTheme%22%2C%22themeData%22%3A%7B%22resource%22%3A%22git%3Adirectory%22%2C%22url%22%3A%22https%3A%2F%2Fgithub.com%2Fthebreezyanupam%2Fpawffee-break%22%2C%22ref%22%3A%22main%22%2C%22refType%22%3A%22branch%22%7D%2C%22options%22%3A%7B%22activate%22%3Atrue%7D%7D%2C%7B%22step%22%3A%22setSiteOptions%22%2C%22options%22%3A%7B%22permalink_structure%22%3A%22%2F%25postname%25%2F%22%2C%22blogname%22%3A%22Pawffee%20Break%22%2C%22blogdescription%22%3A%22Sip.%20Relax.%20Adopt.%22%7D%7D%2C%7B%22step%22%3A%22runPHP%22%2C%22code%22%3A%22%3C%3Fphp%20require%20%27%2Fwordpress%2Fwp-load.php%27%3B%20if%20%28%20function_exists%28%20%27pawffee_seed_demo%27%20%29%20%29%20%7B%20pawffee_seed_demo%28%29%3B%20%7D%20flush_rewrite_rules%28%29%3B%20%3F%3E%22%7D%5D%7D)**: a full WordPress site with the theme, plugins and content installed, running in your browser through WordPress Playground. It takes about 20 seconds to start, and you're logged in as admin so you can look around wp-admin too.

> The café, its pets, staff, address and copy are made up, and the photos are AI-generated (one puppy photo is from Pexels). The theme code, design and build are mine.

## What's inside

- **Editorial block theme**: a child of Twenty Twenty-Five with its own design tokens (paper, espresso and crema), Fraunces / Manrope / Fira Code type, and custom header, footer, page and journal templates
- **Motion layer in plain JavaScript**, with no libraries: a first-visit intro, two-panel page transitions that show the page you're going to, split-word headlines, scroll-driven image reveals, a scroll-lit manifesto, parallax, count-up stats, magnetic buttons and a custom cursor
- **Live open/closed status** in the header, worked out from the café's hours in Hamilton's time zone, with a "closing soon" state and today's row highlighted on the Hours page
- **Plugin-powered pages**, all styled to match: Contact Form 7 (contact), FooGallery (gallery) and WP Job Manager (careers). The location map uses WP Go Maps on the original site and a key-free OpenStreetMap embed in the demo
- **Performance**: images served as WebP (about 90% smaller than the originals), YouTube-style embeds swapped for click-to-play previews, WP-Cron moved off the page-render path, pages prefetched on hover, and below-the-fold sections skipped until needed
- **Accessibility**: real headings, focus styles on every surface, labelled controls, and full support for reduced motion. With it on, the intro, transitions and scroll effects switch off and everything stays readable
- **One-call demo installer** (`inc/demo-content.php`): builds every page, story, job, gallery, form and photo from the bundle in `demo/`

**Built with:** PHP, WordPress 6.7+ (block theme, theme.json), JavaScript, CSS

## Install

1. In WordPress, install and activate the plugins: **Contact Form 7**, **FooGallery** and **WP Job Manager**. Twenty Twenty-Five (the parent theme) ships with WordPress.
2. On this page, click **Code > Download ZIP**, then go to **Appearance > Themes > Add New > Upload Theme**, choose the ZIP and **Activate**.
3. Set **Settings > Permalinks** to "Post name".
4. Optional: to load the demo content, run `pawffee_seed_demo();` once, for example with WP-CLI: `wp eval "pawffee_seed_demo();"`. It only runs once and won't touch an install where it has already run.

## Editing the site

| What | Where |
|---|---|
| Page text and layout | Pages, in the block editor |
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

## License

Copyright (c) 2026 Anupam Pandey. All rights reserved. The code is public to view and evaluate, not to reuse. See [LICENSE](LICENSE). Photos and fonts keep their own terms, listed in [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
