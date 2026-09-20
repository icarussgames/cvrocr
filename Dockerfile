FROM php:8.2-cli

WORKDIR /app
COPY . /app

# Built-in server is enough for this Vue + PHP API app on Render.
ENV PORT=10000
EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]
