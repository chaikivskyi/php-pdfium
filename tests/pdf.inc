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

function form_xobject(string $content, int $w, int $h): string
{
    return stream_obj($content, "/Type /XObject /Subtype /Form /BBox [0 0 $w $h] ");
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
        form_xobject('0 g 0 0 30 30 re f', 30, 30),
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

function annots_pdf(string ...$annots): string
{
    $refs = [];
    $objs = [];
    foreach ($annots as $a) {
        if (str_starts_with($a, '!')) {
            $refs[] = substr($a, 1);
        } else {
            $refs[] = (6 + count($objs)) . ' 0 R';
            $objs[] = $a;
        }
    }
    return nav_pdf(extra: $objs, pageExtra: ['/Annots [' . implode(' ', $refs) . ']', '', '']);
}

function attach_pdf(string $names, array $extra = [], string $catalog = ''): string
{
    return nav_pdf("/Names << /EmbeddedFiles << /Names [$names] >> >> $catalog", $extra);
}

function file_spec(string $name, int $stream, string $more = ''): string
{
    return "<< /Type /Filespec /F ($name) /UF ($name) /EF << /F $stream 0 R >> $more >>";
}

function sig_pdf(string $fields, array $extra = []): string
{
    return nav_pdf("/AcroForm << /Fields [$fields] /SigFlags 3 >>", $extra);
}

function sig_field(string $value): string
{
    return "<< /FT /Sig /T (Sig) /V $value >>";
}

function echo_outcome(callable $f): void
{
    try {
        $f();
        echo "no exception\n";
    } catch (Throwable $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
}

function color(?Pdfium\Color $c): string
{
    return $c ? "$c->red,$c->green,$c->blue,$c->alpha" : 'null';
}

function point(Pdfium\Point $p): string
{
    return "$p->x,$p->y";
}

function quad(Pdfium\Quad $q): string
{
    return "$q->x1 $q->y1 $q->x2 $q->y2 $q->x3 $q->y3 $q->x4 $q->y4";
}

function form_pdf(array $fields, array $extra = [], array $page2Annots = []): string
{
    $objs = $fields + $extra;
    $annots = [];
    $roots = [];
    ksort($objs);
    foreach ($objs as $n => $body) {
        $widget = str_contains($body, '/Subtype /Widget');
        if ($widget && in_array($n, $page2Annots, true)) {
            continue;
        }
        if ($widget || str_contains($body, '/Subtype /Link')) {
            $annots[] = "$n 0 R";
        }
        if (isset($fields[$n]) && !str_contains($body, '/Parent')) {
            $roots[] = "$n 0 R";
        }
    }
    $p2 = implode(' ', array_map(fn($n) => "$n 0 R", $page2Annots));
    $all = [
        1 => '<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>',
        2 => '<< /Type /Pages /Kids [3 0 R 6 0 R] /Count 2 >>',
        3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots [' . implode(' ', $annots) . '] >>',
        4 => '<< /Fields [' . implode(' ', $roots) . '] /DA (/Helv 12 Tf 0 g) /DR << /Font << /Helv 5 0 R >> >> >>',
        5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        6 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots [$p2] >>",
    ];
    $objs = $extra + $fields + $all;
    $list = [];
    for ($i = 1, $max = max(array_keys($objs)); $i <= $max; $i++) {
        $list[] = $objs[$i] ?? 'null';
    }
    return build_pdf($list);
}
