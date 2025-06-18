# Makefile (ubicado en trellis/) para Trellis + GitHub Actions
REPO := aitormendez/espaciosutil.org

.PHONY: help decrypt encrypt deploy-staging deploy-production

help:
	@echo "Comandos disponibles:"
	@echo "  make decrypt             Desencripta los archivos vault"
	@echo "  make encrypt             Encripta los archivos vault"
	@echo "  make deploy-staging      Ejecuta despliegue en staging usando GitHub Actions"
	@echo "  make deploy-production   Ejecuta despliegue en producción usando GitHub Actions"

decrypt:
	trellis decrypt

encrypt:
	trellis encrypt

deploy-staging:
	gh workflow run deploy-staging.yml --repo $(REPO)
	@echo "🔄 Acción iniciada. Consulta el estado en:"
	@echo "   https://github.com/$(REPO)/actions/workflows/deploy-staging.yml"

deploy-production:
	gh workflow run deploy-production.yml --repo $(REPO)
	@echo "🔄 Acción iniciada. Consulta el estado en:"
	@echo "   https://github.com/$(REPO)/actions/workflows/deploy-production.yml"