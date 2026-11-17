--TEST--
Document::bookmarks() returns the outline as a tree with titles, open state, colours, styles, destinations and actions; a colour outside 0..1 is null
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', [
    '<< /Type /Outlines /First 7 0 R /Last 9 0 R /Count 3 >>',
    '<< /Title (Chapter 1) /Parent 6 0 R /Next 9 0 R /First 8 0 R /Last 11 0 R /Count 2 /Dest [3 0 R /XYZ 72 700 2] /C [1 0 0.5] /F 3 >>',
    '<< /Title (Section 1.1) /Parent 7 0 R /Next 11 0 R /Dest [4 0 R /FitH 500] >>',
    '<< /Title (Chapter 2) /Parent 6 0 R /Prev 7 0 R /First 10 0 R /Last 10 0 R /Count -1 /A << /S /URI /URI (https://example.com/) >> /F 2 >>',
    '<< /Title (Hidden) /Parent 9 0 R /A << /S /GoTo /D [5 0 R /Fit] >> >>',
    '<< /Title <FEFF00C400DF> /Parent 7 0 R /Prev 8 0 R /A << /S /GoToR /F (other.pdf) /D [0 /Fit] >> >>',
]));
$b = $doc->bookmarks();
echo count($b), ' ', $b[0]->title, ' ', $b[1]->title, "\n";
$c1 = $b[0];
var_dump($c1->open, $c1->italic, $c1->bold, [$c1->color->red, $c1->color->green, $c1->color->blue, $c1->color->alpha]);
var_dump($c1->destination->pageIndex, $c1->destination->view, $c1->destination->x, $c1->action);
echo implode(',', array_map(fn($c) => $c->title, $c1->children)), "\n";
var_dump($c1->children[0]->color, $c1->children[0]->destination->params, $c1->children[0]->children);
var_dump($c1->children[1]->destination, $c1->children[1]->action->type, $c1->children[1]->action->destination);
$c2 = $b[1];
var_dump($c2->open, $c2->italic, $c2->bold, $c2->destination, $c2->action->uri);
var_dump($c2->children[0]->destination->pageIndex, $c2->children[0]->destination == $c2->children[0]->action->destination);
var_dump(unserialize(serialize($b)) == $b);
foreach (['[1.5 0 0]', '[1 0 0.5 1]'] as $color) {
    var_dump(Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', [
        '<< /Type /Outlines /First 7 0 R /Last 7 0 R /Count 1 >>',
        "<< /Title (X) /Parent 6 0 R /C $color >>",
    ]))->bookmarks()[0]->color);
}
var_dump(Pdfium\Document::fromString(nav_pdf())->bookmarks());
?>
--EXPECT--
2 Chapter 1 Chapter 2
bool(true)
bool(true)
bool(true)
array(4) {
  [0]=>
  int(255)
  [1]=>
  int(0)
  [2]=>
  int(128)
  [3]=>
  int(255)
}
int(0)
string(3) "XYZ"
float(72)
NULL
Section 1.1,Äß
NULL
array(1) {
  [0]=>
  float(500)
}
array(0) {
}
NULL
string(5) "GoToR"
NULL
bool(false)
bool(false)
bool(true)
NULL
string(20) "https://example.com/"
int(2)
bool(true)
bool(true)
NULL
NULL
array(0) {
}
