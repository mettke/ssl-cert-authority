FROM alpine
LABEL maintainer="Marc Mettke <marc@itmettke.de>"

ENV SYSTEM https://github.com/mettke/ssl-cert-authority.git
ADD entrypoint.sh /entrypoint.sh
ADD healthcheck.sh /healthcheck.sh
ADD cron /var/spool/cron/crontabs/root

RUN mkdir -p /var/log/cert/ /run/php/ /sca/ && \
    adduser --system --disabled-password cert-sync && \
    apk add openssh \ 
            openssl \
            php7 \
            php7-fpm \
            php7-json \
            php7-ldap \
            php7-mbstring \
            php7-mysqli \
            php7-pcntl \
            php7-posix \
            rsync \
            ssmtp \
            sudo && \
    sed -i -e '/listen =/ s/= .*/= 0.0.0.0:9000/' /etc/php7/php-fpm.d/www.conf && \
    sed -i -e '/;pid =/ s/.*/pid = \/var\/run\/php-fpm.pid/' /etc/php7/php-fpm.conf && \
    echo "" >> /etc/php7/php-fpm.conf && \
    chmod +x /entrypoint.sh /healthcheck.sh && \
    ln -sf /dev/stderr /var/log/php7/error.log
RUN apk add git && \
   git clone ${SYSTEM} /sca && \
   apk del git && \
   chown -R cert-sync:nogroup /sca/config

EXPOSE 9000
VOLUME /sca/config
VOLUME /public_html

ENTRYPOINT "/entrypoint.sh"
HEALTHCHECK CMD /healthcheck.sh
