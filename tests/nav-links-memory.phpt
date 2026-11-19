--TEST--
links(), linkAt() and Annotation::link() throw MemoryException, not a fatal error, when a link would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=256M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(nav_pdf(
    extra: ['<< /Type /Annot /Subtype /Link /Rect [0 0 100 100] /A << /S /URI /URI (' . str_repeat('a', 32 << 20) . ') >> >>'],
    pageExtra: ['/Annots [6 0 R]', '', ''],
))->page(0);
$annot = $page->annotation(0);
ini_set('memory_limit', (string) (memory_get_usage(true) + (16 << 20)));
try { $page->links(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $page->linkAt(50, 50); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $annot->link(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
echo count(Pdfium\Document::fromString(nav_pdf(
    extra: ['<< /Type /Annot /Subtype /Link /Rect [0 0 100 100] /Dest [3 0 R /Fit] >>'],
    pageExtra: ['/Annots [6 0 R]', '', ''],
))->page(0)->links()), "\n";
?>
--EXPECT--
Links need more memory than memory_limit leaves
The link needs more memory than memory_limit leaves
The link needs more memory than memory_limit leaves
1
