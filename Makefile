APP_CONTAINER=app

up:
	docker compose up -d --build

down:
	docker compose down

restart:
	docker compose down
	docker compose up -d --build

logs:
	docker compose logs -f

ps:
	docker compose ps

shell:
	docker compose exec $(APP_CONTAINER) bash

composer:
	docker compose exec $(APP_CONTAINER) composer install

update:
	docker compose exec $(APP_CONTAINER) composer update

key:
	docker compose exec $(APP_CONTAINER) php artisan key:generate

migrate:
	docker compose exec $(APP_CONTAINER) php artisan migrate

fresh:
	docker compose exec $(APP_CONTAINER) php artisan migrate:fresh

seed:
	docker compose exec $(APP_CONTAINER) php artisan db:seed

optimize:
	docker compose exec $(APP_CONTAINER) php artisan optimize

clear:
	docker compose exec $(APP_CONTAINER) php artisan optimize:clear

cache:
	docker compose exec $(APP_CONTAINER) php artisan config:cache

test:
	docker compose exec $(APP_CONTAINER) php artisan test

tinker:
	docker compose exec $(APP_CONTAINER) php artisan tinker

queue:
	docker compose exec $(APP_CONTAINER) php artisan queue:work

setup:
	docker compose up -d --build
	docker compose exec $(APP_CONTAINER) composer install
	cp -n .env.example .env || true
	docker compose exec $(APP_CONTAINER) php artisan key:generate
	docker compose exec $(APP_CONTAINER) php artisan migrate

reset:
	docker compose down -v
	docker compose up -d --build
	docker compose exec $(APP_CONTAINER) php artisan migrate:fresh --seed

help:
	@echo ""
	@echo "Available commands:"
	@echo ""
	@echo " make up        - start containers"
	@echo " make down      - stop containers"
	@echo " make restart   - restart containers"
	@echo " make ps        - list containers"
	@echo " make logs      - show logs"
	@echo " make shell     - enter container"
	@echo ""
	@echo " make setup     - full setup (build, composer install, key generate, migrate)"
	@echo " make reset     - build, migrate fresh and seed"
	@echo ""
	@echo " make migrate   - run migrations"
	@echo " make fresh     - recreate database"
	@echo " make seed      - run seeders"
	@echo ""
	@echo " make test      - run tests"
	@echo " make tinker    - open tinker"
	@echo ""
