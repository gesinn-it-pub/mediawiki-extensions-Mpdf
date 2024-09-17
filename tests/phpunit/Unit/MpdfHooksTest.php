<?php

/**
 * @group MpdfHooks
 */
class MpdfHooksTest extends MediaWikiUnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		global $wgMpdfToolboxLink;
		$wgMpdfToolboxLink = true;
	}

	/**
	 * @covers MpdfHooks::onParserFirstCallInit
	 */
	public function testOnParserFirstCallInit() {
		$parser = $this->createMock( Parser::class );
		$parser->expects( $this->once() )
			->method( 'setFunctionHook' )
			->with(
				'mpdftags',
				$this->equalTo( [ 'MpdfHooks', 'mpdftagsRender' ] )
			);

		MpdfHooks::onParserFirstCallInit( $parser );
	}

	/**
	 * @covers MpdfHooks::onSidebarBeforeOutput
	 */
	public function testOnSidebarBeforeOutputWhenMpdfToolboxLinkIsFalse() {
		global $wgMpdfToolboxLink;
		$wgMpdfToolboxLink = false;

		$skin = $this->createMock( Skin::class );
		$sidebar = [];

		$result = MpdfHooks::onSidebarBeforeOutput( $skin, $sidebar );

		$this->assertTrue( $result );
		$this->assertArrayNotHasKey( 'mpdf', $sidebar[ 'TOOLBOX' ] ?? [] );
	}

	/**
	 * @covers MpdfHooks::onSidebarBeforeOutput
	 */
	public function testOnSidebarBeforeOutputWhenSpecialPage() {
		global $wgMpdfToolboxLink;
		$wgMpdfToolboxLink = true;

		$title = $this->createMock( Title::class );
		$title->method( 'isSpecialPage' )->willReturn( true );

		$skin = $this->createMock( Skin::class );
		$skin->method( 'getTitle' )->willReturn( $title );

		$sidebar = [];

		$result = MpdfHooks::onSidebarBeforeOutput( $skin, $sidebar );

		$this->assertTrue( $result );
		$this->assertArrayNotHasKey( 'mpdf', $sidebar[ 'TOOLBOX' ] ?? [] );
	}

	/**
	 * @covers MpdfHooks::onSidebarBeforeOutput
	 */
	public function testOnSidebarBeforeOutputAddsMpdfLink() {
		global $wgMpdfToolboxLink;
		$wgMpdfToolboxLink = true;

		$title = $this->createMock( Title::class );
		$title->method( 'isSpecialPage' )->willReturn( false );
		$title->method( 'getLocalUrl' )->with( [ 'action' => 'mpdf' ] )->willReturn( '/index.php?action=mpdf' );

		$skin = $this->createMock( Skin::class );
		$skin->method( 'getTitle' )->willReturn( $title );

		$sidebar = [];

		$result = MpdfHooks::onSidebarBeforeOutput( $skin, $sidebar );

		$this->assertTrue( $result );
		$this->assertArrayHasKey( 'TOOLBOX', $sidebar );
		$this->assertArrayHasKey( 'mpdf', $sidebar[ 'TOOLBOX' ] );
		$this->assertEquals( 'mpdf-action', $sidebar[ 'TOOLBOX' ][ 'mpdf' ][ 'msg' ] );
		$this->assertEquals( '/index.php?action=mpdf', $sidebar[ 'TOOLBOX' ][ 'mpdf' ][ 'href' ] );
		$this->assertEquals( 't-mpdf', $sidebar[ 'TOOLBOX' ][ 'mpdf' ][ 'id' ] );
		$this->assertEquals( 'mpdf', $sidebar[ 'TOOLBOX' ][ 'mpdf' ][ 'rel' ] );
	}
}
