#-include .env-39
export

# setup for docker-compose-ci build directory
# delete "build" directory to update docker-compose-ci

ifeq (,$(wildcard ./build/Makefile))
    $(shell git submodule update --init --remote)
endif

EXTENSION=Mpdf

# docker images
MW_VERSION?=1.35
PHP_VERSION?=7.4
DB_TYPE?=sqlite
DB_IMAGE?=""

# extensions
# Enables installation of apt packages for gd extension
OS_PACKAGES?="zlib1g-dev libpng-dev"

# Enables installation of gd extension
PHP_EXTENSIONS?=gd

# composer
# Enables "composer update" inside of extension
COMPOSER_EXT?=true

# nodejs
# Enables node.js related tests and "npm install"
# NODE_JS?=true

# check for build dir and git submodule init if it does not exist
include build/Makefile