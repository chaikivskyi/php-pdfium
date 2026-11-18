--TEST--
FormField::setChecked() checks and unchecks checkboxes and radio buttons
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

function widgets(Pdfium\Document $doc): array
{
    $out = [];
    foreach ($doc->page(0)->formFields() as $f) {
        $out[$f->name()][] = $f;
    }
    return $out;
}

function attempt(Pdfium\Document $doc, Pdfium\FormField $f, bool $to, string $label): void
{
    $before = $f->isChecked();
    try {
        $f->setChecked($to);
        echo "$label: no exception\n";
    } catch (Throwable $e) {
        echo "$label: ", get_class($e), ': ', $e->getMessage(), "\n";
    }
    var_dump($f->isChecked() === $before);
    $doc->toString();
}

$doc = Pdfium\Document::fromString(standard_form());
$original = $doc->page(0)->render(dpi: 72)->toPng();
$agree = widgets($doc)['agree'][0];
$agree->setChecked();
var_dump($agree->isChecked(), $agree->value());
$agree->setChecked(true);
var_dump($agree->isChecked(), $agree->value());
$again = Pdfium\Document::fromString($doc->toString());
var_dump(widgets($again)['agree'][0]->isChecked());
var_dump($again->page(0)->render(dpi: 72)->toPng() !== $original);
$agree->setChecked(false);
var_dump($agree->isChecked(), $agree->value());
$agree->setChecked(false);
var_dump($agree->isChecked());
var_dump(widgets(Pdfium\Document::fromString($doc->toString()))['agree'][0]->isChecked());

[$red, $blue] = widgets($doc)['colour'];
$blue->setChecked();
var_dump($red->isChecked(), $blue->isChecked(), $red->value(), $blue->value());
$red->setChecked();
var_dump($red->isChecked(), $blue->isChecked(), $red->value(), $blue->value());
attempt($doc, $red, false, 'colour');
$again = Pdfium\Document::fromString($doc->toString());
[$r2, $b2] = widgets($again)['colour'];
var_dump($r2->isChecked(), $b2->isChecked());

attempt($doc, widgets($doc)['rocheck'][0], true, 'rocheck');

foreach (['name', 'country', 'go', 'sig', 'langs'] as $n) {
    attempt($doc, widgets($doc)[$n][0], true, $n);
}

function shared(string $ff): Pdfium\Document
{
    $w = '/Type /Annot /Subtype /Widget /P 3 0 R /F 4';
    $ap = check_ap(...);
    return Pdfium\Document::fromString(form_pdf([
        8 => "<< /FT /Btn $ff /T (g) /V /Off /Kids [9 0 R 10 0 R] >>",
        9 => "<< $w /Parent 8 0 R /AS /Off /Rect [100 650 120 670] {$ap('Yes')} >>",
        10 => "<< $w /Parent 8 0 R /AS /Off /Rect [140 650 160 670] {$ap('Yes')} >>",
    ], check_ap_objects()));
}

foreach (['checkbox' => '', 'radio-unison' => '/Ff 33587200', 'radio' => '/Ff 32768'] as $label => $ff) {
    $d = shared($ff);
    [$a, $b] = widgets($d)['g'];
    echo $label, ":\n";
    try {
        $a->setChecked();
        echo "first ok\n";
    } catch (Throwable $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
    var_dump($a->isChecked(), $b->isChecked(), $a->value());
    try {
        $b->setChecked();
        echo "second ok\n";
    } catch (Throwable $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
    var_dump($a->isChecked(), $b->isChecked(), $a->value());
    $r = Pdfium\Document::fromString($d->toString());
    [$ra, $rb] = widgets($r)['g'];
    var_dump($ra->isChecked(), $rb->isChecked());
}
?>
--EXPECT--
bool(true)
string(3) "Yes"
bool(true)
string(3) "Yes"
bool(true)
bool(true)
bool(false)
string(3) "Off"
bool(false)
bool(false)
bool(false)
bool(true)
string(4) "blue"
string(4) "blue"
bool(true)
bool(false)
string(3) "red"
string(3) "red"
colour: Pdfium\PdfiumException: PDFium did not change the field
bool(true)
bool(true)
bool(false)
rocheck: Pdfium\PdfiumException: PDFium did not change the field; it is read-only
bool(true)
name: Pdfium\PdfiumException: Cannot check or uncheck a Text field
bool(true)
country: Pdfium\PdfiumException: Cannot check or uncheck a ComboBox field
bool(true)
go: Pdfium\PdfiumException: Cannot check or uncheck a PushButton field
bool(true)
sig: Pdfium\PdfiumException: Cannot check or uncheck a Signature field
bool(true)
langs: Pdfium\PdfiumException: Cannot check or uncheck a ListBox field
bool(true)
checkbox:
first ok
bool(true)
bool(true)
string(3) "Yes"
second ok
bool(true)
bool(true)
string(3) "Yes"
bool(true)
bool(true)
radio-unison:
first ok
bool(true)
bool(true)
string(3) "Yes"
second ok
bool(true)
bool(true)
string(3) "Yes"
bool(true)
bool(true)
radio:
first ok
bool(true)
bool(false)
string(3) "Yes"
second ok
bool(false)
bool(true)
string(3) "Yes"
bool(false)
bool(true)