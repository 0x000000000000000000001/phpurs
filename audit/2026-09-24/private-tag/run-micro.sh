#!/usr/bin/env bash
# Mesure isolée de RBTree : A référence, B champs typés, C sans tag, D les deux.
# Usage : run-micro.sh <espace de build contenant output/>
set -euo pipefail
ROOT=$(cd "$(dirname "$0")" && pwd)
WORKSPACE=${1:?usage: run-micro.sh <workspace containing output/>}
PHP=${PHP:-/opt/homebrew/bin/php}
D=$(mktemp -d "${TMPDIR:-/tmp}/rbtree-ab.XXXXXX")
trap 'rm -rf "$D"' EXIT
for v in A B C D; do cp -R "$WORKSPACE/output" "$D/$v"; done
sed -i '' 's/public  \$value0, public  \$value1, public  \$value2, public  \$value3/public int $value0, public  $value1, public int $value2, public  $value3/' "$D/B/Test.RBTree/index.php"
sed -i '' "s/ public \$tag = '__phpurs_enum_0_T';//" "$D/C/Test.RBTree/index.php"
sed -i '' 's/public  \$value0, public  \$value1, public  \$value2, public  \$value3/public int $value0, public  $value1, public int $value2, public  $value3/' "$D/D/Test.RBTree/index.php"
sed -i '' "s/ public \$tag = '__phpurs_enum_0_T';//" "$D/D/Test.RBTree/index.php"
run() {
  "$PHP" -d xdebug.mode=off -d opcache.enable_cli=1 -d opcache.file_update_protection=0 \
    -d opcache.jit_buffer_size=128M -d opcache.jit=1255 "$ROOT/bench-rbtree.php" "$D/$1"
}
for v in A B C D; do run "$v"; done
for v in D C B A; do run "$v"; done
for v in A B C D; do run "$v"; done
for v in D C B A; do run "$v"; done
