/**
 * WordPress header section
 */

window.addEventListener( 'load', function( event ) {
	if ( typeof MicroModal === 'undefined' ) {
		return;
	}

	console.log("Init Micromodal")
	MicroModal.init( {
	openTrigger: 'data-custom-open',
	} );
} );