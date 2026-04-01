#!/usr/bin/env bash
# =============================================================================
# backup_bdd.sh — Sauvegarde journalière de la base de données MySQL
# =============================================================================
#
# PLANIFICATION CRON (exécution quotidienne à 2h du matin) :
#   1. Ouvrir la crontab de l'utilisateur courant :
#        crontab -e
#
#   2. Ajouter la ligne suivante (adapter le chemin absolu du script) :
#        0 2 * * * /chemin/absolu/vers/backup_bdd.sh >> /chemin/absolu/vers/backups/backup.log 2>&1
#
#   3. S'assurer que le script est exécutable :
#        chmod +x /chemin/absolu/vers/backup_bdd.sh
#
#   Vérifier les tâches planifiées en cours :
#        crontab -l
# =============================================================================

set -euo pipefail

# -----------------------------------------------------------------------------
# Paramètres de connexion à la base de données
# Ces valeurs peuvent être surchargées via des variables d'environnement :
#   export DB_PASSWORD="mon_mot_de_passe" && ./backup_bdd.sh
# -----------------------------------------------------------------------------
HOST="${HOST:-localhost}"
DB_NAME="${DB_NAME:-mediatekformation}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

# -----------------------------------------------------------------------------
# Configuration des sauvegardes
# -----------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="${SCRIPT_DIR}/backups"
DATE="$(date +%Y-%m-%d)"
BACKUP_FILE="${BACKUP_DIR}/backup_${DATE}.sql"
RETENTION_DAYS=7

# -----------------------------------------------------------------------------
# Fonctions utilitaires
# -----------------------------------------------------------------------------
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

error() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERREUR : $*" >&2
    exit 1
}

# -----------------------------------------------------------------------------
# Vérifications préalables
# -----------------------------------------------------------------------------
command -v mysqldump &>/dev/null || error "mysqldump n'est pas installé ou absent du PATH."

mkdir -p "${BACKUP_DIR}" || error "Impossible de créer le dossier de sauvegardes : ${BACKUP_DIR}"

if [[ -z "${DB_PASSWORD}" ]]; then
    log "ATTENTION : DB_PASSWORD est vide. Assurez-vous que c'est intentionnel."
fi

# -----------------------------------------------------------------------------
# Sauvegarde
# -----------------------------------------------------------------------------
log "Démarrage de la sauvegarde de '${DB_NAME}' vers ${BACKUP_FILE}..."

MYSQL_PWD="${DB_PASSWORD}" mysqldump \
    --host="${HOST}" \
    --user="${DB_USER}" \
    --single-transaction \
    --routines \
    --triggers \
    --add-drop-table \
    --create-options \
    --extended-insert \
    "${DB_NAME}" > "${BACKUP_FILE}" \
    || error "Échec de mysqldump pour la base '${DB_NAME}'."

# Compression du fichier de sauvegarde
gzip -f "${BACKUP_FILE}" \
    && BACKUP_FILE="${BACKUP_FILE}.gz" \
    && log "Fichier compressé : ${BACKUP_FILE}"

log "Sauvegarde terminée avec succès : $(du -sh "${BACKUP_FILE}" | cut -f1) — ${BACKUP_FILE}"

# -----------------------------------------------------------------------------
# Nettoyage : suppression des sauvegardes de plus de 7 jours
# -----------------------------------------------------------------------------
log "Nettoyage des sauvegardes de plus de ${RETENTION_DAYS} jours..."

find "${BACKUP_DIR}" -maxdepth 1 -name "backup_*.sql.gz" -mtime "+${RETENTION_DAYS}" -print -delete \
    | while read -r old_file; do
        log "Supprimé : ${old_file}"
    done

log "Nettoyage terminé."
