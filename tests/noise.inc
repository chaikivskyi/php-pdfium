<?php
// One page drawing a $side x $side image of random RGB pixels, stored raw: serialising writes every byte of
// it, and neither PNG nor JPEG can compress it. Rendered at 72 DPI, one point is one pixel.
function noise_pdf(int $side): string
{
    $raw = random_bytes(3 * $side * $side);
    $objs = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $side $side] /Resources << /XObject << /Im 4 0 R >> >> /Contents 5 0 R >>",
        "<< /Type /XObject /Subtype /Image /Width $side /Height $side /ColorSpace /DeviceRGB /BitsPerComponent 8 /Length "
            . strlen($raw) . " >>\nstream\n$raw\nendstream",
        "<< /Length " . strlen("q $side 0 0 $side 0 0 cm /Im Do Q") . " >>\nstream\nq $side 0 0 $side 0 0 cm /Im Do Q\nendstream",
    ];
    $pdf = "%PDF-1.7\n";
    $xref = '';
    foreach ($objs as $i => $body) {
        $xref .= sprintf("%010d 00000 n \n", strlen($pdf));
        $pdf .= ($i + 1) . " 0 obj\n$body\nendobj\n";
    }
    $size = count($objs) + 1;
    return $pdf . "xref\n0 $size\n0000000000 65535 f \n$xref"
        . "trailer\n<< /Size $size /Root 1 0 R >>\nstartxref\n" . strlen($pdf) . "\n%%EOF\n";
}
