#!/usr/bin/env bash

set -euo pipefail
umask 077

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$ROOT_DIR/.env"

usage() {
    cat <<'EOF'
Uso: bin/backup.sh /pasta/de/destino

Gera um arquivo ZIP contendo o dump do banco MySQL e todos os arquivos de
writable/uploads (fotos, comprovantes e anexos).
EOF
}

config_value() {
    local key="$1"

    awk -v key="$key" '
        /^[[:space:]]*#/ { next }
        {
            line = $0
            sub(/^[[:space:]]*/, "", line)
            equals = index(line, "=")
            if (equals == 0) next

            name = substr(line, 1, equals - 1)
            value = substr(line, equals + 1)
            gsub(/^[[:space:]]+|[[:space:]]+$/, "", name)
            if (name != key) next

            gsub(/^[[:space:]]+|[[:space:]]+$/, "", value)
            if ((value ~ /^\047.*\047$/) || (value ~ /^".*"$/)) {
                value = substr(value, 2, length(value) - 2)
            }
            print value
            exit
        }
    ' "$ENV_FILE"
}

if [[ $# -eq 1 && ( "$1" == "--help" || "$1" == "-h" ) ]]; then
    usage
    exit 0
fi

if [[ $# -ne 1 ]]; then
    usage
    exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
    printf 'Arquivo .env não encontrado em %s\n' "$ROOT_DIR" >&2
    exit 1
fi

for command in mysqldump zip mktemp sha256sum; do
    if ! command -v "$command" >/dev/null 2>&1; then
        printf 'Dependência ausente: %s\n' "$command" >&2
        exit 1
    fi
done

DESTINATION_DIR="$1"
mkdir -p "$DESTINATION_DIR"
DESTINATION_DIR="$(cd "$DESTINATION_DIR" && pwd)"

DB_HOST="$(config_value 'database.default.hostname')"
DB_NAME="$(config_value 'database.default.database')"
DB_USER="$(config_value 'database.default.username')"
DB_PASSWORD="$(config_value 'database.default.password')"
DB_PORT="$(config_value 'database.default.port')"
DB_PORT="${DB_PORT:-3306}"

if [[ -z "$DB_HOST" || -z "$DB_NAME" || -z "$DB_USER" ]]; then
    printf 'As configurações do banco estão incompletas no arquivo .env.\n' >&2
    exit 1
fi

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_NAME="diecast-manager_${TIMESTAMP}"
ARCHIVE_PATH="$DESTINATION_DIR/${BACKUP_NAME}.zip"
CHECKSUM_PATH="${ARCHIVE_PATH}.sha256"
STAGING_DIR="$(mktemp -d "${TMPDIR:-/tmp}/${BACKUP_NAME}.XXXXXX")"

cleanup() {
    rm -rf "$STAGING_DIR"
}
trap cleanup EXIT

printf 'Exportando banco de dados...\n'
MYSQL_PWD="$DB_PASSWORD" mysqldump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USER" \
    --single-transaction \
    --triggers \
    --no-tablespaces \
    --default-character-set=utf8mb4 \
    "$DB_NAME" > "$STAGING_DIR/database.sql"

printf 'Copiando fotos, comprovantes e anexos...\n'
if [[ -d "$ROOT_DIR/writable/uploads" ]]; then
    cp -a "$ROOT_DIR/writable/uploads" "$STAGING_DIR/uploads"
else
    mkdir -p "$STAGING_DIR/uploads"
fi

cat > "$STAGING_DIR/MANIFEST.txt" <<EOF
Diecast Manager backup
Gerado em: $(date --iso-8601=seconds)
Banco: $DB_NAME
Servidor: $DB_HOST:$DB_PORT
Conteúdo: database.sql e uploads/
EOF

printf 'Compactando backup...\n'
(
    cd "$STAGING_DIR"
    zip -rq "$ARCHIVE_PATH" database.sql uploads MANIFEST.txt
)
sha256sum "$ARCHIVE_PATH" > "$CHECKSUM_PATH"

printf '\nBackup concluído:\n%s\nChecksum:\n%s\n' "$ARCHIVE_PATH" "$CHECKSUM_PATH"
