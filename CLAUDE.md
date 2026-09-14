# Claude project guidance

Follow `AGENTS.md`. Use `site-studio/recipe/HEADLESS_EVENT_PLATFORM_RECIPE.md`
as the repeatable build recipe. Record material architectural or workflow changes in
the canonical architecture and retrospective documents rather than inventing a
parallel plan.

Public-page work must preserve the universal composition contract:
`SharedShell(Header + Navigation + Footer) + PageContent(Sections) +
PageAwareHarry`. Read `docs/architecture/PUBLIC_PAGE_COMPONENT_CONTRACT_2026-08-17.md`
and run `node tests/frontend/shared-public-shell.test.mjs`. Do not copy a new
header/nav/footer into one page or use relative global links from nested routes.

Campaign creative must follow
`docs/creative/HI_TIDE_HARRY_CAMPAIGN_CREATIVE_RECIPE_2026-08-17.md` so Hi-Tide
Harry, the reunion medallion, event facts, crops, and rights QA stay consistent.


## Independent source contract (2026-09-14)

Read `.famtastic/site-manifest.json`, `design.md`, `SITE-LEARNINGS.md` and `CONVERSATIONS.md`. This business has its own source repository; FAMtastic Designs is the builder. Preserve authored source, backend, privacy and operational records during rebuilds. Run `node .famtastic/verify-repository.mjs`; source validation is not a deployment or business-data migration.
