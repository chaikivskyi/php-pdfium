--TEST--
A FormField keeps its widget when annotations before it are removed and throws once its widget is removed; after Page::flatten(), Annotations made earlier throw, through any Page object, while those of other pages stay usable
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

$doc = Pdfium\Document::fromString(standard_form());
$page = $doc->page(0);
$fields = $page->formFields();
$agree = $fields[1];
$at = $page->formFieldAt(110, 660);
$name = $fields[0];
var_dump($page->annotation(0)->type(), $agree->name(), $at->name());
$page->removeAnnotation(0);
var_dump($agree->name(), $agree->type()->name, $at->name(), $name->name());
$agree->setChecked();
var_dump($agree->isChecked(), $page->formFields()[1]->isChecked());
$page->annotation($page->annotationCount() - 1)->remove();
echo_outcome(fn() => end($fields)->name());
$page->annotation(0)->remove();
echo_outcome(fn() => $name->name());
echo_outcome(fn() => $name->setValue('x'));
var_dump($agree->name(), $page->formFields()[0]->name());

$doc = Pdfium\Document::fromString(standard_form());
$page = $doc->page(0);
$other = $doc->page(0);
$link = $page->annotation(0);
$widget = $other->annotation(1);
$elsewhere = $doc->page(1)->addAnnotation('Text', new Pdfium\Rect(10, 10, 30, 30));
var_dump($page->flatten(), $page->annotationCount());
foreach ([
    'type' => fn() => $link->type(),
    'index' => fn() => $link->index(),
    'rect' => fn() => $link->rect(),
    'stringValue' => fn() => $widget->stringValue('T'),
    'setStringValue' => fn() => $widget->setStringValue('Contents', 'lost'),
    'setRect' => fn() => $widget->setRect(new Pdfium\Rect(0, 0, 10, 10)),
    'remove' => fn() => $widget->remove(),
] as $what => $call) {
    echo $what, ': ';
    echo_outcome($call);
}
var_dump(str_contains($doc->toString(), '(lost)'), $elsewhere->type(), $elsewhere->index(), $doc->page(0)->annotationCount());
?>
--EXPECT--
string(4) "Link"
string(5) "agree"
string(5) "agree"
string(5) "agree"
string(8) "Checkbox"
string(5) "agree"
string(4) "name"
bool(true)
bool(true)
Pdfium\PdfiumException: The form field is gone: its widget was removed or its page flattened
Pdfium\PdfiumException: The form field is gone: its widget was removed or its page flattened
Pdfium\PdfiumException: The form field is gone: its widget was removed or its page flattened
string(5) "agree"
string(5) "agree"
bool(true)
int(0)
type: Pdfium\PdfiumException: The annotation was removed from its page
index: Pdfium\PdfiumException: The annotation was removed from its page
rect: Pdfium\PdfiumException: The annotation was removed from its page
stringValue: Pdfium\PdfiumException: The annotation was removed from its page
setStringValue: Pdfium\PdfiumException: The annotation was removed from its page
setRect: Pdfium\PdfiumException: The annotation was removed from its page
remove: Pdfium\PdfiumException: The annotation was removed from its page
bool(false)
string(4) "Text"
int(0)
int(0)
