# Larder — Design Reference

Extracted **2026-06-21** from the Claude Design project *"UI design for web and mobile"*
(`claude.ai/design`, project id `1ea5bbef-8c9e-41b2-848d-24c724ee7cd7`, file `Larder.dc.html`).
This folder is the build-time design reference for the app — it mirrors the spec's feature set
(`../Grocery-App-Scoping-Spec.md` §5).

## Contents

| Path | What it is |
|------|-----------|
| `Larder.dc.html` | The **full original canvas** (brand header, style tile, all screens). Render with `support.js`. |
| `support.js` | The `dc-runtime` script the canvas needs (`<x-dc>` React renderer). Keep next to `Larder.dc.html`. |
| `tokens.md` | Design tokens (color, type, radii, elevation) pulled from the style tile. |
| `_brand-style-tile.html` | The brand header + style-tile blocks, isolated (token source). |
| `web/*.html` | 10 web screens, each a **standalone-renderable** fragment. |
| `mobile/*.html` | 9 mobile screens, each a **standalone-renderable** fragment. |

### Screens
- **Web:** home, recipes, add-edit-recipe, ai-capture, meal-planner, shopping-list, inventory, cook-log-usage, reconciliation, ai-suggestions
- **Mobile:** home, recipe-detail, ai-capture, planner, shopping, inventory, cook-log-usage, reconcile, suggestions

## How to view
- **Full canvas (highest fidelity):** open `Larder.dc.html` in a browser — it loads `./support.js` and Google Fonts. The `.dc.html` format renders via the `<x-dc>` runtime.
- **Single screen:** open any `web/<name>.html` or `mobile/<name>.html` directly. These were wrapped in a minimal HTML doc (same fonts + canvas background) and use only inline styles, so they render without the runtime — handy for referencing one screen while building it.

## Provenance & editing
- The canonical design still lives in Claude Design; this is an extracted snapshot for the repo.
- Re-extract by re-reading the project files and re-running the extraction (the screens are sliced on the canvas's `<!-- ===== SECTION ===== -->` markers).
- The Claude Design **MCP/`/design-sync`** flow pushes a *local component library* **up** to a design-*system* project — it does not import a `.dc.html` artifact down. Your separate "Design System" project (`3fb89f74-…`) is the correct target if you later want to publish reusable components from this app.
