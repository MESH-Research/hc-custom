(function(){
	var handle_response = function( data ) {
		$( '#hc-bpges-disable-warning' ).closest( '.ui-dialog-content' ).dialog( 'close' );
	}

	$( '#hc-bpges-disable-warning' ).on( 'click', function( e ) {
		var data = { 'action': 'hc_custom_bpges_settings_warning' };
		$.post( ajaxurl, data, handle_response );
		e.preventDefault();
	} );
});
