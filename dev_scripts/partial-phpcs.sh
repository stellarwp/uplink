#!/bin/bash
#
# This script is used to run phpcs only on the lines that have been changed in the current branch.
#
# Parameters
# $1 - base branch (default: origin/main)
#
# cSpell:ignore ACMR diffcs

BASE_BRANCH="${1:-origin/main}"

# constants
DIFF_FILE="diff.txt"
DIFF_PHPCS_FILE="diffcs.txt"
PHPCS_JSON_FILE="phpcs.json"

# generate diff of php files
git diff "$BASE_BRANCH" --name-only --diff-filter=ACMR -- '*.php' >$DIFF_PHPCS_FILE

# if empty, exit
if [ ! -s $DIFF_PHPCS_FILE ]; then
	echo "No php files to check."
	exit 0
fi

# generate diff of lines
git diff "$BASE_BRANCH" >$DIFF_FILE

# checking files
phpcs --standard=phpcs.xml --report=json --file-list=$DIFF_PHPCS_FILE >$PHPCS_JSON_FILE || true

# validate the phpcs result
if [ "$(head -c 1 $PHPCS_JSON_FILE)" != "{" ]; then
	echo "Invalid json file generated:"
	cat $PHPCS_JSON_FILE
	exit 1
fi

# filter the diff file — only report issues on changed lines
vendor/bin/diffFilter --phpcs $DIFF_FILE $PHPCS_JSON_FILE
