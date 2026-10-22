;(function ( $, window, document, undefined ) {

    $('body').on( 'change', '.cooked-widget-conditional', function() {
        var thisConditional = $(this);
        cooked_widget_conditional_init( thisConditional );
    });

    $('body').on( 'click', '.cooked-recipe-finder-show', function(e) {
        e.preventDefault();
        var thisFinderShowLink = $(this),
            thisFinderID = thisFinderShowLink.attr('id').split( '-SHOW' )[0],
            thisFinder = document.getElementById( thisFinderID );

        if ( ! thisFinder || thisFinder.tomselect ) {
            return;
        }

        thisFinderShowLink.hide();

        new TomSelect( thisFinder, {
            plugins: ['drag_drop'],
            valueField: 'id',
            labelField: 'title',
            searchField: 'title',
            maxItems: 25,
            maxOptions: 5,
            persist: false,
            hideSelected: true,
            closeAfterSelect: true,
            openOnFocus: false,
            onInitialize: function() {
                this.focus();
            },
            render: {
                option: function(item, escape) {
                    return '<div>' +
                        '<span class="title">' +
                            '<span class="name">' + escape( item.title ) + '</span>' +
                        '</span>' +
                    '</div>';
                }
            },
            load: function (query, callback) {
                $.ajax({
                    url: cooked_widgets_js_vars.rest_url + 'wp/v2/cooked_recipe/',
                    type: 'GET',
                    // Authenticated, so editors still find recipes when public recipes are disabled.
                    headers: { 'X-WP-Nonce': cooked_widgets_js_vars.rest_nonce },
                    dataType: 'json',
                    data: {
                        search: query,
                        per_page: 10
                    },
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        callback( $.map( res, function( recipe ) {
                            return { id: recipe.id, title: recipe.title.rendered };
                        } ) );
                    }
                });
            }
        });
    });

})(jQuery, window, document);

function cooked_widget_conditional_init(thisConditional) {
    thisConditional_ID = thisConditional.attr('id');
    thisConditional_VAL = jQuery('#' + thisConditional_ID + ' option:selected').val();

    jQuery( 'body' ).find( '[data-condition="' + thisConditional_ID + '"]' ).each(function(){
        var thisValue = jQuery(this).data('value');
        if ( thisValue == thisConditional_VAL ){
            jQuery(this).fadeIn( 250 );
        } else {
            jQuery(this).hide();
        }
    });

}
