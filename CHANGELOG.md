## [1.10.44] - 2026-04-30
- Calendario Taller: nuevo campo `Informe entregado` en modal de crear/editar tarea, persistido en meta `_acal_informe_entregado` (`1`/`0`).
- Se incluye `informe_entregado` en `get_tasks_for_week()` y en payload de edición (`data-task`) para cargar estado al editar.
- Tarjetas: muestra chip `Informe entregado` en admin y frontend standalone cuando aplica.
- Copiar/pegar tarea (`acal_paste_task`): ahora copia también `_acal_informe_entregado`.
- Exportación diaria PNG: muestra indicador corto `INF ✓` cuando el informe está entregado.

## 1.10.43
- `?acal_standalone=1`: el header frontend se realinea para replicar visualmente la vista previa de Ajustes en desktop, unificando proporciones, gaps, tipografías, altura de controles, logo y pill de actualización.
- Se reduce el ancho visual útil del contenedor standalone para acercarlo al ancho percibido de la preview y evitar el header excesivamente estirado.
- El botón `Hoy` del header standalone ahora solo se muestra cuando la semana visible no corresponde a la semana actual.
- Se refuerza la consistencia CSS entre preview y frontend para que ambos compartan la misma base visual y no vuelvan a divergir fácilmente.

## 1.10.42
- El header de `?acal_standalone=1` se reestructura a una sola línea real en desktop: título, navegación semanal, selector de fecha, acción `Ir`, botón `Hoy`, logo y pill de actualización.
- Se agregan nuevos ajustes globales para el header standalone: visibilidad de logo/pill/botón `Hoy`, textos de navegación, radio, borde, sombra, padding y separación inferior del contenedor.
- La pantalla de Ajustes ahora incluye una vista previa dinámica en vivo del header standalone con fecha fija de ejemplo, reflejando textos, visibilidad y estilo antes de guardar.
- La pill `Actualizado` usa la última modificación real de las tareas visibles de la semana cargada; si no hay cambios visibles, muestra fallback limpio.
- El logo del header standalone se compacta para sostener el layout horizontal en desktop sin romper la línea.

# Changelog

## 1.10.40
- Ajustes: nuevos campos configurables para tamaño de la card de trabajador (`Ancho card trabajador` y `Alto card trabajador`).
- Se inyectan variables CSS `--acal-worker-card-width` y `--acal-worker-card-height` y se aplican sobre `.acal-techname` en frontend management y standalone.
- Se mantiene el desplazamiento vertical configurable existente y se preserva el centrado interno del contenido.

## 1.10.39
- Fix: `Desplazamiento vertical card trabajador` ahora impacta de forma consistente aplicando `transform: translateY(var(--acal-worker-card-offset))` sobre la card de técnico en frontend management y standalone.
- Se mantiene el ajuste por variable configurable sin tocar padding interno de texto ni lógica funcional.

## 1.10.38
- Ajustes: nuevo campo configurable `Desplazamiento vertical card trabajador (px)` para controlar la posición vertical de la card del técnico en frontend management y standalone.
- Implementación por causa raíz usando variable CSS `--acal-worker-card-offset` aplicada en wrappers frontend, sin tocar padding interno del texto de la card.

## 1.10.37
- Standalone TV/monitor: header más compacto reduciendo altura de fila 1 (título y logo) y densidad general de controles.
- Fila 3 reordenada en una sola línea: `Semana anterior` → `Ver semana` + fecha → `Ir` → `Hoy` → `Próxima semana` (anclada a la derecha).
- Actualización de `PROJECT_MAP.md` con la nueva estructura de subcomponentes del header standalone.

## 1.10.36
- Standalone: header reorganizado en 3 filas dentro de una sola franja compacta (título centrado, semana al centro + actualización a la derecha, navegación en izquierda/centro/derecha).
- Ajuste mínimo de markup y CSS para mantener logo a la derecha, navegación/selector funcionales y responsive estable.
- Documentación: `PROJECT_MAP.md` actualiza subcomponentes del header standalone según la nueva estructura.

## 1.10.35
- Documentación: se actualiza `PROJECT_MAP.md` con el mapa del header standalone (render, contenedores, jerarquía y layout flex) para facilitar diagnósticos UI puntuales.

## 1.10.34
- Standalone: ajuste visual mínimo en la card de trabajador para bajarla levemente y alinear mejor su arranque con las tarjetas de tareas.
- Cambio aplicado en el contenedor de la card (margen externo), sin modificar el padding interno del texto ni reintroducir espacio vertical grande.

## 1.10.33
- Standalone UX/UI: cabecera unificada en una sola franja compacta, integrando título, contexto de semana/estado, navegación semanal, selector de fecha y acción "Hoy" sin bloques apilados.
- Se reduce altura visual del header (logo, paddings y controles) para una lectura más limpia y equilibrada, manteniendo funcionalidad de navegación y selector.
- Ajuste mínimo de markup + CSS scopeado a standalone, sin cambios en lógica de datos ni endpoints.

## 1.10.32
- Standalone: refinamiento UI aplicado con enfoque más sobrio y operativo (`week context`, navegación por grupos, estado visual de `Hoy`, intersección sticky más clara y tarjetas con meta en chips).
- Ajustes: nuevo control numérico para definir desde admin la separación entre el header del día y la primera tarjeta en la vista standalone.
- Implementación sin cambios de lógica de datos, permisos ni navegación semanal; el espaciado ahora usa variable CSS configurable en lugar de hotfix fijo.

## 1.10.31
- Título principal del calendario con mayor jerarquía visual y centrado en frontend (`h1` más grande y bold).
- Standalone header: título centrado respecto al ancho total mediante layout en 3 columnas, manteniendo logo alineado a la derecha sin romper el header.

## 1.10.30
- Header de días: se refuerza tipografía del texto de día (`LUN`) para equilibrarlo visualmente con la fecha (`09/03`) en standalone.
- Ajuste CSS mínimo y limpio, sin cambios en lógica de fecha.

## 1.10.29
- Ajuste visual de header de días: el bloque de fecha queda centrado por columna en management y standalone para una lectura más equilibrada.
- Cambio CSS mínimo, sin modificar lógica de fecha ni layout funcional.

## 1.10.28
- Ajuste visual fino: se aumenta la separación entre header de día y primera tarjeta a `margin-top: 6px` en frontend/standalone.
- Cambio CSS mínimo y sobrio, sin tocar `grid-auto-rows`, `min-height` ni padding interno de `.acal-task`.

## 1.10.27
- Fix standalone: el botón `Ir` del selector de fecha conserva `acal_standalone=1` en el formulario, evitando redirección fuera de la vista standalone.
- La navegación por fecha vuelve a recargar correctamente la semana dentro de la misma vista.

## 1.10.26
- Standalone: se aumenta el tamaño visual del logo en header manteniendo proporción y alineación a la derecha (`max-height: 84px`).
- Ajuste CSS mínimo, sin cambios en lógica de carga/configuración del logo.

## 1.10.25
- Ajuste visual mínimo: se agrega un pequeño espacio antes de la primera tarjeta en frontend/standalone (`margin-top: 3px`) para evitar que quede demasiado pegada al encabezado.
- Sin cambios en `grid-auto-rows`, sin `min-height` nuevo y sin tocar padding interno de `.acal-task`.

## 1.10.24
- Hotfix de causa raíz del espacio vertical: en frontend/standalone la grilla deja de reservar alto de fila por defecto (`grid-auto-rows`) y las celdas de cuerpo ya no fuerzan `min-height`.
- Se elimina reserva superior sobrante en cuerpo de celdas de día (sin tocar `padding` interno de `.acal-task`).
- Ajuste CSS quirúrgico para mantener estable header y columna de técnico.

## 1.10.23
- Ajustes: campo de logo standalone ahora usa biblioteca de medios de WordPress con botón `Seleccionar logo`.
- Se añade opción `Quitar` y preview/URL básica para confirmar la imagen seleccionada antes de guardar.
- La URL elegida persiste en la configuración existente al guardar ajustes, sin depender de pegado manual.

## 1.10.22
- Hotfix frontend: se corrige recorte visual de cards y visibilidad de botones internos (kebab/copiar) ajustando padding y tamaño en tarjeta.
- Hotfix standalone: se reduce espacio residual en el cuerpo de celdas (inicio del contenido más arriba).
- Ajuste CSS puntual y compatible, sin cambios de lógica.

## 1.10.21
- Hotfix urgente de cuerpo de tarjetas (frontend + standalone): se reduce padding superior para que el texto comience más arriba dentro de cada tarea.
- Se ajusta el margen superior del título de tarea para eliminar sensación de contenido hundido en la tarjeta.
- Cambio CSS acotado, sin modificaciones de lógica.

## 1.10.20
- Ajuste UX x8 en frontend y standalone: se elimina el padding superior residual del cuerpo de celdas para acercar al máximo las tarjetas al encabezado.
- En standalone se compacta más la altura mínima de celda para reforzar la reducción de distancia visual solicitada.
- Cambio CSS puntual, sin tocar lógica ni datos.

## 1.10.19
- Hotfix frontend: se alinea mejor la primera fila de celdas de días con la celda de técnico (columna izquierda).
- Hotfix standalone: se reduce nuevamente la distancia superior del cuerpo de columnas para evitar el aumento visual introducido en el ajuste anterior.
- Ajuste CSS acotado, sin cambios de lógica ni datos.

## 1.10.18
- Hotfix visual frontend: se ordena la topbar en gestión removiendo segmentación extra que desalineaba el header.
- Se compacta el cuerpo de columnas en frontend y standalone reduciendo `padding-top` y `min-height` para acercar tarjetas al encabezado.
- Ajuste CSS puntual, sin cambios de lógica ni datos.

## 1.10.17
- Hotfix de espaciado vertical antes de la primera tarjeta: en gestión/frontend se reduce la reserva superior de celda para evitar hueco extra tras compactar el header.
- En standalone se reduce `padding-top` del cuerpo de celda y `min-height` base para acercar visiblemente la primera tarjeta al encabezado del día.
- Ajuste mínimo y visual; sin cambios en lógica de datos, endpoints ni render de tarjetas.

## 1.10.16
- Se reduce el espacio vertical superior en la grilla de gestión frontend ajustando la reserva superior de celdas (acciones) y compactando header de día.
- Header de día en gestión frontend reorganizado: día/fecha arriba izquierda + enlace discreto **Exportar PNG** abajo derecha, manteniendo funcionalidad existente.
- Botones de acción en tarjetas (`kebab` y copiar inline) quedan siempre visibles pero con menor protagonismo visual (menos contraste, estilo más limpio).
- Se agrega cabecera standalone estilo blanco/rojo con logo configurable desde Ajustes (URL), sin hardcodear logo ni cambiar lógica de datos.

## 1.10.15
- Fase 3 UX en frontend management (mobile/tablet): grilla semanal más legible y menos densa para interacción táctil.
- En <=900px se reduce complejidad visual: celdas/cabecera compactas y columna técnica sin sticky lateral para disminuir solapes en scroll.
- En <=640px se ajustan tamaños de pastilla de técnico y botones de acción para mejorar toque/lectura sin tocar lógica del plugin.

## 1.10.14
- Fase 2.1 UX en frontend management: ajuste fino responsive de topbar para reducir saturación visual en pantallas <=1023px.
- Se compactan levemente encabezados/espaciados de bloques y se evita desborde horizontal del buscador en mobile/tablet.
- Los filtros pasan a ocupar ancho completo en mobile para lectura y toque más simple, sin cambiar lógica funcional.

## 1.10.13
- Fase 2 UX en frontend management: topbar segmentada visualmente en bloques de **Navegación semanal**, **Acciones rápidas** y **Filtros** para reducir carga visual.
- Se mejoran contenedores de filtros con chips suaves para facilitar escaneo y lectura de controles.
- Ajuste responsive (<=1023px): cada bloque de topbar ocupa ancho completo para evitar saturación horizontal, sin tocar lógica PHP/AJAX.

## 1.10.12
- Mejora UX de fase 1 en vistas de gestión: se agrega ayuda rápida visible para el flujo **Copiar -> Pegar aquí**.
- Se mejora claridad de acciones en tarjetas/celdas: botón **Pegar aquí** y etiqueta accesible del menú de acciones más descriptiva.
- Ajuste de microcopys en frontend para reducir fricción en errores de pegado y estado de modo copiar, sin tocar lógica de permisos, nonces ni endpoints.

## 1.10.11
- Hardening responsive en standalone (`?acal_standalone=1`) para breakpoints críticos (<=1200px, <=900px y <=640px).
- Se ajustan navegación superior, sello de actualización, alturas mínimas de celdas/cabecera y padding de tarjetas para reducir colisiones/overflow visual.
- Cambio únicamente visual en CSS scopeado a `body.acal-standalone`, sin tocar lógica PHP, permisos, nonces ni endpoints.

## 1.10.10
- Mejora visual de estados interactivos en standalone (`?acal_standalone=1`) para botones, links, input de fecha y tarjetas.
- Se refuerzan `hover` y `focus-visible` con contorno/foco consistente y contraste estable, manteniendo scope `body.acal-standalone`.
- Cambio exclusivamente CSS; sin modificaciones de lógica PHP, permisos, nonces o endpoints.

## 1.10.9
- Ajuste tipográfico en standalone (`?acal_standalone=1`) para reforzar jerarquía visual entre día/fecha de cabecera, título de tarjeta y metadatos secundarios.
- Se ajustan `font-size`, `font-weight`, `line-height` y contraste en scope `body.acal-standalone`, incluyendo calibración en <=1023px.
- Cambio visual acotado; sin modificaciones en lógica PHP, permisos, nonces o endpoints.

## 1.10.8
- Ajuste puntual de densidad vertical en standalone (`?acal_standalone=1`) para armonizar topbar, cabecera de días y celdas de contenido.
- Se normalizan alturas mínimas y márgenes verticales de técnico/tarjetas dentro de scope `body.acal-standalone`, sin tocar lógica funcional.
- Incluye calibración responsive (<=1023px) para mantener ritmo visual consistente.

## 1.10.7
- Limpieza de CSS standalone en `assets/rs-upgrade.css`: se eliminan bloques legacy duplicados de 1.10.2 y 1.10.3 que sobrescribían reglas entre sí y dificultaban la trazabilidad visual.
- Se mantiene el bloque de ajuste vigente (1.10.6) y la base actual, sin cambios de lógica PHP, permisos, nonces ni endpoints.
- Ajuste de mantenimiento visual acotado a standalone para reducir riesgo de regresión por cascada/conflicto de estilos.

## 1.10.6
- Se confirma y corrige la causa del espacio superior en standalone: reglas globales en `rs-upgrade.css` (`.acal-grid .acal-cell { padding-top: 38px; }`) estaban contaminando contextos fuera de management/admin.
- Las reglas con `padding-top: 38px` quedan scopeadas a `body.acal-front-management` y `body.wp-admin` para que no afecten la vista standalone.
- Se eliminan hacks redundantes basados en `nth-child` (1.10.4/1.10.5) y se reemplazan por reglas standalone limpias para celdas, técnico y tarjetas.
- Ajuste visual sin cambios de backend ni comportamiento funcional.

## 1.10.5
- Se elimina el espacio residual entre el header de días y la primera fila de tarjetas OT en standalone (desktop) ajustando `min-height` y `padding-top` de la primera fila de celdas de contenido.
- Se fuerza `margin-top: 0` en la primera fila de tarjetas y en el primer bloque de técnico para alinear el contenido inmediatamente bajo la cabecera.
- Hotfix visual puntual encapsulado a `body.acal-standalone`, sin cambios de lógica ni backend.

## 1.10.4
- Se corrige el espacio vertical excesivo entre el header de días y la primera fila de tarjetas OT en standalone (desktop), compactando la primera fila de celdas de contenido.
- Se ajusta el margen superior de la primera fila de tarjetas y del bloque de técnico para alinear visualmente el inicio del contenido bajo la cabecera.
- Ajuste visual puntual, encapsulado a `body.acal-standalone`, sin cambios de comportamiento ni backend.

## 1.10.3
- Hotfix visual standalone para corregir errores visibles introducidos en 1.10.2: se normaliza la cabecera de días (tamaño/alineación) y se elimina la superposición visual en desktop.
- Se recalibra la densidad de topbar y la etiqueta `Actualizado` para mantener alineación estable sin quiebres.
- Se corrige la meta secundaria de tarjetas OT removiendo viñeta forzada que ensuciaba lectura.
- Ajustes encapsulados a `body.acal-standalone`, sin cambios de comportamiento ni backend.

## 1.10.2
- Se aplica ajuste visual standalone inspirado en la referencia adjunta: topbar más compacta y ordenada, mejor jerarquía en navegación semanal y timestamp `Actualizado` integrado con mayor legibilidad.
- Se refina cabecera de días y composición general de la grilla para un look más limpio/técnico en desktop, con densidad y contraste más consistentes.
- Se suaviza presentación de tarjetas OT (bordes/sombras/meta secundaria) manteniendo el mismo comportamiento funcional.
- Se agregan ajustes responsive para conservar prolijidad en mobile sin romper layout.

## 1.10.1
- Se elimina completamente en standalone el bloque visual de rango semanal (`Semana: dd/mm/yyyy — dd/mm/yyyy`) para evitar redundancia en cabecera.
- El sello `Actualizado: dd/mm/yyyy hh:mm` se integra en la misma línea de la navegación semanal (`Semana anterior`, selector de fecha, `Ir`, `Próxima semana`) para un uso de espacio superior más eficiente.
- Ajuste visual acotado y encapsulado a standalone, sin cambios de comportamiento ni backend.

## 1.10.0
- Se aplica refresh visual **exclusivo de standalone** (`?acal_standalone=1`) con enfoque desktop: barra de contexto semanal y topbar con mayor jerarquía visual, mejor contraste y menor sensación de espacio vacío superior.
- Se fortalece el bloque de navegación semanal (controles, bordes, hover y tipografía) manteniendo el mismo flujo funcional sin cambios de comportamiento.
- Se rediseña el encabezado de días con más presencia visual (jerarquía día/fecha) y se ordena la composición general de la grilla con bordes/sombras consistentes.
- Se mejora lectura de tarjetas OT por técnico: separación más clara entre título y metadatos secundarios, densidad más respirable y estados hover/focus más modernos.
- Se añaden ajustes responsive para no romper en mobile y conservar una presentación prolija.

## 1.9.49
- Se ajusta criterio UX por contexto: en **frontend management** los títulos de OT/tarea vuelven a truncado controlado (2 líneas) para mantener densidad operacional.
- En **standalone** se mantiene prioridad de lectura completa del título (sin truncado agresivo), separando explícitamente reglas CSS por vista.
- Se corrige ancho de grilla desktop para evitar quiebres visuales por columnas sobredimensionadas y reducir el ancho excesivo percibido de la primera columna.

## 1.9.48
- Se prioriza lectura operativa de OT/tareas: el título principal deja de truncarse visualmente (sin cortes agresivos con `...`) en frontend management y standalone.
- Las tarjetas ahora permiten crecimiento vertical controlado para mostrar títulos completos, en lugar de forzar alturas fijas que ocultaban contenido.
- Se ajustan densidad y ancho útil de columnas en desktop para mejorar visibilidad del texto sin perder estabilidad de la grilla.
- Se mantienen sticky header/columna y se recalibra el layout para evitar solapes al crecer contenido.

## 1.9.47
- Se aplica la mejora visual directamente a la vista `?acal_standalone=1` (desktop), corrigiendo que el ajuste previo no impactara esta pantalla.
- Se compactan barra semanal y cabecera de días en standalone, reduciendo altura y espacios muertos sin perder legibilidad.
- Se normaliza densidad de grilla/celdas y se mejora alineación general entre columna de técnicos y columnas de días.
- Se moderniza la presentación de labels de técnicos y tarjetas OT (tipografía, contraste, padding y hover/focus).
- Se refuerza sticky header + sticky columna de técnicos en standalone con z-index/fondos consistentes para evitar solapes visuales.

## 1.9.46
- Se compacta la topbar del gestor frontend (desktop) para reducir altura percibida y espacios muertos, conservando un balance densidad/aire visual.
- Se normalizan paddings/alturas de grilla semanal (header, celdas, acciones y tarjetas OT) para mejorar alineación y ritmo visual.
- Se refuerza jerarquía y legibilidad de tarjetas OT (título/metadatos/controles), con mayor contraste y estados hover/focus más claros.
- Se moderniza la columna de técnicos (labels y controles) con mejor truncado, consistencia de tamaño y lectura.
- Se robustece el comportamiento sticky de header de días y columna de técnicos en desktop frontend para navegación más estable.

## 1.9.45
- Se moderniza tipografía del header de días en standalone readonly separando visualmente día y fecha (ej. `LUN` + `09/03`) con mejor jerarquía y contraste.
- Se reduce espacio residual en la fila de cabecera de días con altura fija compacta (`height/min-height`) y ajuste de celda de esquina para eliminar aire innecesario.
- Se ajusta versión móvil del header de días para mantener proporción compacta y legibilidad.

## 1.9.44
- Se corrige la cascada CSS en standalone para compactar efectivamente el header de días: `min-height` ahora aplica solo a celdas de contenido (`.acal-cell:not(.acal-head)`), eliminando el espacio vacío que mantenía alta la fila de cabecera.
- Se reduce visualmente la topbar de navegación semanal y se refinan tipografía/espaciado del header de días para un resultado más compacto y premium en desktop.
- Se ajustan también los tamaños del header de días en móvil para conservar proporción y limpieza visual.

## 1.9.43
- Se agrega encabezado de contexto semanal en vista standalone readonly (rango de semana + timestamp de actualización) para mejorar orientación operacional.
- Se moderniza y compacta el header de días en standalone: menor altura/espaciado, mejor jerarquía tipográfica y alineación más limpia con look visual más premium.
- Se ajusta la presentación móvil del nuevo contexto semanal y cabeceras para mantener legibilidad y densidad sin afectar el gestor frontend de edición.

## 1.9.42
- Se mejora legibilidad de tarjetas en vista standalone readonly: truncado controlado de título/metadatos y `title` nativo con contenido completo para recuperar contexto en desktop.
- Se corrigen errores visibles en standalone móvil/desktop: topbar móvil reordenada y más usable, grilla/celdas reajustadas y desactivación de sticky lateral en móvil para evitar solapes de la columna de técnicos.
- Se mantiene sticky de cabecera y el comportamiento de lectura en viewport sin afectar el gestor frontend de edición.

## 1.9.41
- Se corrige regresión en vista standalone readonly (`?acal_standalone=1`): se elimina el `overflow` que rompía el contexto de `position: sticky`, restaurando el comportamiento sticky de cabecera/columna con scroll de viewport.
- Se aplica ajuste responsive adicional para standalone en móvil (ancho de columnas, densidad de celdas/tarjetas y legibilidad de cabeceras) sin afectar el gestor frontend.

## 1.9.40
- Se aplica un refresh visual específico para la vista standalone legacy readonly (`?acal_standalone=1`) sin afectar el gestor frontend de edición.
- Se mejora jerarquía visual y legibilidad en standalone: fondo, contenedor, topbar de navegación semanal, grilla y tarjetas con mejor contraste/espaciado.
- Se refuerza accesibilidad visual en standalone con estilos de foco visible para navegación por teclado.

## 1.9.39
- Se mejora legibilidad del tooltip flotante con contenido estructurado (título y metadatos en líneas separadas), reemplazando el texto concatenado en una sola línea.
- Se refuerza accesibilidad: al mostrar tooltip en hover/focus la tarjeta activa enlaza `aria-describedby` al tooltip y se limpia al cerrar.

## 1.9.38
- Se corrige regresión de layout en botón **+Agregar**: se elimina override de `position` en acciones de celda para conservar el anclaje absoluto original y evitar que el botón quede montado sobre la tarjeta.
- Se mantiene prioridad visual (`z-index`) de acciones por encima del tooltip flotante sin romper la distribución de la celda.

## 1.9.37
- Se corrige visibilidad del botón **+Agregar** y acciones de celda cuando hay tooltip activo: ahora los controles de acción se renderizan por encima del tooltip flotante mediante ajuste de `z-index`.

## 1.9.36
- Se mejora la opción 2 de tooltip para tarjetas truncadas con posicionamiento dinámico (floating tooltip en `body`) y lógica de flip arriba/abajo según espacio en viewport.
- Se corrige compatibilidad con mouse hover en celdas complejas de la grilla: el tooltip ya no depende del pseudo-elemento dentro de la tarjeta y evita recortes por stacking/overflow.

## 1.9.35
- Se corrige tooltip expandido en tarjetas para interacción con mouse (`hover`): la tarjeta ya no recorta el contenido del tooltip por `overflow` y eleva `z-index` en hover/focus para asegurar visibilidad sobre la grilla.

## 1.9.34
- Se implementa alternativa UI para tarjetas truncadas: tooltip expandido al `hover/focus` en frontend management, mostrando contenido completo (título + metadatos) sin romper densidad de grilla.
- Se agrega foco accesible en tarjeta para visualizar tooltip también por teclado.

## 1.9.33
- Se agrega guía operativa `QA.md` con checklist de regresión/smoke para flujos críticos (navegación, filtros, CRUD, copy/paste, drag & drop, atajos teclado y permisos/nonce).
- `README.md` incorpora sección de QA con enlace directo a la guía para validar releases antes de despliegue.

## 1.9.32
- Se corrige guardado de turno al crear tareas: cuando el formulario llega sin turno explícito ahora se persiste por defecto como `am`, evitando que el valor preseleccionado se pierda.
- Se mejora accesibilidad del gestor frontend en grilla (desktop): celdas enfocables con feedback visual, soporte de teclado (`Enter` para agregar y `Ctrl/Cmd+V` para pegar en celda) y atajo `N` para nueva tarea.
- Se agrega región `aria-live` para anunciar acciones rápidas de teclado en la planificación.

## 1.9.31
- Se refactoriza `assets/admin.js` para unificar la lógica de **copiar/pegar** en un único módulo y eliminar bindings/eventos duplicados.
- Se conserva compatibilidad funcional con menú kebab, botón inline 📋, acción **Pegar** por celda y evento `acal:clipboard-clear`.
- Se reduce riesgo de dobles llamadas AJAX y comportamiento inconsistente en modo copiar.

## 1.9.30
- Se mejora la legibilidad de tarjetas en el gestor frontend desktop: ajuste de densidad en celdas, jerarquía tipográfica y truncado controlado (2 líneas) para título/descripción.
- Se corrige cálculo de fechas en acciones rápidas (**Hoy** / **Semana actual**) para evitar desfases en sitios no UTC: se reemplaza el uso de timestamp local por `current_datetime()` en zona horaria del sitio.

## 1.9.29
- Se mejora la topbar del gestor frontend (desktop): nueva disposición visual con navegación semanal, acciones rápidas y filtros en estructura más escaneable.
- Se agregan acciones rápidas **Hoy**, **Semana actual** y **Limpiar filtros** para reducir clics en la operación diaria.
- Se añade realce de acción activa por fecha (`is-active`) y feedback visual breve al limpiar filtros.

## 1.9.28
- Se refuerza la preservación de contexto/filtros en redirecciones post crear/editar/eliminar: `redirect_to` ahora se procesa con `wp_unslash` y validación robusta de query.
- Se mejora la detección de `date` en `redirect_to` parseando parámetros reales (evita falsos positivos por coincidencias de texto) para mantener intactos filtros activos.

## 1.9.27
- Se corrige redirección post-guardar en creación/edición de tareas para mantener el contexto actual (frontend management cuando aplica), evitando volver forzadamente a admin legacy.
- Se agrega `redirect_to` al formulario del modal de tarea y se unifica la validación de retorno seguro en un helper reutilizable para crear/editar/eliminar.

## 1.9.26
- Se corrige el flujo de eliminación de tareas para mantener el contexto actual: al borrar desde gestión frontend ahora retorna a la misma vista (en lugar de forzar redirección al admin legacy).
- Se agrega `redirect_to` seguro en formularios de eliminación y se valida servidor-side con `wp_validate_redirect`, conservando compatibilidad con la vista admin.

## 1.9.25
- Mejora UI/UX del modal **Nueva tarea** en frontend: se reorganiza en bloques "Datos clave" y "Detalle de la tarea" para acelerar el flujo de creación.
- Se añaden ayudas de contexto y validación inline accesible (`aria-invalid`, mensajes por campo y región `aria-live`) para técnico, fecha y longitud de descripción.
- Se refuerza visualmente el estado de error en campos y se ajusta el layout responsive del formulario dentro del modal.

## 1.9.24
- Se corrige seguridad en exportación CSV por técnico: ahora cada celda se sanea para prevenir **CSV formula injection** en Excel/Google Sheets (prefijo seguro en valores que inician con `=`, `+`, `-` o `@`).

## 1.9.23
- Se corrige el filtro por técnico en la vista semanal: ahora aplica correctamente y muestra solo la fila del técnico seleccionado.
- Se aplica el filtrado activo (técnico/sucursal/estado/búsqueda) sobre las tareas visibles de cada celda del calendario semanal.
- Se agrega en Importar/Exportar la opción de descargar **CSV por técnico** (formato amigable para Excel/Google Sheets) con columnas de fecha, técnico, cliente, sucursal, equipo, estado, turno y descripción.

## 1.9.22
- Se corrige superposición y alineación de acciones en tarjetas de la vista semanal frontend: se estabiliza layout de `.acal-task`, `kebab` y botón de copia inline con espaciado reservado y z-index consistente.
- Se corrige modal de edición en frontend: overlay `fixed` completo, bloqueo de scroll de fondo, límite de tamaño por viewport y scroll interno para evitar cortes de contenido/acciones.
- Se corrige contraste del botón **Filtrar** en topbar frontend (`button-primary`) para asegurar texto visible en estados normal/hover/focus/active.

## 1.9.21
- Se mejora accesibilidad operable en la ruta frontend de gestión: modal con `role="dialog"`, `aria-modal`, `aria-hidden`, cierre por teclado y foco controlado.
- Se agregan atributos ARIA en acciones de calendario (kebab/menú de tarea, botones agregar/pegar) para reforzar navegación por teclado y soporte de lector de pantalla.
- Se añade estilo de foco visible (`:focus-visible`) y refinamiento visual del botón de cierre del modal en frontend de gestión.

## 1.9.20
- Se inicia la deprecación UX de la vista admin (modo legacy): ahora muestra aviso y CTA al gestor frontend recomendado.
- Se aplica un refresh visual notorio desktop-first en la ruta frontend de gestión (`/calendario-taller/` o slug configurado): topbar elevada, jerarquía tipográfica, grilla y tarjetas modernizadas.
- Se mantienen arquitectura, permisos y endpoints existentes para evitar quiebres funcionales durante la transición desde admin a frontend.

## 1.9.19
- Se optimiza la carga de assets frontend por contexto: la ruta de gestión carga solo assets de gestión, y standalone/shortcode cargan solo assets de lectura.
- Se encapsula la lógica en helpers (`enqueue_management_assets`, `enqueue_readonly_assets`, `is_standalone_request`) para reducir dependencia del shortcode en rutas no objetivo.
- `maybe_standalone()` reutiliza detección centralizada de request standalone.

## 1.9.18
- Se corrige guardado de slug frontend: al guardar ajustes se registra la regla con el nuevo slug antes de `flush_rewrite_rules()`, evitando que la URL nueva falle hasta un flush manual.
- Se desacopla la vista frontend de solo lectura del shortcode: se extrae renderer interno y `?acal_standalone=1` ya no depende de `shortcode_calendar()`.
- Se mantiene `/calendario-taller/` (o slug configurado) como ruta de gestión y `?acal_standalone=1` como visualización.

## 1.9.17
- Se agrega ruta frontend de gestión del calendario vía rewrite (`/calendario-taller/` por defecto), servida por template interno del plugin (sin depender del theme ni shortcode).
- La ruta de gestión exige usuario logueado con capacidad `read` y redirige a login cuando no hay sesión.
- Se añade ajuste en admin para editar el slug de la ruta de gestión y se refrescan reglas de rewrite al guardar/activar/desactivar.

## 1.9.16
- La acción **Pegar en otra fecha…** (solo en tarea origen copiada) ahora usa selector de fecha (`input type="date"`) en lugar de `prompt()`.
- Se agrega mini modal con botones **Confirmar/Cancelar**, validación de formato y selección más intuitiva.
- Se mantiene sin cambios el flujo de **Pegar** rápido por celda en la semana visible.

## 1.9.15
- Se corrige UX: **Pegar en otra fecha…** ya no aparece en todas las celdas.
- La acción **Pegar en otra fecha…** se muestra solo en la tarea origen copiada para evitar confusión.
- Se mantiene **Pegar** rápido por celda en la semana visible actual.

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
