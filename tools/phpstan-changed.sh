#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

# shellcheck source=lib/changed-files.sh
source "$root/tools/lib/changed-files.sh"

declare -a pure_files=()
declare -a laravel_files=()
declare -a symfony_files=()

while IFS=$'\t' read -r stack file; do
    [[ -z "$stack" ]] && continue
    case "$stack" in
        pure-php) pure_files+=("$file") ;;
        laravel) laravel_files+=("$file") ;;
        symfony) symfony_files+=("$file") ;;
    esac
done < <(get_changed_files | collect_phpstan_php)

total=$((${#pure_files[@]} + ${#laravel_files[@]} + ${#symfony_files[@]}))

if [[ "$total" -eq 0 ]]; then
    echo "No changed PHP files in PHPStan scope (SCOPE=${SCOPE:-worktree}${PACKAGE:+, PACKAGE=${PACKAGE}})."
    exit 0
fi

echo "Analysing ${total} file(s) (SCOPE=${SCOPE:-worktree}${PACKAGE:+, PACKAGE=${PACKAGE}}):"

run_stack() {
    local name="$1"
    local config="$2"
    shift 2
    local -a stack_files=("$@")

    if [[ ${#stack_files[@]} -eq 0 ]]; then
        return 0
    fi

    echo "  ${name}: ${#stack_files[@]} file(s)"
    vendor/bin/phpstan analyse \
        --configuration="$config" \
        --memory-limit=512M \
        "${stack_files[@]}"
}

run_stack "pure-php" "phpstan/pure-php.neon" "${pure_files[@]+"${pure_files[@]}"}"
run_stack "laravel" "phpstan/laravel.neon" "${laravel_files[@]+"${laravel_files[@]}"}"
run_stack "symfony" "phpstan/symfony.neon" "${symfony_files[@]+"${symfony_files[@]}"}"
