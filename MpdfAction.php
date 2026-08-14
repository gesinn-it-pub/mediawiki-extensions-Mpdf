<?php
/**
 * Handles the 'mpdf' action.
 */

use MediaWiki\MediaWikiServices;

class MpdfAction extends Action {

	/**
	 * Return the name of the action this object responds to.
	 * @return string lowercase
	 */
	public function getName() {
		return 'mpdf';
	}

	/**
	 * The main action entry point. Do all output for display and send it
	 * to the context output.
	 */
	public function show() {
		global $wgMpdfSimpleOutput;

		$title = $this->getTitle();
		$output = $this->getOutput();
		$request = $this->getRequest();

		$titletext = $title->getPrefixedText();
		$filename = self::sanitizeFilename( $titletext );
		$article = new Article( $title );
		MediaWikiServices::getInstance()->getHookContainer()->run( 'MpdfGetArticle', [ $title, &$article ] );

		if ( $wgMpdfSimpleOutput ) {
			$article->render();
			ob_start();
			$output->output();
			$html = self::buildSimpleOutputHtml( $title->getFullURL(), $titletext, ob_get_clean() );
		} else {
			$article->view();
			ob_start();
			$output->output();
			$html = ob_get_clean();
		}

		// Initialise PDF variables
		$format = $request->getText( 'format' );

		// If format=html in query-string, return html content directly
		if ( $format === 'html' ) {
			$output->disable();
			foreach ( self::buildHtmlDownloadHeaders( $filename ) as $header ) {
				header( $header );
			}

			print self::inlineImagesAsDataUris( $html );
			return;
		}

		// Otherwise, render and stream a PDF file.
		$config = self::parseMpdfConfig( $html );

		$tempDir = wfTempDir();
		if ( !defined( '_MPDF_TEMP_PATH' ) ) {
			define( "_MPDF_TEMP_PATH", "$tempDir/mpdf/temp/" );
			wfMkdirParents( _MPDF_TEMP_PATH );
		}
		if ( !defined( '_MPDF_TTFONTDATAPATH' ) ) {
			define( '_MPDF_TTFONTDATAPATH', "$tempDir/mpdf/ttfontdata/" );
			wfMkdirParents( _MPDF_TTFONTDATAPATH );
		}

		$mpdf = new \Mpdf\Mpdf( $config );
		// set base url to help rendering images inside mpdf
		$mpdf->setBasePath( 'http://127.0.0.1/' );
		$mpdf->showImageErrors = true;

		// Suppress warning messages, because the mPDF library itself
		// generates warnings (due to trying to add variables with a
		// value of 'auto'), and if these get printed out, they can
		// get into the PDF file and make it unreadable.
		\Wikimedia\AtEase\AtEase::suppressWarnings();
		$mpdf->WriteHTML( $html );
		\Wikimedia\AtEase\AtEase::restoreWarnings();

		$mpdf->Output( $filename . '.pdf', 'D' );

		$output->disable();
	}

	/**
	 * Replace characters that are not safe to use in a filename.
	 *
	 * @param string $titletext
	 * @return string
	 */
	private static function sanitizeFilename( $titletext ) {
		return str_replace( [ '\\', '/', ':', '*', '?', '"', '<', '>', "\n", "\r", "\0" ], '_', $titletext );
	}

	/**
	 * Build the HTML shown in "simple output" mode: a source-URL/title
	 * footer prepended to the rendered article body.
	 *
	 * @param string $url
	 * @param string $titletext
	 * @param string $bodyHtml
	 * @return string
	 */
	private static function buildSimpleOutputHtml( $url, $titletext, $bodyHtml ) {
		$footer = "<p><em>$url</em></p><h1>$titletext</h1>\n";
		return $footer . $bodyHtml;
	}

	/**
	 * Build the HTTP headers needed to stream the page as a downloadable
	 * HTML file.
	 *
	 * @param string $filename
	 * @return string[]
	 */
	private static function buildHtmlDownloadHeaders( $filename ) {
		return [
			'Content-Type: text/html',
			"Content-Disposition: attachment; filename=\"$filename.html\"",
		];
	}

	/**
	 * Rewrite every <img> "src" attribute in the given HTML into an inline
	 * data URI, so the image is embedded directly into the document instead
	 * of referenced by path. Used for the "download as HTML" output mode,
	 * which produces a single self-contained file.
	 *
	 * @param string $html
	 * @return string
	 */
	private static function inlineImagesAsDataUris( $html ) {
		$dom = new DOMDocument;
		// Suppress warnings from malformed HTML.
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html );
		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );
		$imgTags = $xpath->query( '//img' );

		foreach ( $imgTags as $img ) {
			$src = $img->getAttribute( 'src' );
			$img->setAttribute( 'src', self::imageSrcToDataUri( $src ) );
		}

		return $dom->saveHTML();
	}

	/**
	 * Convert an <img> "src" attribute value into an inline data URI.
	 *
	 * @param string $src
	 * @return string
	 */
	private static function imageSrcToDataUri( $src ) {
		$src = ltrim( $src, '/' );
		$imageData = base64_encode( file_get_contents( $src ) );

		return 'data:image/' . pathinfo( $src, PATHINFO_EXTENSION ) . ';base64,' . $imageData;
	}

	/**
	 * Build the mPDF configuration array for the given page HTML.
	 *
	 * The wikitext {{#mpdftags:}} parser function (see
	 * MpdfHooks::mpdftagsRender()) lets page authors override PDF layout
	 * settings by embedding an HTML comment of the form:
	 *   <!--mpdf<constructor format="A4" margin-left="15" .../>mpdf-->
	 * This method looks for that marker in the rendered HTML and extracts
	 * any settings it contains, falling back to fixed defaults for
	 * anything not specified.
	 *
	 * @param string $html
	 * @return array
	 */
	private static function parseMpdfConfig( $html ) {
		$config = [
			'mode' => 'utf-8',
			'format' => 'A4',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 16,
			'margin_bottom' => 16,
			'margin_header' => 9,
			'margin_footer' => 9,
			'orientation' => 'P',
		];

		$parts = explode( '<!--mpdf<constructor', $html, 2 );
		if ( !isset( $parts[1] ) ) {
			return $config;
		}
		[ $constructorTag ] = explode( '/>', $parts[1], 1 );

		$stringSettings = [
			'format' => 'format',
			'orientation' => 'orientation',
		];
		foreach ( $stringSettings as $attribute => $configKey ) {
			$matches = [];
			if ( preg_match( '/' . $attribute . '\s*=\s*"(.*?)"/', $constructorTag, $matches ) ) {
				$config[$configKey] = $matches[1];
			}
		}

		$marginSettings = [
			'margin-left' => 'margin_left',
			'margin-right' => 'margin_right',
			'margin-top' => 'margin_top',
			'margin-bottom' => 'margin_bottom',
			'margin-header' => 'margin_header',
			'margin-footer' => 'margin_footer',
		];
		foreach ( $marginSettings as $attribute => $configKey ) {
			$matches = [];
			if ( preg_match( '/' . $attribute . '\s*=\s*"?([0-9.]+)/', $constructorTag, $matches ) ) {
				$config[$configKey] = (float)$matches[1];
			}
		}

		return $config;
	}
}
