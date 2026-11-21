<?php
/** @param string[] $objs object bodies, numbered from 1; object 1 is the catalog */
function build_pdf(array $objs, string $trailer = ''): string
{
    $pdf = "%PDF-1.7\n";
    $xref = '';
    foreach ($objs as $i => $body) {
        $xref .= sprintf("%010d 00000 n \n", strlen($pdf));
        $pdf .= ($i + 1) . " 0 obj\n$body\nendobj\n";
    }
    $size = count($objs) + 1;
    return $pdf . "xref\n0 $size\n0000000000 65535 f \n$xref"
        . "trailer\n<< /Size $size /Root 1 0 R $trailer>>\nstartxref\n" . strlen($pdf) . "\n%%EOF\n";
}

function stream_obj(string $data, string $dict = ''): string
{
    return "<< $dict/Length " . strlen($data) . " >>\nstream\n$data\nendstream";
}

/** One page whose content stream is $content, with Helvetica as /F1 and $resources added. */
function page_pdf(int $w, int $h, string $content, string $resources = '', array $extra = []): string
{
    return build_pdf(array_merge([
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $w $h] /Resources << /Font << /F1 5 0 R >> $resources >> /Contents 4 0 R >>",
        stream_obj($content),
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ], $extra));
}

// A $side x $side image of random RGB pixels, stored raw: serialising writes every byte of it, and neither PNG
// nor JPEG can compress it. Rendered at 72 DPI, one point is one pixel.
function noise_pdf(int $side): string
{
    return page_pdf($side, $side, "q $side 0 0 $side 0 0 cm /Im Do Q", '/XObject << /Im 6 0 R >>', [stream_obj(
        random_bytes(3 * $side * $side),
        "/Type /XObject /Subtype /Image /Width $side /Height $side /ColorSpace /DeviceRGB /BitsPerComponent 8 ",
    )]);
}

// PDFium reads at most 32767 characters from one string operand, so the text goes out 10000 at a time.
function text_pdf(int $chars): string
{
    return page_pdf(612, 792, 'BT /F1 1 Tf 0 0 Td ' . str_repeat('(' . str_repeat('x', 10000) . ') Tj ', intdiv($chars, 10000)) . 'ET');
}

function cmap_pdf(string $content, string ...$bfchars): string
{
    $cmap = '/CIDInit /ProcSet findresource begin 12 dict begin begincmap /CMapName /X def 1 begincodespacerange <00> <FF> '
        . 'endcodespacerange ' . count($bfchars) . ' beginbfchar ' . implode(' ', $bfchars)
        . ' endbfchar endcmap CMapName currentdict /CMap defineresource pop end end';
    return page_pdf(612, 792, $content, '/Font << /F2 6 0 R >>', [
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /ToUnicode 7 0 R >>',
        stream_obj($cmap),
    ]);
}

function lines_pdf(string ...$lines): string
{
    $content = '';
    foreach ($lines as $i => $line) {
        $content .= 'BT /F1 12 Tf 72 ' . (700 - 100 * $i) . ' Td (' . $line . ') Tj ET ';
    }
    return page_pdf(612, 792, $content);
}

function annot_pdf(): string
{
    return build_pdf([
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] /Contents 4 0 R /Annots [5 0 R] >>',
        stream_obj(''),
        '<< /Type /Annot /Subtype /Square /Rect [10 60 40 90] /F 4 /AP << /N 6 0 R >> >>',
        stream_obj('0 g 0 0 30 30 re f', '/Type /XObject /Subtype /Form /BBox [0 0 30 30] '),
    ]);
}

function nav_pdf(string $catalog = '', array $extra = [], array $pageExtra = ['', '', ''], string $trailer = ''): string
{
    $pages = [];
    for ($i = 0; $i < 3; $i++) {
        $pages[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] ' . ($pageExtra[$i] ?? '') . ' >>';
    }
    return build_pdf(array_merge([
        "<< /Type /Catalog /Pages 2 0 R $catalog >>",
        '<< /Type /Pages /Kids [3 0 R 4 0 R 5 0 R] /Count 3 >>',
    ], $pages, $extra), $trailer);
}

function attach_pdf(string $names, array $extra = [], string $catalog = ''): string
{
    return nav_pdf("/Names << /EmbeddedFiles << /Names [$names] >> >> $catalog", $extra);
}

function file_spec(string $name, int $stream, string $more = ''): string
{
    return "<< /Type /Filespec /F ($name) /UF ($name) /EF << /F $stream 0 R >> $more >>";
}

function font_pdf(string $baseFont): string
{
    return build_pdf([
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 300] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
        stream_obj("BT /F1 100 Tf 100 100 Td (A) Tj ET"),
        "<< /Type /Font /Subtype /TrueType /BaseFont /$baseFont >>",
    ]);
}

function js_pdf(string $names, array $extra): string
{
    return nav_pdf("/Names << /JavaScript << /Names [$names] >> >>", $extra);
}
