<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace MediaWiki\Extension\ProtectionIndicator;

use OOUI;
use ExtensionRegistry;
use FRPageConfig;

class ProtectionIndicatorHooks {
	/**
	 * Hook to load the protection icons on article pages
	 * @param \Article $article Article object to be used
	 * @param bool &$outputDone
	 * @param bool &$pcache
	 */
	public static function onArticleViewHeader( \Article $article, &$outputDone, &$pcache ) {
		global $wgRestrictionLevels;
		$title = $article->getTitle();
		$out = $article->getContext()->getOutput();
		$config = $out->getConfig();
		if ( !$out->isArticle() && $title->isSpecialPage() ) {
			return;
		}
		if ( $article->getRevision() ) {
			$pOut = $article->getParserOutput( $article->getRevision()->getID() );
			if ( $pOut->getExtensionData( 'protection-indicator-supress-all' ) ) {
				return;
			}
		}
		// Use configurational variable to check if Wiki wants icons
		// on their main page
		// In the same condition check if the page
		// is configured to supress all protection icons
		if ( !$config->get( 'ShowIconsOnMainPage' ) && $title->isMainPage() ) {
			return;
		}
		// Check a configurational variable to see if the Wiki wants reasons
		// in the popup
		if ( $config->get( 'ShowReasonInPopup' ) ) {
			$out->addJSConfigVars( 'ShowReasonInPopup', true );
		}
		$restrictionTypes = $title->getRestrictionTypes();
		$o = new ProtectionIndicatorHooks;
		foreach ( $restrictionTypes as $action ) {
			$r = $title->getRestrictions( $action );
			$rExpiry = $title->getRestrictionExpiry( $action );
			foreach ( $wgRestrictionLevels as $level ) {
				if ( in_array( $level, $r ) ) {
					$o->createIndicator( $out, $action, $level, $rExpiry );
				}
			}
		}
		$rCascade = $title->getCascadeProtectionSources( true );
		$r = $rCascade[1];
		if ( $rCascade[0] ) {
			foreach ( $restrictionTypes as $action ) {
				if ( array_key_exists( $action, $r ) ) {
					$r = $r[$action];
					foreach ( $wgRestrictionLevels as $level ) {
						if ( in_array( $level, $r ) ) {
							$o->createIndicator( $out, $action, $level, null, true );
						}
					}
				}
			}
		}
		if ( ExtensionRegistry::getInstance()->isLoaded( 'FlaggedRevs' ) ) {
			$r = FRPageConfig::getStabilitySettings( $title );
			if ( $r['autoreview'] ) {
				$o->createIndicator( $out,
					wfMessage( 'protection-indicator-flagged-revs' ), $r['autoreview'], $r['expiry'] );
			}
		}
	}

	/**
	 * A function to create a padlock icon which is then added to output
	 * @param OutputPage $out Output page object to write to
	 * @param string $action Action for which protection has been applied
	 * @param string $level Userright required to perform action
	 * @param string|null $rExpiry Expiry time in 14 character format
	 * @param bool $cascading | true is protection cascading
	 */
	protected function createIndicator( \OutputPage $out, $action,
		$level, $rExpiry, $cascading = false ) {
		$out->enableOOUI();
		$out->addModuleStyles( [ 'ext.protectionIndicator.custom' ] );
		$out->addModules( [ 'ext.protectionIndicator' ] );
		$out->addModuleStyles( [ 'oojs-ui.styles.icons-moderation' ] );
		$timestamp = wfTimestamp( TS_RFC2822, $rExpiry );
		$icon = new OOUI\IconWidget( [
					'icon' => 'lock',
					'infusable' => true,
					'classes' => [ 'protection-indicator-icon', 'protection-indicator-'
					. ( ( $cascading ) ? 'cascading-' : '' ) . $level . '-' . $action ]
					] );

		if ( $cascading ) {
			if ( strlen( $timestamp ) ) {
				$label = wfMessage( 'protection-indicator-explanation-cascading',
					 $level, $action, $timestamp )->parse();
			} else {
				$label = wfMessage( 'protection-indicator-explanation-cascading-infinity',
					 $level, $action )->parse();
			}
		} else {
			if ( strlen( $timestamp ) ) {
				$label = wfMessage( 'protection-indicator-explanation-non-cascading',
					 $level, $action, $timestamp )->parse();
			} else {
				$label = wfMessage( 'protection-indicator-explanation-non-cascading-infinity',
					 $level, $action )->parse();
			}
		}
		$icon->setLabel( $label );
		$out->setIndicators( [ 'protection-indicator-' . ( ( $cascading ) ? 'cascading-'
		 : '' ) . $action => $icon ] );
	}

	/**
	 * Hook to create a magic word
	 * @param \Parser $parser
	 */
	public static function onParserFirstCallInit( \Parser $parser ) {
		$parser->setHook( 'suppressProtectionIndicator',
		[ self::class, 'suppressProtectionIndicator' ] );
	}

	/**
	 * Sets exension data needed to supresss all icons
	 * @param string|null $input
	 * @param array $args Arguments of the magic word
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 * @return string
	 */
	public static function suppressProtectionIndicator( $input, array $args,
	\Parser $parser, \PPFrame $frame ) {
		$out = $parser->getOutput();
		$out->setExtensionData( 'protection-indicator-supress-all', true );
		return '';
	}
}
