#!/usr/bin/env bash
# Programme complet sur un même TAST : classe privée avec (tag) ou sans (lite) tag.
# Usage : run-full.sh <espace de build contenant output/ et vendor/>
set -euo pipefail
WORKSPACE=${1:?usage: run-full.sh <workspace containing output/ and vendor/>}
PHP=${PHP:-/opt/homebrew/bin/php}
D=$(mktemp -d "${TMPDIR:-/tmp}/full-ab.XXXXXX")
trap 'rm -rf "$D"' EXIT
cp -R "$WORKSPACE/output" "$D/lite"
cp -R "$WORKSPACE/output" "$D/tag"
sed -i '' "s/final class Test_RBTree___phpurs_enum_0_T { public function __construct(/final class Test_RBTree___phpurs_enum_0_T { public \$tag = '__phpurs_enum_0_T'; public function __construct(/" "$D/tag/Test.RBTree/index.php"
ln -s "$WORKSPACE/vendor" "$D/lite/vendor"
ln -s "$WORKSPACE/vendor" "$D/tag/vendor"
run() {
  local out
  out=$("$PHP" -d xdebug.mode=off -d opcache.enable_cli=1 -d opcache.file_update_protection=0 \
    -d opcache.jit_buffer_size=128M -d opcache.jit=1255 "$D/$1/App/main.mod.php" 2>&1)
  local code=$?
  printf '%s exit=%s %s\n' "$1" "$code" "$(printf '%s\n' "$out" | grep 'Total exec time' | tail -1)"
}
run lite
run tag
run tag
run lite
