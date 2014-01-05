Project sandbox
===============

Install
-------
For quick setting up, run following command in root of project via terminal:

    mkdir www/webtemp; chmod 777 www/webtemp; composer install; cp app/config/config.local.neon.template app/config/config.local.neon

In other words, create `www/webtemp` and allow it to be writable, install dependencies via *composer* and copy `app/config/config.local.neon.template` into `app/config/config.local.neon`

You will have to edit `app/config.local.neon` with your local settings.