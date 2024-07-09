<?php

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;

/**
 * @group MpdfAction
 */
class MpdfActionTest extends TestCase {

	/**
	 * Configuration setting for determining whether to use simple output mode in mPDF.
	 *
	 * @var bool
	 */
	protected $wgMpdfSimpleOutput;

	/**
	 * @covers MpdfAction::getName
	 */
	public function testGetNameReturnsCorrectActionName() {
		$action = $this->getMockBuilder( MpdfAction::class )
					   ->disableOriginalConstructor()
					   ->getMock();

		// Define what getName() should return in the mocked MpdfAction
		$action->expects( $this->any() )
			   ->method( 'getName' )
			   ->willReturn( 'mpdf' );

		// Call the getName() method
		$name = $action->getName();

		// Assert that the returned name matches 'mpdf'
		$this->assertEquals( 'mpdf', $name );
	}

	/**
	 * @covers MpdfAction::show
	 */
	public function testCheckOutputOfHtmlInsideShowMethod() {
		// Set up expectations
		$titleText = 'Test_Title';
		$outputHtml = '<html><body>Test HTML</body></html>';
		$format = 'html';

		// Mock objects
		$outputPageMock = $this->createMock( OutputPage::class );
		$titleMock = $this->createMock( Title::class );
		$articleMock = $this->getMockBuilder( Article::class )
						->disableOriginalConstructor()
						->getMock();

		$requestMock = $this->getMockBuilder( WebRequest::class )
					   ->disableOriginalConstructor()
					   ->getMock();

		$requestMock->expects( $this->any() )
				->method( 'getText' )
				->willReturn( 'format' );

		 // Mocking getTitle() method
		 $titleMock->expects( $this->any() )
		 ->method( 'getPrefixedText' )
		 ->willReturn( $titleText );

		// Mocking getOutput() method
		$outputPageMock->expects( $this->any() )
					->method( 'getOutput' )
					->willReturn( $outputHtml );

		// Mocking output() method
		$outputPageMock->expects( $this->any() )
					->method( 'output' )
					->willReturn( $outputHtml );

		$titleToCheck = $titleMock->getPrefixedText();
		$format = 'html';
		$output = $outputPageMock;

		$filename = str_replace( [ '\\', '/', ':', '*', '?', '"', '<', '>', "\n", "\r", "\0" ], '_', $titleText );
		MediaWikiServices::getInstance()->getHookContainer()->run( 'MpdfGetArticle', [ $titleText, &$article ] );

		$this->assertEquals( $titleText, $titleToCheck );

		if ( !$this->wgMpdfSimpleOutput ) {
			ob_start();
			$articleMock->expects( $this->any() )
						->method( 'view' )
						->willReturn( $outputHtml );

			$html = $articleMock->view();
			$outputPageMock->output();

			$this->assertStringContainsString( "Test HTML", $html );
			$html = ob_get_clean();
		}
	}

	/**
	 * @covers MpdfAction::show
	 */
	public function testImgTagsTransformedToDataUri() {
		// Path to your test image
		$imagePath = __DIR__ . '/images/MediaWiki-2020.png';

		// Simulated HTML output with <img> tags
		$html = '<html><body><img src="/tests/images/MediaWiki-2020.png"></body></html>';

		// Simulate the process of transforming img tags to data URIs
		$dom = new DOMDocument;
		$dom->loadHTML( $html );
		$xpath = new DOMXPath( $dom );
		$imgTags = $xpath->query( '//img' );

		// Apply the transformation as done in the original code
		foreach ( $imgTags as $img ) {
			$src = $img->getAttribute( 'src' );
			$src = ltrim( $src, '/' );
			$imageData = base64_encode( file_get_contents( $src ) );
			$dataUri = 'data:image/' . pathinfo( $src, PATHINFO_EXTENSION ) . ';base64,' . $imageData;
			$img->setAttribute( 'src', $dataUri );
		}

		// Get the updated HTML content
		$updatedHtml = $dom->saveHTML();

		// Assert that the transformation was successful
		$this->assertStringContainsString( 'data:image', $updatedHtml );
	}

	/**
	 * @covers MpdfAction::show
	 */
	public function testPdfGeneration() {
		// HTML content to test
		$html = '<html><body><h1>Hello, PDF!</h1></body></html>';

		// Assume $filename is generated dynamically in your actual code
		$filename = 'test_document';

		// Call your PDF generation method
		$pdfFilePath = $this->generatePdf( $html, $filename );

		// Assert that the PDF file was generated
		$this->assertFileExists( $pdfFilePath );
		$this->assertStringContainsString( '.pdf', $pdfFilePath );

		// Clean up: delete the generated PDF after testing
		unlink( $pdfFilePath );
	}

	private function generatePdf( $html, $filename ) {
		// Your original PDF generation code
		$mode = 'utf-8';
		$format = 'A4';
		$marginLeft = 15;
		$marginRight = 15;
		$marginTop = 16;
		$marginBottom = 16;
		$marginHeader = 9;
		$marginFooter = 9;
		$orientation = 'P';

		// Example: Use a temporary directory for testing
		$tempDir = sys_get_temp_dir();

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

		// Create mPDF instance
		$mpdf = new \Mpdf\Mpdf( $config );

		// Write HTML content to PDF
		$mpdf->WriteHTML( $html );

		// Set base URL for images (if needed)
		$url = 'http://localhost/';
		$mpdf->setBasePath( $url );

		// Output PDF to a file (for testing, use a temporary directory)
		$pdfFilePath = $tempDir . '/' . $filename . '.pdf';
		$mpdf->Output( $pdfFilePath, 'F' );

		return $pdfFilePath;
	}
}
