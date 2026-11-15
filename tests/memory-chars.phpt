--TEST--
TextPage::chars() and TextPage::search(), including its needle, throw MemoryException, not a fatal error, when the result would pass memory_limit; an invalid needle throws ValueError first; a long repetitive needle on a long repetitive page does not take time quadratic in their sizes
--EXTENSIONS--
pdfium
--INI--
memory_limit=512M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(text_pdf(200000))->page(0)->textPage();
$needle = str_repeat('x', 4 << 20);
$bad = "\xFF" . substr($needle, 1);
ini_set('memory_limit', (string) (memory_get_usage(true) + (8 << 20)));
try { $page->search('x'); echo "searched\n"; } catch (Pdfium\MemoryException $e) { echo get_class($e), "\n"; }
ini_set('memory_limit', '512M');
ini_set('memory_limit', (string) (memory_get_usage(true) + (3 << 20)));
try { $page->chars(); echo "listed\n"; } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $page->search($needle); echo "searched\n"; } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $page->search($bad); echo "searched\n"; } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
unset($needle, $bad);
ini_set('memory_limit', '512M');
var_dump(count($page->chars()), count($page->search('x')));
var_dump(count($page->search(str_repeat('x', 100000) . 'y')), count($page->search(str_repeat('x', 199999), overlapping: true)));
?>
--EXPECT--
Pdfium\MemoryException
Page characters need more memory than memory_limit leaves
The needle needs more memory than memory_limit leaves
Pdfium\TextPage::search(): Argument #1 ($needle) must be valid UTF-8
int(200000)
int(200000)
int(0)
int(2)
