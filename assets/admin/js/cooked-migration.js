(function( $ ) {

    $(document).ready(function() {

	    var $_CookedMigrationButton 		= $('#cooked-migration-button'),
	    	$_CookedMigrationProgress 		= $('#cooked-migration-progress'),
	    	$_CookedMigrationProgressText 	= $('#cooked-migration-progress-text');

	    // Migration Button Exists?
	    if ($_CookedMigrationButton.length){

	    	$_CookedMigrationButton.on('click',function(e){

            	e.preventDefault();

            	var thisButton = $(this),
            		confirm_migrate = confirm( cooked_js_vars.i18n_confirm_migrate_recipes );

            	if ( confirm_migrate && !thisButton.hasClass('disabled') ){

            		thisButton.addClass('disabled').attr('disabled',true);
            		thisButton.hide();

            		var ajax__bulk_migrate_recipes = $.post(
	                    cooked_js_vars.ajax_url,
	                    { action:'cooked_get_migrate_ids' },
	                    function( json_recipe_ids ) {

	                    	if ( json_recipe_ids ){

	                    		var recipe_ids = JSON.parse( json_recipe_ids ),
	                    			total_recipes = Object.keys(recipe_ids).length;

	                    		if ( total_recipes > 0 ){
	                    			cooked_migrate_recipes( json_recipe_ids, total_recipes );
	                    		}

	                    	}
	                    });

            	}

            });

        }

    });

})( jQuery );

if (typeof cookedDecimalAdjust != 'function') {
    function cookedDecimalAdjust(type, value, exp) {
        // If the exp is undefined or zero...
        if (typeof exp === 'undefined' || +exp === 0) {
          return Math[type](value);
        }
        value = +value;
        exp = +exp;
        // If the value is not a number or the exp is not an integer...
        if (value === null || isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
          return NaN;
        }
        // If the value is negative...
        if (value < 0) {
          return -cookedDecimalAdjust(type, -value, exp);
        }
        // Shift
        value = value.toString().split('e');
        value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
        // Shift back
        value = value.toString().split('e');
        return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
    }
}

if ( !Math.round10 ) {
    Math.round10 = function(value, exp) {
        var percent = cookedDecimalAdjust('round', value, exp);
        return percent;
    };
}

var cooked_recipe_migrate_counter = 0;
var progressIterations = 0;

function cooked_migrate_recipes( recipe_ids, total_recipes ){

	var temp_counter = 0,
		total_counter = 0,
		progress_percent = 0;

	if ( total_recipes > 0 ){

		var progress = jQuery( '#cooked-migration-progress' );
		var progress_bar = progress.find( '.cooked-progress-bar' );
		var progress_text = jQuery( '#cooked-migration-progress-text' );

		if ( !progress.hasClass('cooked-active') ){
			progress.addClass('cooked-active');
			progress_text.addClass('cooked-active');
			progress_bar.css( { "width" : "0%" } );
		}

        var this_recipe_ids = JSON.parse( recipe_ids ),
        this_total_recipe_ids = Object.keys(this_recipe_ids).length;

        var formattedTotal = total_recipes;
        formattedTotal.toLocaleString();

		var ajax__bulk_migrate_recipes = jQuery.post(
            cooked_js_vars.ajax_url,
            { action:'cooked_migrate_recipes', recipe_ids:recipe_ids },
            function( new_recipe_ids ) {

            	if ( new_recipe_ids && new_recipe_ids != 'false' && new_recipe_ids != false ){

	            	var leftover_recipe_ids = JSON.parse( new_recipe_ids ),
				        leftover_recipes = Object.keys(leftover_recipe_ids).length;

				    cooked_recipe_migrate_counter = total_recipes - leftover_recipes;

                    var formattedTotal = total_recipes;
                    formattedTotal.toLocaleString();

                    var formattedComplete = cooked_recipe_migrate_counter;
                    formattedComplete.toLocaleString();

					progress_percent = Math.round10( ( cooked_recipe_migrate_counter / total_recipes ) * 100, -1 );
                    if ( progress_percent < 2 ){ progress_percent = 2; }
					progress_bar.css( { "width" : progress_percent + "%" } );

                    var remainingProgress = 100 - progress_percent;
                    var estimatedCompletionTime = Math.round( ( remainingProgress / progress_percent ) * progressIterations );
                    var estimatedHours, estimatedMinutes;
                    progressIterations += 1;

                    if ( progress_percent < 100 && progress_percent > 3 && isFinite( estimatedCompletionTime ) ){
                        estimatedHours = Math.floor(estimatedCompletionTime / 3600);
                        estimatedMinutes = Math.floor((estimatedCompletionTime / 60) % 60);
                        if ( estimatedHours >= 1 ){
                            progress_text.html( formattedComplete + " / " + formattedTotal + "<strong style='display:inline-block; float:right;'>" + estimatedHours + " hrs, " + estimatedMinutes + " mins " + cooked_js_vars.i18n_remaining + "</strong>" );
                        } else if ( estimatedMinutes >= 1 ){
                            progress_text.html( formattedComplete + " / " + formattedTotal + "<strong style='display:inline-block; float:right;'>" + estimatedMinutes + " mins " + cooked_js_vars.i18n_remaining + "</strong>" );
                        } else {
                            progress_text.text( formattedComplete + " / " + formattedTotal );
                        }

                    } else {
                        progress_text.text( formattedComplete + " / " + formattedTotal );
                    }

					cooked_migrate_recipes( new_recipe_ids, total_recipes );

				} else {

					progress.hide();
					progress_text.hide();

					jQuery('.recipe-setting-block.migrate_button').find('h3').hide();
					jQuery('.recipe-setting-block.migrate_button').find('p:nth-child(2)').hide();
                    jQuery('.recipe-setting-block.migrate_button').find('ul.cooked-admin-ul').hide();
					jQuery('#cooked-migration-button').hide();
					jQuery('#cooked-migration-completed').addClass('cooked-active');

				}

			})

	}

}
