( function () {
	// eslint-disable-next-line no-jquery/no-global-selector
	const $protectionIndicators = $( '.protectionindicator-icon' );
	$protectionIndicators.each( function () {
		let protectionExplanation, icon = OO.ui.infuse( this ), $htmlContent;
		function showProtectionExplanation( e ) {
			e.preventDefault();
			if ( !protectionExplanation ) {
				protectionExplanation = new OO.ui.PopupWidget( {
					$content: $( '<div>' ).html( $htmlContent ),
					padded: true,
					anchor: true,
					autoClose: true,
					$autoCloseIgnore: e.target,
					width: ( window.screen.width > 600 ) ? 600 : 320,
					// Mainly for viewer who will veiw this on mobile
					classes: [ 'protectionindicator-popup' ]
				} );
				$( e.target ).after( protectionExplanation.$element );
			}
			if ( !protectionExplanation.isVisible() ) {
				protectionExplanation.toggle( true );
			} else {
				protectionExplanation.toggle( false );
			}
		}
		$htmlContent = $( '<div>' ).addClass( 'protectionindicator-help-text' ).html( icon.getLabel() );
		icon.$element.on( 'click', ( e ) => {
			showProtectionExplanation( e );
		} );
	} );
}() );
