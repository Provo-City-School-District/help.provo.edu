#!/bin/bash

# Source environment variables
set -o allexport
. /root/.env
set +o allexport

# Run PHP script
/usr/local/bin/php /var/www/html/scripts/check_for_repeat_tickets.php