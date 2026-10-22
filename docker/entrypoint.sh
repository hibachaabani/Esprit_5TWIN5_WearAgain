#!/bin/sh
# Démarrage du conteneur : port, clé, base de données, caches, planificateur, puis Apache.
set -e
cd /var/www/html

# Les commandes artisan tournent sous l'utilisateur d'Apache, pour que les logs et caches lui appartiennent.
artisan() { runuser -u www-data -- php artisan "$@" --no-interaction; }

# 1. Port imposé par l'hébergeur (Render fournit $PORT).
PORT="${PORT:-8080}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# 2. Adresse publique et clé de chiffrement.
if [ -z "$APP_URL" ] && [ -n "$RENDER_EXTERNAL_URL" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
fi
if [ -z "$APP_KEY" ]; then
    APP_KEY="$(php artisan key:generate --show --no-ansi)"
    export APP_KEY
    echo "APP_KEY absente : clé générée pour ce démarrage (les sessions repartent à zéro à chaque redémarrage)."
fi

# 3. Base de données. En SQLite (démonstration), la base est créée et remplie au premier démarrage.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    export DB_CONNECTION=sqlite
    export DB_DATABASE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    # Mode WAL : une écriture disque par lot plutôt qu'une par insertion (démarrage bien plus rapide).
    export DB_JOURNAL_MODE="${DB_JOURNAL_MODE:-wal}" DB_SYNCHRONOUS="${DB_SYNCHRONOUS:-normal}" DB_BUSY_TIMEOUT="${DB_BUSY_TIMEOUT:-5000}"
    if [ ! -f "$DB_DATABASE" ]; then
        runuser -u www-data -- touch "$DB_DATABASE"
        NOUVELLE_BASE=1
    fi
fi
artisan migrate --force
if [ "${NOUVELLE_BASE:-0}" = "1" ] || [ "${SEED_ON_START:-false}" = "true" ]; then
    artisan db:seed --force
fi

# 4. Caches de configuration, routes et vues.
artisan optimize

# 5. Planificateur Laravel (ex. depots:expirer chaque nuit) : une passe par minute, en arrière-plan.
( while true; do artisan schedule:run >/dev/null 2>&1 || true; sleep 60; done ) &

exec "$@"
