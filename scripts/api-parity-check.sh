#!/usr/bin/env bash
# Cutover parity check: prove two API base URLs serve byte-identical responses.
#
# Usage: api-parity-check.sh <base1> <base2>
#   https bases: fetches each canary path from both bases and byte-compares.
#   file:// bases: compares the two documents directly (self-test mode).
#
# Exit 0 when every check is identical, 1 with per-check DIFF/FETCH-FAIL lines
# otherwise. Transient marketplace writes between the two fetches can cause a
# rare false DIFF; re-run once before treating a single canary DIFF as real.
set -euo pipefail

base1=${1:?usage: api-parity-check.sh <base1> <base2}
base2=${2:?usage: api-parity-check.sh <base1> <base2}
base1=${base1%/}
base2=${base2%/}

failures=0

check() { # $1 = label, $2 = url1, $3 = url2
    local body1 body2
    if ! body1=$(curl -fsS --max-time 20 "$2"); then
        echo "FETCH-FAIL $1 ($2)"
        failures=$((failures + 1))
        return
    fi
    if ! body2=$(curl -fsS --max-time 20 "$3"); then
        echo "FETCH-FAIL $1 ($3)"
        failures=$((failures + 1))
        return
    fi
    if [ "$body1" = "$body2" ]; then
        echo "PASS $1"
    else
        echo "DIFF $1"
        diff <(printf '%s\n' "$body1") <(printf '%s\n' "$body2") | head -20 || true
        failures=$((failures + 1))
    fi
}

if [[ $base1 == file://* && $base2 == file://* ]]; then
    check "document" "$base1" "$base2"
else
    for canary in /api/v1/market/prices/guide /api/v1/geo/regions /api/v1/market/demands /; do
        check "$canary" "${base1}${canary}" "${base2}${canary}"
    done
fi

if [ "$failures" -gt 0 ]; then
    echo "RESULT: $failures check(s) differed"
    exit 1
fi

echo "RESULT: all checks identical"
exit 0
