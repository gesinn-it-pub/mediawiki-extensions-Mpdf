<?php

use MediaWiki\MediaWikiServices;

class MpdfHooks {

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser &$parser
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'mpdftags', [ 'MpdfHooks', 'mpdftagsRender' ] );
	}

	/**
	 * Add "PDF Export" link to the sidebar's toolbox
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SidebarBeforeOutput
	 *
	 * @param Skin $skin
	 * @param array &$sidebar
	 * @return bool
	 */
	public static function onSidebarBeforeOutput( Skin $skin, array &$sidebar ) {
		global $wgMpdfToolboxLink;

		if ( !$wgMpdfToolboxLink ) {
			return true;
		}

		$title = $skin->getTitle();
		if ( $title->isSpecialPage() ) {
			return true;
		}

		$sidebar['TOOLBOX']['mpdf'] = [
			'msg' => 'mpdf-action',
			'href' => $title->getLocalUrl( [ 'action' => 'mpdf' ] ),
			'id' => 't-mpdf',
			'rel' => 'mpdf'
		];

		return true;
	}

	/**
	 * Adds a "PDF Export" link to the set of tabs/actions, if one was
	 * specified.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation::Universal
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array &$links
	 */
	public static function onSkinTemplateNavigationUniversal( SkinTemplate $skinTemplate, array &$links ) {
		$mpdfTab = MediaWikiServices::getInstance()->getMainConfig()->get( 'MpdfTab' );

		if ( $mpdfTab ) {
			$links['views']['mpdf'] = [
				'class' => false,
				'text' => wfMessage( 'mpdf-action' )->text(),
				'href' => $skinTemplate->getTitle()->getLocalURL( 'action=mpdf' ),
			];
		}
	}

	/**
	 * Wraps the given parameters in an HTML comment marker that
	 * MpdfAction::show() later parses out to configure PDF generation
	 * (page format, margins, orientation).
	 *
	 * @param Parser $parser
	 * @param string ...$params
	 * @return string
	 */
	public static function mpdftagsRender( Parser $parser, ...$params ) {
		// Escape angle brackets so the parameters cannot inject markup.
		$escapedParams = str_replace( [ '<', '>' ], [ '&lt;', '&gt;' ], $params );

		$comment = '<!--mpdf';
		foreach ( $escapedParams as $param ) {
			$comment .= '<' . $param . " />\n";
		}
		$comment .= "mpdf-->\n";

		return $parser->insertStripItem( $comment );
	}

}
