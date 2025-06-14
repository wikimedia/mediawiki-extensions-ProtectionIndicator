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

use ExtensionRegistry;
use FRPageConfig;
use LogEventsList;
use MediaWiki\MediaWikiServices;
use OOUI;

class ProtectionIndicatorHooks {
	/**
	 * Hook to load the protection icons on article pages
	 * @param \Article $article Article object to be used
	 * @param bool &$outputDone
	 * @param bool &$pcache
	 */
	public static function onArticleViewHeader( \Article $article, &$outputDone, &$pcache ) {
		$title = $article->getTitle();
		$out = $article->getContext()->getOutput();
		$config = $out->getConfig();
		// Make sure that we are in the correct page
		if ( !$out->isArticle() && $title->isSpecialPage() ) {
			return;
		}
		// Use configurational variable to check if Wiki wants icons
		// on their main page
		// In the same condition check if the page
		// is configured to supress all protection icons
		if ( !$config->get( 'ShowIconsOnMainPage' ) && $title->isMainPage() ) {
			return;
		}
		// Check if Revision of the article can be accessed,
		// if we cannot we are probably on the wrong page
		$revisionRecord = $article->getPage()->getRevisionRecord();
		if ( !$revisionRecord ) {
			return;
		}
		$pOut = $article->getParserOutput( $revisionRecord->getID() );
		// Make sure protection icons have not been supressed.
		if ( $pOut->getExtensionData( 'protectionindicator-supress-all' ) ) {
			return;
		}
		// Load the data of the extension
		$protectionData = $pOut->getExtensionData( 'protectionindicator-protection-data' );
		$indicators = [];
		foreach ( $protectionData as $protection ) {
			if ( $config->get( 'ShowLogInPopup' ) && $protection[0] == 'edit-flaggedrev' ) {
				array_push( $protection,
					$pOut->getExtensionData( 'protectionindicator-stability-log-data' ) );
			} elseif ( $config->get( 'ShowLogInPopup' ) ) {
				array_push( $protection,
					$pOut->getExtensionData( 'protectionindicator-protect-log-data' ) );
			} else {
				array_push( $protection, null );
			}
			$indicators[ 'protectionindicator-' .
			 $protection[0] ] = self::createIndicator( $protection );
		}
		$out->enableOOUI();
		$out->addModuleStyles( [ 'ext.protectionIndicator.custom' ] );
		$out->addModules( [ 'ext.protectionIndicator' ] );
		$out->addModuleStyles( [ 'oojs-ui.styles.icons-moderation' ] );
		$out->setIndicators( $indicators );
	}

	/**
	 * A function to create a padlock icon.
	 * @param array $protection 1st element is action,
	 * 2nd element is level required to do action,
	 * 3rd element is a boolean true if it is cascading protection
	 * 4th element is a boolean true if it is a flaggedrevs protection
	 * 5th element Log entry to be added, null if empty
	 * @return OOUI\IconWidget OOUI object of icon
	 */
	protected static function createIndicator( $protection ) {
		// infinity time should give us a empty string
		$o = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		// @todo Figure out support for Bengali digits
		$timestamp = $o->formatExpiry( $protection[2], true );
		// classes are of the type
		// protectionindicator-<cascading>-<flaggedrevs>-<action>-<level>
		$icon = new OOUI\IconWidget( [
					'icon' => 'lock',
					'infusable' => true,
					'classes' => [ 'protectionindicator-icon', 'protectionindicator-'
					. ( ( $protection[3] ) ? 'cascading-' : '' ) .
					( ( $protection[4] ) ? 'flaggedrevs-' : '' ) . $protection[1] . '-' . $protection[0] ]
					] );
		if ( $protection[3] ) {
			$label = wfMessage( 'protectionindicator-explanation-cascading-' . $protection[0],
				 $protection[1] )->parse();
		} elseif ( $protection[4] ) {
			if ( $timestamp != 'infinite' ) {
				$label = wfMessage( 'protectionindicator-explanation-flaggedrevs',
					 $protection[1], $timestamp )->parse();
			} else {
				$label = wfMessage( 'protectionindicator-explanation-flaggedrevs-infinity',
					 $protection[1] )->parse();
			}
		} else {
			if ( $timestamp != 'infinite' ) {
				$label = wfMessage( 'protectionindicator-explanation-normal-' . $protection[0],
					 $protection[1], $timestamp )->parse();
			} else {
				$label = wfMessage( 'protectionindicator-explanation-normal-infinity-' . $protection[0],
					 $protection[1] )->parse();
			}
		}
		$label .= ( $protection[5] ) ? $protection[5] : '';
		$icon->setLabel( $label );
		return $icon;
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
		$out->setExtensionData( 'protectionindicator-supress-all', true );
		return '';
	}

	/**
	 * Updates the values of portection and stores them in extension data
	 * @param \Content $content Cotent object of page
	 * @param \MediaWiki\Title\Title $title MediaWiki\Title\Title object of page
	 * @param \ParserOutput $pOut ParserOutput object of the page
	 */
	public static function onContentAlterParserOutput( \Content $content, \MediaWiki\Title\Title $title,
	 \ParserOutput $pOut ) {
		global $wgRestrictionLevels;
		// Get the log entry
		$out1 = '';
		$out2 = '';
		$protectionIndicatorData = [];
		LogEventsList::showLogExtract( $out1, 'protect', $title, '', [ 'lim' => 1 ] );
		$pOut->setExtensionData( 'protectionindicator-protect-log-data', $out1 );
		LogEventsList::showLogExtract( $out2, 'stable', $title, '', [ 'lim' => 1 ] );
		$pOut->setExtensionData( 'protectionindicator-stability-log-data', $out2 );
		// Start checking for the protection types
		if ( method_exists( MediaWikiServices::class, 'getRestrictionStore' ) ) {
			// MW 1.37+
			$restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();
			$restrictionTypes = $restrictionStore->listApplicableRestrictionTypes( $title );
			foreach ( $restrictionTypes as $action ) {
				$r = $restrictionStore->getRestrictions( $title, $action );
				$rExpiry = $restrictionStore->getRestrictionExpiry( $title, $action );
				foreach ( $wgRestrictionLevels as $level ) {
					if ( in_array( $level, $r ) ) {
						array_push( $protectionIndicatorData, [ $action, $level, $rExpiry, false, false ] );
					}
				}
			}
			$rCascade = $restrictionStore->getCascadeProtectionSources( $title, true );
		} else {
			$restrictionTypes = $title->getRestrictionTypes();
			foreach ( $restrictionTypes as $action ) {
				$r = $title->getRestrictions( $action );
				$rExpiry = $title->getRestrictionExpiry( $action );
				foreach ( $wgRestrictionLevels as $level ) {
					if ( in_array( $level, $r ) ) {
						array_push( $protectionIndicatorData, [ $action, $level, $rExpiry, false, false ] );
					}
				}
			}
			$rCascade = $title->getCascadeProtectionSources( true );
		}
		$r = $rCascade[1];
		if ( $rCascade[0] ) {
			foreach ( $restrictionTypes as $action ) {
				if ( array_key_exists( $action, $r ) ) {
					$r = $r[$action];
					foreach ( $wgRestrictionLevels as $level ) {
						if ( in_array( $level, $r ) ) {
							array_push( $protectionIndicatorData, [ $action, $level, null, true, false ] );
						}
					}
				}
			}
		}
		if ( ExtensionRegistry::getInstance()->isLoaded( 'FlaggedRevs' ) ) {
			$r = FRPageConfig::getStabilitySettings( $title );
			if ( $r['autoreview'] ) {
				foreach ( $wgRestrictionLevels as $level ) {
					if ( $r['autoreview'] == $level ||
						( is_array( $r['autoreview'] ) && in_array( $level, $r['autoreview'] ) ) ) {
						array_push( $protectionIndicatorData, [ 'edit', $level,
						 $r['expiry'], false, true ] );
						// not auto-review since, we already have a
					// true/false parameter denoting flaggedrevs
					}
				}
			}
		}
		$protectionIndicatorData = array_unique( $protectionIndicatorData, SORT_REGULAR );
		$pOut->setExtensionData( 'protectionindicator-protection-data',
		 $protectionIndicatorData );
	}
}
