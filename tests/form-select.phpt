--TEST--
FormField::select() selects and deselects the options of combo boxes and list boxes
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

function chosen(Pdfium\FormField $f): string
{
    return implode(',', array_map(fn($o) => (int) $o->selected, $f->options()));
}

function state(Pdfium\FormField $f): string
{
    return $f->options() ? chosen($f) : $f->value();
}

function attempt(Pdfium\Document $doc, Pdfium\FormField $f, int $i, bool $to, string $label): void
{
    $before = state($f);
    echo "$label: ", outcome(fn() => $f->select($i, $to)), "\n";
    var_dump(state($f) === $before);
    $doc->toString();
}

$doc = Pdfium\Document::fromString(standard_form());
$f = fields($doc);
$country = $f['country'];
echo chosen($country), "\n";
$country->select(2);
echo chosen($country), ' ', $country->value(), "\n";
$r = fields(Pdfium\Document::fromString($doc->toString()))['country'];
echo chosen($r), ' ', $r->value(), "\n";

$langs = $f['langs'];
$langs->select(0);
$langs->select(2);
echo chosen($langs), "\n";
$langs->select(0, false);
echo chosen($langs), ' ', $langs->value(), "\n";
echo chosen(fields(Pdfium\Document::fromString($doc->toString()))['langs']), "\n";

attempt($doc, $country, 0, false, 'country deselect');
attempt($doc, $country, 2, false, 'country deselect 2');
echo chosen($country), ' ', var_export($country->value(), true), "\n";
$r = fields(Pdfium\Document::fromString($doc->toString()))['country'];
echo chosen($r), ' ', var_export($r->value(), true), "\n";

$ro = Pdfium\Document::fromString(form_pdf([
    8 => '<< /Type /Annot /Subtype /Widget /P 3 0 R /F 4 /FT /Ch /Ff 1 /T (l) /Opt [(a) (b)] /Rect [100 400 300 480] >>',
    9 => '<< /Type /Annot /Subtype /Widget /P 3 0 R /F 4 /FT /Ch /Ff 131073 /T (c) /Opt [(a) (b)] /Rect [100 300 300 320] >>',
    10 => '<< /Type /Annot /Subtype /Widget /P 3 0 R /F 4 /FT /Ch /Ff 2097152 /T (e) /Opt [] /Rect [100 200 300 220] >>',
]));
$g = fields($ro);
$g['l']->select(1);
echo chosen($g['l']), "\n";
$g['c']->select(1);
echo chosen($g['c']), "\n";
echo chosen(fields(Pdfium\Document::fromString($ro->toString()))['l']), "\n";

echo "-- a single-selection list box and a repeated combo selection\n";
$one = Pdfium\Document::fromString(form_pdf([
    8 => '<< /Type /Annot /Subtype /Widget /P 3 0 R /F 4 /FT /Ch /T (single) /Opt [(a) (b) (c)] /V (a) /Rect [100 400 300 480] >>',
]));
$s = fields($one)['single'];
echo chosen($s), "\n";
$s->select(1);
echo chosen($s), ' ', $s->value(), "\n";
$s->select(2);
echo chosen($s), ' ', $s->value(), "\n";
echo chosen(fields(Pdfium\Document::fromString($one->toString()))['single']), "\n";
$country->select(2);
echo chosen($country), ' ', $country->value(), "\n";
$country->select(1);
echo chosen($country), ' ', $country->value(), "\n";
$country->select(1);
echo chosen($country), ' ', $country->value(), "\n";

foreach ([-1, 3] as $i) {
    attempt($doc, $country, $i, true, "country $i");
}
attempt($ro, $g['e'], 0, true, 'empty');
attempt($doc, $f['name'], 0, true, 'name');
attempt($doc, $f['agree'], 0, true, 'agree');
attempt($doc, $f['go'], 0, true, 'go');
attempt($doc, $f['sig'], 0, true, 'sig');
?>
--EXPECT--
1,0,0
0,0,1 de
0,0,1 de
1,0,1
0,0,1 go
0,0,1
country deselect: Pdfium\PdfiumException: PDFium did not change the selection
bool(true)
country deselect 2: Pdfium\PdfiumException: PDFium did not change the selection
bool(true)
0,0,1 'de'
0,0,1 'de'
0,1
0,1
0,1
-- a single-selection list box and a repeated combo selection
1,0,0
0,1,0 b
0,0,1 c
0,0,1
0,0,1 de
0,1,0 PL
0,1,0 PL
country -1: ValueError: Pdfium\FormField::select(): Argument #1 ($option) must be between 0 and 2
bool(true)
country 3: ValueError: Pdfium\FormField::select(): Argument #1 ($option) must be between 0 and 2
bool(true)
empty: ValueError: Pdfium\FormField::select(): Argument #1 ($option) must be an option index, but the field has no options
bool(true)
name: Pdfium\PdfiumException: Cannot select an option of a Text field
bool(true)
agree: Pdfium\PdfiumException: Cannot select an option of a Checkbox field
bool(true)
go: Pdfium\PdfiumException: Cannot select an option of a PushButton field
bool(true)
sig: Pdfium\PdfiumException: Cannot select an option of a Signature field
bool(true)
