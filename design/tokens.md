# Larder — Design Tokens

Extracted from the `Larder.dc.html` style tile (claude.ai/design project "UI design for web and mobile").
Source of truth for the eventual frontend theme. Hex values are taken verbatim from the canvas.

## Color

### Surfaces & text
| Token | Hex | Use |
|-------|-----|-----|
| `bg/canvas` | `#E4DDCF` | App background (warm parchment) |
| `surface/paper` | `#F4EFE6` | Panel / paper surface |
| `surface/card` | `#FBF8F2` | Cards, tiles |
| `border/hairline` | `#E8DFD0` | Card & divider borders |
| `text/primary` | `#292320` | Body & headings |
| `text/muted` | `#6B5F54` | Secondary text |
| `text/subtle` | `#8A7C6D` | Tertiary / captions |
| `text/label` | `#A8997F` | Uppercase section labels |

### Semantic / brand
| Token | Hex | Meaning |
|-------|-----|---------|
| `brand/terracotta` | `#BC5B3C` | Primary brand accent (logo, primary actions) |
| `diet/veg` | `#4F7A55` | Vegetarian (veg green) |
| `diet/meat` | `#9E4B36` | Meat |
| `state/freezer` | `#5B7E8C` | Freezer / frozen |
| `state/best-before` | `#D08C3A` | Best-before / expiring soon |

### Status pills (bg / text pairs)
| Pill | Background | Text |
|------|-----------|------|
| Vegetarian | `#E7F0E4` | `#3F6645` |
| Meat | `#F3E2DC` | `#8A3A28` |
| Frozen | `#E2EAEE` | `#436370` |

## Typography
| Family | Role | Weights |
|--------|------|---------|
| **Spectral** (serif) | Display & recipe names | 400, 500, 600, 700 (+ italic 400) |
| **Hanken Grotesque** (sans) | Interface & labels | 400, 500, 600, 700, 800 |

Google Fonts import:
```html
<link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Hanken+Grotesque:wght@400;500;600;700;800&display=swap" rel="stylesheet">
```
- Display heading: Spectral 600, `letter-spacing:-0.02em`, `line-height:1`.
- Uppercase labels: Hanken 700, `letter-spacing:0.12–0.14em`, `text-transform:uppercase`.

## Radii
| Token | Value | Use |
|-------|-------|-----|
| `radius/sm` | `9px` | Swatches, small chips |
| `radius/md` | `14px` | Cards, panels |
| `radius/lg` | `16px` | Logo tile |
| `radius/pill` | `20px` | Tags / status pills |

## Elevation
- `shadow/brand`: `0 10px 24px -10px rgba(188,91,60,.7)` (terracotta-tinted, used on the logo/primary elements).
- Scrollbar thumb: `#CBBFA9`, 10px, `radius 8px`.

## Note on the veg/non-veg copy
The style tile's tagline reads *"she's vegetarian, he's not"*, but the scoping spec's confirmed data
model is **pescatarian** (Jolene eats fish, not meat) — see `../Grocery-App-Scoping-Spec.md` §3 and
[[ADR-0002-diet-class-substitute-model]]. The color system (veg green / meat) still applies; the
`diet_class` mechanism generalizes it. Treat the marketing copy as illustrative, the model as canonical.
