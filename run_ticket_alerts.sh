#!/bin/sh

# Source environment variables
set -o allexport
. /root/.env
set +o allexport

# Run PHP script
/usr/local/bin/php /var/www/html/includes/ticket_alerts.php