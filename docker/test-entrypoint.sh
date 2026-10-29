#!/bin/sh
set -eu

if [ "${1:-}" = "php" ] && [ "${2:-}" = "artisan" ]; then
    case "${3:-}" in
        db:seed|db:wipe|migrate|migrate:fresh|migrate:install|migrate:refresh|migrate:reset|migrate:rollback)
            php artisan temuka:verify-test-database
            ;;
    esac
fi

exec "$@"
