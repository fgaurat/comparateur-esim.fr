#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# dev.sh — active un site localement via symlinks
#
# Usage :
#   bin/dev.sh <site>
#   bin/dev.sh univers-ponies.com
#
# Puis lancer le serveur :
#   php -S localhost:8000
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SITE="${1:-}"

usage() {
    echo "Usage: bin/dev.sh <site>"
    echo ""
    echo "  site : nom du dossier dans sites/ (ex: univers-ponies.com)"
    echo ""
    echo "Sites disponibles :"
    for d in sites/*/; do echo "  - $(basename "$d")"; done
    exit 1
}

[[ -z "$SITE" ]] && usage

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

SITE_DIR="sites/$SITE"
[[ ! -d "$SITE_DIR" ]] && { echo "Erreur : $SITE_DIR introuvable"; usage; }

echo "→ Activation du site : $SITE"

# ── config.php ────────────────────────────────────────────────────────────────
[[ -e config.php || -L config.php ]] && rm config.php
ln -s "$SITE_DIR/config.php" config.php
echo "  ✓ config.php → $SITE_DIR/config.php"

# ── content/ ──────────────────────────────────────────────────────────────────
[[ -e content || -L content ]] && rm -f content
ln -s "$SITE_DIR/content" content
echo "  ✓ content → $SITE_DIR/content"

# ── media/ ────────────────────────────────────────────────────────────────────
[[ -e media || -L media ]] && rm -f media
ln -s "$SITE_DIR/media" media
echo "  ✓ media → $SITE_DIR/media"

# ── templates du site ─────────────────────────────────────────────────────────
if [[ -d "$SITE_DIR/templates" ]]; then
    for theme_dir in "$SITE_DIR/templates"/*/; do
        [[ -d "$theme_dir" ]] || continue
        theme=$(basename "$theme_dir")
        target="templates/$theme"
        [[ -e "$target" || -L "$target" ]] && rm -f "$target"
        ln -s "../$SITE_DIR/templates/$theme" "$target"
        echo "  ✓ templates/$theme → $SITE_DIR/templates/$theme"
    done
fi

echo ""
echo "Site actif : $SITE"
echo "Lancer : php -S localhost:8000"
