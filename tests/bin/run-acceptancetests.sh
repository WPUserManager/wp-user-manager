#!/usr/bin/env bash

REPO_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." && pwd )"
export PATH="${REPO_DIR}/vendor/bin:${PATH}"

cd "${REPO_DIR}"
composer install
cd "${REPO_DIR}/tests"

PREFIX="tests/"
SCRIPT="$@"

codecept build

run_acceptance_tests() {
    kill -9 $(pgrep chromedriver)
    chromedriver --url-base=/wd/hub &

    codecept run acceptance "$@";

    kill -9 $(pgrep chromedriver)
}

if [ -z "$SCRIPT" ]
then
    # Run all test suites
    run_acceptance_tests
else
    # Run specific suite test
    SCRIPT=${SCRIPT/#$PREFIX}
    SCRIPT_SUITE="${SCRIPT%%/*}"

    if [ $SCRIPT_SUITE = "acceptance" ]; then
        run_acceptance_tests $SCRIPT
    else
        codecept run $SCRIPT_SUITE $SCRIPT
    fi
fi
