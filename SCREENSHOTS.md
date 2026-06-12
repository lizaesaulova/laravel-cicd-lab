# How to produce the three required screenshots

Use separate temporary branches so the successful long-lived branches remain clean.

## Screenshot 1 — successful pipeline

1. Push the prepared project to `develop`.
2. Open **Actions → Laravel CI/CD**.
3. Open the latest run and expand the jobs so the green `Tests`, `Static analysis`, `Lint`, and `Simulate deployment to development` results are visible.
4. Capture the browser window with repository name, branch, and green status visible.

## Screenshot 2 — test job failure

```bash
git checkout -b demo/failing-test develop
```

Temporarily change this assertion in `tests/Unit/ExampleTest.php`:

```php
self::assertSame(151.0, $resultPrice);
```

Commit and push:

```bash
git add tests/Unit/ExampleTest.php
git commit -m "test: demonstrate failing test"
git push -u origin demo/failing-test
```

Open the failed workflow run, expand **Tests and coverage ≥ 50%**, and capture the failed assertion. Afterwards delete the branch or revert the commit.

To demonstrate the coverage gate specifically instead, temporarily change `--min=50` to `--min=100` in the workflow and add an intentionally uncovered class under `app/`.

## Screenshot 3 — linter failure

Create a pull request into `develop` because pull requests use Pint check-only mode:

```bash
git checkout -b demo/failing-lint develop
```

Add an obvious formatting violation to `app/Services/DiscountCalculator.php`, for example:

```php
if($price < 0.0){
```

Commit and push without running Pint:

```bash
git add app/Services/DiscountCalculator.php
git commit -m "style: demonstrate linter failure"
git push -u origin demo/failing-lint
```

Create a pull request from `demo/failing-lint` to `develop`. Open the failed workflow run, expand **Lint and type-style rules**, and capture the Pint error. Close the pull request and delete the branch after taking the screenshot.

Note: a direct push to a non-long-lived branch auto-formats code by design, so a pull request is the reliable way to demonstrate the check-only linter failure.
