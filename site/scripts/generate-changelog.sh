#!/usr/bin/env bash

# Generates a Markdown snippet grouping commits by Conventional Commit type.
# Usage: ./site/scripts/generate-changelog.sh <from-ref> [to-ref]

set -euo pipefail

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Error: this script must be executed inside a Git repository." >&2
  exit 1
fi

if [[ $# -lt 1 || $# -gt 2 ]]; then
  echo "Usage: $0 <from-ref> [to-ref]" >&2
  exit 1
fi

from_ref="$1"
to_ref="${2:-HEAD}"

# Validate references early to provide a clearer error.
if ! git rev-parse --verify "$from_ref^{commit}" >/dev/null 2>&1; then
  echo "Error: invalid from-ref '$from_ref'." >&2
  exit 1
fi

if ! git rev-parse --verify "$to_ref^{commit}" >/dev/null 2>&1; then
  echo "Error: invalid to-ref '$to_ref'." >&2
  exit 1
fi

if [[ "$from_ref" == "$to_ref" ]]; then
  echo "No changes: from-ref and to-ref are identical." >&2
  exit 0
fi

log_output="$(git log --no-merges --pretty=format:'%h%x09%s' "$from_ref..$to_ref")"

if [[ -z "$log_output" ]]; then
  echo "No commits found between $from_ref and $to_ref."
  exit 0
fi

echo "$log_output" | awk '
function heading(text) {
  print ""
  print "### " text
  print ""
}

function format_line(message, hash) {
  printf("- %s (`%s`)\n", message, hash)
}

{
  hash = $1
  sub(/^[^\t]+\t/, "", $0)
  message = $0

  if (message ~ /^feat(\(.+\))?:[[:space:]]*/) {
    if (!feat_printed) {
      heading("Features")
      feat_printed = 1
    }
    sub(/^feat(\(.+\))?:[[:space:]]*/, "", message)
    format_line(message, hash)
  } else if (message ~ /^fix(\(.+\))?:[[:space:]]*/) {
    if (!fix_printed) {
      heading("Fixes")
      fix_printed = 1
    }
    sub(/^fix(\(.+\))?:[[:space:]]*/, "", message)
    format_line(message, hash)
  } else if (message ~ /^docs(\(.+\))?:[[:space:]]*/) {
    if (!docs_printed) {
      heading("Documentation")
      docs_printed = 1
    }
    sub(/^docs(\(.+\))?:[[:space:]]*/, "", message)
    format_line(message, hash)
  } else if (message ~ /^refactor(\(.+\))?:[[:space:]]*/) {
    if (!refactor_printed) {
      heading("Refactors")
      refactor_printed = 1
    }
    sub(/^refactor(\(.+\))?:[[:space:]]*/, "", message)
    format_line(message, hash)
  } else if (message ~ /^perf(\(.+\))?:[[:space:]]*/) {
    if (!perf_printed) {
      heading("Performance")
      perf_printed = 1
    }
    sub(/^perf(\(.+\))?:[[:space:]]*/, "", message)
    format_line(message, hash)
  } else if (message ~ /^test(\(.+\))?:[[:space:]]*/) {
    if (!test_printed) {
      heading("Tests")
      test_printed = 1
    }
    sub(/^test(\(.+\))?:[[:space:]]*/, "", message)
    format_line(message, hash)
  } else if (message ~ /^chore(\(.+\))?:[[:space:]]*/) {
    if (!chore_printed) {
      heading("Chores")
      chore_printed = 1
    }
    sub(/^chore(\(.+\))?:[[:space:]]*/, "", message)
    format_line(message, hash)
  } else {
    if (!other_printed) {
      heading("Other")
      other_printed = 1
    }
    format_line(message, hash)
  }
}

END {
  print ""
}
'
