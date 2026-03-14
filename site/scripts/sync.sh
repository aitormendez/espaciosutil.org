#!/bin/bash

# Syncing Trellis & Bedrock-based WordPress environments with WP-CLI aliases
# Version 1.3.0
# Copyright (c) Ben Word

set -o pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SITE_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
REPO_ROOT="$(cd "${SITE_DIR}/.." && pwd)"
TRELLIS_DIR="${REPO_ROOT}/trellis"
VAULT_PASS_FILE="${TRELLIS_DIR}/.vault_pass"

DEVDIR="web/app/uploads/"
DEVSITE="http://espaciosutil.test"
DEVSSH="espaciosutil.test"

PRODDIR="web@espaciosutil.org:/srv/www/espaciosutil.org/shared/uploads/"
PRODSITE="https://espaciosutil.org"
PRODSSH="web@espaciosutil.org"

STAGDIR="web@stage.espaciosutil.org:/srv/www/espaciosutil.org/shared/uploads/"
STAGSITE="https://stage.espaciosutil.org"
STAGSSH="web@stage.espaciosutil.org"

LOCAL=false
SKIP_DB=false
SKIP_ASSETS=false
SKIP_MATOMO=false
POSITIONAL_ARGS=()
MATOMO_READY=false

WP_BIN="$(command -v wp)"
ANSIBLE_VAULT_BIN="$(command -v ansible-vault || true)"
if [[ -z "$ANSIBLE_VAULT_BIN" && -x "${TRELLIS_DIR}/.trellis/virtualenv/bin/ansible-vault" ]]; then
  ANSIBLE_VAULT_BIN="${TRELLIS_DIR}/.trellis/virtualenv/bin/ansible-vault"
fi

if [[ -z "$WP_BIN" ]]; then
  echo "❌  WP-CLI is required to run this script."
  exit 1
fi

WP_BIN_HEADER="$(head -n 1 "$WP_BIN" 2>/dev/null || true)"

# Keep PHP notices visible (stderr) without polluting SQL stdout streams.
if [[ "$WP_BIN_HEADER" == *"php"* ]]; then
  wp_cmd() {
    php -d "error_reporting=E_ALL" -d display_errors=stderr "$WP_BIN" "$@"
  }
else
  wp_cmd() {
    "$WP_BIN" "$@"
  }
fi

usage() {
  echo "Usage: $0 [[--skip-db] [--skip-assets] [--skip-matomo] [--local]] [ENV_FROM] [ENV_TO]"
}

trim_quotes() {
  local value="$1"
  value="${value%\"}"
  value="${value#\"}"
  value="${value%\'}"
  value="${value#\'}"
  echo "$value"
}

yaml_get_scalar() {
  local file="$1"
  local key="$2"

  [[ -f "$file" ]] || return 0

  awk -F': *' -v key="$key" '
    $1 == key {
      sub(/^[^:]*:[[:space:]]*/, "", $0)
      print $0
      exit
    }
  ' "$file" | head -n 1
}

yaml_get_first_list_item() {
  local file="$1"
  local key="$2"

  [[ -f "$file" ]] || return 0

  awk -v key="$key" '
    $0 ~ "^[[:space:]]*" key ":[[:space:]]*$" { in_list = 1; next }
    in_list && $0 ~ "^[[:space:]]*-[[:space:]]*" {
      sub(/^[[:space:]]*-[[:space:]]*/, "", $0)
      print $0
      exit
    }
    in_list && $0 !~ "^[[:space:]]+" { exit }
  ' "$file" | head -n 1
}

vault_file_content() {
  local env_name="$1"
  local vault_file="${TRELLIS_DIR}/group_vars/${env_name}/vault.yml"

  [[ -f "$vault_file" ]] || return 1

  if head -n 1 "$vault_file" | grep -q '^\$ANSIBLE_VAULT;'; then
    if [[ -z "$ANSIBLE_VAULT_BIN" ]]; then
      echo "❌  ansible-vault is required to read ${vault_file}" >&2
      return 1
    fi

    if [[ ! -f "$VAULT_PASS_FILE" ]]; then
      echo "❌  Vault password file not found at ${VAULT_PASS_FILE}" >&2
      return 1
    fi

    "$ANSIBLE_VAULT_BIN" view --vault-password-file "$VAULT_PASS_FILE" "$vault_file"
  else
    cat "$vault_file"
  fi
}

vault_get_scalar() {
  local env_name="$1"
  local key="$2"

  vault_file_content "$env_name" | awk -F': *' -v key="$key" '
    $1 == key {
      sub(/^[^:]*:[[:space:]]*/, "", $0)
      print $0
      exit
    }
  ' | head -n 1
}

matomo_config_file() {
  echo "${TRELLIS_DIR}/group_vars/$1/matomo.yml"
}

env_ssh_target() {
  case "$1" in
    development) echo "$DEVSSH" ;;
    staging) echo "$STAGSSH" ;;
    production) echo "$PRODSSH" ;;
  esac
}

matomo_enabled_for_env() {
  local env_name="$1"
  local config_file
  local enabled

  config_file="$(matomo_config_file "$env_name")"
  [[ -f "$config_file" ]] || return 1

  enabled="$(trim_quotes "$(yaml_get_scalar "$config_file" "matomo_enabled")")"
  [[ "$enabled" == "true" ]]
}

matomo_scalar_for_env() {
  local env_name="$1"
  local key="$2"
  local default_value="$3"
  local config_file
  local value

  config_file="$(matomo_config_file "$env_name")"
  value="$(trim_quotes "$(yaml_get_scalar "$config_file" "$key")")"

  if [[ -n "$value" ]]; then
    echo "$value"
  else
    echo "$default_value"
  fi
}

matomo_url_for_env() {
  local env_name="$1"
  local config_file
  local host
  local use_https
  local scheme

  config_file="$(matomo_config_file "$env_name")"
  host="$(trim_quotes "$(yaml_get_first_list_item "$config_file" "matomo_site_hosts")")"
  use_https="$(trim_quotes "$(yaml_get_scalar "$config_file" "matomo_use_https")")"
  scheme="https"

  if [[ "$use_https" == "false" ]]; then
    scheme="http"
  fi

  if [[ -n "$host" ]]; then
    echo "${scheme}://${host}"
  fi
}

matomo_db_password_for_env() {
  local env_name="$1"
  trim_quotes "$(vault_get_scalar "$env_name" "vault_matomo_db_password")"
}

run_env_command() {
  local env_name="$1"
  local command_string="$2"

  if [[ "$LOCAL" == true && "$env_name" == "development" ]]; then
    bash -lc "$command_string"
  else
    ssh -o ForwardAgent=yes "$(env_ssh_target "$env_name")" "$command_string"
  fi
}

mysql_host_args() {
  local db_host="$1"
  local result=""

  if [[ -n "$db_host" ]]; then
    printf -v result ' -h %q' "$db_host"
  fi

  echo "$result"
}

matomo_export_command() {
  local env_name="$1"
  local db_name="$2"
  local db_user="$3"
  local db_password="$4"
  local db_host="$5"
  local host_args
  local command_string

  host_args="$(mysql_host_args "$db_host")"
  printf -v command_string "MYSQL_PWD=%q mysqldump --default-character-set=utf8mb4 --single-transaction --quick --skip-lock-tables%s -u %q %q" \
    "$db_password" "$host_args" "$db_user" "$db_name"
  echo "$command_string"
}

matomo_import_command() {
  local env_name="$1"
  local db_name="$2"
  local db_user="$3"
  local db_password="$4"
  local db_host="$5"
  local host_args
  local command_string

  host_args="$(mysql_host_args "$db_host")"
  printf -v command_string "MYSQL_PWD=%q mysql --default-character-set=utf8mb4%s -u %q %q" \
    "$db_password" "$host_args" "$db_user" "$db_name"
  echo "$command_string"
}

matomo_mysql_exec() {
  local env_name="$1"
  local sql="$2"
  local db_name
  local db_user
  local db_password
  local db_host
  local host_args
  local command_string

  db_name="$(matomo_scalar_for_env "$env_name" "matomo_db_name" "matomo")"
  db_user="$(matomo_scalar_for_env "$env_name" "matomo_db_user" "matomo")"
  db_password="$(matomo_db_password_for_env "$env_name")"
  db_host="$(matomo_scalar_for_env "$env_name" "matomo_db_host" "localhost")"
  host_args="$(mysql_host_args "$db_host")"

  printf -v command_string "MYSQL_PWD=%q mysql --batch --skip-column-names --default-character-set=utf8mb4%s -u %q %q -e %q" \
    "$db_password" "$host_args" "$db_user" "$db_name" "$sql"
  run_env_command "$env_name" "$command_string"
}

matomo_drop_tables() {
  local env_name="$1"
  local tables
  local drop_sql="SET FOREIGN_KEY_CHECKS=0;"
  local table

  tables="$(matomo_mysql_exec "$env_name" "SHOW TABLES;")"
  [[ -n "$tables" ]] || return 0

  while IFS= read -r table; do
    [[ -n "$table" ]] || continue
    drop_sql+=" DROP TABLE IF EXISTS \`$table\`;"
  done <<< "$tables"
  drop_sql+=" SET FOREIGN_KEY_CHECKS=1;"

  matomo_mysql_exec "$env_name" "$drop_sql" >/dev/null
}

matomo_replace_urls() {
  local env_name="$1"
  local from_wp_site="$2"
  local to_wp_site="$3"
  local from_matomo_site="$4"
  local to_matomo_site="$5"
  local db_prefix
  local sql

  db_prefix="$(matomo_scalar_for_env "$env_name" "matomo_db_prefix" "matomo_")"
  sql="UPDATE \`${db_prefix}site\` SET main_url = REPLACE(main_url, '${from_wp_site}', '${to_wp_site}');"
  sql+=" UPDATE \`${db_prefix}site_url\` SET url = REPLACE(url, '${from_wp_site}', '${to_wp_site}');"
  sql+=" UPDATE \`${db_prefix}option\` SET option_value = REPLACE(option_value, '${from_wp_site}', '${to_wp_site}') WHERE option_value LIKE '%${from_wp_site}%';"

  if [[ -n "$from_matomo_site" && -n "$to_matomo_site" ]]; then
    sql+=" UPDATE \`${db_prefix}option\` SET option_value = REPLACE(option_value, '${from_matomo_site}', '${to_matomo_site}') WHERE option_value LIKE '%${from_matomo_site}%';"
  fi

  matomo_mysql_exec "$env_name" "$sql" >/dev/null
}

sync_matomo_database() {
  local from_env="$1"
  local to_env="$2"
  local from_db_name
  local from_db_user
  local from_db_password
  local from_db_host
  local to_db_name
  local to_db_user
  local to_db_password
  local to_db_host
  local from_wp_site="$3"
  local to_wp_site="$4"
  local from_matomo_site="$5"
  local to_matomo_site="$6"
  local export_command
  local import_command

  from_db_name="$(matomo_scalar_for_env "$from_env" "matomo_db_name" "matomo")"
  from_db_user="$(matomo_scalar_for_env "$from_env" "matomo_db_user" "matomo")"
  from_db_password="$(matomo_db_password_for_env "$from_env")"
  from_db_host="$(matomo_scalar_for_env "$from_env" "matomo_db_host" "localhost")"

  to_db_name="$(matomo_scalar_for_env "$to_env" "matomo_db_name" "matomo")"
  to_db_user="$(matomo_scalar_for_env "$to_env" "matomo_db_user" "matomo")"
  to_db_password="$(matomo_db_password_for_env "$to_env")"
  to_db_host="$(matomo_scalar_for_env "$to_env" "matomo_db_host" "localhost")"

  if [[ -z "$from_db_password" || -z "$to_db_password" ]]; then
    echo "❌  Missing Matomo database credentials for $from_env or $to_env"
    exit 1
  fi

  export_command="$(matomo_export_command "$from_env" "$from_db_name" "$from_db_user" "$from_db_password" "$from_db_host")"
  import_command="$(matomo_import_command "$to_env" "$to_db_name" "$to_db_user" "$to_db_password" "$to_db_host")"

  matomo_drop_tables "$to_env"

  if [[ "$LOCAL" == true && "$from_env" == "development" ]]; then
    bash -lc "$export_command" | run_env_command "$to_env" "$import_command"
  elif [[ "$LOCAL" == true && "$to_env" == "development" ]]; then
    run_env_command "$from_env" "$export_command" | bash -lc "$import_command"
  else
    run_env_command "$from_env" "$export_command" | run_env_command "$to_env" "$import_command"
  fi

  matomo_replace_urls "$to_env" "$from_wp_site" "$to_wp_site" "$from_matomo_site" "$to_matomo_site"
}

while [[ $# -gt 0 ]]; do
  case $1 in
    --skip-db)
      SKIP_DB=true
      shift
      ;;
    --skip-assets)
      SKIP_ASSETS=true
      shift
      ;;
    --skip-matomo)
      SKIP_MATOMO=true
      shift
      ;;
    --local)
      LOCAL=true
      shift
      ;;
    --*)
      echo "Unknown option $1"
      exit 1
      ;;
    *)
      POSITIONAL_ARGS+=("$1")
      shift
      ;;
  esac
done

set -- "${POSITIONAL_ARGS[@]}"

if [ $# != 2 ]; then
  usage
  exit 1
fi

FROM=$1
TO=$2

bold=$(tput bold)
normal=$(tput sgr0)

case "$1-$2" in
  production-development) DIR="down ⬇️ "          FROMSITE=$PRODSITE; FROMDIR=$PRODDIR; TOSITE=$DEVSITE;  TODIR=$DEVDIR; ;;
  staging-development)    DIR="down ⬇️ "          FROMSITE=$STAGSITE; FROMDIR=$STAGDIR; TOSITE=$DEVSITE;  TODIR=$DEVDIR; ;;
  development-production) DIR="up ⬆️ "            FROMSITE=$DEVSITE;  FROMDIR=$DEVDIR;  TOSITE=$PRODSITE; TODIR=$PRODDIR; ;;
  development-staging)    DIR="up ⬆️ "            FROMSITE=$DEVSITE;  FROMDIR=$DEVDIR;  TOSITE=$STAGSITE; TODIR=$STAGDIR; ;;
  production-staging)     DIR="horizontally ↔️ ";  FROMSITE=$PRODSITE; FROMDIR=$PRODDIR; TOSITE=$STAGSITE; TODIR=$STAGDIR; ;;
  staging-production)     DIR="horizontally ↔️ ";  FROMSITE=$STAGSITE; FROMDIR=$STAGDIR; TOSITE=$PRODSITE; TODIR=$PRODDIR; ;;
  *)
    usage
    exit 1
    ;;
esac

if [ "$SKIP_DB" = false ]; then
  DB_MESSAGE=" - ${bold}reset the $TO database${normal} ($TOSITE)"
fi

if [ "$SKIP_ASSETS" = false ]; then
  ASSETS_MESSAGE=" - sync ${bold}$DIR${normal} from $FROM ($FROMSITE)?"
fi

if [[ "$SKIP_MATOMO" = false ]] && matomo_enabled_for_env "$FROM" && matomo_enabled_for_env "$TO"; then
  MATOMO_READY=true
  FROM_MATOMO_SITE="$(matomo_url_for_env "$FROM")"
  TO_MATOMO_SITE="$(matomo_url_for_env "$TO")"
  MATOMO_MESSAGE=" - sync ${bold}Matomo${normal} from $FROM (${FROM_MATOMO_SITE}) to $TO (${TO_MATOMO_SITE})"
elif [[ "$SKIP_MATOMO" = false ]]; then
  MATOMO_MESSAGE=" - skip ${bold}Matomo${normal} (not configured for $FROM and/or $TO)"
fi

if [ "$SKIP_DB" = true ] && [ "$SKIP_ASSETS" = true ] && { [ "$SKIP_MATOMO" = true ] || [ "$MATOMO_READY" = false ]; }; then
  echo "Nothing to synchronize."
  exit
fi

echo
echo "Would you really like to "
echo "$DB_MESSAGE"
echo "$MATOMO_MESSAGE"
echo "$ASSETS_MESSAGE"
read -r -p " [y/N] " response

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  # Change to site directory
  cd "$SITE_DIR" &&
  echo

  # Make sure both environments are available before we continue
  availfrom() {
    local AVAILFROM

    if [[ "$LOCAL" = true && $FROM == "development" ]]; then
      AVAILFROM=$(wp_cmd option get home 2>&1)
    else
      AVAILFROM=$(wp_cmd "@$FROM" option get home 2>&1)
    fi
    if [[ $AVAILFROM == *"Error"* ]]; then
      echo "❌  Unable to connect to $FROM"
      exit 1
    else
      echo "✅  Able to connect to $FROM"
    fi
  }
  availfrom

  availto() {
    local AVAILTO
    if [[ "$LOCAL" = true && $TO == "development" ]]; then
      AVAILTO=$(wp_cmd option get home 2>&1)
    else
      AVAILTO=$(wp_cmd "@$TO" option get home 2>&1)
    fi

    if [[ $AVAILTO == *"Error"* ]]; then
      echo "❌  Unable to connect to $TO $AVAILTO"
      exit 1
    else
      echo "✅  Able to connect to $TO"
    fi
  }
  availto

  if [ "$SKIP_DB" = false ]; then
    echo "Syncing WordPress database..."
    # Export/import database, run search & replace
    if [[ "$LOCAL" = true && $TO == "development" ]]; then
      wp_cmd db export --default-character-set=utf8mb4 &&
      wp_cmd db reset --yes &&
      wp_cmd "@$FROM" db export --default-character-set=utf8mb4 - | wp_cmd db import - &&
      wp_cmd search-replace "$FROMSITE" "$TOSITE" --all-tables-with-prefix
    elif [[ "$LOCAL" = true && $FROM == "development" ]]; then
      wp_cmd "@$TO" db export --default-character-set=utf8mb4 &&
      wp_cmd "@$TO" db reset --yes &&
      wp_cmd db export --default-character-set=utf8mb4 - | wp_cmd "@$TO" db import - &&
      wp_cmd "@$TO" search-replace "$FROMSITE" "$TOSITE" --all-tables-with-prefix
    else
      wp_cmd "@$TO" db export --default-character-set=utf8mb4 &&
      wp_cmd "@$TO" db reset --yes &&
      wp_cmd "@$FROM" db export --default-character-set=utf8mb4 - | wp_cmd "@$TO" db import - &&
      wp_cmd "@$TO" search-replace "$FROMSITE" "$TOSITE" --all-tables-with-prefix
    fi
  fi

  if [[ "$SKIP_MATOMO" = false ]]; then
    if [[ "$MATOMO_READY" = true ]]; then
      echo "Syncing Matomo database..."
      sync_matomo_database "$FROM" "$TO" "$FROMSITE" "$TOSITE" "$FROM_MATOMO_SITE" "$TO_MATOMO_SITE"
    else
      echo "Skipping Matomo sync because it is not configured for $FROM and/or $TO."
    fi
  fi

  if [ "$SKIP_ASSETS" = false ]; then
    echo "Syncing assets..."
    # Sync uploads directory
    chmod -R 755 web/app/uploads/ &&
    if [[ $DIR == "horizontally"* ]]; then
      [[ $FROMDIR =~ ^(.*): ]] && FROMHOST=${BASH_REMATCH[1]}
      [[ $FROMDIR =~ ^(.*):(.*)$ ]] && FROMDIR=${BASH_REMATCH[2]}
      [[ $TODIR =~ ^(.*): ]] && TOHOST=${BASH_REMATCH[1]}
      [[ $TODIR =~ ^(.*):(.*)$ ]] && TODIR=${BASH_REMATCH[2]}

      ssh -o ForwardAgent=yes $FROMHOST "rsync -aze 'ssh -o StrictHostKeyChecking=no' --progress $FROMDIR $TOHOST:$TODIR"
    else
      rsync -az --progress "$FROMDIR" "$TODIR"
    fi
  fi

  # Slack notification when sync direction is up or horizontal
  # if [[ $DIR != "down"* ]]; then
  #   USER="$(git config user.name)"
  #   curl -X POST -H "Content-type: application/json" --data "{\"attachments\":[{\"fallback\": \"\",\"color\":\"#36a64f\",\"text\":\"🔄 Sync from ${FROMSITE} to ${TOSITE} by ${USER} complete \"}],\"channel\":\"#site\"}" https://hooks.slack.com/services/xx/xx/xx
  # fi
  echo -e "\n🔄  Sync from $FROM to $TO complete.\n\n    ${bold}$TOSITE${normal}\n"
fi
