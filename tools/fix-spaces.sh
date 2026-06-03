#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

while IFS= read -r -d '' file; do
    expand -t 4 "$file" > "${file}.tmp"
    mv "${file}.tmp" "$file"
done < <(
    find . -type f \( \
        -name '*.php' -o -name '*.neon' -o -name '*.md' -o -name '*.json' \
        -o -name '*.yml' -o -name '*.yaml' -o -name '*.xml' -o -name '*.sh' \
        -o -name '.editorconfig' \
    \) \
        ! -path './vendor/*' \
        ! -path './node_modules/*' \
        ! -path '*/vendor/*' \
        ! -path '*/node_modules/*' \
        ! -path './.git/*' \
        -print0
)

echo "Converted tabs to 4 spaces in project source files."
