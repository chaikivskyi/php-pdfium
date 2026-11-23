--TEST--
FormField reads and checks buttons after another Page object for the same page touched the form and went
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

function state(array $f): array
{
    return [$f[1]->isChecked(), $f[1]->exportValue(), $f[2]->isChecked(), $f[2]->exportValue(), $f[3]->isChecked(), $f[3]->exportValue()];
}

$triggers = [
    'render' => fn(Pdfium\Page $p) => $p->render(dpi: 36),
    'formFields' => fn(Pdfium\Page $p) => $p->formFields(),
    'field reader' => fn(Pdfium\Page $p) => $p->formFields()[0]->value(),
];

foreach (['after', 'before'] as $when) {
    foreach ([...array_keys($triggers), 'iteration'] as $trigger) {
        echo "-- $trigger, the other Page made $when the held one\n";
        $doc = Pdfium\Document::fromString(standard_form());
        $others = $when === 'before' ? iterator_to_array($doc, false) : [];
        $page = $doc->page(0);
        $f = $page->formFields();
        $f[1]->setChecked();
        $f[3]->setChecked();
        if ($trigger === 'iteration') {
            foreach ($others ?: $doc as $p) {
                $p->formFields();
                $p->render(dpi: 36);
            }
        } elseif ($when === 'before') {
            $triggers[$trigger]($others[0]);
        } else {
            $triggers[$trigger]($doc->page(0));
        }
        unset($p, $others);
        echo json_encode(state($f)), "\n";
        echo json_encode(state($page->formFields())), "\n";
        echo outcome(fn() => $f[2]->setChecked()), ' ', outcome(fn() => $f[1]->setChecked(false)), "\n";
        echo json_encode(state($f)), "\n";
        echo outcome(fn() => $f[1]->setChecked()), "\n";
        $again = Pdfium\Document::fromString($doc->toString())->page(0)->formFields();
        echo json_encode([state($f), state($again)]), "\n";
    }
}

echo "-- a checkbox on page 0 whose /P names page 1, where a text field has no appearance\n";
$ap = stream_obj('0 g 0 0 20 20 re f', '/Type /XObject /Subtype /Form /BBox [0 0 20 20] ');
$w = '/Type /Annot /Subtype /Widget /F 4 /P 6 0 R';
$strip = fn(Pdfium\Document $d) => preg_replace('~/ID\s*\[[^\]]*\]~', '', $d->toString());
foreach (['Yes', 'Off'] as $state) {
    $pdf = form_pdf([
        8 => "<< $w /FT /Btn /T (tick) /V /$state /AS /$state /Rect [100 600 120 620] /AP << /N << /Yes 22 0 R /Off 23 0 R >> >> >>",
        10 => "<< $w /FT /Tx /T (other) /V (zzz) /Rect [100 500 300 520] >>",
    ], [22 => $ap, 23 => $ap], [10]);
    $doc = Pdfium\Document::fromString($pdf);
    $page = $doc->page(0);
    $tick = $page->formFields()[0];
    $doc->page(0)->formFields();
    $before = $strip($doc);
    var_dump($tick->isChecked(), $tick->exportValue(), $strip($doc) === $before);
    echo outcome(fn() => $tick->setChecked($state === 'Off')), "\n";
    $again = Pdfium\Document::fromString($doc->toString())->page(0)->formFields()[0];
    var_dump($tick->isChecked(), $doc->page(0)->formFields()[0]->isChecked(), $again->isChecked(), $again->value());
}
?>
--EXPECT--
-- render, the other Page made after the held one
[true,"Yes",false,"red",true,"blue"]
[true,"Yes",false,"red",true,"blue"]
ok ok
[false,"Yes",true,"red",false,"blue"]
ok
[[true,"Yes",true,"red",false,"blue"],[true,"Yes",true,"red",false,"blue"]]
-- formFields, the other Page made after the held one
[true,"Yes",false,"red",true,"blue"]
[true,"Yes",false,"red",true,"blue"]
ok ok
[false,"Yes",true,"red",false,"blue"]
ok
[[true,"Yes",true,"red",false,"blue"],[true,"Yes",true,"red",false,"blue"]]
-- field reader, the other Page made after the held one
[true,"Yes",false,"red",true,"blue"]
[true,"Yes",false,"red",true,"blue"]
ok ok
[false,"Yes",true,"red",false,"blue"]
ok
[[true,"Yes",true,"red",false,"blue"],[true,"Yes",true,"red",false,"blue"]]
-- iteration, the other Page made after the held one
[true,"Yes",false,"red",true,"blue"]
[true,"Yes",false,"red",true,"blue"]
ok ok
[false,"Yes",true,"red",false,"blue"]
ok
[[true,"Yes",true,"red",false,"blue"],[true,"Yes",true,"red",false,"blue"]]
-- render, the other Page made before the held one
[true,"Yes",false,"red",true,"blue"]
[true,"Yes",false,"red",true,"blue"]
ok ok
[false,"Yes",true,"red",false,"blue"]
ok
[[true,"Yes",true,"red",false,"blue"],[true,"Yes",true,"red",false,"blue"]]
-- formFields, the other Page made before the held one
[true,"Yes",false,"red",true,"blue"]
[true,"Yes",false,"red",true,"blue"]
ok ok
[false,"Yes",true,"red",false,"blue"]
ok
[[true,"Yes",true,"red",false,"blue"],[true,"Yes",true,"red",false,"blue"]]
-- field reader, the other Page made before the held one
[true,"Yes",false,"red",true,"blue"]
[true,"Yes",false,"red",true,"blue"]
ok ok
[false,"Yes",true,"red",false,"blue"]
ok
[[true,"Yes",true,"red",false,"blue"],[true,"Yes",true,"red",false,"blue"]]
-- iteration, the other Page made before the held one
[true,"Yes",false,"red",true,"blue"]
[true,"Yes",false,"red",true,"blue"]
ok ok
[false,"Yes",true,"red",false,"blue"]
ok
[[true,"Yes",true,"red",false,"blue"],[true,"Yes",true,"red",false,"blue"]]
-- a checkbox on page 0 whose /P names page 1, where a text field has no appearance
bool(true)
string(3) "Yes"
bool(true)
ok
bool(false)
bool(false)
bool(false)
string(3) "Off"
bool(false)
string(3) "Yes"
bool(true)
ok
bool(true)
bool(true)
bool(true)
string(3) "Yes"
