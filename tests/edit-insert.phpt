--TEST--
Document::insertPages() copies pages of another document in; held pages follow; errors leave the document unchanged; it sets the producer when the document has an information dictionary
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf(3));
$src = Pdfium\Document::fromString(letters_pdf(6));
[$a, $b, $c] = [$doc->page(0), $doc->page(1), $doc->page(2)];
$doc->insertPages($src, [5, 3, 3], 1);
echo order($doc), ' ', $a->index(), $b->index(), $c->index(), "\n";
$doc->insertPages($src);
echo order($doc), "\n";
$doc->insertPages($src, [4], 0);
echo order($doc), ' ', $a->index(), "\n";
unset($src);
echo order(Pdfium\Document::fromString($doc->toString())), "\n";
$src = Pdfium\Document::fromString(letters_pdf(6));
$count = $doc->pageCount();
foreach ([
    fn () => $doc->insertPages($src, []),
    fn () => $doc->insertPages($src, [6]),
    fn () => $doc->insertPages($src, [-1]),
    fn () => $doc->insertPages($src, ['1']),
    fn () => $doc->insertPages($src, [0], -1),
    fn () => $doc->insertPages($src, [0], $count + 1),
    fn () => $doc->insertPages(Pdfium\Document::create()),
    fn () => $doc->insertPages(Pdfium\Document::create(), [0]),
] as $f) {
    try { $f(); echo "no throw\n"; } catch (Throwable $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
    var_dump($doc->pageCount() === $count);
}
$doc = Pdfium\Document::fromString(letters_pdf(4));
$many = array_fill(0, 1500000, 0);
ini_set('memory_limit', (string) (memory_get_usage(true) + (1 << 20)));
try { $doc->insertPages($src, $many); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
ini_restore('memory_limit');
echo $doc->pageCount(), ' ', order($doc), "\n";
$info = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf');
$info->insertPages($src, [0]);
$info->insertPages($info, [0]);
$reopened = Pdfium\Document::fromString($info->toString())->metadata();
var_dump($info->metadata()->producer, $reopened->producer, $reopened->title, $reopened->creationDate);
$doc->insertPages($src, [0]);
var_dump($doc->metadata()->producer);
?>
--EXPECT--
AFDDBC 045
AFDDBCABCDEF
EAFDDBCABCDEF 1
EAFDDBCABCDEF
ValueError: Pdfium\Document::insertPages(): Argument #2 ($indexes) must not be empty
bool(true)
ValueError: Pdfium\Document::insertPages(): Argument #2 ($indexes) must contain only indexes between 0 and 5
bool(true)
ValueError: Pdfium\Document::insertPages(): Argument #2 ($indexes) must contain only indexes between 0 and 5
bool(true)
TypeError: Pdfium\Document::insertPages(): Argument #2 ($indexes) must contain only int, string given
bool(true)
ValueError: Pdfium\Document::insertPages(): Argument #3 ($index) must be between 0 and 13
bool(true)
ValueError: Pdfium\Document::insertPages(): Argument #3 ($index) must be between 0 and 13
bool(true)
ValueError: Pdfium\Document::insertPages(): Argument #1 ($source) must have pages
bool(true)
ValueError: Pdfium\Document::insertPages(): Argument #2 ($indexes) must contain only page indexes, but the document has no pages
bool(true)
The page indexes need more memory than memory_limit leaves
4 ABCD
string(6) "PDFium"
string(6) "PDFium"
string(13) "Fixture Title"
string(17) "D:20261031120000Z"
NULL
