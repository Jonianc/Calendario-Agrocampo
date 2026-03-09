(function($){
function patchFilters(){
  var $form = $('.wrap.acal-wrap form.acal-topbar');
  if(!$form.length){ $form = $('form.acal-topbar'); }

  // Reemplaza "Sucursal" (select) por "Lugar" (input texto) manteniendo name=f_sucursal
  var $sel = $form.find('select[name="f_sucursal"]');
  if($sel.length && !$form.find('#acal-f-lugar').length){
    var $label = $sel.closest('label');
    // Cambiar texto del label
    var nodes = $label.get(0).childNodes;
    if(nodes && nodes.length){
      for(var i=0;i<nodes.length;i++){
        if(nodes[i].nodeType === 3 && /Sucursal/i.test(nodes[i].textContent)){
          nodes[i].textContent = nodes[i].textContent.replace(/Sucursal/i, 'Lugar');
          break;
        }
      }
    }
    // Input sin placeholder + hidden para preservar f_sucursal
    var initVal = $sel.val() || '';
    var $input = $('<input type="text" id="acal-f-lugar" />').val(initVal);
    var $hidden = $('<input type="hidden" name="f_sucursal" id="acal-f-sucursal-hidden" />').val(initVal);
    $sel.removeAttr('name').hide();
    $label.append(' ').append($input).append($hidden);
    $form.on('submit', function(){
      $('#acal-f-sucursal-hidden').val($('#acal-f-lugar').val());
    });
  } else {
    // Si ya existe, asegúrate de que no tenga placeholder
    $form.find('#acal-f-lugar').removeAttr('placeholder');
  }

  // Oculta filtro "Estado"
  var $estado = $form.find('select[name="f_estado"]');
  if($estado.length){ $estado.closest('label').hide(); }
}

function enableTechOrder(){
  // Solo en admin y si tenemos ajax configurado
  if (jQuery('.acal-frontend').length) return;
  if (typeof ACAL_ORDER === 'undefined') return;

  // Arrastrable en la 1ª columna
  $(document).on('dragstart', '.acal-tech-item[draggable="true"]', function(e){
    e.originalEvent.dataTransfer.effectAllowed = 'move';
    e.originalEvent.dataTransfer.setData('text/plain', $(this).data('tecnico-id'));
    $(this).addClass('tech-dragging');
  });
  $(document).on('dragend', '.acal-tech-item[draggable="true"]', function(){
    $(this).removeClass('tech-dragging');
    $('.acal-tech-item.drop-here').removeClass('drop-here');
  });

  // Permitir soltar sobre otras celdas de técnico
  $(document).on('dragover dragenter', '.acal-tech-item[draggable="true"]', function(e){
    e.preventDefault(); $(this).addClass('drop-here');
  });
  $(document).on('dragleave', '.acal-tech-item[draggable="true"]', function(){
    $(this).removeClass('drop-here');
  });

  // Al soltar, reordenamos DOM, guardamos y recargamos
  $(document).on('drop', '.acal-tech-item[draggable="true"]', function(e){
    e.preventDefault();
    var target = $(this);
    var draggedId = e.originalEvent.dataTransfer.getData('text/plain');
    if(!draggedId) return;

    var draggedEl = $('.acal-tech-item[draggable="true"][data-tecnico-id="'+draggedId+'"]');
    if(!draggedEl.length || draggedEl.is(target)) return;

    // Reordenar visualmente (mover antes o después según posición)
    var targetTop  = target.offset().top;
    var draggedTop = draggedEl.offset().top;
    if(draggedTop < targetTop){
      target.after(draggedEl);
    } else {
      target.before(draggedEl);
    }
    $('.acal-tech-item.drop-here').removeClass('drop-here');

    // Tomar el orden resultante (solo 1ª columna)
    var order = $('.acal-tech-item').map(function(){ return $(this).data('tecnico-id'); }).get();

    // Guardar vía AJAX
    $.post(ACAL_ORDER.ajax, {
      action: 'acal_save_tecnicos_order',
      nonce: ACAL_ORDER.nonce,
      order: order
    }).done(function(resp){
      if(!(resp && resp.success)){
        alert('No se pudo guardar el orden');
      }else{
        // Recarga rápida para que la grilla completa respete el orden
        location.reload();
      }
    }).fail(function(){
      alert('Error de red guardando el orden');
    });
  });
}


function patchModal(){
  var $form = $('#acal-form');
  if(!$form.length) return;
  var isFrontManagement = $('body').hasClass('acal-front-management');

  function syncRedirectToCurrentUrl(){
    var $redirect = $form.find('#acal-redirect-to');
    if($redirect.length){
      $redirect.val((window.location.href || '').split('#')[0]);
    }
  }

  // ----- Oculta "Estado" y fija "programado"
  var $est = $form.find('#acal-estado');
  if($est.length){ $est.val('programado'); $est.closest('label').hide(); }

  // ----- "Sucursal" visual -> "Lugar" (manteniendo name="sucursal")
  var $sel = $form.find('#acal-sucursal');
  if($sel.length && !$form.find('#acal-lugar').length){
    var $label = $sel.closest('label');
    var nodes = $label.get(0).childNodes;
    if(nodes && nodes.length){
      for(var i=0;i<nodes.length;i++){
        if(nodes[i].nodeType === 3 && /Sucursal/i.test(nodes[i].textContent)){
          nodes[i].textContent = nodes[i].textContent.replace(/Sucursal/i, 'Lugar');
          break;
        }
      }
    }
    var initVal = $sel.val() || '';
    var $input = $('<input type="text" id="acal-lugar" />').val(initVal);
    $sel.hide().removeAttr('name').attr('data-original-name','sucursal');
    $label.append('<br/>').append($input);

    $form.on('submit', function(){
      var val = $('#acal-lugar').val() || '';
      var $hidden = $form.find('input[name="sucursal"]');
      if(!$hidden.length){ $hidden = $('<input type="hidden" name="sucursal" />').appendTo($form); }
      $hidden.val(val);
    });
  } else {
    $form.find('#acal-lugar').removeAttr('placeholder');
  }

  // ---------- Turno AM/PM (radios + hidden) ----------
  function ensureTurnoControls(){
    if(!$form.find('#acal-turno-group').length){
      var $fechaLabel = $form.find('#acal-fecha').closest('label');
      var $grp = $('<fieldset id="acal-turno-group" class="acf-field"><legend>Turno</legend></fieldset>');
      var $am = $('<label style="margin-right:10px"><input type="radio" name="acal_turno_ui" id="acal-turno-am" value="am"> AM</label>');
      var $pm = $('<label><input type="radio" name="acal_turno_ui" id="acal-turno-pm" value="pm"> PM</label>');
      $grp.append($am).append($pm);
      $grp.insertAfter($fechaLabel);
    }
    if(!$form.find('input[name="turno"]').length){
      $('<input type="hidden" name="turno" id="acal-turno-hidden">').appendTo($form);
    }
  }
  function setTurno(v){
    v = (v||'').toLowerCase();
    if(v!=='am' && v!=='pm') v='am'; // default AM
    $('#acal-turno-am').prop('checked', v==='am');
    $('#acal-turno-pm').prop('checked', v==='pm');
    $('#acal-turno-hidden').val(v);
  }
  ensureTurnoControls();

  $form.off('change.acalTurno').on('change.acalTurno', 'input[name="acal_turno_ui"]', function(){
    $('#acal-turno-hidden').val($(this).val());
  });
  $form.off('submit.acalTurno').on('submit.acalTurno', function(){
    var v = $('input[name="acal_turno_ui"]:checked').val() || 'am';
    $('#acal-turno-hidden').val(v);
  });
  $(document).off('click.acalAddTurno').on('click.acalAddTurno', '.acal-add', function(){
    setTimeout(function(){ ensureTurnoControls(); setTurno('am'); toggleDelete(false); syncRedirectToCurrentUrl(); }, 0);
  });
  $(document).off('click.acalEditTurno').on('click.acalEditTurno', '.acal-edit', function(){
    var task = $(this).data('task') || {};
    setTimeout(function(){
      ensureTurnoControls();
      setTurno(task.turno || 'am');
      toggleDelete(true);
      syncRedirectToCurrentUrl();
    }, 0);
  });
  if($form.is(':visible')){ setTurno($('#acal-turno-hidden').val() || 'am'); }

  if(isFrontManagement){
    if(!$form.find('.acal-form-priority').length){
      var $priority = $('<div class="acal-form-priority" />');
      var $details  = $('<div class="acal-form-details" />');

      $form.find('#acal-tecnico-id').closest('label').addClass('acal-field-tecnico').appendTo($priority);
      $form.find('#acal-fecha').closest('label').addClass('acal-field-fecha').appendTo($priority);
      $form.find('#acal-turno-group').appendTo($priority);

      $form.find('#acal-cliente').closest('label').addClass('acal-field-cliente').appendTo($details);
      $form.find('#acal-equipo').closest('label').addClass('acal-field-equipo').appendTo($details);
      $form.find('#acal-lugar').closest('label').addClass('acal-field-lugar').appendTo($details);
      $form.find('#acal-descripcion').closest('label').addClass('acal-field-descripcion').appendTo($details);

      $('<h3 class="acal-form-block-title">Datos clave</h3>').prependTo($priority);
      $('<p class="acal-form-block-help">Completa estos campos primero para programar rápidamente.</p>').insertAfter($priority.find('.acal-form-block-title'));
      $('<h3 class="acal-form-block-title">Detalle de la tarea</h3>').prependTo($details);

      $priority.insertBefore($form.find('.acal-form-actions'));
      $details.insertBefore($form.find('.acal-form-actions'));
    }

    if(!$form.find('#acal-form-live').length){
      $('<div id="acal-form-live" class="acal-form-live" aria-live="polite" aria-atomic="true"></div>').insertBefore($form.find('.acal-form-actions'));
    }

    if(!$form.find('#acal-fecha-help').length){
      $('<small id="acal-fecha-help" class="acal-help">Usa una fecha válida en la semana que estás planificando.</small>').insertAfter($form.find('#acal-fecha'));
      $form.find('#acal-fecha').attr('aria-describedby', 'acal-fecha-help');
    }
    if(!$form.find('#acal-descripcion-help').length){
      $('<small id="acal-descripcion-help" class="acal-help">Máximo recomendado: 280 caracteres para mantener tarjetas legibles.</small>').insertAfter($form.find('#acal-descripcion'));
      $form.find('#acal-descripcion').attr('aria-describedby', 'acal-descripcion-help');
    }

    function setFieldError(selector, message){
      var $field = $form.find(selector);
      if(!$field.length) return;
      var id = $field.attr('id');
      var errId = id + '-error';
      $field.attr('aria-invalid', 'true').addClass('acal-invalid');
      if(!$form.find('#'+errId).length){
        $('<span class="acal-error" id="'+errId+'"></span>').insertAfter($field);
      }
      $form.find('#'+errId).text(message);
      var desc = ($field.attr('aria-describedby') || '').split(' ').filter(Boolean);
      if(desc.indexOf(errId) === -1){
        desc.push(errId);
        $field.attr('aria-describedby', desc.join(' '));
      }
    }

    function clearFieldError(selector){
      var $field = $form.find(selector);
      if(!$field.length) return;
      var id = $field.attr('id');
      var errId = id + '-error';
      $field.removeAttr('aria-invalid').removeClass('acal-invalid');
      $form.find('#'+errId).remove();
      var desc = ($field.attr('aria-describedby') || '').split(' ').filter(function(v){ return v && v !== errId; });
      if(desc.length){ $field.attr('aria-describedby', desc.join(' ')); }
      else { $field.removeAttr('aria-describedby'); }
      if(id === 'acal-fecha' && !$form.find('#acal-fecha-help').length){
        $('<small id="acal-fecha-help" class="acal-help">Usa una fecha válida en la semana que estás planificando.</small>').insertAfter($field);
        $field.attr('aria-describedby','acal-fecha-help');
      }
      if(id === 'acal-descripcion' && !$form.find('#acal-descripcion-help').length){
        $('<small id="acal-descripcion-help" class="acal-help">Máximo recomendado: 280 caracteres para mantener tarjetas legibles.</small>').insertAfter($field);
        $field.attr('aria-describedby','acal-descripcion-help');
      }
    }

    function isValidDate(v){
      return /^\d{4}-\d{2}-\d{2}$/.test(v || '');
    }

    function validateForm(){
      var errors = [];
      var tecnico = ($form.find('#acal-tecnico-id').val() || '').trim();
      var fecha = ($form.find('#acal-fecha').val() || '').trim();
      var desc = ($form.find('#acal-descripcion').val() || '').trim();

      clearFieldError('#acal-tecnico-id');
      clearFieldError('#acal-fecha');
      clearFieldError('#acal-descripcion');

      if(!tecnico){ errors.push({selector:'#acal-tecnico-id', message:'Selecciona un técnico para continuar.'}); }
      if(!fecha || !isValidDate(fecha)){ errors.push({selector:'#acal-fecha', message:'Ingresa una fecha válida (AAAA-MM-DD).'}); }
      if(desc.length > 280){ errors.push({selector:'#acal-descripcion', message:'La descripción no debe superar 280 caracteres.'}); }

      if(errors.length){
        errors.forEach(function(err){ setFieldError(err.selector, err.message); });
        $form.find('#acal-form-live').text('Hay ' + errors.length + ' campo(s) por corregir antes de guardar.');
        $form.find(errors[0].selector).trigger('focus');
        return false;
      }

      $form.find('#acal-form-live').text('Formulario listo para guardar.');
      return true;
    }

    $form.off('input.acalValidation change.acalValidation')
      .on('input.acalValidation change.acalValidation', '#acal-tecnico-id, #acal-fecha, #acal-descripcion', function(){
        clearFieldError('#'+this.id);
      });

    $form.off('submit.acalValidation').on('submit.acalValidation', function(e){
      if(!validateForm()) e.preventDefault();
    });
  }

  // ---------- NUEVO: Botón "Eliminar" (solo en editar) ----------
  function ensureDeleteButton(){
    if(!$form.find('.acal-btn-delete').length){
      var $btnDel = $('<button type="button" class="button button-link-delete acal-btn-delete" style="margin-left:8px">Eliminar</button>');
      $form.find('.acal-form-actions').append($btnDel);
    }
  }
  function toggleDelete(show){
    ensureDeleteButton();
    $form.find('.acal-btn-delete').toggle(!!show);
  }
  // handler de click (una sola vez con namespace)
  $(document).off('click.acalDelete').on('click.acalDelete', '.acal-btn-delete', function(){
    var taskId = $('#acal-task-id').val();
    if(!taskId){ alert('No hay tarea seleccionada.'); return; }
    if(!confirm('¿Eliminar esta tarea?')) return;

    var actionUrl = $form.attr('action') || 'admin-post.php';
    var delForm = $('<form>', {method:'post', action:actionUrl}).appendTo('body');
    delForm.append($('<input>', {type:'hidden', name:'action', value:'acal_delete_task'}));
    delForm.append($('<input>', {type:'hidden', name:'task_id', value:taskId}));
    delForm.append($('<input>', {type:'hidden', name:'redirect_to', value:(window.location.href || '').split('#')[0]}));
    var nonce = $form.find('input[name="_wpnonce"]').val() || '';
    delForm.append($('<input>', {type:'hidden', name:'_wpnonce', value:nonce}));
    delForm.trigger('submit');
  });

  // Al abrir el modal manualmente (por si quedó abierto)
  toggleDelete( !!$('#acal-task-id').val() );

  // Limpieza de placeholders
  $form.find('#acal-descripcion').removeAttr('placeholder');
}


    function openEditFromKebab(){
  // Al hacer click en ⋮, abrimos directamente el modal de editar
  $(document).on('click', '.acal-kebab', function(e){
    e.preventDefault();
    var $menu = $(this).siblings('.acal-menu');
    var $edit = $menu.find('.acal-edit');
    if($edit.length){
      $edit.trigger('click'); // delega al handler de edición existente (admin.js)
    }
  });

  // Cada vez que se abre "Editar", asegúrate de que exista el botón Eliminar
  $(document).on('click', '.acal-edit', function(){
    setTimeout(function(){ patchModal(); }, 0);
  });
}

function enableDragDrop(){
  // Solo en admin y con config válida
  if (jQuery('.acal-frontend').length) return;
  if (typeof ACAL_MOVE === 'undefined') return;

  // 1) Marcar tarjetas como arrastrables
  jQuery('.acal-task').attr('draggable', true);
  jQuery(document).on('mouseenter', '.acal-task', function(){
    if (!this.hasAttribute('draggable')) this.setAttribute('draggable', 'true');
  });

  // 2) Inicio / fin de drag
  jQuery(document).on('dragstart', '.acal-task', function(e){
    // evitar drag desde botones/menús de la tarjeta
    if (jQuery(e.originalEvent.target).closest('button,a,.acal-kebab,.acal-menu').length){
      e.preventDefault(); return false;
    }
    e.originalEvent.dataTransfer.effectAllowed = 'move';
    e.originalEvent.dataTransfer.setData('text/plain', jQuery(this).data('task-id'));
    jQuery(this).addClass('is-dragging');
  });

  jQuery(document).on('dragend', '.acal-task', function(){
    jQuery(this).removeClass('is-dragging');
    jQuery('.acal-cell.drop-ok').removeClass('drop-ok');
  });

  // 3) Habilitar drop en celdas de calendario (las que tienen .acal-add)
  jQuery('.acal-grid .acal-cell').each(function(){
    var $cell = jQuery(this);
    var $add  = $cell.find('.acal-add');
    if(!$add.length) return; // cabecera/columna técnico no son drop targets

    this.addEventListener('dragenter', function(ev){ ev.preventDefault(); $cell.addClass('drop-ok'); });
    this.addEventListener('dragover',  function(ev){ ev.preventDefault(); ev.dataTransfer.dropEffect = 'move'; });
    this.addEventListener('dragleave', function(){ $cell.removeClass('drop-ok'); });
    this.addEventListener('drop',      function(ev){
      ev.preventDefault();
      $cell.removeClass('drop-ok');

      var taskId = ev.dataTransfer.getData('text/plain');
      if(!taskId) return;

      var tecnicoId = $add.data('tecnico');
      var fecha     = $add.data('date');

      jQuery.post(ACAL_MOVE.ajax, {
        action: 'acal_move_task',
        nonce:  ACAL_MOVE.nonce,
        task_id: taskId,
        tecnico_id: tecnicoId,
        fecha: fecha
      }).done(function(resp){
        if(resp && resp.success){
          // mover en el DOM
          var $task = jQuery('.acal-task[data-task-id="'+taskId+'"]');
          if($task.length){ $task.appendTo($cell); }
        }else{
          alert((resp && resp.data && resp.data.msg) ? resp.data.msg : 'No se pudo mover la tarea.');
        }
      }).fail(function(){
        alert('Error de red moviendo tarea.');
      });
    });
  });
}

function enableTechArrows(){
  // Solo en admin y si tenemos config AJAX
  if (jQuery('.acal-frontend').length) return;
  if (typeof ACAL_ORDER === 'undefined') return;

  function collectOrder(){
    return jQuery('.acal-tech-col.acal-tech-item').map(function(){
      return jQuery(this).data('tecnico-id');
    }).get();
  }

  function saveOrder(){
    var order = collectOrder();
    jQuery.post(ACAL_ORDER.ajax, {
      action: 'acal_save_tecnicos_order',
      nonce:  ACAL_ORDER.nonce,
      order:  order
    }).done(function(resp){
      if(!(resp && resp.success)){ alert('No se pudo guardar el orden'); }
      else { location.reload(); }
    }).fail(function(){ alert('Error de red guardando el orden'); });
  }

  // Subir
  jQuery(document).on('click', '.acal-move-up', function(){
    var $row  = jQuery(this).closest('.acal-tech-item');
    var $prev = $row.prevAll('.acal-tech-item').first();
    if($prev.length){ $prev.before($row); saveOrder(); }
  });

  // Bajar
  jQuery(document).on('click', '.acal-move-down', function(){
    var $row  = jQuery(this).closest('.acal-tech-item');
    var $next = $row.nextAll('.acal-tech-item').first();
    if($next.length){ $next.after($row); saveOrder(); }
  });
}




function getFocusableElements($container){
  return $container.find('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])').filter(':visible');
}

function syncKebabA11y(){
  $('.acal-kebab').each(function(){
    var $btn = $(this);
    var menuId = $btn.attr('aria-controls');
    if(!menuId) return;
    var $menu = $('#'+menuId);
    if(!$menu.length) return;
    var expanded = $menu.is(':visible');
    $btn.attr('aria-expanded', expanded ? 'true' : 'false');
  });
}

function enhanceTopbarQuickActions(){
  var $topbar = $('.acal-topbar');
  if(!$topbar.length) return;

  var $quick = $topbar.find('.acal-quick-actions');
  if(!$quick.length) return;

  var params = new URLSearchParams(window.location.search || '');
  var selectedDate = params.get('date') || '';

  $quick.find('a.button').each(function(){
    var href = $(this).attr('href') || '';
    if(!href) return;
    try {
      var url = new URL(href, window.location.origin);
      var actionDate = url.searchParams.get('date') || '';
      if(actionDate && selectedDate && actionDate === selectedDate){
        $(this).addClass('is-active').attr('aria-current', 'page');
      }
    } catch(e){ }
  });

  var $clear = $quick.find('a.button').filter(function(){
    return /Limpiar filtros/i.test($(this).text());
  }).first();

  if($clear.length){
    $clear.on('click', function(){
      if($('#acal-filter-feedback').length) return;
      $('<span id="acal-filter-feedback" class="acal-help" aria-live="polite">Limpiando filtros…</span>').insertAfter($quick);
    });
  }
}

function enableModalA11y(){
  var $modal = $('#acal-modal');
  if(!$modal.length) return;

  var lastFocused = null;

  function openA11y(){
    lastFocused = document.activeElement;
    $modal.attr('aria-hidden', 'false');
    $('body').addClass('acal-modal-open');
    $('html').addClass('acal-modal-open');
    var $focusables = getFocusableElements($modal);
    if($focusables.length){ $focusables.first().trigger('focus'); }
  }

  function closeA11y(){
    $modal.attr('aria-hidden', 'true');
    $('body').removeClass('acal-modal-open');
    $('html').removeClass('acal-modal-open');
    if(lastFocused && typeof lastFocused.focus === 'function'){ lastFocused.focus(); }
  }

  $(document).off('keydown.acalModalTrap').on('keydown.acalModalTrap', function(e){
    if(!$modal.is(':visible')) return;

    if(e.key === 'Escape'){
      e.preventDefault();
      $modal.find('.acal-modal-close').first().trigger('click');
      return;
    }

    if(e.key !== 'Tab') return;

    var $focusables = getFocusableElements($modal);
    if(!$focusables.length) return;

    var first = $focusables.get(0);
    var last  = $focusables.get($focusables.length - 1);

    if(e.shiftKey && document.activeElement === first){
      e.preventDefault();
      last.focus();
    } else if(!e.shiftKey && document.activeElement === last){
      e.preventDefault();
      first.focus();
    }
  });

  $(document).off('click.acalModalA11yOpen').on('click.acalModalA11yOpen', '.acal-add, .acal-edit, .acal-kebab', function(){
    setTimeout(function(){
      if($modal.is(':visible')) openA11y();
      syncKebabA11y();
    }, 0);
  });

  $(document).off('click.acalModalA11yClose').on('click.acalModalA11yClose', '.acal-modal-close, #acal-modal', function(e){
    if($(e.target).is('#acal-modal, .acal-modal-close')){
      setTimeout(function(){
        if(!$modal.is(':visible')) closeA11y();
      }, 0);
    }
  });

  $(document).off('click.acalKebabA11y').on('click.acalKebabA11y', '.acal-kebab', function(){
    setTimeout(syncKebabA11y, 0);
  });
  $(document).off('click.acalKebabA11yDoc').on('click.acalKebabA11yDoc', function(){
    setTimeout(syncKebabA11y, 0);
  });

  if(!$modal.is(':visible')) closeA11y();
}

function init(){
  patchFilters();
  patchModal();
  openEditFromKebab();

  // Delegado de +Agregar (lo mantienes)
  $(document).on('click', '.acal-add', function(e){
    $(document).trigger('acal:addClick', [ $(this).data() ]);
  });

  // NUEVO: activar drag & drop
  enableDragDrop();
  //enableTechOrder();
  enableTechArrows();
  enableModalA11y();
  syncKebabA11y();
  enhanceTopbarQuickActions();

}


  $(document).ready(init);
})(jQuery);
