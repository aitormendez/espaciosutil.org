# Monitorizacion operativa externa

## Estado vigente

La disponibilidad publica de `espaciosutil.org` queda cubierta por una capa externa minima en HetrixTools.

Esta capa solo detecta disponibilidad externa y errores visibles desde Internet. No sustituye la monitorizacion interna del VPS Hetzner ni aporta metricas de CPU, memoria, disco, procesos, base de datos o logs de aplicacion.

## Proveedor

- Proveedor: HetrixTools.
- Cuenta: cuenta operativa existente de Espacio Sutil.
- Plan: gratuito, con checks de 1 minuto disponibles segun documentacion vigente del proveedor.
- Status page publica: no habilitada.
- Agentes instalados en servidores: ninguno.

Referencia de proveedor revisada al implementar esta capa:

- https://docs.hetrixtools.com/api-add-website-ping-service-smtp-uptime-monitor/
- https://hetrixtools.com/uptime-monitor/

## Monitor web principal

- Nombre operativo: `Espacio Sutil - Web principal`.
- URL monitorizada: `https://espaciosutil.org/`.
- Tipo: Website HTTPS.
- Metodo: `GET`.
- Frecuencia: `1 min`.
- Condicion: HTTP `200`.
- Keyword: `Espacio Sutil`.
- Ubicaciones activas: Amsterdam, London, Frankfurt.
- Alerta: HetrixTools `Default Contact`, email operativo asociado a la cuenta.
- Anti-ruido inicial: alerta tras `3` fallos; HetrixTools exige `2` ubicaciones fallidas sobre las 3 configuradas.
- Verificacion SSL: certificado y hostname activados; aviso de expiracion SSL a `15` dias.

## Criterio de actuacion ante alerta de espaciosutil.org

1. Confirmar en HetrixTools si la caida afecta a varias ubicaciones o parece un falso positivo aislado.
2. Validar desde una red externa:

```bash
curl -I https://espaciosutil.org/
curl -fsS https://espaciosutil.org/ | rg -i 'Espacio Sutil'
```

3. Si falla externamente, revisar el droplet DigitalOcean/Trellis de `espaciosutil.org`, empezando por conectividad, Nginx, PHP-FPM, certificados y estado de WordPress.
4. Si la web responde pero el keyword falla, revisar si hubo cambio legitimo de plantilla, home o contenido identificable.
5. No modificar Trellis ni DNS durante el diagnostico inicial sin una tarea tecnica explicita y plan de rollback.

## Relacion con otras capas

- HetrixTools cubre disponibilidad externa del sitio publico.
- La salud interna de WordPress, Matomo, base de datos, PHP, Nginx o colas no queda cubierta por esta capa.
- La monitorizacion interna del VPS Hetzner vive en `/Volumes/E/Nextcloud/docs/monitorizacion.md`.
