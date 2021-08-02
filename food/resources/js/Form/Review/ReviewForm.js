Drupal.behaviors.reviewform = {
  attach: function (context, settings) {

    var point = jQuery('#rating_star').attr('point');

    if(typeof point == 'undefined' || point == ''){
        point = '';
    }

    jQuery("#rating_star").once().codexworld_rating_widget({
        starLength: '5',
        initialValue: point,
        callbackFunctionName: 'processRating',
        imageDirectory: '/modules/food/resources/images/',
        inputAttr: 'orderID'
    });
  }
};

function processRating(val, attrVal){
    jQuery('#rating_point').val(val);
    jQuery.ajax({
        type: 'POST',
        url: '/rating/data',
        data: { orderID: attrVal, ratingPoints: val },
        success : function(data) {
            console.log(data);
            if (data.status == 'ok') {
                jQuery('#avgrat').text(data.average_rating);
                jQuery('#totalrat').text(data.rating_number);
            }else{
                //alert('Some problem occured, please try again.');
            }
        }
    });
}