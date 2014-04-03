#!/bin/bash
clear

# Clearing web scripts temp folder and/or creating it
rm -rf ./www/webtemp/
mkdir ./www/webtemp/ && echo "$(tput setaf 2)Scripts temp directory created successfully.$(tput sgr0)" || echo "$(tput setaf 1)Failed creating www/webtemp directory with appropriate access!$(tput sgr0)"
chmod 777 ./www/webtemp/ || echo "$(tput setaf 1)Failed setting write rights to www/webtemp!$(tput sgr0)"

# Clearing temp folder and/or creating it
rm -rf ./temp/
mkdir -p ./temp/sessions/ && echo "$(tput setaf 2)Temp directory created successfully.$(tput sgr0)" || echo "$(tput setaf 1)Failed creating temp directory with appropriate access!$(tput sgr0)"
chmod -R 777 ./temp/ || echo "$(tput setaf 1)Failed setting write rights to temp!$(tput sgr0)"

# Copying system files into temp directory
cp ./libs/.htaccess ./temp/.htaccess || echo "$(tput setaf 1)Failed creating temp/.htaccess!$(tput sgr0)"
cp ./libs/web.config ./temp/web.config || echo "$(tput setaf 1)Failed creating temp/web.config!$(tput sgr0)"

# Creates log directory, if does not exist
if [ ! -e ./log/ ]
then
    mkdir -p ./log/ && echo "$(tput setaf 2)Log directory created successfully.$(tput sgr0)" || echo "$(tput setaf 1)Failed creating log directory!$(tput sgr0)"
    chmod 777 ./log/ || echo "$(tput setaf 1)Failed setting write rights to log!$(tput sgr0)"
fi

# Copying system files into log directory
cp ./libs/.htaccess ./log/.htaccess || echo "$(tput setaf 1)Failed creating log/.htaccess!$(tput sgr0)"
cp ./libs/web.config ./log/web.config || echo "$(tput setaf 1)Failed creating log/web.config!$(tput sgr0)"

# Creates packagist directory, if does not exist
if [ ! -e ./packagist/ ]
then
    mkdir -p ./packagist/ && echo "$(tput setaf 2)Packagist directory created successfully.$(tput sgr0)" || echo "$(tput setaf 1)Failed creating packagist directory!$(tput sgr0)"
fi

# Copying system files into packagist directory
cp ./libs/.htaccess ./packagist/.htaccess || echo "$(tput setaf 1)Failed creating packagist/.htaccess!$(tput sgr0)"
cp ./libs/web.config ./packagist/web.config || echo "$(tput setaf 1)Failed creating packagist/web.config!$(tput sgr0)"

# Creating local config file from template, if does not exist
if [ -f ./app/config/config.local.neon ]
then
    echo "$(tput setaf 2)Local config file already exists.$(tput sgr0)"
else
    cp ./app/config/config.local.neon.template ./app/config/config.local.neon && echo "$(tput setaf 2)Local config has been successfully created.$(tput sgr0)" || echo "$(tput setaf 1)Failed creating app/config/config.local.neon!$(tput sgr0)"
fi

if [ ! -e ./databases/ ]
then
    mkdir -p ./databases/ && echo "$(tput setaf 2)Databases directory created successfully.$(tput sgr0)" || echo "$(tput setaf 1)Failed creating databases directory!$(tput sgr0)"
    chmod -R 777 ./databases/ || echo "$(tput setaf 1)Failed setting write rights to databases!$(tput sgr0)"
fi

if [ -f ./databases/logs.db ]
then
	echo "$(tput setaf 2)Logs SQLite database already exists.$(tput sgr0)"
else
	sqlite3 ./databases/logs.db "CREATE TABLE log_visit ( id integer NOT NULL PRIMARY KEY AUTOINCREMENT, ajax_flag numeric NOT NULL DEFAULT '0', url text NULL, http_method text NULL, http_get text NULL, http_post text NULL, remote_ip text NULL, server_ip text NULL, user_agent text NULL, referral text NULL, elapsed real NOT NULL DEFAULT '0', ins_dt datetime default current_timestamp, ins_user_id text NULL );"  && echo "$(tput setaf 2)Logs SQLite database created successfully.$(tput sgr0)" || echo "$(tput setaf 1)Failed creating logs SQLite database!$(tput sgr0)"
fi
chmod 777 ./databases/logs.db || echo "$(tput setaf 1)Failed setting write rights to ./databases/logs.db!$(tput sgr0)"

# Installing composer from composer.lock file
# TODO: Download composer.phar from remote and delete it after installing
composer install

# Thannk you!
echo "" ; echo "Thank you for using janmikes.cz Sandbox!" ; echo ""
