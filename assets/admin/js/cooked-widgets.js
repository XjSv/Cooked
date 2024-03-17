;(function ( $, window, document, undefined ) {

    $('body').on( 'change', '.cooked-widget-conditional', function(){
        var thisConditional = $(this);
        cooked_widget_conditional_init( thisConditional );
    });

    $('body').on( 'click', '.cooked-recipe-finder-show', function(e){

        e.preventDefault();
        var thisFinderShowLink = $(this),
            thisFinderShow = thisFinderShowLink.attr('id');

        thisFinderShow = thisFinderShow.split( '-SHOW' );
        thisFinderID = thisFinderShow[0];
        thisFinder = $( '#' + thisFinderID );

        thisFinderShowLink.hide();

        thisFinder.selectize({
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
            options: [],
            onInitialize: function(){
                $( '#' + thisFinderID + '-selectized' ).focus();
            },
            onItemAdd: function(){
                thisFinder.trigger('change');
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
                this.refreshItems();
                if (!query.length) return callback();
                $.ajax({
                    url: cooked_js_vars.rest_url + 'wp/v2/cooked_recipe/',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        search: query,
                        per_page: 10
                    },
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        var item = {}, items = [];
                        for (var key in res) {
                            if (!res.hasOwnProperty(key)) continue;
                            var obj = res[key];
                            for (var prop in obj) {
                                if(!obj.hasOwnProperty(prop)) continue;
                                if ( prop == 'id' ){
                                    var id = obj[prop];
                                } else if ( prop == 'title' ){
                                    var title = obj[prop].rendered;
                                }
                            }
                            item = { id: id, title: title };
                            items.push( item );
                        }
                        callback(items);
                    }
                });
            }
        });
    });

})(jQuery, window, document);

function cooked_widget_conditional_init( thisConditional ){

    thisConditional_ID = thisConditional.attr('id'),
    thisConditional_VAL = jQuery( '#' + thisConditional_ID + ' option:selected').val();

    jQuery( 'body' ).find( '[data-condition="' + thisConditional_ID + '"]' ).each(function(){
        var thisValue = jQuery(this).data('value');
        if ( thisValue == thisConditional_VAL ){
            jQuery(this).fadeIn( 250 );
        } else {
            jQuery(this).hide();
        }
    });

}
