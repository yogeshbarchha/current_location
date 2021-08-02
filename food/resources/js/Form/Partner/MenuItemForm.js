(function(jQuery) {
    jQuery(document).ready(function() {
        var body = jQuery('body');
        body.on('click', '#size_add_button', function() {
            var div_html = `<tr class='size_row'>
			    	<td><input type="text" class="form-control size_name" ></td>
			    	<td><input type="number" max="999999" min="0" step=".001" class="form-control size_price" ></td>
			    	<td><input type="radio" class="radio size_default" value="1" name="sizeDefault" ></td>
			    	<td><span class='glyphicon glyphicon-trash remove-size'></span></td>
			    </tr>`;

            jQuery("#size_table").append(div_html);
        });

        body.on('click', '.remove-size', function() {
            var res = confirm('Are you sure you want to remove this size?');
            if (res) {
                jQuery(this).parents('.size_row').remove();
            }
        });


        body.on('click', '#button_submit', function() {
			var sizes = [],
				categories = [];
				
            //get sizes  
            jQuery('.size_name').each(function(index) {
                var sizeName = jQuery('.size_name:eq(' + index + ')').val();
                var sizePrice = jQuery('.size_price:eq(' + index + ')').val();
                var sizeDefault = jQuery('.size_default:eq(' + index + ')');
				
                if (jQuery.trim(sizeName) != "") {
					sizes.push({
						name: sizeName,
						price: sizePrice,
						is_default: sizeDefault.is(':checked')
					});
                }
            });

            //get category & item
            jQuery(".item_cat_table").each(function(i) {
                var categoryName = jQuery('.category_name:eq(' + i + ')').val();
                var categoryDisplayType = jQuery('.display_type:eq(' + i + ')').val();
                var required = jQuery('.category_required:eq(' + i + ')').is(':checked');
                if (jQuery.trim(categoryName) == "") {
					return;
                }

				var category = {
					name: categoryName,
					display_type: categoryDisplayType,
					required: required,
					options: []
				};
				categories.push(category);

                jQuery(this).find('.category_option_name').each(function(itemR) {
                    var optionName = jQuery('.item_cat_table:eq(' + i + ')').find('.category_option_name:eq(' + itemR + ')').val();
                    var optionPrice = jQuery('.item_cat_table:eq(' + i + ')').find('.category_option_price:eq(' + itemR + ')').val();
                    var optionIsPricePct = jQuery('.item_cat_table:eq(' + i + ')').find('.category_option_is_price_pct:eq(' + itemR + ')');
                    var optionIsDefault = jQuery('.item_cat_table:eq(' + i + ')').find('.category_option_default:eq(' + itemR + ')');

                    if (jQuery.trim(optionName) == "") {
						return;
                    }

					category.options.push({
						name: optionName,
						price: optionPrice,
						is_price_pct: optionIsPricePct.is(':checked'),
						is_default: optionIsDefault.is(':checked'),
					});
                });
            });

			jQuery("#variations").val(JSON.stringify({
				sizes: sizes,
				categories: categories
			}));
			console.log(jQuery("#variations").val());
        });

        body.on('click', '#variation_add_category_button', function() {
            var ocHtml = `<table class="table item_cat_table">
	                       <tr>
	                         <td><input type="text" class="form-control category_name" placeholder="Category Name"></td>
	                         <td>
	                           <select class="form-control display_type">
	                              <option value='checkbox'>Checkbox (Choose several)</option>
	                              <option value='dropdown'>Dropdown (Choose one)</option>
	                              <option value='radio'>Radio (Choose one)</option>
	                            </select>
	                         </td>
	                         <td>
	                            <a href="javascript:;" class='btn btn-info category_option_add_button'>Add Option</a>
	                         </td>
	                         <td>
								<div class="form-item js-form-item form-type-checkbox js-form-type-checkbox checkbox">
									<label class="control-label option">
										<input class="form-checkbox category_required" type="checkbox">
										Required
									</label>
								</div>							 
								<span class="glyphicon glyphicon-trash variation_remove_category_button"></span>
							</td>
	                       </tr>
                       </table>`

            jQuery(".container_categories").append(ocHtml);
        });

        body.on('click', '.variation_remove_category_button', function() {
            var res = confirm('Are you sure you want to remove this category?');
            if (res) {
                jQuery(this).parents('.item_cat_table').remove();
            }
        });

        body.on('click', '.category_option_add_button', function() {
            var itemRow = `<tr class="category_option_row">
                              <td><input type="text" class="form-control category_option_name" placeholder="Item Name"/></td>
                              <td><input type="number" max="999999" min="0" step=".001" class="form-control category_option_price" placeholder="Item Price"/></td>
							  <td>
								 <label class="control-label option">
									<input class="form-checkbox category_option_is_price_pct" type="checkbox" value="1" />
									Price in %
								 </label>
							  </td>
                              <td>
                                 <label class="radio-inline">
                                  <input type="radio" class="category_option_default" value="1"/>&nbsp;Default
                                 </label> 
                               </td>
                               <td>
                                  <span class="glyphicon glyphicon-trash category_option_remove_button"></span>
                                </td>  
                           </tr>`;
            jQuery(this).parents('.item_cat_table').append(itemRow);
        });

        body.on('click', '.category_option_remove_button', function() {
            var res = confirm('Are you sure you want to remove this option?');
            if (res) {
                jQuery(this).parents('.category_option_row').remove();
            }
        });

        body.on('click', '.size_default', function() {
            jQuery('.size_default').prop('checked', false);
            jQuery(this).prop('checked', true);
        });

        body.on('click', '.category_option_default', function() {
            jQuery(this).parents('.item_cat_table').find('.category_option_default').prop('checked', false);
            jQuery(this).prop('checked', true);
        });

    });
})(jQuery);
