# Calendario Taller

Plugin de WordPress para planificar tareas semanales de técnicos en una grilla tipo calendario (L–V por defecto), con administración en el backoffice, visualización en frontend por shortcode y exportación diaria a PNG.

## Contenido

- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Arquitectura general](#arquitectura-general)
- [Funcionalidades principales](#funcionalidades-principales)
  - [Calendario semanal](#calendario-semanal)
  - [Tareas](#tareas)
  - [Técnicos](#técnicos)
  - [Filtros y búsqueda](#filtros-y-búsqueda)
  - [Exportar día a PNG](#exportar-día-a-png)
  - [Orden de técnicos](#orden-de-técnicos)
  - [Copiar y pegar tareas](#copiar-y-pegar-tareas)
  - [Arrastrar tareas entre días](#arrastrar-tareas-entre-días)
  - [Modo pantalla completa](#modo-pantalla-completa)
- [Vista standalone](#vista-standalone)
- [Shortcode y parámetros](#shortcode-y-parámetros)
- [Datos y metadatos](#datos-y-metadatos)
- [Exportación e importación de datos](#exportación-e-importación-de-datos)
- [Permisos](#permisos)
- [Seguridad](#seguridad)
- [Assets](#assets)
- [Desarrollo y personalización](#desarrollo-y-personalización)

## Requisitos

- WordPress 5.0+ (probado con editor clásico).
- PHP 7.4+ recomendado.
- Usuario con permisos de lectura para ver el calendario en admin.

## Instalación

1. Copia la carpeta `calendario-taller` dentro de `wp-content/plugins/`.
2. Activa el plugin desde **Plugins** en el panel de WordPress.
3. Ingresa a **Calendario Taller** desde el menú de administración.

## Arquitectura general

- **Custom Post Type (CPT)**: `acal_tarea` (no público, sin UI nativa) para almacenar las tareas del calendario.
- **Opciones**:
  - `acal_tecnicos`: lista de técnicos con nombre, color y estado activo.
  - `acal_tecnicos_order`: orden global de técnicos (IDs) para la grilla.
- **Frontend**: `shortcode [calendario_taller]` para renderizar una versión de solo lectura.
- **Backend**: página de administración “Calendario Taller” y subpágina “Técnicos”.

## Funcionalidades principales

Las funcionalidades principales también están disponibles en el frontend (vista de solo lectura de **Calendario Taller**), salvo aquellas que requieren edición de datos.

### Calendario semanal

- Vista semanal iniciando en lunes, por defecto se muestran solo L–V.
- Navegación por semana con selector de fecha.
- Columna por técnico y fila por día.
- Leyenda con colores por técnico (opcional en frontend).

### Tareas

Las tareas se crean y editan desde un modal en la vista admin.
Campos principales:

- **Técnico** (ID interno).
- **Fecha** (YYYY-MM-DD).
- **Estado** (programado, en progreso, completado, cancelado). *En la UI de mejoras se fija como “programado”.*
- **Lugar/Sucursal**.
- **Cliente**.
- **Equipo/Modelo**.
- **Descripción**.
- **Turno** (AM / PM) con ordenamiento en la vista.

### Técnicos

La administración de técnicos permite:

- Crear y actualizar técnicos con **nombre**, **color HEX** y **activo**.
- Eliminar técnicos (no elimina tareas existentes).
- Reordenar el listado en la grilla (ver [Orden de técnicos](#orden-de-técnicos)).

### Filtros y búsqueda

En admin:

- Filtro por técnico.
- Filtro por lugar (internamente usa `f_sucursal`).
- Búsqueda por cliente, equipo y descripción.

> Nota: el filtro de estado se oculta en la UI mejorada, pero sigue existiendo en la lógica base.

### Exportar día a PNG

- Desde la cabecera de cada día, se puede exportar la programación diaria a PNG.
- El export muestra tareas por técnico separadas en grupos AM/PM.
- La generación se hace en el navegador usando `html2canvas`.

### Orden de técnicos

- Reordenación por flechas **↑/↓** en cada técnico.
- El orden se guarda vía AJAX en la opción `acal_tecnicos_order`.
- La grilla completa respeta el orden guardado.

### Copiar y pegar tareas

- Desde el menú de la tarea (⋮) o botón inline 📋 se puede copiar una tarea.
- Al copiar se activa un “modo copiar” y aparece el botón **Pegar** en las celdas.
- **Pegar** clona la tarea en otro técnico/fecha (se crea un nuevo post).

### Arrastrar tareas entre días

- Se puede arrastrar una tarea y soltarla en otra celda.
- Al soltar, se actualizan técnico y fecha vía AJAX.

### Modo pantalla completa

- `?acal_full=1` en un post/página con el shortcode activa una vista limpia a pantalla completa.

## Vista standalone

Para mostrar el calendario en frontend sin theme ni shortcode, usa el enlace directo:

```
https://tu-sitio.com/?acal_standalone=1
```

Desde la vista admin del calendario también existe un botón **“Abrir vista standalone”** que genera este link automáticamente.

También puedes abrir **Calendario Taller** desde **Ajustes** (menú del plugin) para acceder a una vista standalone sin theme en modo solo lectura.

## Shortcode y parámetros

Uso básico:

```
[calendario_taller]
```

Parámetros disponibles:

- `filters`: habilita filtros (0/1). *(En frontend no se muestran filtros visuales.)*
- `legend`: muestra la leyenda de técnicos (0/1).
- `actions`: ignorado en frontend; no se muestran acciones de edición.
- `lv`: `1` (L–V) o `0` (L–D).
- `sticky`: `both` | `header` | `none` (controla sticky header/columna).
- `refresh`: minutos para refresco automático (0 desactiva).

Ejemplo:

```
[calendario_taller legend="1" lv="0" sticky="header" refresh="5"]
```

## Datos y metadatos

### CPT `acal_tarea`

Metadatos usados por tarea:

- `_acal_tecnico_id` (string)
- `_acal_fecha` (YYYY-MM-DD)
- `_acal_estado` (programado | en_progreso | completado | cancelado)
- `_acal_sucursal` (texto)
- `_acal_cliente` (texto)
- `_acal_equipo` (texto)
- `_acal_descripcion` (texto)
- `_acal_turno` (am | pm)

El título del post se deriva de **Cliente** o un resumen de la descripción.

## Exportación e importación de datos

Para migrar el plugin con sus datos, asegúrate de exportar e importar lo siguiente:

- `wp_posts` y `wp_postmeta` correspondientes al post type **`acal_tarea`**.
- Las opciones **`acal_tecnicos`** y **`acal_tecnicos_order`** (tabla `wp_options`).

Con esto se conservan todas las tareas, técnicos y su orden en la grilla.

### Opción integrada en el plugin

El plugin incluye una pantalla **Importar/Exportar** en el menú de Calendario Taller:

- **Exportar**: descarga un archivo JSON con tareas, metadatos, técnicos y orden.
- **Importar**: permite cargar ese JSON y recrear las tareas. Incluye opción para reemplazar las tareas existentes.

## Permisos

- **Ver calendario admin**: usuarios con `read`.
- **Editar (crear/actualizar/eliminar tareas y técnicos)**: usuarios con `manage_options`, `shop_manager`, `manage_woocommerce` o `edit_shop_orders`.

## Seguridad

- Acciones protegidas con **nonce** (`acal_nonce`).
- Operaciones AJAX validan nonce y permisos.

## Assets

- `assets/admin.css`: estilos del admin.
- `assets/admin.js`: modal, menú kebab, copiar/pegar, modo copiar.
- `assets/front.css`: estilos del frontend.
- `assets/front.js`: refresco automático en frontend.
- `assets/rs-upgrade.css` y `assets/rs-upgrade.js`: mejoras UX/UI (filtros, turno AM/PM, reorden, drag & drop).
- `assets/html2canvas.min.js`: dependencia para exportar PNG.

## Desarrollo y personalización

- El CPT se registra como no público para evitar visibilidad directa.
- Para agregar nuevos campos, extender los metadatos y actualizar el render del modal y de la tarjeta.
- El ordenamiento de tareas dentro de una fecha prioriza AM, luego PM y finalmente tareas sin turno.

---

**Autor:** Rocket Solutions (https://www.rocketsolutions.cl)
