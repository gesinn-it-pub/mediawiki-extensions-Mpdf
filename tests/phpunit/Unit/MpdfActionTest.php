<?php

/**
 * @covers MpdfAction
 * @group MpdfAction
 */
class MpdfActionTest extends MediaWikiUnitTestCase {

	private function callSanitizeFilename( string $titletext ): string {
		$method = new ReflectionMethod( MpdfAction::class, 'sanitizeFilename' );
		$method->setAccessible( true );
		return $method->invoke( null, $titletext );
	}

	private function callImageSrcToDataUri( string $src ): string {
		$method = new ReflectionMethod( MpdfAction::class, 'imageSrcToDataUri' );
		$method->setAccessible( true );
		return $method->invoke( null, $src );
	}

	private function callParseMpdfConfig( string $html ): array {
		$method = new ReflectionMethod( MpdfAction::class, 'parseMpdfConfig' );
		$method->setAccessible( true );
		return $method->invoke( null, $html );
	}

	private function callBuildSimpleOutputHtml( string $url, string $titletext, string $bodyHtml ): string {
		$method = new ReflectionMethod( MpdfAction::class, 'buildSimpleOutputHtml' );
		$method->setAccessible( true );
		return $method->invoke( null, $url, $titletext, $bodyHtml );
	}

	private function callBuildHtmlDownloadHeaders( string $filename ): array {
		$method = new ReflectionMethod( MpdfAction::class, 'buildHtmlDownloadHeaders' );
		$method->setAccessible( true );
		return $method->invoke( null, $filename );
	}

	/**
	 * @covers MpdfAction::sanitizeFilename
	 * @dataProvider provideUnsafeFilenameCharacters
	 */
	public function testSanitizeFilenameReplacesUnsafeCharacters( string $titletext, string $expected ) {
		$this->assertSame( $expected, $this->callSanitizeFilename( $titletext ) );
	}

	public static function provideUnsafeFilenameCharacters(): array {
		return [
			'plain title' => [ 'Test_Title', 'Test_Title' ],
			'namespaced title with colon' => [ 'Category:Foo', 'Category_Foo' ],
			'path separators' => [ 'a/b\\c', 'a_b_c' ],
			'reserved characters' => [ 'a*b?c"d<e>f', 'a_b_c_d_e_f' ],
			'control characters' => [ "a\nb\rc\0d", 'a_b_c_d' ],
		];
	}

	/**
	 * @covers MpdfAction::imageSrcToDataUri
	 */
	public function testImageSrcToDataUriEncodesFileContentsAsBase64() {
		$imagePath = __DIR__ . '/../../images/MediaWiki-2020.png';
		$this->assertFileExists( $imagePath );

		$dataUri = $this->callImageSrcToDataUri( 'tests/images/MediaWiki-2020.png' );

		$expected = 'data:image/png;base64,' . base64_encode( file_get_contents( $imagePath ) );
		$this->assertSame( $expected, $dataUri );
	}

	/**
	 * @covers MpdfAction::imageSrcToDataUri
	 */
	public function testImageSrcToDataUriStripsLeadingSlash() {
		$dataUriWithSlash = $this->callImageSrcToDataUri( '/tests/images/MediaWiki-2020.png' );
		$dataUriWithoutSlash = $this->callImageSrcToDataUri( 'tests/images/MediaWiki-2020.png' );

		$this->assertSame( $dataUriWithoutSlash, $dataUriWithSlash );
	}

	/**
	 * @covers MpdfAction::parseMpdfConfig
	 */
	public function testParseMpdfConfigReturnsDefaultsWithoutConstructorTag() {
		$config = $this->callParseMpdfConfig( '<html><body>No constructor tag here</body></html>' );

		$this->assertSame( [
			'mode' => 'utf-8',
			'format' => 'A4',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 16,
			'margin_bottom' => 16,
			'margin_header' => 9,
			'margin_footer' => 9,
			'orientation' => 'P',
		], $config );
	}

	/**
	 * @covers MpdfAction::parseMpdfConfig
	 */
	public function testParseMpdfConfigAppliesConstructorTagOverrides() {
		$html = 'content<!--mpdf<constructor format="A5" orientation="L" '
			. 'margin-left="10" margin-right="11" margin-top="12" '
			. 'margin-bottom="13" margin-header="5" margin-footer="6" />mpdf-->';

		$config = $this->callParseMpdfConfig( $html );

		$this->assertSame( 'A5', $config['format'] );
		$this->assertSame( 'L', $config['orientation'] );
		$this->assertSame( 10.0, $config['margin_left'] );
		$this->assertSame( 11.0, $config['margin_right'] );
		$this->assertSame( 12.0, $config['margin_top'] );
		$this->assertSame( 13.0, $config['margin_bottom'] );
		$this->assertSame( 5.0, $config['margin_header'] );
		$this->assertSame( 6.0, $config['margin_footer'] );
	}

	/**
	 * @covers MpdfAction::parseMpdfConfig
	 */
	public function testParseMpdfConfigAppliesPartialConstructorTagOverrides() {
		$html = 'content<!--mpdf<constructor format="Letter" />mpdf-->';

		$config = $this->callParseMpdfConfig( $html );

		$this->assertSame( 'Letter', $config['format'] );
		// Everything else keeps its default.
		$this->assertSame( 'P', $config['orientation'] );
		$this->assertSame( 15, $config['margin_left'] );
	}

	/**
	 * @covers MpdfAction::buildSimpleOutputHtml
	 */
	public function testBuildSimpleOutputHtmlPrependsUrlAndTitleFooter() {
		$html = $this->callBuildSimpleOutputHtml(
			'https://example.org/wiki/Test_Title',
			'Test_Title',
			'<p>Body</p>'
		);

		$this->assertSame(
			"<p><em>https://example.org/wiki/Test_Title</em></p><h1>Test_Title</h1>\n<p>Body</p>",
			$html
		);
	}

	/**
	 * @covers MpdfAction::buildHtmlDownloadHeaders
	 */
	public function testBuildHtmlDownloadHeadersReturnsContentTypeAndDisposition() {
		$headers = $this->callBuildHtmlDownloadHeaders( 'Test_Title' );

		$this->assertSame( [
			'Content-Type: text/html',
			'Content-Disposition: attachment; filename="Test_Title.html"',
		], $headers );
	}
}
