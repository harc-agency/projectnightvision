#!/bin/bash
# dev.sh

if [ "$1" == "up" ]; then
    cd laradock
    docker compose up -d nginx mysql phpmyadmin redis redis-webui
    cd ..
    echo ''
    echo 'Website: http://localhost'
    echo 'Phpmyadmin: http://localhost:8081 root:root'
    echo 'Redis: http://localhost:9987 laradock:laradock'
elif [ "$1" == "down" ]; then
    cd laradock
    docker compose down
    cd ..
    echo 'Services have been stopped.'
else
    echo 'Usage: ./dev.sh [up|down]'
fi
