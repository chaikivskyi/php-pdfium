--TEST--
bookmarks() throws MemoryException, not a fatal error, when the outline would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=64M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', [
    '<< /Type /Outlines /First 7 0 R /Last 7 0 R >>',
    '<< /Title (' . str_repeat('A', 12 << 20) . ') /Parent 6 0 R >>',
]));
try { $doc->bookmarks(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
echo Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', [
    '<< /Type /Outlines /First 7 0 R /Last 7 0 R >>',
    '<< /Title (Small) /Parent 6 0 R >>',
]))->bookmarks()[0]->title, "\n";
?>
--EXPECT--
Bookmarks need more memory than memory_limit leaves
Small
