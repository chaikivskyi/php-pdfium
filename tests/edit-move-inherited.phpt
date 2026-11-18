--TEST--
Document::movePages() takes a page out from under its page tree node: a page that took its rotation, size or resources from the old node takes the new node's, in a held Page's rotation and after a save, while a held Page keeps the width and height it was loaded with until setRotation() or a box setter refreshes them; the documented recipe keeps a moved page's rotation and size, also with its own crop box off 0, 0 and when moved into a node that sets a crop box; a page without /Type moved within its node keeps its rotation
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$size = fn (Pdfium\Page $p) => trim($p->text()) . ' ' . $p->rotation() . ' ' . $p->width() . 'x' . $p->height();
$box = fn (?Pdfium\Rect $r) => $r ? "[$r->left $r->bottom $r->right $r->top]" : 'null';
$all = fn (Pdfium\Document $doc) => implode(', ', array_map($size, iterator_to_array($doc)));
$run = function (string $pdf, array $indexes, int $to, ?callable $before = null) use ($size, $all) {
    $doc = Pdfium\Document::fromString($pdf);
    $held = iterator_to_array($doc);
    if ($before) { $before($doc); }
    echo 'before: ', $all($doc), "\n";
    $doc->movePages($indexes, $to);
    echo 'after:  ', $all($doc), "\n";
    echo 'held:   ', implode(', ', array_map($size, $held)), "\n";
    echo 'saved:  ', $all(Pdfium\Document::fromString($doc->toString())), "\n";
    return $doc;
};
$rotated = nodes_pdf([['/MediaBox [0 0 200 100] /Rotate 90', 2], ['/MediaBox [0 0 600 800]', 2]]);
$run($rotated, [0], 3);
$run($rotated, [0], 2);
$run(untyped_pdf(), [0], 1);
$doc = $run(nodes_pdf([['/MediaBox [0 0 300 400] /CropBox [10 20 110 220] /Rotate 180', 2], ['', 2]]), [0, 1], 1,
    fn (Pdfium\Document $doc) => $doc->page(0)->setMediaBox(new Pdfium\Rect(0, 0, 60, 80)));
foreach ([1, 2] as $i) {
    echo trim($doc->page($i)->text()), ' media ', $box($doc->page($i)->mediaBox()), ' crop ', $box($doc->page($i)->cropBox()), "\n";
}
$doc = $run($rotated, [1], 2, fn (Pdfium\Document $doc) => $doc->page(1)->setCropBox(new Pdfium\Rect(150, 50, 250, 150)));
echo 'B media ', $box($doc->page(2)->mediaBox()), ' crop ', $box($doc->page(2)->cropBox()), "\n";
$cropped = nodes_pdf([['/MediaBox [0 0 200 100] /Rotate 90', 2], ['/MediaBox [0 0 600 800] /CropBox [0 0 50 60]', 2]]);
foreach ([[$rotated, null, 0, 3], [$rotated, new Pdfium\Rect(150, 50, 250, 150), 1, 3], [$cropped, null, 0, 2]] as [$pdf, $ownCrop, $i, $to]) {
    $doc = Pdfium\Document::fromString($pdf);
    if ($ownCrop) { $doc->page($i)->setCropBox($ownCrop); }
    $page = $doc->page($i);
    echo 'recipe: ', $size($page);
    $r = $page->rotation();
    $media = $page->mediaBox();
    $crop = $page->cropBox();
    [$w, $h] = $r % 180 ? [$page->height(), $page->width()] : [$page->width(), $page->height()];
    $c = $crop ?? $media ?? new Pdfium\Rect(0, 0, 0, 0);
    $shown = new Pdfium\Rect($c->left, $c->bottom, $c->left + $w, $c->bottom + $h);
    $doc->movePages([$i], $to);
    $moved = $doc->page($to);
    $moved->setRotation($r);
    $moved->setMediaBox($media ?? $shown);
    $moved->setCropBox($crop ?? $shown);
    echo " moved to $to: ", $size($moved), ', saved: ', $size(Pdfium\Document::fromString($doc->toString())->page($to)), "\n";
}
foreach (['setArtBox' => new Pdfium\Rect(0, 0, 50, 50), 'setRotation' => 0] as $setter => $value) {
    $doc = Pdfium\Document::fromString($rotated);
    $a = $doc->page(0);
    $doc->movePages([0], 2);
    echo 'A held from before the move: ', $size($a);
    $a->$setter($value);
    echo ", after $setter(): ", $size($a), "\n";
}
$courier = nodes_pdf([['/MediaBox [0 0 200 100] /Resources << /Font << /F1 3 0 R >> >>', 2], ['/MediaBox [0 0 200 100]', 2]],
    '', false, 'Courier');
$font = fn (Pdfium\Page $p) => trim($p->text()) . ' in ' . $p->textPage()->chars()[0]->fontName;
foreach ([0, 3] as $to) {
    $doc = Pdfium\Document::fromString($courier);
    $b = $doc->page(1);
    $png = $b->render()->toPng();
    echo 'before: ', $font($b), "\n";
    $doc->movePages([1], $to);
    $moved = $doc->page($to);
    $saved = Pdfium\Document::fromString($doc->toString())->page($to);
    echo "moved to $to: ", $font($moved), ', renders the same: ', json_encode($moved->render()->toPng() === $png),
        '; saved: ', $font($saved), ', renders the same: ', json_encode($saved->render()->toPng() === $png), "\n";
}
?>
--EXPECT--
before: A 90 100x200, B 90 100x200, C 0 600x800, D 0 600x800
after:  B 90 100x200, C 0 600x800, D 0 600x800, A 0 612x792
held:   A 0 100x200, B 90 100x200, C 0 600x800, D 0 600x800
saved:  B 90 100x200, C 0 600x800, D 0 600x800, A 0 612x792
before: A 90 100x200, B 90 100x200, C 0 600x800, D 0 600x800
after:  B 90 100x200, C 0 600x800, A 0 600x800, D 0 600x800
held:   A 0 100x200, B 90 100x200, C 0 600x800, D 0 600x800
saved:  B 90 100x200, C 0 600x800, A 0 600x800, D 0 600x800
before: A 90 400x300, B 180 300x400
after:  B 180 300x400, A 90 400x300
held:   A 90 400x300, B 180 300x400
saved:  B 180 300x400, A 90 400x300
before: A 180 50x60, B 180 100x200, C 0 612x792, D 0 612x792
after:  C 0 612x792, A 0 60x80, B 0 612x792, D 0 612x792
held:   A 0 50x60, B 0 100x200, C 0 612x792, D 0 612x792
saved:  C 0 612x792, A 0 60x80, B 0 612x792, D 0 612x792
A media [0 0 60 80] crop null
B media null crop null
before: A 90 100x200, B 90 50x50, C 0 600x800, D 0 600x800
after:  A 90 100x200, C 0 600x800, B 0 100x100, D 0 600x800
held:   A 90 100x200, B 0 50x50, C 0 600x800, D 0 600x800
saved:  A 90 100x200, C 0 600x800, B 0 100x100, D 0 600x800
B media null crop [150 50 250 150]
recipe: A 90 100x200 moved to 3: A 90 100x200, saved: A 90 100x200
recipe: B 90 50x50 moved to 3: B 90 50x50, saved: B 90 50x50
recipe: A 90 100x200 moved to 2: A 90 100x200, saved: A 90 100x200
A held from before the move: A 0 100x200, after setArtBox(): A 0 600x800
A held from before the move: A 0 100x200, after setRotation(): A 0 600x800
before: B in Courier
moved to 0: B in Courier, renders the same: true; saved: B in Courier, renders the same: true
before: B in Courier
moved to 3: B in Helvetica, renders the same: false; saved: B in Helvetica, renders the same: false
