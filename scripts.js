var spinner = [];
$( document ).ready( function () {
	get( '.gerrit', 'gerrit.php', 'Wikimedia' );
	get( '.github', 'github.php', 'Github' );
	get( '.openrw', 'openrw.php', 'Bitbucket' );
} );

function get( selector, url, description ) {
	updateSpinner( '+', description );
	$( selector ).hide();
	$.get( url , function ( data ) {
		$( selector ).html( data );
		$( selector ).fadeIn();
		updateSpinner( '-', description );
	} );
}

function updateSpinner( polarity, description ) {
	console.log( polarity, spinner, description );
	if ( polarity == '+' ) {
		spinner.push( description );
	} else {
		var index = spinner.indexOf( description );
		if ( index > -1 ) {
			spinner.splice( index, 1 );
		}
	}
	if ( spinner.length !== 0 ) {
		$( '.loadingdesc' ).html( spinner.join( ', ' ) + '&hellip;' );
		$( '.loading' ).fadeIn();
	} else {
		$( '.loading' ).slideUp();
	}
}
