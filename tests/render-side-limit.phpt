--TEST--
Page::render() rejects a side longer than JPEG allows, even under the pixel ceiling
--EXTENSIONS--
pdfium
--FILE--
<?php
$objs = [
    '<< /Type /Catalog /Pages 2 0 R >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 14400 10] >>',
];
$pdf = "%PDF-1.7\n";
$xref = '';
foreach ($objs as $i => $body) {
    $xref .= sprintf("%010d 00000 n \n", strlen($pdf));
    $pdf .= ($i + 1) . " 0 obj\n$body\nendobj\n";
}
$pdf .= "xref\n0 4\n0000000000 65535 f \n{$xref}trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n" . strlen($pdf) . "\n%%EOF\n";
$page = Pdfium\Document::fromString($pdf)->page(0);
try { $page->render(dpi: 400); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
$img = $page->render(dpi: 327);
var_dump($img->width(), strlen($img->toJpeg()) > 0);
?>
--EXPECT--
Pdfium\Page::render(): Argument #1 ($dpi) would render 80000x56 pixels, above the 65500-pixel limit per side
int(65400)
bool(true)
