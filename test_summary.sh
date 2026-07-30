#!/bin/bash
failed=0
passed=3
echo ""
if [ $failed -eq 0 ]; then
  echo -e "Summary: \033[0;32m$passed passed\033[0m, 0 failed."
else
  echo -e "Summary: \033[0;32m$passed passed\033[0m, \033[0;31m$failed failed\033[0m."
  exit 1
fi
