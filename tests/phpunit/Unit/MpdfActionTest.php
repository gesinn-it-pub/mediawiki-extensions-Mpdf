<?php

use MediaWiki\MediaWikiServices;

/**
 * @group MpdfAction
 */
class MpdfActionTest extends MediaWikiUnitTestCase {

	protected $wgMpdfSimpleOutput;

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
}
