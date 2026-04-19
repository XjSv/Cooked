var $_CookedConditionalTimeout  = false;

/** Maps touch events on sortable drag handles to mouse events so sortable works on touch devices. */
var cookedSortableTouchHandler = function(event) {
    var target = event.target;
    var types = {
        touchstart: "mousedown",
        touchmove: "mousemove",
        touchend: "mouseup"
    };

    // Only handle touches on drag handles within cooked sortable containers
    var dragHandle = target.closest('.cooked-icon-drag');
    if (!dragHandle || !dragHandle.closest('.cooked-sortable')) {
        return; // Let the event proceed normally (allows scrolling)
    }

    if (!event.changedTouches || !event.changedTouches.length || !types[event.type]) {
        return;
    }

    var touch = event.changedTouches[0];

    // Prevent default to stop page scrolling when dragging
    event.preventDefault();

    var simulatedEvent = new MouseEvent(types[event.type], {
        bubbles: true,
        cancelable: true,
        view: window,
        detail: 1,
        screenX: touch.screenX,
        screenY: touch.screenY,
        clientX: touch.clientX,
        clientY: touch.clientY,
        ctrlKey: false,
        altKey: false,
        shiftKey: false,
        metaKey: false,
        button: 0,
        relatedTarget: null
    });
    touch.target.dispatchEvent(simulatedEvent);
};

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
            $_CookedAutoNutritionButton		= $('.cooked-auto-nutrition-button'),
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
        if ($_CookedSortable.length) {
            document.addEventListener("touchstart", cookedSortableTouchHandler, { passive: false });
            document.addEventListener("touchmove", cookedSortableTouchHandler, { passive: false });
            document.addEventListener("touchend", cookedSortableTouchHandler, { passive: false });

            if ($_CookedSortable.find('.cooked-icon-drag').length) {
                $_CookedSortable.sortable({
                    handle: '.cooked-icon-drag',
                    // scroll: true,
                    // scrollSensitivity: 80,
                    // scrollSpeed: 30,
                    stop: function(event, ui) {
                        // Update direction step numbers when reordering directions
                        if (ui.item.closest('#cooked-directions-builder').length) {
                            cooked_reset_direction_builder();
                        }

                        let textarea = ui.item.find('textarea');
                        var canUseWpEditor = !!(
                            cooked_admin_functions_js_vars.wp_editor_roles_allowed &&
                            typeof wp !== 'undefined' &&
                            wp.editor &&
                            typeof wp.editor.remove === 'function' &&
                            typeof wp.editor.initialize === 'function'
                        );

                        if (textarea.length && canUseWpEditor) {
                            let textareaName = textarea.attr('name');
                            let fieldID = textarea.attr('id');

                            wp.editor.remove(fieldID);
                            wp.editor.initialize(fieldID, {
                                tinymce: {
                                    wpautop: false,
                                    toolbar1: 'bold,italic,underline,blockquote,strikethrough,bullist,numlist,alignleft,aligncenter,alignright,undo,redo,wp_link_advanced,unlink,fullscreen',
                                    toolbar2: '',
                                    toolbar3: '',
                                    toolbar4: '',
                                    height: 100,
                                    textarea_name: textareaName,
                                    plugins: 'link lists fullscreen wordpress wplink',
                                    setup: function(editor) {
                                        // @TODO: Inline Link editor does not work.
                                        // editor.on('init', function() {
                                        //     if (typeof wpLink !== 'undefined') {
                                        //         editor.addCommand('WP_Link', function() {
                                        //             window.wpActiveEditor = editor.id;
                                        //             wpLink.open(editor.id);
                                        //             return false;
                                        //         });
                                        //     }
                                        // });
                                    }
                                },
                                quicktags: true,
                                mediaButtons: false
                            });
                        }
                    }
                });
            } else {
                $_CookedSortable.sortable({
                    // scroll: true,
                    // scrollSensitivity: 80,
                    // scrollSpeed: 30,
                    stop: function(event, ui) {
                        // Update direction step numbers when reordering directions
                        if (ui.item.closest('#cooked-directions-builder').length) {
                            cooked_reset_direction_builder();
                        }
                    }
                });
            }
        }

        // Save as Default
        if ($_CookedRecipeSaveDefault.length) {
            var saveDefaultTooltip = $_CookedRecipeSaveDefault.tooltipster({
                theme			: ['tooltipster-light', 'tooltipster-sideTip-cooked'],
                trigger			: 'click',
                animation		: 'grow',
                delay			: 0,
                speed			: 200,
                maxWidth		: 275,
                contentAsHTML	: true,
                interactive		: true,
                functionReady: function(instance, helper) {
                    $('.cooked-save-default-all').on('click', function(e) {
                        e.preventDefault();

                        var thisButton = $(this),
                            cooked_save_default_nonce = thisButton.data('nonce'),
                            cooked_save_default_bulk_nonce = thisButton.data('bulk-nonce'),
                            thisContainer = thisButton.parent(),
                            confirm_save = confirm(cooked_admin_functions_js_vars.i18n_confirm_save_default_all),
                            recipe_editor_textarea = $( "#_recipe_settings_content" ),
                            recipe_editor = tinymce.get('_recipe_settings_content');

                        if (recipe_editor === null) {
                            var recipe_editor_content = recipe_editor_textarea.val();
                        } else {
                            var recipe_editor_content = recipe_editor.getContent();
                        }

                        if (confirm_save && !thisButton.hasClass('disabled')) {
                            thisContainer.find('.button, .button-primary').addClass('disabled');

                            $.post(
                                cooked_admin_functions_js_vars.ajax_url,
                                {
                                    action: 'cooked_save_default',
                                    'default_content': recipe_editor_content,
                                    nonce: cooked_save_default_nonce
                                },
                                function() {
                                    $.post(
                                        cooked_admin_functions_js_vars.ajax_url,
                                        {
                                            action: 'cooked_get_recipe_count',
                                            nonce: cooked_save_default_bulk_nonce
                                        },
                                        function(response) {
                                            thisButton.removeClass("button-primary").addClass("button");

                                            if (response && response.success && response.data.total > 0) {
                                                cooked_recipe_update_counter = 0;
                                                cooked_set_default_template(0, response.data.total, recipe_editor_content, cooked_save_default_bulk_nonce, instance);
                                            }
                                        },
                                        'json'
                                    );
                                }
                            );
                        }
                    });

                    $('.cooked-save-default-new').on('click', function(e) {
                        e.preventDefault();
                        var thisButton = $(this),
                            nonce = thisButton.data('nonce'),
                            thisContainer = thisButton.parent(),
                            recipe_editor_textarea = $("#_recipe_settings_content"),
                            recipe_editor = tinymce.get('_recipe_settings_content');

                        if (recipe_editor === null) {
                            var recipe_editor_content = recipe_editor_textarea.val();
                        } else {
                            var recipe_editor_content = recipe_editor.getContent();
                        }

                        if (!thisButton.hasClass('disabled')) {
                            thisContainer.find('.button, .button-primary').addClass('disabled');
                            var ajax__save_default_new = $.post(
                                cooked_admin_functions_js_vars.ajax_url,
                                {
                                    action: 'cooked_save_default',
                                    'default_content': recipe_editor_content,
                                    nonce: nonce
                                },
                                function(result) {
                                    thisButton.text( cooked_admin_functions_js_vars.i18n_saved );
                                    thisContainer.find('.button-primary').removeClass('disabled');
                                }
                            ).fail(function(result) {
                                //console.log( 'Error: ' + result );
                            });
                        }
                    });
                }
            });

            $('.cooked-layout-load-default').on('click', function(e) {
                e.preventDefault();

                var thisButton = $(this),
                    thisContainer = thisButton.parent(),
                    confirm_load = confirm( cooked_admin_functions_js_vars.i18n_confirm_load_default ),
                    recipe_editor_textarea = $( "#_recipe_settings_content" ),
                    recipe_editor = tinymce.get('_recipe_settings_content');

                if (confirm_load && !thisButton.hasClass('disabled')) {
                    thisContainer.find('.button, .button-primary').addClass('disabled');
                    var ajax__save_default_all = $.post(
                        cooked_admin_functions_js_vars.ajax_url,
                        {
                            action: 'cooked_load_default'
                        },
                        function (result) {
                            if ( recipe_editor === null ) {
                                recipe_editor_textarea.val( result );
                            } else {
                                recipe_editor_textarea.val( result );
                                recipe_editor.setContent( result );
                            }

                            thisContainer.find('.button, .button-primary').removeClass('disabled');
                        }).fail(function(result) {
                            thisContainer.find('.button, .button-primary').removeClass('disabled');
                        });
                }
            });
        }

        // Cooked Select Wrappers
        if ($_CookedSelectFields.length) {
            $_CookedSelectFields.each(function() {
                $(this).wrap('<div class="cooked-select-wrapper" />');
            });
        }

        // Cooked Tooltips
        if ($_CookedTooltips.length){
            $_CookedTooltips.tooltipster({
                theme			: ['tooltipster-light', 'tooltipster-sideTip-cooked'],
                animation		: 'grow',
                delay			: 100,
                speed			: 200,
                maxWidth		: 275,
                contentAsHTML	: true,
                interactive		: true
            });
        }

        // Cooked Shortcode Fields
        if ($_CookedShortcodeField.length) {
            $_CookedShortcodeField.on('click',function(e) {
                $(this).select();
            });
        }

        // Conditional Fields (Recipes and Settings Pages)
        if ($_CookedConditionals.length) {
            var conditionalFields = [];
            $_CookedConditionals.each(function() {
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
        if ($_CookedRecipeTabs.length) {
            var $_CookedRecipeTab = $_CookedRecipeTabs.find('li'),
                $_CookedRecipeTabsOffset = $_CookedRecipeTabs.offset().top - 32; // 32px for the admin bar

            $(window).on('load scroll',function() {
                var scroll = $(window).scrollTop();
                if (scroll >= $_CookedRecipeTabsOffset) {
                    $_CookedRecipeSettings.addClass("stuck");
                } else {
                    $_CookedRecipeSettings.removeClass("stuck");
                }
            });

            $_CookedRecipeTab.on('click', function(e) {
                e.preventDefault();
                $_CookedRecipeTab.removeClass('active');
                window.scrollTo(0, 0);

                var thisTab = $(this),
                    thisTabID = thisTab.attr('id').split('cooked-recipe-tab-')[1];

                $('.cooked-recipe-tab-content').hide();
                var $newTab = $('#cooked-recipe-tab-content-' + thisTabID);
                $newTab.show();

                thisTab.addClass('active');
            });
        }

        // Checkboxes
        if ( $_CookedSettingsTabs.length || $_CookedRecipeTabs.length ) {
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
        if ($_CookedSettingsTabs.length) {
            var CookedSettingsTabHash = window.location.hash;
            var $_CookedSettingsTab = $_CookedSettingsTabs.find('li'),
                $_CookedSettingsTabOffset = $_CookedSettingsTabs.offset().top - 32; // 32px for the admin bar

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

            if ( CookedSettingsTabHash ) {
                var activeTab = CookedSettingsTabHash;
                activeTab = activeTab.split('#');
                activeTab = activeTab[1];
                $_CookedSettingsTabs.find('li').removeClass('active');
                $_CookedSettingsTabs.find('a[href="'+CookedSettingsTabHash+'"]').parent().addClass('active');
                if ( activeTab == 'migration' ) {
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

            $_CookedSettingsTab.on('click',function(e) {
                $('.tab-content').hide();
                var thisTab = $(this).find('a');
                $_CookedSettingsTabs.find('li').removeClass('active');

                $(this).addClass('active');
                var activeTab = thisTab.attr('href');
                activeTab = activeTab.split('#');
                activeTab = activeTab[1];

                if ( activeTab == 'migration' ) {
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

        if ($_CookedIngredientBuilder.length) {
            cooked_reset_ingredient_builder();

            $_CookedIngredientBuilder.on('keydown', 'input[data-ingredient-part="name"]', function(e) {
                if ( e.keyCode === 9 || e.keyCode === 13 ) {
                    if ( $(this).parents('.cooked-ingredient-block').is(':last-child') ) {
                        e.preventDefault();
                        $('#cooked-recipe-tab-content-ingredients').find('.cooked-add-ingredient-button').trigger('click');
                        $_CookedIngredientBuilder.find('.cooked-ingredient-block:last-child input[data-ingredient-part="amount"]').focus();
                    }
                } else {
                    return;
                }
            });

            $_CookedIngredientBuilder.parent().on('click', '.cooked-show-substitution', function(e) {
                e.preventDefault();
                var thisParent = $(this).parent();
                if ( thisParent.hasClass('cooked-expanded') ) {
                    thisParent.removeClass('cooked-expanded');
                } else {
                    thisParent.addClass('cooked-expanded');
                    thisParent.find('input[data-ingredient-part="sub_amount"]').focus();
                }
            });

            $_CookedIngredientBuilder.on('keyup change', 'input[data-ingredient-part="sub_amount"], input[data-ingredient-part="sub_name"], select[data-ingredient-part="sub_measurement"]', function(e) {
                var parentBlock = $(this).parents('.recipe-setting-block'),
                    subName = parentBlock.find('input[data-ingredient-part="sub_name"]').val();

                if (subName && subName.trim()){
                    parentBlock.addClass('cooked-has-substitution');
                } else {
                    parentBlock.removeClass('cooked-has-substitution');
                }
            });

            if ($_CookedAutoNutritionButton.length) {
                $_CookedIngredientBuilder.on('change', 'input[data-ingredient-part="name"]', function(e) {
                    var ingredient_name_value = false;

                    $_CookedIngredientBuilder.find('input[data-ingredient-part="name"]').each(function() {
                        if ($(this).val() != '') {
                            ingredient_name_value = true;
                            return false; // Break the loop
                        }
                    });

                    if (ingredient_name_value) {
                        $_CookedAutoNutritionButton.prop('disabled', false);
                    } else {
                        $_CookedAutoNutritionButton.prop('disabled', true);
                    }
                });
            }

            $_CookedIngredientBuilder.on('keyup', 'input[data-ingredient-part="url"]', function(e) {
                var thisVal = $(this).val(),
                    parentBlock = $(this).parents('.recipe-setting-block');
                if (thisVal){
                    parentBlock.addClass('cooked-has-url');
                } else {
                    parentBlock.removeClass('cooked-has-url');
                }
            });

            $_CookedIngredientBuilder.parent().on('click', '.cooked-add-ingredient-button', function(e) {
                e.preventDefault();
                var clonedIngredientTemplate = $_CookedIngredientBuilder.parent().find('.cooked-ingredient-template').clone().removeClass('cooked-template cooked-ingredient-template').addClass('cooked-ingredient-block');
                $_CookedIngredientBuilder.append(clonedIngredientTemplate);
                cooked_reset_ingredient_builder();
            });

            $_CookedIngredientBuilder.parent().on('click','.cooked-add-heading-button',function(e) {
                e.preventDefault();
                var clonedHeadingTemplate = $_CookedIngredientBuilder.parent().find('.cooked-heading-template').clone().removeClass('cooked-template cooked-heading-template').addClass('cooked-ingredient-block cooked-ingredient-heading');
                $_CookedIngredientBuilder.append(clonedHeadingTemplate);
                cooked_reset_ingredient_builder();
            });

            $_CookedIngredientBuilder.parent().on('click','.cooked-delete-ingredient',function(e) {
                e.preventDefault();
                $(this).parent().remove();
                cooked_reset_ingredient_builder();
            });
        }

        if ($_CookedDirectionBuilder.length) {
            cooked_reset_direction_builder();

            $_CookedDirectionBuilder.parent().on('click', '.cooked-show-heading-element', function(e) {
                e.preventDefault();
                var thisParent = $(this).parent();
                if ( thisParent.hasClass('cooked-expanded') ) {
                    thisParent.removeClass('cooked-expanded');
                } else {
                    thisParent.addClass('cooked-expanded');
                    thisParent.find('input[data-direction-part="section_heading_element"]').focus();
                }
            });

            $_CookedDirectionBuilder.parent().on('click', '.cooked-add-direction-button', function(e) {
                e.preventDefault();
                var clonedDirectionTemplate = $_CookedDirectionBuilder.parent().find('.cooked-direction-template').clone().removeClass('cooked-template cooked-direction-template').addClass('cooked-direction-block');
                $_CookedDirectionBuilder.append(clonedDirectionTemplate);
                cooked_reset_direction_builder();
            });

            $_CookedDirectionBuilder.parent().on('click', '.cooked-add-heading-button', function(e) {
                e.preventDefault();
                var clonedHeadingTemplate = $_CookedDirectionBuilder.parent().find('.cooked-heading-template').clone().removeClass('cooked-template cooked-heading-template').addClass('cooked-direction-block cooked-direction-heading');
                $_CookedDirectionBuilder.append(clonedHeadingTemplate);
                cooked_reset_direction_builder();
            });

            $_CookedDirectionBuilder.parent().on('click', '.cooked-delete-direction', function(e) {
                e.preventDefault();
                var directionBlock = $(this).parent();
                var canRemoveWpEditor = !!(
                    cooked_admin_functions_js_vars.wp_editor_roles_allowed &&
                    typeof wp !== 'undefined' &&
                    wp.editor &&
                    typeof wp.editor.remove === 'function'
                );

                if (canRemoveWpEditor) {
                    var directionTextarea = directionBlock.find('textarea[data-direction-part="content"]');
                    var fieldID = directionTextarea.attr('id');

                    if (fieldID) {
                        wp.editor.remove(fieldID);
                    }
                }

                directionBlock.remove();
                cooked_reset_direction_builder();
            });

            $_CookedDirectionBuilder.parent().on('click', '.remove-image-button', function(e) {
                e.preventDefault();
                var $parent = $(this).parent();
                $parent.removeClass('cooked-has-image');
                $parent.find('img').attr('src', '').removeAttr('srcset').removeAttr('sizes');
                $parent.find('input[data-direction-part="image"]').val('');
                cooked_reset_direction_builder();
            });

            // Instantiates the variable that holds the media library frame.
            var direction_image_frame, directionID;

            $('body').on('click', '.cooked-direction-img-placeholder, .cooked-direction-img', function(e) {
                e.preventDefault();
                var thisButton = $(this).parent().find('.direction-image-button');
                thisButton.trigger('click');
            });

            // Runs when the image button is clicked.
            $('body').on('click', '.direction-image-button', function(e) {
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
                    title: cooked_admin_functions_js_vars.i18n_image_title,
                    button: { text:  cooked_admin_functions_js_vars.i18n_image_button },
                    library: { type: 'image' }
                });

                // Runs when an image is selected.
                direction_image_frame.on('select', function() {
                    // Grabs the attachment selection and creates a JSON representation of the model.
                    var media_attachment = direction_image_frame.state().get('selection').first().toJSON();

                    // Sends the attachment URL to our custom image input field.
                    // Remove srcset/sizes so the UI updates when an image was previously loaded (WP outputs srcset on edit).
                    var $directionImg = $('#direction-' + directionID + '-image-src');
                    $directionImg.attr('src', media_attachment.sizes.thumbnail.url)
                        .removeAttr('srcset')
                        .removeAttr('sizes')
                        .parent().addClass('cooked-has-image');
                    $('input[name="_recipe_settings[directions][' + directionID + '][image]"]').val( media_attachment.id );
                    $('.direction-image-button[data-id="' + directionID + '"]').prop( 'value', cooked_admin_functions_js_vars.i18n_image_change );
                });

                // Opens the media library frame.
                direction_image_frame.open();
            });
        }

        // Bulk Add Modal
        cooked_init_bulk_add($);

        if ( $_CookedRecipeGallery.length ) {

            var gallery_images_frame;
            cooked_init_gallery_sorting();

            // Runs when the Add Images button is clicked in the Gallery tab.
            $('body').on('click','.cooked-gallery-add-button', function(e) {
                var thisButton = $(this);
                e.preventDefault();

                // If the frame already exists, re-open it.
                if ( gallery_images_frame ) {
                    gallery_images_frame.open();
                    return;
                }

                // Sets up the media library frame
                gallery_images_frame = wp.media.frames.gallery_images_frame = wp.media({
                    title: cooked_admin_functions_js_vars.i18n_gallery_image_title,
                    button: { text:  cooked_admin_functions_js_vars.i18n_gallery_image_title },
                    library: { type: 'image' },
                    multiple: true
                });

                // Runs when an image is selected.
                gallery_images_frame.on('select', function() {
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

            $('body').on('click','.cooked-recipe-gallery-item img', function(e) {
                e.preventDefault();
                var thisButton = $(this).parent().find('.cooked-gallery-edit-button');
                thisButton.trigger('click');
            });

            $('body').on('click','.cooked-gallery-edit-button', function(e) {
                var thisButton = $(this),
                    attachment_id = thisButton.data('attachment-id');

                e.preventDefault();

                // Sets up the media library frame
                var image_edit_frame = wp.media.frames.gallery_images_frame = wp.media({
                    title: cooked_admin_functions_js_vars.i18n_edit_image_title,
                    button: { text:  cooked_admin_functions_js_vars.i18n_edit_image_button },
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
                image_edit_frame.on('select', function() {
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

            $_CookedRecipeGallery.on('click', '.remove-image-button', function(e) {
                var thisButton = $(this);
                directionID = thisButton.data('id');
                e.preventDefault();

                if ( directionID ) {
                    $('#direction-'+directionID+'-image-src').parent().removeClass('cooked-has-image').prop('src',false);
                       $('input[name="_recipe_settings[directions]['+directionID+'][image]"]').val('');
                    $('.direction-image-button[data-id="'+directionID+'"]').prop( 'value',cooked_admin_functions_js_vars.i18n_image_title );
                } else {
                    thisButton.parent().remove();
                }
            });
        }

        if ( $_CookedNutritionFactsTab.length ) {
            init_nutrition_facts( $_CookedNutritionFactsTab );

            $_CookedNutritionFactsTab.on('keyup', 'input', function(e) {
                init_nutrition_facts( $_CookedNutritionFactsTab );
            });
        }

        /****   Time Pickers   ****/
        if ( $('#cooked-prep-time').length ) {
            $( '#cooked-prep-time,#cooked-cook-time' ).on('change', function() {
                var prepTimeValue = parseInt( $( '#cooked-prep-time' ).val() ),
                    cookTimeValue = parseInt( $( '#cooked-cook-time' ).val() );
                cooked_updateTotalTimeValue( prepTimeValue, cookTimeValue );
            });
        }

    });

})( jQuery );

var cooked_recipe_update_counter = 0;
var cooked_bulk_per_page = 20;

/** Applies default recipe content in paginated AJAX batches and updates the progress UI. */
function cooked_set_default_template(page, total_recipes, content, nonce, instance) {
    if (total_recipes <= 0) {
        return;
    }

    var progress = jQuery('#cooked-template-progress');
    var progress_bar = progress.find('.cooked-progress-bar');
    var progress_text = jQuery('#cooked-template-progress-text');

    if (!progress.hasClass('cooked-active')) {
        progress.addClass('cooked-active');
        progress_text.addClass('cooked-active');
        progress_bar.css({ "width" : "0%" });
        instance.reposition();
    }

    jQuery.post(
        cooked_admin_functions_js_vars.ajax_url,
        {
            action: 'cooked_save_default_bulk',
            page: page,
            default_content: content,
            nonce: nonce
        },
        function(response) {
            if (response && response.success) {
                cooked_recipe_update_counter = Math.min((page + 1) * cooked_bulk_per_page, total_recipes);

                var progress_percent = Math.round((cooked_recipe_update_counter / total_recipes) * 100);
                progress_bar.css({ "width" : progress_percent + "%" });
                progress_text.text(cooked_recipe_update_counter + " / " + total_recipes);

                if (response.data.has_more) {
                    cooked_set_default_template(page + 1, total_recipes, content, nonce, instance);
                } else {
                    jQuery('.cooked-save-default-all').text(cooked_admin_functions_js_vars.i18n_applied);
                    progress_bar.css({ "width" : "100%" });
                    progress_text.text(total_recipes + " / " + total_recipes);
                    progress.removeClass('cooked-active');
                    progress_text.removeClass('cooked-active').text("");

                    instance.reposition();
                }
            }
        },
        'json'
    );
}

/** Sets the total time field to prep time plus cook time. */
function cooked_updateTotalTimeValue( prepTime, cookTime ) {
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

/** Syncs nutrition tab inputs with the live nutrition label preview (values and daily-value percents). */
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
                jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').text( '___' );
                jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').parents('li').eq(0).hide();
                jQuery('.cooked-nut-label[data-labeltype="' + thisID + '"]').parents('p').eq(0).hide();
            }
        }
    });
}

// Reset Time Picker Settings & Names
function cooked_reset_ingredient_builder() {
    var ingredientBlocks = jQuery('.cooked-ingredient-block'),
        total_ingredients_blocks = 0,
        total_blocks = 0,
        ingredientNameValue = false;

    if (ingredientBlocks.length > 0) {
        ingredientBlocks.each(function() {
            var randomKeyForInterval = cooked_get_random_int(10000000, 99999999);
            total_blocks++;

            // Set the input "name" values.
            var $_this = jQuery(this);

            if (!$_this.hasClass('cooked-ingredient-heading')) {
                total_ingredients_blocks++;
            }

            $_this.find("[data-ingredient-part]").each(function() {
                var thisField = jQuery(this);
                if (thisField.attr('name') == '') {
                    var ingredientPartName = thisField.data('ingredient-part');
                    thisField.attr('name', '_recipe_settings[ingredients][' + randomKeyForInterval + '][' + ingredientPartName + ']');
                }
            });
        });

        if ($_CookedAutoNutritionButton.length) {
            jQuery('input[data-ingredient-part="name"]').each(function() {
                if (jQuery(this).val() != '') {
                    ingredientNameValue = true;
                    return false; // Break the loop
                }
            });
        }
    }

    if (total_ingredients_blocks) {
        jQuery('.cooked-ingredient-headers').show();
        if ($_CookedAutoNutritionButton.length) {
            if (ingredientNameValue) {
                $_CookedAutoNutritionButton.prop('disabled', false);
            } else {
                $_CookedAutoNutritionButton.prop('disabled', true);
            }
        }
    } else {
        jQuery('.cooked-ingredient-headers').hide();
        if ($_CookedAutoNutritionButton.length) $_CookedAutoNutritionButton.prop('disabled', true);
    }

    if (total_blocks) {
        jQuery('#cooked-ingredients-builder').css( { 'margin-bottom':'20px' } );
    } else {
        jQuery('#cooked-ingredients-builder').css( { 'margin-bottom':'12px' } );
    }
}

// Reset Time Picker Settings & Names
function cooked_reset_direction_builder() {
    var directionBlocks = jQuery('.cooked-direction-block'),
        total_blocks = 0;

    directionBlocks.each(function() {
        var randomKeyForInterval = cooked_get_random_int(10000000, 99999999);
        total_blocks++;

        // Set the input "name" values.
        var $_this = jQuery(this);

        $_this.find("[data-direction-part]").each(function() {
            var thisField = jQuery(this);
            var directionPartName = thisField.data('direction-part');

            if ( thisField.attr('name') == '') {
                thisField.attr( 'name', '_recipe_settings[directions][' + randomKeyForInterval + '][' + directionPartName + ']' );
            }

            if ( thisField.attr('data-id') == '') {
                thisField.attr( 'data-id', randomKeyForInterval );
            }

            let theId = thisField.attr('id');

            if ( theId == '' || theId == undefined ) {
                directionPartName = directionPartName.replace( '_', '-' );
                var fieldID = 'direction-' + randomKeyForInterval + '-' + directionPartName;
                thisField.attr('id', fieldID);

                var canInitializeWpEditor = !!(
                    cooked_admin_functions_js_vars.wp_editor_roles_allowed &&
                    typeof wp !== 'undefined' &&
                    wp.editor &&
                    typeof wp.editor.initialize === 'function'
                );

                if (directionPartName === 'content' && thisField.is('textarea') && canInitializeWpEditor) {
                    // Init the WordPress Editor.
                    wp.editor.initialize(fieldID, {
                        tinymce: {
                            wpautop: false,
                            toolbar1: 'bold,italic,underline,blockquote,strikethrough,bullist,numlist,alignleft,aligncenter,alignright,undo,redo,wp_link_advanced,unlink,fullscreen',
                            toolbar2: '',
                            toolbar3: '',
                            toolbar4: '',
                            height: 100,
                            textarea_name: '_recipe_settings[directions][' + randomKeyForInterval + '][' + directionPartName + ']',
                            plugins: 'link lists fullscreen wordpress wplink',
                            setup: function(editor) {
                                // @TODO: Inline Link editor does not work.
                                // editor.on('init', function() {
                                //     if (typeof wpLink !== 'undefined') {
                                //         editor.addCommand('WP_Link', function() {
                                //             window.wpActiveEditor = editor.id;
                                //             wpLink.open(editor.id);
                                //             return false;
                                //         });
                                //     }
                                // });
                            }
                        },
                        quicktags: true,
                        mediaButtons: false
                    });
                }
            }
        });
    });

    // Update step numbers for direction blocks (skip section headings).
    var stepNum = 0;
    jQuery('#cooked-directions-builder').find('.cooked-direction-block').each(function() {
        var $_block = jQuery(this);
        if ( !$_block.hasClass('cooked-direction-heading') ) {
            stepNum++;
            $_block.find('.cooked-direction-number').text(stepNum);
            $_block.toggleClass('cooked-direction-has-number-wide', stepNum > 9);
            $_block.addClass('cooked-direction-has-number');
        } else {
            $_block.removeClass('cooked-direction-has-number cooked-direction-has-number-wide');
        }
    });

    if ( total_blocks ) {
        jQuery('#cooked-directions-builder').css( { 'margin-bottom': '20px' } );
    } else {
        jQuery('#cooked-directions-builder').css( { 'margin-bottom': '12px' } );
    }
}

// Get random integer for sortable lists (ingredients and directions)
function cooked_get_random_int(min, max) {
    return Math.floor(Math.random() * (max - min)) + min;
}

// Check if value is an integer (for amount field in Ingredients Builder)
function cooked_is_int(val) {
    if (Math.floor(val) == val && $.isNumeric(val)) {
        return true;
    } else {
        return false;
    }
}

// Cooked Conditional Fields Function
function cooked_init_conditional_field(thisID) {
    var thisField = jQuery('#' + thisID);

    if (thisField.is(':radio')) {
        jQuery('body').find('input:radio').on('change', function() {
            if (!thisField.is(':checked')) {
                jQuery('body').find("[data-condition='" + thisID + "']").each(function() {
                    jQuery(this).hide();
                });
            }
        });
    }

    thisField.on('change', function() {
        jQuery('#cooked_recipe_settings').addClass('cooked-loading');

        window.clearTimeout($_CookedConditionalTimeout);

        $_CookedConditionalTimeout = window.setTimeout(function() {
            jQuery('body').find("[data-condition='" + thisID + "']").each(function() {
                var thisBlock = jQuery(this),
                    thisBlockType;

                if ( thisBlock.is('li') || thisBlock.is('span') ) {
                    thisBlockType = 'inline-block';
                } else {
                    thisBlockType = 'block';
                }

                var thisVal = thisBlock.data('value');

                if (thisVal) {
                    thisVal = thisVal.split(' ');
                } else {
                    thisVal = false;
                }

                if (thisField.is(":checkbox") && thisField.is(":checked") || thisField.is(":radio") && thisField.is(":checked")) {
                    thisBlock.css({'display':thisBlockType});
                } else if (thisField.is(":checkbox") && !thisField.is(":checked")) {
                    thisBlock.hide();
                } else if (!thisField.is(":checkbox") && !thisVal && thisField.val() || !thisField.is(":checkbox") && thisVal && jQuery.inArray(thisField.val(),thisVal) > -1) {
                    thisBlock.css({'display':thisBlockType});
                } else if (!thisField.is(":radio") && !thisVal && thisField.val() || !thisField.is(":radio") && thisVal && jQuery.inArray(thisField.val(),thisVal) > -1) {
                    thisBlock.css({'display':thisBlockType});
                } else {
                    thisBlock.hide();
                }
            });

            jQuery('#cooked_recipe_settings').removeClass('cooked-loading');

        }, 25);
    });
}

/** Wires the bulk-add modal (preview, parse, submit) for ingredients and directions. */
function cooked_init_bulk_add($) {
    var $overlay = $('#cooked-bulk-add-overlay');
    if (!$overlay.length) return;

    var $textarea = $('#cooked-bulk-add-textarea'),
        $preview = $('#cooked-bulk-add-preview'),
        $previewList = $('#cooked-bulk-add-preview-list'),
        $title = $('#cooked-bulk-add-title'),
        $typeField = $('#cooked-bulk-add-type'),
        $submitBtn = $overlay.find('.cooked-bulk-add-submit'),
        $spinner = $overlay.find('.cooked-bulk-add-spinner'),
        jsVars = cooked_admin_functions_js_vars,
        parseTimer = null,
        lastParsedText = '';

    /** Opens the bulk-add overlay for the given type and sets copy/placeholders. */
    function openModal(type) {
        $typeField.val(type);
        $textarea.val('');
        $previewList.empty();
        $preview.attr('data-bulk-type', '');
        $preview.hide();
        $submitBtn.attr('disabled', 'disabled');
        $spinner.hide();
        lastParsedText = '';

        if (type === 'ingredients') {
            $title.text(jsVars.i18n_bulk_add_ingredients);
            $textarea.attr('placeholder', jsVars.i18n_bulk_add_placeholder_ingredients);
            $submitBtn.text(jsVars.i18n_bulk_add_submit_ingredients);
        } else {
            $title.text(jsVars.i18n_bulk_add_directions);
            $textarea.attr('placeholder', jsVars.i18n_bulk_add_placeholder_directions);
            $submitBtn.text(jsVars.i18n_bulk_add_submit_directions);
        }

        $overlay.show();
        $textarea.focus();
    }

    /** Hides the bulk-add overlay and clears its state. */
    function closeModal() {
        $overlay.hide();
        $textarea.val('');
        $previewList.empty();
        $preview.attr('data-bulk-type', '');
        $preview.hide();
        lastParsedText = '';
        if (parseTimer) clearTimeout(parseTimer);
    }

    /** Strips leading list markers from a single line of bulk text. */
    function cleanLine(line) {
        line = line.trim();
        line = line.replace(/^(\d+\)\s+|\d+\.\s+|[a-z]+\)\s+|[•·\-\*]\s+|[A-Z]+\.\s+|[IVX]+\.\s+)/, '');
        return line.trim();
    }

    /** Splits bulk textarea content into non-empty cleaned lines. */
    function parseTextToLines(text) {
        var lines = text.split(/[\r\n]+/);
        var result = [];
        for (var i = 0; i < lines.length; i++) {
            var cleaned = cleanLine(lines[i]);
            if (cleaned) {
                result.push(cleaned);
            }
        }
        return result;
    }

    /** Escapes a string for safe insertion into HTML attribute strings. */
    function escHtml(str) {
        return $('<span>').text(str).html();
    }

    /** Renders bulk directions preview rows from parsed lines. */
    function renderDirectionsPreview(lines) {
        $previewList.empty();
        if (!lines.length) {
            $preview.attr('data-bulk-type', '');
            $preview.hide();
            $submitBtn.attr('disabled', 'disabled');
            return;
        }
        $preview.attr('data-bulk-type', 'directions');
        $preview.show();
        $submitBtn.removeAttr('disabled');

        for (var i = 0; i < lines.length; i++) {
            var $row = $('<div class="cooked-bulk-add-preview-row"></div>');
            var $cb = $('<label class="cooked-bulk-add-heading-toggle"><input type="checkbox" data-index="' + i + '" /><span>' + jsVars.i18n_bulk_add_section_heading + '</span></label>');
            var $text = $('<input type="text" class="cooked-bulk-add-preview-text" data-index="' + i + '" value="' + escHtml(lines[i]) + '" />');
            $row.append($cb).append($text);
            $previewList.append($row);
        }
    }

    /** Renders bulk ingredients preview rows (optionally with server-parsed amount/unit/name). */
    function renderIngredientsPreview(lines, parsed) {
        $previewList.empty();
        if (!lines.length) {
            $preview.attr('data-bulk-type', '');
            $preview.hide();
            $submitBtn.attr('disabled', 'disabled');
            return;
        }
        $preview.attr('data-bulk-type', 'ingredients');
        $preview.show();
        $submitBtn.removeAttr('disabled');

        if (!$previewList.find('.cooked-bulk-add-preview-header').length) {
            $previewList.prepend(
                '<div class="cooked-bulk-add-preview-header">' +
                    '<span class="cooked-bulk-add-col-heading"></span>' +
                    '<span class="cooked-bulk-add-col-amount">' + escHtml(jsVars.i18n_bulk_add_amount) + '</span>' +
                    '<span class="cooked-bulk-add-col-unit">' + escHtml(jsVars.i18n_bulk_add_unit) + '</span>' +
                    '<span class="cooked-bulk-add-col-name">' + escHtml(jsVars.i18n_bulk_add_name) + '</span>' +
                '</div>'
            );
        }

        for (var i = 0; i < lines.length; i++) {
            var p = parsed && parsed[i] ? parsed[i] : { amount: '', measurement: '', name: lines[i] };
            var $row = $('<div class="cooked-bulk-add-preview-row cooked-bulk-add-preview-row-ingredient" data-index="' + i + '"></div>');
            var $cb = $('<label class="cooked-bulk-add-heading-toggle"><input type="checkbox" data-index="' + i + '" /><span>' + jsVars.i18n_bulk_add_section_heading + '</span></label>');
            var $headingWrap = $('<div class="cooked-bulk-add-heading-line-wrap"></div>');
            var $headingLbl = $('<span class="cooked-bulk-add-heading-line-label"></span>').text(jsVars.i18n_bulk_add_heading_line_label);
            var $lineInput = $('<input type="text" class="cooked-bulk-add-preview-text" data-index="' + i + '" />').val(lines[i]);
            $headingWrap.append($headingLbl).append($lineInput);
            var $amt = $('<input type="text" class="cooked-bulk-add-parsed-amount" data-index="' + i + '" value="' + escHtml(p.amount) + '" />');
            var $unit = $('<input type="text" class="cooked-bulk-add-parsed-unit" data-index="' + i + '" value="' + escHtml(p.measurement) + '" />');
            var $name = $('<input type="text" class="cooked-bulk-add-parsed-name" data-index="' + i + '" value="' + escHtml(p.name) + '" />');
            $row.append($cb).append($headingWrap).append($amt).append($unit).append($name);
            $previewList.append($row);
        }
    }

    /** Fetches AJAX-parsed ingredient lines and refreshes the preview. */
    function fetchIngredientParse(lines) {
        if (!lines.length) {
            renderIngredientsPreview([], null);
            return;
        }

        $spinner.show().css('visibility', 'visible');

        $.post(jsVars.ajax_url, {
            action: 'cooked_parse_bulk_ingredients',
            nonce: jsVars.cooked_bulk_add_nonce,
            lines: lines
        }, function(response) {
            $spinner.hide();
            var parsed = (response.success && response.data && response.data.parsed) ? response.data.parsed : null;
            renderIngredientsPreview(lines, parsed);
        }).fail(function() {
            $spinner.hide();
            renderIngredientsPreview(lines, null);
        });
    }

    $textarea.on('input', function() {
        var type = $typeField.val();
        var lines = parseTextToLines($(this).val());
        var textKey = lines.join('\n');

        if (type === 'directions') {
            renderDirectionsPreview(lines);
        } else {
            if (textKey === lastParsedText) return;
            lastParsedText = textKey;
            renderIngredientsPreview(lines, null);
            if (parseTimer) clearTimeout(parseTimer);
            parseTimer = setTimeout(function() {
                fetchIngredientParse(lines);
            }, 400);
        }
    });

    $(document).on('click', '.cooked-bulk-add-button', function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        openModal(type);
    });

    $overlay.on('click', '.cooked-bulk-add-close, .cooked-bulk-add-cancel', function(e) {
        e.preventDefault();
        closeModal();
    });

    $overlay.on('click', function(e) {
        if ($(e.target).is($overlay)) {
            closeModal();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $overlay.is(':visible')) {
            closeModal();
        }
    });

    $previewList.on('change', 'input[type="checkbox"]', function() {
        var $row = $(this).closest('.cooked-bulk-add-preview-row');
        var isHeading = $(this).is(':checked');
        $row.toggleClass('cooked-bulk-add-is-heading', isHeading);
    });

    $submitBtn.on('click', function(e) {
        e.preventDefault();
        if ($(this).attr('disabled')) return;

        var type = $typeField.val();
        var items = [];

        if (type === 'ingredients') {
            $previewList.find('.cooked-bulk-add-preview-row').each(function() {
                var $row = $(this);
                var isHeading = $row.find('input[type="checkbox"]').is(':checked');
                var text = $row.find('.cooked-bulk-add-preview-text').val().trim();
                if (!text) return;

                if (isHeading) {
                    items.push({ text: text, heading: true });
                } else {
                    items.push({
                        heading: false,
                        amount: $row.find('.cooked-bulk-add-parsed-amount').val().trim(),
                        measurement: $row.find('.cooked-bulk-add-parsed-unit').val().trim(),
                        name: $row.find('.cooked-bulk-add-parsed-name').val().trim() || text
                    });
                }
            });
        } else {
            $previewList.find('.cooked-bulk-add-preview-row').each(function() {
                var $row = $(this);
                var text = $row.find('.cooked-bulk-add-preview-text').val().trim();
                var isHeading = $row.find('input[type="checkbox"]').is(':checked');
                if (text) {
                    items.push({ text: text, heading: isHeading });
                }
            });
        }

        if (!items.length) return;

        if (type === 'directions') {
            cooked_bulk_add_directions(items);
            closeModal();
        } else {
            cooked_bulk_add_ingredients_parsed(items);
            closeModal();
        }
    });
}

/** Returns true if an ingredient row has no meaningful field values (heading or line fields). */
function cooked_is_ingredient_block_empty($block) {
    if ($block.hasClass('cooked-ingredient-heading')) {
        return ($block.find('[data-ingredient-part="section_heading_name"]').val() || '').trim() === '';
    }
    var hasContent = false;
    $block.find('[data-ingredient-part]').each(function() {
        var part = jQuery(this).data('ingredient-part');
        if (part === 'section_heading_element') {
            return;
        }
        var $f = jQuery(this);
        var v = $f.is('select') ? $f.val() : ($f.val() || '').trim();
        if (v !== null && v !== '' && String(v).trim() !== '') {
            hasContent = true;
            return false;
        }
    });
    return !hasContent;
}

/** Plain-text direction step content from TinyMCE or textarea (HTML stripped, NBSPs normalized). */
function cooked_direction_block_content_text($block) {
    var $ta = $block.find('textarea[data-direction-part="content"]');
    if (!$ta.length) {
        return '';
    }
    var id = $ta.attr('id');
    var raw = '';
    if (id && typeof tinymce !== 'undefined' && tinymce.get(id)) {
        raw = tinymce.get(id).getContent() || '';
    } else {
        raw = $ta.val() || '';
    }
    return jQuery('<div>').html(raw).text().replace(/\u00a0/g, ' ').trim();
}

/** Returns true if a direction row is empty (no heading text, image, or body content). */
function cooked_is_direction_block_empty($block) {
    if ($block.hasClass('cooked-direction-heading')) {
        return ($block.find('[data-direction-part="section_heading_name"]').val() || '').trim() === '';
    }
    if (($block.find('input[data-direction-part="image"]').val() || '').trim() !== '') {
        return false;
    }
    return cooked_direction_block_content_text($block) === '';
}

/** Removes ingredient rows that are empty before bulk-adding new items. */
function cooked_bulk_remove_empty_ingredient_rows() {
    jQuery('#cooked-ingredients-builder').children('.cooked-ingredient-block').each(function() {
        var $b = jQuery(this);
        if (cooked_is_ingredient_block_empty($b)) {
            $b.remove();
        }
    });
}

/** Removes empty direction rows before bulk-adding, and removes WP editors when needed. */
function cooked_bulk_remove_empty_direction_rows() {
    var canRemoveWpEditor = !!(
        cooked_admin_functions_js_vars.wp_editor_roles_allowed &&
        typeof wp !== 'undefined' &&
        wp.editor &&
        typeof wp.editor.remove === 'function'
    );

    jQuery('#cooked-directions-builder').children('.cooked-direction-block').each(function() {
        var $b = jQuery(this);
        if (!cooked_is_direction_block_empty($b)) {
            return;
        }
        if (canRemoveWpEditor) {
            var $ta = $b.find('textarea[data-direction-part="content"]');
            var fieldID = $ta.attr('id');
            if (fieldID) {
                wp.editor.remove(fieldID);
            }
        }
        $b.remove();
    });
}

/** Appends direction rows from bulk-add items (headings or plain steps) and resets the builder. */
function cooked_bulk_add_directions(items) {
    cooked_bulk_remove_empty_direction_rows();

    var $_builder = jQuery('#cooked-directions-builder');
    var $_parent = $_builder.parent();

    for (var i = 0; i < items.length; i++) {
        var item = items[i];

        if (item.heading) {
            var $heading = $_parent.find('.cooked-heading-template').clone()
                .removeClass('cooked-template cooked-heading-template')
                .addClass('cooked-direction-block cooked-direction-heading');
            $heading.find('[data-direction-part="section_heading_name"]').val(item.text);
            $_builder.append($heading);
        } else {
            var $direction = $_parent.find('.cooked-direction-template').clone()
                .removeClass('cooked-template cooked-direction-template')
                .addClass('cooked-direction-block');
            $direction.find('[data-direction-part="content"]').val(item.text);
            $_builder.append($direction);
        }
    }

    cooked_reset_direction_builder();
}

/** Appends ingredient rows from bulk-add items (headings or amount/unit/name) and resets the builder. */
function cooked_bulk_add_ingredients_parsed(items) {
    cooked_bulk_remove_empty_ingredient_rows();

    var $_builder = jQuery('#cooked-ingredients-builder');
    var $_parent = $_builder.parent();

    for (var i = 0; i < items.length; i++) {
        var item = items[i];

        if (item.heading) {
            var $heading = $_parent.find('.cooked-heading-template').clone()
                .removeClass('cooked-template cooked-heading-template')
                .addClass('cooked-ingredient-block cooked-ingredient-heading');
            $heading.find('[data-ingredient-part="section_heading_name"]').val(item.text);
            $_builder.append($heading);
        } else {
            var $ingredient = $_parent.find('.cooked-ingredient-template').clone()
                .removeClass('cooked-template cooked-ingredient-template')
                .addClass('cooked-ingredient-block');

            $ingredient.find('[data-ingredient-part="amount"]').val(item.amount || '');

            if (item.measurement) {
                $ingredient.find('[data-ingredient-part="measurement"]').val(item.measurement);
            }

            $ingredient.find('[data-ingredient-part="name"]').val(item.name || '');

            $_builder.append($ingredient);
        }
    }

    cooked_reset_ingredient_builder();
}
