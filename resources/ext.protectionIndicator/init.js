( function () {
	// eslint-disable-next-line no-jquery/no-global-selector
	var $protectionIndicators = $( '.protection-indicator-icon' );
	$protectionIndicators.each( function () {
		var protectionExplanation, icon = OO.ui.infuse( this ), $htmlContent, $htmlfooter, $reasonhtml = '', api = new mw.Api( {
			'user-agent': 'ProtectionIndicator-extension'
		} );
		function showProtectionExplanation( e ) {
			e.preventDefault();
			if ( !protectionExplanation ) {
				protectionExplanation = new OO.ui.PopupWidget( {
					$content: $( '<div>' ).html( $htmlContent ).append( $reasonhtml, $htmlfooter ),
					padded: true,
					anchor: true,
					autoClose: true,
					$autoCloseIgnore: e.target,
					width: 400
				} );
				$( e.target ).after( protectionExplanation.$element );
			}
			if ( !protectionExplanation.isVisible() ) {
				protectionExplanation.toggle( true );
			} else {
				protectionExplanation.toggle( false );
			}
		}
		$htmlContent = $( '<div>' ).addClass( 'protection-indicator-help-text' ).html( icon.getLabel() );
		$htmlfooter = $( '<div>' ).addClass( 'protection-indicator-footer' )
			.append(
				$( '<a>' ).addClass( 'protection-indicator-log-link' )
					.attr( 'href',
						mw.util.getUrl( 'Special:Log', { type: 'protect', page: mw.config.get( 'wgPageName' ) } ) )
					.text( mw.msg( 'protection-indicator-protection-log-link-text' ) )
			);
		if ( mw.config.get( 'ShowReasonInPopup' ) ) {
			api.get( {
				action: 'query',
				list: 'logevents',
				letype: 'protect',
				letitle: mw.config.get( 'wgTitle' ),
				leprop: 'comment|user',
				lelimit: 1
			} ).done( function ( response ) {
				if ( response.query.logevents[ 0 ] && response.query.logevents[ 0 ].comment ) {
					$reasonhtml = $( '<div>' )
						.addClass( 'protection-indicator-reason' )
						.html( mw.message( 'protection-indicator-reason-wrapper',
							response.query.logevents[ 0 ].user,
							response.query.logevents[ 0 ].comment ).parseDom() );
				}
				icon.$element.on( 'click', function ( e ) {
					showProtectionExplanation( e );
				} );
			} );// fail quietly
		} else {
			icon.$element.on( 'click', function ( e ) {
				showProtectionExplanation( e );
			} );
		}
	} );
}() );
