# =============================================================
# OracleSecLab - Web container (PHP 8.1 + Apache + OCI8)
# Also hosts the baseline "partner" manifest server on port 8081
# so Challenge 3 (Supply Chain) works exactly as on the VM setup.
# =============================================================
# Pinned to Debian 12 (bookworm): Debian 13 renamed libaio1 -> libaio1t64,
# which breaks Oracle Instant Client's dependency on libaio.so.1.
FROM php:8.1-apache-bookworm

# Links the GHCR package to this repository (also set by the CI workflow)
LABEL org.opencontainers.image.source="https://github.com/Khanh1916/oracle-sec-lab"

# Oracle Instant Client version (public OTN download, no login required)
ENV IC_BASE=2112000 \
    IC_VERSION=21.12.0.0.0dbru \
    ORACLE_HOME=/opt/oracle/instantclient \
    LD_LIBRARY_PATH=/opt/oracle/instantclient

RUN apt-get update && apt-get install -y --no-install-recommends \
        libaio1 unzip curl ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# --- Oracle Instant Client: basiclite + sdk + sqlplus ---------
RUN mkdir -p /opt/oracle && cd /opt/oracle \
    && curl -fsSLO https://download.oracle.com/otn_software/linux/instantclient/${IC_BASE}/instantclient-basiclite-linux.x64-${IC_VERSION}.zip \
    && curl -fsSLO https://download.oracle.com/otn_software/linux/instantclient/${IC_BASE}/instantclient-sdk-linux.x64-${IC_VERSION}.zip \
    && curl -fsSLO https://download.oracle.com/otn_software/linux/instantclient/${IC_BASE}/instantclient-sqlplus-linux.x64-${IC_VERSION}.zip \
    && unzip -q 'instantclient-*.zip' \
    && rm -f instantclient-*.zip \
    && mv instantclient_* instantclient \
    && echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf \
    && ldconfig

ENV PATH="/opt/oracle/instantclient:${PATH}"

# --- PHP OCI8 extension ---------------------------------------
# PECL oci8 versions track PHP versions: 3.2.1 is the release for PHP 8.1
# (3.3.x needs PHP 8.2+, 3.4.x needs PHP 8.4).
RUN echo "instantclient,/opt/oracle/instantclient" | pecl install oci8-3.2.1 \
    && docker-php-ext-enable oci8

# --- Apache modules + partner vhost on 8081 -------------------
RUN a2enmod rewrite headers \
    && echo "Listen 8081" >> /etc/apache2/ports.conf
COPY docker/apache-app.conf     /etc/apache2/conf-available/dbs401.conf
COPY docker/apache-partner.conf /etc/apache2/sites-available/partner.conf
RUN a2enconf dbs401 && a2ensite partner

# --- Baseline partner manifest (identical to setup.sh) --------
COPY docker/partner-api/ /var/www/partner-api/

# --- Application source ---------------------------------------
COPY . /var/www/html/dbs401-oracle-app/
RUN rm -rf /var/www/html/dbs401-oracle-app/docker \
    && chown -R www-data:www-data /var/www/html/dbs401-oracle-app /var/www/partner-api

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 8081
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
