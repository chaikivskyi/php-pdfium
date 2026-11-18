--TEST--
PageIterator::current() gives the page now at key() after the current page was deleted or moved by an edit; a Page already held keeps its own; foreach skips one page and sees another again when a page moves across its position
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf(4));
$it = $doc->pages();
$it->rewind();
$it->next();
$b = $it->current();
$doc->deletePage(1);
var_dump($it->valid(), $it->key(), $it->current()->index(), trim($it->current()->text()));
try { $b->index(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
$c = $it->current();
var_dump($it->current() === $c);
$doc->addPage(10, 10, 0);
var_dump($it->key(), $it->current()->index(), trim($it->current()->text()), $c->index(), trim($c->text()));
$a = $it->current();
$doc->movePages([1], 3);
var_dump($it->key(), $it->current()->index(), trim($it->current()->text()), $a->index());
$it->next();
$it->next();
var_dump($it->valid(), $it->key(), trim($it->current()->text()));
$doc->deletePage(3);
var_dump($it->valid());
try { $it->current(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
$doc = Pdfium\Document::fromString(letters_pdf(5));
$seen = '';
foreach ($doc as $i => $page) { $seen .= trim($page->text()); if ($i === 1) { $doc->movePages([1], 3); } }
echo $seen, "\n";
?>
--EXPECT--
bool(true)
int(1)
int(1)
string(1) "C"
The page was deleted from its document
bool(true)
int(1)
int(1)
string(1) "A"
int(2)
string(1) "C"
int(1)
int(1)
string(1) "C"
int(3)
bool(true)
int(3)
string(1) "A"
bool(false)
Iterator is past the last page
ABDBE
