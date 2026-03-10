# QA / Smoke test — Calendario Taller

Esta guía define un **checklist reproducible** para validar regresiones en admin/frontend.

## 1) Preparación

1. Activar plugin y confirmar versión visible en código (`1.9.33`).
2. Habilitar logging:
   - `WP_DEBUG=true`
   - `WP_DEBUG_LOG=true`
   - `WP_DEBUG_DISPLAY=false`
3. Ingresar con dos perfiles:
   - **Editor operativo** (`shop_manager` o equivalente con edición).
   - **Solo lectura** (`read` sin permisos de edición).

## 2) Datos de prueba recomendados

- 2 técnicos activos con colores distintos.
- Semana con al menos 5 tareas mezclando:
  - `turno=am`
  - `turno=pm`
  - descripciones largas (>280 chars) para validar truncado visual.

## 3) Checklist funcional (frontend gestión)

### 3.1 Navegación y filtros

1. Abrir `/calendario-taller/`.
2. Navegar semana anterior/siguiente.
3. Usar acciones rápidas: **Hoy**, **Semana actual**, **Limpiar filtros**.
4. Aplicar filtros de técnico/lugar/búsqueda y confirmar persistencia en URL.

**Resultado esperado**
- La grilla cambia de semana correctamente.
- Los filtros afectan solo las filas/tareas objetivo.
- Limpiar filtros deja `date` y elimina `f_tecnico`, `f_sucursal`, `f_estado`, `s`.

### 3.2 CRUD de tareas + turno

1. Crear tarea desde una celda con teclado (`Enter`) y con mouse (`+Agregar`).
2. Guardar sin cambiar turno (default AM).
3. Editar la tarea y cambiar a PM.
4. Eliminar tarea desde menú de acciones.

**Resultado esperado**
- Crear guarda `_acal_turno=am` si no se envía explícito.
- Editar persiste `_acal_turno=pm` al cambiar.
- Eliminar retorna al mismo contexto (fecha/filtros).

### 3.3 Copiar/Pegar y mover

1. Copiar tarea desde menú kebab o botón inline 📋.
2. Pegar en otra celda de la semana visible.
3. Pegar en otra fecha usando acción dedicada.
4. Arrastrar tarea entre celdas (drag & drop).

**Resultado esperado**
- Una sola solicitud AJAX por acción.
- Tarea clonada conserva datos y turno.
- Mover actualiza técnico/fecha sin duplicar tarjeta.

### 3.4 Accesibilidad y teclado (desktop)

1. Hacer `Tab` por celdas enfocables.
2. `Enter` en celda abre modal de creación.
3. `Ctrl/Cmd+V` pega cuando existe clipboard activo.
4. Tecla `N` abre creación rápida (fuera de input/modal).
5. `Esc` cierra modal/sale de modo copiar cuando aplique.

**Resultado esperado**
- Foco visible en celdas.
- Región `aria-live` anuncia acciones de teclado.
- Sin bloqueos de foco ni pérdida de navegación.

## 4) Permisos y nonce

1. Repetir acciones con usuario solo lectura.
2. Intentar invocar endpoints AJAX sin nonce válido.

**Resultado esperado**
- Usuario read-only puede ver, pero no editar.
- Endpoints responden error `403` por nonce/permisos.

## 5) Observabilidad mínima

- Revisar `wp-content/debug.log` después de la sesión.
- Revisar consola del navegador (sin errores JS no controlados).
- Revisar pestaña Network (sin duplicación inesperada de requests AJAX).

## 6) Casos borde

- Fecha inválida al crear/editar.
- Técnicos inactivos/no existentes.
- Texto muy largo en descripción.
- Semana sin tareas.

## 7) Reversión rápida

Si una release falla:
1. Restaurar versión previa del plugin desde backup/zip.
2. Limpiar caché de assets (query version del plugin).
3. Repetir checklist de secciones 3.1 y 3.2 para confirmar recuperación.
