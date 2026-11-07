FROM ubuntu:26.04
ARG ZTS=
RUN apt-get update \
 && apt-get install -y --no-install-recommends build-essential autoconf pkg-config curl ca-certificates file \
    libpng-dev libjpeg-dev \
 && rm -rf /var/lib/apt/lists/*
COPY ci/php-asan.sh /tmp/php-asan.sh
RUN /tmp/php-asan.sh /opt/php $ZTS && rm /tmp/php-asan.sh
ENV PATH=/opt/php/bin:$PATH
