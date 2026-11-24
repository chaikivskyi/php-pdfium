FROM ubuntu:26.04
ARG TOOL
ARG ZTS=
RUN apt-get update \
 && apt-get install -y --no-install-recommends build-essential autoconf pkg-config curl ca-certificates file \
    libpng-dev libjpeg-dev zlib1g-dev libssl-dev $([ "$TOOL" = valgrind ] && echo valgrind) \
 && rm -rf /var/lib/apt/lists/*
COPY ci/php-debug.sh /tmp/php-debug.sh
RUN /tmp/php-debug.sh /opt/php $TOOL $ZTS && rm /tmp/php-debug.sh
ENV PATH=/opt/php/bin:$PATH
