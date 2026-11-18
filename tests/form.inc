<?php
require_once __DIR__ . '/pdf.inc';

function check_ap(string $on, int $first = 22): string
{
    return "/AP << /N << /$on $first 0 R /Off " . ($first + 1) . ' 0 R >> >>';
}

function check_ap_objects(int $first = 22): array
{
    return [
        $first => stream_obj('0 g 0 0 20 20 re f', '/Type /XObject /Subtype /Form /BBox [0 0 20 20] '),
        $first + 1 => stream_obj('', '/Type /XObject /Subtype /Form /BBox [0 0 20 20] '),
    ];
}

function text_ap(string $text): string
{
    return stream_obj("/Tx BMC BT /Helv 12 Tf 0 g 2 6 Td ($text) Tj ET EMC",
        '/Type /XObject /Subtype /Form /BBox [0 0 200 20] /Resources << /Font << /Helv 5 0 R >> >> ');
}

function standard_form(): string
{
    $ap = check_ap(...);
    $w = '/Type /Annot /Subtype /Widget /P 3 0 R /F 4';
    return form_pdf([
        8 => "<< $w /FT /Tx /T (name) /TU (Your name) /V (old) /MaxLen 10 /Rect [100 700 300 720] >>",
        9 => "<< $w /FT /Btn /T (agree) /V /Off /AS /Off /Rect [100 650 120 670] {$ap('Yes')} >>",
        10 => '<< /FT /Btn /Ff 49152 /T (colour) /V /Off /Kids [11 0 R 12 0 R] >>',
        11 => "<< $w /Parent 10 0 R /AS /Off /Rect [100 600 120 620] {$ap('red')} >>",
        12 => "<< $w /Parent 10 0 R /AS /Off /Rect [140 600 160 620] {$ap('blue')} >>",
        13 => "<< $w /FT /Ch /Ff 131072 /T (country) /Opt [(UA) (PL) [(de) (Germany)]] /V (UA) /Rect [100 550 300 570] >>",
        14 => "<< $w /FT /Ch /Ff 393216 /T (city) /Opt [(Kyiv) (Lviv)] /Rect [100 500 300 520] >>",
        15 => "<< $w /FT /Ch /Ff 2097152 /T (langs) /Opt [(php) (c) (go)] /Rect [100 400 300 480] >>",
        16 => "<< $w /FT /Tx /Ff 1 /T (locked) /V (fixed) /Rect [100 350 300 370] >>",
        17 => '<< /T (parent) /Kids [18 0 R] >>',
        18 => "<< $w /Parent 17 0 R /FT /Tx /Ff 4096 /T (notes) /Rect [100 250 300 330] >>",
        19 => "<< $w /FT /Sig /T (sig) /Rect [100 200 300 240] >>",
        20 => "<< $w /FT /Btn /Ff 65536 /T (go) /Rect [100 150 160 180] >>",
        21 => "<< $w /FT /Btn /Ff 1 /T (rocheck) /V /Off /AS /Off /Rect [200 650 220 670] {$ap('Yes')} >>",
    ], [
        7 => '<< /Type /Annot /Subtype /Link /Rect [10 10 50 50] /Border [0 0 0] >>',
    ] + check_ap_objects());
}

function repeat_pdf(): string
{
    $w = '/Type /Annot /Subtype /Widget /Parent 7 0 R /F 4 /Rect [100 700 300 720]';
    return form_pdf([7 => '<< /FT /Tx /T (shared) /V (old) /Kids [8 0 R 9 0 R] >>'], [
        8 => "<< $w /P 3 0 R /AP << /N 10 0 R >> >>",
        9 => "<< $w /P 6 0 R /AP << /N 11 0 R >> >>",
        10 => text_ap('old'),
        11 => text_ap('old'),
    ], [9]);
}

function field(Pdfium\Page|Pdfium\Document $in, string $name): Pdfium\FormField
{
    foreach (($in instanceof Pdfium\Document ? $in->page(0) : $in)->formFields() as $f) {
        if ($f->name() === $name) {
            return $f;
        }
    }
    throw new LogicException($name);
}
