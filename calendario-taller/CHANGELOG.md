# Changelog

## 1.9.6
- Unificada la versión del plugin y de todos los assets en una constante única (`VERSION`) para mejorar cache busting y trazabilidad.
- Añadida normalización/validación centralizada de fecha (`normalize_date`) y aplicada en creación/edición/AJAX para robustecer entradas.
- Corregida detección de ID de tarea en `admin.js` para usar `task_id` en lugar de `post_id`, alineado con el HTML renderizado.
- Ajustada versión de `html2canvas` en exportación para usar la versión del plugin.
