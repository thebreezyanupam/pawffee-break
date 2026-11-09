# Pawffee Adoptions

A WordPress plugin that turns the Pawffee Break café's adoptable pets into
structured content. Staff fill in a form for each pet; the Adoption page,
every pet's profile page and the dashboard list are all built from it.

Before this plugin, the Adoption page was four pets laid out by hand in the
block editor. Changing a pet's age meant editing layout markup. Now each pet
is a **custom post type** with an **Advanced Custom Fields** profile, and the
page renders itself from that data in the same design.

## What it does

- **Pet post type** (`pawffee_pet`) at `/pets/{name}/`, registered in a
  plugin rather than the theme, so the café keeps its pets through a redesign
- **ACF field group, "Pet profile"**, stored as local JSON in
  [`acf-json/`](acf-json/group_pawffee_pet.json) so it's version-controlled
  with the code; edits made in the ACF UI write back to the same file
  - Two tabs (Profile, Adoption & care); button groups, select, number,
    image, textarea, checkbox, date picker, true/false and group fields
  - Conditional logic: **Adopted on** appears only when status is *Adopted*
  - Required fields, length limits and editor instructions on every field
- **Adoptable pets block** (`pawffee/pet-list`): lists pets in the theme's
  existing layout. Sidebar settings filter by species and choose whether
  pending and adopted pets show
- **Pet profile block** (`pawffee/pet-profile`) and a **Single Pet
  template** registered with `register_block_template()` (WordPress 6.7+),
  using the active theme's header and footer
- **Status-aware output**: pending pets keep their place with a note and a
  *Join the waitlist* button; adopted pets leave the list but keep their page
- **Dashboard tools**: photo, status, species/breed/age and order columns;
  a status filter; and built-in **Help tabs** that teach staff the workflow
- **Staff guide**: [`docs/EDITING-GUIDE.md`](docs/EDITING-GUIDE.md)
- **Meta descriptions**: each pet's summary becomes its page description
- **Works without ACF**: every read falls back to the post meta ACF writes,
  so pages keep rendering if ACF is switched off, with a clear admin notice.
  Works with ACF or ACF PRO

## How it renders

Both blocks are dynamic (rendered in PHP). They build the same core block
markup the page was originally made of (Columns, Image, Heading, Paragraph,
Buttons) and render it with `do_blocks()`. The theme's layout, styles and
motion layer therefore apply unchanged, there is no duplicated CSS, and the
images get WordPress's responsive `srcset`, lazy loading and LCP priority.
Every value is escaped as the markup is built.

The editor side is plain JavaScript with no build step: a
`ServerSideRender` preview plus `InspectorControls` for the list settings.

## Accessibility

- Real heading levels: `h1` on a pet's page, `h2` for each pet in the list
- Alt text required by the workflow and enforced by the demo installer;
  a "Photo of {name}" fallback if it's ever missing
- Status is written in words, never shown by colour alone
- Call-to-action links name the pet (*Book a meet & greet — Benny*) so a
  screen-reader list of links isn't a row of identical buttons
- Lighthouse Accessibility 100 on the Adoption page (desktop and mobile)
  and on every pet page

## Files

```
pawffee-adoptions.php       Plugin header, constants, activation
inc/post-type.php           Pet post type; featured image kept in sync
inc/fields.php              ACF local JSON load/save; missing-ACF notice
inc/pet-data.php            Reads a pet into one array (ACF or meta fallback)
inc/blocks.php              Shared pet markup builder, block registration
inc/templates.php           Single Pet block template
inc/admin.php               Columns, status filter, help tabs
inc/seed.php                Demo installer (the café's four residents)
acf-json/                   The Pet profile field group
blocks/pet-list/            block.json, render.php, editor.js
blocks/pet-profile/         block.json, render.php, editor.js
assets/pets.css             The few styles the theme doesn't provide
docs/EDITING-GUIDE.md       Guide for café staff
```

## Install

1. Install and activate **Advanced Custom Fields** (free or PRO).
2. Copy this folder to `wp-content/plugins/` and activate **Pawffee Adoptions**.
3. Optional demo pets: `wp eval "pawffee_adoptions_seed();"` (runs once).
4. Add the **Adoptable pets** block to any page.

Requires WordPress 6.7+ and PHP 8.0+.
