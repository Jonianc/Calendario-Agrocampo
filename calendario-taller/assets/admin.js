(function($){
  function openModal(mode, preset){
    $('#acal-modal').show();
    if(mode === 'create'){
      $('#acal-modal-title').text('Nueva tarea');
      $('#acal-action').val('acal_create_task');
      $('#acal-task-id').val('');
      if(preset && preset.tecnico_id){ $('#acal-tecnico-id').val(preset.tecnico_id); }
      if(preset && preset.fecha){ $('#acal-fecha').val(preset.fecha); }
      $('#acal-estado').val('programado'); $('#acal-sucursal').val(''); $('#acal-cliente').val(''); $('#acal-equipo').val(''); $('#acal-descripcion').val('');
    } else {
      $('#acal-modal-title').text('Editar tarea');
      $('#acal-action').val('acal_update_task');
      $('#acal-task-id').val(preset.id || '');
      $('#acal-tecnico-id').val(preset.tecnico_id || '');
      $('#acal-fecha').val(preset.fecha || '');
      $('#acal-estado').val(preset.estado || 'programado');
      $('#acal-sucursal').val(preset.sucursal || '');
      $('#acal-cliente').val(preset.cliente || '');
      $('#acal-equipo').val(preset.equipo || '');
      $('#acal-descripcion').val(preset.descripcion || '');
    }
  }
  function closeModal(){ $('#acal-modal').hide(); }

  $(document).on('click', '.acal-add', function(e){ e.preventDefault(); openModal('create', { tecnico_id: $(this).data('tecnico'), fecha: $(this).data('date') }); });
  $(document).on('click', '.acal-edit', function(e){ e.preventDefault(); var data=$(this).data('task'); if(typeof data==='string'){ try{ data=JSON.parse(data);}catch(e){} } openModal('edit', data); });
  $(document).on('click', '.acal-modal-close', function(){ closeModal(); });
  $(document).on('click', '#acal-modal', function(e){ if(e.target === this) closeModal(); });

  // Kebab menu
  $(document).on('click', '.acal-kebab', function(e){ e.preventDefault(); e.stopPropagation(); var $w=$(this).closest('.acal-kebab-wrap'); $('.acal-menu').hide(); $w.find('.acal-menu').toggle(); });
  $(document).on('click', function(){ $('.acal-menu').hide(); });
})(jQuery);


/* ===== Copiar / Pegar por ID (robusto) ===== */
(function($){
  var CLIP_ID = null;
  function refreshPaste(){ if(CLIP_ID){ $('.acal-paste').show(); } else { $('.acal-paste').hide(); } }

  $(document).on('click', '.acal-copy', function(e){
    e.preventDefault();
    var tid = $(this).data('task-id');
    CLIP_ID = tid ? parseInt(tid,10) : null;
    refreshPaste();
  });
  
  // Permite que otros módulos limpien el “portapapeles” (CLIP_ID)
$(document).on('acal:clipboard-clear', function(){
  CLIP_ID = null;
  if (typeof refreshPaste === 'function') refreshPaste();
});


  $(document).on('click', '.acal-paste', function(e){
    e.preventDefault();
    if(!CLIP_ID) return;

    var $b = $(this);
    var defaultDate = String($b.data('date') || '');
    var targetDate = window.prompt('Fecha destino (YYYY-MM-DD). Puedes pegar en otra semana:', defaultDate);
    if (targetDate === null) return;
    targetDate = String(targetDate).trim();
    if(!/^\d{4}-\d{2}-\d{2}$/.test(targetDate)){
      alert('Fecha inválida. Usa formato YYYY-MM-DD.');
      return;
    }

    $b.prop('disabled', true);
    $.post(ajaxurl, {
      action: 'acal_paste_task',
      nonce: (window.ACAL_NONCE || ''),
      src_id: CLIP_ID,
      tecnico_id: $b.data('tecnico'),
      fecha: targetDate
    }).done(function(r){
      if(r && r.success){
        window.location = window.location.pathname + '?page=acal_calendario&date=' + encodeURIComponent(targetDate);
      } else {
        alert((r && r.data && r.data.msg) || 'No se pudo pegar');
      }
    }).fail(function(){ alert('Error pegando'); })
      .always(function(){ $b.prop('disabled', false); });
  });

  $(function(){ refreshPaste(); });
})(jQuery);

/* ===== Inyectar “Copiar” (robusto, por ID) ===== */
/* ===== Inyectar “Copiar” (robusto, por ID) + Pegar ===== */
(function($){
  var CLIP_ID = null;

  function refreshPaste(){ $('.acal-paste')[CLIP_ID ? 'show' : 'hide'](); }

  // Obtiene el ID de la tarea desde el menú
  function getTaskIdFromMenu($menu){
    // a) hidden del form de Eliminar (el más estable)
    var tid = $menu.find('input[name="task_id"]').val();
    if (tid) return parseInt(tid, 10);

    // b) data-json del enlace Editar
    var payload = $menu.find('.acal-edit').data('json');
    try{ if(typeof payload === 'string') payload = JSON.parse(payload); }catch(e){ payload = null; }
    if (payload && payload.id) return parseInt(payload.id, 10);

    // c) atributo en la tarjeta
    var t = $menu.closest('.acal-task').data('taskId') || $menu.closest('.acal-task').data('task-id');
    if (t) return parseInt(t, 10);

    return null;
  }

  // Inserta “Copiar” si aún no existe
  function ensureCopy($menu){
    if (!$menu.length || $menu.find('.acal-copy').length) return;
    var id = getTaskIdFromMenu($menu);
    if (!id) return;
    var $edit = $menu.find('.acal-edit').first();
    var $copy = $('<a href="#" class="acal-menu-link acal-copy" data-task-id="'+ id +'">Copiar</a>');
    if ($edit.length) $copy.insertAfter($edit); else $menu.prepend($copy);
  }

  // Disparadores para inyectar “Copiar”
  $(document).on('click', '.acal-kebab', function(){
    var $m = $(this).siblings('.acal-menu');
    setTimeout(function(){ ensureCopy($m); }, 0);
  });
  $(document).on('mouseenter focusin', '.acal-menu', function(){ ensureCopy($(this)); });

  // Clipboard por ID
  $(document).on('click', '.acal-copy', function(e){
    e.preventDefault();
    var tid = $(this).data('task-id');
    CLIP_ID = tid ? parseInt(tid, 10) : null;
    refreshPaste();
  });

  // Pegar en celda
  $(document).on('click', '.acal-paste', function(e){
    e.preventDefault();
    if (!CLIP_ID) return;

    var $b = $(this);
    var defaultDate = String($b.data('date') || '');
    var targetDate = window.prompt('Fecha destino (YYYY-MM-DD). Puedes pegar en otra semana:', defaultDate);
    if (targetDate === null) return;
    targetDate = String(targetDate).trim();
    if(!/^\d{4}-\d{2}-\d{2}$/.test(targetDate)){
      alert('Fecha inválida. Usa formato YYYY-MM-DD.');
      return;
    }

    $b.prop('disabled', true);
    $.post(ajaxurl, {
      action: 'acal_paste_task',
      nonce: (window.ACAL_NONCE || ''),
      src_id: CLIP_ID,
      tecnico_id: $b.data('tecnico'),
      fecha: targetDate
    }).done(function(r){
      if (r && r.success) {
        window.location = window.location.pathname + '?page=acal_calendario&date=' + encodeURIComponent(targetDate);
      } else {
        alert((r && r.data && r.data.msg) || 'No se pudo pegar');
      }
    }).fail(function(){ alert('Error pegando'); })
      .always(function(){ $b.prop('disabled', false); });
  });

  $(function(){ $('.acal-menu').each(function(){ ensureCopy($(this)); }); refreshPaste(); });
})(jQuery);


/* ===== Copiar/Pegar robusto (menu + botón inline) ===== */
(function($){
  var CLIP_ID = null;
  function refreshPaste(){ $('.acal-paste')[CLIP_ID ? 'show' : 'hide'](); }

  function getTaskIdFromMenu($menu){
    var tid = $menu.find('input[name="task_id"]').val();
    if (tid) return parseInt(tid,10);
    var payload = $menu.find('.acal-edit').data('json');
    try{ if(typeof payload==='string') payload=JSON.parse(payload); }catch(e){ payload=null; }
    if (payload && payload.id) return parseInt(payload.id,10);
    var t = $menu.closest('.acal-task').data('taskId') || $menu.closest('.acal-task').data('task-id');
    if (t) return parseInt(t,10);
    return null;
  }

  function ensureCopy($menu){
    if(!$menu.length || $menu.find('.acal-copy').length) return;
    var id = getTaskIdFromMenu($menu);
    if(!id) return;
    var $edit = $menu.find('.acal-edit').first();
    var $copy = $('<a href="#" class="acal-menu-link acal-copy" data-task-id="'+id+'">Copiar</a>');
    if($edit.length) $copy.insertAfter($edit); else $menu.prepend($copy);
  }

  // Inyección en menú al abrir
  $(document).on('click', '.acal-kebab', function(){
    var $m = $(this).siblings('.acal-menu');
    setTimeout(function(){ ensureCopy($m); }, 0);
  });
  $(document).on('mouseenter focusin', '.acal-menu', function(){ ensureCopy($(this)); });

  // Botón inline al lado del kebab (si podemos deducir el ID)
  function addInlineCopy(){
    $('.acal-kebab-wrap').each(function(){
      var $wrap=$(this);
      if($wrap.find('.acal-copy-inline').length) return;
      var $menu=$wrap.find('.acal-menu');
      var id = getTaskIdFromMenu($menu);
      if(!id) return;
      var $k = $wrap.find('.acal-kebab').first();
      var $btn=$('<button type="button" class="button button-small acal-copy-inline" title="Copiar" style="margin-left:4px">📋</button>').attr('data-task-id', id);
      if($k.length) $btn.insertAfter($k);
    });
  }

  // Clipboard
  $(document).on('click', '.acal-copy, .acal-copy-inline', function(e){
    e.preventDefault();
    var tid = $(this).data('task-id');
    CLIP_ID = tid ? parseInt(tid,10) : null;
    refreshPaste();
  });

  // Pegar
  $(document).on('click', '.acal-paste', function(e){
    e.preventDefault();
    if(!CLIP_ID) return;

    var $b = $(this);
    var defaultDate = String($b.data('date') || '');
    var targetDate = window.prompt('Fecha destino (YYYY-MM-DD). Puedes pegar en otra semana:', defaultDate);
    if (targetDate === null) return;
    targetDate = String(targetDate).trim();
    if(!/^\d{4}-\d{2}-\d{2}$/.test(targetDate)){
      alert('Fecha inválida. Usa formato YYYY-MM-DD.');
      return;
    }

    $b.prop('disabled', true);
    $.post(ajaxurl, {
      action:'acal_paste_task',
      nonce:(window.ACAL_NONCE||''),
      src_id: CLIP_ID,
      tecnico_id: $b.data('tecnico'),
      fecha: targetDate
    }).done(function(r){
      if(r && r.success){
        window.location = window.location.pathname + '?page=acal_calendario&date=' + encodeURIComponent(targetDate);
      }else{
        alert((r && r.data && r.data.msg)||'No se pudo pegar');
      }
    }).fail(function(){ alert('Error pegando'); })
      .always(function(){ $b.prop('disabled', false); });
  });

  $(function(){ setTimeout(addInlineCopy, 0); refreshPaste(); });
})(jQuery);

/* ===== Colocar solo "Pegar" en la esquina izq. sin mover "+ Agregar" ===== */
(function($){
  function placePasteOnly(){
    $('.acal-cell').each(function(){
      var $cell  = $(this);
      var $paste = $cell.find('.acal-paste').first();
      if(!$paste.length) return;

      // Crea contenedor fijo SI no existe
      if(!$cell.find('.acal-actions-fixed').length){
        $('<div class="acal-actions-fixed"></div>').prependTo($cell);
      }
      var $box = $cell.find('.acal-actions-fixed');

      // Mueve SOLO el botón "Pegar" al contenedor (evita duplicados)
      if(!$box.find('.acal-paste').length){
        $box.append($paste);
      }
    });
  }

  // Primera pasada
  $(placePasteOnly);

  // Reaplica cuando el calendario se re-renderiza
  var mo = new MutationObserver(function(){ placePasteOnly(); });
  mo.observe(document.body, { childList:true, subtree:true });

  // Eventos del propio plugin (si existen)
  $(document).on('acal:rerender acal:rendered', placePasteOnly);
})(jQuery);

/* ===== Modo copiar: ocultar +Agregar cuando se copia ===== */
(function($){
  function enterCopyMode(){
    if (!$('body').hasClass('acal-copy-mode')){
      $('body').addClass('acal-copy-mode');
      if (!$('#acal-copy-pill').length){
        $('body').append(
          '<div id="acal-copy-pill" class="acal-copy-pill">Modo copiar activo — presiona ESC para salir <button type="button" class="acal-copy-exit">Salir</button></div>'
        );
      }
    }
  }
  function exitCopyMode(){
    $(document).trigger('acal:clipboard-clear');
    $('body').removeClass('acal-copy-mode');
    $('#acal-copy-pill').remove();
  }

  // Al copiar, entra a modo copiar (no tocamos la lógica de clipboard previo)
  $(document).on('click', '.acal-copy, .acal-copy-inline', function(){
    enterCopyMode();
  });

  // Salir con ESC
  $(document).on('keydown', function(e){
    if (e.key === 'Escape') exitCopyMode();
  });

  // Salir con el botón del aviso
  $(document).on('click', '.acal-copy-exit', function(){
    exitCopyMode();
  });

  // Al cargar, asegúrate de estar fuera de modo copiar
  $(function(){ exitCopyMode(); });
})(jQuery);
