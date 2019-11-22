( function () {
	// eslint-disable-next-line no-jquery/no-global-selector
	var $protectionIndicators = $( '.protection-indicator-icon' );
	$protectionIndicators.each( function () {
		var protectionExplanation, icon = OO.ui.infuse( this ), htmlContent, htmlfooter;
		htmlContent = $( '<div>' ).addClass( 'protection-indicator-help-text' ).html( icon.getLabel() );
		htmlfooter = $( '<div>' ).addClass( 'protection-indicator-footer' )
			.append(
				$( '<a>' ).addClass( 'protection-indicator-log-link' )
					.attr( 'href',
						mw.util.getUrl( 'Special:Log', { type: 'protect', page: mw.config.get( 'wgPageName' ) } ) )
					.text( mw.msg( 'protection-indicator-protection-log-link-text' ) )
			);
		function showProtectionExplanation( e ) {
			e.preventDefault();
			if ( !protectionExplanation ) {
				protectionExplanation = new OO.ui.PopupWidget( {
					$content: $( '<div>' ).html( htmlContent ).append( htmlfooter ),
					padded: true,
					anchor: true,
					autoClose: true,
					$autoCloseIgnore: e.target,
					width: 500
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
