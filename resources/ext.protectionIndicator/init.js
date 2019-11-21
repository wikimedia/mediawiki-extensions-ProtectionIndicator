( function () {
	// eslint-disable-next-line no-jquery/no-global-selector
	var $protectionIndicators = $( '.protection-indicator-icon' );
	$protectionIndicators.each( function () {
		var protectionExplanation, text, icon = OO.ui.infuse( this );
		function showProtectionExplanation( e ) {
			e.preventDefault();
			text = icon.getLabel();
			if ( !protectionExplanation ) {
				protectionExplanation = new OO.ui.PopupWidget( {
					$content: $( '<div>' )
						.text( text ),
					padded: true,
					anchor: true,
					autoClose: true,
					$autoCloseIgnore: e.target
				} );
				$( e.target ).after( protectionExplanation.$element );
			}
			if ( !protectionExplanation.isVisible() ) {
				protectionExplanation.toggle( true );
			} else {
				protectionExplanation.toggle( false );
			}
		}

		icon.$element.on( 'click', function ( e ) {
			showProtectionExplanation( e );
		} );
	} );
}() );