.PHONY: up down build logs ps keys migrate fresh restart \
        kube kube-up kube-down kube-status kube-logs kube-restart kube-migrate kube-fresh kube-load

# ─────────────────────────────────────────────
#  DOCKER COMPOSE  (desarrollo local)
# ─────────────────────────────────────────────

up:            ## Build & start the whole stack (Docker)
	docker compose up -d --build

down:          ## Stop and remove containers (Docker)
	docker compose down

build:
	docker compose build

logs:          ## Tail logs for all services (Docker)
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

migrate:       ## Run migrations in every Laravel service (Docker)
	docker compose exec auth-service  php artisan migrate --force
	docker compose exec item-service  php artisan migrate --force
	docker compose exec chat-service  php artisan migrate --force
	docker compose exec media-service php artisan migrate --force

fresh:         ## Drop & re-run migrations with seeders (Docker)
	docker compose exec item-service php artisan migrate:fresh --seed --force

# ─────────────────────────────────────────────
#  KUBERNETES  (producción / minikube)
# ─────────────────────────────────────────────

K8S_DIR := k8s
NS      := recloset

kube: kube-up  ## Alias rápido → make kube

kube-up:       ## Levanta TODO el stack en Kubernetes (aplica manifiestos en orden)
	@echo "🚀  Aplicando manifiestos en $(K8S_DIR)/ ..."
	kubectl apply -f $(K8S_DIR)/00-namespace.yaml
	kubectl apply -f $(K8S_DIR)/01-secrets.yaml
	kubectl apply -f $(K8S_DIR)/02-configmap.yaml
	kubectl apply -f $(K8S_DIR)/10-db-auth.yaml
	kubectl apply -f $(K8S_DIR)/10-db-chat.yaml
	kubectl apply -f $(K8S_DIR)/10-db-item.yaml
	kubectl apply -f $(K8S_DIR)/10-db-media.yaml
	kubectl apply -f $(K8S_DIR)/11-media-pvc.yaml
	kubectl apply -f $(K8S_DIR)/20-auth-service.yaml
	kubectl apply -f $(K8S_DIR)/20-chat-service.yaml
	kubectl apply -f $(K8S_DIR)/20-item-service.yaml
	kubectl apply -f $(K8S_DIR)/20-media-service.yaml
	kubectl apply -f $(K8S_DIR)/21-reverb.yaml
	kubectl apply -f $(K8S_DIR)/30-hpa.yaml
	kubectl apply -f $(K8S_DIR)/40-gateway.yaml
	kubectl apply -f $(K8S_DIR)/42-gateway-configmap.yaml
	kubectl apply -f $(K8S_DIR)/41-frontend.yaml
	kubectl apply -f $(K8S_DIR)/50-prometheus.yaml
	kubectl apply -f $(K8S_DIR)/51-grafana.yaml
	@echo "✅  Stack levantado. Ejecuta 'make kube-status' para ver el estado."

kube-down:     ## Elimina todos los recursos del namespace recloset
	@echo "🗑️   Eliminando todos los recursos en namespace $(NS) ..."
	kubectl delete -f $(K8S_DIR)/ --ignore-not-found=true
	@echo "✅  Stack eliminado."

kube-status:   ## Muestra pods, deployments y services en el namespace
	@echo "──── Pods ────────────────────────────────────"
	kubectl get pods -n $(NS) -o wide
	@echo "──── Deployments ─────────────────────────────"
	kubectl get deployments -n $(NS)
	@echo "──── Services ────────────────────────────────"
	kubectl get services -n $(NS)
	@echo "──── HPA ─────────────────────────────────────"
	kubectl get hpa -n $(NS)

kube-logs:     ## Ver logs de un pod  →  make kube-logs POD=auth-xxx-yyy
	kubectl logs -n $(NS) $(POD) --tail=100 -f

kube-restart:  ## Reinicia (rollout) todos los deployments
	@echo "🔄  Reiniciando deployments en namespace $(NS) ..."
	kubectl rollout restart deployment -n $(NS)
	@echo "✅  Rollout iniciado."

kube-migrate:  ## Ejecuta migraciones dentro de los pods de Kubernetes
	kubectl exec -n $(NS) deploy/auth-service  -- php artisan migrate --force
	kubectl exec -n $(NS) deploy/item-service  -- php artisan migrate --force
	kubectl exec -n $(NS) deploy/chat-service  -- php artisan migrate --force
	kubectl exec -n $(NS) deploy/media-service -- php artisan migrate --force

kube-fresh:    ## Drop & re-run migrations con seeders (Kubernetes)
	kubectl exec -n $(NS) deploy/item-service -- php artisan migrate:fresh --seed --force

kube-load:     ## Carga imágenes locales al nodo kind (resuelve ImagePullBackOff)
	@echo "📦  Cargando imágenes al nodo kind ..."
	kind load docker-image recloset/auth-service:latest
	kind load docker-image recloset/chat-service:latest
	kind load docker-image recloset/item-service:latest
	kind load docker-image recloset/media-service:latest
	kind load docker-image recloset/frontend:latest
	@echo "✅  Imágenes cargadas. Reiniciando pods ..."
	kubectl rollout restart deployment/auth-service deployment/chat-service deployment/item-service deployment/media-service deployment/reverb deployment/frontend -n $(NS)
