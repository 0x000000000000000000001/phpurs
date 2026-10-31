#!/usr/bin/env bash
set -euo pipefail

# Parse every shell entry point and, when available, run shellcheck on it.
# CI sets SHELLCHECK_REQUIRED=1 so a runner without shellcheck fails instead of
# silently skipping the static analysis.
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
files=()
for file in "$root"/bin/*; do
  [ -f "$file" ] || continue
  case "$(head -n 1 "$file")" in
    *bash* | *sh*) files+=("$file") ;;
  esac
done

status=0
for file in "${files[@]}"; do
  bash -n "$file" || status=1
done

if command -v shellcheck >/dev/null 2>&1; then
  shellcheck -x "${files[@]}" || status=1
elif [ "${SHELLCHECK_REQUIRED:-0}" = "1" ]; then
  echo "shellcheck is required but not installed" >&2
  status=1
fi

if [ "$status" -eq 0 ]; then
  echo "${#files[@]} shell scripts checked."
fi
exit "$status"
