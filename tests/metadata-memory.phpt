--TEST--
metadata() throws MemoryException, not a fatal error, when an entry would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=64M
--FILE--
<?php
$objs = [
    '<< /Type /Catalog /Pages 2 0 R >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] >>',
    '<< /Title (' . str_repeat('A', 12 << 20) . ') >>',
];
$pdf = "%PDF-1.7\n";
$xref = '';
foreach ($objs as $i => $body) {
    $xref .= sprintf("%010d 00000 n \n", strlen($pdf));
    $pdf .= ($i + 1) . " 0 obj\n$body\nendobj\n";
}
$pdf .= "xref\n0 5\n0000000000 65535 f \n{$xref}trailer\n<< /Size 5 /Root 1 0 R /Info 4 0 R >>\nstartxref\n"
    . strlen($pdf) . "\n%%EOF\n";
unset($objs, $xref);
$doc = Pdfium\Document::fromString($pdf);
unset($pdf);
try { $doc->metadata(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump(Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf')->metadata()['title']);
?>
--EXPECT--
The Title entry needs more memory than memory_limit leaves
string(13) "Fixture Title"
