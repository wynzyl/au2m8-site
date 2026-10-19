# SC-008 Baseline: `/projects` response time

Measured 2026-10-19 on the Herd site `http://au2ma8-site.test/projects` with
`curl.exe -s -o NUL -w "%{time_total}"`, 21 requests, first discarded, median of the other 20.

| Measurement | Projects | Median (s) |
|---|---|---|
| T001: before any change (hard-coded array) | 2 | 0.0493 |
| T039: same session, old code restored temporarily (hard-coded array) | 2 | 0.0583 |
| T039: same session, new code (database) | 2 | 0.0607 |
| T039: same session, new code (database) | 20 | 0.0617 |

**Result: pass.** With 20 projects the page is 5.8% slower than the old code measured in the same
session (0.0617 s against 0.0583 s), within the 10% limit.

The T001 figure is not directly comparable. By the time of T039 the whole machine was slower: the
unchanged `/about` page also measured 0.059–0.061 s. So the old code was re-measured in the same
session by stashing the `PageController` and view changes for one run.
