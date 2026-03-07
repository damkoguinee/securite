#!/bin/bash

# Racine où se trouvent tous les domaines
BASE_DIR="/home/u553146816/domains"
LOG_FILE="$BASE_DIR/global_backup.log"

# Créer le dossier logs global (si inexistant)
mkdir -p "$BASE_DIR"

echo "=== Sauvegarde globale lancée le $(date) ===" >> "$LOG_FILE"

# Parcours de tous les domaines
for DOMAIN_DIR in "$BASE_DIR"/*/; do
    APP_DIR="${DOMAIN_DIR}app"

    # Vérifier que app existe
    if [ ! -d "$APP_DIR" ]; then
        continue
    fi

    echo "→ Domaine trouvé : $DOMAIN_DIR" >> "$LOG_FILE"

    # 1️⃣ Projet Symfony directement dans app/
    if [ -f "$APP_DIR/bin/console" ]; then
        echo "   → Sauvegarde dans : $APP_DIR" >> "$LOG_FILE"
        /usr/bin/php "$APP_DIR/bin/console" app:backup-database-distant >> "$LOG_FILE" 2>&1
    fi

    # 2️⃣ Vérifier les sous-dossiers de app/
    for SITE_DIR in "$APP_DIR"/*/; do
        if [ -f "$SITE_DIR/bin/console" ]; then
            echo "   → Sauvegarde du sous-site : $SITE_DIR" >> "$LOG_FILE"
            /usr/bin/php "$SITE_DIR/bin/console" app:backup-database-distant >> "$LOG_FILE" 2>&1
        fi
    done

done

echo "=== Fin de la sauvegarde globale $(date) ===" >> "$LOG_FILE"
