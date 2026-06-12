# Laravel CI/CD laboratory overlay

This folder contains the files required for the CI/CD laboratory. It is designed to be copied over a **fresh Laravel 12 project**.

## Fast installation

```bash
composer create-project laravel/laravel laravel-cicd-lab "^12.0"
cd laravel-cicd-lab
# Copy all files from this overlay into the project, preserving paths.
composer update
cp .env.ci .env
php artisan key:generate --force
bash scripts/verify-local.sh
```

Then initialize Git, create a GitHub repository, push `main`, and run:

```bash
bash scripts/create-branches.sh main
```

Read `PIPELINE.md` for repository settings, production approval, notification secrets, and screenshot scenarios.
