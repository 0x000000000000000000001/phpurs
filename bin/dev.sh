#!/bin/bash
set -e

# Run spago build in phpurs directory
cd "$(dirname "$0")/.."
echo "==> Rebuilding phpurs compiler in $(pwd)..."
npx spago build

# Now go to b8x
cd ../b8x
echo "==> Generating PHP for b8x..."
node ../phpurs/bin/index.js "$@"
echo "==> Done!"
