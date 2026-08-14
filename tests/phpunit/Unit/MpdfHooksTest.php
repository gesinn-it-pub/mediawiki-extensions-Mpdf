<?php

/**
 * @group MpdfHooks
 * @group Skin
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

	/**
	 * @covers MpdfHooks::mpdftagsRender
	 */
	public function testMpdftagsRenderWrapsParamsInMpdfComment() {
		$parser = $this->createMock( Parser::class );
		$parser->method( 'insertStripItem' )->willReturnArgument( 0 );

		$result = MpdfHooks::mpdftagsRender( $parser, 'format="A5"', 'orientation="L"' );

		$this->assertSame(
			"<!--mpdf<format=\"A5\" />\n<orientation=\"L\" />\nmpdf-->\n",
			$result
		);
	}

	/**
	 * @covers MpdfHooks::mpdftagsRender
	 */
	public function testMpdftagsRenderEscapesAngleBrackets() {
		$parser = $this->createMock( Parser::class );
		$parser->method( 'insertStripItem' )->willReturnArgument( 0 );

		$result = MpdfHooks::mpdftagsRender( $parser, '<script>alert(1)</script>' );

		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $result );
	}
}
