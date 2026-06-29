# SIGMA — Design System

**UI/UX Reference Document**
Tailwind CSS · Inter · Lucide Icons

---

## Principles

**Clarity over decoration.** Every visual element earns its place by reducing cognitive load, not by adding visual interest. Warehouse operators scan interfaces quickly under time pressure — the system must communicate state instantly.

**High contrast, never soft.** Text that matters is never gray-on-gray. Primary text is `slate-900` (#0f172a) on white — a contrast ratio of 16:1. Status colors (emerald, rose, amber) are always paired with matching backgrounds, never used as pure foreground on white.

**Density with breathing room.** Tables and cards are information-dense by necessity, but internal padding is generous enough that nothing feels cramped. The system targets trained users, not first-time visitors — tight information layout is a feature.

**Offline confidence.** No spinner-dependent UX. All JavaScript libraries are bundled locally. The UI never promises something that requires a server round-trip before giving feedback.

---

## Typography

The entire system uses a single font stack. No headings font, no display face — discipline in one typeface, enforced through weight and tracking.

### Font Stack

```css
font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
```

### Global Settings

```css
body {
  -webkit-font-smoothing: antialiased;
  letter-spacing: -0.011em;      /* slightly tighter than default — Inter's natural rhythm */
}

h1, h2, h3 {
  letter-spacing: -0.02em;       /* headings compress further — creates visual weight without bold alone */
}
```

Negative tracking is the primary typographic tool. Inter at `-0.02em` feels editorial; at `-0.011em` it reads as clean and professional without looking designed.

### Scale

| Role | Size | Weight | Tracking | Color | Usage |
|---|---|---|---|---|---|
| Hero H1 | clamp(2.5rem → 3.4rem) | 700 | -0.02em | slate-900 | Landing page hero |
| Section H2 | 1.875rem (30px) | 700 | -0.02em | slate-900 | Section headings |
| Page title | 1.5rem (24px) | 700 | -0.02em | slate-900 | Dashboard greeting |
| Card heading | 1rem (16px) | 600 | -0.02em | slate-900 | Widget titles |
| Eyebrow | 0.875rem (14px) | 600 | 0.05em | blue-600 | Section labels (uppercase) |
| Body | 1rem (16px) | 400 | -0.011em | slate-500 | Descriptive text |
| Body small | 0.875rem (14px) | 400 | -0.011em | slate-600 | Table cells, form text |
| Caption | 0.75rem (12px) | 400 | -0.011em | slate-400 | Metadata, timestamps |
| Table header | 0.6875rem (11px) | 600 | 0.045em | slate-400 | Column labels (uppercase) |
| Mono | font-mono, 0.75rem | 400 | default | slate-600/blue-600 | Barcode, invoice number |

### Key Rules

**Eyebrows are always uppercase + wide-tracked.** The combination of `text-sm font-semibold text-blue-600 uppercase tracking-wider` (tracking ~0.05em) creates a visual anchor that separates sections without a horizontal rule.

**Table headers are micro-labels, not headings.** At 11px with 0.045em tracking and `slate-400` color, they recede behind the data — the data is the signal.

**Monospace for machine-generated strings.** Barcode codes, invoice numbers (`INV-20260629-0007`), and any identifier that a human shouldn't need to read word-by-word uses `font-mono`. Keeps scannable data visually distinct from prose.

---

## Color

Tailwind CSS color palette. No custom color values outside of CSS animations.

### Semantic Palette

| Token | Tailwind | Hex | Role |
|---|---|---|---|
| Primary | `blue-600` | #2563eb | Brand, CTA, active states, links |
| Primary hover | `blue-700` | #1d4ed8 | Hover for primary elements |
| Primary surface | `blue-50` | #eff6ff | Icon backgrounds, active nav bg |
| Success / Masuk | `emerald-600` | #059669 | Incoming stock, positive delta |
| Success surface | `emerald-50` | #ecfdf5 | Icon bg for masuk |
| Danger / Keluar | `rose-600` | #e11d48 | Outgoing stock, errors, void |
| Danger surface | `rose-50` | #fff1f2 | Icon bg for keluar, error alerts |
| Warning / Low stock | `amber-600` | #d97706 | Restock alerts, today's transactions |
| Warning surface | `amber-50` | #fffbeb | Icon bg for warning metrics |
| Admin accent | `violet-600` | #7c3aed | Employee count, special admin KPIs |
| Admin surface | `violet-50` | #f5f3ff | Icon bg for admin metrics |

### Neutral Palette

| Token | Tailwind | Hex | Role |
|---|---|---|---|
| Background (app shell) | `slate-50` | #f8fafc | `<body>` background inside app |
| Background (public) | `white` | #ffffff | Landing page, login |
| Surface (card) | `white` | #ffffff | All cards, panels, modals |
| Border (default) | `slate-200` | #e2e8f0 | Card borders, form inputs |
| Border (muted) | `slate-100` | #f1f5f9 | Table row dividers, section dividers |
| Text (primary) | `slate-900` | #0f172a | Headings, card numbers, labels |
| Text (secondary) | `slate-600` | #475569 | Table body cells |
| Text (muted) | `slate-500` | #64748b | Descriptions, secondary labels |
| Text (placeholder) | `slate-400` | #94a3b8 | Placeholders, table headers, timestamps |
| Sidebar bg | `white` | #ffffff | Sidebar panel |
| Sidebar border | `slate-200` | #e2e8f0 | Sidebar right edge |

### Color Logic

Status colors map directly to business semantics and never cross-contaminate:

- **Emerald** = something came in (positive, more stock)
- **Rose** = something went out (outgoing, errors, void)
- **Amber** = attention required (low stock, today's neutral metrics)
- **Blue** = primary action (the thing to do next)
- **Violet** = admin-scope data (not available to karyawan)

This mapping is consistent from dashboard KPIs to table row icons to flash messages.

---

## Spacing & Layout

### Page Layout

```
┌──────────────────────────────────────────────────┐
│  Sidebar (w-64 / 256px)  │  Main content area    │
│  fixed on mobile,        │  flex-1, min-w-0      │
│  sticky on desktop       │                       │
│                          │  Topbar h-16, sticky  │
│                          │  ────────────────────  │
│                          │  <main> p-4 sm:p-6    │
│                          │         lg:p-8        │
└──────────────────────────────────────────────────┘
```

Max content width for public pages: `max-w-6xl` (1152px), centered.
Max content width for invoice: `max-w-3xl` (768px), centered.

### Spacing Scale (key values)

| Usage | Value |
|---|---|
| Card padding | `p-5` (20px) or `p-6` (24px) |
| Card gap | `gap-5` (20px) |
| Section vertical gap | `mt-5` or `mt-6` (20–24px) |
| Table cell padding | `px-5 py-3.5` |
| Form input padding | `px-4 py-2.5` |
| Button padding | `px-5 py-2.5` or `px-6 py-3` |

---

## Border Radius

The system uses two primary radii. The distinction is intentional:

| Radius | Token | Value | Used for |
|---|---|---|---|
| **Large** | `rounded-2xl` | 16px | Cards, panels, modals, large containers |
| **Medium** | `rounded-xl` | 12px | Buttons, form inputs, icon badges, tags |
| **Small** | `rounded-lg` | 8px | Small icon badges, sidebar active indicators' container |
| **Full** | `rounded-full` | 9999px | Avatars, pills, dot indicators |
| **Minimal** | `rounded` | 4px | Barcode/monospace chip badges |

`rounded-2xl` on containers + `rounded-xl` on interactive elements creates a two-tier hierarchy: the container is "softer" and the interactive elements are "firmer."

---

## Shadows

Used sparingly. Three levels:

| Level | Class | Usage |
|---|---|---|
| Card depth | `shadow-2xl shadow-slate-300/50` | Hero mockup card |
| CTA / primary button | `shadow-lg shadow-blue-600/25` | Blue primary buttons |
| Floating popup | `shadow-xl` | Login floating badge, tooltips |
| Standard card | none (border only) | All dashboard cards |

Dashboard cards use `border border-slate-200/70` without shadow. This keeps the surface flat and reduces visual noise when many cards are on screen simultaneously.

---

## Components

### Stat Card

```
┌────────────────────────────────┐
│  [Icon gradient badge 11×11]   │
│                                │
│  1.240                         │  ← text-3xl font-bold slate-900
│  Jenis Barang · macam produk   │  ← text-sm slate-500 + slate-400
└────────────────────────────────┘
```

- Container: `bg-white border border-slate-200/70 rounded-2xl p-5`
- Icon badge: `w-11 h-11 rounded-xl bg-gradient-to-br from-{color}-500 to-{color}-600 text-white shadow-lg shadow-{color}-500/30`
- Number: `text-3xl font-bold text-slate-900 mt-4`
- Label: `text-sm text-slate-500 mt-1`
- Hover: icon scales to 1.06 via CSS (`transition: transform .25s cubic-bezier(.16,1,.3,1)`)
- Staggered entrance: `nth-child` delays of 0, 70, 140, 210ms

### Sidebar

- Width: `w-64` (256px), white background, right border `border-slate-200`
- Fixed on mobile (slides in/out), sticky on desktop
- Logo row: `h-16`, `border-b border-slate-100`
- Nav items: `px-3 py-2.5 rounded-xl text-[15px] font-medium`
- **Active state:** `bg-blue-50 text-blue-700` + left accent bar: `absolute left-0 w-1 h-6 rounded-r-full bg-blue-600`
- **Inactive state:** `text-slate-600 hover:text-slate-900 hover:bg-slate-100`
- Section labels: `text-[11px] font-semibold uppercase tracking-wider text-slate-400`
- User profile at bottom: avatar (initial letter in blue-600 circle) + name + role + logout link

### Topbar

```css
/* Glass effect */
background: rgba(255, 255, 255, .75);
backdrop-filter: blur(10px);
border-bottom: 1px solid;  /* slate-200 */
```

Height: `h-16`. Sticky. Contains: hamburger (mobile), page title, user greeting + avatar.

### Form Inputs

Base class applied consistently as a PHP variable (`$inp`):
```
w-full rounded-xl bg-slate-50 border border-slate-200
px-4 py-2.5 text-sm outline-none
focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white
transition
```

The `outline-none` + manual `focus:ring-2` pattern gives precise control over the focus ring color and spread without browser default interference. Background shifts from `slate-50` to `white` on focus — a subtle "activation" cue.

### Buttons

**Primary (blue):**
```
inline-flex items-center justify-center gap-2
px-6 py-3 rounded-xl
bg-blue-600 text-white font-semibold
hover:bg-blue-700 transition
shadow-lg shadow-blue-600/25
```

**Secondary / ghost:**
```
px-6 py-3 rounded-xl
text-slate-700 font-semibold
hover:bg-slate-100 transition
```

**Danger (small, in table):**
```
inline-flex items-center gap-1 text-xs
text-slate-500 hover:text-rose-600 transition
```

No outlined button variant. Destructive actions use text-only with hover color change — the low visual weight matches their low frequency of use.

### Flash Messages

```
flex items-center gap-2 rounded-xl px-4 py-3 text-sm border
```

Success: `bg-emerald-50 border-emerald-200 text-emerald-700`
Error: `bg-rose-50 border-rose-200 text-rose-700`

Always prefixed with a Lucide icon (`check-circle` / `alert-circle`).

### Table (`.table-pro`)

Three-zone structure:

```css
/* Header */
font-size: 11px; font-weight: 600;
text-transform: uppercase; letter-spacing: .045em;
color: #94a3b8; /* slate-400 */
border-bottom: 1px solid #e2e8f0;

/* Body cells */
padding: .9rem (vertical);
border-bottom: 1px solid #f1f5f9; /* slate-100 */
color: #475569; /* slate-600 */

/* Footer (totals) */
border-top: 1px solid #e2e8f0;
font-weight: 600; color: #0f172a;
```

Row hover: `background: #f8fafc` (slate-50), `transition: background .15s ease`.

The header uses `slate-100` as divider (not `slate-200`) to be slightly lighter than the card border — the table content appears to float inside its container without fighting the border.

### Modal

- Backdrop: `fixed inset-0 bg-black/40 z-50`
- Panel: `bg-white rounded-2xl w-full max-w-lg shadow-2xl my-8`
- Closes on backdrop click and on explicit close button
- Form content uses standard input classes throughout

### Status Badges (Stock Level)

```php
$stokCls = $stok === 0
    ? 'bg-rose-100 text-rose-700'
    : ($stok <= 5
        ? 'bg-amber-100 text-amber-700'
        : 'bg-emerald-100 text-emerald-700');
```

Class: `inline-block px-2.5 py-1 rounded-lg text-xs font-bold`

Three states, three colors, no ambiguity.

---

## Iconography

**Library:** Lucide Icons (bundled locally as `lucide.min.js`, initialized via `lucide.createIcons()`).

All icons are rendered at `w-4 h-4` (16px) in body text and `w-5 h-5` (20px) in headings and card badges. The consistent sizing means icons are always optically aligned with the text next to them.

Icons are semantic, not decorative:

| Icon | Meaning |
|---|---|
| `package` | Barang / produk (brand icon) |
| `arrow-down-to-line` | Stok masuk (inbound) |
| `arrow-up-from-line` | Stok keluar (outbound) |
| `scan-line` | Barcode scanning |
| `receipt` | Invoice |
| `bar-chart-3` | Laporan / tren |
| `alert-triangle` | Low stock warning |
| `history` | Riwayat transaksi |
| `check` | Success / centang list |
| `rotate-ccw` | Void / batalkan |

Never use icons without accompanying text labels in navigation. Icons alone fail under high cognitive load.

---

## Animations

### Scroll Reveal (Landing Page)

```css
.reveal {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity .7s ease, transform .7s ease;
}
.reveal.show { opacity: 1; transform: translateY(0); }
```

Triggered by `IntersectionObserver` at `threshold: 0.15`. Applied to feature cards and section headings on the landing page only — not inside the application shell.

### Hero Card Float

```css
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-12px); }
}
.float { animation: float 4s ease-in-out infinite; }
```

Applied only to the browser mockup in the landing page hero. Communicates "living product" without distraction.

### Login Panel Transition

Enter (page load):
```css
/* cubic-bezier(.16,1,.3,1) — spring-like ease-out, 850ms */
.anim-left  { animation: slideInLeft  .85s cubic-bezier(.16,1,.3,1) both; }
.anim-right { animation: slideInRight .85s cubic-bezier(.16,1,.3,1) both; }
```

Exit (after successful login):
```css
/* cubic-bezier(.7,0,.84,0) — aggressive ease-in, 700ms */
.exit-left  { animation: slideOutLeft  .7s cubic-bezier(.7,0,.84,0) forwards; }
.exit-right { animation: slideOutRight .7s cubic-bezier(.7,0,.84,0) forwards; }
```

The asymmetric easing is intentional: panels enter slowly (spring) to feel welcoming, and exit fast (aggressive ease-in) so the user reaches their dashboard without waiting.

### Stat Card Hover

```css
.stat-card [class*="gradient"] {
  transition: transform .25s cubic-bezier(.16,1,.3,1);
}
.stat-card:hover [class*="gradient"] { transform: scale(1.06); }
```

Only the icon badge scales — not the entire card. This prevents layout shift and keeps the interaction feeling precise rather than heavy.

### General Transitions

All interactive elements use `transition` (shorthand, ~150ms ease-in-out):
- Nav link hover: bg + text color
- Button hover: bg color
- Input focus: border color + ring + bg
- Table row hover: bg

No `transition-all` is used — Tailwind's `transition` shorthand covers `color, background-color, border-color, box-shadow, opacity, transform` which covers every case needed.

---

## Responsive Behavior

| Breakpoint | Sidebar | Layout | Content |
|---|---|---|---|
| < 1024px (mobile) | Off-screen, toggle via hamburger | Single column | Full-width cards |
| ≥ 1024px (desktop) | Sticky, always visible | Sidebar + content | Grid layouts (2–4 cols) |

Key grid patterns:
- Dashboard KPI: `grid-cols-1 sm:grid-cols-2 xl:grid-cols-4`
- Dashboard charts: `grid-cols-1 lg:grid-cols-3` (2/3 + 1/3)
- Stok keluar form: `grid-cols-1 lg:grid-cols-5` (2 + 3)
- Landing hero: `grid-cols-1 lg:grid-cols-2`

Mobile sidebar uses a dark overlay (`bg-slate-900/40`) and a close-on-overlay-click pattern. No hamburger icon on desktop.

---

## Print Styles

Both `laporan.php` and `invoice_view.php` include `@media print` blocks:

```css
@media print {
  #sidebar, #overlay, header, .no-print { display: none !important; }
  body { background: #ffffff !important; }
  main { padding: 0 !important; }
  @page { margin: 1.5cm; }
}
```

For invoices, `.invoice-box` also removes its border and border-radius so the printed document looks like a clean sheet, not a screenshot of a web UI.

The `.no-print` utility class gates filter forms, export buttons, and action buttons — anything that belongs to the screen interaction, not the printed document.
