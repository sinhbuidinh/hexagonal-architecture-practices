#!/usr/bin/env bash
# Shared helpers for "changed files only" tooling.
# SCOPE: worktree (default) | staged | branch
# BASE:  merge-base ref when SCOPE=branch (default: main, else master, else HEAD~1)

set -euo pipefail

get_changed_files() {
    local scope="${SCOPE:-worktree}"

    case "$scope" in
        worktree)
            {
                git diff --name-only --diff-filter=ACMRTUXB HEAD 2>/dev/null || true
                git diff --cached --name-only --diff-filter=ACMRTUXB 2>/dev/null || true
            } | sort -u | sed '/^$/d'
            ;;
        staged)
            git diff --cached --name-only --diff-filter=ACMRTUXB 2>/dev/null | sed '/^$/d'
            ;;
        branch)
            local base="${BASE:-}"
            if [[ -z "$base" ]]; then
                base="$(git merge-base HEAD main 2>/dev/null || git merge-base HEAD master 2>/dev/null || echo HEAD~1)"
            fi
            git diff --name-only --diff-filter=ACMRTUXB "${base}...HEAD" 2>/dev/null | sed '/^$/d'
            ;;
        *)
            echo "Unknown SCOPE=${scope} (use worktree, staged, or branch)" >&2
            return 1
            ;;
    esac
}

is_formattable_php() {
    local file="$1"
    [[ "$file" == *.php ]] || return 1
    [[ -f "$file" ]] || return 1

    case "$file" in
        pure-php/src/*|pure-php/tests/*|pure-php/public/*|pure-php/bin/*) return 0 ;;
        laravel/app/*|laravel/routes/*|laravel/tests/*) return 0 ;;
        symfony/src/*) return 0 ;;
        *) return 1 ;;
    esac
}

phpstan_stack_for() {
    local file="$1"

    case "$file" in
        pure-php/src/*|pure-php/tests/*) echo pure-php ;;
        laravel/app/*|laravel/routes/*|laravel/tests/*) echo laravel ;;
        symfony/src/*) echo symfony ;;
        *) echo "" ;;
    esac
}

collect_formattable_php() {
    local file
    while IFS= read -r file; do
        if is_formattable_php "$file"; then
            printf '%s\n' "$file"
        fi
    done
}

collect_phpstan_php() {
    local file stack
    while IFS= read -r file; do
        stack="$(phpstan_stack_for "$file")"
        if [[ -n "$stack" && "$file" == *.php ]]; then
            printf '%s\t%s\n' "$stack" "$file"
        fi
    done
}
