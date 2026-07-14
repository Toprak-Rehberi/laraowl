# Coolify varyantı: Caddyfile bind-mount yerine image'a gömülür — Coolify'ın
# runtime compose dizininde repo dosyaları bulunmaz, bind mount boş klasöre dönüşür.
FROM caddy:2-alpine
COPY docker/Caddyfile.coolify /etc/caddy/Caddyfile
