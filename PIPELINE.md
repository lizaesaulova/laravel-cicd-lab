# CI/CD pipeline for the Laravel application

## 1. Purpose

The repository uses GitHub Actions to validate a Laravel application and simulate deployments. The workflow file is `.github/workflows/ci.yml`.

The long-lived branches are:

- `develop` — development environment;
- `uat` — user acceptance testing environment;
- `main` or `master` — production environment.

The workflow runs on every push. Pull requests are validated when their target branch is `develop`, `uat`, `main`, or `master`.

## 2. Environment files

The repository contains non-secret templates:

| File | Purpose |
|---|---|
| `.env.dev` | Development deployment simulation |
| `.env.uat` | UAT deployment simulation |
| `.env.prod` | Production deployment simulation |
| `.env.ci` | CI tests with SQLite `:memory:` and debug disabled |

The real `.env` is ignored by Git and must never be committed. `APP_KEY` is intentionally blank in the committed templates. In CI, the key is generated temporarily. Real database passwords and application keys must be stored as environment secrets, not committed.

## 3. Pipeline jobs and gates

### Tests

The test job installs dependencies, copies `.env.ci` to `.env`, generates a temporary key, and runs:

```bash
php artisan test --coverage --min=50
```

The job fails when a test fails or total application coverage is lower than 50%. PCOV is enabled in the runner.

### Static analysis

Larastan and PHPStan strict rules analyze `app` and `routes` at level 6:

```bash
./vendor/bin/phpstan analyse --no-progress --memory-limit=2G
```

No baseline is used. Every reported PHPStan error fails the job.

### Linting and typing rules

On pull requests and long-lived branches, Pint runs in check-only mode:

```bash
./vendor/bin/pint --test
```

On pushes to other branches, Pint formats the code and the workflow commits the changes back with a `[skip ci]` commit message. The custom `tools/check-native-types.php` checker fails when a named function or method has an untyped parameter or lacks a return type where PHP permits one. It also checks `<?php`, `strict_types`, camelCase variable names, forbidden short tags, and direct HTML output through `echo`/`print`.

The Laravel/PSR-12 formatter requires four-space indentation and omits the closing `?>` tag in PHP-only files. Those two rules conflict with the supplied course notes requiring tabs and a closing tag. The pipeline follows the explicit laboratory requirement to use the Laravel/PSR-12 preset; all non-conflicting course rules are followed, including `<?php`, meaningful English camelCase names, strict declarations, and typed parameters/returns.

### Deployment simulation

Deployment jobs depend on successful tests, static analysis, and linting:

| Branch | File copied to `.env` | Output |
|---|---|---|
| `develop` | `.env.dev` | `Deploying to development with .env.dev` |
| `uat` | `.env.uat` | `Deploying to uat with .env.uat` |
| `main` / `master` | `.env.prod` | `Deploying to production with .env.prod` |

No actual server deployment is performed.

## 4. Required manual production approval

Create a GitHub Environment named exactly `production`:

1. Open repository **Settings → Environments**.
2. Create or open `production`.
3. Enable **Required reviewers**.
4. Select at least one maintainer/reviewer and save the protection rule.

The `deploy-production` job references this environment, so it waits for reviewer approval before running. The `development` and `uat` environments may also be created, but they do not need reviewers.

## 5. Optional maintainer notification

The final job can send the result to selected Telegram chats. Add these repository Actions secrets:

- `TELEGRAM_BOT_TOKEN` — token from BotFather;
- `TELEGRAM_CHAT_IDS` — one chat ID or several comma-separated chat IDs.

When the secrets are absent, the notification step logs that it was skipped and does not fail the pipeline.

## 6. Local verification

```bash
composer install
bash scripts/verify-local.sh
```

Local coverage requires PCOV or Xdebug.

## 7. Creating the repository and branches

```bash
git init
git add .
git commit -m "feat: add Laravel application and CI/CD pipeline"
git branch -M main
git remote add origin <REPOSITORY_SSH_OR_HTTPS_URL>
git push -u origin main
bash scripts/create-branches.sh main
```

For a repository whose production branch is `master`, pass `master` to the script.

## 8. Required screenshots

See `SCREENSHOTS.md`. Keep the successful and intentionally failed runs in the GitHub Actions history until the work has been graded.

## 9. References

- Laravel testing documentation: coverage uses `--coverage`, and `--min` makes the suite fail below the threshold.
- Larastan documentation: install `larastan/larastan` and include its extension in `phpstan.neon`.
- GitHub Environments documentation: a job that references a protected environment waits for a required reviewer.
