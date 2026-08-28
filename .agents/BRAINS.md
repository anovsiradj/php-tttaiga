# AGENT BRAIN OPERATIONAL ENGINE

## SECTION 1: SYSTEM BOUNDARIES (NON-NEGOTIABLE)

- **SCOPE ISOLATION:** All naming formats, line limits, and context pruning rules apply EXCLUSIVELY to `./.agents/brains/*.md`. NEVER modify or apply these rules to standard project documentation (`README.md`, `docs/*.md`, etc.).
- **HIERARCHY OF TRUTH:** `./.agents/brains/*.md` is the SINGLE SOURCE OF TRUTH. If codebase code conflicts with `brains/`, `brains/` ALWAYS wins.
- **AUDIENCE:** You are writing for OTHER AGENTS, not humans. Use extreme token-efficient shorthand, bullet points, and pseudo-code. No fluff.

---

## SECTION 2: EXECUTION PIPELINE (IF-THEN TRIGGERS)

Follow this deterministic workflow for EVERY interaction:

### STEP 1: PRE-FLIGHT LOOKUP
- **IF** user prompt contains specific technical keywords/features/files:
- **THEN** search and read ONLY the matching `./.agents/brains/[category]-[topic].md` files BEFORE writing code.

### STEP 2: WRITE-ON-DISCOVERY (AUTO-PERSIST)
- **IF** inspecting codebase/workspace reveals NEW unrecorded patterns, stack details, database schemas, or rules:
- **THEN** immediately create or update `./.agents/brains/[category]-[topic].md` BEFORE returning the final response. Do not ask for user permission.

### STEP 3: CONFLICT CHECK & PUSHBACK
- **IF** user prompt violates rules in `brains/` or introduces technical debt/flaws:
- **THEN** STOP. Execute Radical Candor: push back directly, explain the violation objectively, and propose the architecturally sound alternative. Do not apologize.

---

## SECTION 3: FREEDOM & AUTONOMY ZONE (AGENT IMPLEMENTATION)

You have 100% free will and autonomy over HOW you structure and manage knowledge inside `./.agents/brains/*.md`:

- **AUTONOMOUS TAXONOMY:** You decide file names using `[category]-[topic].md` (e.g., `db-postgres.md`, `ui-darktheme.md`). Split or merge files whenever a single topic grows to a few hundred lines or covers multiple domains.
- **FREEFORM WRITING:** Choose any machine-readable syntax (Markdown bullets, YAML-like key-values, pseudo-code, 5W1H format) that best preserves intent and saves tokens.
- **SELF-CLEANING:** Delete obsolete rules freely. If a file inside `brains/` becomes empty, physically DELETE the file. Maintain ONLY active current reality—no changelogs or history.

---

## SECTION 4: ANTI-HALLUCINATION & INTEGRITY

- **ZERO GUESSWORK:** Verify actual existence of tools/libraries in the workspace before documenting or implementing.
- **MISSING CONTEXT:** If critical context is absent from both workspace and `brains/`, ask the user directly instead of hallucinating workarounds.
