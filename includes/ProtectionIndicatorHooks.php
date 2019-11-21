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
 *
 * @file
 */

namespace MediaWiki\Extension\ProtectionIndicator;

class ProtectionIndicatorHooks {
	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @param \OutputPage $out
	 * @param \Skin $skin
	 */
	public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {
		global $wgRestrictionLevels;
		$title = $skin->getRelevantTitle();
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
	}

	/**
	 * A function to create a padlock icon which is then
	 * @param \OutputPage $out Output page object to write to
	 * @param string $action Action for which protection has been applied
	 * @param string $level Userright required to perform action
	 * @param string|null $rExpiry Expiry time in 14 character format
	 * @param bool $cascading | true is protection cascading
	 */
	protected function createIndicator( \OutputPage $out, $action,
		$level, $rExpiry, $cascading = false ) {
		$out->enableOOUI();
		$out->addModuleStyles( [ 'oojs-ui.styles.icons-moderation' ] );
		$out->addModules( [ 'ext.protectionIndicator' ] );
		$timestamp = wfTimestamp( TS_RFC2822, $rExpiry );
		if ( $cascading ) {
			if ( strlen( $timestamp ) ) {
				$icon = new \OOUI\IconWidget( [
					'icon' => 'lock',
					'label' => wfMessage( 'protection-indicator-explanation-cascading',
					 $level, $action, $timestamp )->parse(),
					'infusable' => true,
					'classes' => [ 'protection-indicator-icon' ]
				] );
			} else {
				$icon = new \OOUI\IconWidget( [
					'icon' => 'lock',
					'label' => wfMessage( 'protection-indicator-explanation-cascading-infinity',
					 $level, $action )->parse(),
					'infusable' => true,
					'classes' => [ 'protection-indicator-icon' ]
				] );
			}
		} else {
			if ( strlen( $timestamp ) ) {
				$icon = new \OOUI\IconWidget( [
					'icon' => 'lock',
					'label' => wfMessage( 'protection-indicator-explanation-non-cascading',
					 $level, $action, $timestamp )->parse(),
					'infusable' => true,
					'classes' => [ 'protection-indicator-icon' ]
				] );
			} else {
				$icon = new \OOUI\IconWidget( [
					'icon' => 'lock',
					'label' => wfMessage( 'protection-indicator-explanation-non-cascading-infinity',
					 $level, $action )->parse(),
					'infusable' => true,
					'classes' => [ 'protection-indicator-icon' ]
				] );
			}
		}
		$out->setIndicators( [ 'protection-indicator-' . ( ( $cascading ) ? 'cascading-'
		 : '' ) . $action => $icon ] );
	}
}
