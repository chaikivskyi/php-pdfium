--TEST--
Annotation::hasKey(), valueType(), stringValue() and numberValue() read the annotation's dictionary by key
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$a = Pdfium\Document::fromString(annots_pdf(
    '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] /Contents (hi) /T <FEFF00C9> /NM /name /Rotate 90 /Open true /IRT 9 0 R /Empty () /Width 2.5 /Popup 6 0 R >>',
))->page(0)->annotation(0);
var_dump($a->stringValue('Contents'), $a->stringValue('T'), $a->stringValue('NM'));
var_dump($a->stringValue('Rotate'), $a->stringValue('Missing'), $a->stringValue('Empty'));
var_dump($a->numberValue('Rotate'), $a->numberValue('Width'), $a->numberValue('Contents'), $a->numberValue('Missing'));
var_dump($a->hasKey('Contents'), $a->hasKey('Missing'), $a->hasKey('Empty'));
echo implode(' ', array_map($a->valueType(...), ['Contents', 'NM', 'Rotate', 'Open', 'Rect', 'IRT', 'Popup', 'Missing'])), "\n";
foreach (['hasKey', 'valueType', 'stringValue', 'numberValue'] as $method) {
    foreach (['', "a\0b"] as $k) {
        try { $a->$method($k); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
    }
}
?>
--EXPECT--
string(2) "hi"
string(2) "É"
string(4) "name"
NULL
NULL
NULL
float(90)
float(2.5)
NULL
NULL
bool(true)
bool(false)
bool(true)
String Name Number Boolean Array Reference Reference Unknown
Pdfium\Annotation::hasKey(): Argument #1 ($key) must not be empty
Pdfium\Annotation::hasKey(): Argument #1 ($key) must not contain a null byte
Pdfium\Annotation::valueType(): Argument #1 ($key) must not be empty
Pdfium\Annotation::valueType(): Argument #1 ($key) must not contain a null byte
Pdfium\Annotation::stringValue(): Argument #1 ($key) must not be empty
Pdfium\Annotation::stringValue(): Argument #1 ($key) must not contain a null byte
Pdfium\Annotation::numberValue(): Argument #1 ($key) must not be empty
Pdfium\Annotation::numberValue(): Argument #1 ($key) must not contain a null byte
