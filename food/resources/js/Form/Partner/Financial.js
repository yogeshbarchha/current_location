jQuery(document).ready(function() {
    function refreshReport() {               
        var params = {
            restaurant_id: jQuery('#edit-restaurant-id').val(),
            start_date: jQuery('#edit-start-date').val(),
            end_date: jQuery('#edit-end-date').val(),
        };

        Drupal.ajax({
            url: drupalSettings.food.statementCallbackUrl + '?' + jQuery.param(params),
        }).execute();
    }

    jQuery('#edit-submit').click(function() {
        refreshReport();
        return (false);
    });
	
    jQuery('#edit-duration').on('change',function(){
        var start_date,
			end_date;
		
        var duartionVal = jQuery('#edit-duration').val();        
        switch (duartionVal) {
            case '':
                start_date = '';
                end_date = '';
                break;
				
            case 'currentWeek':
                var now = new Date(); // get current date
                var first = now.getDate() - ((now.getDay() + 6) % 7); // First day is the day of the month - the day of the week
                start_date = new Date(now.setDate(first));
                end_date = new Date();
                break;
				
            case 'lastWeek':
                var beforeOneWeek = new Date(new Date().getTime() - 7 * 24 * 60 * 60 * 1000)
                var beforeOneWeek2 = new Date(beforeOneWeek);
                day = beforeOneWeek.getDay()
                diffToMonday = beforeOneWeek.getDate() - day + (day === 0 ? -6 : 1)
                start_date = new Date(beforeOneWeek.setDate(diffToMonday));
                end_date = new Date(beforeOneWeek2.setDate(diffToMonday + 6));
                break;
				
            case 'currentMonth':
                var now = new Date();
                start_date = new Date(now.getFullYear(), now.getMonth(), 1);
                end_date = now;
                break;
				
            case 'lastMonth':
                var now = new Date();
                start_date = new Date(now.getFullYear(), (now.getMonth() - 1 + 12) % 12, 1);
                end_date = new Date(now.getFullYear(), now.getMonth(), 0);
                break;
				
            case 'currentYear':
                start_date = new Date(new Date().getFullYear(), 0, 1);
                end_date = new Date();
                break;
				
            case 'lastYear':
                start_date = new Date(new Date().getFullYear() - 1, 0, 1);
                end_date = new Date(new Date().getFullYear() - 1, 11, 31);
                break;
				
            case 'custom':
                start_date = jQuery('#edit-start-date').val();
                end_date = jQuery('#edit-end-date').val();
                break;
				
            default :
				break;
        }

		if(Object.prototype.toString.call(start_date) === '[object Date]'){
			start_date = start_date.getFullYear() + '-' + String.padLeft(start_date.getMonth() + 1, 2, '0') + '-' + String.padLeft(start_date.getDate(), 2, '0');
			end_date = end_date.getFullYear() + '-' + String.padLeft(end_date.getMonth() + 1, 2, '0') + '-' + String.padLeft(end_date.getDate(), 2, '0');
		}
		
		jQuery('#edit-start-date').val(start_date);
		jQuery('#edit-end-date').val(end_date);
		
        refreshReport(); 
        return (false);
    });
	
    refreshReport();
    
});
