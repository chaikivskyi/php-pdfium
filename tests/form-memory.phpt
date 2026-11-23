--TEST--
value(), options(), formFields() and setValue() throw MemoryException, not a fatal error, when the result would not fit under memory_limit, and a small field still reads afterwards
--EXTENSIONS--
pdfium
--INI--
memory_limit=256M
--FILE--
<?php
require __DIR__ . '/form.inc';

$w = '/Type /Annot /Subtype /Widget /P 3 0 R /F 4';
$small = "<< $w /FT /Tx /T (small) /V (tiny) /Rect [100 600 300 620] >>";

function tight(int $mb): void
{
    ini_set('memory_limit', (string) (memory_get_usage(true) + ($mb << 20)));
}

$doc = Pdfium\Document::fromString(form_pdf([
    8 => "<< $w /FT /Tx /T (big) /V (" . str_repeat('a', 4 << 20) . ") /Rect [100 700 300 720] /AP << /N 10 0 R >> >>",
    9 => $small,
], [10 => stream_obj('', '/Type /XObject /Subtype /Form /BBox [0 0 200 20] ')]));
$page = $doc->page(0);
$big = field($page, 'big');
$tiny = field($page, 'small');
tight(3);
echo outcome(fn() => $big->value()), "\n";
var_dump($tiny->value());
ini_set('memory_limit', '256M');
var_dump(strlen($big->value()));

$opts = [];
for ($i = 0; $i < 50000; $i++) {
    $opts[] = "(option $i)";
}
$doc = Pdfium\Document::fromString(form_pdf([
    8 => "<< $w /FT /Ch /Ff 131072 /T (many) /Opt [" . implode(' ', $opts) . "] /Rect [100 700 300 720] >>",
    9 => $small,
]));
unset($opts);
$page = $doc->page(0);
$many = field($page, 'many');
$tiny = field($page, 'small');
tight(3);
echo outcome(fn() => $many->options()), "\n";
var_dump($tiny->value(), $tiny->options());
ini_set('memory_limit', '256M');
var_dump(count($many->options()), $many->options()[49999]->label);

$doc = Pdfium\Document::fromString(form_pdf([8 => $small], [
    3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots [' . str_repeat('8 0 R ', 100000) . '] >>',
]));
$page = $doc->page(0);
tight(4);
echo outcome(fn() => $page->formFields()), "\n";
ini_set('memory_limit', '256M');
$fields = $page->formFields();
var_dump(count($fields), $fields[0]->value());
$page2 = $doc->page(1);
var_dump($page2->formFields());

$doc = Pdfium\Document::fromString(form_pdf([8 => $small]));
$page = $doc->page(0);
$f = $page->formFields()[0];
$huge = str_repeat('a', 4 << 20);
tight(3);
echo outcome(fn() => $f->setValue($huge)), "\n";
ini_set('memory_limit', '256M');
var_dump($f->value());
$f->setValue(str_repeat('a', 1 << 18));
var_dump(strlen($f->value()));
$f->setValue('after');
var_dump($f->value(), strlen($doc->toString()) > 0);
?>
--EXPECT--
Pdfium\MemoryException: The form field needs more memory than memory_limit leaves
string(4) "tiny"
int(4194304)
Pdfium\MemoryException: The form field options need more memory than memory_limit leaves
string(4) "tiny"
array(0) {
}
int(50000)
string(12) "option 49999"
Pdfium\MemoryException: The form fields need more memory than memory_limit leaves
int(100000)
string(4) "tiny"
array(0) {
}
Pdfium\MemoryException: The form field needs more memory than memory_limit leaves
string(4) "tiny"
int(262144)
string(5) "after"
bool(true)
