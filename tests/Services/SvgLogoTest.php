<?php

namespace Tests\Services;

use App\Services\SvgLogo;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;
use Tests\TestCase;

class SvgLogoTest extends TestCase {
    public function testSanitizesActiveContentBeforeRendering(): void {
        $service = new SvgLogo();
        $svg = $service->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" onload="alert(1)">' .
            '<script>alert(1)</script><foreignObject><div>unsafe</div></foreignObject>' .
            '<rect width="200" height="200" fill="#336699"/></svg>'
        );
        $this->assertStringNotContainsString('onload', $svg);
        $this->assertStringNotContainsString('script', $svg);
        $this->assertStringNotContainsString('foreignObject', $svg);
        $image = Image::make($service->rasterize($svg));
        $this->assertSame(135, $image->width());
        $this->assertSame(135, $image->height());
        $this->assertSame('#336699', $image->pickColor(60, 60, 'hex'));
    }

    public function testPreservesInternalGradientReferencesAndCss(): void {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">' .
            '<defs><linearGradient id="g"><stop offset="0" stop-color="red"/>' .
            '<stop offset="1" stop-color="blue"/></linearGradient></defs>' .
            '<style>.logo { fill: url("#g"); }</style>' .
            '<rect class="logo" width="200" height="200" style="stroke: url(#g)"/></svg>';
        $service = new SvgLogo();
        $sanitized = $service->sanitize($svg);
        $this->assertStringContainsString('linearGradient', $sanitized);
        $this->assertStringContainsString('url("#g")', $sanitized);
        $this->assertSame(135, Image::make($service->rasterize($sanitized))->width());
    }

    public function testSanitizerRemovesUnsafeImageReferences(): void {
        $svg = (new SvgLogo())->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">' .
            '<image href="relative.png"/><image href="https://example.test/image.png"/>' .
            '<image href="data:image/svg+xml;base64,PHN2Zy8+"/></svg>'
        );
        $this->assertStringNotContainsString('relative.png', $svg);
        $this->assertStringNotContainsString('example.test', $svg);
        $this->assertStringNotContainsString('base64', $svg);
    }

    public function testRasterizerPreservesAspectRatioAndBoundsHugeDimensions(): void {
        $service = new SvgLogo();
        $svg = $service->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" width="100000000" height="50000000" viewBox="0 0 200 100">' .
            '<rect width="200" height="100" fill="red"/></svg>'
        );
        $image = Image::make($service->rasterize($svg));
        $this->assertSame(135, $image->width());
        $this->assertSame(68, $image->height());
    }

    /**
     * @dataProvider invalidSvgProvider
     */
    public function testRejectsInvalidOrExternalResources(string $svg): void {
        $this->expectException(ValidationException::class);
        (new SvgLogo())->sanitize($svg);
    }

    public static function invalidSvgProvider(): iterable {
        yield 'malformed XML' => ['<svg xmlns="http://www.w3.org/2000/svg"><g></svg>'];
        yield 'not an SVG' => ['<html><body>not an image</body></html>'];
        yield 'wrong namespace' => ['<svg xmlns="http://www.w3.org/1999/xhtml"/>'];
        yield 'empty file' => [''];
        yield 'external entity' => ['<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]>' .
            '<svg xmlns="http://www.w3.org/2000/svg"><text>&x;</text></svg>'];
        foreach ([
            '<image href="/etc/passwd"/>',
            '<rect fill="url(/etc/passwd)"/>',
            '<rect style="fill: url(relative.svg#g)"/>',
            '<rect style="fill: red&#x7f;"/>',
            '<style>.logo { fill: url("relative.svg#g"); }</style>',
            '<style>.logo { fill: u\\72l("relative.svg#g"); }</style>',
            '<style>.logo { background: image("relative.png"); }</style>',
            '<style>.logo { background: image-set("relative.png" 1x); }</style>',
            '<style>.logo { background: -webkit-image-set("relative.png" 1x); }</style>',
        ] as $contents) {
            yield $contents => ['<svg xmlns="http://www.w3.org/2000/svg">' . $contents . '</svg>'];
        }
    }

    public function testRejectsUnrenderableSvg(): void {
        $service = new SvgLogo();
        $svg = $service->sanitize('<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0"/>');
        $this->expectException(ValidationException::class);
        $service->rasterize($svg);
    }
}
