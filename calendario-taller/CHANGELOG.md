# Changelog

## 1.9.8
- El importador en modo normal ahora compara contra datos existentes y agrega solo faltantes (evita duplicados exactos de tareas).
- Merge de técnicos por `id`: conserva los existentes y añade solo técnicos nuevos del archivo importado.
- Merge de `acal_tecnicos_order`: mantiene el orden actual y agrega IDs nuevos al final.
- Se sube versión del plugin a `1.9.8`.

## 1.9.7
- Se endurece `normalize_date()` para aceptar solo formatos explícitos válidos (`Y-m-d`, `d/m/Y`, `d-m-Y`) y validar calendario con `checkdate`.
- Se elimina el `strtotime` genérico para evitar interpretaciones ambiguas de fecha.
- `handle_export_day_png` y redirección de `handle_delete_task` ahora reutilizan `normalize_date()` para consistencia.

## 1.9.6
- Unificada la versión del plugin y de todos los assets en una constante única (`VERSION`) para mejorar cache busting y trazabilidad.
- Añadida normalización/validación centralizada de fecha (`normalize_date`) y aplicada en creación/edición/AJAX para robustecer entradas.
- Corregida detección de ID de tarea en `admin.js` para usar `task_id` en lugar de `post_id`, alineado con el HTML renderizado.
- Ajustada versión de `html2canvas` en exportación para usar la versión del plugin.
