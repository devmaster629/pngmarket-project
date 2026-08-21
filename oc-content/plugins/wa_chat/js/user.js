$(document).ready(function() {
  $('body').on('click', 'a.wac-btn.wac-item', function(e) { 
    var elem = $(this);
    
    if(elem.attr('href') == '#') {
      return false;
    } else if(elem.attr('data-stats') == 1) {
      $.ajax({
        timeout: 5000,
        url: elem.attr('data-url'),
        type: "GET",
        data: { 
          itemId: $(this).attr('data-item-id')
        },
        success: function(response){
          //console.log('Stats collected');
        },
        error: function(response){
          //console.log(response);
        }
      });
    }
  }); 
});