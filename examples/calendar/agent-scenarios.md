# Agent scenario tests for siesta-carbon

These scenarios mirror `php tools/siesta-cli/bin/siesta test:scenarios`.

| # | Scenario | Flow |
|---|----------|------|
| 1 | Add 2 weeks and format | create(now) → addWeeks(2) → format(Y-m-d) |
| 2 | Parse and diff days | parse(2026-01-01) → now → diffInDays |
| 3 | Business week boundaries | configure(weekStartsAt) → startOfWeek → endOfWeek |
| 4 | Timezone switch | configure → setTimezone → format |
| 5 | Weekend check | createFromDate → isWeekend |
| 6 | Month boundaries | startOfMonth → endOfMonth |
| 7 | Error recovery | addWeeks(-1) → retry with suggestedFix |
| 8 | Batch workflow | batch(configure + create) |

Run: `composer test` or `php tools/siesta-cli/bin/siesta test:scenarios`
