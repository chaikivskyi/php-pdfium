<?php
// Regenerates every fixture in this directory; run in the dev image (needs qpdf).
declare(strict_types=1);

const MEDIA = '[0 0 612 792]';

/** @param array<int,string> $objs object bodies keyed 1..n */
function build(array $objs, string $trailer): string
{
    $out = "%PDF-1.7\n";
    $offsets = [];
    foreach ($objs as $n => $body) {
        $offsets[$n] = strlen($out);
        $out .= "$n 0 obj\n$body\nendobj\n";
    }
    $size = count($objs) + 1;
    $xref = strlen($out);
    $out .= "xref\n0 $size\n0000000000 65535 f \n";
    foreach ($offsets as $o) {
        $out .= sprintf("%010d 00000 n \n", $o);
    }
    return $out . "trailer\n<< /Size $size /Root 1 0 R $trailer >>\nstartxref\n$xref\n%%EOF\n";
}

function stream(string $data, string $dict = ''): string
{
    return "<< $dict/Length " . strlen($data) . " >>\nstream\n$data\nendstream";
}

const HELV = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

/** @param string[] $texts one page per entry */
function textDoc(array $texts, string $info): string
{
    $n = count($texts);
    $kids = [];
    $objs = [];
    for ($i = 0; $i < $n; $i++) {
        $page = 4 + 2 * $i;
        $kids[] = "$page 0 R";
        $objs[$page] = '<< /Type /Page /Parent 2 0 R /MediaBox ' . MEDIA
            . ' /Resources << /Font << /F1 3 0 R >> >> /Contents ' . ($page + 1) . ' 0 R >>';
        $objs[$page + 1] = stream("BT /F1 24 Tf 72 700 Td ({$texts[$i]}) Tj ET");
    }
    $head = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '<< /Type /Pages /Kids [' . implode(' ', $kids) . "] /Count $n >>",
        3 => HELV,
    ];
    $objs = $head + $objs;
    $objs[4 + 2 * $n] = $info;
    return build($objs, '/Info ' . (4 + 2 * $n) . ' 0 R');
}

function blankPage(string $extra): string
{
    return build([
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        3 => "<< /Type /Page /Parent 2 0 R $extra >>",
    ], '');
}

function form(bool $withValue): string
{
    $v = $withValue ? ' /V (FORMVALUE)' : '';
    return build([
        1 => '<< /Type /Catalog /Pages 2 0 R /AcroForm 5 0 R >>',
        2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        3 => '<< /Type /Page /Parent 2 0 R /MediaBox ' . MEDIA . ' /Annots [4 0 R] >>',
        4 => '<< /Type /Annot /Subtype /Widget /FT /Tx /T (field1)' . $v
            . ' /Rect [100 600 400 650] /F 4 /DA (/Helv 24 Tf 0 g) /P 3 0 R >>',
        5 => '<< /Fields [4 0 R] /NeedAppearances true /DA (/Helv 24 Tf 0 g) /DR << /Font << /Helv 6 0 R >> >> >>',
        6 => HELV,
    ], '');
}

function unicodeDoc(): string
{
    $cmap = "/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n"
        . "/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n"
        . "/CMapName /Adobe-Identity-UCS def\n/CMapType 2 def\n"
        . "1 begincodespacerange\n<00> <FF>\nendcodespacerange\n"
        . "3 beginbfchar\n<01> <D83DDE00>\n<02> <D800>\n<03> <0041>\nendbfchar\n"
        . "endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend";
    return build([
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        3 => '<< /Type /Page /Parent 2 0 R /MediaBox ' . MEDIA
            . ' /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
        4 => stream("BT /F1 24 Tf 72 700 Td <010203> Tj ET"),
        5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /ToUnicode 6 0 R >>',
        6 => stream($cmap),
    ], '');
}

$dir = __DIR__;
$tmp = sys_get_temp_dir() . '/pdfium-fixtures-' . getmypid();
mkdir($tmp);

function sh(string $cmd): void
{
    passthru($cmd . ' 2>&1', $rc);
    if ($rc !== 0) {
        fwrite(STDERR, "failed: $cmd\n");
        exit(1);
    }
}

// qpdf rewrites the xref canonically; --deterministic-id keeps the trailer /ID stable across runs.
function normalise(string $raw, string $name): void
{
    global $dir, $tmp;
    file_put_contents("$tmp/$name", $raw);
    sh('qpdf --deterministic-id ' . escapeshellarg("$tmp/$name") . ' ' . escapeshellarg("$dir/$name"));
}

$author = '<FEFF' . strtoupper(bin2hex(mb_convert_encoding('Ünïcode Author', 'UTF-16BE', 'UTF-8'))) . '>';
$info = "<< /Title (Fixture Title) /Author $author /CreationDate (D:20261031120000Z) >>";

normalise(textDoc(['Page 1 alpha', 'Page 2 beta', 'Page 3 gamma'], $info), 'text-3pages.pdf');
normalise(blankPage('/MediaBox [0 0 612 792] /CropBox [0 0 300 400] /Rotate 90'), 'rotated.pdf');
normalise(blankPage('/MediaBox [0 0 1 1]'), 'tiny.pdf');
normalise(build([
    1 => '<< /Type /Catalog /Pages 2 0 R >>',
    2 => '<< /Type /Pages /Kids [] /Count 0 >>',
], ''), 'empty.pdf');
normalise(unicodeDoc(), 'unicode.pdf');
normalise(form(true), 'form.pdf');
normalise(form(false), 'form-empty.pdf');

// qpdf 11 rejects --deterministic-id with encryption and AES-256 keys/IVs are random per run,
// so existing encrypted fixtures are kept to avoid git churn; delete them to regenerate.
$src = escapeshellarg("$dir/text-3pages.pdf");
if (!file_exists("$dir/encrypted-user.pdf")) {
    sh("qpdf --encrypt user owner 256 -- $src " . escapeshellarg("$dir/encrypted-user.pdf"));
}
if (!file_exists("$dir/encrypted-owner.pdf")) {
    sh("qpdf --encrypt '' owner 256 --print=none --modify=none --extract=n -- $src " . escapeshellarg("$dir/encrypted-owner.pdf"));
}
if (!file_exists("$dir/encrypted-r2.pdf")) {
    sh("qpdf --allow-weak-crypto --encrypt '' owner 40 --print=n --modify=n --extract=y --annotate=y -- $src "
        . escapeshellarg("$dir/encrypted-r2.pdf"));
}

file_put_contents("$dir/truncated.pdf", substr(file_get_contents("$dir/text-3pages.pdf"), 0, 200));
file_put_contents("$dir/not-a-pdf.txt", "this is not a pdf\n");

array_map('unlink', glob("$tmp/*"));
rmdir($tmp);
