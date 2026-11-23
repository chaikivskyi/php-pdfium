--TEST--
Damaged annotation lists and annotations: every reader returns or throws a PdfiumException; non-dictionary entries, a list that is not an array, self and mutual links, short arrays, out-of-range colours and an annotation listed twice, removed through an object or by index
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';

function show(mixed $v): string
{
    if ($v === null) return 'null';
    if (is_bool($v)) return $v ? 'true' : 'false';
    if (is_int($v) || is_float($v)) return var_export($v, true);
    if (is_string($v)) return json_encode($v);
    if (is_array($v)) return '[' . implode(', ', array_map('show', $v)) . ']';
    if ($v instanceof Pdfium\Rect) return "Rect($v->left $v->bottom $v->right $v->top)";
    if ($v instanceof Pdfium\Color) return "Color($v->red $v->green $v->blue $v->alpha)";
    if ($v instanceof Pdfium\Border) return "Border($v->horizontalRadius $v->verticalRadius $v->width)";
    if ($v instanceof Pdfium\Point) return "Point($v->x $v->y)";
    if ($v instanceof Pdfium\Quad) return 'Quad(' . quad($v) . ')';
    if ($v instanceof Pdfium\Annotation) return 'Annotation#' . $v->index();
    if ($v instanceof Pdfium\Attachment) return 'Attachment(' . show($v->name()) . ')';
    if ($v instanceof Pdfium\Link) return 'Link';
    return get_class($v);
}

function readers(Pdfium\Annotation $a): array
{
    return [
        'index' => fn() => $a->index(), 'type' => fn() => $a->type(), 'rect' => fn() => $a->rect(),
        'flags' => fn() => $a->flags(), 'color' => fn() => $a->color(), 'interiorColor' => fn() => $a->interiorColor(),
        'border' => fn() => $a->border(), 'quadPoints' => fn() => $a->quadPoints(), 'vertices' => fn() => $a->vertices(),
        'line' => fn() => $a->line(), 'inkStrokes' => fn() => $a->inkStrokes(),
        'hasKey' => fn() => $a->hasKey('Contents'), 'valueType' => fn() => $a->valueType('Popup'),
        'stringValue' => fn() => $a->stringValue('Contents'), 'numberValue' => fn() => $a->numberValue('F'),
        'appearance' => fn() => $a->appearance(),
        'popup' => fn() => $a->linkedAnnotation('Popup'), 'irt' => fn() => $a->linkedAnnotation('IRT'),
        'link' => fn() => $a->link(), 'attachment' => fn() => $a->attachment(),
    ];
}

function report(string $label, string $pdf): void
{
    echo "== $label\n";
    try {
        $page = Pdfium\Document::fromString($pdf)->page(0);
        echo 'count ', $page->annotationCount(), ' keys ', implode(',', array_keys($page->annotations())), "\n";
        for ($i = 0; $i < $page->annotationCount(); $i++) {
            try {
                $a = $page->annotation($i);
            } catch (Pdfium\PdfiumException $e) {
                echo "  $i: ", get_class($e), ': ', $e->getMessage(), "\n";
                continue;
            }
            $parts = [];
            foreach (readers($a) as $name => $fn) {
                try {
                    $parts[] = "$name=" . show($fn());
                } catch (Pdfium\PdfiumException $e) {
                    $parts[] = "$name!" . get_class($e) . ': ' . $e->getMessage();
                }
            }
            echo "  $i: ", implode(' ', $parts), "\n";
        }
    } catch (Pdfium\PdfiumException $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
}

$text = '/Type /Annot /Subtype /Text /Rect [10 10 30 30]';
report('non-dictionary entries', annots_pdf('!null', '!(text)', '![1 2]', '!99 0 R', "<< $text /Contents (ok) >>"));
report('not an array: number', nav_pdf(pageExtra: ['/Annots 42', '', '']));
report('not an array: dictionary', nav_pdf(extra: ["<< $text >>"], pageExtra: ['/Annots 6 0 R', '', '']));
report('popup to itself', annots_pdf("<< $text /Popup 6 0 R >>"));
report('IRT loop', annots_pdf("<< $text /IRT 7 0 R /Contents (a) >>", "<< $text /IRT 6 0 R /Contents (b) >>"));
report('quad points 7', annots_pdf('<< /Type /Annot /Subtype /Highlight /Rect [10 10 30 30] /QuadPoints [1 2 3 4 5 6 7] >>'));
report('ink list', annots_pdf('<< /Type /Annot /Subtype /Ink /Rect [10 10 30 30] /InkList [1 2 [3]] >>'));
report('rect 2', annots_pdf('<< /Type /Annot /Subtype /Square /Rect [0 0] >>'));
report('rect 2, not zero', annots_pdf('<< /Type /Annot /Subtype /Square /Rect [10 20] >>'));
report('border string', annots_pdf("<< $text /Border (x) >>"));
report('colour 5', annots_pdf("<< $text /C [9 9 9 9 9] >>"));
report('colour negative', annots_pdf("<< $text /C [-1 0 0] /IC [-1 0.5 -0.001] >>"));
report('colour 2', annots_pdf("<< $text /C [2 0 0] >>"));
report('listed twice', annots_pdf("<< $text /Contents (twice) >>", '!6 0 R'));

echo "== listed twice, removed through the second\n";
$page = Pdfium\Document::fromString(annots_pdf("<< $text /Contents (twice) >>", '!6 0 R'))->page(0);
[$first, $second] = $page->annotations();
$second->remove();
var_dump($page->annotationCount(), $first->index(), $first->stringValue('Contents'));
try { $second->index(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
$first->remove();
var_dump($page->annotationCount());
try { $first->index(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
foreach (['[1 2]', '[]'] as $border) {
    var_dump(Pdfium\Document::fromString(annots_pdf("<< $text /Border $border >>"))->page(0)->annotation(0)->border());
}

echo "== listed twice, removed by index\n";
$page = Pdfium\Document::fromString(annots_pdf("<< $text /Contents (twice) >>", '!6 0 R'))->page(0);
[$first, $second] = $page->annotations();
$page->removeAnnotation(0);
var_dump($page->annotationCount(), $first->index(), $second->index(), $first->stringValue('Contents'), $second->stringValue('Contents'));
$r = Pdfium\Document::fromString(annots_pdf('<< /Type /Annot /Subtype /Square /Rect [10 20 30 40 50] >>'))->page(0)->annotation(0)->rect();
echo "rect 5: $r->left $r->bottom $r->right $r->top\n";
?>
--EXPECT--
== non-dictionary entries
count 5 keys 4
  0: Pdfium\FormatException: Cannot read annotation 0
  1: Pdfium\FormatException: Cannot read annotation 1
  2: Pdfium\FormatException: Cannot read annotation 2
  3: Pdfium\FormatException: Cannot read annotation 3
  4: index=4 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=true valueType="Unknown" stringValue="ok" numberValue=null appearance=null popup=null irt=null link=null attachment=null
== not an array: number
count 0 keys 
== not an array: dictionary
count 0 keys 
== popup to itself
count 1 keys 0
  0: index=0 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=false valueType="Reference" stringValue=null numberValue=null appearance=null popup=Annotation#0 irt=null link=null attachment=null
== IRT loop
count 2 keys 0,1
  0: index=0 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=true valueType="Unknown" stringValue="a" numberValue=null appearance=null popup=null irt=Annotation#1 link=null attachment=null
  1: index=1 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=true valueType="Unknown" stringValue="b" numberValue=null appearance=null popup=null irt=Annotation#0 link=null attachment=null
== quad points 7
count 1 keys 0
  0: index=0 type="Highlight" rect=Rect(10 10 30 30) flags=0 color=Color(255 255 0 255) interiorColor=Color(255 255 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=false valueType="Unknown" stringValue=null numberValue=null appearance=null popup=null irt=null link=null attachment=null
== ink list
count 1 keys 0
  0: index=0 type="Ink" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[[], [], []] hasKey=false valueType="Unknown" stringValue=null numberValue=null appearance=null popup=null irt=null link=null attachment=null
== rect 2
count 1 keys 0
  0: index=0 type="Square" rect=Rect(0 0 0 0) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=false valueType="Unknown" stringValue=null numberValue=null appearance=null popup=null irt=null link=null attachment=null
== rect 2, not zero
count 1 keys 0
  0: index=0 type="Square" rect=Rect(0 0 0 0) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=false valueType="Unknown" stringValue=null numberValue=null appearance=null popup=null irt=null link=null attachment=null
== border string
count 1 keys 0
  0: index=0 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=false valueType="Unknown" stringValue=null numberValue=null appearance=null popup=null irt=null link=null attachment=null
== colour 5
count 1 keys 0
  0: index=0 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=false valueType="Unknown" stringValue=null numberValue=null appearance=null popup=null irt=null link=null attachment=null
== colour negative
count 1 keys 0
  0: index=0 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 127 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=false valueType="Unknown" stringValue=null numberValue=null appearance=null popup=null irt=null link=null attachment=null
== colour 2
count 1 keys 0
  0: index=0 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(255 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=false valueType="Unknown" stringValue=null numberValue=null appearance=null popup=null irt=null link=null attachment=null
== listed twice
count 2 keys 0,1
  0: index=0 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=true valueType="Unknown" stringValue="twice" numberValue=null appearance=null popup=null irt=null link=null attachment=null
  1: index=0 type="Text" rect=Rect(10 10 30 30) flags=0 color=Color(0 0 0 255) interiorColor=Color(0 0 0 255) border=null quadPoints=[] vertices=[] line=null inkStrokes=[] hasKey=true valueType="Unknown" stringValue="twice" numberValue=null appearance=null popup=null irt=null link=null attachment=null
== listed twice, removed through the second
int(1)
int(0)
string(5) "twice"
The annotation was removed from its page
int(0)
The annotation was removed from its page
NULL
NULL
== listed twice, removed by index
int(1)
int(0)
int(0)
string(5) "twice"
string(5) "twice"
rect 5: 0 0 0 0
