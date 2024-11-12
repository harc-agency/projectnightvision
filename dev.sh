#!/bin/bash
# ./dev.sh [up|down|workspace]

if [ "$1" == "up" ]; then
    cd laradock
    docker compose up -d nginx mysql phpmyadmin redis redis-webui
    cd ..
    echo ''
    echo 'Website: http://localhost'
    echo 'Phpmyadmin: http://localhost:8081 root:root'
    echo 'Redis: http://localhost:9987 laradock:laradock'
elif
[ "$1" == "down" ]; then
    cd laradock
    docker compose down
    cd ..
    echo 'Services have been stopped.'

# docker exec -it laradock-workspace-1 bash
elif
[ "$1" == "workspace" ]; then
    echo 'Connecting to workspace...'
    docker exec -it laradock-workspace-1 bash
else
    echo 'Usage: ./dev.sh [up|down|workspace]'
fi
