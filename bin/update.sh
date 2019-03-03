#!/usr/bin/env bash

set -e

function run() {
    echo -e "\e[0;31m $@ \e[39m"
    "$@" || exit 1
}

DIR="/data/check-page"

cd ${DIR}

git reset --hard origin/master
git pull

run rm -rf var/cache/*
run php /usr/local/bin/composer.phar install
run php bin/console doctrine:migrations:migrate --no-interaction
run rm -rf var/cache/*
run chmod 777 var/cache var/logs -R

echo -e "\e[0;32m COMPLETE \e[39m"