--TEST--
Annotations and FormFields follow their page when pages are moved, added or deleted before it, fill and flatten the page where it is now, and throw the deleted-page message once their page is deleted
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

$doc = Pdfium\Document::fromString(standard_form());
$page = $doc->page(0);
$name = $page->formFields()[0];
$widget = $page->annotation(1);
$doc->movePages([0], 1);
var_dump($page->index(), $widget->index(), $widget->stringValue('T'), $name->name());
$name->setValue('moved');
var_dump($name->value(), $doc->page(1)->formFields()[0]->value(), count($doc->page(0)->formFields()));
$doc->addPage(100, 100, 0);
var_dump($page->index());
$name->setValue('again');
$saved = Pdfium\Document::fromString($doc->toString());
var_dump($saved->page(2)->formFields()[0]->value(), count($saved->page(0)->formFields()), count($saved->page(1)->formFields()));
var_dump($page->flatten(), $page->index(), $page->annotationCount(), $doc->page(2)->annotationCount());
echo_outcome(fn () => $name->name());
echo_outcome(fn () => $widget->type());
$doc->deletePage(0);
var_dump($page->index());
$page->addAnnotation('Text', new Pdfium\Rect(10, 10, 30, 30));
var_dump($doc->page(1)->annotationCount(), $doc->page(1)->annotation(0)->type());

$doc = Pdfium\Document::fromString(standard_form());
$page = $doc->page(0);
$link = $page->annotation(0);
$field = $page->formFields()[0];
$clip = $page->addAnnotation('FileAttachment', new Pdfium\Rect(10, 60, 30, 80));
$file = $clip->setAttachment('a.txt', 'a');
$doc->deletePage(0);
foreach ([
    'Annotation::type' => fn () => $link->type(),
    'Annotation::setRect' => fn () => $link->setRect(new Pdfium\Rect(0, 0, 5, 5)),
    'Annotation::remove' => fn () => $link->remove(),
    'FormField::name' => fn () => $field->name(),
    'FormField::setValue' => fn () => $field->setValue('x'),
    'Attachment::name' => fn () => $file->name(),
] as $what => $call) {
    echo $what, ': ';
    echo_outcome($call);
}
var_dump($doc->pageCount(), count($doc->page(0)->formFields()));
?>
--EXPECT--
int(1)
int(1)
string(4) "name"
string(4) "name"
string(5) "moved"
string(5) "moved"
int(0)
int(2)
string(5) "again"
int(0)
int(0)
bool(true)
int(2)
int(0)
int(0)
Pdfium\PdfiumException: The form field is gone: its widget was removed or its page flattened
Pdfium\PdfiumException: The annotation was removed from its page
int(1)
int(1)
string(4) "Text"
Annotation::type: Pdfium\PdfiumException: The page was deleted from its document
Annotation::setRect: Pdfium\PdfiumException: The page was deleted from its document
Annotation::remove: Pdfium\PdfiumException: The page was deleted from its document
FormField::name: Pdfium\PdfiumException: The page was deleted from its document
FormField::setValue: Pdfium\PdfiumException: The page was deleted from its document
Attachment::name: Pdfium\PdfiumException: The page was deleted from its document
int(1)
int(0)
