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
