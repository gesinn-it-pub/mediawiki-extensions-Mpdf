<?php

/**
 * @covers MpdfAction
 * @group MpdfAction
 * @group Database
 */
class MpdfActionIntegrationTest extends MediaWikiIntegrationTestCase {

	private function newAction( array $requestParams = [], ?Title $title = null ): MpdfAction {
		$title ??= Title::makeTitle( NS_MAIN, 'Test' );
		RequestContext::getMain()->setTitle( $title );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setTitle( $title );
		$context->setRequest( new FauxRequest( $requestParams ) );
		$article = Article::newFromTitle( $title, $context );
		$context->setWikiPage( $article->getPage() );
		$context->setOutput( new OutputPage( $context ) );
		return new MpdfAction( $article, $context );
	}

	/**
	 * @covers MpdfAction::getName
	 */
	public function testGetNameReturnsMpdf() {
		$this->assertSame( 'mpdf', $this->newAction()->getName() );
	}

	/**
	 * @covers MpdfAction::show
	 */
	public function testShowWithFormatHtmlOutputsInlinedHtmlWithDownloadHeaders() {
		$page = $this->getExistingTestPage( 'MpdfActionIntegrationTestPage' );
		$action = $this->newAction( [ 'format' => 'html' ], $page->getTitle() );

		// imageSrcToDataUri() resolves <img> "src" paths relative to the
		// MediaWiki install root, as they are at runtime (index.php cwd).
		$previousCwd = getcwd();
		chdir( MW_INSTALL_PATH );
		try {
			ob_start();
			$action->show();
			$html = ob_get_clean();
		} finally {
			chdir( $previousCwd );
		}

		// Header values themselves are covered by
		// MpdfActionTest::testBuildHtmlDownloadHeadersReturnsContentTypeAndDisposition();
		// here we only verify show() reaches the html-download branch and
		// prints the inlined page content.
		$this->assertStringContainsString( 'MpdfActionIntegrationTestPage', $html );
	}

	/**
	 * @covers MpdfAction::show
	 */
	public function testShowWithSimpleOutputPrependsUrlAndTitleFooter() {
		$this->overrideConfigValue( 'MpdfSimpleOutput', true );

		$page = $this->getExistingTestPage( 'MpdfActionIntegrationSimpleOutputPage' );
		$action = $this->newAction( [ 'format' => 'html' ], $page->getTitle() );

		$previousCwd = getcwd();
		chdir( MW_INSTALL_PATH );
		try {
			ob_start();
			$action->show();
			$html = ob_get_clean();
		} finally {
			chdir( $previousCwd );
		}

		$this->assertStringContainsString(
			'<h1>' . $page->getTitle()->getPrefixedText() . '</h1>',
			$html
		);
		$this->assertStringContainsString( $page->getTitle()->getFullURL(), $html );
	}

	/**
	 * @covers MpdfAction::show
	 */
	public function testShowWithoutFormatOutputsAValidPdf() {
		// The rendered page includes the skin's logo and footer icons
		// (e.g. "Powered by MediaWiki"), which mPDF resolves via
		// $mpdf->setBasePath( 'http://127.0.0.1/' ) — no webserver listens
		// there in the PHPUnit CLI environment. Configure an icon-less skin
		// so WriteHTML() has nothing to fetch over HTTP.
		$this->overrideConfigValues( [
			'Logos' => [],
			'FooterIcons' => [],
		] );

		$page = $this->getExistingTestPage( 'MpdfActionIntegrationPdfPage' );
		// No 'format' request parameter takes the PDF-generation branch.
		$action = $this->newAction( [], $page->getTitle() );

		$previousCwd = getcwd();
		chdir( MW_INSTALL_PATH );
		try {
			ob_start();
			$action->show();
			$pdf = ob_get_clean();
		} finally {
			chdir( $previousCwd );
		}

		$this->assertStringStartsWith( '%PDF-', $pdf );
		$this->assertStringEndsWith( '%%EOF', $pdf );
		// A blank page still runs to a few KB once fonts/metadata are embedded;
		// a near-empty response would indicate WriteHTML()/Output() failed silently.
		$this->assertGreaterThan( 1000, strlen( $pdf ) );
	}
}
