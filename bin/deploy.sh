#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy.sh — déploie le CMS sur root@wp.seo4.fun
#
# Usage :
#   bin/deploy.sh <domain>
#
# Exemple :
#   bin/deploy.sh lagalvanisation.com
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

DOMAIN="${1:-}"

[[ -z "$DOMAIN" ]] && { echo "Usage: bin/deploy.sh <domain>"; exit 1; }

SERVER="root@wp.seo4.fun"
REMOTE_PATH="/var/www/$DOMAIN"

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Déploiement : $DOMAIN → $SERVER:$REMOTE_PATH"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

ssh "$SERVER" "mkdir -p $REMOTE_PATH/templates $REMOTE_PATH/logs $REMOTE_PATH/media"

echo ""
echo "→ lib/..."
rsync -az --delete --exclude='.DS_Store' lib/ "$SERVER:$REMOTE_PATH/lib/"

echo "→ bin/..."
rsync -az --delete --exclude='.DS_Store' bin/ "$SERVER:$REMOTE_PATH/bin/"

echo "→ templates/..."
rsync -az --delete --exclude='.DS_Store' templates/ "$SERVER:$REMOTE_PATH/templates/"

echo "→ index.php, .htaccess, config.php..."
rsync -az index.php .htaccess config.php "$SERVER:$REMOTE_PATH/"

echo "→ content/..."
rsync -az --exclude='.DS_Store' --exclude='db.sqlite' \
    content/ "$SERVER:$REMOTE_PATH/content/"

echo ""
echo "✓ Déploiement terminé : $DOMAIN → $SERVER:$REMOTE_PATH"
