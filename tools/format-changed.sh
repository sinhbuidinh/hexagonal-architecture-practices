#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

# shellcheck source=lib/changed-files.sh
source "$root/tools/lib/changed-files.sh"

dry_run=0
if [[ "${1:-}" == "--check" ]]; then
    dry_run=1
fi

files=()
while IFS= read -r file; do
    files+=("$file")
done < <(get_changed_files | collect_formattable_php)

if [[ ${#files[@]} -eq 0 ]]; then
    echo "No changed PHP files in format scope (SCOPE=${SCOPE:-worktree}${PACKAGE:+, PACKAGE=${PACKAGE}})."
    exit 0
fi

echo "Formatting ${#files[@]} file(s) (SCOPE=${SCOPE:-worktree}${PACKAGE:+, PACKAGE=${PACKAGE}}):"
printf '  %s\n' "${files[@]}"

args=(
    vendor/bin/php-cs-fixer
    fix
    --config=.php-cs-fixer.dist.php
    --allow-risky=yes
    --path-mode=intersection
)

if [[ "$dry_run" -eq 1 ]]; then
    args+=(--dry-run --diff)
fi

"${args[@]}" -- "${files[@]}"
