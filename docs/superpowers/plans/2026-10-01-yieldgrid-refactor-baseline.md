# Refactor Baseline — 2026-10-01 (HEAD b2f762c)

One line per command. Rule: new failures vs this baseline must be zero at every gate.

- `backend: ./vendor/bin/pint --test` → PASS (303 files)
- `backend: ./vendor/bin/phpstan analyse --level=6 --no-progress` → FAILS environmentally (parallel runner `listen EPERM` under sandbox); workaround `./vendor/bin/phpstan analyse --level=6 --no-progress --debug` → PASS, no errors. All later gates use the `--debug` form.
- `backend: php artisan test` → 165 failed / 1 risky / 5 passed. ALL 165 failures are `SQLSTATE[08006]` (no Postgres reachable from sandbox — no TCP, no socket, no binaries). Failure signature saved at `.superpowers/sdd/2026-10-01-yieldgrid-refactor/pest-baseline.txt` (166 lines). `php artisan test --testsuite=Unit` → 5 passed (true green signal).
- `frontend: npx oxlint .` → PASS (0 warnings, 0 errors, 165 files). NOTE: baseline used non-mutating form; package `lint` script runs `--fix`.
- `frontend: npx eslint .` → PASS (exit 0, non-mutating form).
- `frontend: npm run format:check` → PASS (all files Prettier-clean).
- `frontend: npx vitest run` → PASS (31 files, 117 tests).
- `frontend: npx playwright test` → SKIP (config webServer cannot `listen` under sandbox, EPERM; 1 test listed, never runs).

Comparator rule: backend gates pass when the Pest failure set is IDENTICAL to the signature file (diff empty) and Unit stays 5/5; frontend gates pass on literal green.

## Task 12 amendment (2026-10-01)

58 new boundary tests added (`tests/Feature/Marketplace/*BoundaryTest.php`). They fail on `SQLSTATE[08006]` like all Feature tests (no DB in sandbox) and were verified loadable via `php -l` + `--list-tests` (58 discovered) but NOT executed. Working comparator for Tasks 13–14 is `.superpowers/sdd/2026-10-01-yieldgrid-refactor/pest-baseline.txt` (222 failed / 1 risky / 5 passed): non-boundary failures must stay identical, all boundary failures must stay `QueryException`.
