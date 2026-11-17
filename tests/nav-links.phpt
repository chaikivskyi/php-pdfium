--TEST--
Page::links() returns the link annotations with their rectangles, quad points, destinations and actions; linkAt() the topmost at a point
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
];
$doc = Pdfium\Document::fromString(nav_pdf(
    extra: array_map(fn($a) => "<< /Type /Annot $a >>", $annots),
    pageExtra: ['/Annots [6 0 R 7 0 R 8 0 R 9 0 R 10 0 R 11 0 R 12 0 R]', '', ''],
));
$links = $doc->page(0)->links();
echo count($links), "\n";
foreach ($links as $l) {
    printf("%s %s %s %s | %s %s | %s %s\n", $l->rect->left, $l->rect->bottom, $l->rect->right, $l->rect->top,
        $l->destination?->pageIndex ?? '-', $l->destination?->view ?? '-',
        $l->action?->type ?? '-', $l->action?->uri ?? $l->action?->filePath ?? '-');
}
var_dump(count($links[0]->quadPoints), $links[1]->quadPoints[0]->x1, $links[1]->quadPoints[0]->y4);
var_dump($links[5]->destination->x, $links[5]->destination->y, $links[5]->destination->zoom);
var_dump($links[5]->destination == $links[5]->action->destination);
var_dump($doc->page(0)->linkAt(175, 710) == $links[5], $doc->page(0)->linkAt(120, 710) == $links[0]);
var_dump($doc->page(0)->linkAt(10, 10), $doc->page(1)->links());
try { $doc->page(0)->linkAt(NAN, 0); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
try { $doc->page(0)->linkAt(0, INF); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
var_dump(unserialize(serialize($links)) == $links);

$embedded = Pdfium\Document::fromString(nav_pdf(
    extra: ['<< /Type /Annot /Subtype /Link /Rect [0 0 10 10] /A << /S /GoToE /D [0 /Fit] /T << /R /C /N (embedded.pdf) >> >> >>'],
    pageExtra: ['/Annots [6 0 R]', '', ''],
))->page(0)->links()[0];
var_dump($embedded->action->type, $embedded->action->filePath, $embedded->action->destination, $embedded->destination);
var_dump(Pdfium\Document::fromString(nav_pdf(
    extra: ['<< /Type /Annot /Subtype /Link /Rect [0 0 10 10] /A << /S /GoToE /F (outer.pdf) /D [0 /Fit] /T << /R /C /N (embedded.pdf) >> >> >>'],
    pageExtra: ['/Annots [6 0 R]', '', ''],
))->page(0)->links()[0]->action->filePath);
?>
--EXPECT--
6
100 700 200 720 | 1 Fit | - -
300 700 400 720 | - - | URI https://example.com/
100 600 200 620 | - - | GoToR other.pdf
100 500 200 520 | - - | Launch app.exe
100 400 200 420 | - - | Unsupported -
150 700 250 720 | 2 XYZ | GoTo -
int(0)
float(300)
float(700)
NULL
NULL
NULL
bool(true)
bool(true)
bool(true)
NULL
array(0) {
}
Pdfium\Page::linkAt(): Argument #1 ($x) must be finite
Pdfium\Page::linkAt(): Argument #2 ($y) must be finite
bool(true)
string(5) "GoToE"
NULL
NULL
NULL
string(9) "outer.pdf"
