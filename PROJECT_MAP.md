# PROJECT MAP — Calendario Taller

> Documento de referencia rápida del estado actual del plugin.
> Objetivo: reducir exploración innecesaria en futuros prompts.

## 1) Estructura real del plugin

```text
calendario-taller/
├─ calendario-taller.php
├─ PROJECT_MAP.md
├─ README.md
├─ CHANGELOG.md
├─ QA.md
├─ templates/
│  └─ frontend-management.php
└─ assets/
   ├─ admin.css
   ├─ admin.js
   ├─ front.css
   ├─ front.js
   ├─ rs-upgrade.css
   ├─ rs-upgrade.js
   └─ html2canvas.min.js
```

## 2) Mapa funcional de vistas (archivo real)

- **Calendario semanal (vista principal):** `calendario-taller.php` (`render_calendar_page`).
- **Gestión de técnicos:** `calendario-taller.php` (`render_tecnicos_page`).
- **Importar / Exportar:** `calendario-taller.php` (`render_import_export_page`).
- **Frontend management (fuera del admin clásico):**
  - Ruta/controlador: `calendario-taller.php` (`maybe_front_management`, rewrite por slug).
  - Vista: `templates/frontend-management.php`.
- **Standalone read-only (`?acal_standalone=1`):** `calendario-taller.php` (`maybe_standalone`).


## 2.1) Estructura del header standalone (componente relevante)

- **Render HTML:** `calendario-taller.php` (`render_front_readonly_calendar`).
- **Contenedor raíz:** `header.acal-standalone-header`.
- **Estructura actual (3 filas visuales):**
  - `header.acal-standalone-header`
    - `div.acal-standalone-header__row--top`
      - `div.acal-standalone-header__spacer`
      - `div.acal-standalone-header__title`
      - `div.acal-standalone-header__logo > img` (condicional)
    - `div.acal-standalone-header__row--meta`
      - `div.acal-standalone-header__spacer`
      - `div.acal-week-context > div.acal-week-context__range`
      - `div.acal-week-context__updated`
    - `form.acal-topbar`
      - `div.acal-nav`
        - `a.acal-nav-prev`
        - `label.acal-nav-jump-label` (+ `input[type="date"]`)
        - `button.acal-nav-go`
        - `a.acal-nav-today` (condicional)
        - `a.acal-nav-next`
- **Layout dominante:** grid en filas 1/2 + flex lineal compacto en fila 3.
- **CSS principal:** `assets/rs-upgrade.css` (scope `body.acal-standalone`).

## 2.2) Layout de card de trabajador (columna técnico)

- **Render HTML:** `calendario-taller.php` (`render_calendar_page` y `render_front_readonly_calendar`).
- **Estructura:** `.acal-grid > .acal-cell.acal-tech-col.acal-tech-item > .acal-techname > .acal-techlabel`.
- **Posicionamiento vertical:** margen externo de `.acal-techname` + padding de `.acal-cell`.
- **Variables configurables:** `--acal-worker-card-offset`, `--acal-worker-card-width`, `--acal-worker-card-height` (inyectadas en wrappers frontend desde PHP).
- **Opciones en Ajustes:** `worker_card_offset`, `worker_card_width`, `worker_card_height`.

## 3) Núcleo del plugin

- **Archivo central:** `calendario-taller.php`.
- Define:
  - Menú y submenús de admin.
  - Rutas frontend/standalone.
  - Shortcode `[calendario_taller]`.
  - Acciones `admin_post` (crear/editar/eliminar tareas y técnicos, export/import, ajustes).
  - Endpoints AJAX (mover tarea, guardar orden de técnicos, pegar tarea).

## 4) Templates y vistas

- **`templates/frontend-management.php`**
  - Shell HTML liviano para gestión en frontend.
  - Invoca el render del calendario principal.

## 5) Scripts y estilos

- **Admin/UI de gestión:**
  - `assets/admin.css`
  - `assets/admin.js`
  - `assets/rs-upgrade.css`
  - `assets/rs-upgrade.js`
- **Frontend lectura:**
  - `assets/front.css`
  - `assets/front.js`
- **Soporte exportación PNG:**
  - `assets/html2canvas.min.js`

## 6) Módulos sensibles (tocar con cuidado)

- **Permisos y seguridad**
  - Control de lectura/edición y validaciones con nonce.
- **Importación / Exportación**
  - Flujo de respaldo/migración (JSON y CSV por técnico).
- **Rutas frontend / standalone**
  - Rewrite, query vars y manejo de salida de vistas sin theme.

## 7) Alcance funcional actual (resumen)

- Planificación semanal por técnico y día.
- Crear, editar, eliminar, mover, copiar y pegar tareas.
- Filtros y búsqueda en calendario.
- Exportar día a PNG.
- Importar/exportar datos del calendario.

## 8) Nota para futuras ampliaciones

Cuando se agreguen nuevas vistas o módulos:
1. Actualizar árbol de archivos.
2. Actualizar “Mapa funcional de vistas”.
3. Registrar módulos sensibles nuevos (si aplica).
