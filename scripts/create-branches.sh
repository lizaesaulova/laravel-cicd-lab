#!/usr/bin/env bash
set -euo pipefail

mainBranch="${1:-main}"

git checkout "$mainBranch"
git pull --ff-only

git checkout -b develop
git push -u origin develop

git checkout "$mainBranch"
git checkout -b uat
git push -u origin uat

git checkout "$mainBranch"

echo "Created and pushed develop and uat branches."
