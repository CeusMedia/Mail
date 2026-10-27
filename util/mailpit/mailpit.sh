#!/usr/bin/env bash
#  Starts or stops the Mailpit instances for integration tests.
#  Creates a self-signed certificate for "localhost" on first start.
#  Usage: util/mailpit/mailpit.sh up|down
set -euo pipefail
cd "$(dirname "$0")"

COMPOSE="docker compose"
docker compose version >/dev/null 2>&1 || COMPOSE="docker-compose"

case "${1:-up}" in
	up)
		if [ ! -f tls/cert.pem ]; then
			mkdir -p tls
			openssl req -x509 -newkey rsa:2048 -nodes -days 3650 \
				-keyout tls/key.pem -out tls/cert.pem \
				-subj "/CN=localhost" -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" 2>/dev/null
			chmod 644 tls/key.pem
		fi
		$COMPOSE up -d
		#  wait for the APIs, else tests would be skipped
		for port in 8525 8587 8465; do
			for try in $(seq 1 50); do
				curl -s -o /dev/null "http://localhost:$port/api/v1/info" && break
				[ "$try" -eq 50 ] && { echo "Mailpit API on port $port not reachable" >&2; exit 1; }
				sleep 0.2
			done
		done
		;;
	down)
		$COMPOSE down
		;;
	*)
		echo "Usage: $0 up|down" >&2
		exit 1
		;;
esac
