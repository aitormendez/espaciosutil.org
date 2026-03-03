#!/bin/bash

# Syncing Trellis & Bedrock-based WordPress environments with WP-CLI aliases
# Version 1.2.0
# Copyright (c) Ben Word

DEVDIR="web/app/uploads/"
DEVSITE="http://espaciosutil.test"

PRODDIR="web@espaciosutil.org:/srv/www/espaciosutil.org/shared/uploads/"
PRODSITE="https://espaciosutil.org"

STAGDIR="web@stage.espaciosutil.org:/srv/www/espaciosutil.org/shared/uploads/"
STAGSITE="https://stage.espaciosutil.org"

LOCAL=false
SKIP_DB=false
SKIP_ASSETS=false
POSITIONAL_ARGS=()

WP_BIN="$(command -v wp)"
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

if [ $# != 2 ]
then
  echo "Usage: $0 [[--skip-db] [--skip-assets] [--local]] [ENV_FROM] [ENV_TO]"
exit;
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
  *) echo "usage: $0 [[--skip-db] [--skip-assets] [--local]] production development | staging development | development staging | development production | staging production | production staging" && exit 1 ;;
esac

if [ "$SKIP_DB" = false ]
then
  DB_MESSAGE=" - ${bold}reset the $TO database${normal} ($TOSITE)"
fi

if [ "$SKIP_ASSETS" = false ]
then
  ASSETS_MESSAGE=" - sync ${bold}$DIR${normal} from $FROM ($FROMSITE)?"
fi

if [ "$SKIP_DB" = true ] && [ "$SKIP_ASSETS" = true ]
then
  echo "Nothing to synchronize."
  exit;
fi

echo
echo "Would you really like to "
echo $DB_MESSAGE
echo $ASSETS_MESSAGE
read -r -p " [y/N] " response

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  # Change to site directory
  cd ../ &&
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
  };
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
  };
  availto

  if [ "$SKIP_DB" = false ]
  then
  echo "Syncing database..."
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

  if [ "$SKIP_ASSETS" = false ]
  then
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
