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

            // Desktop: Tom Select, showing the current servings ("4 Servings") as the value.
            // Touch devices keep the native picker laid over the link.
            // On DOM ready: Tom Select is enqueued with the switcher and can print after this file.
            $(function() {
                if ( window.TomSelect && ! window.matchMedia('(pointer: coarse)').matches ) {
                    servingsSelectField.each(function() {
                        var select = this,
                            wrap = select.closest('.cooked-servings'),
                            current = wrap ? wrap.querySelector('a') : null,
                            currentLabel = current ? current.textContent : '';

                        if ( select.tomselect ) {
                            return;
                        }

                        var tomSelect = new window.TomSelect(select, {
                            maxOptions: null,
                            controlInput: null,
                            dropdownParent: 'body',
                            render: {
                                item: function(data, escape) {
                                    return '<div>' + escape(currentLabel || data.text) + '</div>';
                                }
                            }
                        });

                        tomSelect.dropdown.classList.add('cooked-servings-dropdown');
                        if ( wrap ) {
                            wrap.classList.add('is-enhanced');
                        }
                    });
                }
            });

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

        /****   5. Browse Search & Filters   ****/

        if ( $_Cooked_Recipe_Search.length ) {
            var browseResultsSelector = '[data-cooked-results]';
            var browseRequest         = null;
            var browseRequestId       = 0;
            var browseDesiredUrl      = null;
            var browseCategoryTimer   = null;
            var browseSearchTimer     = null;

            function cookedStripTags(value) {
                return wp.sanitize.stripTags(String(value || ''));
            }

            function cookedBrowseResults() {
                return document.querySelector(browseResultsSelector);
            }

            function cookedBrowseForm(element) {
                if (element) {
                    return element.closest('form');
                }

                return document.querySelector('.cooked-recipe-search form');
            }

            function cookedEncodeTaxonomyValue(value) {
                return String(value || '')
                    .split(',')
                    .map(function(term) {
                        return encodeURIComponent(term.trim());
                    })
                    .filter(Boolean)
                    .join(',');
            }

            function cookedBrowseUrl(form) {
                var $form      = $(form);
                var categories = $form.find('input[name="cooked_category_filter[]"]:checked').map(function() {
                    return this.value;
                }).get();
                var values = {
                    category: categories.length ? categories.join(',') : cookedStripTags($form.find('[name="cp_recipe_category"]').val() || ''),
                    method: cookedStripTags($form.find('[name="cp_recipe_cooking_method"]').val() || ''),
                    cuisine: cookedStripTags($form.find('[name="cp_recipe_cuisine"]').val() || ''),
                    tags: cookedStripTags($form.find('[name="cp_recipe_tags"]').val() || ''),
                    diet: cookedStripTags($form.find('[name="cp_recipe_diet"]').val() || ''),
                    search: cookedStripTags($form.find('[name="cooked_search_s"]').val() || ''),
                    sort: cookedStripTags($form.find('[name="cooked_browse_sort_by"]').val() || 'date_desc'),
                };

                // Without pretty permalinks, let WordPress read the form fields from the query string.
                if (!cooked_functions_js_vars.permalink_structure) {
                    var queryUrl = new URL(form.action, window.location.origin);
                    var params   = new URLSearchParams();

                    queryUrl.search = '';
                    new FormData(form).forEach(function(value, key) {
                        params.append(key, value);
                    });
                    queryUrl.search = params.toString();

                    return queryUrl.toString();
                }

                var segments       = [];
                var taxonomyFields = [
                    { value: values.category, prefix: cooked_functions_js_vars.recipe_category_slug, multiple: true },
                    { value: values.method, prefix: cooked_functions_js_vars.recipe_cooking_method_slug },
                    { value: values.cuisine, prefix: cooked_functions_js_vars.recipe_cuisine_slug },
                    { value: values.tags, prefix: cooked_functions_js_vars.recipe_tags_slug },
                    { value: values.diet, prefix: cooked_functions_js_vars.recipe_diet_slug },
                ];

                taxonomyFields.forEach(function(field) {
                    if (!field.value) {
                        return;
                    }

                    var safeValue = field.multiple ? cookedEncodeTaxonomyValue(field.value) : encodeURIComponent(field.value);
                    segments.push(encodeURIComponent(field.prefix) + '/' + safeValue);
                });

                if (values.search) {
                    segments.push('search/' + encodeURIComponent(values.search));
                }

                segments.push('sort/' + encodeURIComponent(values.sort));

                // Build URL using browse_page_url as base (includes language prefix for multilingual sites).
                var baseUrl = cooked_functions_js_vars.browse_page_url || cooked_functions_js_vars.site_url;

                return (baseUrl + '/' + segments.join('/')).replace(/\/+/g, '/').replace(':/', '://');
            }

            function cookedNormalizeUrl(url) {
                try {
                    var parsed = new URL(url, window.location.origin);
                    parsed.hash = '';
                    return parsed.href.replace(/\/+$/, '');
                } catch (error) {
                    return String(url || '');
                }
            }

            function cookedBrowseStatus(message) {
                var status = document.querySelector('.cooked-recipe-search .cooked-browse-status');

                if (!status) {
                    return;
                }

                status.textContent = '';
                window.setTimeout(function() {
                    status.textContent = message;
                }, 50);
            }

            function cookedAnnounceResults(results) {
                var i18n  = cooked_functions_i18n_js_vars || {};
                var count = parseInt(results.getAttribute('data-cooked-recipe-count'), 10);

                if (isNaN(count)) {
                    count = results.querySelectorAll('.cooked-recipe').length;
                }

                if (count < 1) {
                    cookedBrowseStatus(i18n.i18n_browse_no_results || 'No recipes found.');
                    return;
                }

                if (1 === count) {
                    cookedBrowseStatus(i18n.i18n_browse_results_one || '1 recipe found.');
                    return;
                }

                cookedBrowseStatus((i18n.i18n_browse_results_many || '%s recipes found.').replace('%s', count));
            }

            function cookedSyncBrowseControls(doc) {
                var currentBoxes   = document.querySelectorAll('.cooked-recipe-search input[name="cooked_category_filter[]"]');
                var incomingBoxes  = doc.querySelectorAll('.cooked-recipe-search input[name="cooked_category_filter[]"]');
                var checkedByValue = {};

                incomingBoxes.forEach(function(input) {
                    if (input.checked) {
                        checkedByValue[input.value] = true;
                    }
                });

                currentBoxes.forEach(function(input) {
                    input.checked = Boolean(checkedByValue[input.value]);
                });

                var currentSearch  = document.querySelector('.cooked-recipe-search .cooked-browse-search');
                var incomingSearch = doc.querySelector('.cooked-recipe-search .cooked-browse-search');

                // Never overwrite a search field the visitor is still typing in.
                if (currentSearch && incomingSearch && document.activeElement !== currentSearch) {
                    currentSearch.value = incomingSearch.value;
                }

                var currentSort  = document.querySelector('.cooked-recipe-search .cooked-sortby-select');
                var incomingSort = doc.querySelector('.cooked-recipe-search .cooked-sortby-select');

                if (currentSort && incomingSort) {
                    currentSort.value = incomingSort.value;

                    if (currentSort.tomselect) {
                        currentSort.tomselect.setValue(incomingSort.value, true);
                    }
                }

                var currentWrap    = document.querySelector('.cooked-recipe-search .cooked-field-wrap-select');
                var incomingWrap   = doc.querySelector('.cooked-recipe-search .cooked-field-wrap-select');
                var currentTitle   = currentWrap ? currentWrap.querySelector('.cooked-field-title') : null;
                var incomingTitle  = incomingWrap ? incomingWrap.querySelector('.cooked-field-title') : null;
                var currentButton  = currentWrap ? currentWrap.querySelector('.cooked-browse-select') : null;
                var incomingButton = incomingWrap ? incomingWrap.querySelector('.cooked-browse-select') : null;

                if (currentWrap && incomingWrap) {
                    currentWrap.classList.toggle('cooked-taxonomy-selected', incomingWrap.classList.contains('cooked-taxonomy-selected'));
                }

                if (currentTitle && incomingTitle) {
                    currentTitle.textContent = incomingTitle.textContent;
                }

                if (currentButton && incomingButton && incomingButton.getAttribute('aria-label')) {
                    currentButton.setAttribute('aria-label', incomingButton.getAttribute('aria-label'));
                }

                var currentClear  = document.querySelector('.cooked-recipe-search .cooked-filter-all');
                var incomingClear = doc.querySelector('.cooked-recipe-search .cooked-filter-all');

                if (currentClear) {
                    currentClear.classList.toggle('cooked-filter-all-hidden', !incomingClear || incomingClear.classList.contains('cooked-filter-all-hidden'));
                }
            }

            function cookedSetBrowseLoading(isLoading) {
                var results = cookedBrowseResults();

                if (!results) {
                    return;
                }

                results.classList.toggle('cooked-is-loading', isLoading);
                results.setAttribute('aria-busy', isLoading ? 'true' : 'false');
            }

            function cookedLoadBrowse(url, options) {
                options = options || {};

                var results = cookedBrowseResults();
                var form    = cookedBrowseForm();

                if (!results || !form || !window.fetch || !window.DOMParser || !window.URLSearchParams || !window.AbortController) {
                    window.location.href = url;
                    return;
                }

                var target = cookedNormalizeUrl(url);

                // A newer checkbox change is still waiting for its debounce.
                if (browseDesiredUrl && browseDesiredUrl !== target) {
                    return;
                }

                if (options.history === 'push' && target !== cookedNormalizeUrl(window.location.href)) {
                    window.history.pushState({ cookedBrowse: true }, '', url);
                } else if (options.history === 'replace' && target !== cookedNormalizeUrl(window.location.href)) {
                    window.history.replaceState({ cookedBrowse: true }, '', url);
                }

                // A sort change already triggers the form submit event; do not fetch the same results twice.
                if (results.getAttribute('data-cooked-url') === target) {
                    return;
                }

                if (browseRequest) {
                    browseRequest.abort();
                }

                browseRequest = new AbortController();

                var requestId = ++browseRequestId;
                var signal    = browseRequest.signal;
                var i18n      = cooked_functions_i18n_js_vars || {};

                cookedSetBrowseLoading(true);
                cookedBrowseStatus(i18n.i18n_browse_loading || 'Loading recipes…');

                fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: signal
                })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }

                        return response.text();
                    })
                    .then(function(html) {
                        // Ignore a stale response while a newer filter change is pending.
                        if (requestId !== browseRequestId || (browseDesiredUrl && browseDesiredUrl !== target)) {
                            return;
                        }

                        var doc  = new DOMParser().parseFromString(html, 'text/html');
                        var next = doc.querySelector(browseResultsSelector);

                        if (!next) {
                            throw new Error('No recipe results in response.');
                        }

                        var imported = document.importNode(next, true);

                        imported.setAttribute('data-cooked-url', target);
                        imported.classList.remove('cooked-is-loading');
                        imported.setAttribute('aria-busy', 'false');

                        results.replaceWith(imported);
                        cookedSyncBrowseControls(doc);
                        cookedAnnounceResults(imported);
                        browseDesiredUrl = null;
                    })
                    .catch(function(error) {
                        if (error && error.name === 'AbortError') {
                            return;
                        }

                        if (requestId !== browseRequestId) {
                            return;
                        }

                        browseDesiredUrl = null;
                        cookedSetBrowseLoading(false);
                        window.location.href = url;
                    });
            }

            $('body').on('click', '.cooked-browse-select', function(e) {
                e.preventDefault();

                var thisButton = $(this),
                    isOpen     = thisButton.attr('aria-expanded') === 'true';

                $('.cooked-browse-select').not(thisButton).removeClass('cooked-active').attr('aria-expanded', 'false');

                thisButton.toggleClass('cooked-active', !isOpen).attr('aria-expanded', isOpen ? 'false' : 'true');
            });

            $('body').on('click', function(e) {
                if (!$(e.target).closest('.cooked-field-wrap-select').length) {
                    $('.cooked-browse-select').removeClass('cooked-active').attr('aria-expanded', 'false');
                }
            });

            $('body').on('keydown', function(e) {
                if (e.key !== 'Escape') {
                    return;
                }

                var openButton = $('.cooked-browse-select[aria-expanded="true"]').first();

                if (openButton.length) {
                    openButton.removeClass('cooked-active').attr('aria-expanded', 'false').trigger('focus');
                }
            });

            // The submit event is dispatched directly in tests, so bind it directly instead of delegating.
            $('.cooked-recipe-search form').on('submit', function(e) {
                if (!cookedBrowseResults()) {
                    return;
                }

                e.preventDefault();

                var url = cookedBrowseUrl(this);

                window.clearTimeout(browseCategoryTimer);
                window.clearTimeout(browseSearchTimer);
                browseDesiredUrl = cookedNormalizeUrl(url);
                cookedLoadBrowse(url, { history: 'push' });
            });

            $('body').on('input', '.cooked-recipe-search .cooked-browse-search', function(e) {
                var form = cookedBrowseForm(this);

                if (!form || !cookedBrowseResults()) {
                    return;
                }

                var originalEvent = e.originalEvent || e;

                // Wait until IME composition (e.g. Japanese) has finished.
                if (originalEvent.isComposing) {
                    return;
                }

                var url = cookedBrowseUrl(form);

                window.clearTimeout(browseSearchTimer);
                browseDesiredUrl = cookedNormalizeUrl(url);
                browseSearchTimer = window.setTimeout(function() {
                    // Typing replaces the current history entry; Enter remains a deliberate navigation.
                    cookedLoadBrowse(url, { history: 'replace' });
                }, 300);
            });

            $('body').on('change', '.cooked-recipe-search input[name="cooked_category_filter[]"]', function() {
                var form = cookedBrowseForm(this);

                if (!form || !cookedBrowseResults()) {
                    return;
                }

                var url = cookedBrowseUrl(form);

                window.clearTimeout(browseCategoryTimer);
                window.clearTimeout(browseSearchTimer);
                browseDesiredUrl = cookedNormalizeUrl(url);
                browseCategoryTimer = window.setTimeout(function() {
                    cookedLoadBrowse(url, { history: 'push' });
                }, 200);
            });

            $('body').on('change', '.cooked-recipe-search .cooked-sortby-select', function() {
                var form = cookedBrowseForm(this);

                if (!form || !cookedBrowseResults()) {
                    return;
                }

                var url = cookedBrowseUrl(form);

                window.clearTimeout(browseCategoryTimer);
                window.clearTimeout(browseSearchTimer);
                browseDesiredUrl = cookedNormalizeUrl(url);
                cookedLoadBrowse(url, { history: 'push' });
            });

            $('body').on('click', '.cooked-recipe-search .cooked-filter-all', function(e) {
                var form = cookedBrowseForm(this);

                if (!form || !cookedBrowseResults()) {
                    return;
                }

                e.preventDefault();
                window.clearTimeout(browseCategoryTimer);
                window.clearTimeout(browseSearchTimer);
                $(form).find('input[name="cooked_category_filter[]"]').prop('checked', false);

                var url = cookedBrowseUrl(form);

                browseDesiredUrl = cookedNormalizeUrl(url);
                cookedLoadBrowse(url, { history: 'push' });
            });

            $('body').on('click', '[data-cooked-filter-chip]', function(e) {
                var form  = cookedBrowseForm();
                var field = this.getAttribute('data-field') || this.getAttribute('data-taxonomy');
                var term  = this.getAttribute('data-term');

                if (!form || !field || !term || !cookedBrowseResults()) {
                    return;
                }

                var input = form.querySelector('input[name="' + field + '[]"][value="' + term + '"]');

                if (!input) {
                    return;
                }

                e.preventDefault();
                window.clearTimeout(browseCategoryTimer);
                window.clearTimeout(browseSearchTimer);
                input.checked = false;

                var url = cookedBrowseUrl(form);

                browseDesiredUrl = cookedNormalizeUrl(url);
                cookedLoadBrowse(url, { history: 'push' });
            });

            window.addEventListener('popstate', function() {
                if (!cookedBrowseResults()) {
                    return;
                }

                window.clearTimeout(browseCategoryTimer);
                window.clearTimeout(browseSearchTimer);
                browseDesiredUrl = cookedNormalizeUrl(window.location.href);
                cookedLoadBrowse(window.location.href, { history: 'none' });
            });

            // Remember the URL the server rendered so repeat submit events do not fetch it again.
            $('[data-cooked-results]').each(function() {
                this.setAttribute('data-cooked-url', cookedNormalizeUrl(window.location.href));
            });

            // Desktop: Tom Select for the sort menu. Touch devices keep the native picker,
            // which suits them better (the same rule as the admin selects).
            if (window.TomSelect && !window.matchMedia('(pointer: coarse)').matches) {
                $('.cooked-recipe-search .cooked-sortby-select').each(function() {
                    var select = this;

                    if (select.tomselect) {
                        return;
                    }

                    var tomSelect = new window.TomSelect(select, {
                            allowEmptyOption: true,
                            maxOptions: null,
                            controlInput: null
                        }),
                        ariaLabel = select.getAttribute('aria-label');

                    if (ariaLabel) {
                        tomSelect.control.setAttribute('aria-label', ariaLabel);
                    }

                    if (select.parentNode) {
                        select.parentNode.classList.add('is-enhanced');
                    }
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

            // The button is a span, so Enter and Space open it as a click does.
            $_Cooked_FSM_Button.on('keydown', function(e) {
                if ('Enter' === e.key || ' ' === e.key) {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });

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
