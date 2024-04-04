var $_CookedConditionalTimeout  = false;

(function( $ ) {

    $(document).ready(function() {

	    var $_CookedColorPickers 			= $('.cooked-color-field'),
	    	$_CookedSelectFields 			= $('#cooked_recipe_settings').find('select'),
			$_CookedRecipeTabs 				= $('#cooked-recipe-tabs'),
			$_CookedRecipeSettingsWrap 		= $('#cooked-settings-wrap'),
			$_CookedRecipeSettings 			= $('#cooked_recipe_settings'),
			$_CookedRecipeSettingsSubmit 	= $_CookedRecipeSettings.find( 'input#submit' )
	    	$_CookedTooltips 				= $('.cooked-tooltip'),
	    	$_CookedConditionals			= $('.cooked-conditional-hidden'),
	    	$_CookedSortable				= $('.cooked-sortable'),
	    	$_CookedRecipeSaveDefault		= $('.cooked-layout-save-default'),
			$_CookedShortcodeField 			= $('.cooked-shortcode-field'),
			$_CookedIngredientBuilder		= $('#cooked-ingredients-builder'),
			$_CookedDirectionBuilder		= $('#cooked-directions-builder'),
			$_CookedRecipeGallery			= $('#cooked-recipe-image-gallery'),
			$_CookedNutritionFactsTab		= $('#cooked-recipe-tab-content-nutrition'),
			$_CookedSettingsPanel 			= $('#cooked-settings-panel'),
			$_CookedSettingsTabs 			= $('#cooked-settings-tabs');

	    // Cooked Color Pickers
	    if ($_CookedColorPickers.length) {
		    $_CookedColorPickers.wpColorPicker();
	    }

	    // Cooked Sortables
	    if ($_CookedSortable.length){
	    	if ($_CookedSortable.find( '.cooked-icon-drag' )) {
			    $_CookedSortable.sortable({
					handle: ".cooked-icon-drag"
				});
			} else {
				$_CookedSortable.sortable();
			}
	    }

	    // Save as Default
	    if ($_CookedRecipeSaveDefault.length) {

	    	var saveDefaultTooltip = $_CookedRecipeSaveDefault.tooltipster({
		    	theme			: 'tooltipster-light',
		    	trigger			: 'click',
		    	animation		: 'grow',
		    	delay			: 0,
		    	speed			: 200,
		    	maxWidth		: 275,
                contentAsHTML	: true,
                interactive		: true,
                functionReady: function(instance, helper) {

                	$('.cooked-save-default-all').on('click',function(e) {

		            	e.preventDefault();
		            	var thisButton = $(this),
		            		thisContainer = thisButton.parent(),
		            		confirm_save = confirm( cooked_js_vars.i18n_confirm_save_default_all ),
		            		recipe_editor_textarea = $( "#_recipe_settings_content" ),
		            		recipe_editor = tinymce.get('_recipe_settings_content');

						if ( recipe_editor === null ) {
							var recipe_editor_content = recipe_editor_textarea.val();
						} else {
							var recipe_editor_content = recipe_editor.getContent();
						}

		            	if ( confirm_save && !thisButton.hasClass('disabled') ){
		            		thisContainer.find('.button, .button-primary').addClass('disabled');

		            		var ajax__save_default_new = $.post(
			                    cooked_js_vars.ajax_url,
			                    { action:'cooked_save_default', 'default_content':recipe_editor_content },
			                    function( result ) {

			                    	var ajax__save_default_all = $.post(
					                    cooked_js_vars.ajax_url,
					                    { action:'cooked_get_recipe_ids' },
					                    function( json_recipe_ids ) {

					                    	thisButton.removeClass("button-primary").addClass("button");
					                    	if ( json_recipe_ids ){

					                    		var recipe_ids = JSON.parse( json_recipe_ids ),
					                    			total_recipes = Object.keys(recipe_ids).length;

					                    		if ( total_recipes > 0 ){
					                    			cooked_set_default_template( json_recipe_ids, total_recipes, recipe_editor_content );
					                    		}

					                    	}
					                    });
			                    });

		            	}
		            });

		            $('.cooked-save-default-new').on('click',function(e){

		            	e.preventDefault();
		            	var thisButton = $(this),
		            		thisContainer = thisButton.parent(),
		            		recipe_editor_textarea = $( "#_recipe_settings_content" ),
		            		recipe_editor = tinymce.get('_recipe_settings_content');

						if ( recipe_editor === null ) {
							var recipe_editor_content = recipe_editor_textarea.val();
						} else {
							var recipe_editor_content = recipe_editor.getContent();
						}

		            	if ( !thisButton.hasClass('disabled') ){
		            		thisContainer.find('.button, .button-primary').addClass('disabled');
			            	var ajax__save_default_new = $.post(
			                    cooked_js_vars.ajax_url,
			                    { action:'cooked_save_default', 'default_content':recipe_editor_content },
			                    function( result ) {
			                    	thisButton.text( cooked_js_vars.i18n_saved );
			                    	thisContainer.find('.button-primary').removeClass('disabled');
			                        //console.log( 'Success: ' + result );
			                    })
			                    .fail(function( result ) {
			                        //console.log( 'Error: ' + result );
			                    });
		            	}
		            });

                }
            });

            $('.cooked-layout-load-default').on('click',function(e){
            	e.preventDefault();
            	var thisButton = $(this),
            		thisContainer = thisButton.parent(),
            		confirm_load = confirm( cooked_js_vars.i18n_confirm_load_default ),
            		recipe_editor_textarea = $( "#_recipe_settings_content" ),
            		recipe_editor = tinymce.get('_recipe_settings_content');

            	if ( confirm_load && !thisButton.hasClass('disabled') ){
            		thisContainer.find('.button, .button-primary').addClass('disabled');
	            	var ajax__save_default_all = $.post(
	                    cooked_js_vars.ajax_url,
	                    { action:'cooked_load_default' },
	                    function( result ) {

	                    	if ( recipe_editor === null ) {
								recipe_editor_textarea.val( result );
							} else {
								recipe_editor_textarea.val( result );
								recipe_editor.setContent( result );
							}

	                    	thisContainer.find('.button, .button-primary').removeClass('disabled');
	                        //console.log( 'SUCCESS' );
	                        //console.log( result );
	                    })
	                    .fail(function( result ) {
	                    	thisContainer.find('.button, .button-primary').removeClass('disabled');
	                        //console.log( 'ERROR' );
	                        //console.log( result );
	                    });
            	}
            });

       	}

	    // Cooked Select Wrappers
	    if ($_CookedSelectFields.length){
		    $_CookedSelectFields.each(function(){
			   	$(this).wrap('<div class="cooked-select-wrapper" />');
		    });
	    }

	    // Cooked Tooltips
	    if ($_CookedTooltips.length){
	    	$_CookedTooltips.tooltipster({
		    	theme			: 'tooltipster-light',
		    	animation		: 'grow',
		    	delay			: 100,
		    	speed			: 200,
		    	maxWidth		: 275,
                contentAsHTML	: true,
                interactive		: true
            });
        }

        // Cooked Shortcode Fields
	    if ($_CookedShortcodeField.length){
		    $_CookedShortcodeField.on('click',function(e){
				$(this).select();
			});
	    }

    	// Conditional Fields (Recipes and Settings Pages)
    	if ($_CookedConditionals.length){
	        var conditionalFields = [];
	        $_CookedConditionals.each(function(){
				var thisBlock = $(this),
					thisBlockType,
					thisID = $(this).data('condition'),
					thisVal = $(this).data('value'),
					thisField = $('#'+thisID);

				if (thisVal){
					thisVal = thisVal.split(' ');
				} else {
					thisVal = false;
				}

				if ( thisBlock.is('li') || thisBlock.is('span') ) {
					thisBlockType = 'inline-block';
				} else {
					thisBlockType = 'block';
				}

				if (thisField.is(":checkbox") && thisField.is(":checked") || thisField.is(":radio") && thisField.is(":checked")) {
					thisBlock.css({'display':thisBlockType});
				} else if (thisField.is(":checkbox") && !thisField.is(":checked") || thisField.is(":radio") && !thisField.is(":checked")) {
					thisBlock.hide();
				} else if (!thisField.is(":checkbox") && !thisVal && thisField.val() || !thisField.is(":checkbox") && thisVal && $.inArray(thisField.val(),thisVal) > -1){
					thisBlock.css({'display':thisBlockType});
				} else if (!thisField.is(":radio") && !thisVal && thisField.val() || !thisField.is(":radio") && thisVal && $.inArray(thisField.val(),thisVal) > -1){
					thisBlock.css({'display':thisBlockType});
				} else {
					thisBlock.hide();
				}

				if ($.inArray(thisID,conditionalFields) == -1){
					conditionalFields.push(thisID);
				}

				var conditionalsLength = conditionalFields.length;
				for (var i = 0; i < conditionalsLength; i++) {
				    cooked_init_conditional_field(thisID);
				}

		    });
		}

		// Recipe Tabs
		if ($_CookedRecipeTabs.length){

			var $_CookedRecipeTab 			= $_CookedRecipeTabs.find('li'),
				$_CookedRecipeTabsOffset 	= $_CookedRecipeTabs.offset().top - 32; // 32px for the admin bar

			$(window).on('load scroll',function() {
			    var scroll = $(window).scrollTop();
			    if (scroll >= $_CookedRecipeTabsOffset) {
			        $_CookedRecipeSettings.addClass("stuck");
			    } else {
				    $_CookedRecipeSettings.removeClass("stuck");
			    }
			});

			$_CookedRecipeTab.on('click',function(e){

				e.preventDefault();
				window.scrollTo(0,0);

				var thisTab 	= $(this),
					thisTabID 	= thisTab.attr('id');

				thisTabID = thisTabID.split('cooked-recipe-tab-');
				thisTabID = thisTabID[1];
				$('.cooked-recipe-tab-content').hide();
				$('#cooked-recipe-tab-content-'+thisTabID).show();

				$_CookedRecipeTab.removeClass('active');
				thisTab.addClass('active');

			});
		}

		// Checkboxes
		if ( $_CookedSettingsTabs.length || $_CookedRecipeTabs.length ){

			var greenSwitches = Array.prototype.slice.call(document.querySelectorAll('.cooked-switch'));
			var redSwitches = Array.prototype.slice.call(document.querySelectorAll('.cooked-switch-red'));
			var yellowSwitches = Array.prototype.slice.call(document.querySelectorAll('.cooked-switch-yellow'));
			var graySwitches = Array.prototype.slice.call(document.querySelectorAll('.cooked-switch-gray'));

			greenSwitches.forEach(function( html ) {
				var greenSwitchery = new Switchery( html, { color: '#00c086', size: 'small' } );
			});

			redSwitches.forEach(function( html ) {
				var redSwitchery = new Switchery( html, { color: '#d44d1f', size: 'small' } );
			});

			yellowSwitches.forEach(function( html ) {
				var yellowSwitchery = new Switchery( html, { color: '#ffad18', size: 'small' } );
			});

			graySwitches.forEach(function( html ) {
				var graySwitchery = new Switchery( html, { color: '#aaaaaa', size: 'small' } );
			});

		}

		// Settings Tabs
		if ($_CookedSettingsTabs.length){

			var CookedSettingsTabHash = window.location.hash;

			var $_CookedSettingsTab 			= $_CookedSettingsTabs.find('li'),
				$_CookedSettingsTabOffset 		= $_CookedSettingsTabs.offset().top - 32; // 32px for the admin bar

			$(window).on('load scroll',function() {
			    var scroll = $(window).scrollTop();
			    if (scroll >= $_CookedSettingsTabOffset) {
			        $_CookedSettingsPanel.addClass("stuck");
			        $("#cooked-settings-wrap").addClass("is-stuck");
			    } else {
				    $_CookedSettingsPanel.removeClass("stuck");
				    $("#cooked-settings-wrap").removeClass("is-stuck");
			    }
			});

			if ( CookedSettingsTabHash ){
				var activeTab = CookedSettingsTabHash;
				activeTab = activeTab.split('#');
				activeTab = activeTab[1];
				$_CookedSettingsTabs.find('li').removeClass('active');
				$_CookedSettingsTabs.find('a[href="'+CookedSettingsTabHash+'"]').parent().addClass('active');
				if ( activeTab == 'migration' ){
					$_CookedRecipeSettingsSubmit.hide();
				} else {
					$_CookedRecipeSettingsSubmit.show();
				}
				$('.cooked-settings-tab-content').hide();
				$('#cooked-settings-tab-content-'+activeTab).show();
			} else {
				var activeTab = $_CookedSettingsTabs.find('.active > a').attr('href');
				activeTab = activeTab.split('#');
				activeTab = activeTab[1];
				$('.cooked-settings-tab-content').hide();
				$('#cooked-settings-tab-content-'+activeTab).show();
			}

			$_CookedSettingsTab.on('click',function(e){

				$('.tab-content').hide();
				var thisTab = $(this).find('a');
				$_CookedSettingsTabs.find('li').removeClass('active');

				$(this).addClass('active');
				var activeTab = thisTab.attr('href');
				activeTab = activeTab.split('#');
				activeTab = activeTab[1];

				if ( activeTab == 'migration' ){
					$_CookedRecipeSettingsSubmit.hide();
				} else {
					$_CookedRecipeSettingsSubmit.show();
				}

				$('.cooked-settings-tab-content').hide();
				$('#cooked-settings-tab-content-'+activeTab).show();

				if ( $('#cooked-settings-panel').hasClass('stuck') ) {
					window.scrollTo(0,130);
				} else {
					window.scrollTo(0,0);
				}

			});
		}

		if ($_CookedIngredientBuilder.length){

			cooked_reset_ingredient_builder();

			$_CookedIngredientBuilder.on('keydown','input[data-ingredient-part="name"]',function(e){
				if ( e.keyCode === 9 || e.keyCode === 13 ){
					if ( $(this).parents('.cooked-ingredient-block').is(':last-child') ){
						e.preventDefault();
						$('#cooked-recipe-tab-content-ingredients').find('.cooked-add-ingredient-button').trigger('click');
						$_CookedIngredientBuilder.find('.cooked-ingredient-block:last-child input[data-ingredient-part="amount"]').focus();
					}
				} else {
					return;
				}
			});

			$_CookedIngredientBuilder.on('keyup','input[data-ingredient-part="url"]',function(e){
				var thisVal = $(this).val(),
					parentBlock = $(this).parents('.recipe-setting-block');
				if (thisVal){
					parentBlock.addClass('cooked-has-url');
				} else {
					parentBlock.removeClass('cooked-has-url');
				}
			});

			$_CookedIngredientBuilder.parent().on('click','.cooked-add-ingredient-button',function(e){
				e.preventDefault();
				var clonedIngredientTemplate = $_CookedIngredientBuilder.parent().find('.cooked-ingredient-template').clone().removeClass('cooked-template cooked-ingredient-template').addClass('cooked-ingredient-block');
				$_CookedIngredientBuilder.append(clonedIngredientTemplate);
				cooked_reset_ingredient_builder();
			});

			$_CookedIngredientBuilder.parent().on('click','.cooked-add-heading-button',function(e){
				e.preventDefault();
				var clonedHeadingTemplate = $_CookedIngredientBuilder.parent().find('.cooked-heading-template').clone().removeClass('cooked-template cooked-heading-template').addClass('cooked-ingredient-block cooked-ingredient-heading');
				$_CookedIngredientBuilder.append(clonedHeadingTemplate);
				cooked_reset_ingredient_builder();
			});

			$_CookedIngredientBuilder.parent().on('click','.cooked-delete-ingredient',function(e){
				e.preventDefault();
				$(this).parent().remove();
				cooked_reset_ingredient_builder();
			});

		}

		if ($_CookedDirectionBuilder.length){

			cooked_reset_direction_builder();

			$_CookedDirectionBuilder.parent().on('click','.cooked-add-direction-button',function(e){
				e.preventDefault();
				var clonedDirectionTemplate = $_CookedDirectionBuilder.parent().find('.cooked-direction-template').clone().removeClass('cooked-template cooked-direction-template').addClass('cooked-direction-block');
				$_CookedDirectionBuilder.append(clonedDirectionTemplate);
				cooked_reset_direction_builder();
			});

			$_CookedDirectionBuilder.parent().on('click','.cooked-add-heading-button',function(e){
				e.preventDefault();
				var clonedHeadingTemplate = $_CookedDirectionBuilder.parent().find('.cooked-heading-template').clone().removeClass('cooked-template cooked-heading-template').addClass('cooked-direction-block cooked-direction-heading');
				$_CookedDirectionBuilder.append(clonedHeadingTemplate);
				cooked_reset_direction_builder();
			});

			$_CookedDirectionBuilder.parent().on('click','.cooked-delete-direction',function(e){
				e.preventDefault();
				$(this).parent().remove();
				cooked_reset_direction_builder();
			});

			$_CookedDirectionBuilder.parent().on('click','.remove-image-button',function(e){
				e.preventDefault();
				$(this).parent().removeClass('cooked-has-image');
				$(this).parent().find('img').remove();
				$(this).parent().find('input[data-direction-part="image"]').val('');
				cooked_reset_direction_builder();
			});

			// Instantiates the variable that holds the media library frame.
		    var direction_image_frame,directionID;

		    $('body').on('click','.cooked-direction-img-placeholder, .cooked-direction-img',function(e){
		    	e.preventDefault();
		    	var thisButton = $(this).parent().find('.direction-image-button');
		    	thisButton.trigger('click');
		    });

		    // Runs when the image button is clicked.
		    $('body').on('click','.direction-image-button',function(e){

			    var thisButton = $(this);
			    directionID = thisButton.data('id');

		        e.preventDefault();

		        // If the frame already exists, re-open it.
		        if ( direction_image_frame ) {
		            direction_image_frame.open();
		            return;
		        }

		        // Sets up the media library frame
		        direction_image_frame = wp.media.frames.direction_image_frame = wp.media({
		            title: cooked_js_vars.i18n_image_title,
		            button: { text:  cooked_js_vars.i18n_image_button },
		            library: { type: 'image' }
		        });

		        // Runs when an image is selected.
		        direction_image_frame.on('select', function(){

		            // Grabs the attachment selection and creates a JSON representation of the model.
		            var media_attachment = direction_image_frame.state().get('selection').first().toJSON();

		            // Sends the attachment URL to our custom image input field.
		            $('#direction-'+directionID+'-image-src').attr('src',media_attachment.sizes.thumbnail.url).parent().addClass('cooked-has-image');
		            $('input[name="_recipe_settings[directions]['+directionID+'][image]"]').val( media_attachment.id );
		            $('.direction-image-button[data-id="'+directionID+'"]').prop( 'value',cooked_js_vars.i18n_image_change );

		        });

		        // Opens the media library frame.
		        direction_image_frame.open();
		    });

		}

		if ( $_CookedRecipeGallery.length ){

		    var gallery_images_frame;
		    cooked_init_gallery_sorting();

		    // Runs when the Add Images button is clicked in the Gallery tab.
		    $('body').on('click','.cooked-gallery-add-button',function(e){

			    var thisButton = $(this);
		        e.preventDefault();

		        // If the frame already exists, re-open it.
		        if ( gallery_images_frame ) {
		            gallery_images_frame.open();
		            return;
		        }

		        // Sets up the media library frame
		        gallery_images_frame = wp.media.frames.gallery_images_frame = wp.media({
		            title: cooked_js_vars.i18n_gallery_image_title,
		            button: { text:  cooked_js_vars.i18n_gallery_image_title },
		            library: { type: 'image' },
		            multiple: true
		        });

		        // Runs when an image is selected.
		        gallery_images_frame.on('select', function(){

		            // Grabs the attachment selection and creates a JSON representation of the model.
		            var media_attachments = gallery_images_frame.state().get('selection').toJSON();
		            var thisThumbnail;

		            for (var media_key in media_attachments) {
					    if (!media_attachments.hasOwnProperty(media_key)) continue;
					    var media_attachment = media_attachments[media_key];
					    thisThumbnail = media_attachment.sizes.thumbnail.url;
					    $('#cooked-recipe-image-gallery').append( '<div data-attachment-id="' + media_attachment.id + '" class="cooked-recipe-gallery-item"><img src="' + thisThumbnail + '" /><span class="cooked-gallery-item-title">' + media_attachment.title + '</span><input type="hidden" name="_recipe_settings[gallery][items][]" value="' + media_attachment.id + '" /><a href="#" data-attachment-id="' + media_attachment.id + '" class="cooked-gallery-edit-button"><i class="cooked-icon cooked-icon-pencil"></i></a><a href="#" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a></div>' );
					}

					cooked_init_gallery_sorting();

		        });

		        // Opens the media library frame.
		        gallery_images_frame.open();

		    });

		    $('body').on('click','.cooked-recipe-gallery-item img',function(e){
		    	e.preventDefault();
		    	var thisButton = $(this).parent().find('.cooked-gallery-edit-button');
		    	thisButton.trigger('click');
		    });

		    $('body').on('click','.cooked-gallery-edit-button',function(e){

		    	var thisButton = $(this),
		    		attachment_id = thisButton.data('attachment-id');

		        e.preventDefault();

		        // Sets up the media library frame
		        var image_edit_frame = wp.media.frames.gallery_images_frame = wp.media({
		            title: cooked_js_vars.i18n_edit_image_title,
		            button: { text:  cooked_js_vars.i18n_edit_image_button },
		            library: { type: 'image' },
		            multiple: false
		        });

		        image_edit_frame.on('open',function() {
				  	var selection = image_edit_frame.state().get('selection');
				  	attachment = wp.media.attachment(attachment_id);
				  	attachment.fetch();
				  	selection.add( attachment ? [ attachment ] : [] );
				});

		        // Opens the media library frame.
		        image_edit_frame.open();

		        // Runs when an image is selected.
		        image_edit_frame.on('select', function(){

		            // Grabs the attachment selection and creates a JSON representation of the model.
		            var media_attachments = image_edit_frame.state().get('selection').toJSON();
		            var thisThumbnail;

		            for (var media_key in media_attachments) {
					    if (!media_attachments.hasOwnProperty(media_key)) continue;
					    var media_attachment = media_attachments[media_key];
					    thisThumbnail = media_attachment.sizes.thumbnail.url;
					    thisButton.parent().replaceWith( '<div data-attachment-id="' + media_attachment.id + '" class="cooked-recipe-gallery-item"><img src="' + thisThumbnail + '" /><span class="cooked-gallery-item-title">' + media_attachment.title + '</span><input type="hidden" name="_recipe_settings[gallery][items][]" value="' + media_attachment.id + '" /><a href="#" data-attachment-id="' + media_attachment.id + '" class="cooked-gallery-edit-button"><i class="cooked-icon cooked-icon-pencil"></i></a><a href="#" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a></div>' );
					}

					cooked_init_gallery_sorting();

		        });

		    });

		    $_CookedRecipeGallery.on('click','.remove-image-button',function(e){

		    	var thisButton = $(this);
		    	directionID = thisButton.data('id');
		    	e.preventDefault();

		    	if ( directionID ){

			    	$('#direction-'+directionID+'-image-src').parent().removeClass('cooked-has-image').prop('src',false);
			       	$('input[name="_recipe_settings[directions]['+directionID+'][image]"]').val('');
			        $('.direction-image-button[data-id="'+directionID+'"]').prop( 'value',cooked_js_vars.i18n_image_title );

		    	} else {

		    		thisButton.parent().remove();

		    	}

		    });

		}

		if ( $_CookedNutritionFactsTab.length ){

			init_nutrition_facts( $_CookedNutritionFactsTab );

			$_CookedNutritionFactsTab.on('keyup','input',function(e){
				init_nutrition_facts( $_CookedNutritionFactsTab );
			});

		}

		/****   Time Pickers   ****/
	    if ( $('#cooked-prep-time').length ){
	    	$( '#cooked-prep-time,#cooked-cook-time' ).on('change',function(){

	    		var prepTimeValue = parseInt( $( '#cooked-prep-time' ).val() ),
	            cookTimeValue = parseInt( $( '#cooked-cook-time' ).val() );
	       		cooked_updateTotalTimeValue( prepTimeValue, cookTimeValue );

	    	});
	    }

	});

})( jQuery );

var cooked_recipe_update_counter = 0;

function cooked_set_default_template( recipe_ids, total_recipes, content ){

	var temp_counter = 0,
		total_counter = 0,
		progress_percent = 0;

	if ( total_recipes > 0 ){

		var progress = jQuery( '#cooked-template-progress' );
		var progress_bar = progress.find( '.cooked-progress-bar' );
		var progress_text = jQuery( '#cooked-template-progress-text' );

		if ( !progress.hasClass('cooked-active') ){
			progress.addClass('cooked-active');
			progress_text.addClass('cooked-active');
			progress_bar.css( { "width" : "0%" } );
		}

		var ajax__bulk_save_default_template = jQuery.post(
            cooked_js_vars.ajax_url,
            { action:'cooked_save_default_bulk', recipe_ids:recipe_ids, default_content:content },
            function( new_recipe_ids ) {

            	//console.log(new_recipe_ids);

            	if ( new_recipe_ids && new_recipe_ids != 'false' && new_recipe_ids != false ){

	            	var leftover_recipe_ids = JSON.parse( new_recipe_ids ),
				        leftover_recipes = Object.keys(leftover_recipe_ids).length;

				    cooked_recipe_update_counter = total_recipes - leftover_recipes;

					progress_percent = Math.round( ( cooked_recipe_update_counter / total_recipes ) * 100 );
					progress_bar.css( { "width" : progress_percent + "%" } );
					progress_text.text( cooked_recipe_update_counter + " / " + total_recipes );

					cooked_set_default_template( new_recipe_ids, total_recipes, content );

				} else {

					jQuery('.cooked-save-default-all').text( cooked_js_vars.i18n_applied );
					progress_bar.css( { "width" : "100%" } );
					progress.removeClass('cooked-active');
					progress_text.removeClass('cooked-active').text("");

				}

			})

	};

}

function cooked_updateTotalTimeValue( prepTime, cookTime ){

    var totalTimeInput = jQuery( '#cooked-total-time' ),
        totalTime = prepTime + cookTime;

    totalTimeInput.val( totalTime );

}

// Reset the Gallery Builder
// Assign a value to the hidden input field.
// Init the jQuery UI Sort
function cooked_init_gallery_sorting() {
	jQuery('#cooked-recipe-image-gallery').sortable();
}

function init_nutrition_facts( nutritionTab ) {
	nutritionTab.find('input').each(function() {
		var thisInput = jQuery(this),
			thisID = thisInput.attr('id'),
			thisVal = thisInput.val(),
			hasPercent = jQuery('.cooked-nut-percent[data-labeltype="' + thisID + '"]').length;

		if ( jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').length ) {
			if ( thisVal ) {
				jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').text( thisVal );
				jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').parents('li').eq(0).show();
				jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').parents('p').eq(0).show();
				if ( hasPercent ) {
					var thisPercentElem = jQuery('.cooked-nut-percent[data-labeltype="' + thisID + '"]'),
						pvd = thisPercentElem.data('pdv');
					if ( pvd ) {
						thisPercent = Math.ceil( ( thisVal / pvd ) * 100 );
						thisPercentElem.text( thisPercent );
					}
				}
			} else {
				jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').text( '___ ' );
				jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').parents('li').eq(0).hide();
				jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').parents('p').eq(0).hide();
			}
		}
	});
}

// Reset Time Picker Settings & Names
function cooked_reset_ingredient_builder() {

	var ingredientBlocks = jQuery('.cooked-ingredient-block'),
		total_ingredients = 0,
		total_blocks = 0;

	ingredientBlocks.each(function(){

		var randomKeyForInterval = cooked_get_random_int(10000000,99999999);
		total_blocks++;

		// Set the input "name" values.
		var $_this = jQuery(this);

		if ( !$_this.hasClass('cooked-ingredient-heading') ){
			total_ingredients++;
		}

		$_this.find("[data-ingredient-part]").each(function(){
			var thisField = jQuery(this);
			if ( thisField.attr('name') == ''){
				var ingredientPartName = thisField.data('ingredient-part');
				thisField.attr( 'name','_recipe_settings[ingredients][' + randomKeyForInterval + '][' + ingredientPartName + ']' );
			}
		});

	});

	if ( total_ingredients ){
		jQuery('.cooked-ingredient-headers').show();
	} else {
		jQuery('.cooked-ingredient-headers').hide();
	}

	if ( total_blocks ){
		jQuery('#cooked-ingredients-builder').css( { 'margin-bottom':'20px' } );
	} else {
		jQuery('#cooked-ingredients-builder').css( { 'margin-bottom':'12px' } );
	}

}

// Reset Time Picker Settings & Names
function cooked_reset_direction_builder(){

	var directionBlocks = jQuery('.cooked-direction-block'),
		total_blocks = 0;

	directionBlocks.each(function(){

		var randomKeyForInterval = cooked_get_random_int(10000000,99999999);
		total_blocks++;

		// Set the input "name" values.
		var $_this = jQuery(this);

		$_this.find("[data-direction-part]").each(function(){

			var thisField = jQuery(this);
			if ( thisField.attr('name') == ''){
				var directionPartName = thisField.data('direction-part');
				thisField.attr( 'name','_recipe_settings[directions][' + randomKeyForInterval + '][' + directionPartName + ']' );
			}
			if ( thisField.attr('data-id') == ''){
				thisField.attr( 'data-id',randomKeyForInterval );
			}
			if ( thisField.attr('id') == ''){
				var directionPartName = thisField.data( 'direction-part' );
				directionPartName = directionPartName.replace( '_', '-' );
				thisField.attr( 'id','direction-'+randomKeyForInterval+'-'+directionPartName);
			}

		});

	});

	if ( total_blocks ){
		jQuery('#cooked-directions-builder').css( { 'margin-bottom':'20px' } );
	} else {
		jQuery('#cooked-directions-builder').css( { 'margin-bottom':'12px' } );
	}

}

// Get random integer for sortable lists (ingredients and directions)
function cooked_get_random_int(min, max) {
	return Math.floor(Math.random() * (max - min)) + min;
}

// Check if value is an integer (for amount field in Ingredients Builder)
function cooked_is_int(val){
	if(Math.floor(val) == val && $.isNumeric(val)){
		return true;
	} else {
		return false;
	}
}

// Cooked Conditional Fields Function
function cooked_init_conditional_field(thisID){

	var thisField = jQuery('#'+thisID);

	if (thisField.is(':radio')){
		jQuery('body').find('input:radio').on('change',function(){
			if (!thisField.is(':checked')){
				jQuery('body').find("[data-condition='" + thisID + "']").each(function(){
					jQuery(this).hide();
				});
			}
		});
	}

	thisField.on('change',function(){

		jQuery('#cooked_recipe_settings').addClass('cooked-loading');

		window.clearTimeout($_CookedConditionalTimeout);
		$_CookedConditionalTimeout = window.setTimeout(function(){

			jQuery('body').find("[data-condition='" + thisID + "']").each(function(){
				var thisBlock = jQuery(this),
					thisBlockType;

				if ( thisBlock.is('li') || thisBlock.is('span') ) {
					thisBlockType = 'inline-block';
				} else {
					thisBlockType = 'block';
				}

				var thisVal = thisBlock.data('value');

				if (thisVal){
					thisVal = thisVal.split(' ');
				} else {
					thisVal = false;
				}

				if (thisField.is(":checkbox") && thisField.is(":checked") || thisField.is(":radio") && thisField.is(":checked")) {
					thisBlock.css({'display':thisBlockType});
				} else if (thisField.is(":checkbox") && !thisField.is(":checked")) {
					thisBlock.hide();
				} else if (!thisField.is(":checkbox") && !thisVal && thisField.val() || !thisField.is(":checkbox") && thisVal && jQuery.inArray(thisField.val(),thisVal) > -1){
					thisBlock.css({'display':thisBlockType});
				} else if (!thisField.is(":radio") && !thisVal && thisField.val() || !thisField.is(":radio") && thisVal && jQuery.inArray(thisField.val(),thisVal) > -1){
					thisBlock.css({'display':thisBlockType});
				} else {
					thisBlock.hide();
				}
			});

			jQuery('#cooked_recipe_settings').removeClass('cooked-loading');

		},25);

	});

}
