#!/bin/sh
# Login and test dashboard overview

TOKEN=$(curl -s -X POST \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@handayani.test","password":"password"}' \
  http://localhost:8080/api/login | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

echo "TOKEN: $TOKEN"

if [ -z "$TOKEN" ]; then
  echo "Login failed, trying to find users..."
  curl -s -X POST \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@handayani.test","password":"12345678"}' \
    http://localhost:8080/api/login
  exit 1
fi

echo ""
echo "=== Testing /api/dashboard/overview ==="
HTTP_CODE=$(curl -s -o /tmp/dashboard_response.json -w "%{http_code}" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  http://localhost:8080/api/dashboard/overview)

echo "HTTP Status: $HTTP_CODE"
head -c 500 /tmp/dashboard_response.json
echo ""
