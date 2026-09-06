# PNG Market — Phase 2 Design System

## Goal
Match client mockups pixel-for-pixel for typography, spacing, buttons, cards, forms, icons, and hierarchy — without rebuilding Osclass/Epsilon.

## Loaded CSS
1. `css/design-system.css` — shared components (`pngm-ds-*`)
2. `css/custom.css` — page-specific overrides

Version: `PNGM_CHILD_VERSION` in `functions_child.php` (currently `1.6.0`).

## Tokens (no CSS variables — Osclass strips them)
| Token | Hex |
|---|---|
| Brand | `#006b24` |
| Brand hover | `#00551c` |
| Brand soft | `#e8f5ec` |
| Sell / alert | `#e30000` |
| Ink | `#16202a` |
| Muted | `#6b7785` |
| Hairline | `#e3e8ed` |
| Canvas | `#f4f6f8` |
| Radius | 8 / 12 / 16 / pill |

## Assets in theme
| File | Use |
|---|---|
| `images/home-hero.png` | Homepage hero collage (exact asset) |
| `images/design/home-hero-tall.png` | Alternate hero crop |
| `images/design/home-hero-zoomed.png` | Alternate hero crop |
| `images/design/ref-homepage.jpg` | Mockup reference for QA |
| `images/small_cat/sample/*.svg` | Category icons |
| `images/logo.png` | Brand logo |

## Still needed from design export (for pixel-perfect art)
Please export from Figma/PSD as PNG @2x (transparent where needed):

1. Homepage hero collage (isolated, no UI chrome) — if different from current `home-hero.png`
2. Empty states: Near You / Latest / Recently Viewed / No search results / No category listings / Companies empty
3. About page 3D hero art
4. Contact hero art (if separate)
5. Location modal illustrations (if any)
6. Notification / unauthorized-thread lock illustration
7. App icon set for PWA: 192, 512, apple-touch-icon

Until those exports arrive, layout/CSS uses the design system; illustrations fall back to existing theme assets.

## Component classes
- Buttons: `.pngm-ds-btn` + `.pngm-ds-btn-primary|secondary|ghost|danger`
- Forms: `.pngm-ds-input` / `.pngm-ds-label` / `.pngm-ds-error`
- Cards: `.pngm-ds-card` / `.pngm-ds-listing-card`
- Badges: `.pngm-ds-badge-active|pending|inactive|expired|premium`
- Account nav: `.pngm-ds-sidebar`
- Empty: `.pngm-ds-empty`

## Next implementation steps
- Step 2: Listing expiry UI using badges + renew buttons
- Step 8: Account pages using sidebar + cards
- Step 10: Homepage listing cards using `.pngm-ds-listing-card`
