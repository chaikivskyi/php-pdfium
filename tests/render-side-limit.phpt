--TEST--
Page::render() rejects a side longer than JPEG allows, even under the pixel ceiling
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(page_pdf(14400, 10, ''))->page(0);
try { $page->render(dpi: 400); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
$img = $page->render(dpi: 327);
var_dump($img->width(), strlen($img->toJpeg()) > 0);
?>
--EXPECT--
Pdfium\Page::render(): Argument #1 ($dpi) would render 80000x56 pixels, above the 65500-pixel limit per side
int(65400)
bool(true)
