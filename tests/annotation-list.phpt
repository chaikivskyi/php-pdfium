--TEST--
Page::annotationCount(), annotations() and annotation() list a page's annotations keyed by index, leaving out an entry PDFium returns no annotation for; Annotation::type(), index(), rect() and flags() read them
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(annots_pdf(
    '<< /Type /Annot /Subtype /Text /Rect [100 650 300 700] /F 4 >>',
    '<< /Type /Annot /Subtype /Highlight /Rect [0 0 10 10] >>',
    '<< /Type /Annot /Subtype /Line /Rect [0 0 10 10] /L [0 0 10 10] >>',
    '<< /Type /Annot /Subtype /Widget /Rect [0 0 10 10] >>',
    '<< /Type /Annot /Subtype /Bogus /Rect [0 0 10 10] >>',
    '<< /Type /Annot /Subtype /Text >>',
    '<< /Type /Annot /Subtype /Text /Rect [300 700 100 650] >>',
    '!42',
));
$page = $doc->page(0);
var_dump($page->annotationCount());
$all = $page->annotations();
var_dump(array_keys($all));
echo implode(' ', array_map(fn($a) => $a->type(), $all)), "\n";
var_dump($all[1]->index(), $all[0]->flags(), $all[1]->flags());
var_dump($all[0]->rect());
var_dump($all[5]->rect());
var_dump($all[6]->rect());
var_dump($page->annotation(1)->type(), $page->annotation(6)->index());
var_dump($doc->page(1)->annotations(), $doc->page(1)->annotationCount());
foreach ([-1, 8] as $i) {
    try { $page->annotation($i); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
echo_outcome(fn() => $page->annotation(7));
try { $doc->page(1)->annotation(0); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECTF--
int(8)
array(7) {
  [0]=>
  int(0)
  [1]=>
  int(1)
  [2]=>
  int(2)
  [3]=>
  int(3)
  [4]=>
  int(4)
  [5]=>
  int(5)
  [6]=>
  int(6)
}
Text Highlight Line Widget Unknown Text Text
int(1)
int(4)
int(0)
object(Pdfium\Rect)#%d (4) {
  ["left"]=>
  float(100)
  ["bottom"]=>
  float(650)
  ["right"]=>
  float(300)
  ["top"]=>
  float(700)
}
object(Pdfium\Rect)#%d (4) {
  ["left"]=>
  float(0)
  ["bottom"]=>
  float(0)
  ["right"]=>
  float(0)
  ["top"]=>
  float(0)
}
object(Pdfium\Rect)#%d (4) {
  ["left"]=>
  float(100)
  ["bottom"]=>
  float(650)
  ["right"]=>
  float(300)
  ["top"]=>
  float(700)
}
string(9) "Highlight"
int(6)
array(0) {
}
int(0)
Pdfium\Page::annotation(): Argument #1 ($index) must be between 0 and 7
Pdfium\Page::annotation(): Argument #1 ($index) must be between 0 and 7
Pdfium\FormatException: Cannot read annotation 7
Pdfium\Page::annotation(): Argument #1 ($index) must be an annotation index, but the page has no annotations
