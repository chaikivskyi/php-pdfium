--TEST--
Document::addPage() returns a page of the size asked for, unrotated, also where a page tree node above it carries a crop box or a rotation, and the saved file keeps that size
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$size = fn (Pdfium\Page $p) => trim($p->text()) . ' ' . $p->rotation() . ' ' . $p->width() . 'x' . $p->height();
$all = fn (Pdfium\Document $doc) => implode(', ', array_map($size, iterator_to_array($doc)));
foreach ([
    ['/CropBox [0 0 50 50]', '', 1],
    ['/CropBox [100 100 1000 1000]', '', 1],
    ['/CropBox [0 0 1000 1000]', '', 1],
    ['/Rotate 90 /CropBox [0 0 50 50]', '', 1],
    ['', '/CropBox [0 0 50 50] /Rotate 270', null],
] as [$node, $root, $at]) {
    $doc = Pdfium\Document::fromString(nodes_pdf([[$node, 2]], $root));
    $new = $doc->addPage(300, 400, $at);
    echo "node $node, root $root: new page ", $new->index(), ' ', $size($new), "\n";
    echo '  ', $all($doc), "\n";
    echo '  saved: ', $all(Pdfium\Document::fromString($doc->toString())), "\n";
}
?>
--EXPECT--
node /CropBox [0 0 50 50], root : new page 1  0 300x400
  A 0 50x50,  0 300x400, B 0 50x50
  saved: A 0 50x50,  0 300x400, B 0 50x50
node /CropBox [100 100 1000 1000], root : new page 1  0 300x400
  A 0 512x692,  0 300x400, B 0 512x692
  saved: A 0 512x692,  0 300x400, B 0 512x692
node /CropBox [0 0 1000 1000], root : new page 1  0 300x400
  A 0 612x792,  0 300x400, B 0 612x792
  saved: A 0 612x792,  0 300x400, B 0 612x792
node /Rotate 90 /CropBox [0 0 50 50], root : new page 1  0 300x400
  A 90 50x50,  0 300x400, B 90 50x50
  saved: A 90 50x50,  0 300x400, B 90 50x50
node , root /CropBox [0 0 50 50] /Rotate 270: new page 2  0 300x400
  A 270 50x50, B 270 50x50,  0 300x400
  saved: A 270 50x50, B 270 50x50,  0 300x400
