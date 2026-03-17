# Instrucciones del repositorio

## Commits

- Usa mensajes de commit con formato Conventional Commits.
- La descripción del commit debe estar en español.
- El `scope` es obligatorio.
- La primera línea del commit debe seguir el formato `tipo(scope): descripcion en espanol`.
- Se permite añadir un cuerpo descriptivo debajo cuando ayude a explicar cambios complejos.
- No hagas commits si hay archivos `trellis/group_vars/*/vault.yml` desencriptados en el índice; re-cífralos antes con `trellis vault encrypt`.
- No uses mensajes libres como `Actualizar...`, `Refactoriza...` o similares sin prefijo convencional.
- Tipos recomendados: `feat`, `fix`, `docs`, `refactor`, `style`, `test`, `build`, `ci`, `chore`.
- Ejemplo: `chore(deps): actualiza WordPress y dependencias de Composer`
