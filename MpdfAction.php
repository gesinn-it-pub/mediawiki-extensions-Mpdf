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
		$filename = str_replace( [ '\\', '/', ':', '*', '?', '"', '<', '>', "\n", "\r", "\0" ], '_', $titletext );
		$article = new Article( $title );
		MediaWikiServices::getInstance()->getHookContainer()->run( 'MpdfGetArticle', [ $title, &$article ] );

		if ( $wgMpdfSimpleOutput ) {
			$article->render();
			ob_start();
			$output->output();
			$url = $title->getFullURL();
			$footer = "<p><em>$url</em></p><h1>$titletext</h1>\n";
			$html = $footer . ob_get_clean();
		} else {
			$article->view();
			ob_start();
			$output->output();
			$html = ob_get_clean();
		}

		// Initialise PDF variables
		$format = $request->getText( 'format' );

		// If format=html in query-string, return html content directly
		if ( $format == 'html' ) {
			$output->disable();
			header( "Content-Type: text/html" );
			header( "Content-Disposition: attachment; filename=\"$filename.html\"" );
			// Create a new DOMDocument instance
			$dom = new DOMDocument;
			// Load HTML content, suppress errors for malformed HTML
			libxml_use_internal_errors( true );
			$dom->loadHTML( $html );
			libxml_clear_errors();

			// Create a new DOMXPath instance
			$xpath = new DOMXPath( $dom );

			// Query for all <img> tags
			$imgTags = $xpath->query( '//img' );

			// Loop through the <img> tags and output attributes
			foreach ( $imgTags as $img ) {
				$src = $img->getAttribute( 'src' );
				$src = ltrim( $src, '/' );

				$imageData = base64_encode( file_get_contents( $src ) );

				// Create data URI for the image
				$dataUri = 'data:image/' . pathinfo( $src, PATHINFO_EXTENSION ) . ';base64,' . $imageData;

				// Set the src attribute to the data URI
				$img->setAttribute( 'src', $dataUri );

				// Get the updated HTML content after modifying img tags
				$html = $dom->saveHTML();
			}
			print $html;
		} else {
			// return pdf file
			$mode = 'utf-8';
			$format = 'A4';
			$marginLeft = 15;
			$marginRight = 15;
			$marginTop = 16;
			$marginBottom = 16;
			$marginHeader = 9;
			$marginFooter = 9;
			$orientation = 'P';
			$constr1 = explode( '<!--mpdf<constructor', $html, 2 );
			if ( isset( $constr1[1] ) ) {
				[ $constr2 ] = explode( '/>', $constr1[1], 1 );
				$matches = [];
				if ( preg_match( '/format\s*=\s*"(.*?)"/', $constr2, $matches ) ) {
					$format = $matches[1];
				}
				if ( preg_match( '/margin-left\s*=\s*"?([0-9.]+)/', $constr2, $matches ) ) {
					$marginLeft = (float)$matches[1];
				}
				if ( preg_match( '/margin-right\s*=\s*"?([0-9.]+)/', $constr2, $matches ) ) {
					$marginRight = (float)$matches[1];
				}
				if ( preg_match( '/margin-top\s*=\s*"?([0-9.]+)/', $constr2, $matches ) ) {
						$marginTop = (float)$matches[1];
				}
				if ( preg_match( '/margin-bottom\s*=\s*"?([0-9.]+)/', $constr2, $matches ) ) {
					$marginBottom = (float)$matches[1];
				}
				if ( preg_match( '/margin-header\s*=\s*"?([0-9.]+)/', $constr2, $matches ) ) {
					$marginHeader = (float)$matches[1];
				}
				if ( preg_match( '/margin-footer\s*=\s*"?([0-9.]+)/', $constr2, $matches ) ) {
					$marginFooter = (float)$matches[1];
				}
				if ( preg_match( '/orientation\s*=\s*"(.*?)"/', $constr2, $matches ) ) {
					$orientation = $matches[1];
				}
			}

			$tempDir = wfTempDir();
			if ( !defined( '_MPDF_TEMP_PATH' ) ) {
				define( "_MPDF_TEMP_PATH", "$tempDir/mpdf/temp/" );
				wfMkdirParents( _MPDF_TEMP_PATH );
			}
			if ( !defined( '_MPDF_TTFONTDATAPATH' ) ) {
				define( '_MPDF_TTFONTDATAPATH', "$tempDir/mpdf/ttfontdata/" );
				wfMkdirParents( _MPDF_TTFONTDATAPATH );
			}

			// Configuration array
			$config = [
				'mode' => $mode,
				'format' => $format,
				'margin_left' => $marginLeft,
				'margin_right' => $marginRight,
				'margin_top' => $marginTop,
				'margin_bottom' => $marginBottom,
				'margin_header' => $marginHeader,
				'margin_footer' => $marginFooter,
				'orientation' => $orientation
			];

			$mpdf = new \Mpdf\Mpdf( $config );
			// set base url to help rendering images inside mpdf
			$url = 'http://127.0.0.1/';
			$mpdf->setBasePath( $url );

			$mpdf->showImageErrors = true;

			// Suppress warning messages, because the mPDF library
			// itself generates warnings (due to trying to add
			// variables with a value of 'auto'), and if these get
			// printed out, they can get into the PDF file and make
			// it unreadable.
			if ( function_exists( '\Wikimedia\suppressWarnings' ) ) {
				// MW 1.31+
				\Wikimedia\suppressWarnings();
			} else {
				wfSuppressWarnings();
			}
			$mpdf->WriteHTML( $html );
			if ( function_exists( '\Wikimedia\restoreWarnings' ) ) {
				// MW 1.31+
				\Wikimedia\restoreWarnings();
			} else {
				wfRestoreWarnings();
			}

			$mpdf->Output( $filename . '.pdf', 'D' );
		}
		$output->disable();
	}
}
