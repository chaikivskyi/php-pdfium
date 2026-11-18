--TEST--
Document::insertPages() copies the rotation and boxes a source page has or takes from its page tree; a copy without a rotation or crop box takes the destination node's at the insert position, from another document and from the document itself; a copy without a media box gets its crop box, or US Letter; a source page without /Type copies only its own, and one with another /Type is copied as a page
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$size = fn (Pdfium\Page $p) => trim($p->text()) . ' ' . $p->rotation() . ' ' . $p->width() . 'x' . $p->height();
$all = fn (Pdfium\Document $doc) => implode(', ', array_map($size, iterator_to_array($doc)));
$show = function (string $name, Pdfium\Document $doc, array $held) use ($size, $all) {
    echo "$name: ", $all($doc), "\n";
    echo '  held: ', implode(', ', array_map(fn (Pdfium\Page $p) => $p->index() . ' ' . $size($p), $held)), "\n";
    echo '  saved: ', $all(Pdfium\Document::fromString($doc->toString())), "\n";
};
$plain = Pdfium\Document::fromString(page_pdf(612, 792, 'BT /F1 24 Tf 10 10 Td (S) Tj ET'));
foreach (['/Rotate 90', '/CropBox [0 0 50 50]', '/MediaBox [0 0 50 60]'] as $attributes) {
    $doc = Pdfium\Document::fromString(nodes_pdf([[$attributes, 2]]));
    $held = iterator_to_array($doc);
    $doc->insertPages($plain, null, 1);
    $show("into a node with $attributes", $doc, $held);
}
$inherits = Pdfium\Document::fromString(nodes_pdf([['/MediaBox [0 0 300 400] /CropBox [0 0 100 150] /Rotate 270', 2]]));
$doc = Pdfium\Document::fromString(nodes_pdf([['/Rotate 90 /CropBox [0 0 50 50]', 2]]));
$held = iterator_to_array($doc);
$doc->insertPages($inherits, [1, 0], 1);
$show('inherited from the source into a node with /Rotate 90 /CropBox [0 0 50 50]', $doc, $held);
$doc = Pdfium\Document::fromString(nodes_pdf([['/Rotate 90 /CropBox [0 0 50 50]', 2], ['', 2]]));
$held = iterator_to_array($doc);
$doc->insertPages($doc, [2, 0], 1);
$show('itself, [2, 0] at 1', $doc, $held);
$doc = Pdfium\Document::fromString(nodes_pdf([['/Rotate 90 /CropBox [0 0 50 50]', 2], ['', 1]]));
$held = iterator_to_array($doc);
$doc->insertPages($doc, null, 1);
$show('itself, every page at 1', $doc, $held);
$noMediaBox = Pdfium\Document::fromString(build_pdf([
    '<< /Type /Catalog /Pages 2 0 R >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /CropBox [0 0 1000 1000] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
    stream_obj('BT /F1 24 Tf 10 10 Td (N) Tj ET'),
    '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
]));
$doc = Pdfium\Document::fromString(letters_pdf(2));
$held = iterator_to_array($doc);
$doc->insertPages($noMediaBox, null, 1);
$show('a source page with a crop box larger than the default media box, and no media box', $doc, $held);
$doc = Pdfium\Document::fromString(letters_pdf(2));
$held = iterator_to_array($doc);
$doc->insertPages(Pdfium\Document::fromString(untyped_pdf()), null, 1);
$show('source pages without /Type, one inheriting /Rotate 90 and one with its own /Rotate 180', $doc, $held);
$doc = Pdfium\Document::create();
$doc->insertPages(Pdfium\Document::fromString(untyped_pdf()));
echo 'the same into a new document: ', $all($doc), "\n";
$wrong = Pdfium\Document::fromString(untyped_pdf('/Type /Pagex'));
try { $wrong->page(0); } catch (Pdfium\FormatException $e) { echo 'a source page whose /Type is not /Page: ', $e->getMessage(), "\n"; }
$doc->insertPages($wrong);
echo '  inserted: ', $all($doc), "\n";
?>
--EXPECT--
into a node with /Rotate 90: A 90 792x612, S 90 792x612, B 90 792x612
  held: 0 A 90 792x612, 2 B 90 792x612
  saved: A 90 792x612, S 90 792x612, B 90 792x612
into a node with /CropBox [0 0 50 50]: A 0 50x50, S 0 50x50, B 0 50x50
  held: 0 A 0 50x50, 2 B 0 50x50
  saved: A 0 50x50, S 0 50x50, B 0 50x50
into a node with /MediaBox [0 0 50 60]: A 0 50x60, S 0 612x792, B 0 50x60
  held: 0 A 0 50x60, 2 B 0 50x60
  saved: A 0 50x60, S 0 612x792, B 0 50x60
inherited from the source into a node with /Rotate 90 /CropBox [0 0 50 50]: A 90 50x50, B 270 150x100, A 270 150x100, B 90 50x50
  held: 0 A 90 50x50, 3 B 90 50x50
  saved: A 90 50x50, B 270 150x100, A 270 150x100, B 90 50x50
itself, [2, 0] at 1: A 90 50x50, C 90 50x50, A 90 50x50, B 90 50x50, C 0 612x792, D 0 612x792
  held: 0 A 90 50x50, 3 B 90 50x50, 4 C 0 612x792, 5 D 0 612x792
  saved: A 90 50x50, C 90 50x50, A 90 50x50, B 90 50x50, C 0 612x792, D 0 612x792
itself, every page at 1: A 90 50x50, A 90 50x50, B 90 50x50, C 90 50x50, B 90 50x50, C 0 612x792
  held: 0 A 90 50x50, 4 B 90 50x50, 5 C 0 612x792
  saved: A 90 50x50, A 90 50x50, B 90 50x50, C 90 50x50, B 90 50x50, C 0 612x792
a source page with a crop box larger than the default media box, and no media box: A 0 612x792, N 0 1000x1000, B 0 612x792
  held: 0 A 0 612x792, 2 B 0 612x792
  saved: A 0 612x792, N 0 1000x1000, B 0 612x792
source pages without /Type, one inheriting /Rotate 90 and one with its own /Rotate 180: A 0 612x792, A 0 612x792, B 180 612x792, B 0 612x792
  held: 0 A 0 612x792, 3 B 0 612x792
  saved: A 0 612x792, A 0 612x792, B 180 612x792, B 0 612x792
the same into a new document: A 0 612x792, B 180 612x792
a source page whose /Type is not /Page: Cannot load page 0
  inserted: A 0 612x792, B 180 612x792, A 0 612x792, B 180 612x792
