<?php

/**
 * @covers MpdfAction
 * @group MpdfAction
 * @group Database
 */
class MpdfActionIntegrationTest extends MediaWikiIntegrationTestCase {

	private function newAction( array $requestParams = [] ): MpdfAction {
		$title = Title::makeTitle( NS_MAIN, 'Test' );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setTitle( $title );
		$context->setRequest( new FauxRequest( $requestParams ) );
		$article = Article::newFromTitle( $title, $context );
		return new MpdfAction( $article, $context );
	}

	/**
	 * @covers MpdfAction::getName
	 */
	public function testGetNameReturnsMpdf() {
		$this->assertSame( 'mpdf', $this->newAction()->getName() );
	}
}
