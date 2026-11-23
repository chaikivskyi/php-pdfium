--TEST--
Page::formFields() lists the widgets of a page and FormField reads their name, type, value, state and place
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';
$doc = Pdfium\Document::fromString(standard_form());
$fields = $doc->page(0)->formFields();
var_dump(count($fields), array_is_list($fields));
foreach ($fields as $f) {
    echo json_encode([$f->name(), $f->type()->name, $f->value(), $f->alternateName(), $f->exportValue(),
        $f->isChecked(), $f->controlIndex(), $f->controlCount()]), "\n";
}
var_dump($fields[0]->rect() == new Pdfium\Rect(100, 700, 300, 720));
var_dump($doc->page(1)->formFields());
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/a4.pdf')->page(0)->formFields());
$on = form_pdf([8 => '<< /Type /Annot /Subtype /Widget /FT /Btn /T (on) /V /Yes /AS /Yes /Rect [0 0 20 20] /AP << /N << /Yes 22 0 R /Off 23 0 R >> >> >>'],
    [22 => stream_obj('', '/Type /XObject /Subtype /Form /BBox [0 0 20 20] '), 23 => stream_obj('', '/Type /XObject /Subtype /Form /BBox [0 0 20 20] ')]);
$c = Pdfium\Document::fromString($on)->page(0)->formFields()[0];
var_dump($c->isChecked(), $c->value(), $c->exportValue());
?>
--EXPECT--
int(12)
bool(true)
["name","Text","old","Your name",null,false,0,1]
["agree","Checkbox","Off",null,"Yes",false,0,1]
["colour","RadioButton","Off",null,"red",false,0,2]
["colour","RadioButton","Off",null,"blue",false,1,2]
["country","ComboBox","UA",null,null,false,0,1]
["city","ComboBox","",null,null,false,0,1]
["langs","ListBox","",null,null,false,0,1]
["locked","Text","fixed",null,null,false,0,1]
["parent.notes","Text","",null,null,false,0,1]
["sig","Signature","",null,null,false,0,1]
["go","PushButton","",null,null,false,0,1]
["rocheck","Checkbox","Off",null,"Yes",false,0,1]
bool(true)
array(0) {
}
array(0) {
}
bool(true)
string(3) "Yes"
string(3) "Yes"
