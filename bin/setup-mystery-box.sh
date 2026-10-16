#!/usr/bin/env bash
# Configurează WooCommerce pentru landing-ul Mystery Box (idempotent).
# Utilizare:  bash bin/setup-mystery-box.sh            (din rădăcina temei)
#             WP_CLI="wp --path=/cale/wp" bash bin/setup-mystery-box.sh
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP="${WP_CLI:-wp}"
THEME="$(basename "$(dirname "$DIR")")"

$WP plugin is-active woocommerce || $WP plugin install woocommerce --activate
$WP theme activate "$THEME"
$WP eval-file "$DIR/setup-mystery-box.php"
