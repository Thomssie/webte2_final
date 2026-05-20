# Webová aplikácia TomTib Lab

## Predstavenie a postup práce

- Video s predstavením webovej aplikácie: [YouTube](https://www.youtube.com/watch?v=GKwxXNw8iCo)
- GitHub repozitár s kompletným postupom práce: [Thomssie/webte2_final](https://github.com/Thomssie/webte2_final)

## Rozdelenie práce

| Meno | Hlavné úlohy |
|---|---|
| Tibor Vnuk | - inverzné kyvadlo + frontend animácie a grafy<br>- backend výpočty pre simuláciu inverzného kyvadla<br>- logovanie používateľských/CAS aktivít<br>- export logov do CSV<br>- OpenAPI dokumentácia<br>- generovanie PDF dokumentácie<br>- CSRF zabezpečenie web požiadaviek<br>- úprava aplikácie pre nasadenie v podadresári /webte_final<br>- nasadenie aplikácie na školský server<br>- serverová dokumentácia nasadenia |
| Tomáš Kmiť | - založenie projektu Laravel + Inertia + React<br>- prepojenie aplikácie s GNU Octave<br>- CAS konzola so syntax highlightingom<br>- uchovávanie premenných medzi CAS výpočtami<br>- gulička na tyči + frontend animácie a grafy<br>- dvojjazyčné rozhranie SK/EN<br>- responzívny layout a navigácia aplikácie<br>- štatistiky používania animácií<br>- Docker konfigurácia<br>- vytvorenie prezentačného videa |

# Nasadenie na server node90

Projekt sme nasadili na adresu:

```text
https://node90.webte.fei.stuba.sk/webte_final/
```

Projekt sme uložili do adresára:

```text
/var/www/node90.webte.fei.stuba.sk/webte_final/
```

Laravel sme v Nginxe smerovali na priečinok `public`.

## 1. Serverové závislosti

GNU Octave aplikácia potrebuje na CAS výpočty a simulácie. 
Preto sme doinštalovali Octave aj balík `control`:

```bash
sudo apt update
sudo apt install octave octave-control
```

Octave sme v `.env` nastavili cez:

```env
CAS_OCTAVE_BINARY=octave
```

## 2. Súbory projektu

Do serverového adresára sme nahrali projekt vrátane hotového frontend buildu:

```text
public/build/
```

Po nahratí sme na serveri doplnili produkčné PHP závislosti:

```bash
cd /var/www/node90.webte.fei.stuba.sk/webte_final
composer install --no-dev --optimize-autoloader
```

## 3. Serverový `.env`

Na serveri sme nastavili produkčný `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://node90.webte.fei.stuba.sk/webte_final
ASSET_URL=https://node90.webte.fei.stuba.sk/webte_final

DB_CONNECTION=mariadb
DB_DATABASE=tomtib_lab_app
DB_USERNAME=xvnuk
DB_PASSWORD=<serverové-heslo>

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

CAS_API_KEY=<vygenerovaný-api-kľúč>
CAS_OCTAVE_BINARY=octave
```

API kľúč sme vygenerovali priamo na serveri:

```bash
openssl rand -hex 32
```

Vygenerovaný kľúč sme vložili do `.env` ako `CAS_API_KEY`. 

Po zmene `.env` sme obnovili konfiguračnú cache:

```bash
php8.4 artisan config:clear
php8.4 artisan config:cache
sudo systemctl reload php8.4-fpm
```

## 4. Databáza

Použili sme MariaDB databázu `tomtib_lab_app`.Spustili sme migrácie, aby sme vytvorili potrebné tabuľky:

```bash
cd /var/www/node90.webte.fei.stuba.sk/webte_final
php8.4 artisan migrate --force
```

## 5. Práva

Laravel musí vedieť zapisovať do `storage` a `bootstrap/cache`, preto sme nastavili vlastníka a práva:

```bash
cd /var/www/node90.webte.fei.stuba.sk/webte_final
sudo chown -R xvnuk:www-data /var/www/node90.webte.fei.stuba.sk/webte_final
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

## 6. Nginx

Do HTTPS `server { ... }` bloku sme pridali konfiguráciu pre `/webte_final/`. Bloky sme vložili pred všeobecný PHP blok:

```nginx
location = /webte_final {
    return 301 /webte_final/;
}

location ^~ /webte_final/ {
    alias /var/www/node90.webte.fei.stuba.sk/webte_final/public/;
    index index.php;
    try_files $uri $uri/ @webte_final;
}

location @webte_final {
    include fastcgi_params;
    fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;

    set $webte_final_request_uri $request_uri;

    if ($request_uri ~ ^/webte_final(?<wfpath>/.*)$) {
        set $webte_final_request_uri $wfpath;
    }

    fastcgi_param SCRIPT_FILENAME /var/www/node90.webte.fei.stuba.sk/webte_final/public/index.php;
    fastcgi_param DOCUMENT_ROOT /var/www/node90.webte.fei.stuba.sk/webte_final/public;
    fastcgi_param SCRIPT_NAME /webte_final/index.php;
    fastcgi_param PHP_SELF /webte_final/index.php;
    fastcgi_param PATH_INFO "";
    fastcgi_param REQUEST_URI $webte_final_request_uri;
    fastcgi_param QUERY_STRING $query_string;
    fastcgi_param REQUEST_METHOD $request_method;
    fastcgi_param HTTPS on;
    fastcgi_hide_header X-Powered-By;
}

location = /webte_final/index.php {
    include fastcgi_params;
    fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;

    fastcgi_param SCRIPT_FILENAME /var/www/node90.webte.fei.stuba.sk/webte_final/public/index.php;
    fastcgi_param DOCUMENT_ROOT /var/www/node90.webte.fei.stuba.sk/webte_final/public;
    fastcgi_param SCRIPT_NAME /webte_final/index.php;
    fastcgi_param PHP_SELF /webte_final/index.php;
    fastcgi_param PATH_INFO "";
    fastcgi_param REQUEST_URI /;
    fastcgi_param QUERY_STRING $query_string;
    fastcgi_param REQUEST_METHOD $request_method;
    fastcgi_param HTTPS on;
    fastcgi_hide_header X-Powered-By;
}

location ~ ^/webte_final/(?!index\.php).+\.php$ {
    deny all;
    return 403;
}
```

Po úprave sme otestovali a znovu načítali Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 7. Cache po nasadení

Po nahratí zmien sme vyčistili staré cache súbory a vytvorili produkčnú cache:

```bash
cd /var/www/node90.webte.fei.stuba.sk/webte_final
php8.4 artisan optimize:clear
php8.4 artisan config:cache
php8.4 artisan route:cache
php8.4 artisan view:cache
sudo systemctl reload php8.4-fpm
sudo systemctl reload nginx
```




# Docker spustenie

Aplikácia je kontajnerizovaná pomocou Dockeru

## Požiadavky

Na spustenie je potrebné mať nainštalované:

- Docker
- Docker Compose

## Spustenie projektu

V koreňovom priečinku projektu spustite:

```bash
cp .env.docker .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate --force
docker compose exec app npm ci
docker compose exec app npm run build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
```

Prvé spustenie môže trvať niekoľko minút, pretože sa inštalujú PHP a JavaScript závislosti a buildujú sa frontend assety.

Po úspešnom spustení bude aplikácia dostupná na adrese:
```bash
http://localhost:8080
```
