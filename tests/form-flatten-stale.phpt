--TEST--
After Page::flatten(), FormFields made earlier throw, TextPages made earlier keep the old text, and other Page objects stay safe
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

function all_methods(Pdfium\FormField $f): void
{
    $calls = [
        'name' => fn() => $f->name(),
        'alternateName' => fn() => $f->alternateName(),
        'type' => fn() => $f->type(),
        'value' => fn() => $f->value(),
        'exportValue' => fn() => $f->exportValue(),
        'isChecked' => fn() => $f->isChecked(),
        'options' => fn() => $f->options(),
        'flags' => fn() => $f->flags(),
        'rect' => fn() => $f->rect(),
        'controlIndex' => fn() => $f->controlIndex(),
        'controlCount' => fn() => $f->controlCount(),
        'setValue' => fn() => $f->setValue('x'),
        'setChecked' => fn() => $f->setChecked(),
        'select' => fn() => $f->select(0),
    ];
    foreach ($calls as $name => $call) {
        echo "$name: ", outcome($call, fn() => 'no exception'), "\n";
    }
}

function attempt(string $what, callable $call): void
{
    echo "$what: ", outcome($call, 'json_encode'), "\n";
}

echo "-- the flattening Page\n";
$doc = Pdfium\Document::fromString(standard_form());
$page = $doc->page(0);
$f = field($page, 'name');
$f->setValue('Jane');
$tp = $page->textPage();
$text = $tp->text();
var_dump($page->flatten());
all_methods($f);
var_dump($tp->text() === $text, str_contains($tp->text(), 'Jane'), str_contains($page->text(), 'Jane'));

echo "-- another Page for the same index\n";
$doc = Pdfium\Document::fromString(standard_form());
$a = $doc->page(0);
$b = $doc->page(0);
$c = $doc->page(0);
$f = field($a, 'name');
$f->setValue('Jane');
$atp = $a->textPage();
$aBefore = $a->render(dpi: 72)->toPng();
$aBare = $a->render(dpi: 72, annotations: false)->toPng();
var_dump($b->flatten());
attempt('name', fn() => $f->name());
attempt('setValue', fn() => $f->setValue('x'));
var_dump($a->formFields(), $a->links());
var_dump($a->render(dpi: 72)->toPng() === $aBefore, $a->render(dpi: 72, annotations: false)->toPng() === $aBare);
var_dump($c->render(dpi: 72)->toPng() === $aBefore, $c->render(dpi: 72, annotations: false)->toPng() === $aBare, $c->render(dpi: 72)->toPng() === $aBare);
$bBare = $b->render(dpi: 72, annotations: false)->toPng();
var_dump($bBare !== $aBare, $bBare !== $a->render(dpi: 72, annotations: false)->toPng(), $bBare !== $c->render(dpi: 72, annotations: false)->toPng());
var_dump(str_contains($a->text(), 'Jane'), str_contains($atp->text(), 'Jane'), $a->flatten());
var_dump(str_contains($doc->page(0)->text(), 'Jane'), str_contains($b->text(), 'Jane'));

echo "-- a field repeated on another page\n";
$doc = Pdfium\Document::fromString(repeat_pdf());
$p1 = $doc->page(1);
$g = $p1->formFields()[0];
$controls = fn(Pdfium\FormField $f) => [$f->controlIndex(), $f->controlCount()];
attempt('controls', fn() => $controls($g));
var_dump($doc->page(0)->flatten());
attempt('value', fn() => $g->value());
attempt('controls', fn() => [$controls($g), $controls($doc->page(1)->formFields()[0])]);
attempt('setValue', fn() => $g->setValue('z'));
attempt('value', fn() => $g->value());
$again = Pdfium\Document::fromString($doc->toString());
var_dump(count($again->page(0)->formFields()), str_contains($again->page(0)->text(), 'old'));
attempt('page 1', fn() => array_map(fn($f) => $f->value(), $again->page(1)->formFields()));
attempt('controls', fn() => $controls($again->page(1)->formFields()[0]));
$doc = Pdfium\Document::fromString(repeat_pdf());
$h = $doc->page(0)->formFields()[0];
var_dump($doc->page(1)->flatten());
attempt('controls', fn() => [$controls($h), $controls($doc->page(0)->formFields()[0])]);
attempt('setValue', fn() => $h->setValue('y'));
$again = Pdfium\Document::fromString($doc->toString());
attempt('reopened', fn() => [$again->page(0)->formFields()[0]->value(), $controls($again->page(0)->formFields()[0]), count($again->page(1)->formFields())]);

echo "-- release orders\n";
foreach ([['f', 'tp', 'a', 'b', 'doc'], ['doc', 'b', 'a', 'tp', 'f'], ['a', 'doc', 'f', 'b', 'tp']] as $order) {
    $o = ['doc' => Pdfium\Document::fromString(standard_form())];
    $o['a'] = $o['doc']->page(0);
    $o['b'] = $o['doc']->page(0);
    $o['f'] = field($o['a'], 'name');
    $o['tp'] = $o['a']->textPage();
    $o['b']->render(dpi: 72);
    $o['a']->flatten();
    foreach ($order as $k) {
        unset($o[$k]);
        gc_collect_cycles();
    }
    echo implode(' ', $order), ": ok\n";
}
?>
--EXPECT--
-- the flattening Page
bool(true)
name: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
alternateName: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
type: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
value: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
exportValue: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
isChecked: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
options: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
flags: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
rect: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
controlIndex: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
controlCount: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
setValue: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
setChecked: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
select: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
bool(true)
bool(false)
bool(true)
-- another Page for the same index
bool(true)
name: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
setValue: Pdfium\PdfiumException: The form field is gone; its page may have been flattened
array(0) {
}
array(0) {
}
bool(true)
bool(true)
bool(false)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
bool(false)
bool(false)
bool(true)
bool(true)
-- a field repeated on another page
controls: [1,2]
bool(true)
value: "old"
controls: [[1,2],[1,2]]
setValue: null
value: "z"
int(0)
bool(true)
page 1: ["z"]
controls: [0,1]
bool(true)
controls: [[0,2],[0,2]]
setValue: null
reopened: ["y",[0,1],0]
-- release orders
f tp a b doc: ok
doc b a tp f: ok
a doc f b tp: ok
