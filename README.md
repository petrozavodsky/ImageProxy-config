## Установка

1. Так как выдумать длинное шестнадцатеричное число сложно запускаем в терминале генератор ключей 
`php generate.php` он заменит в файле docker-compose.yml `{KEY}` и `{SALT}` случайными HEX строками.
2. Выполняем `docker-compose up -d` что бы запустить контейнер.

Строки из docker-compose.yml `IMGPROXY_KEY` и `IMGPROXY_SALT` нужно запомнить, для использования в
 клиентском приложении.

В этом же файле docker-compose.yml остальные параметры из секции `environment` можно поменять по желанию.

## Реверс прокси и кэширование 

Так как образ imgproxy не содержит инструментов кэширования, рекомендуется использовать реверс прокси для кэширования
 результатов его работы.

Для этого нужно создать каталог для кэширования изображений, командой в терминале:

`mkdir /var/cache/nginx/image_proxy`

После в каталоге `/etc/nginx/conf.d` создать файл с произвольным именем и
 расширением `*.conf` такого содержания

```
proxy_cache_path /var/cache/nginx/image_proxy levels=1:2 keys_zone=image_proxy:900m inactive=360m max_size=3G;
proxy_cache_min_uses 1;
```

Настройки виртуального хоста nginx должны быть примерно такими:

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

вместо site.ru желаемый домен.
