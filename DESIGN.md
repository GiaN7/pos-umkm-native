---
name: Mitra Tech Electrical POS
colors:
  surface: '#fff8f4'
  surface-dim: '#e1d8d3'
  surface-bright: '#fff8f4'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#fbf2ec'
  surface-container: '#f5ece6'
  surface-container-high: '#efe7e1'
  surface-container-highest: '#e9e1db'
  on-surface: '#1f1b18'
  on-surface-variant: '#504440'
  inverse-surface: '#34302c'
  inverse-on-surface: '#f8efe9'
  outline: '#83746f'
  outline-variant: '#d5c3bd'
  surface-tint: '#7b5649'
  primary: '#210a03'
  on-primary: '#ffffff'
  primary-container: '#3a1e14'
  on-primary-container: '#ad8375'
  inverse-primary: '#ecbcac'
  secondary: '#825422'
  on-secondary: '#ffffff'
  secondary-container: '#fdc083'
  on-secondary-container: '#784c1a'
  tertiary: '#001502'
  on-tertiary: '#ffffff'
  tertiary-container: '#002d06'
  on-tertiary-container: '#4f9d4e'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdbcf'
  primary-fixed-dim: '#ecbcac'
  on-primary-fixed: '#2e140b'
  on-primary-fixed-variant: '#603e33'
  secondary-fixed: '#ffdcbd'
  secondary-fixed-dim: '#f7bb7e'
  on-secondary-fixed: '#2c1600'
  on-secondary-fixed-variant: '#663d0b'
  tertiary-fixed: '#a3f69c'
  tertiary-fixed-dim: '#88d982'
  on-tertiary-fixed: '#002204'
  on-tertiary-fixed-variant: '#005312'
  background: '#fff8f4'
  on-background: '#1f1b18'
  surface-variant: '#e9e1db'
typography:
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 26px
    fontWeight: '700'
    lineHeight: 32px
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 22px
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
  numeric-currency:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 30px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-mobile: 0.75rem
  margin: 1.25rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system is crafted for Mitra Tech, a small electrical supplies retailer. It pairs a clear, dependable blue interface with orange accents drawn from the shop logo, while keeping daily sales and stock workflows quick to scan and easy to use.

The core design movement combines **Practical Retail Utility** with **Clean Editorial Clarity**. Cool neutral surfaces keep product and transaction information legible, while the blue and orange brand colors make key actions easy to recognize.

Key attributes:
- **Clear & Dependable:** Deep blue anchors primary actions and navigation; orange highlights secondary actions and shop identity.
- **Empowering Simplicity:** Clean, unencumbered visual hierarchy designed for quick one-handed work during busy shop hours.
- **Thoughtful Semantics:** Financial indicators retain familiar green for incoming revenue and red for outflows.

## Colors

The palette is tuned to Mitra Tech's blue-and-orange logo and clear financial semantics.

### Foundational Tones
- **Primary (`#315EAF` - Mitra Blue):** Serves as the primary brand signature, high-contrast action color, main balance summary backgrounds, and top-tier navigation.
- **Secondary (`#D36B35` - Mitra Orange):** Highlights intermediate card highlights, subtle badges, and secondary balance accents.
- **Neutral Canvas (`#F5F8FC` - Cool Mist):** Default light canvas that reduces glare while keeping product and sales information legible.
- **Neutral Container (`#E8EFFA` - Soft Blue):** Card backgrounds, segmented control wells, and input field strokes.

### Semantic Financial Tones
- **Income / Positive (`#2E7D32` - Mint Herb):** Represents incoming sales, deposits, and positive cash flow. Soft mint tints (`#E8F5E9`) serve as badge chips.
- **Expense / Negative (`#C62828` - Red):** Represents operating costs and refunds. Soft red tints (`#FFEBEE`) back expense chips.
- **Text Primary (`#1F2F48` - Deep Slate):** High-legibility text.
- **Text Secondary (`#66758C` - Slate):** Mid-tone for timestamps, notes, and auxiliary labels.

## Typography

The typography uses **Plus Jakarta Sans** across all hierarchies to ensure high readability, geometric clarity, and an approachable modern voice.

- **Monetary Figures:** Use `tabular-nums` formatting across all numbers and currency amounts to guarantee clean vertical alignment in financial transaction logs.
- **Headlines:** Kept sturdy and bold (`fontWeight: 700`) for primary total metrics (e.g., "Laba Bersih"), communicating stability and financial clarity.
- **Labels & Microcopy:** Crisp medium weights (`500` - `600`) ensure quick scannability for category tags (Bahan Baku, Penjualan, Operasional).

## Layout & Spacing

The layout model is optimized for single-handed mobile interactions while cleanly constraining to a centered container max-width on wider viewports (tablet and web preview).

- **Grid & Safe Margins:** Uses a 4-column fluid mobile grid with `1rem` outer margins (`margin-mobile`) and `0.75rem` gutters. On desktop viewports, the app shell is centered inside a max-width container (`430px` for mobile frame simulator, or `768px` for tablet dashboard view).
- **Rhythm & Stacking:** Vertical stacks between major card components follow `space-md` (`1rem`). Card internal paddings use `space-lg` (`1.5rem`) for hero cards and `space-md` (`1rem`) for secondary stat modules.
- **Touch Targets:** Interactive inputs, buttons, and bottom tab navigation items strictly maintain a minimum tap height of `48px`.

## Elevation & Depth

This system uses subtle, cool-tinted ambient depth and tonal card layering.

- **Tonal Base:** Flat surface cards sit on the cool neutral canvas with a 1px border (`#D8E1ED`).
- **Ambient Shadow:** Floating actions, modals, and the hero balance card use a subtle, diffused shadow:
  - *Standard Card:* `0px 4px 16px rgba(31, 47, 72, 0.06)`
  - *Elevated Nav / Floating Modal:* `0px 10px 30px rgba(31, 47, 72, 0.10)`
- **Hero Cards:** The primary balance card uses solid Mitra Blue (`#315EAF`) for clear visual hierarchy.

## Shapes

The interface uses clear, approachable contours. Primary cards feature generous `20px` to `24px` border radii (`rounded-2xl` behavior).

Segmented tab controls, status tags, toggle pills, and primary CTAs use complete pill rounding (`9999px`) for comfortable touch targets.

## Components

### Buttons & CTAs
- **Primary Button:** Filled Mitra Blue (`#315EAF`) with white typography (`#FFFFFF`), rounded-2xl (`16px`) or full pill (`9999px`), minimum height `52px`.
- **Secondary / Ghost Button:** Soft blue background (`#E8EFFA`) with deep blue text (`#244A90`), or outlined with a `1.5px` blue border.

### Segmented Transaction Toggle (Pill Toggle)
- Two-option or three-option toggle contained in a soft blue track (`#E8EFFA`).
- Selected state adopts Mitra Blue (`#315EAF`) with crisp white text; unselected state remains transparent with muted slate text (`#66758C`).

### Financial Stat Cards
- **Hero Balance Card:** Solid `#3A1E14` background, rounded-2xl (`20px`), white headline values, with secondary white/opacity text for dates.
- **Split Stat Cards (Pendapatan vs Pengeluaran):** Soft cream-white containers (`#FFFFFF` or `#F7EBE1`) with an inline circular icon badge:
  - *Pendapatan:* Circular badge in `#E8F5E9` with deep green down-left arrow icon; value formatted as `+Rp [amount]` in `#2E7D32`.
  - *Pengeluaran:* Circular badge in `#FFEBEE` with terracotta red up-right arrow icon; value formatted as `-Rp [amount]` in `#C62828`.

### Input Fields & Selectors
- Background `#FFFFFF` or `#FDF9F6`, `1.5px` border colored `#E8D5C4`.
- Focus state activates a subtle `#3A1E14` border ring with zero blur spread.
- Pre-filled category selectors (e.g., "Kabel", "Lampu", "Peralatan Listrik") feature relevant icons and trailing chevron indicators.

### Transaction List Items
- Clean horizontal rows on subtle white or cream cards, separated by hairline `#F2E4D8` dividers.
- Left side: circular category iconography (electrical supplies or sales) + title and subtitle (date/notes).
- Right side: right-aligned numeric balance colored strictly green (`#2E7D32`) or terracotta (`#C62828`).

### Bottom Navigation Bar
- Cool neutral container (`#F1F5FB`) with hairline top border (`#D8E1ED`).
- Active items highlighted in Mitra Blue (`#315EAF`); inactive items in muted slate (`#8796AB`).