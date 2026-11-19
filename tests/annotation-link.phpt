--TEST--
Annotation::link() gives the Link that Page::links() gives for a link annotation, null for others; setUri() makes a link open a URI
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$annots = [
    '/Subtype /Link /Rect [100 700 200 720] /Dest [4 0 R /Fit]',
    '/Subtype /Link /Rect [400 720 300 700] /QuadPoints [300 720 400 720 300 700 400 700] /A << /S /URI /URI (https://example.com/) >>',
    '/Subtype /Link /Rect [100 600 200 620] /A << /S /GoToR /F (other.pdf) /D [0 /Fit] >>',
    '/Subtype /Link /Rect [100 500 200 520] /A << /S /Launch /F (app.exe) >>',
    '/Subtype /Link /Rect [100 400 200 420] /A << /S /JavaScript /JS (app.alert\(1\)) >>',
    '/Subtype /Square /Rect [0 0 50 50]',
    '/Subtype /Link /Rect [150 700 250 720] /A << /S /GoTo /D [5 0 R /XYZ null null null] >>',
    '/Subtype /Text /Rect [0 0 10 10]',
];
$doc = Pdfium\Document::fromString(annots_pdf(...array_map(fn($a) => "<< /Type /Annot $a >>", $annots)));
$page = $doc->page(0);
$links = $page->links();
$k = 0;
foreach ($page->annotations() as $i => $a) {
    $link = $a->link();
    if ($a->type() === 'Link') {
        echo $i, ' ', var_export($link == $links[$k++], true), "\n";
    } else {
        echo $i, ' ', $a->type(), ' ', var_export($link, true), "\n";
    }
}
var_dump($k === count($links), $page->annotation(1)->link()->action->uri);

$new = $page->addAnnotation('Link', new Pdfium\Rect(10, 10, 60, 30));
var_dump($new->link()->action);
$new->setUri('https://example.com/a?b=c');
$link = $new->link();
var_dump($link->action->type, $link->action->uri, $link->destination);
var_dump(array_slice($page->links(), -1)[0] == $link);
$page->annotation(0)->setUri('https://example.com/dest');
var_dump($page->annotation(0)->link()->action->uri, $page->annotation(0)->link()->destination);
var_dump($page->annotation(0)->valueType('Dest'), $page->annotation(0)->valueType('A'));

$saved = Pdfium\Document::fromString($doc->toString())->page(0);
$reread = $saved->annotation($new->index())->link();
var_dump($reread->action->type, $reread->action->uri, $reread == $link);
var_dump(array_slice($saved->links(), -1)[0]->action->uri, $saved->links()[0]->action->uri);

foreach (['', "a\0b"] as $uri) {
    try { $new->setUri($uri); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
foreach ([5, 7] as $i) {
    attempt(fn() => $page->annotation($i)->setUri('x'));
}
var_dump($page->annotation(7)->hasKey('A'), $new->link()->action->uri);
$new->setUri("https://example.com/\xC3\xA9\xFF");
var_dump(bin2hex($new->link()->action->uri));
var_dump(bin2hex(Pdfium\Document::fromString($doc->toString())->page(0)->annotation($new->index())->link()->action->uri));
?>
--EXPECT--
0 true
1 true
2 true
3 true
4 true
5 Square NULL
6 true
7 Text NULL
bool(true)
string(20) "https://example.com/"
NULL
string(3) "URI"
string(25) "https://example.com/a?b=c"
NULL
bool(true)
string(24) "https://example.com/dest"
NULL
string(5) "Array"
string(10) "Dictionary"
string(3) "URI"
string(25) "https://example.com/a?b=c"
bool(true)
string(25) "https://example.com/a?b=c"
string(24) "https://example.com/dest"
Pdfium\Annotation::setUri(): Argument #1 ($uri) must not be empty
Pdfium\Annotation::setUri(): Argument #1 ($uri) must not contain a null byte
Pdfium\PdfiumException: Cannot set the URI of a Square annotation
Pdfium\PdfiumException: Cannot set the URI of a Text annotation
bool(false)
string(25) "https://example.com/a?b=c"
string(46) "68747470733a2f2f6578616d706c652e636f6d2fc3a9ff"
string(46) "68747470733a2f2f6578616d706c652e636f6d2fc3a9ff"
