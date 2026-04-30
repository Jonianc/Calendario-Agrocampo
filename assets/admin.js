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


/* ===== Clipboard unificado: Copiar / Pegar por ID + botón inline ===== */
(function($){
  var CLIP_ID = null;

  function refreshPaste(){
    $('.acal-paste')[CLIP_ID ? 'show' : 'hide']();
  }

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

  function addInlineCopy(){
    $('.acal-kebab-wrap').each(function(){
      var $wrap = $(this);
      if($wrap.find('.acal-copy-inline').length) return;

      var $menu = $wrap.find('.acal-menu');
      var id = getTaskIdFromMenu($menu);
      if(!id) return;

      var $k = $wrap.find('.acal-kebab').first();
      var $btn = $('<button type="button" class="button button-small acal-copy-inline" title="Copiar tarea" style="margin-left:4px">📋</button>').attr('data-task-id', id);
      if($k.length) $btn.insertAfter($k);
    });
  }

  function setClipboard(taskId){
    CLIP_ID = taskId ? parseInt(taskId,10) : null;
    refreshPaste();
  }

  $(document).on('click', '.acal-kebab', function(){
    var $m = $(this).siblings('.acal-menu');
    setTimeout(function(){ ensureCopy($m); }, 0);
  });

  $(document).on('mouseenter focusin', '.acal-menu', function(){
    ensureCopy($(this));
  });

  $(document).on('click', '.acal-copy, .acal-copy-inline', function(e){
    e.preventDefault();
    setClipboard($(this).data('task-id'));
  });

  $(document).on('acal:clipboard-clear', function(){
    setClipboard(null);
  });

  $(document).on('click', '.acal-paste', function(e){
    e.preventDefault();
    if(!CLIP_ID) return;

    var $b = $(this).prop('disabled', true);
    $.post(ajaxurl, {
      action:'acal_paste_task',
      nonce:(window.ACAL_NONCE||''),
      src_id: CLIP_ID,
      tecnico_id: $b.data('tecnico'),
      fecha: $b.data('date')
    }).done(function(r){
      if(r && r.success){
        var d = String($b.data('date'));
        window.location = window.location.pathname + '?page=acal_calendario&date=' + encodeURIComponent(d);
      } else {
        alert((r && r.data && r.data.msg) || 'No se pudo pegar. Intenta nuevamente.');
      }
    }).fail(function(){ alert('Error de red al pegar. Reintenta.'); })
      .always(function(){ $b.prop('disabled', false); });
  });

  $(function(){
    $('.acal-menu').each(function(){ ensureCopy($(this)); });
    setTimeout(addInlineCopy, 0);
    refreshPaste();
  });
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
          '<div id="acal-copy-pill" class="acal-copy-pill">Modo copiar activo: copia una tarea y luego usa Pegar aquí en la celda destino. Presiona ESC para salir <button type="button" class="acal-copy-exit">Salir</button></div>'
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


/* ===== Pegar en otra fecha SOLO desde tarea origen copiada ===== */
(function($){
  var SRC = { id:null, tecnico:'' };
  var DATE_MODAL_ID = 'acal-date-picker-modal';

  function parseTaskPayloadFromElement(el){
    var $task = $(el).closest('.acal-task');
    var payload = $task.find('.acal-edit').first().data('task');
    if (typeof payload === 'string') { try { payload = JSON.parse(payload); } catch(e){ payload = null; } }
    return payload && typeof payload === 'object' ? payload : null;
  }

  function clearSourceAction(){
    $('.acal-source-task').removeClass('acal-source-task');
    $('.acal-paste-other-source').remove();
  }

  function ensureDateModal(){
    var $modal = $('#'+DATE_MODAL_ID);
    if($modal.length) return $modal;

    $modal = $(
      '<div id="'+DATE_MODAL_ID+'" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:100000">'+
        '<div style="max-width:340px;margin:12vh auto;background:#fff;border-radius:8px;padding:14px;box-shadow:0 8px 28px rgba(0,0,0,.25)">'+
          '<h3 style="margin:0 0 10px;font-size:15px">Selecciona fecha destino</h3>'+
          '<input type="date" class="acal-target-date" style="width:100%;margin-bottom:10px" />'+
          '<div style="display:flex;gap:8px;justify-content:flex-end">'+
            '<button type="button" class="button acal-date-cancel">Cancelar</button>'+
            '<button type="button" class="button button-primary acal-date-confirm">Confirmar</button>'+
          '</div>'+
        '</div>'+
      '</div>'
    );

    $('body').append($modal);
    return $modal;
  }

  function openDateModal(onConfirm){
    var $modal = ensureDateModal();
    var $input = $modal.find('.acal-target-date');
    var today = new Date().toISOString().slice(0,10);

    $input.val(today);
    $modal.show();
    setTimeout(function(){ $input.trigger('focus'); }, 10);

    function close(){
      $modal.hide();
      $modal.off('.acalDatePicker');
    }

    $modal.on('click.acalDatePicker', '.acal-date-cancel', function(){ close(); });
    $modal.on('click.acalDatePicker', function(e){ if(e.target === this) close(); });
    $modal.on('click.acalDatePicker', '.acal-date-confirm', function(){
      var v = String($input.val() || '').trim();
      if(!/^\d{4}-\d{2}-\d{2}$/.test(v)){
        alert('Fecha inválida. Usa formato YYYY-MM-DD.');
        return;
      }
      close();
      if(typeof onConfirm === 'function') onConfirm(v);
    });
  }

  function renderSourceAction(){
    clearSourceAction();
    if(!SRC.id) return;
    var $task = $('.acal-task[data-task-id="'+SRC.id+'"]').first();
    if(!$task.length) return;
    $task.addClass('acal-source-task');

    var $btn = $('<button type="button" class="button button-small acal-paste-other-source" style="margin-top:6px">Pegar en otra fecha…</button>')
      .attr('data-task-id', SRC.id)
      .attr('data-tecnico', SRC.tecnico || '');

    var $anchor = $task.find('.acal-kebab-wrap').first();
    if($anchor.length){ $btn.insertAfter($anchor); }
    else { $task.append($btn); }
  }

  $(document).on('click', '.acal-copy, .acal-copy-inline', function(){
    var tid = $(this).data('task-id');
    SRC.id = tid ? parseInt(tid, 10) : null;
    var payload = parseTaskPayloadFromElement(this);
    SRC.tecnico = payload && payload.tecnico_id ? String(payload.tecnico_id) : '';
    renderSourceAction();
  });

  $(document).on('acal:clipboard-clear', function(){
    SRC = { id:null, tecnico:'' };
    clearSourceAction();
  });

  $(document).on('click', '.acal-paste-other-source', function(e){
    e.preventDefault();
    var $btn = $(this);
    var srcId = $btn.data('task-id');
    var tecnico = $btn.data('tecnico') || SRC.tecnico || '';
    if(!srcId || !tecnico){
      alert('No se pudo identificar la tarea origen.');
      return;
    }

    openDateModal(function(targetDate){
      $btn.prop('disabled', true);
      $.post(ajaxurl, {
        action:'acal_paste_task',
        nonce:(window.ACAL_NONCE||''),
        src_id: srcId,
        tecnico_id: tecnico,
        fecha: targetDate
      }).done(function(r){
        if(r && r.success){
          window.location = window.location.pathname + '?page=acal_calendario&date=' + encodeURIComponent(targetDate);
        } else {
          alert((r && r.data && r.data.msg) || 'No se pudo pegar. Intenta nuevamente.');
        }
      }).fail(function(){ alert('Error de red al pegar. Reintenta.'); })
        .always(function(){ $btn.prop('disabled', false); });
    });
  });
})(jQuery);

/* ===== Ajustes: selector de logo standalone con Media Uploader ===== */
(function($){
  $(function(){
    var $input = $('#acal-standalone-logo');
    if(!$input.length) return;

    var $url = $('#acal-logo-url');
    var $preview = $('#acal-logo-preview');
    var frame;

    function sync(url){
      url = String(url || '').trim();
      $input.val(url);
      if(url){
        $url.text(url);
        $preview.attr('src', url).show();
      } else {
        $url.text('Sin logo seleccionado');
        $preview.attr('src', '').hide();
      }
      $input.trigger('change');
    }

    $(document).on('click', '#acal-logo-select', function(e){
      e.preventDefault();
      if(typeof wp === 'undefined' || !wp.media){
        alert('Media uploader no disponible.');
        return;
      }

      if(frame){ frame.open(); return; }

      frame = wp.media({
        title: 'Seleccionar logo standalone',
        button: { text: 'Usar este logo' },
        library: { type: 'image' },
        multiple: false
      });

      frame.on('select', function(){
        var item = frame.state().get('selection').first().toJSON();
        sync(item && item.url ? item.url : '');
      });

      frame.open();
    });

    $(document).on('click', '#acal-logo-remove', function(e){
      e.preventDefault();
      sync('');
    });
  });
})(jQuery);


/* ===== Ajustes: preview dinámica del header standalone ===== */
(function($){
  $(function(){
    var $preview = $('#acal-standalone-header-preview');
    if(!$preview.length) return;

    var $logoInput = $('#acal-standalone-logo');
    var $logoImg = $('#acal-standalone-header-preview-logo');
    var $logoPlaceholder = $preview.find('.acal-standalone-header__logo-placeholder');

    function boolValue(selector){
      return $(selector).is(':checked');
    }

    function textValue(selector, fallback){
      var value = $.trim($(selector).val() || '');
      return value ? value : fallback;
    }

    function togglePreview(selector, visible){
      $(selector, $preview).toggleClass('is-hidden', !visible);
    }

    function syncLogo(url){
      url = $.trim(String(url || ''));
      if(url){
        $logoImg.attr('src', url).show();
        $logoPlaceholder.hide();
      } else {
        $logoImg.attr('src', '').hide();
        $logoPlaceholder.show();
      }
    }

    function updatePreview(){
      $preview
        .toggleClass('is-no-border', !boolValue('#acal-header-show-border'))
        .toggleClass('is-no-shadow', !boolValue('#acal-header-show-shadow'))
        .css({
          '--acal-standalone-header-radius': Math.max(0, parseInt($('#acal-header-radius').val(), 10) || 0) + 'px',
          '--acal-standalone-header-padding': Math.max(0, parseInt($('#acal-header-padding').val(), 10) || 0) + 'px',
          '--acal-standalone-header-gap': Math.max(0, parseInt($('#acal-header-bottom-gap').val(), 10) || 0) + 'px'
        });

      $preview.find('[data-preview-role="week-label"]').text(textValue('#acal-header-week-label', 'Semana de:'));
      $preview.find('[data-preview-role="go"]').text(textValue('#acal-header-go-label', 'Ir'));
      $preview.find('[data-preview-role="prev"]').text(textValue('#acal-header-prev-label', 'Semana anterior'));
      $preview.find('[data-preview-role="next"]').text(textValue('#acal-header-next-label', 'Próxima semana'));

      togglePreview('[data-preview-role="today"]', boolValue('#acal-header-show-today'));
      togglePreview('[data-preview-section="logo"]', boolValue('#acal-header-show-logo'));
      togglePreview('[data-preview-section="updated"]', boolValue('#acal-header-show-updated'));

      syncLogo($logoInput.val());
    }

    $(document).on('input change', [
      '#acal-header-show-logo',
      '#acal-header-show-updated',
      '#acal-header-show-today',
      '#acal-header-week-label',
      '#acal-header-go-label',
      '#acal-header-prev-label',
      '#acal-header-next-label',
      '#acal-header-radius',
      '#acal-header-show-border',
      '#acal-header-show-shadow',
      '#acal-header-padding',
      '#acal-header-bottom-gap',
      '#acal-standalone-logo'
    ].join(','), updatePreview);

    updatePreview();
  });
})(jQuery);
