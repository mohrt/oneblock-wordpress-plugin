jQuery(function() {
  jQuery('#oneblock_qrcode').hide();
  jQuery('#oneblock_link').click(function(event) {
    event.preventDefault(); 
    jQuery('#oneblock_qrcode').toggle();
    if(jQuery('#oneblock_qrcode').is(':visible')) {
      // generate challenge URL
      var data = {
        'action': 'oneblock_getchallenge',
      };
      jQuery.post(ajaxurl, data, function(response) {
        //alert('Got this from the server: ' + response);
        jQuery('#oneblock_qrcode').html('');
        new QRCode(document.getElementById('oneblock_qrcode'), response);
      });
    }
  });
});
