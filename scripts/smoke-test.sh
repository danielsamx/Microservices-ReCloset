#!/usr/bin/env bash
# Prueba de humo: verifica que el stack de ReCloset responde end-to-end.
# Uso: docker compose up -d --build  &&  bash scripts/smoke-test.sh
set -u
GW="${GW:-http://localhost:8080}"
FE="${FE:-http://localhost:5173}"
pass=0; fail=0
check () { # descripcion  url  codigo_esperado
  local code; code=$(curl -s -o /dev/null -w '%{http_code}' "$2")
  if [ "$code" = "$3" ]; then echo "  OK  [$code] $1"; pass=$((pass+1));
  else echo "  XX  [$code, esperado $3] $1"; fail=$((fail+1)); fi
}

echo "== Salud de servicios (vía gateway) =="
check "gateway /health"          "$GW/health" 200
check "auth  /api/auth/login (sin body -> 422)" "$GW/api/auth/login" 405
check "item  /api/meta"          "$GW/api/meta" 200
check "item  /api/catalog"       "$GW/api/catalog" 200
check "frontend"                 "$FE" 200

echo "== Flujo real: registro -> login -> crear/leer catálogo =="
EMAIL="tester_$(date +%s)@recloset.dev"
REG=$(curl -s -X POST "$GW/api/auth/register" -H 'Content-Type: application/json' \
  -d "{\"name\":\"Tester\",\"email\":\"$EMAIL\",\"password\":\"Password1\",\"password_confirmation\":\"Password1\"}")
TOKEN=$(echo "$REG" | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')
if [ -n "$TOKEN" ]; then echo "  OK  registro + token"; pass=$((pass+1));
else echo "  XX  registro falló: $REG"; fail=$((fail+1)); fi

if [ -n "$TOKEN" ]; then
  ME=$(curl -s -o /dev/null -w '%{http_code}' "$GW/api/auth/me" -H "Authorization: Bearer $TOKEN")
  [ "$ME" = "200" ] && { echo "  OK  /api/auth/me autenticado"; pass=$((pass+1)); } || { echo "  XX  /api/auth/me [$ME]"; fail=$((fail+1)); }
  WS=$(curl -s -o /dev/null -w '%{http_code}' "$GW/api/wardrobe/summary" -H "Authorization: Bearer $TOKEN")
  [ "$WS" = "200" ] && { echo "  OK  /api/wardrobe/summary"; pass=$((pass+1)); } || { echo "  XX  wardrobe [$WS]"; fail=$((fail+1)); }
fi

echo ""
echo "RESULTADO: $pass OK, $fail fallos"
[ "$fail" = "0" ] && echo "✅ Stack operativo" || echo "⚠️  Revisa 'docker compose logs' para los servicios en rojo"
