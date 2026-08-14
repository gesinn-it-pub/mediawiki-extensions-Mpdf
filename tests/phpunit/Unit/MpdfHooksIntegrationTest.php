<?php

/**
 * @covers MpdfHooks::onSkinTemplateNavigationUniversal
 * @group MpdfHooks
 * @group Skin
 */
class MpdfHooksIntegrationTest extends MediaWikiIntegrationTestCase {

	public function testOnSkinTemplateNavigationUniversalAddsLinkWhenTabEnabled() {
		$this->overrideConfigValue( 'MpdfTab', true );

		$title = $this->createMock( Title::class );
		$title->method( 'getLocalURL' )
			->with( 'action=mpdf' )
			->willReturn( '/index.php?title=Test&action=mpdf' );

		$skinTemplate = $this->createMock( SkinTemplate::class );
		$skinTemplate->method( 'getTitle' )->willReturn( $title );

		$links = [ 'views' => [] ];

		MpdfHooks::onSkinTemplateNavigationUniversal( $skinTemplate, $links );

		$this->assertArrayHasKey( 'mpdf', $links['views'] );
		$this->assertSame( '/index.php?title=Test&action=mpdf', $links['views']['mpdf']['href'] );
		$this->assertSame( wfMessage( 'mpdf-action' )->text(), $links['views']['mpdf']['text'] );
	}

	public function testOnSkinTemplateNavigationUniversalSkipsLinkWhenTabDisabled() {
		$this->overrideConfigValue( 'MpdfTab', false );

		$skinTemplate = $this->createMock( SkinTemplate::class );
		$skinTemplate->expects( $this->never() )->method( 'getTitle' );

		$links = [ 'views' => [] ];

		MpdfHooks::onSkinTemplateNavigationUniversal( $skinTemplate, $links );

		$this->assertArrayNotHasKey( 'mpdf', $links['views'] );
	}
}
