#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

search_roots=(".")
if [[ -n "${PACKAGE:-}" ]]; then
    search_roots=("./${PACKAGE}")
fi

patterns=(
    '*.php'
    '*.neon'
    '*.md'
    '*.json'
    '*.yml'
    '*.yaml'
    '*.xml'
    '*.sh'
    '.editorconfig'
)

found=0
for search_root in "${search_roots[@]}"; do
    while IFS= read -r -d '' file; do
        printf '%s\n' "$file"
        found=1
    done < <(
        find "$search_root" -type f \( \
            -name '*.php' -o -name '*.neon' -o -name '*.md' -o -name '*.json' \
            -o -name '*.yml' -o -name '*.yaml' -o -name '*.xml' -o -name '*.sh' \
            -o -name '.editorconfig' \
        \) \
            ! -path './vendor/*' \
            ! -path './node_modules/*' \
            ! -path '*/vendor/*' \
            ! -path '*/node_modules/*' \
            ! -path './.git/*' \
            -print0 | xargs -0 grep -l $'\t' 2>/dev/null || true
    )
done

if [[ "$found" -eq 1 ]]; then
    echo "Tab characters found in project files (use 4 spaces). Run: composer spaces:fix" >&2
    exit 1
fi

echo "OK: no tab characters in tracked project sources."
