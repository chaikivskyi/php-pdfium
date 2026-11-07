ARG BASE=php:8.4-cli-trixie
FROM $BASE
ARG PARALLEL_VERSION=
RUN if command -v apk >/dev/null; then \
      apk add --no-cache $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev curl tar; \
    else \
      apt-get update \
      && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev pkg-config valgrind \
      && rm -rf /var/lib/apt/lists/*; \
    fi \
 && if [ -n "$PARALLEL_VERSION" ]; then pecl install "parallel-$PARALLEL_VERSION"; fi
