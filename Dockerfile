FROM php:8.3-cli

WORKDIR /app
COPY . /app

# Railway/most hosts provide PORT as an env var
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
