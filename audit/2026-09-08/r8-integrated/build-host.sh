#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../../.."
TASK_HOST_BIN=$(mktemp -d "${TMPDIR:-/tmp}/phpurs-host.XXXXXX")
trap 'rm -rf "$TASK_HOST_BIN"' EXIT
ln -s "$PWD/../../altbak.pub-phpurs/run/bak/js/node_modules/purescript/purs.bin" "$TASK_HOST_BIN/purs"
ln -s "$PWD/../../altbak.pub-phpurs/run/bak/js/node_modules/.bin/spago" "$TASK_HOST_BIN/spago"
export PATH="$TASK_HOST_BIN:$PWD/node_modules/.bin:$PATH"
spago build
spago bundle --module Main --platform node --outfile bin/phpurs.js --bundle-type app
