# Graph Report - grocery-app  (2026-06-21)

## Corpus Check
- 3 files · ~3,536 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 20 nodes · 18 edges · 5 communities (1 shown, 4 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `11d2f530`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- [[_COMMUNITY_Community 0|Community 0]]
- [[_COMMUNITY_Community 1|Community 1]]
- [[_COMMUNITY_Community 2|Community 2]]
- [[_COMMUNITY_Community 3|Community 3]]
- [[_COMMUNITY_Community 4|Community 4]]

## God Nodes (most connected - your core abstractions)
1. `VAULT.md — per-repo vault configuration` - 2 edges
2. `framework_path: ~/workspace/vault   # override the global framework install (rarely needed)` - 2 edges
3. `optional: [research, legal]          # folders that may be absent without a warning` - 2 edges
4. `Vault memory stack` - 1 edges
5. `config` - 1 edges
6. `structure` - 1 edges
7. `add_folders: [runbooks]              # extra folders to scaffold + treat as vault dirs` - 1 edges
8. `rename: {indications: conventions}   # local aliases for standard folders` - 1 edges
9. `behaviour` - 1 edges
10. `load_context_extra: [runbooks]       # folders Step 2 loads beyond the defaults` - 1 edges

## Surprising Connections (you probably didn't know these)
- None detected - all connections are within the same source files.

## Import Cycles
- None detected.

## Communities (5 total, 4 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.17
Nodes (11): add_folders: [runbooks]              # extra folders to scaffold + treat as vault dirs, add: [./vault/personas/billing-domain.md]   # custom persona files (repo-relative), load_context_extra: [runbooks]       # folders Step 2 loads beyond the defaults, personas:, project_type: api-laravel            # api-laravel | nuxt | flutter — selects the default persona pack, rename: {indications: conventions}   # local aliases for standard folders, skip: [skeptic]                    # drop a persona by id, team_max_parallel_critics: 3         # critics selected per change (hard max 5) (+3 more)

## Knowledge Gaps
- **15 isolated node(s):** `Vault memory stack`, `config`, `structure`, `add_folders: [runbooks]              # extra folders to scaffold + treat as vault dirs`, `rename: {indications: conventions}   # local aliases for standard folders` (+10 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **4 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `VAULT.md — per-repo vault configuration` connect `Community 3` to `Community 0`?**
  _High betweenness centrality (0.094) - this node is a cross-community bridge._
- **Why does `framework_path: ~/workspace/vault   # override the global framework install (rarely needed)` connect `Community 4` to `Community 0`?**
  _High betweenness centrality (0.094) - this node is a cross-community bridge._
- **Why does `optional: [research, legal]          # folders that may be absent without a warning` connect `Community 2` to `Community 0`?**
  _High betweenness centrality (0.094) - this node is a cross-community bridge._
- **What connects `Vault memory stack`, `config`, `structure` to the rest of the system?**
  _15 weakly-connected nodes found - possible documentation gaps or missing edges._