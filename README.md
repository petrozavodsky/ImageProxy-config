## Установка на отдельный сервер

1. Клонируем ветку этого репозитория содержащую реверс прокси `git clone git@github.com:petrozavodsky/ImageProxy-config.git -b with-nginx` 
2. Запускаем в терминале генератор ключей `php generate.php`.
3. Меняем в конфиге `/conf/imageproxy.conf` домен `site.ru` собственный или несколько собственных доменов. 
3. В каталоге содержащем файл `docker-compose.yml` выполняем `docker-compose up -d`.

## Установка на сервер с уже существующим сайтом

1. Клонируем эту ветку`git clone git@github.com:petrozavodsky/ImageProxy-config.git -b nginx-reverse-proxy` 
2. Запускаем в терминале генератор ключей `php generate.php`.
3. Выполняем `docker-compose up -d` что бы запустить контейнер.
3. Создаем каталог для кэширования изображений `
mkdir /var/cache/nginx/image_proxy  && chmod 775 /var/cache/nginx/image_proxy && chown www-data:www-data /var/cache/nginx/image_proxy` 
и выставляем ей необходимые атрибуты.
4. В каталоге `/etc/nginx/conf.d` создать файл с произвольным именем и расширением `*.conf` такого содержания:

```
proxy_cache_path /var/cache/nginx/image_proxy levels=1:2 keys_zone=image_proxy:900m inactive=360m max_size=3G;
proxy_cache_min_uses 1;
```

5. В каталоге `/etc/nginx/sites-available` создаем конфиг виртуальный хост nginx с произвольным именем и 
расширением `*.conf` его конфиг должен выглядеть примерно так:

```
server {
    listen 80;

    server_name {images-0.site.ru} {images-1.site.ru} {images-2.site.ru};
    
    charset utf-8;

    error_log /var/log/nginx/imageproxy-error.log;

    location / {
        proxy_pass  http://127.0.0.1:1314; 
        proxy_next_upstream error timeout invalid_header;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_redirect off;
        proxy_cache image_proxy;
        proxy_cache_valid 1d;
  
        set $webp "";

        if ($http_accept ~* "webp") { set $webp T; }

        proxy_cache_key "$request_method|$http_if_modified_since|$http_if_none_match|$request_uri|$webp";
    }
}
```
домен site.ru нужно поменять на собственный. 

6. Создаем символическую ссылку `ln -s /etc/nginx/sites-available/imgproxy.conf /etc/nginx/sites-enabled/imgproxy.conf`.

7. Проверяем корректность конфигов `nginx -t` и перезапускаем nginx в случае отсутствия сообщений об ошибках `serice nginx restart`.

Строки из docker-compose.yml `IMGPROXY_KEY` и `IMGPROXY_SALT` нужно запомнить, для использования в
 клиентском приложении.

Так как выдумать длинное шестнадцатеричное число не имея привычки сложно я написал скрипт `generate.php` который сгенерирует
 случайные HEX строки.

### P.S.

Так же рекомендую настроить соединение через SSL используя Certbot и Let’s Encrypt, а также соединение через HTTP/2. Описание всех процесса выходит за рамки этой инструкции, поэтому я не буду говорить здесь об этом подробно по крайней мере сейчас.
