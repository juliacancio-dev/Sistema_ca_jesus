#!/bin/bash
# Roda os testes E2E em cada banco. Uso: tests/e2e/rodar.sh [imagem...]
# Sem argumentos: MariaDB 10.4 (igual ao XAMPP) e MySQL 8.0.
cd "$(dirname "$0")" || exit 1
imagens=("$@")
[ ${#imagens[@]} -eq 0 ] && imagens=("mariadb:10.4" "mysql:8.0")

[ -d node_modules ] || npm install --no-audit --no-fund
npx playwright install chromium

falhou=0
for imagem in "${imagens[@]}"; do
    echo "=================== $imagem"
    export E2E_DB_IMAGE="$imagem"
    docker compose down -v --remove-orphans > /dev/null 2>&1
    if ! docker compose up -d --build --wait; then
        echo "Falha ao subir o ambiente com $imagem"
        docker compose logs --tail 40
        falhou=1
        continue
    fi
    npx playwright test --output "test-results/${imagem//[:.]/-}" || falhou=1
    docker compose exec -T web sh -c 'cat armazenamento/logs/php-erros.log 2>/dev/null | grep -E "PHP (Fatal|Warning|Deprecated|Notice|Parse)" | tail -20'
    docker compose down -v > /dev/null 2>&1
done
exit $falhou
