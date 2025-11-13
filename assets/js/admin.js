// ResetCraft admin interactions.
 
( function ( $ ) {
	'use strict';

	function handleConfirmation( event, message ) {
		const $form = $( event.currentTarget );
		const confirmationInput = $form.find( 'input[name="resetcraft_confirmation"]' );

		if ( confirmationInput.length <= 0 ) {
			return;
		}

		const value = confirmationInput.val().trim().toUpperCase();

		if ( 'RESET' !== value ) {
			event.preventDefault();
			window.alert( window.resetcraftAdmin?.confirmSelective ?? message ); // eslint-disable-line no-alert
			confirmationInput.focus();
			return;
		}

		const promptMessage = message || window.resetcraftAdmin?.confirmSelective;

		if ( promptMessage && ! window.confirm( promptMessage ) ) { // eslint-disable-line no-alert
			event.preventDefault();
		}
	}

	$( document ).ready( function () {
		const $fullForm = $( '.resetcraft-form--full' );
		const $selectiveForm = $( '.resetcraft-form--selective' );

		$fullForm.on( 'submit', function ( event ) {
			handleConfirmation(
				event,
				window.resetcraftAdmin?.confirmFullReset ||
					'This will remove all data and restore defaults. Type RESET to continue.'
			);
		} );

		$selectiveForm.on( 'submit', function ( event ) {
			const $checked = $selectiveForm.find( 'input[type="checkbox"]:checked' );

			if ( $checked.length === 0 ) {
				event.preventDefault();
				window.alert( 'Please choose at least one operation to run.' ); // eslint-disable-line no-alert
				return;
			}

			handleConfirmation(
				event,
				window.resetcraftAdmin?.confirmSelective ||
					'Selected data will be permanently deleted. Type RESET to continue.'
			);
		} );
	} );
}( window.jQuery ));

