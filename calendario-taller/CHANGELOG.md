# Changelog

## 1.9.14
- Se mejora la UX de pegado: el botón **Pegar** vuelve a ser de un clic en la semana visible actual.
- Se agrega botón adicional **Pegar en otra fecha…** para copiar una tarea de a una hacia otra semana cuando se necesite.
- Se mantiene validación de fecha (`YYYY-MM-DD`) solo en el flujo de “otra fecha”.

## 1.9.13
- Se mejora el pegado de tareas (copiado de a una): ahora permite indicar fecha destino manual (`YYYY-MM-DD`).
- Esto habilita copiar una tarea hacia otra semana sin depender solo de celdas visibles en la semana actual.
- Se valida formato de fecha en cliente antes de enviar la solicitud de pegado.

## 1.9.12
- Se agrega opción en importación para **permitir tareas duplicadas**.
- Si la opción está activa, las tareas con motivo `duplicada` dejan de omitirse y se importan.
- Se muestra en resultados cuando la importación se ejecutó con duplicadas permitidas.

## 1.9.11
- Durante la importación ahora se registran motivos de tareas omitidas (`duplicada`, `fecha_invalida`, `meta_invalida`).
- Se muestra en la UI de Importar/Exportar un resumen por motivo y una muestra de tareas omitidas con razón.
- El detalle de omisiones se transporta de forma temporal y segura usando transients para evitar URLs extensas.

## 1.9.10
- Se agrega opción de **sanitizar**: eliminar todas las tareas desde Importar/Exportar.
- Nueva acción protegida por nonce/permisos para purgar el CPT `acal_tarea` y reportar cantidad eliminada.
- La UI muestra confirmación y aviso de resultado de la sanitización.

## 1.9.9
- Se muestra la cantidad actual de tareas y técnicos en la pantalla de Importar/Exportar.
- Se agrega detalle visual del resultado de importación (tareas totales, agregadas, omitidas, errores; técnicos totales/agregados/omitidos).
- La exportación ahora incluye bloque `summary` con metadatos del proceso (versión plugin, conteos, usuario y fecha).
- Se mantiene importación incremental: en modo normal agrega solo faltantes y evita duplicados.

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
