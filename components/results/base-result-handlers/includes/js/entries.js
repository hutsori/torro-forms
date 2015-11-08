(function ( exports, $ ) {

    /**
     * Form_Builder constructor
     */
    function AF_FB_Entry() {
        this.selectors = {
            show_entry: '.af-show-entry',
            hide_entry: '.af-hide-entry',
            entries_table: '#af-entries-table',
            entry: '#af-entry',
            entries_slider: '.af-entries-slider',
            entries_slider_right: '.af-slider-right'
        };
    }

    /**
     * AF_Entry class
     */
    AF_FB_Entry.prototype = {
        init: function () {
            this.init_show_entry();
            this.init_hide_entry();
        },
        /**
         * Shows clicked Entry
         */
        init_show_entry: function () {
            var self = this;

            $(self.selectors.show_entry).on('click', function () {
                var $button = $(this);

                if ($button.hasClass('button')) {

                    var result_id = $button.attr( 'rel' );

                    var data = {
                        action: 'af_show_entry',
                        form_id: self.get_form_id(),
                        result_id: result_id
                    };

                    $button.addClass('button-loading');

                    $.post(ajaxurl, data, function (response) {
                        var html = response;

                        $( self.selectors.entries_slider_right ).html( html );
                        $( self.selectors.entries_slider ).animate({marginLeft: "-100%"});

                        $button.removeClass('button-loading');

                        self.init_hide_entry();
                    });

                } else {
                    $button.addClass('button');
                }
            });
        },
        init_hide_entry: function () {
            var self = this;

            $(self.selectors.hide_entry).on('click', function () {
                console.log( self.selectors.hide_entry );
                $( self.selectors.entries_slider ).animate({marginLeft: "0"});
            });
        },
        /**
         * Returns the current form ID
         */
        get_form_id: function() {
            return $( '#post_ID' ).val();
        }
    };

    var af_fb_entry = new AF_FB_Entry();

    $( document ).ready( function() {
        af_fb_entry.init();
    });

}( window, jQuery) );