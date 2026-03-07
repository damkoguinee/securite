#!/bin/bash

BASE_DIR="/home/u553146816/domains"
LOG_FILE="$BASE_DIR/global_cloture.log"

echo "=== Clôture annuelle lancée le $(date) ===" >> "$LOG_FILE"

# Parcours tous les domaines
for DOMAIN in "$BASE_DIR"/*/; do

    APP_DIR="${DOMAIN}app"

    # 1️⃣ Clôture si Symfony est directement dans app/
    if [ -f "$APP_DIR/bin/console" ]; then
        echo "→ Clôture dans : $APP_DIR" >> "$LOG_FILE"
        /usr/bin/php "$APP_DIR/bin/console" app:cloture-annuelle >> "$LOG_FILE" 2>&1
    fi

    # 2️⃣ Clôture si Symfony est dans un sous-dossier de app/
    for SUB in "$APP_DIR"/*/; do
        if [ -f "$SUB/bin/console" ]; then
            echo "→ Clôture dans sous-site : $SUB" >> "$LOG_FILE"
            /usr/bin/php "$SUB/bin/console" app:cloture-annuelle >> "$LOG_FILE" 2>&1
        fi
    done
done

echo "=== Fin de la clôture annuelle le $(date) ===" >> "$LOG_FILE"
