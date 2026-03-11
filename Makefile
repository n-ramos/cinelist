.PHONY: help start stop restart logs ps \
        install migrate seed fresh test lint \
        build-prod run-prod

DC := docker compose -f docker/docker-compose.dev.yml --env-file .env

# ─────────────────────────────────────────────
#  Aide
# ─────────────────────────────────────────────
help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

# ─────────────────────────────────────────────
#  Docker (services: postgres + meilisearch)
# ─────────────────────────────────────────────
start: ## Démarre les services Docker (DB, Meilisearch)
	$(DC) up -d

stop: ## Arrête les services Docker
	$(DC) down

restart: ## Redémarre les services Docker
	$(DC) restart

logs: ## Affiche les logs Docker en continu
	$(DC) logs -f

ps: ## Liste les conteneurs actifs
	$(DC) ps

# ─────────────────────────────────────────────
#  Base de données
# ─────────────────────────────────────────────
migrate: ## Lance les migrations
	php artisan migrate --no-interaction

seed: ## Peuple la base avec les données de démo
	php artisan db:seed --no-interaction

fresh: ## Recrée la base et peuple avec les données de démo
	php artisan migrate:fresh --seed --no-interaction

# ─────────────────────────────────────────────
#  Qualité
# ─────────────────────────────────────────────
test: ## Lance tous les tests Pest
	php artisan test --compact

test-filter: ## Lance les tests filtrés (usage: make test-filter FILTER=KeyGenerator)
	php artisan test --compact --filter=$(FILTER)

lint: ## Formate le code PHP avec Pint
	vendor/bin/pint --format agent

# ─────────────────────────────────────────────
#  Production (Docker)
# ─────────────────────────────────────────────
build-prod: ## Build l'image Docker de production
	docker build -f docker/Dockerfile.prod -t trackr-api:latest .

run-prod: ## Lance le conteneur de production
	docker run --rm -p 8000:8000 --env-file .env trackr-api:latest
