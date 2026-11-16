--TEST--
Page::render(region:) renders a rectangle of the page in page coordinates, sized from it, with background outside the page and the limits applied to the region only; a rectangle that only touches the page, has no area, or is on a page too large to place it is refused
--EXTENSIONS--
pdfium
zlib
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
$page = Pdfium\Document::fromString(page_pdf(100, 100, '1 0 0 rg 10 10 30 30 re f'))->page(0);
$letter = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf')->page(0);
$r = $page->render(dpi: 72, region: new Pdfium\Rect(10, 10, 40, 40));
var_dump($r->width(), $r->height());
var_dump(png_pixel(png_decode($r->toPng()), 15, 15) === [255, 0, 0]);
$r = $page->render(dpi: 144, region: new Pdfium\Rect(0, 0, 50, 25));
var_dump($r->width(), $r->height());
$out = png_decode($page->render(dpi: 72, region: new Pdfium\Rect(90, 90, 120, 120))->toPng());
var_dump($out['width'], png_pixel($out, 25, 5) === [255, 255, 255]);
$t = png_decode($page->render(dpi: 72, region: new Pdfium\Rect(90, 90, 120, 120), transparent: true)->toPng());
var_dump(png_pixel($t, 25, 5)[3]);
var_dump($page->render(dpi: 72, region: new Pdfium\Rect(50, 50, 50.1, 50.1))->width());
var_dump($letter->render(dpi: 2400, region: new Pdfium\Rect(0, 0, 10, 10))->width());
$wide = Pdfium\Document::fromString(page_pdf(200, 100, ''))->page(0);
foreach ([72, 150] as $dpi) {
    $r = $wide->render(dpi: $dpi, region: new Pdfium\Rect(30.4, 20.2, 130.7, 70.9));
    echo $r->width(), 'x', $r->height(), "\n";
}
$r = Pdfium\Document::fromFile(__DIR__ . '/fixtures/rotated.pdf')->page(0)->render(dpi: 72, region: new Pdfium\Rect(10.4, 20.2, 110.7, 70.9));
echo $r->width(), 'x', $r->height(), "\n";
foreach ([
    fn() => $page->render(region: new Pdfium\Rect(101, 0, 120, 10)),
    fn() => $page->render(region: new Pdfium\Rect(-20, -20, -1, -1)),
    fn() => $page->render(region: new Pdfium\Rect(100, 100, 130, 130)),
    fn() => $page->render(region: new Pdfium\Rect(-50, 0, 0, 100)),
    fn() => $page->render(region: new Pdfium\Rect(0, 100, 100, 100)),
    fn() => $page->render(region: new Pdfium\Rect(50, 50, 50, 50)),
    fn() => $page->render(region: new Pdfium\Rect(50, 40, 60, 40)),
    fn() => Pdfium\Document::fromString(page_pdf(150000, 100, ''))->page(0)->render(dpi: 2400, region: new Pdfium\Rect(-10, 0, 10, 10)),
    fn() => Pdfium\Document::fromString(page_pdf(100000000, 100, ''))->page(0)->render(dpi: 2400, region: new Pdfium\Rect(-10, 0, 10, 10)),
    fn() => $letter->render(dpi: 2400, region: new Pdfium\Rect(0, 0, 612, 792)),
    fn() => $page->render(dpi: 72, region: new Pdfium\Rect(-1e30, -1e30, 1e30, 1e30)),
    fn() => $letter->render(dpi: 2400, region: new Pdfium\Rect(-1e9, 0, 1e9, 10)),
    fn() => $page->render(region: (new ReflectionClass(Pdfium\Rect::class))->newInstanceWithoutConstructor()),
] as $fn) {
    try { $fn(); echo "ok\n"; } catch (Throwable $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
}
?>
--EXPECTF--
int(30)
int(30)
bool(true)
int(100)
int(50)
int(30)
bool(true)
int(0)
int(1)
int(333)
100x51
209x106
51x100
ValueError: Pdfium\Page::render(): Argument #2 ($region) must overlap the page
ValueError: Pdfium\Page::render(): Argument #2 ($region) must overlap the page
ValueError: Pdfium\Page::render(): Argument #2 ($region) must overlap the page
ValueError: Pdfium\Page::render(): Argument #2 ($region) must overlap the page
ValueError: Pdfium\Page::render(): Argument #2 ($region) must have a width and a height
ValueError: Pdfium\Page::render(): Argument #2 ($region) must have a width and a height
ValueError: Pdfium\Page::render(): Argument #2 ($region) must have a width and a height
ValueError: Pdfium\Page::render(): Argument #2 ($region) needs the whole page at %dx%d pixels, above the 4194304-pixel limit per side for a region
ValueError: Pdfium\Page::render(): Argument #2 ($region) needs the whole page at %dx%d pixels, above the 4194304-pixel limit per side for a region
ValueError: Pdfium\Page::render(): Argument #2 ($region) would render %dx%d pixels, above the 100000000-pixel ceiling
ValueError: Pdfium\Page::render(): Argument #2 ($region) would render %dx%d pixels, above the 65500-pixel limit per side
ValueError: Pdfium\Page::render(): Argument #2 ($region) would render %dx%d pixels, above the 65500-pixel limit per side
ValueError: Pdfium\Page::render(): Argument #2 ($region) must have every side set
