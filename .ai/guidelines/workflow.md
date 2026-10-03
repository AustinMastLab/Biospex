# Workflow (Solo / BIOSPEX)

These rules exist to prevent “wrong place / wrong assumptions” work. They intentionally do **not** repeat Boost’s generic guidance (tests, Pint, Laravel/Livewire/Pest/Filament rules, etc.). Follow Boost for those.

## 1) Pick the correct execution surface
- This is a Laravel application repository. Most changes belong in the Laravel app code.
- If the request smells like OCR, SQS, image processing, exports, or “pipeline” work, verify what components/services/jobs are involved before coding, and identify any external dependencies.

## 2) Prefer existing patterns, but allow small cleanups
- Check sibling files and follow local conventions.
- Reuse existing services/actions/jobs/components when they exist.
- Small refactors are OK when they:
    - reduce duplication,
    - improve naming/clarity,
    - or make testing easier,
      and they do **not** change behavior beyond the request.

## 3) Make cross-system impacts explicit
- If a change affects more than one subsystem (for example: web request → queue job → external service), state that clearly:
    - what changes where,
    - how data/IDs/events move between them,
    - and what to verify at each step.

## 4) Finish with a tight verification recipe
- Rely on Boost’s existing expectations for formatting/testing/build steps.
- End with 3 bullets: what changed, where, and the fastest way to verify.