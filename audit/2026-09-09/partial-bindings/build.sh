#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../../.."
TASK_HOST_BIN=$(mktemp -d /tmp/phpurs-copy-host.XXXXXX)
trap 'rm -rf "$TASK_HOST_BIN"' EXIT
ln -s "$PWD/../../altbak.pub-phpurs/run/bak/js/node_modules/purescript/purs.bin" "$TASK_HOST_BIN/purs"
ln -s "$PWD/../../altbak.pub-phpurs/run/bak/js/node_modules/.bin/spago" "$TASK_HOST_BIN/spago"
PATH="$TASK_HOST_BIN:$PATH" spago build
