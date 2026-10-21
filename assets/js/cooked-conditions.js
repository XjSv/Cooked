/**
 * Shows and hides settings that depend on other settings.
 *
 * A checkbox or radio with data-cooked-condition-value="name" sets the flag "name"
 * while it is checked. An element with data-cooked-condition="a b" is shown only
 * while every listed flag is set.
 */
;(function ( $, Backbone, _ ) {

    var CookedConditionsView = Backbone.View.extend({

        events: {
            'change [data-cooked-condition-value]': 'readFlags'
        },

        initialize: function() {
            this.model = new Backbone.Model();
            this.readFlags();
            this.render( false );
            this.listenTo( this.model, 'change', function() {
                this.render( true );
            } );
        },

        readFlags: function() {
            var flags = {};

            this.$( '[data-cooked-condition-value]' ).each( function() {
                var name = $( this ).data( 'cooked-condition-value' );
                flags[ name ] = flags[ name ] || this.checked;
            } );

            this.model.set( flags );
        },

        render: function( animate ) {
            var flags = this.model.attributes;

            this.$( '[data-cooked-condition]' ).each( function() {
                var $element = $( this ),
                    names = String( $element.data( 'cooked-condition' ) ).split( ' ' ),
                    met = _.every( names, function( name ) {
                        return !! flags[ name ];
                    } );

                if ( ! animate ) {
                    $element.toggle( met );
                } else if ( met ) {
                    $element.stop( true, true ).fadeIn( 250 );
                } else {
                    $element.stop( true, true ).hide();
                }
            } );
        }
    });

    $( function() {
        $( '#cooked-settings-panel' ).each( function() {
            new CookedConditionsView( { el: this } );
        } );
    } );

})( jQuery, Backbone, _ );
