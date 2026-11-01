--TEST--
metadata() throws MemoryException, not a fatal error, when an entry would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=64M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(build_pdf([
    '<< /Type /Catalog /Pages 2 0 R >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] >>',
    '<< /Title (' . str_repeat('A', 12 << 20) . ') >>',
], '/Info 4 0 R '));
try { $doc->metadata(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf')->metadata()->title);
?>
--EXPECT--
The Title entry needs more memory than memory_limit leaves
string(13) "Fixture Title"
