# Penpot MCP (Penpot Cloud + Codex)

Fecha: 2026-03-04

Este documento describe lo que se ha configurado y el procedimiento para correr el servidor MCP oficial de Penpot, conectarlo a un archivo abierto en `https://design.penpot.app` y operarlo desde Codex.

Estado actual resumido:

- El MCP oficial de Penpot arranca y responde correctamente.
- El plugin de Penpot conecta correctamente con el servidor MCP.
- El flujo integrado de Codex con herramientas MCP de Penpot es inestable y puede quedarse colgado incluso con llamadas mínimas.
- La forma fiable de operar hoy es mantener Penpot conectado y ejecutar las llamadas MCP con un cliente HTTP directo.

Issue abierto en Codex:
- [openai/codex#16086](https://github.com/openai/codex/issues/16086)

Issue posiblemente relacionado:
- [openai/codex#15365](https://github.com/openai/codex/issues/15365)

## Qué hay corriendo / instalado

- **Penpot MCP server** (HTTP/SSE + WebSocket para el plugin).
- **Penpot MCP plugin server** (servidor web que expone `manifest.json` para cargar el plugin en Penpot Cloud).
- **Codex (VSCode + CLI)** configurado con un servidor MCP llamado `penpot` que usa `mcp-remote` como proxy `stdio -> SSE`.
- Dependencias:
  - Node.js + `corepack` + `pnpm`.
  - `mcp-remote` se ejecuta con `npx` (no hace falta instalación global).

## Repos y directorios

Este repo (`espaciosutil.org`) solo guarda esta documentación. El código del MCP de Penpot se ejecuta desde un checkout del repo oficial de Penpot.

Ejemplo de directorio (elige el que prefieras):

```bash
mkdir -p ~/src
cd ~/src
git clone https://github.com/penpot/penpot.git --branch mcp-prod --depth 1
cd penpot/mcp
```

Notas:
- `mcp-prod` es la rama recomendada para versiones estables/release.
- Si quieres lo último de desarrollo, usa `--branch develop`.
- En esta máquina, el checkout real está en `/Users/aitor/penpot`, así que el directorio de trabajo es `/Users/aitor/penpot/mcp`.

## 1) Arrancar servidores (MCP + plugin)

Directorio de trabajo: dentro del checkout de Penpot, en `penpot/mcp`.

Primera vez (instala dependencias):

```bash
cd /Users/aitor/penpot/mcp
./scripts/setup
```

Arranque (deja esto corriendo en una terminal, por ejemplo iTerm):

```bash
cd /Users/aitor/penpot/mcp
pnpm run bootstrap
```

Puertos por defecto:
- Plugin server: `http://localhost:4400/manifest.json`
- MCP endpoints:
  - Streamable HTTP: `http://localhost:4401/mcp`
  - SSE (legacy): `http://localhost:4401/sse`
- WebSocket (plugin -> MCP): `ws://localhost:4402` (lo usa internamente el plugin)

## 2) Conectar el plugin en Penpot Cloud a tu archivo

1. Abre `https://design.penpot.app`.
2. Entra en un **archivo de diseño** (no en el dashboard).
3. Menú de Plugins: carga el plugin desde la URL de desarrollo:
   - `http://localhost:4400/manifest.json`
4. Abre la UI del plugin y pulsa **Connect to MCP server**.
5. Mantén la UI del plugin abierta mientras uses el MCP (si la cierras, se corta la conexión).

Nota (Chromium/PNA):
- En Chrome/Brave/Vivaldi puede aparecer un prompt para permitir acceso a red local (`localhost`). Hay que aprobarlo.
- Si el navegador bloquea, prueba con Firefox o revisa shields/protecciones.

## 3) Configurar Codex (MCP server `penpot`)

Codex espera servidores MCP como procesos `stdio`. Para conectar con Penpot (que expone SSE/HTTP), se usa `mcp-remote` como proxy.

Archivo de configuración:
- `~/.codex/config.toml`

Servidor MCP configurado (bloque mínimo):

```toml
[mcp_servers.penpot]
command = "npx"
args = ["-y", "mcp-remote", "http://localhost:4401/sse", "--allow-http"]
```

Nota:
- Para clientes MCP con transporte HTTP nativo se puede usar `http://localhost:4401/mcp`.
- En Codex, si usas `mcp-remote` como proxy `stdio`, mantén `http://localhost:4401/sse`, que es la ruta documentada oficialmente para ese flujo.

Comentarios en TOML:
- Se comentan líneas con `#`.

Modo "Penpot rápido" (recomendado):
- Deja activo solo `penpot` y comenta otros `mcp_servers` que no uses para evitar fallos/timeouts al arrancar Codex.

Aplicación:
- Reinicia Codex para que vuelva a leer `config.toml`.

## 4) Problema conocido del MCP integrado en Codex

La configuración anterior es correcta, pero no garantiza un flujo estable dentro de Codex.

Comportamiento observado en esta máquina:

- `pnpm run bootstrap` arranca correctamente.
- El plugin de Penpot muestra `Connected to MCP server`.
- El servidor MCP expone correctamente:
  - `http://localhost:4401/mcp`
  - `http://localhost:4401/sse`
- Sin embargo, las llamadas integradas de Codex a Penpot pueden quedarse colgadas indefinidamente, incluso con una llamada mínima tipo:

```js
return { ok: true };
```

Conclusión práctica:

- El problema no parece estar en Penpot MCP.
- El problema parece estar en la ruta integrada de Codex hacia ese servidor MCP.
- Mientras no se resuelva el issue, el flujo fiable es usar Penpot conectado + cliente directo HTTP.

## 5) Verificación rápida (sin Codex, directo al MCP)

Útil para diagnosticar si el MCP responde aunque el cliente integrado de Codex falle.

Directorio de trabajo (cliente temporal):

```bash
mkdir -p /private/tmp/penpot-mcp-client
cd /private/tmp/penpot-mcp-client
npm init -y
npm i @modelcontextprotocol/sdk
```

Script de llamada (1 vez):

```bash
cat > /private/tmp/penpot-mcp-client/call-tool.mjs <<'EOF'
import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StreamableHTTPClientTransport } from "@modelcontextprotocol/sdk/client/streamableHttp.js";

const toolName = process.argv[2];
const argsRaw = process.argv[3] ?? "{}";
const args = JSON.parse(argsRaw);

const client = new Client({ name: "penpot-direct-client", version: "1.0.0" }, { capabilities: {} });
const transport = new StreamableHTTPClientTransport(new URL("http://localhost:4401/mcp"));

await client.connect(transport);
const result = await client.callTool({ name: toolName, arguments: args });
console.log(JSON.stringify(result, null, 2));
await client.close();
EOF
```

Probar que responde:

```bash
node /private/tmp/penpot-mcp-client/call-tool.mjs high_level_overview '{}'
```

Crear un layout simple (Board vacío con flex vertical) en `Page 1` o la primera página:

```bash
node /private/tmp/penpot-mcp-client/call-tool.mjs execute_code '{
  "code": "const pages = penpotUtils.getPages(); const t = penpotUtils.getPageByName(\"Page 1\") ?? penpotUtils.getPageById(pages[0].id); const page = penpotUtils.getPageById(t.id); const root = page.root; const board = penpot.createBoard(); board.name = \"Main Layout\"; board.x = 100; board.y = 100; board.resize(1200, 800); root.insertChild(root.children.length, board); const flex = board.addFlexLayout(); flex.dir = \"column\"; flex.rowGap = 0; flex.columnGap = 0; flex.topPadding = 0; flex.rightPadding = 0; flex.bottomPadding = 0; flex.leftPadding = 0; return { boardId: board.id, boardName: board.name };"
}'
```

## 6) Forma correcta de operar actualmente

Hasta que se resuelva el problema de Codex, este es el flujo recomendado:

1. Arranca Penpot MCP:

```bash
cd /Users/aitor/penpot/mcp
pnpm run bootstrap
```

2. Abre el archivo de Penpot en `https://design.penpot.app`.
3. Carga el plugin desde `http://localhost:4400/manifest.json`.
4. Pulsa **Connect to MCP server**.
5. Mantén abierta la UI del plugin.
6. Ejecuta las llamadas reales con el cliente directo:

```bash
node /private/tmp/penpot-mcp-client/call-tool.mjs execute_code '{"code":"return { ok: true };"}'
```

Regla operativa:

- Usa el MCP integrado de Codex solo como intento inicial o para pruebas rápidas.
- Si una llamada se cuelga, cambia de inmediato al cliente directo.
- No cierres la UI del plugin mientras trabajas.
- Si Penpot recarga la página o pierde sesión, vuelve a conectar el plugin antes de lanzar más llamadas.

## 7) Persistencia de la conexión

- Reiniciar Codex no debería cortar la conexión del plugin si:
  - `pnpm run bootstrap` sigue corriendo.
  - La UI del plugin sigue abierta.
- Si se corta, basta con volver a pulsar **Connect to MCP server** en el plugin de Penpot.

## 8) Notas de rendimiento

Si una acción tarda demasiado, no asumas que Penpot está roto. En el estado actual, el problema más frecuente está en el cliente integrado.

Checklist:
- Mantén `penpot` como único MCP server activo durante sesiones de diseño.
- Evita terminal integrada de tu editor para `pnpm run bootstrap` si sueles reiniciar la app cliente (mejor iTerm).
- En Codex, evita que el arranque intente levantar MCP servers externos que no necesitas en ese momento.
- Si el MCP integrado se cuelga, valida con el cliente directo antes de tocar la configuración.
