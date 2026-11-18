--TEST--
FormField::options() lists the labels of a combo or list box and which are selected
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';
$doc = Pdfium\Document::fromString(standard_form());
$by = [];
foreach ($doc->page(0)->formFields() as $f) {
    $by[$f->name()] = $f;
}
foreach (['country', 'langs', 'name', 'agree'] as $n) {
    echo $n, ': ', json_encode(array_map(fn($o) => [$o->label, $o->selected], $by[$n]->options())), "\n";
}
$o = $by['country']->options()[0];
var_dump(unserialize(serialize($o)) == $o);
var_dump($o instanceof Pdfium\FormFieldOption);
?>
--EXPECT--
country: [["UA",true],["PL",false],["Germany",false]]
langs: [["php",false],["c",false],["go",false]]
name: []
agree: []
bool(true)
bool(true)
