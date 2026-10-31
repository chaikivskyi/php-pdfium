--TEST--
Page::render() sizes the bitmap from DPI and encodes PNG and JPEG
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$page = Pdfium\Document::open($f . 'text-3pages.pdf')->page(0);
$img = $page->render(dpi: 72);
var_dump($img->width(), $img->height());
$img = $page->render();
var_dump($img->width(), $img->height());
var_dump(str_starts_with($img->toPng(), "\x89PNG\r\n\x1a\n"), str_starts_with($img->toJpeg(), "\xFF\xD8\xFF"));
$out = sys_get_temp_dir() . '/pdfium-render-test';
$img->savePng("$out.png");
$img->saveJpeg("$out.jpg", quality: 50);
var_dump(getimagesize("$out.png")[0], getimagesize("$out.jpg")[2] === IMAGETYPE_JPEG);
unlink("$out.png"); unlink("$out.jpg");
$r = Pdfium\Document::open($f . 'rotated.pdf')->page(0)->render(dpi: 72);
var_dump($r->width(), $r->height());
$t = Pdfium\Document::open($f . 'tiny.pdf')->page(0)->render(dpi: 1);
var_dump($t->width(), $t->height());
$detached = Pdfium\Document::open($f . 'text-3pages.pdf')->page(2)->render(dpi: 10);
gc_collect_cycles();
var_dump(strlen($detached->toPng()) > 0);
foreach ([
    fn() => $page->render(dpi: 0),
    fn() => $page->render(dpi: 2401),
    fn() => $page->render(dpi: 2400),
    fn() => $img->toJpeg(quality: 0),
    fn() => $img->toJpeg(quality: 101),
    fn() => $img->savePng('/nonexistent-dir/x.png'),
] as $fn) {
    try { $fn(); echo "ok\n"; } catch (Throwable $e) { echo get_class($e), "\n"; }
}
?>
--EXPECT--
int(612)
int(792)
int(1275)
int(1650)
bool(true)
bool(true)
int(1275)
bool(true)
int(400)
int(300)
int(1)
int(1)
bool(true)
ValueError
ValueError
ValueError
ValueError
ValueError
Pdfium\FileException
