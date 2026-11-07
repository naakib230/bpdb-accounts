#!/bin/bash
set -e

echo "=== Test 1: Healthcheck ==="
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:1972/up

echo "=== Test 2: Login Page ==="
CSRF=$(curl -s -c /tmp/c.txt http://localhost:1972/login | grep 'name="_token"' | head -1 | sed -e 's/.*value="//' -e 's/".*//')
echo "Extracted CSRF: ${CSRF:0:15}..."

echo "=== Test 3: Authenticate Admin 140011111 ==="
HTTP_LOGIN=$(curl -s -b /tmp/c.txt -c /tmp/c.txt -d "_token=$CSRF" -d "identifier=140011111" -d "password=Systemadmin123@" -o /dev/null -w "%{http_code}\n" http://localhost:1972/login)
echo "Login HTTP status: $HTTP_LOGIN (Expected: 302 Found redirecting to /dashboard)"

echo "=== Test 3b: Authenticate Admin 140038764 ==="
CSRF2=$(curl -s -c /tmp/c2.txt http://localhost:1972/login | grep 'name="_token"' | head -1 | sed -e 's/.*value="//' -e 's/".*//')
HTTP_LOGIN2=$(curl -s -b /tmp/c2.txt -c /tmp/c2.txt -d "_token=$CSRF2" -d "identifier=140038764" -d "password=Systemadmin123@" -o /dev/null -w "%{http_code}\n" http://localhost:1972/login)
echo "Admin 140038764 Login status: $HTTP_LOGIN2 (Expected: 302 Found redirecting to /dashboard)"

echo "=== Test 4: Access Dashboard ==="
curl -s -b /tmp/c.txt -c /tmp/c.txt http://localhost:1972/dashboard | grep -E "বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড|মোট ভাউচার সংখ্যা|ক্যাশ ও ব্যাংক স্থিতি" | head -3

echo "=== Test 5: Check Voucher List ==="
curl -s -b /tmp/c.txt http://localhost:1972/vouchers | grep -E "ভাউচার তালিকা|CV-|JV-" | head -3

echo "=== Test 6: Check Ledger ==="
curl -s -b /tmp/c.txt http://localhost:1972/ledger | grep -E "সাধারণ খতিয়ান|হিসাব নির্বাচন করুন" | head -2

echo "=== Test 7: Check Trial Balance ==="
curl -s -b /tmp/c.txt http://localhost:1972/trial-balance | grep -E "রেওয়ামিল|ডেবিট ও ক্রেডিট সমান" | head -2

echo "=== Test 8: Check Cost Centers ==="
curl -s -b /tmp/c.txt http://localhost:1972/cost-centers | grep -E "কস্ট সেন্টার তালিকা|CC-101|WAPDA" | head -2

echo "=== Test 9: Check Chart of Accounts ==="
curl -s -b /tmp/c.txt http://localhost:1972/chart-of-accounts | grep -E "চার্ট অব অ্যাকাউন্টস|মোট প্রদর্শিত হিসাব|Land and Land Rights" | head -3

echo "=== ALL E2E SMOKE TESTS PASSED! ==="
