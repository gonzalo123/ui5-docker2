#!/bin/bash

help(){
    echo "Usage:"
    printf "\t build\t: Build containers.\n"
    printf "\t down\t: Stop services (e.g. DB).\n"
    printf "\t help\t: Show this help.\n"
    printf "\t up\t: Launch app.\n"
    exit 0
}

if [[ -z $1 ]];then
    help
    exit 0
fi

case $1 in
    build)
        cd src/frontend/ && npm install && npm audit fix && cd -
        cd src/io/ && npm install && npm audit fix && cd -
        docker-compose build --no-cache
        ;;
    up)
        docker-compose up -d
        docker run --rm -it -v ${PWD}/src/backend:/app composer:1.6 install
        cd src/frontend/ && npm install && npm audit fix && cd -
        cd src/io/ && npm install && npm audit fix && cd -
        ;;
    down)
        docker-compose down
        ;;
    * | -h | help)
        help
        ;;
esac
