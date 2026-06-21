# Larder — Engineering Backlog

This is the working backlog for **Larder**, derived from the scoping spec. It is organized by the spec's Phase 0–5 roadmap (§9), grounded in the §4 data model and §5 feature walkthrough. Larder is a private household grocery and meal-planning app built **API-first**: a Laravel API (PHP 8.3) is the single source of truth, with an **Inertia + Vue 3** web client first and a **Compose Multiplatform** Android + iOS mobile client later. A core constraint runs throughout — the household has a mixed diet (omnivore + pescatarian), so excluded-class ingredients carry substitutes and both versions are tracked independently. Phase 1 ships the full manual planning loop with **zero AI**; AI lands in Phase 3.

Tasks are roughly half-day to two-day units. References point to vault features (recipes, meal-planning, shopping-list, inventory-reconciliation, ai-services) and ADRs (ADR-0001 fraction-of-pack inventory, ADR-0002 diet_class + substitute, ADR-0003 two-clock best-before, ADR-0004 api-first architecture).

---

## Phase 0 — Foundations

### Project skeleton & runtime
- [ ] Scaffold a Laravel 11 project on PHP 8.3 with a `/api/v1` route group and versioned JSON responses (ADR-0004)
- [ ] Configure standardized API response envelope, error handling, and JSON validation problem format (ADR-0004)
- [ ] Add API resource/transformer base classes for consistent serialization (ADR-0004)
- [ ] Configure Octane/FrankenPHP runtime locally and document the dev bootstrap (ADR-0004)
- [ ] Set up code style (Pint), static analysis (PHPStan/Larastan), and CI lint+test gate

### Persistence & infrastructure
- [ ] Provision Postgres connection and enable extensions for full-text search and fuzzy matching (pg_trgm)
- [ ] Configure Redis for cache and queue drivers
- [ ] Install and configure Laravel Horizon for queue supervision
- [ ] Configure S3-compatible object storage (Cloudflare R2/S3) for recipe images, with a signed-upload flow
- [ ] Add `.env` template and config for all backing services (Postgres, Redis, R2, mail)

### Data model migrations (§4, all household_id-scoped)
- [ ] Create `household` and `household_member` (membership) migrations with diet profile fields (ADR-0002)
- [ ] Create `ingredient` migration: diet_class, default_unit, default_pack_size, category, substitute_ingredient_id, shelf_life_sealed_days, use_within_after_open_days, requires_open_tracking (ADR-0001, ADR-0002, ADR-0003)
- [ ] Create `recipe`, `recipe_ingredient`, and `recipe_component` migrations (feat: recipes)
- [ ] Create `tag` and `recipe_tag` migrations (feat: recipes)
- [ ] Create `meal_plan` and `meal_plan_entry` migrations (feat: meal-planning)
- [ ] Create `shopping_list` and `shopping_list_item` migrations (feat: shopping-list)
- [ ] Create `inventory_item` migration: location, remaining, is_opened, opened_on, sealed_best_before, effective_best_before, status (feat: inventory-reconciliation, ADR-0001, ADR-0003)
- [ ] Create `usage_log` migration linked to inventory_item and meal_plan_entry (feat: inventory-reconciliation)
- [ ] Verify every migration is reversible and every FK is indexed
- [ ] Add Eloquent models with relationships and a `household_id` global scope/trait

### Auth & authorization
- [ ] Install Laravel Sanctum and configure cookie sessions (web) + token auth (mobile) (ADR-0004)
- [ ] Implement login/logout and current-user endpoints
- [ ] Implement household membership resolution and an `ActsAsHouseholdMember` middleware
- [ ] Write household-scoped authorization policies for every resource and wire them into controllers

### Deploy pipeline
- [ ] Create deploy pipeline (build, migrate, queue/Horizon restart) to the target environment (ADR-0004)
- [ ] Add health-check endpoint and basic uptime/queue monitoring
- [ ] Seed a single household with two members (omnivore + pescatarian) and base tags for local/dev (ADR-0002)

---

## Phase 1 — MVP: manual planning loop (web only, zero AI)

### Ingredient library
- [ ] Implement ingredient CRUD endpoints with category, diet_class, and unit/pack fields (feat: recipes, ADR-0002)
- [ ] Implement ingredient autocomplete/search endpoint (full-text + trigram) (feat: recipes)
- [ ] Add ingredient dedup helper that flags near-duplicate names on create (feat: recipes)
- [ ] Implement substitute-link endpoint to set/clear `substitute_ingredient_id` on an ingredient (feat: recipes, ADR-0002)
- [ ] Build Inertia+Vue ingredient library screen with autocomplete and substitute pairing UI

### Recipe CRUD
- [ ] Implement recipe CRUD endpoints with simplified recipe_ingredients (quantity_hint optional) (feat: recipes, ADR-0001)
- [ ] Implement add/remove tags on a recipe (feat: recipes)
- [ ] Implement sub-recipe linking via recipe_component endpoints (feat: recipes)
- [ ] Add circular-reference guard for sub-recipe links (recursive cycle detection) (feat: recipes)
- [ ] Implement recursive sub-recipe expansion service that flattens a recipe to base ingredients (feat: recipes)
- [ ] Implement recipe image upload to object storage (feat: recipes)
- [ ] Build Inertia+Vue recipe list and recipe editor screens (ingredients, tags, sub-recipes, substitutes)

### Weekly meal planner
- [ ] Implement meal_plan creation anchored to a week_start_date with status lifecycle (feat: meal-planning)
- [ ] Implement meal_plan_entry CRUD assigning recipe to day + slot tag (feat: meal-planning)
- [ ] Implement auto veg/non-veg split: mark entry `is_split` when a recipe ingredient is excluded by a member's diet (feat: meal-planning, ADR-0002)
- [ ] Implement per-entry override to force split or shared (feat: meal-planning, ADR-0002)
- [ ] Add "re-use last week" / duplicate-day helpers (feat: meal-planning)
- [ ] Build Inertia+Vue weekly grid (days × slots) with tag-filtered recipe assignment and split indicators

### Shopping list generation
- [ ] Implement shopping-list generation service: expand recipes + sub-recipes to base ingredients (feat: shopping-list)
- [ ] Apply per-member substitutes during expansion (meat → vegetarian substitute for the excluded member) (feat: shopping-list, ADR-0002)
- [ ] Subtract on-hand inventory and frozen items from required quantities (feat: shopping-list, feat: inventory-reconciliation)
- [ ] Dedupe/aggregate duplicate ingredient lines and group by store category (feat: shopping-list)
- [ ] Implement shopping_list_item check-off endpoint and manual-add line support (feat: shopping-list)
- [ ] Add unbought-item rollover when completing a list (feat: shopping-list)
- [ ] Build Inertia+Vue shopping list screen grouped by category with check-off

### Complete shopping → inventory
- [ ] Implement "Complete shopping" action: convert checked items into inventory_item lots at remaining = 1.0 (feat: inventory-reconciliation, ADR-0001)
- [ ] Set sealed_best_before from purchase date + ingredient sealed shelf life on creation (feat: inventory-reconciliation, ADR-0003)
- [ ] Build Inventory screen showing active lots by location with remaining fraction (feat: inventory-reconciliation)

### Usage logging
- [ ] Implement usage-log endpoint with ¼ / ⅓ / ½ / ¾ / all preset amounts decrementing `remaining` (feat: inventory-reconciliation, ADR-0001)
- [ ] Resolve the correct lot per member, respecting veg vs non-veg substitutes when depleting (feat: inventory-reconciliation, ADR-0002)
- [ ] Auto-mark a sealed item opened and start the opened clock on first usage log (feat: inventory-reconciliation, ADR-0003)
- [ ] Build Inertia+Vue "log usage" UI from a planned meal entry with quick fraction taps (feat: inventory-reconciliation)

---

## Phase 2 — Reconciliation & waste reduction

### Weekly stock-confirm
- [ ] Implement stock-confirm flow endpoint that surfaces current inventory for fraction adjustment (feat: inventory-reconciliation)
- [ ] Add "mark opened since last week" step within the confirm flow (feat: inventory-reconciliation, ADR-0003)
- [ ] Build Inertia+Vue weekly stock-confirm screen as a fast two-tap correction (feat: inventory-reconciliation)

### Two-clock best-before
- [ ] Implement effective_best_before computation = earlier of (sealed) and (opened_on + use_within_after_open) (feat: inventory-reconciliation, ADR-0003)
- [ ] Recompute effective_best_before on open/edit and persist it (feat: inventory-reconciliation, ADR-0003)
- [ ] Implement FIFO depletion ordering by effective_best_before when multiple lots exist (feat: inventory-reconciliation, ADR-0003)
- [ ] Surface expiring-soon flags in inventory and planning views (feat: inventory-reconciliation, ADR-0003)

### Freeze / thaw
- [ ] Implement freeze action: status active → frozen, pause both clocks, extend effective_best_before (feat: inventory-reconciliation, ADR-0003)
- [ ] Implement thaw action: restart the opened window on un-freeze (feat: inventory-reconciliation, ADR-0003)
- [ ] Implement rules-based freeze suggestions for items likely to spoil before use (feat: inventory-reconciliation)
- [ ] Maintain a per-category freezability hint table (feat: inventory-reconciliation)

### Discard pass
- [ ] Implement throw-away surfacing: list items past effective_best_before as "worth checking" (nothing auto-discarded) (feat: inventory-reconciliation)
- [ ] Implement user-driven discard action that sets status = discarded and logs the discard (feat: inventory-reconciliation)
- [ ] Build Inertia+Vue reconciliation screen combining stock-confirm, expiry flags, freeze prompts, and discard pass (feat: inventory-reconciliation)

### Inventory-aware shopping
- [ ] Extend shopping-list generation to include the frozen pool as available stock (feat: shopping-list, feat: inventory-reconciliation)
- [ ] Verify new meal plan + shopping list are built around remaining + frozen stock (feat: shopping-list)

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
