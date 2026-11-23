--TEST--
Document::movePages() reorders pages; held pages follow; bad arguments are refused with the order unchanged; its memory check counts the index buffer once
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$fresh = fn () => Pdfium\Document::fromString(letters_pdf(4));
$idx = fn (array $ps) => implode('', array_map(fn ($p) => $p->index(), $ps));
$doc = $fresh(); $held = iterator_to_array($doc);
$doc->movePages([3, 2], 1);
echo order($doc), ' ', $idx($held), "\n";
$doc = $fresh(); $held = iterator_to_array($doc);
$doc->movePages([0], 3);
echo order($doc), ' ', $idx($held), "\n";
$doc = $fresh(); $held = iterator_to_array($doc);
$doc->movePages([1, 3], 0);
echo order($doc), ' ', $idx($held), "\n";
$doc->movePages([0, 1, 2, 3], 0);
echo order($doc), ' ', trim($held[0]->text()), "\n";
echo order(Pdfium\Document::fromString($doc->toString())), "\n";
foreach ([[[], 0], [[0, 4], 0], [[2, 2], 0], [[3, 0, 3], 0], [['0'], 0], [[0, 3, 1], 2], [[0], -1], [[0], 4]] as [$ix, $to]) {
    try { $doc->movePages($ix, $to); echo "no throw\n"; } catch (Throwable $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
    echo order($doc), "\n";
}
try { Pdfium\Document::create()->movePages([0], 0); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
$doc = Pdfium\Document::fromString(letters_pdf(6)); $held = iterator_to_array($doc);
$doc->movePages([4, 1], 2);
echo order($doc), ' ', $idx($held), "\n";
$doc = $fresh(); $held = iterator_to_array($doc);
$doc->deletePage(1);
$doc->movePages([2, 0], 0);
echo order($doc), ' ', $held[0]->index(), $held[2]->index(), $held[3]->index(), "\n";
try { $held[1]->index(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
$doc = $fresh();
$many = array_fill(0, 600000, 0);
ini_set('memory_limit', (string) (memory_get_usage(true) + (4 << 20)));
try { $doc->movePages($many, 0); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
echo order($doc), "\n";
unset($many);
ini_set('memory_limit', '512M');
$liar = Pdfium\Document::fromString(liar_pdf(1048574));
$many = range(0, 999999);
ini_set('memory_limit', (string) (memory_get_usage(true) + 12000000 + (8 << 20)));
try { $liar->movePages($many, 48575); } catch (Throwable $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
?>
--EXPECT--
ADCB 0321
BCDA 3012
BDAC 2031
BDAC A
BDAC
ValueError: Pdfium\Document::movePages(): Argument #1 ($indexes) must not be empty
BDAC
ValueError: Pdfium\Document::movePages(): Argument #1 ($indexes) must contain only indexes between 0 and 3
BDAC
ValueError: Pdfium\Document::movePages(): Argument #1 ($indexes) must not contain an index twice
BDAC
ValueError: Pdfium\Document::movePages(): Argument #1 ($indexes) must not contain an index twice
BDAC
TypeError: Pdfium\Document::movePages(): Argument #1 ($indexes) must contain only int, string given
BDAC
ValueError: Pdfium\Document::movePages(): Argument #2 ($to) must be between 0 and 1
BDAC
ValueError: Pdfium\Document::movePages(): Argument #2 ($to) must be between 0 and 3
BDAC
ValueError: Pdfium\Document::movePages(): Argument #2 ($to) must be between 0 and 3
BDAC
Pdfium\Document::movePages(): Argument #1 ($indexes) must contain only page indexes, but the document has no pages
ACEBDF 031425
DAC 120
The page was deleted from its document
The page indexes need more memory than memory_limit leaves
ABCD
ValueError: Pdfium\Document::movePages(): Argument #2 ($to) must be between 0 and 48574
