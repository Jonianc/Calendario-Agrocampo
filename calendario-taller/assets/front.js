(function($){
  $(function(){
    $('.acal-frontend').each(function(){
      var mins = parseInt($(this).data('refresh'),10);
      if(mins && mins>0){ setInterval(function(){ location.reload(); }, mins*60*1000); }
    });
  });
})(jQuery);
