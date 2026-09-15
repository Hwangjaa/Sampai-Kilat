# SampaiKilat — Design System

Single source of truth for the interface rebuild. Every page must follow this
document; the shared stylesheet `css/site-system.css` implements it.

## 1. Direction

| | |
|---|---|
| Product | SampaiKilat — courier service: tracking, rates, coverage, staff operations |
| Audience | Indonesian senders/receivers on phones in the field, plus warehouse staff on desktop |
| Promise | "Paket jalan. Anda tenang." — the differentiator is *readable status*, not speed claims |
| Personality | Operational and precise. Like a logistics control panel a customer is allowed to see |
| Mood | Calm surfaces, strong navy structure, one yellow signal colour used only for attention |
| Anti-goal | Not a startup landing page. No gradient blobs, no fake testimonials, no invented statistics |

## 2. Tokens (already in `css/site-system.css`)

- Colour: `--navy-900` structure/dark sections · `--blue-600` the only interactive colour ·
  `--signal` (#ffc61a) attention, only on dark or as an accent fill with navy text ·
  `--paper` page background · `--line` hairlines.
  Status colour pairs: `--success/-bg`, `--warn/-bg`, `--danger/-bg`, `--info/-bg`.
- Type roles:
  - `--font-display` (Archivo) → all headings, buttons, nav, labels, table headers, badges.
  - `--font-body` (system stack) → paragraphs, inputs, table cells.
  - `--font-mono` (Plex Mono, class `.mono`) → tracking numbers, timestamps, codes, IDs only.
- Sizes/spacing/radius/shadow: use the `--fs-*`, `--sp-*`, `--radius*`, `--shadow-*` variables.
  Do not invent new pixel values.
- Motion: `--dur-1` (120ms) for state, `--dur-2` (200ms) for reveals, `--dur-3` (320ms) for entry.
  Everything must stay inside `prefers-reduced-motion` handling that the system already provides.

## 3. Non-negotiable rules

1. Language is Indonesian everywhere. No "Home", "About Us", "Help" in the UI.
2. No inline `<script>`, no `onclick=` / `on*=` attributes, no `<style>` blocks and no inline
   `style=` attributes (the CSP only allows same-origin scripts). Put page logic in
   `javascript/pages/<page>.js` and wire it from there.
3. No dead controls: no `href="#"`, no button without behaviour, no "coming soon".
   If a feature has no backend, either remove it or make it genuinely work through
   `mailto:` / `tel:`. Never fake a success message.
4. Phone display text is `(021) 222 2222` and its href must be `tel:+62212222222`.
   Email is `sampai@kilat.co.id`.
5. Every page carries: skip link, `.site-header` (public) or `.app-bar` (staff), `<main id="utama">`,
   `.site-footer`, and `javascript/site.js` with `defer`.
6. Status is never communicated by colour alone — always colour + text label.
7. Empty, error and success states are required wherever data can be absent or an action can fail.
8. One `<h1>` per page. Heading order never skips a level.
9. `aria-current="page"` on the nav link of the page being viewed.

## 4. Page shell (public pages)

```html
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="…">
  <title>Halaman | SampaiKilat</title>
  <link rel="icon" href="../../assets/brand/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="../../css/site-system.css">
  <link rel="stylesheet" href="../../css/<page>.css">
</head>
<body>
<a class="skip-link" href="#utama">Lewati ke isi</a>
[HEADER]
<main id="utama"> … </main>
[FOOTER]
<script src="../../javascript/site.js" defer></script>
<script src="../../javascript/pages/<page>.js" defer></script>
</body>
</html>
```

## 5. Header (public) — copy exactly, only `aria-current` moves

```html
<header class="site-header">
  <div class="container site-header__inner">
    <a class="brand" href="../../homepage.html">
      <svg class="brand__mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 2 4 13.2h5.6L9.4 22 19 10.8h-5.6z"/></svg>
      <span class="brand__text">Sampai<em>Kilat</em></span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Buka menu" data-nav-toggle>
      <span class="nav-toggle__bars" aria-hidden="true"></span><span class="nav-toggle__label">Menu</span>
    </button>
    <nav class="site-nav" id="site-nav" aria-label="Navigasi utama">
      <a href="../../homepage.html">Beranda</a>
      <a href="../cekresi/cekresi.php">Cek Resi</a>
      <a href="../cektarif/cektarif.html">Cek Tarif</a>
      <a href="../ceklokasi/CekLokasi.html">Lokasi</a>
      <a href="../aboutus/AboutUs.html">Tentang Kami</a>
      <a href="../help/Help.html">Bantuan</a>
      <a class="btn btn--sm site-nav__cta" href="../login/loginpage.html">Masuk staf</a>
    </nav>
  </div>
</header>
```

Breadcrumb on every page except the homepage, directly under `<main id="utama">` inside the container:

```html
<nav aria-label="Remah roti">
  <ol class="breadcrumb"><li><a href="../../homepage.html">Beranda</a></li><li aria-current="page">Cek Tarif</li></ol>
</nav>
```

## 6. Footer (public)

```html
<footer class="site-footer">
  <div class="container site-footer__grid">
    <div class="site-footer__brand">
      <a class="brand" href="../../homepage.html">
        <svg class="brand__mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 2 4 13.2h5.6L9.4 22 19 10.8h-5.6z"/></svg>
        <span class="brand__text">Sampai<em>Kilat</em></span>
      </a>
      <p>Paket jalan. Anda tenang. Layanan pengiriman domestik dengan status yang bisa dibaca.</p>
    </div>
    <nav class="site-footer__col" aria-labelledby="foot-layanan">
      <h2 id="foot-layanan">Layanan</h2>
      <a href="../cekresi/cekresi.php">Cek resi</a>
      <a href="../cektarif/cektarif.html">Cek tarif</a>
      <a href="../ceklokasi/CekLokasi.html">Cakupan lokasi</a>
    </nav>
    <nav class="site-footer__col" aria-labelledby="foot-dukungan">
      <h2 id="foot-dukungan">Dukungan</h2>
      <a href="../help/Help.html">Bantuan dan komplain</a>
      <a href="../rules/RulesPage.html">Larangan pengiriman</a>
      <a href="../aboutus/AboutUs.html">Tentang kami</a>
    </nav>
    <div class="site-footer__col">
      <h2 id="foot-kontak">Hubungi</h2>
      <a href="tel:+62212222222">(021) 222 2222</a>
      <a href="mailto:sampai@kilat.co.id">sampai@kilat.co.id</a>
      <span>Senin–Minggu, 24 jam</span>
    </div>
  </div>
  <div class="container site-footer__base">
    <p>© 2026 SampaiKilat. Proyek akademik Secure Programming, BINUS University.</p>
    <p>Jakarta · Tangerang · Depok · Bekasi · Bogor</p>
  </div>
</footer>
```

## 7. Staff app bar (`css/internal.css` pages)

```html
<header class="app-bar">
  <div class="container app-bar__inner">
    <a class="brand" href="../homepageAS/HomePageAdminStaff.php">
      <svg class="brand__mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 2 4 13.2h5.6L9.4 22 19 10.8h-5.6z"/></svg>
      <span class="brand__text">Sampai<em>Kilat</em></span>
    </a>
    <nav class="app-nav" aria-label="Navigasi staf">
      <a href="../homepageAS/HomePageAdminStaff.php">Dashboard</a>
      <a href="../createDelivery/Create1.php">Buat pengiriman</a>
      <a href="../updatingDelivery/Update3.php">Update lokasi</a>
      <a href="../mendaftarPelanggan/pelanggan.php">Daftar pelanggan</a>
    </nav>
    <div class="app-user">
      <span class="app-user__name"><span class="app-user__avatar" aria-hidden="true">AP</span><?= e($_SESSION['username']) ?></span>
      <form method="post" action="../../controller/login/logout.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="btn btn--ghost btn--sm" type="submit">Keluar</button>
      </form>
    </div>
  </div>
</header>
```

Staff pages use `css/site-system.css` + `css/internal.css` only, wrap content in
`<main id="utama" class="container app-main">` and end with `<script src="../../javascript/site.js" defer>`.

## 8. Components (markup contract)

Button:
```html
<button class="btn btn--primary" type="submit"><span class="btn__spinner" aria-hidden="true"></span><span class="btn__label">Kirim</span></button>
<a class="btn btn--secondary" href="…">Batal</a>
```
Variants: `btn--primary` (default), `btn--secondary`, `btn--ghost`, `btn--signal`, `btn--danger`,
sizes `btn--sm`, `btn--lg`, `btn--block`. `aria-busy="true"` shows the spinner.

Field (always label + optional help + error slot):
```html
<div class="field">
  <label class="field__label" for="resi">Nomor resi</label>
  <input class="input input--mono" id="resi" name="nomor_resi" data-resi-input required
         pattern="RS-[0-9]{7}" maxlength="10" aria-describedby="resi-help">
  <p class="field__help" id="resi-help">Format RS diikuti 7 angka, contoh RS-0000001.</p>
  <p class="field__error" data-error-for="resi" role="alert"></p>
</div>
```
Forms that need client-side validation get `data-validate`; put an empty
`<div data-form-alert hidden></div>` as the first child. Page JS calls `SK.initFilter(...)`,
`SK.toast(...)`, `SK.validateField(input)`, `SK.recentResi.*`.

Feedback: `<p class="alert alert--success" role="status">…</p>` variants
`alert--success|warn|danger`, optionally with `<strong class="alert__title">`.

Status badge: `<span class="badge badge--transit"><span class="badge__dot"></span>Dalam perjalanan</span>`
with `badge--created` (Menunggu diproses), `badge--transit` (Dalam perjalanan), `badge--delivered` (Sampai).

Timeline: `.timeline > .timeline__item > .timeline__marker + .timeline__body(.timeline__label, .timeline__meta)`,
newest first, first item gets `timeline__item--current`.

Table (stacks into cards under 820px, so every `<td>` needs `data-label`):
```html
<div class="table-wrap"><table class="table table--stack">
  <thead><tr><th scope="col">Resi</th>…</tr></thead>
  <tbody><tr><td data-label="Resi" class="cell-resi">RS-0000001</td>…</tr></tbody>
</table></div>
```

Empty state: `.empty-state` with `.empty-state__icon`, `<h3>`, `<p>`, optional `.btn`.
Steps: `.steps > .steps__item[aria-current="step"]` containing `.steps__num` and a label.
Dialog: `<dialog class="modal">` with `.modal__head` (`h2` + `button.modal__close[data-close-dialog]`),
`.modal__body`, `.modal__foot`. Open with `data-open-dialog="<id>"`, never `onclick`.
Progress: `.progress > .progress__bar` (add `progress--done` when delivered).

## 9. File ownership

| Area | Files |
|---|---|
| Design system | `css/site-system.css`, `css/internal.css`, `javascript/site.js` |
| Public page CSS | `css/<page>/<Name>.css` — page-specific layout only, no re-declared tokens/components |
| Page JS | `javascript/pages/<page>.js` |
| Brand assets | `assets/brand/` |

## 10. Verification hooks

Every page must satisfy, at 320 / 375 / 390 / 430 / 768 / 1280 / 1600 px:
`document.documentElement.scrollWidth <= clientWidth` (no horizontal scroll), one visible `h1`,
working mobile drawer, keyboard-reachable controls, visible focus ring, and no console errors.
