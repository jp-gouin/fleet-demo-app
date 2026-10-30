# PHP 8.3.35 on Apache — security training demo for php2shell (CVE-2026-109618 / CVE-2026-109619).
FROM php:8.3.35-apache

# `ping` is needed by the diagnostics demo endpoint.
# Bullseye is EOL; its security pocket index can get ahead of the pool and 404.
# This is a throwaway lab image, so install ping from the stable main archive.
RUN { [ -f /etc/apt/sources.list ] && sed -i '/security/d' /etc/apt/sources.list; true; } \
    && apt-get update \
    && apt-get install -y --no-install-recommends iputils-ping \
    && rm -rf /var/lib/apt/lists/*

# App source.
COPY src/ /var/www/html/

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
    CMD php -r 'exit(file_exists("/var/www/html/index.php") ? 0 : 1);'
