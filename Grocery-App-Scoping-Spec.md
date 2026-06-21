# Household Grocery & Meal-Planning App — Scoping Spec

**Status:** Draft v2 · **Date:** 21 June 2026 · **Owner:** Artur
**Working title:** **Larder**

---

## 1. The idea in one paragraph

A private household app where you and your wife add your own recipes in a deliberately
**simplified** form (ingredient names, not gram weights), plan a week of meals against
flexible tags (breakfast / lunch / dinner / custom), and auto-generate a shopping list.
After shopping, purchased items flow into a shared **inventory**. As you cook through the
week you log roughly how much of each item you've used. Each new week the app **reconciles**
what's left, estimates best-before dates, suggests what to freeze, lets you confirm/discard
stock, and builds the next shopping list around what you already have. AI helps with three
jobs: capturing recipes from a photo or web link, estimating shelf life / freeze advice, and
suggesting meals based on what you actually cook and like. A core constraint runs throughout:
**your wife is pescatarian (eats fish, not meat) and you are not**, so every meat ingredient can
carry a vegetarian substitute for her while fish dishes are shared — and the app tracks both
versions independently.

---

## 2. Goals and non-goals

**Goals**
- Make weekly meal planning and shopping fast for a two-person household.
- Cut food waste and cost through inventory reconciliation, freeze suggestions and best-before tracking.
- Handle the veg / non-veg split as a first-class concept, not an afterthought.
- Reduce decision fatigue ("what's for dinner?") with AI suggestions grounded in your real habits.
- Be architected so it *could* later become a multi-household product, without over-building now.

**Non-goals (for now)**
- Calorie / macro nutrition tracking.
- Grocery-store price comparison or online-order integration (possible later).
- Social features, recipe sharing marketplace, monetisation.
- Barcode-based exact-weight inventory (we deliberately stay coarse — see §4.3).

---

## 3. Users, roles and tenancy

For v1 there is **one household** with **two members** (you + wife) sharing a single
inventory, recipe book and meal plan. But every record is scoped to a `household_id` from
day one so the data model is already multi-tenant — turning it into a product later means
adding sign-up, billing and household invites, not re-modelling the database.

**Member attributes that matter**
- A **diet profile** per member — a *type* rather than a boolean: `omnivore`, `pescatarian`,
  `vegetarian`, `vegan` (extensible), plus optional dislikes/allergens. Each diet type defines
  which **ingredient classes** that member excludes (e.g. pescatarian excludes meat/poultry but
  **allows fish**; vegetarian excludes meat and fish). This drives which version of an ingredient
  each person consumes and feeds AI suggestions.

> **Our household (confirmed):** Artur = omnivore, Jolene = **pescatarian** (eats fish, not meat).
> Practical consequence baked into the model below: on a **meat** night the app plans **two
> versions** of the dish (Artur's meat + Jolene's substitute) by default; on a **fish** night it
> plans a **single shared version** because fish suits both diets. This "two versions most nights,
> shared on fish nights" pattern is the planning default, not a special case.

---

## 4. Core concepts and data model

This section is the heart of the spec — the features fall out of getting these right.

### 4.1 Ingredients and the vegetarian-substitute pairing

An **Ingredient** is a reusable named thing: `Mince Beef`, `Granola`, `Spaghetti`, `Yoghurt`.
No quantities live on the ingredient itself.

The veg/non-veg requirement is modelled as a **substitute link** between ingredients:

```
Ingredient: "Mince Beef"   (is_vegetarian: false)
   └── vegetarian_substitute → Ingredient: "Vegetarian Mince"  (is_vegetarian: true)
```

The substitute kicks in **whenever an ingredient's class is excluded by a member's diet** — not a
hard-coded "vegetarian" rule. When a recipe calls for `Mince Beef` (diet_class: meat), the app
sees Jolene's pescatarian diet excludes meat and plans/shops/depletes `Vegetarian Mince` for her
while Artur gets the beef. But a `Salmon` ingredient (diet_class: fish) is *not* excluded by
pescatarian, so no substitute is triggered and it's a single shared dish. Substituted items are
tracked as **separate inventory items** (you literally buy two packs), which is exactly what you
asked for — "1/3 of mince beef gone and 1/4 of vegetarian mince gone."

Data shape (simplified):

| Entity | Key fields |
|---|---|
| `ingredient` | id, household_id, name, diet_class (meat/fish/dairy/egg/plant/other), default_unit, default_pack_size (optional), category (produce/meat/dairy/dry…), substitute_ingredient_id (nullable), shelf_life_sealed_days, use_within_after_open_days, requires_open_tracking (bool) |

*(`diet_class` replaces a simple `is_vegetarian` flag so the same mechanism handles pescatarian,
vegan, allergens, etc. A member's diet type maps to the set of classes they exclude.)*

> **Two best-before clocks (important):** many products have a long *sealed* shelf life but a
> much shorter window once opened — milk might be in date for weeks unopened, but "use within
> 5 days of opening." So each ingredient carries both `shelf_life_sealed_days` and
> `use_within_after_open_days`, and a `requires_open_tracking` flag for items where opening
> resets the clock (milk, yoghurt, jars, cold cuts) vs items where it doesn't matter much (dried
> pasta, tinned goods). The *effective* best-before of an inventory item is the **earlier** of:
> (sealed best-before) and (opened date + use-within-after-open). See §4.6 and §6.

### 4.2 Recipes, sub-recipes and tags

A **Recipe** is a named dish with a list of recipe-ingredients and optional links to
**sub-recipes**.

- **Simplified ingredients:** a recipe ingredient is just `ingredient_id` (+ optional free-text
  note like "to taste" and an optional quantity hint — see §4.3). No "100g" required.
- **Sub-recipes:** a recipe can include another recipe as a component. *Granola & Yoghurt*
  includes the *Granola* recipe plus the `Yoghurt` ingredient. When generating a shopping
  list or depleting inventory, sub-recipes are recursively expanded into their base ingredients.
  (We guard against circular references.)
- **Tags:** many-to-many. Seeded with `Breakfast`, `Lunch`, `Dinner`; users can add their own
  (e.g. `Quick`, `Batch-cook`, `Date night`). Tags drive meal-plan slots and AI filtering.

| Entity | Key fields |
|---|---|
| `recipe` | id, household_id, name, servings_default, instructions (optional), source_type (manual/photo/url), source_url, image_url |
| `recipe_ingredient` | id, recipe_id, ingredient_id, quantity_hint (nullable), note (nullable), is_optional |
| `recipe_component` | id, parent_recipe_id, child_recipe_id (the sub-recipe link) |
| `tag` | id, household_id, name |
| `recipe_tag` | recipe_id, tag_id |

### 4.3 The quantity question (important design decision)

There's a real tension in your brief: you want recipes to be *simplified* (no gram weights),
but you also want to track that "1/3 of the mince is gone." You can't have precise depletion
without *some* notion of amount. The resolution that keeps entry simple:

- **Recipes stay quantity-light.** An ingredient may optionally carry a coarse `quantity_hint`
  (e.g. "1 pack", "2", "a handful") but it's never required.
- **Inventory is tracked per pack/unit, as a fraction remaining (0–1), not in grams.** When you
  shop, each purchased ingredient becomes an inventory item at `remaining = 1.0` (one "lot").
  Logging usage just decrements the fraction: "used 1/3" → `remaining = 0.67`. Quick-tap presets
  (¼, ⅓, ½, ¾, all) make this a two-second action while cooking.
- This gives you waste/freeze/reconciliation intelligence **without** forcing you to weigh
  anything. It's approximate by design — which matches how a household actually cooks.

> **Decision (confirmed):** inventory is tracked as **fraction of a pack remaining**. Discrete
> counts and real weights are out of scope for v1.

### 4.4 Meal plan

A **MealPlan** is a week (anchored to a start date). Each entry assigns a recipe to a
day + slot (slot = a tag like Breakfast/Lunch/Dinner).

**Default split behaviour:** when an entry's recipe contains an ingredient that one member's diet
excludes, the entry is automatically treated as **two variants** (e.g. Artur = meat, Jolene =
substitute), each shopping for and depleting its own inventory. When no ingredient is excluded for
anyone (e.g. a fish or fully veg dish), it's a **single shared** entry. You can override either way
per entry. Servings/scaling is **not** modelled in v1 — a household portion is assumed fixed.

| Entity | Key fields |
|---|---|
| `meal_plan` | id, household_id, week_start_date, status (planning/active/closed) |
| `meal_plan_entry` | id, meal_plan_id, date, slot_tag_id, recipe_id, is_split (bool), members (who eats) |

### 4.5 Shopping list

Generated from a meal plan by expanding every recipe (and sub-recipe) into base ingredients,
applying veg substitutes per member, **subtracting current inventory and frozen items**, then
de-duplicating. Each line is an ingredient + suggested quantity + check-off state.

| Entity | Key fields |
|---|---|
| `shopping_list` | id, household_id, meal_plan_id, status (draft/shopping/completed) |
| `shopping_list_item` | id, shopping_list_id, ingredient_id, quantity, is_checked, source (plan/manual) |

### 4.6 Inventory and freezer

"Complete shopping" converts checked shopping-list items into **inventory items**. Inventory is
the live picture of the fridge/cupboard/freezer.

| Entity | Key fields |
|---|---|
| `inventory_item` | id, household_id, ingredient_id, location (fridge/pantry/freezer), remaining (0–1), purchased_on, is_opened (bool), opened_on (nullable), sealed_best_before, effective_best_before, status (active/frozen/used/discarded) |
| `usage_log` | id, inventory_item_id, meal_plan_entry_id (nullable), amount_used, logged_at |

**Opening resets the clock.** An inventory item tracks both a `sealed_best_before` (from purchase
date + the ingredient's sealed shelf life) and, once `is_opened` is set, an opened window
(`opened_on` + `use_within_after_open_days`). The `effective_best_before` shown to the user is the
**earlier of the two**. Marking an item "opened" is a one-tap action — and the *first time you log
usage* against a sealed item, the app automatically marks it opened and starts the opened clock,
so you rarely have to do it manually. Items where opening doesn't meaningfully shorten life
(`requires_open_tracking = false`, e.g. dried pasta, tins) skip the opened clock entirely.

Freezing is a status change (`active → frozen`) that **pauses both clocks** and extends the
effective best-before; thawing later restarts the opened window. Frozen items are offered back
into future shopping-list generation so you don't re-buy them.

**Depletion order (FIFO by expiry).** When more than one lot of the same ingredient is on hand,
usage logging and shopping-list subtraction consume the lot with the **earliest
`effective_best_before` first**, so the oldest stock is used before it spoils. (This keeps the
multi-pack maths well-defined — e.g. two packs of mince deplete oldest-first.)

---

## 5. Feature walkthrough (mapped to your brief)

### 5.1 Add & manage recipes
Create a recipe, add simplified ingredients, attach tags, link sub-recipes, set a vegetarian
substitute on any meat ingredient. Reuse ingredients across recipes (autocomplete from your
ingredient library).

### 5.2 Capture a recipe from a photo or a web link
Two AI-assisted import paths that both produce a draft recipe you then confirm/edit:
- **Photo (OCR + extraction):** snap a cookbook page or handwritten card → OCR → an LLM parses
  it into title, ingredients (mapped to your existing ingredient library where possible) and
  steps. Quantities are dropped/simplified to match your model.
- **URL (scrape + extraction):** paste a recipe URL → server fetches the page → first tries
  **schema.org `Recipe` structured data** (most recipe sites expose this, fast and reliable) →
  falls back to an LLM extracting from the page HTML when structured data is missing.
- Either way the user lands on a pre-filled review screen — never auto-saved blindly — and the
  app suggests substitute pairings (e.g. detects "beef mince" → offers to link "Vegetarian Mince").

### 5.3 Weekly meal planning
A week grid (days × slots). Drag/assign recipes into slots, filtered by tag. See the veg/non-veg
implications at a glance. Re-use last week, duplicate days, etc.

### 5.4 Generate the shopping list
One tap from a meal plan. Expands sub-recipes, applies veg substitutes per member, subtracts
on-hand and frozen inventory, aggregates duplicates, groups by store category for easy shopping.

### 5.5 Complete shopping → inventory
Check off what you bought, hit **Complete shopping**, and checked items become active inventory
at full quantity with an AI-estimated best-before. Unbought items can roll over.

### 5.6 Log usage while cooking
From a planned meal (or ad hoc), tap the ingredients used and a quick fraction (¼/⅓/½/all).
Depletes the matching inventory item(s), respecting veg vs non-veg. This is the data that powers
reconciliation and waste-reduction.

### 5.7 Weekly reconciliation (the smart part)
When you start planning week 2 (and every week after):
1. **Stock check:** the app shows current inventory and asks you to **confirm what's actually
   left** (quick adjust the fractions) — reality drifts from logs.
2. **Best-before estimation:** AI estimates typical shelf life per item from purchase date,
   category, storage location **and whether it's been opened** — using the earlier of the sealed
   and opened clocks (§4.6) — flagging what's expiring soon. The confirm step also asks you to
   mark anything you've opened since last week so the shorter clock kicks in.
3. **Freeze suggestions:** items likely to spoil before they'll be used get a "freeze to save"
   prompt; accepting extends their best-before and keeps them in the available pool.
4. **Throw-away pass:** the app *surfaces* items past their estimated date as "worth checking,"
   but **only you** decide what to discard — nothing is ever auto-marked as spoiled or removed
   from inventory by the system. Discards are logged so the app learns your real waste patterns.
5. **Plan around what you have:** the new meal plan and shopping list are built taking remaining
   + frozen stock into account, so you buy less.

### 5.8 AI meal suggestions
"What should we have?" → AI analyses your recipe history, tags, what you cook most, current
inventory and dietary profiles, then suggests meals for a slot — biased toward using stock you
already have (waste reduction) and toward dishes you actually like. Always veg-aware.

---

## 6. AI architecture

All AI work runs as **asynchronous queued jobs** (Laravel queue + Horizon) so the UI never
blocks, with results pushed back to the client.

| Job | Approach | Notes / risks |
|---|---|---|
| URL recipe import | schema.org JSON-LD first, LLM-on-HTML fallback | Cheapest & most accurate when structured data exists; cache by URL |
| Photo recipe import | OCR (cloud OCR or vision-capable LLM) → LLM structuring | Handwriting is the hard case; always land on a review screen |
| Ingredient matching | Embeddings / fuzzy match to existing ingredient library | Prevents "Beef mince" vs "Mince Beef" duplicates; suggests substitute links |
| Best-before estimation | LLM + a cached rules table per category/location, producing **both** a sealed shelf life and a use-within-after-open window | Two clocks: effective best-before = earlier of sealed vs opened (§4.6). Estimates are advisory, never a safety guarantee — show as "typical" with a disclaimer |
| Freeze suggestions | Rules + LLM reasoning over best-before vs planned usage | Some foods freeze badly — maintain a freezability hint per category |
| Meal suggestions | LLM over recipe history + tags + inventory + profiles | Ground the prompt in *your* data to avoid generic output; let users thumbs-up/down to tune |

**Cost control:** prefer deterministic/structured paths before calling an LLM (schema.org,
rules tables, caches). Batch and cache aggressively. For a two-person household the monthly AI
spend is negligible; the patterns above also keep it cheap at product scale.

**Safety note:** best-before output is an estimate to reduce waste, not a food-safety
authority — surface it with clear "use your judgement" wording.

---

## 7. Technical architecture (recommended)

**Shape: API-first.** One backend is the single source of truth; web and mobile are thin clients.

```
                ┌─────────────────────────────┐
                │   Laravel API (PHP 8.3)      │
                │   • Service-class business    │
                │     logic (shared by all)     │
                │   • Sanctum auth              │
                │   • Queue + Horizon (AI jobs) │
                │   • Octane/FrankenPHP runtime │
                └───────────────┬──────────────┘
        ┌───────────────────────┼───────────────────────┐
        │                       │                        │
  ┌─────▼──────┐        ┌───────▼───────┐        ┌───────▼────────┐
  │ Postgres   │        │ Redis         │        │ Object storage │
  │ (data)     │        │ (cache+queue) │        │ (recipe photos)│
  └────────────┘        └───────────────┘        └────────────────┘
        ▲                                                 ▲
        │ JSON API (versioned, /api/v1)                   │
   ┌────┴───────────────┐                    ┌────────────┴─────────────┐
   │  Web app           │                    │  Mobile app              │
   │  Vue/Nuxt or       │                    │  Compose Multiplatform   │
   │  Inertia+Vue (SPA) │                    │  (Android + iOS, shared  │
   │                    │                    │  Kotlin, native camera + │
   │                    │                    │  offline)                │
   └────────────────────┘                    └──────────────────────────┘
```

**Why this split**
- **Laravel backend, API-first:** all logic in service/action classes so web and mobile never
  duplicate rules. Postgres (relational + JSON + full-text for dedup), Redis for cache & queues,
  Horizon for AI jobs, S3-compatible storage (e.g. Cloudflare R2) for photos. Run under Octane/
  FrankenPHP for high throughput.
- **Web app:** consumes the same `/api/v1`. *Inertia + Vue 3* is the fastest to build and very
  snappy; a standalone Nuxt SSR client is the more decoupled option if you want the web fully
  symmetric with mobile. Recommendation: **Inertia + Vue** for v1, revisit if web needs to be a
  fully independent product surface.
- **Mobile:** **Compose Multiplatform** for one Kotlin codebase across Android + iOS, native
  camera (recipe photos), and offline-first local cache that syncs to the API. (CMP's *web*
  target is still Beta in 2026, which is why web is Laravel/JS rather than CMP — the more robust
  choice.)
- **Auth:** Laravel Sanctum — cookie sessions for web, token auth for mobile. Household-scoped
  authorization policies on every resource.
- **Offline & sync (mobile):** local store (SQLDelight) with a sync layer; usage logging and
  planning work offline and reconcile on reconnect. This matters because you'll log usage
  standing at the fridge, not always online.

**Performance summary:** the heavy/slow things (scraping, OCR, LLM calls) are all async jobs;
reads are cached in Redis; mobile is native and offline-capable; the API stays thin and fast.
This is the high-performance shape you asked for, and it scales cleanly if it becomes a product.

---

## 8. Decisions & open questions

**Confirmed — now locked** (recorded as ADRs in the project vault, `~/vault/grocery-app/decisions/`):

- **Inventory granularity** — fraction-of-pack remaining (0–1); discrete counts and real weights are
  out of scope for v1. (§4.3 · ADR-0001)
- **Per-meal veg/non-veg portioning** — **two versions on meat nights** (Artur = meat, Jolene =
  substitute), **single shared dish on fish nights**; overridable per entry. (§4.4 · ADR-0002)
- **Servings / scaling** — not modelled in v1; the household portion is fixed. (§4.4)
- **App name** — **Larder** (branding + high-fidelity UI design done; see `design/`).

**Still open — confirm before build:**

1. **Web framework** — Inertia + Vue (recommended, fastest) vs a decoupled Nuxt SSR SPA. Both
   consume the same `/api/v1`. (§7 · ADR-0004 is *accepted with this point flagged revisitable*.)
2. **Best-before disclaimer wording** — confirm estimates are presented as advisory ("typical — use
   your judgement"), never a food-safety guarantee. (§6)

---

## 9. Phased roadmap

**Phase 0 — Foundations (backend skeleton)**
Laravel API, Postgres schema (§4), auth, household model, deploy pipeline.

**Phase 1 — MVP: manual planning loop**
Recipes (manual entry, simplified ingredients, veg substitutes, tags, sub-recipes) → weekly
meal plan → shopping list generation → Complete shopping → inventory → usage logging. Web app
only. *This delivers the core daily value with zero AI.*

**Phase 2 — Reconciliation & waste reduction**
Weekly stock-confirm, best-before estimation, freeze suggestions, discard pass, inventory-aware
shopping lists.

**Phase 3 — AI capture & suggestions**
URL import, photo/OCR import, ingredient auto-matching, AI meal suggestions.

**Phase 4 — Mobile (CMP)**
Compose Multiplatform Android + iOS client with native camera and offline sync.

**Phase 5 — Productisation (optional)**
Sign-up, household invites, billing, onboarding — flip the multi-tenant switch.

---

## 10. Key risks

- **OCR on handwriting/photos** is the least reliable AI path — mitigate with a mandatory review
  screen and good manual-edit UX.
- **Inventory drift** (logs never perfectly match reality) — mitigated by the weekly confirm step;
  keep it a two-tap correction, not a chore.
- **Best-before liability** — always advisory, never presented as a safety guarantee.
- **Scope creep** — the reconciliation logic is rich; ship the manual loop (Phase 1) first and
  prove the daily habit before layering AI.

---

## 11. Suggested next step

The core §8 decisions are now locked (inventory granularity, veg/non-veg pattern, name = **Larder**),
and the work is broken down in **`TASKS.md`** with the load-bearing decisions captured as ADRs in the
project vault (`~/vault/grocery-app/decisions/`). The next build step is the **Phase 0/1 database
migrations and API design**, then the manual planning loop. Two items remain to confirm before then:
the web framework (§8.1) and the best-before disclaimer wording (§8.2).
