--TEST--
Page::render(region:) gives the same pixels as that area of the full render, on a rotated page with an offset crop box and on a form page
--EXTENSIONS--
pdfium
zlib
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
$content = '1 0 0 rg 10 10 50 30 re f 0 1 0 rg 80 40 40 50 re f 0 0 1 rg 150 5 40 20 re f';
$plain = page_pdf(200, 100, $content);
$rotated = build_pdf([
    '<< /Type /Catalog /Pages 2 0 R >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 100] /CropBox [20 10 180 90] /Rotate 90 /Contents 4 0 R >>',
    stream_obj($content),
]);
$form = file_get_contents(__DIR__ . '/fixtures/form.pdf');
$cases = [
    [$plain, new Pdfium\Rect(30, 20, 130, 70), 30, 30, 100, 50],
    [$rotated, new Pdfium\Rect(30, 20, 130, 70), 10, 10, 50, 100],
    [$form, new Pdfium\Rect(50, 580, 450, 700), 50, 92, 400, 120],
];
foreach ($cases as [$pdf, $rect, $x, $y, $w, $h]) {
    foreach ([72, 144] as $dpi) {
        $s = $dpi / 72;
        $page = Pdfium\Document::fromString($pdf)->page(0);
        $full = png_decode($page->render(dpi: $dpi)->toPng());
        $part = png_decode($page->render(dpi: $dpi, region: $rect)->toPng());
        $slice = [];
        for ($j = 0; $j < $h * $s; $j++) {
            $slice[] = substr($full['rows'][$y * $s + $j], $x * $s * 3, $w * $s * 3);
        }
        var_dump($part['width'] === $w * $s && $part['height'] === $h * $s && $part['rows'] === $slice);
    }
}
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
