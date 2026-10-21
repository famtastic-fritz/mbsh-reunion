# MBSH local-main worktree re-anchor — October 4, 2026

**Classification:** `changed` for local Git organization and this source receipt only. No customer-facing capability, hosting, data, payment, provider, or owner-acceptance state changed.

The source-only release gate advanced local `main` from `414e2d029d82f8fb02f7f7065e2bef453b4a1bb3` to `35784679efab4cd246335767acf1f7bff830d3ff`. Another worktree, `sites/site-mbsh-reunion-cinematic-proof`, was still checked out on `main` with its index and files based on the old commit. Moving the branch ref without updating that checked-out worktree made `git status` report 284 apparent staged changes, including broad deletions. This was an integration bookkeeping mistake, not evidence that the owner had staged those deletions.

Read-only diagnosis proved `git diff --cached --quiet 414e2d0` passed: the affected worktree's index matched the previous `main` exactly. Compared with that previous commit, it had **37 genuine tracked edits** and **26 untracked paths**. Thirty-five of the 37 edited paths overlap files changed on newer `main`, so an automatic restore/reset or blind patch application could lose real work.

Before changing Git pointers, a private local manifest recorded the index SHA-256 `c020739ce1aed972c85dda86ef554ae42ae0daa3a0f72a9aacbf0d44d6b13417` and hashes of all 63 tracked-edited/untracked files. The manifest itself has SHA-256 `4164644c2551bb3843e3a90c72786ee8f7091b26364ac61c588385220df585f5` and remains permission-restricted under `/private/tmp/mbsh-cinematic-pre-reanchor-20261004.json`; it is not a portable customer artifact.

Recovery created `recovery/mbsh-pre-integration-working-tree-20261004` at the old commit and changed only the affected worktree's `HEAD` symbolic ref to that branch. Its file and index bytes did not change. It now correctly reports **37 tracked edits, 26 untracked paths, zero staged paths**. The clean `worktrees/mbsh-main-release-gate-20261004` checkout was then attached to local `main` at `3578467` and reports no changes. Post-change hashing matched all 63 file bytes and the original index hash. No reset, restore, stash, worktree pruning, or source-file overwrite occurred.

The actual next step is a separate three-way review of the 37 recovery edits against current main, with explicit treatment of the 35 overlapping paths. The Event Cinema worktree and release-retention branch remain distinct. The host's earlier live marker `67f2248`, its 251/251 code-manifest check, and all database/uploads/owner/provider gates are unchanged by this local repair.

Review: the reusable lesson is to inspect every worktree checked out on a branch before moving its ref. Prefer a normal branch checkout/merge in the intended worktree. If a ref movement leaves an old index behind, prove its base and preserve the real diff before re-anchoring pointers; do not clean a misleading status display by deleting files.
