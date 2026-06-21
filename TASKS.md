# Larder — Engineering Backlog

This is the working backlog for **Larder**, derived from the scoping spec. It is organized by the spec's Phase 0–5 roadmap (§9), grounded in the §4 data model and §5 feature walkthrough. Larder is a private household grocery and meal-planning app built **API-first**: a Laravel API (PHP 8.4) is the single source of truth, with an **Inertia + Vue 3** web client first and a **Compose Multiplatform** Android + iOS mobile client later. A core constraint runs throughout — the household has a mixed diet (omnivore + pescatarian), so excluded-class ingredients carry substitutes and both versions are tracked independently. Phase 1 ships the full manual planning loop with **zero AI**; AI lands in Phase 3.

Tasks are roughly half-day to two-day units. References point to vault features (recipes, meal-planning, shopping-list, inventory-reconciliation, ai-services) and ADRs (ADR-0001 fraction-of-pack inventory, ADR-0002 diet_class + substitute, ADR-0003 two-clock best-before, ADR-0004 api-first architecture).

---

## Phase 0 — Foundations

> **Status (2026-06-21): core foundations landed** — Laravel 13 / PHP 8.4 scaffold, all 14 §4 migrations (verified green + reversible on Postgres 17), Eloquent models + 8 enums + `BelongsToHousehold` tenancy trait, Sanctum + Horizon installed, household-scoped policies, `/api/v1` (health, me), Dockerized stack, 8 tests green. **Deferred to later:** Octane/FrankenPHP runtime, Pint/PHPStan/CI gate, R2/S3 object storage, login/logout endpoints, deploy pipeline, dev seeder, pg_trgm/full-text (lands with ingredient search in Phase 1). Membership is modelled as `users.household_id` + diet fields on `users` (not a separate `household_member` table) — sufficient for the single two-person household; revisit at Phase 5 productisation.

### Project skeleton & runtime
- [x] Scaffold a Laravel 13 project on PHP 8.4 with a `/api/v1` route group and JSON responses for `api/*` (ADR-0004)
- [ ] Configure standardized API response envelope, error handling, and JSON validation problem format (ADR-0004)
- [ ] Add API resource/transformer base classes for consistent serialization (ADR-0004)
- [ ] Configure Octane/FrankenPHP runtime locally and document the dev bootstrap (ADR-0004)
- [ ] Set up code style (Pint), static analysis (PHPStan/Larastan), and CI lint+test gate

### Persistence & infrastructure
- [ ] Provision Postgres connection and enable extensions for full-text search and fuzzy matching (pg_trgm)
- [x] Configure Redis for cache and queue drivers
- [x] Install and configure Laravel Horizon for queue supervision
- [ ] Configure S3-compatible object storage (Cloudflare R2/S3) for recipe images, with a signed-upload flow
- [ ] Add `.env` template and config for all backing services (Postgres, Redis, R2, mail)

### Data model migrations (§4, all household_id-scoped)
- [x] Create `household` migration + diet profile fields on `users` (membership via `users.household_id`) (ADR-0002)
- [x] Create `ingredient` migration: diet_class, default_unit, default_pack_size, category, substitute_ingredient_id, shelf_life_sealed_days, use_within_after_open_days, requires_open_tracking (ADR-0001, ADR-0002, ADR-0003)
- [x] Create `recipe`, `recipe_ingredient`, and `recipe_component` migrations (feat: recipes)
- [x] Create `tag` and `recipe_tag` migrations (feat: recipes)
- [x] Create `meal_plan` and `meal_plan_entry` migrations (feat: meal-planning)
- [x] Create `shopping_list` and `shopping_list_item` migrations (feat: shopping-list)
- [x] Create `inventory_item` migration: location, remaining, is_opened, opened_on, sealed_best_before, effective_best_before, status (feat: inventory-reconciliation, ADR-0001, ADR-0003)
- [x] Create `usage_log` migration linked to inventory_item and meal_plan_entry (feat: inventory-reconciliation)
- [x] Verify every migration is reversible and every FK is indexed
- [x] Add Eloquent models with relationships and a `household_id` global scope/trait

### Auth & authorization
- [x] Install Laravel Sanctum and configure cookie sessions (web) + token auth (mobile) (ADR-0004)
- [ ] Implement login/logout and current-user endpoints
- [ ] Implement household membership resolution and an `ActsAsHouseholdMember` middleware
- [ ] Write household-scoped authorization policies for every resource and wire them into controllers

### Deploy pipeline
- [ ] Create deploy pipeline (build, migrate, queue/Horizon restart) to the target environment (ADR-0004)
- [ ] Add health-check endpoint and basic uptime/queue monitoring
- [ ] Seed a single household with two members (omnivore + pescatarian) and base tags for local/dev (ADR-0002)

---

## Phase 1 — MVP: manual planning loop (web only, zero AI)

> **Status (2026-06-21): Phase 1a (backend) complete** — the full manual-loop API is built + tested (59 tests green): ingredients, recipes (+ recursive sub-recipe expansion & cycle guard), meal planner (auto veg/non-veg split), shopping-list generation (per-member substitutes + inventory subtraction), complete→inventory, usage logging (first-use auto-open + FIFO). Business logic lives in services: `RecipeExpansionService`, `MealSplitResolver`, `ShoppingListGenerationService`, `InventoryDepletionService`, `BestBeforeCalculator`. **Phase 1b-i (done 2026-06-21):** Inertia + Vue + Tailwind v4 foundation, Larder design system, session auth + seeder, Ingredient Library + Recipe editor screens (web controllers reuse the services; `/api/v1` stays the mobile contract). **Phase 1b-ii (done 2026-06-21):** planner, shopping, inventory, cook screens — the **manual loop now runs end-to-end in the browser.** **Deferred:** recipe image upload (R2 storage), near-duplicate-name flag, server-side group-by-category, pg_trgm fuzzy search (ILIKE for now).

### Ingredient library
- [x] Implement ingredient CRUD endpoints with category, diet_class, and unit/pack fields (feat: recipes, ADR-0002)
- [x] Implement ingredient autocomplete/search endpoint (ILIKE; full-text + trigram deferred) (feat: recipes)
- [ ] Add ingredient dedup helper that flags near-duplicate names on create (feat: recipes)
- [x] Implement substitute-link endpoint to set/clear `substitute_ingredient_id` on an ingredient (feat: recipes, ADR-0002)
- [x] Build Inertia+Vue ingredient library screen with substitute pairing UI (Phase 1b-i)

### Recipe CRUD
- [x] Implement recipe CRUD endpoints with simplified recipe_ingredients (quantity_hint optional) (feat: recipes, ADR-0001)
- [x] Implement add/remove tags on a recipe (feat: recipes)
- [x] Implement sub-recipe linking via recipe_component endpoints (feat: recipes)
- [x] Add circular-reference guard for sub-recipe links (recursive cycle detection) (feat: recipes)
- [x] Implement recursive sub-recipe expansion service that flattens a recipe to base ingredients (feat: recipes)
- [ ] Implement recipe image upload to object storage (feat: recipes)
- [x] Build Inertia+Vue recipe list and recipe editor screens (ingredients, tags, sub-recipes, substitutes) (Phase 1b-i)

### Weekly meal planner
- [x] Implement meal_plan creation anchored to a week_start_date with status lifecycle (feat: meal-planning)
- [x] Implement meal_plan_entry CRUD assigning recipe to day + slot tag (feat: meal-planning)
- [x] Implement auto veg/non-veg split: mark entry `is_split` when a recipe ingredient is excluded by a member's diet (feat: meal-planning, ADR-0002)
- [x] Implement per-entry override to force split or shared (feat: meal-planning, ADR-0002)
- [x] Add "re-use last week" / duplicate-day helpers (feat: meal-planning)
- [x] Build Inertia+Vue weekly grid (days × slots) with recipe assignment and split indicators (Phase 1b-ii)

### Shopping list generation
- [x] Implement shopping-list generation service: expand recipes + sub-recipes to base ingredients (feat: shopping-list)
- [x] Apply per-member substitutes during expansion (meat → vegetarian substitute for the excluded member) (feat: shopping-list, ADR-0002)
- [x] Subtract on-hand inventory and frozen items from required quantities (feat: shopping-list, feat: inventory-reconciliation)
- [x] Dedupe/aggregate duplicate ingredient lines and group by store category (Phase 1b-ii) (feat: shopping-list)
- [x] Implement shopping_list_item check-off endpoint and manual-add line support (feat: shopping-list)
- [x] Add unbought-item rollover when completing a list (feat: shopping-list)
- [x] Build Inertia+Vue shopping list screen grouped by category with check-off (Phase 1b-ii)

### Complete shopping → inventory
- [x] Implement "Complete shopping" action: convert checked items into inventory_item lots at remaining = 1.0 (feat: inventory-reconciliation, ADR-0001)
- [x] Set sealed_best_before from purchase date + ingredient sealed shelf life on creation (feat: inventory-reconciliation, ADR-0003)
- [x] Build Inventory screen showing active lots by location with remaining fraction (Phase 1b-ii) (feat: inventory-reconciliation)

### Usage logging
- [x] Implement usage-log endpoint with ¼ / ⅓ / ½ / ¾ / all preset amounts decrementing `remaining` (feat: inventory-reconciliation, ADR-0001)
- [~] Resolve the correct lot per member, respecting veg vs non-veg substitutes when depleting — FIFO lot resolution done in `InventoryDepletionService`; per-member variant wiring at the usage endpoint → 1b (feat: inventory-reconciliation, ADR-0002)
- [x] Auto-mark a sealed item opened and start the opened clock on first usage log (feat: inventory-reconciliation, ADR-0003)
- [x] Build Inertia+Vue "log usage" UI (Cook screen) with quick fraction taps (Phase 1b-ii) (feat: inventory-reconciliation)

---

## Phase 2 — Reconciliation & waste reduction

> **Status (2026-06-21): Phase 2 complete.** `InventoryActionService` (open / adjust / freeze / thaw / discard) implements the ADR-0003 clock pause-and-resume (freeze snapshots the remaining shelf + extends; thaw resumes + restarts the opened window). Action endpoints on `/api/v1` + web; the inventory screen gained per-lot actions; a new **Reconcile** screen runs the weekly flow (confirm stock → expiring flags → rules-based freeze suggestions → discard pass, nothing auto-removed). 65 tests green. **Simplification:** freezability is a per-ingredient `freezable` flag (default true), not a per-category hint table. Several two-clock items were already delivered in Phase 1a.

### Weekly stock-confirm
- [x] Implement stock-confirm flow endpoint that surfaces current inventory for fraction adjustment (feat: inventory-reconciliation)
- [x] Add "mark opened since last week" step within the confirm flow (feat: inventory-reconciliation, ADR-0003)
- [x] Build Inertia+Vue weekly stock-confirm screen as a fast two-tap correction (feat: inventory-reconciliation)

### Two-clock best-before
- [x] Implement effective_best_before computation = earlier of (sealed) and (opened_on + use_within_after_open) (Phase 1a) (feat: inventory-reconciliation, ADR-0003)
- [x] Recompute effective_best_before on open/edit and persist it (feat: inventory-reconciliation, ADR-0003)
- [x] Implement FIFO depletion ordering by effective_best_before when multiple lots exist (Phase 1a) (feat: inventory-reconciliation, ADR-0003)
- [x] Surface expiring-soon flags in inventory and planning views (feat: inventory-reconciliation, ADR-0003)

### Freeze / thaw
- [x] Implement freeze action: status active → frozen, pause both clocks, extend effective_best_before (feat: inventory-reconciliation, ADR-0003)
- [x] Implement thaw action: restart the opened window on un-freeze (feat: inventory-reconciliation, ADR-0003)
- [x] Implement rules-based freeze suggestions for items likely to spoil before use (feat: inventory-reconciliation)
- [ ] Maintain a per-category freezability hint table — _simplified to a per-ingredient `freezable` flag for now_ (feat: inventory-reconciliation)

### Discard pass
- [x] Implement throw-away surfacing: list items past effective_best_before as "worth checking" (nothing auto-discarded) (feat: inventory-reconciliation)
- [x] Implement user-driven discard action that sets status = discarded and logs the discard (feat: inventory-reconciliation)
- [x] Build Inertia+Vue reconciliation screen combining stock-confirm, expiry flags, freeze prompts, and discard pass (feat: inventory-reconciliation)

### Inventory-aware shopping
- [x] Extend shopping-list generation to include the frozen pool as available stock (Phase 1a) (feat: shopping-list, feat: inventory-reconciliation)
- [x] Verify new meal plan + shopping list are built around remaining + frozen stock (Phase 1a) (feat: shopping-list)

---

## Phase 3 — AI capture & suggestions

### AI services foundation
- [ ] Create an LLM client abstraction with provider config, retries, and cost logging (feat: ai-services)
- [ ] Establish async Horizon job pattern for all AI work with result push-back to the client (feat: ai-services, ADR-0004)
- [ ] Add a per-URL and per-rule cache layer to avoid redundant LLM calls (feat: ai-services)

### URL recipe import
- [ ] Implement URL fetch + schema.org `Recipe` JSON-LD parser as the primary path (feat: ai-services, feat: recipes)
- [ ] Implement LLM-on-HTML fallback when structured data is absent (feat: ai-services, feat: recipes)
- [ ] Cache import results by URL and map ingredients to the existing library (feat: ai-services)
- [ ] Build review screen landing for URL imports (never auto-saved) (feat: ai-services, feat: recipes)

### Photo / OCR import
- [ ] Implement photo upload → OCR / vision extraction job (feat: ai-services, feat: recipes)
- [ ] Implement LLM structuring of OCR output into title, ingredients, and steps (feat: ai-services, feat: recipes)
- [ ] Build mandatory review/edit screen for photo imports with good manual-edit UX (feat: ai-services, feat: recipes)

### Ingredient auto-matching
- [ ] Implement embeddings/fuzzy matching of imported ingredient names to the library (feat: ai-services, feat: recipes)
- [ ] Suggest substitute-link pairings on import (e.g. detect "beef mince" → offer "Vegetarian Mince") (feat: ai-services, ADR-0002)

### AI best-before & freeze
- [ ] Implement best-before estimation job producing both sealed and use-within-after-open windows (feat: ai-services, ADR-0003)
- [ ] Build and maintain a cached rules table per category/location for best-before estimates (feat: ai-services, ADR-0003)
- [ ] Implement LLM-assisted freeze suggestions over best-before vs planned usage (feat: ai-services, feat: inventory-reconciliation)

### AI meal suggestions
- [ ] Implement meal-suggestion job grounded in recipe history, tags, inventory, and diet profiles (feat: ai-services, feat: meal-planning)
- [ ] Bias suggestions toward using existing stock and keep them veg-aware (feat: ai-services, ADR-0002)
- [ ] Add thumbs up/down feedback capture to tune suggestions (feat: ai-services)
- [ ] Build Inertia+Vue "what should we have?" suggestion UI per slot (feat: ai-services, feat: meal-planning)

---

## Phase 4 — Mobile (CMP)

### Project & shared layer
- [ ] Scaffold a Compose Multiplatform project targeting Android + iOS (ADR-0004)
- [ ] Implement API client with Sanctum token auth against `/api/v1` (ADR-0004)
- [ ] Build shared UI for recipes, meal plan, shopping list, inventory, and usage logging

### Native capture & offline
- [ ] Integrate native camera for recipe photo capture feeding the photo-import flow (feat: ai-services)
- [ ] Implement SQLDelight local store mirroring core entities (feat: inventory-reconciliation)
- [ ] Implement offline-first sync layer: queue usage logs / planning changes and reconcile on reconnect (feat: inventory-reconciliation)
- [ ] Handle conflict resolution for offline edits (last-write / merge rules)

---

## Phase 5 — Productisation (optional)

### Multi-tenant go-live
- [ ] Implement public sign-up and account creation (ADR-0004)
- [ ] Implement household invites and membership management
- [ ] Integrate billing/subscription provider
- [ ] Build onboarding flow for new households
- [ ] Flip the multi-tenant switch and audit household_id scoping across all endpoints (ADR-0004)

---

## Resolved decisions

- [x] **Web framework:** **Inertia + Vue 3** for v1 (decided 2026-06-21, over decoupled Nuxt SSR). (ADR-0004)
- [x] **Best-before disclaimer wording:** fixed advisory copy confirmed — see spec §6 and the `best-before-is-advisory` indication.

_No open scoping decisions remain — Phase 0 is unblocked._
