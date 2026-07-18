.PHONY: up down build logs ps keys migrate fresh restart

up:            ## Build & start the whole stack
	docker compose up -d --build

down:          ## Stop and remove containers
	docker compose down

build:
	docker compose build

logs:          ## Tail logs for all services
	docker compose logs -f --tail=100

ps:
	docker compose ps

restart:
	docker compose restart

# Generate APP_KEY values for each service (paste into .env)
keys:
	@for s in AUTH ITEM CHAT MEDIA; do \
	  echo "$${s}_APP_KEY=base64:$$(head -c 32 /dev/urandom | base64)"; \
	done

migrate:       ## Run migrations in every Laravel service
	docker compose exec auth-service  php artisan migrate --force
	docker compose exec item-service  php artisan migrate --force
	docker compose exec chat-service  php artisan migrate --force
	docker compose exec media-service php artisan migrate --force

fresh:         ## Drop & re-run migrations with seeders
	docker compose exec item-service php artisan migrate:fresh --seed --force
