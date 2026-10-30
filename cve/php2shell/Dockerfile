# php2shell (CVE-2026-109618 / CVE-2026-109619) training demo on Ubuntu 24.04.
#
# Ubuntu backports security fixes without bumping the upstream version, so BOTH
# the vulnerable and patched builds report PHP 8.3.6. The security state is set by
# which package revision we install, controlled by one ARG:
#
#   PATCH_STATE=vulnerable -> GA php8.3 from noble/main       (pre-php2shell)
#   PATCH_STATE=fixed      -> php8.3 8.3.6-0ubuntu0.24.04.13  (php2shell fixed)
#
# The fix PR flips PATCH_STATE to "fixed".
FROM ubuntu:24.04

ARG PATCH_STATE=vulnerable

ENV DEBIAN_FRONTEND=noninteractive \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_RUN_DIR=/var/run/apache2 \
    APACHE_PID_FILE=/var/run/apache2/apache2.pid \
    APACHE_LOG_DIR=/var/log/apache2

RUN set -eux; \
    if [ "$PATCH_STATE" = "vulnerable" ]; then \
        # Hold PHP at the GA release so the php2shell fix in -security/-updates
        # is withheld. Other packages still get security updates.
        printf 'Package: php8.3* libapache2-mod-php8.3\nPin: release a=noble\nPin-Priority: 1001\n' \
            > /etc/apt/preferences.d/php-ga; \
    fi; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        apache2 libapache2-mod-php8.3 php8.3-cli iputils-ping; \
    rm -f /var/www/html/index.html; \
    rm -rf /var/lib/apt/lists/*; \
    # Record the installed SAPI revision so the app can report its patch state.
    dpkg-query -W -f='${Version}' libapache2-mod-php8.3 > /etc/php2shell.pkgver; \
    a2enmod php8.3 >/dev/null 2>&1 || true

# App source.
COPY src/ /var/www/html/

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
    CMD php8.3 -r 'exit(file_exists("/var/www/html/index.php") ? 0 : 1);'

CMD ["sh", "-c", "mkdir -p \"$APACHE_RUN_DIR\" && exec apache2ctl -D FOREGROUND"]
