--TEST--
Document::movePages() takes a page out from under its page tree node: a page that took its rotation, size or resources from the old node takes the new node's, in a held Page's rotation and after a save, while a held Page keeps the width and height it was loaded with; a page without /Type moved within its node keeps its rotation
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$size = fn (Pdfium\Page $p) => trim($p->text()) . ' ' . $p->rotation() . ' ' . $p->width() . 'x' . $p->height();
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
before: B in Courier
moved to 0: B in Courier, renders the same: true; saved: B in Courier, renders the same: true
before: B in Courier
moved to 3: B in Helvetica, renders the same: false; saved: B in Helvetica, renders the same: false
