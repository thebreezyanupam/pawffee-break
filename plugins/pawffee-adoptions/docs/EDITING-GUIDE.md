# Pawffee Break: looking after the adoption pages

A guide for café staff. You don't need to know any code. Everything here
happens in the WordPress dashboard, under **Pets** in the left-hand menu.

The same steps are built into the dashboard: on any Pets screen, click
**Help** in the top-right corner.

---

## Adding a new pet

1. Go to **Pets › Add pet**.
2. Type the pet's name where it says **Add title**. For a bonded pair, write
   both names with an ampersand: `Milo & Mango`.
3. In the editor, write **their story**: how they came to the café, their
   quirks, and the kind of home they need. A few short paragraphs is plenty.
   This appears under "Their story" on the pet's own page.
4. Scroll down to **Pet profile**. Fill in the **Profile** tab:
   - **Species** and **Sex**: click the buttons.
   - **Tagline**: a few words shown above the name, such as *Café favourite*.
   - **Breed** and **Age**: for babies, choose *Months* instead of *Years*.
   - **Photo**: see [Photos](#photos) below.
   - **Summary**: two or three sentences for the Adoption page.
   - **Good to know**: the kind of home they'd do best in. It's shown in a
     highlighted note.
5. Open the **Adoption & care** tab:
   - **Adoption status**: leave it on *Available* for a new pet.
   - **Good with**: tick kids, dogs and/or cats.
   - **Health**: switch on each step that's done.
   - **Adoption fee**: leave empty to hide it.
6. In the right-hand sidebar, open **Page Attributes** and set **Order**.
   Lower numbers show first on the Adoption page.
7. Click **Publish**. The pet appears on the Adoption page straight away.

Fields marked with a red * are required. WordPress won't publish until
they're filled in, and it highlights anything missing.

## When someone applies to adopt

Open the pet, go to **Adoption & care**, and set **Adoption status** to
**Adoption pending**, then **Update**.

The pet stays on the Adoption page, marked *Adoption pending*, and the
main button changes to **Join the waitlist**, so other people can still
register interest in case the match doesn't work out.

## When a pet goes home

Set **Adoption status** to **Adopted**. A new **Adopted on** box appears.
Pick the date, then **Update**.

The pet comes off the Adoption page, but their own page stays online,
so links people have shared still work. It shows *Adopted* and the date.

To see everyone at a stage, use the **All statuses** filter above the
Pets list.

## Photos

- **Shape:** use a portrait photo (taller than it is wide), at least 600
  pixels wide. The site crops it into an arch, so keep the pet's face in
  the upper middle.
- **Format:** JPG, PNG or WebP. The site makes a smaller WebP copy
  automatically, so there's no need to resize first.
- **Alternative text (important):** after choosing the photo, fill in
  **Alternative Text** in the Media Library panel. Describe the pet so
  someone using a screen reader knows who they're meeting:
  - ✅ *Benny, a yellow labrador mix, lounging on a rug in the café*
  - ❌ *image of dog* / *IMG_2041.jpg* / leaving it empty

  This is part of keeping the site accessible under Ontario's AODA.

## Changing the order on the Adoption page

Open each pet and change **Order** under **Page Attributes**. The Pets list
in the dashboard shows the same order, in the **Order** column.

## Something looks wrong?

| What you see | What to do |
|---|---|
| A pet isn't on the Adoption page | Check it's **Published** (not Draft) and the status isn't *Adopted*. |
| The **Pet profile** boxes are missing | Click the **Meta Boxes** bar at the bottom of the editor to open them. |
| A yellow notice about Advanced Custom Fields | The ACF plugin has been switched off. Ask whoever manages the site to turn it back on. Pets still show on the site in the meantime. |
| The photo looks badly cropped | Use a taller photo, with the pet near the top-centre. |
