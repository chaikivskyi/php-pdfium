--TEST--
FormField::flags() reports the flags that apply to the field's type
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';
$doc = Pdfium\Document::fromString(standard_form());
$by = fields($doc);
foreach (['name', 'locked', 'parent.notes', 'city', 'langs', 'country'] as $n) {
    $set = array_keys(array_filter((array) $by[$n]->flags()));
    echo $n, ': ', json_encode($set), "\n";
}
var_dump($by['name']->flags());
$w = '/Type /Annot /Subtype /Widget /P 3 0 R /F 4';
$doc = Pdfium\Document::fromString(form_pdf([
    8 => "<< $w /FT /Tx /Ff 8198 /T (t) /Rect [0 0 50 20] >>",
    9 => "<< $w /FT /Btn /Ff 4096 /T (c) /V /Off /AS /Off /Rect [0 30 20 50] >>",
]));
foreach ($doc->page(0)->formFields() as $f) {
    echo $f->name(), ': ', json_encode(array_keys(array_filter((array) $f->flags()))), "\n";
}
?>
--EXPECTF--
name: []
locked: ["readOnly"]
parent.notes: ["multiline"]
city: ["editable"]
langs: ["multiSelect"]
country: []
object(Pdfium\FormFieldFlags)#%d (7) {
  ["readOnly"]=>
  bool(false)
  ["required"]=>
  bool(false)
  ["noExport"]=>
  bool(false)
  ["multiline"]=>
  bool(false)
  ["password"]=>
  bool(false)
  ["editable"]=>
  bool(false)
  ["multiSelect"]=>
  bool(false)
}
t: ["required","noExport","password"]
c: []
