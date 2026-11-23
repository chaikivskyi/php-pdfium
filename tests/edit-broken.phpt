--TEST--
Page edits on a page tree that lists a page twice, loops, or has a /Count that lies return or throw, and never leave a held Page with a wrong index; after a failed edit, held pages say their position is unknown and deleted ones still say deleted; a page listed twice takes a rotation and a box at both indexes, and a Page held at the other index keeps its old size while one fetched again has the new size (documented limit)
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$good = Pdfium\Document::fromString(letters_pdf(4))->extractPages([3]);
$fixtures = ['twice' => twice_pdf(), 'loop' => loop_pdf(), 'liar 2' => liar_pdf(2), 'liar 50' => liar_pdf(50)];
$calls = [
    'deletePage' => fn (Pdfium\Document $doc) => $doc->deletePage(0),
    'movePages' => fn (Pdfium\Document $doc) => $doc->movePages([0], 1),
    'addPage' => fn (Pdfium\Document $doc) => $doc->addPage(10, 10, 0),
    'insertPages' => fn (Pdfium\Document $doc) => $doc->insertPages($good, null, 0),
];
function held(Pdfium\Document $doc): array
{
    $held = [];
    for ($i = 0; $i < $doc->pageCount(); $i++) {
        try { $held[] = $doc->page($i); } catch (Pdfium\FormatException) {}
    }
    return $held;
}
function edit(Pdfium\Document $doc, array &$held, callable $call): string
{
    try {
        $page = $call($doc);
        if ($page instanceof Pdfium\Page) { $held[] = $page; }
        $out = 'ok';
    } catch (ValueError) {
        $out = 'ValueError';
    } catch (Pdfium\PdfiumException $e) {
        $out = $e->getMessage();
    }
    $checked = $lost = 0;
    foreach ($held as $k => $p) {
        try { $index = $p->index(); } catch (Pdfium\PdfiumException $e) {
            if ($e->getMessage() === "The page's position is unknown after a failed edit of its document; load the document again") {
                $lost++;
            } elseif ($e->getMessage() !== 'The page was deleted from its document') {
                $out .= " [held $k: {$e->getMessage()}]";
            }
            continue;
        }
        if ($index >= $doc->pageCount()) {
            $out .= " [held $k past the end at $index]";
            continue;
        }
        try {
            if (trim($doc->page($index)->text()) !== trim($p->text())) { $out .= " [held $k at $index is another page]"; }
            $checked++;
        } catch (Pdfium\FormatException $e) {
            $out .= " [held $k at $index: {$e->getMessage()}]";
        }
    }
    return "$out ($checked checked" . ($lost ? ", $lost lost" : '') . ')';
}
foreach ($fixtures as $name => $bytes) {
    $doc = Pdfium\Document::fromString($bytes);
    $held = [];
    echo "$name: ", $doc->pageCount(), ' pages, ', count(held($doc)), " load\n";
    foreach ($calls as $call => $f) {
        $held = array_merge($held, held($doc));
        echo "$name $call: ", edit($doc, $held, $f), "\n";
    }
    echo "$name: ", order($doc), ', saved and reopened: ', order(Pdfium\Document::fromString($doc->toString())), "\n";
    $doc = Pdfium\Document::fromString($bytes);
    $held = held($doc);
    echo "$name movePages([2], 0) alone: ", edit($doc, $held, fn (Pdfium\Document $doc) => $doc->movePages([2], 0)), "\n";
}
foreach ([null, [4]] as $indexes) {
    $doc = Pdfium\Document::fromString(letters_pdf(3));
    $held = held($doc);
    echo 'letters insertPages(liar 50, ', json_encode($indexes), '): ', edit($doc, $held,
        fn (Pdfium\Document $doc) => $doc->insertPages(Pdfium\Document::fromString(liar_pdf(50)), $indexes, 0)),
        ', ', $doc->pageCount(), " pages\n";
}
$doc = Pdfium\Document::fromString(liar_pdf(50));
$held = held($doc);
echo 'liar 50 insertPages(itself): ', edit($doc, $held, fn (Pdfium\Document $doc) => $doc->insertPages($doc, null, 0)),
    ', ', $doc->pageCount(), " pages\n";
$doc = Pdfium\Document::fromString(liar_pdf(50));
$held = held($doc);
echo 'liar 50 movePages([0, 5], 1): ', edit($doc, $held, fn (Pdfium\Document $doc) => $doc->movePages([0, 5], 1)),
    ', ', substr(order($doc), 0, 4), "\n";
$doc = Pdfium\Document::fromString(liar_pdf(50));
$saved = fn (Pdfium\Document $doc) => preg_replace('~/ID\[<\w+><\w+>\]~', '', $doc->toString());
$bytes = $saved($doc);
try { $doc->movePages([0, 1, 5], 0); } catch (Pdfium\PdfiumException $e) { echo 'liar 50 movePages([0, 1, 5], 0): ', $e->getMessage(), "\n"; }
$box = fn (?Pdfium\Rect $r) => $r ? "[$r->left $r->bottom $r->right $r->top]" : 'null';
echo '  saved the same but for its file identifier: ', json_encode($saved($doc) === $bytes), ', crop boxes ', $box($doc->page(0)->cropBox()), ' ',
    $box($doc->page(1)->cropBox()), ', media boxes ', $box($doc->page(0)->mediaBox()), ' ', $box($doc->page(1)->mediaBox()), "\n";
$doc = Pdfium\Document::fromString(liar_pdf(50));
$held = held($doc);
echo 'liar 50 deletePage(5): ', edit($doc, $held, fn (Pdfium\Document $doc) => $doc->deletePage(5)), ', ',
    $doc->pageCount(), " pages\n";
$doc = Pdfium\Document::fromString(twice_pdf());
$first = $doc->page(0);
$third = $doc->page(2);
$first->setRotation(90);
$size = fn (Pdfium\Page $p) => $p->rotation() . ' ' . $p->width() . 'x' . $p->height();
echo 'twice setRotation(90) on index 0: index 0 ', $size($first), ', held index 2 ', $size($third),
    ', fetched index 2 ', $size($doc->page(2)), "\n";
$first->setCropBox(new Pdfium\Rect(10, 20, 300, 400));
$box = $third->cropBox();
echo "twice setCropBox() on index 0: held index 2 crop box $box->left $box->bottom $box->right $box->top\n";
$back = Pdfium\Document::fromString($doc->toString());
echo 'twice saved and reopened: ', $size($back->page(0)), ', ', $size($back->page(2)), "\n";
?>
--EXPECT--
twice: 3 pages, 3 load
twice deletePage: ok (2 checked)
twice movePages: ok (4 checked)
twice addPage: ok (7 checked)
twice insertPages: ok (10 checked)
twice: D_AB, saved and reopened: D_AB
twice movePages([2], 0) alone: ok (3 checked)
loop: 5 pages, 5 load
loop deletePage: ok (4 checked)
loop movePages: Cannot move the pages: the document's page order is now undefined; load the document again (0 checked, 8 lost)
loop addPage: Cannot add a page (3 checked, 8 lost)
loop insertPages: Cannot import pages (6 checked, 8 lost)
loop: ABA, saved and reopened: !!!
loop movePages([2], 0) alone: Cannot move the pages: the document's page order is now undefined; load the document again (0 checked, 5 lost)
liar 2: 2 pages, 2 load
liar 2 deletePage: ok (1 checked)
liar 2 movePages: ValueError (2 checked)
liar 2 addPage: ok (4 checked)
liar 2 insertPages: ok (6 checked)
liar 2: D_B, saved and reopened: D_B
liar 2 movePages([2], 0) alone: ValueError (2 checked)
liar 50: 50 pages, 3 load
liar 50 deletePage: ok (2 checked)
liar 50 movePages: ok (4 checked)
liar 50 addPage: ok (7 checked)
liar 50 insertPages: ok (10 checked)
liar 50: D_CB!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!, saved and reopened: D_C!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
liar 50 movePages([2], 0) alone: ok (3 checked)
letters insertPages(liar 50, null): Cannot import pages: the document's page order is now undefined; load the document again (0 checked, 3 lost), 7 pages
letters insertPages(liar 50, [4]): Cannot import pages: the document's page order is now undefined; load the document again (0 checked, 3 lost), 4 pages
liar 50 insertPages(itself): Cannot import pages (3 checked), 50 pages
liar 50 movePages([0, 5], 1): Cannot move the pages: the document's page order is now undefined; load the document again (0 checked, 3 lost), ABC!
liar 50 movePages([0, 1, 5], 0): Cannot move the pages: the document's page order is now undefined; load the document again
  saved the same but for its file identifier: true, crop boxes null null, media boxes [0 0 612 792] [0 0 612 792]
liar 50 deletePage(5): Cannot delete the page at index 5 (3 checked), 50 pages
twice setRotation(90) on index 0: index 0 90 792x612, held index 2 90 612x792, fetched index 2 90 792x612
twice setCropBox() on index 0: held index 2 crop box 10 20 300 400
twice saved and reopened: 90 380x290, 90 380x290