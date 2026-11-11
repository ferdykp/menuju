---
name: Warm Editorial Utility
colors:
  surface: '#FFFFFF'
  surface-dim: '#d8dbd1'
  surface-bright: '#f8fbef'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f5ea'
  surface-container: '#ecefe4'
  surface-container-high: '#e6e9de'
  surface-container-highest: '#e1e4d9'
  on-surface: '#191d16'
  on-surface-variant: '#444841'
  inverse-surface: '#2e322a'
  inverse-on-surface: '#eff2e7'
  outline: '#757870'
  outline-variant: '#c5c8be'
  surface-tint: '#54634c'
  primary: '#3e4c37'
  on-primary: '#ffffff'
  primary-container: '#55644d'
  on-primary-container: '#cfe0c3'
  inverse-primary: '#bbccb0'
  secondary: '#596057'
  on-secondary: '#ffffff'
  secondary-container: '#dee5d8'
  on-secondary-container: '#5f665d'
  tertiary: '#5c414c'
  on-tertiary: '#ffffff'
  tertiary-container: '#755864'
  on-tertiary-container: '#f7d1df'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d7e8cb'
  primary-fixed-dim: '#bbccb0'
  on-primary-fixed: '#121f0d'
  on-primary-fixed-variant: '#3c4b35'
  secondary-fixed: '#dee5d8'
  secondary-fixed-dim: '#c2c9bd'
  on-secondary-fixed: '#171d16'
  on-secondary-fixed-variant: '#424940'
  tertiary-fixed: '#ffd8e7'
  tertiary-fixed-dim: '#e1bdcb'
  on-tertiary-fixed: '#2a151f'
  on-tertiary-fixed-variant: '#5a3f4b'
  background: '#f8fbef'
  on-background: '#191d16'
  surface-variant: '#e1e4d9'
  canvas: '#F8F6F2'
  surface-soft: '#F1EEE8'
  text-primary: '#242821'
  text-secondary: '#676E64'
  accent-hover: '#43503D'
  border: '#C9CDC6'
  danger: '#A43636'
  focus: '#536A54'
typography:
  display:
    fontFamily: EB Garamond
    fontSize: 48px
    fontWeight: '500'
    lineHeight: 56px
  display-mobile:
    fontFamily: EB Garamond
    fontSize: 36px
    fontWeight: '500'
    lineHeight: 44px
  headline-lg:
    fontFamily: EB Garamond
    fontSize: 36px
    fontWeight: '600'
    lineHeight: 44px
  headline-lg-mobile:
    fontFamily: EB Garamond
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
  headline-md:
    fontFamily: EB Garamond
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
  headline-sm:
    fontFamily: EB Garamond
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
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
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.5px
  numeric-table:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 1rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

# MENUJU — Design System & UX Writing
**Versi:** 1.0 · **Status:** Art direction proposal

## 1. Creative direction
**Nama arah desain:** Warm Editorial Utility
Produk harus terasa seperti pendamping persiapan pernikahan: hangat, terpercaya, cerdas, rapi, intim, dan fungsional.
Bukan corporate ERP kaku, dan bukan juga undangan penuh dekorasi di dashboard aplikasi perencanaan.
Pisahkan visual aplikasi perencanaan (efisien, terstruktur) dan undangan publik (sinematik, emosional).

## 2. Foundations Tokens
- `--canvas`: #F8F6F2 (Background warm ivory)
- `--surface`: #FFFFFF (Panel, cards, modal, drawer)
- `--surface-soft`: #F1EEE8 (Subtle backgrounds, table hover, secondary blocks)
- `--text-primary`: #242821 (Deep charcoal botanical)
- `--text-secondary`: #676E64 (Muted sage charcoal)
- `--accent`: #55644D (Muted botanical green CTA)
- `--accent-hover`: #43503D (Deep botanical green)
- `--border`: #C9CDC6 (Clear visible input borders and dividers)
- `--danger`: #A43636 (Destructive actions and critical alerts)
- `--focus`: #536A54 (Focus outline ring)

## 3. Typography
- Headline/Display: Cormorant Garamond / Editorial Serif elegan
- UI & Body text: Plus Jakarta Sans / Inter (highly legible)
- Tabular numerals untuk angka keuangan format Rupiah (id-ID)

## 4. Components & Layout
- 8px spacing rhythm (4, 8, 12, 16, 24, 32, 48)
- Inputs: 44-48px height, rounded-xl (10-12px), 1px solid visible border
- Buttons: Single primary button per context, clear secondary/ghost buttons
- Currency format: `Rp 1.500.000`
