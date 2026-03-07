# Penpot MCP (Penpot Cloud + VSCode Codex)

Fecha: 2026-03-04

Este documento describe lo que se ha configurado y el procedimiento para correr el servidor MCP de Penpot y conectarlo a un archivo abierto en `https://design.penpot.app` usando VSCode (extensión ChatGPT/Codex).

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

## 1) Arrancar servidores (MCP + plugin)

Directorio de trabajo: dentro del checkout de Penpot, en `penpot/mcp`.

Primera vez (instala dependencias):

```bash
cd ~/src/penpot/mcp
./scripts/setup
```

Arranque (deja esto corriendo en una terminal, por ejemplo iTerm):

```bash
cd ~/src/penpot/mcp
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

## 3) Configurar VSCode Codex (MCP server `penpot`)

Codex CLI `0.42.0` espera servidores MCP como procesos `stdio`. Para conectar con Penpot (que expone SSE/HTTP), se usa `mcp-remote` como proxy.

Archivo de configuración:
- `~/.codex/config.toml`

Servidor MCP configurado (bloque mínimo):

```toml
[mcp_servers.penpot]
command = "npx"
args = ["-y", "mcp-remote", "http://localhost:4401/sse", "--allow-http"]
```

Comentarios en TOML:
- Se comentan líneas con `#`.

Modo "Penpot rápido" (recomendado):
- Deja activo solo `penpot` y comenta otros `mcp_servers` que no uses para evitar fallos/timeouts al arrancar Codex.

Aplicación en VSCode:
- Reinicia VSCode (o recarga la ventana) para que la extensión re-lea `config.toml`.

## 4) Verificación rápida (sin Codex, directo al MCP)

Útil para diagnosticar si el MCP responde aunque el cliente (VSCode) falle.

Directorio de trabajo (cliente temporal):

```bash
mkdir -p /tmp/penpot-mcp-client
cd /tmp/penpot-mcp-client
npm init -y
npm i @modelcontextprotocol/sdk
```

Script de llamada (1 vez):

```bash
cat > /tmp/penpot-mcp-client/call-tool.mjs <<'EOF'
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
node /tmp/penpot-mcp-client/call-tool.mjs high_level_overview '{}'
```

Crear un layout simple (Board vacío con flex vertical) en `Page 1` o la primera página:

```bash
node /tmp/penpot-mcp-client/call-tool.mjs execute_code '{
  "code": "const pages = penpotUtils.getPages(); const t = penpotUtils.getPageByName(\"Page 1\") ?? penpotUtils.getPageById(pages[0].id); const page = penpotUtils.getPageById(t.id); const root = page.root; const board = penpot.createBoard(); board.name = \"Main Layout\"; board.x = 100; board.y = 100; board.resize(1200, 800); root.insertChild(root.children.length, board); const flex = board.addFlexLayout(); flex.dir = \"column\"; flex.rowGap = 0; flex.columnGap = 0; flex.topPadding = 0; flex.rightPadding = 0; flex.bottomPadding = 0; flex.leftPadding = 0; return { boardId: board.id, boardName: board.name };"
}'
```

## Persistencia de la conexión

- Reiniciar VSCode no debería cortar la conexión del plugin si:
  - `pnpm run bootstrap` sigue corriendo.
  - La UI del plugin sigue abierta.
- Si se corta, basta con volver a pulsar **Connect to MCP server** en el plugin de Penpot.

## Notas de rendimiento

Si una acción tarda demasiado, casi siempre es por inicialización/errores de clientes MCP no relacionados.

Checklist:
- Mantén `penpot` como único MCP server activo durante sesiones de diseño.
- Evita terminal integrada de VSCode para `pnpm run bootstrap` si sueles reiniciar VSCode (mejor iTerm).
- En Codex, evita que el arranque intente levantar MCP servers externos que no necesitas en ese momento.
