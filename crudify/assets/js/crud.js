jQuery.fn.slugify = function(obj) {
  jQuery(this).data('obj', jQuery(obj));
  jQuery(this).keyup(function() {
    var obj = jQuery(this).data('obj');
    var slug = jQuery(this).val().replace(/\s+/g,'-').replace(/[^a-zA-Z0-9\-]/g,'');
    obj.val(slug);
  });
}

jQuery.fn.autoWidth = function(options) {
  var settings = {
    limitWidth : false,
    minWidth : 120
  }

  if(options) {
    jQuery.extend(settings, options);
  };

  var maxWidth = 0;

  this.each(function(){
    if ($(this).width() > maxWidth){
      if(settings.limitWidth && maxWidth >= settings.limitWidth) {
        maxWidth = settings.limitWidth;
      } else {
        maxWidth = $(this).width();
      }
    }
  });

  if(settings.minWidth && maxWidth < settings.minWidth) {
    maxWidth = settings.minWidth;
  }

  this.width(maxWidth);
}
