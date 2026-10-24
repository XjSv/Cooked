/*
Cooked Javascript Functions

Contents:
1. Variables
2a. Cooked Gallery Callback
2b. Cooked Gallery Init
3. Ingredients
4. Servings Switcher
5. Browse Search Button
6. Timers
7. Full-Screen Mode
*/

var cooked_loading = false;

(function($) {

    /****   1. Variables   ****/
    $_Cooked_Ingredient_Boxes = $('.cooked-ingredient-checkbox');
    $_Cooked_Ajax_List = $('.cooked-recipe-loader');
    $_Cooked_Recipe_Search = $('.cooked-recipe-search');
    $_Cooked_Timers = $('.cooked-timer > a');
    $_Cooked_FSM_Button = $('.cooked-fsm-button');

    $(document).ready(function() {

        /****   3. Ingredients   ****/

        if ( $_Cooked_Ingredient_Boxes.length ) {

            init_cooked_ingredients($_Cooked_Ingredient_Boxes);
        }

        function init_cooked_ingredients( Cooked_Ingredient_Boxes ){
            Cooked_Ingredient_Boxes.on( 'click',function(e){
                var thisCheckbox = $(this);
                if ( thisCheckbox.hasClass( 'cooked-checked' ) ){
                    thisCheckbox.parent().removeClass( 'cooked-checked' );
                    thisCheckbox.removeClass( 'cooked-checked' );
                } else {
                    thisCheckbox.parent().addClass( 'cooked-checked' );
                    thisCheckbox.addClass( 'cooked-checked' );
                }
            });
        }

        /****   4. Servings Switcher   ****/

    	if ( $('.cooked-servings').length ) {
    		var servingsSelectField = $('.cooked-servings').find('select');
    		servingsSelectField.on('change', function(e) {
    			e.preventDefault();
    			var servings = $(this).children("option:selected").val();
    			const url = new URL(window.location.href);
                url.searchParams.set('servings', servings);
                window.location.href = url.toString();
    		});
    	}

        /****   4b. Measurement System Switcher   ****/

        if ( $('.cooked-measurement-system').length ) {
            var msSelectField = $('.cooked-measurement-system').find('select');
            msSelectField.on('change', function(e) {
                e.preventDefault();
                var ms = $(this).children("option:selected").val();
                const url = new URL(window.location.href);
                if ( ms ) {
                    url.searchParams.set('measurement_system', ms);
                } else {
                    url.searchParams.delete('measurement_system');
                }
                window.location.href = url.toString();
            });
        }

        /****   5. Browse Search Button   ****/

        if ( $_Cooked_Recipe_Search.length ) {
           $('body').on( 'click',function(e) {
                var thisButton = false;

                // Did someone click the Browse Button in one way or another?
                if ( $('.cooked-browse-select').has(e.target).length > 0 ) {
                    thisButton = $(e.target).parents('.cooked-browse-select');
                } else if ( $(e.target).hasClass('cooked-browse-select') ) {
                    thisButton = $(e.target);
                }

                // Yep, they clicked the button!
                if (thisButton) {
                    if (thisButton.hasClass('cooked-active') && $(e.target).hasClass('cooked-browse-select') || thisButton.hasClass('cooked-active') && $(e.target).hasClass('cooked-field-title')) {
                        thisButton.removeClass('cooked-active');
                    } else {
                        thisButton.addClass('cooked-active');
                    }

                // Nope, they clicked something else.
                } else {
                    $('.cooked-browse-select').removeClass('cooked-active');
                }
            });

            var browseSearchButton = $('.cooked-browse-search-button');
            browseSearchButton.on('click', function(e) {
                e.preventDefault();
                var thisButton = $(this);
                thisButton.parents('form').trigger('submit');
            });

            // Add form submit handler for the Browse Search Form
            if ( cooked_functions_js_vars.permalink_structure ) {
                $('.cooked-recipe-search form').on('submit', function(e) {
                    e.preventDefault();

                    // Get form values
                    const formValues = {
                        category: wp.sanitize.stripTags($(this).find('[name="cp_recipe_category"]').val() || ''),
                        method: wp.sanitize.stripTags($(this).find('[name="cp_recipe_cooking_method"]').val() || ''),
                        cuisine: wp.sanitize.stripTags($(this).find('[name="cp_recipe_cuisine"]').val() || ''),
                        tags: wp.sanitize.stripTags($(this).find('[name="cp_recipe_tags"]').val() || ''),
                        diet: wp.sanitize.stripTags($(this).find('[name="cp_recipe_diet"]').val() || ''),
                        search: wp.sanitize.stripTags($(this).find('[name="cooked_search_s"]').val() || ''),
                        sort: wp.sanitize.stripTags($(this).find('[name="cooked_browse_sort_by"]').val() || 'date_desc'),
                    };

                    // Create URL segments with proper encoding
                    const urlSegments = [];

                    // Add taxonomy segments with improved encoding
                    const taxonomyFields = [
                        { value: formValues.category, prefix: cooked_functions_js_vars.recipe_category_slug },
                        { value: formValues.method, prefix: cooked_functions_js_vars.recipe_cooking_method_slug },
                        { value: formValues.cuisine, prefix: cooked_functions_js_vars.recipe_cuisine_slug },
                        { value: formValues.tags, prefix: cooked_functions_js_vars.recipe_tags_slug },
                        { value: formValues.diet, prefix: cooked_functions_js_vars.recipe_diet_slug },
                    ];

                    taxonomyFields.forEach(field => {
                        if (field.value) {
                            const safePrefix = encodeURIComponent(field.prefix);
                            const safeValue = encodeURIComponent(field.value);
                            urlSegments.push(`${safePrefix}/${safeValue}`);
                        }
                    });

                    // Add search segment
                    if (formValues.search) {
                        urlSegments.push(`search/${encodeURIComponent(formValues.search)}`);
                    }

                    // Add sort segment
                    urlSegments.push(`sort/${encodeURIComponent(formValues.sort)}`);

                    // Build URL using browse_page_url as base (includes language prefix for multilingual sites)
                    const prettyUrl = urlSegments.filter(Boolean).join('/');
                    const baseUrl = cooked_functions_js_vars.browse_page_url || cooked_functions_js_vars.site_url;
                    const finalUrl = `${baseUrl}/${prettyUrl}`.replace(/\/+/g, '/').replace(':/', '://');

                    // Navigate to URL
                    window.location.href = finalUrl;
                });
            }
        }

        /****   6. Timers   ****/
        function init_cooked_timers(Cooked_Timers) {
            Cooked_Timers.on('click', function(e) {
                e.preventDefault();

                var thisTimer = $(this),
                    timerID = 'cookedTimer-' + thisTimer.data('timer-id'),
                    totalTimers = $('#cooked-timers-wrap').find('.cooked-timer-block').length,
                    visibleClass, newHeight;

                // This timer is already here, let's just flash it.
                if ($('div#' + timerID).length) {
                    var existingTimer = $('div#' + timerID);
                    existingTimer.addClass('cooked-timer-flash');
                    setTimeout(function() {
                        existingTimer.removeClass('cooked-timer-flash');
                    }, 300);

                    return;
                } else {
                    // Only 4 timers allowed at a time.
                    if (totalTimers == 4) {
                        var timersWrap = $('#cooked-timers-wrap');
                        timersWrap.addClass('cooked-timer-attention');
                        setTimeout(function() {
                            timersWrap.removeClass('cooked-timer-attention');
                        },400);
                        return;
                    }

                    // Okay we're good to go, let's add this timer!
                    totalTimers = totalTimers + 1;
                    newHeight = totalTimers * 7.5;
                    if (thisTimer.parents('.cooked-single-direction').length) {
                        var thisStep = thisTimer.parents('.cooked-single-direction').data('step');
                    } else {
                        var thisStep = cooked_functions_i18n_js_vars.i18n_timer;
                    }

                    var Timer = {
                        id: timerID,
                        seconds: thisTimer.data('seconds'),
                        step: thisStep,
                        desc: thisTimer.data('desc')
                    };

                    // Timers wrap already there?
                    if ($('#cooked-timers-wrap').length) {
                        if (totalTimers == 1) {
                            visibleClass = ' cooked-visible';
                        } else {
                            visibleClass = '';
                        }

                        if (totalTimers > multiplesTrigger) {
                            $('#cooked-timers-wrap').addClass('cooked-multiples');
                        } else {
                            $('#cooked-timers-wrap').removeClass('cooked-multiples');
                        }

                        $('#cooked-timers-wrap').addClass('cooked-visible');
                        let timerBlock = cookedTimerBlock(Timer, visibleClass);
                        $('#cooked-timers-wrap').append(timerBlock);

                        var thisTimerObj = $('#' + Timer.id).find('.cooked-timer-obj');

                        cookedTimer(thisTimerObj, false);

                        setTimeout(function() {
                            $('#cooked-timers-wrap').css({ 'height' : newHeight + 'em' });
                            $('.cooked-timer-block').addClass('cooked-visible');
                        }, 50);
                    } else {
                        let timerWrap = $('<div>', { id: 'cooked-timers-wrap' });
                        let timerBlock = cookedTimerBlock(Timer);
                        timerWrap.append(timerBlock);

                        $('body').append(timerWrap);
                        var thisTimerObj = $('#' + Timer.id).find('.cooked-timer-obj');
                        cookedTimer(thisTimerObj, false);

                        setTimeout(function() {
                            $('#cooked-timers-wrap').addClass('cooked-visible');
                        }, 50);
                    }
                }
            });

            // The timer link is an anchor without an href, so give it button-like keyboard support.
            Cooked_Timers.on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
        }

        function cookedTimerBlock(Timer, visibleClass = 'cooked-visible') {
            // Create the second div with dynamic id and classes
            let timerBlock = $('<div>', {
                id: Timer.id,
                class: 'cooked-timer-block ' + visibleClass
            });

            // Create and append the step span
            $('<span>', {
                class: 'cooked-timer-step',
                text: Timer.step
            }).appendTo(timerBlock);

            // Create and append the description span
            $('<span>', {
                class: 'cooked-timer-desc',
                text: Timer.desc
            }).appendTo(timerBlock);

            // Create and append the timer object div
            $('<div>', {
                class: 'cooked-timer-obj',
                'data-seconds-left': Timer.seconds
            }).appendTo(timerBlock);

            // Create and append the close button
            $('<button>', {
                type: 'button',
                class: 'cooked-timer-close',
                'aria-label': cooked_functions_i18n_js_vars.i18n_timer_close
            }).append(
                $('<i>', { class: 'cooked-icon cooked-icon-close', 'aria-hidden': 'true' })
            ).appendTo(timerBlock);

            // Create and append the progress bar
            let progress = $('<div>', { class: 'cooked-progress' });
            $('<span>').appendTo(progress);
            progress.appendTo(timerBlock);

            return timerBlock;
        }

        function cookedTimerControls(timerObj) {
            var i18n = cooked_functions_i18n_js_vars;

            function control(className, label, icon) {
                var button = $('<button>', {
                    type: 'button',
                    class: 'cooked-timer-control ' + className,
                    'aria-label': label
                });

                $('<i>', {
                    class: 'cooked-icon cooked-icon-' + icon,
                    'aria-hidden': 'true'
                }).appendTo(button);

                return button;
            }

            timerObj.prepend(
                control('cooked-timer-reset', i18n.i18n_timer_reset, 'reverse'),
                control('cooked-timer-pause', i18n.i18n_timer_pause, 'pause'),
                control('cooked-timer-play', i18n.i18n_timer_play, 'play')
            );
        }

        function cookedTimer(timerObj, startPaused) {
            var timer_sound = cooked_functions_js_vars.timer_sound;
            var audio = new Audio(timer_sound);

            var thisTimerID = timerObj.parents('.cooked-timer-block').attr('id'),
                secondsLeft = timerObj.data('seconds-left'),
                parentBlock = timerObj.parents('.cooked-timer-block');

            timerObj.startTimer({
                classNames: {
                    hours: 'cooked-timer-hours',
                    minutes: 'cooked-timer-minutes',
                    seconds: 'cooked-timer-seconds',
                    clearDiv: 'cooked-timer-clearDiv',
                    timeout: 'cooked-timer-timeout'
                },
                onComplete: function() {
                    audio.play();
                    parentBlock.addClass('cooked-complete');
                    parentBlock.find('.cooked-timer-hours').text('00:');
                    parentBlock.find('.cooked-timer-minutes').text('00:');
                    parentBlock.find('.cooked-timer-seconds').text('00');
                    parentBlock.find('.cooked-progress > span').css({ 'width' : '100%' });
                }
            });

            cookedTimerControls(timerObj);

            if (startPaused){
                timerObj.trigger('pause');
                parentBlock.addClass('cooked-paused');
            }

            cookedTimer_progress_bar( parentBlock, 10, 10 );

            timerObj.on( 'update', function( e,timeLeft ){
                cookedTimer_progress_bar( parentBlock,timeLeft,secondsLeft );
            });

            timerObj.on( 'click', '.cooked-timer-pause', function(e) {
                e.preventDefault();
                parentBlock.addClass('cooked-paused');
                timerObj.trigger('pause');
            });

            timerObj.on( 'click', '.cooked-timer-play', function(e) {
                e.preventDefault();
                parentBlock.removeClass('cooked-paused cooked-complete');
                timerObj.trigger('resume');
            });

            timerObj.on( 'click', '.cooked-timer-reset', function(e) {
                e.preventDefault();
                parentBlock.removeClass('cooked-paused cooked-complete');
                timerObj.remove();
                $( '#' + thisTimerID ).append('<div class="cooked-timer-obj" data-seconds-left="' + secondsLeft + '"></div>');
                var newTimer = $( '#' + thisTimerID ).find( '.cooked-timer-obj' );
                cookedTimer( newTimer, true );
            });

            // Rebind, since a reset calls this function again on the same block.
            parentBlock.off( 'click.cookedTimerClose', '.cooked-timer-close' ).on( 'click.cookedTimerClose', '.cooked-timer-close', function(e) {
                e.preventDefault();

                if ( $('#cooked-timers-wrap').find('.cooked-timer-block').length == 1 ){
                    $('#cooked-timers-wrap').removeClass('cooked-visible');
                } else {
                    var totalTimers = $('#cooked-timers-wrap').find('.cooked-timer-block').length - 1;
                    var newHeight = totalTimers * 7.5;
                    $('#cooked-timers-wrap').css({ 'height' : newHeight + 'em' });
                    parentBlock.removeClass('cooked-visible');
                    if ( totalTimers == multiplesTrigger ){
                        $('#cooked-timers-wrap').removeClass('cooked-multiples');
                    }
                }

                setTimeout(function(){
                    parentBlock.remove();
                },200);
            });

        }

        function cookedTimer_progress_bar( container,remaining_time,total_time ){
            var progressPercent = 100 - ( ( remaining_time / total_time ) * 100 );
            container.find('.cooked-progress > span').css({ 'width' : progressPercent + '%' });
            if ( progressPercent >= 100 ){
                container.addClass('cooked-complete');
            }
        }

        if ($_Cooked_Timers.length){
            // How many timers to show before it moves to the right side of the screen?
            var multiplesTrigger = 1;
            init_cooked_timers($_Cooked_Timers);
        }

        /****   7. Full-Screen Mode   ****/

        if ($_Cooked_FSM_Button.length) {
            var noSleep = new NoSleep();

            $_Cooked_FSM_Button.on('click', function(e) {
                e.preventDefault();

                var recipe_id = $(this).data('recipe-id'),
                    FSM_Container = $('.cooked-fsm[data-recipe-id="' + recipe_id + '"]');

                $('body').addClass('cooked-noscroll cooked-fsm-active');

                var New_FSM_Container = FSM_Container.clone().appendTo('body');

                setTimeout(function() {
                    New_FSM_Container.addClass('cooked-visible');
                }, 10);

                setTimeout(function() {
                    New_FSM_Container.addClass('cooked-active');
                }, 50);

                var Cooked_Timers = New_FSM_Container.find('.cooked-timer > a');
                var Cooked_Ingredient_Boxes = New_FSM_Container.find('.cooked-ingredient-checkbox');
                init_cooked_timers(Cooked_Timers);
                init_cooked_ingredients(Cooked_Ingredient_Boxes);

                // Enable wake lock aka "Hands Free Cooking Mode" (Keeps the screen from going dark).
                noSleep.enable();

                New_FSM_Container.on('click', '.cooked-close-fsm', function(e) {
                    e.preventDefault();
                    New_FSM_Container.removeClass('cooked-active');
                    $('body').removeClass('cooked-noscroll cooked-fsm-active');

                    // Disable wake lock aka "Hands Free Cooking Mode".
                    noSleep.disable();

                    setTimeout(function() {
                        New_FSM_Container.remove();
                    }, 350);
                });
            });

            $('body').on('click', '.cooked-fsm-mobile-nav > a', function(e) {
                e.preventDefault();

                var thisButton = $(this),
                    nav_id = thisButton.data('nav-id'),
                    FSM_Container = thisButton.parents('.cooked-fsm');

                FSM_Container.find('.cooked-fsm-mobile-nav > a').removeClass('cooked-active');
                FSM_Container.find('.cooked-fsm-content').removeClass('cooked-active');

                thisButton.addClass('cooked-active');

                if (nav_id == 'ingredients') {
                    FSM_Container.find('.cooked-fsm-content.cooked-fsm-ingredients').addClass('cooked-active');
                } else {
                    FSM_Container.find('.cooked-fsm-content.cooked-fsm-directions-wrap').addClass('cooked-active');
                    FSM_Container.find('.cooked-fsm-content.cooked-fsm-directions').addClass('cooked-active');
                    FSM_Container.find('.cooked-fsm-content.cooked-fsm-notes').addClass('cooked-active');
                }
            });
        }
	});

})( jQuery );
