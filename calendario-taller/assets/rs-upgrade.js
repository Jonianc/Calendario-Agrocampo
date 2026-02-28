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
    setTimeout(function(){ ensureTurnoControls(); setTurno('am'); toggleDelete(false); }, 0);
  });
  $(document).off('click.acalEditTurno').on('click.acalEditTurno', '.acal-edit', function(){
    var task = $(this).data('task') || {};
    setTimeout(function(){
      ensureTurnoControls();
      setTurno(task.turno || 'am');
      toggleDelete(true);
    }, 0);
  });
  if($form.is(':visible')){ setTurno($('#acal-turno-hidden').val() || 'am'); }

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

function enableModalA11y(){
  var $modal = $('#acal-modal');
  if(!$modal.length) return;

  var lastFocused = null;

  function openA11y(){
    lastFocused = document.activeElement;
    $modal.attr('aria-hidden', 'false');
    var $focusables = getFocusableElements($modal);
    if($focusables.length){ $focusables.first().trigger('focus'); }
  }

  function closeA11y(){
    $modal.attr('aria-hidden', 'true');
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

}


  $(document).ready(init);
})(jQuery);
