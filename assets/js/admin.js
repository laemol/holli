/**
 * Plugin Saving process
 */
jQuery( document ).ready( function () {

    jQuery( document ).on( 'submit', '#holli-admin-form', function ( e ) {

        e.preventDefault();

        // We inject some extra fields required for the security
        jQuery(this).append('<input type="hidden" name="action" value="store_admin_data" />');
        jQuery(this).append('<input type="hidden" name="security" value="'+ holli_exchanger._nonce +'" />');

        // We make our call
        jQuery.ajax( {
            url: holli_exchanger.ajax_url,
            type: 'post',
            data: jQuery(this).serialize(),
            success: function (response) {
                location.reload();
                // alert(response); 
            }
        } );

    } );

} );