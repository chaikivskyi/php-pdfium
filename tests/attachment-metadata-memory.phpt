--TEST--
Attachment::metadata() throws MemoryException naming the field, not a fatal error, when an entry would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=64M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(attach_pdf('(a) 6 0 R (b) 8 0 R', [
    file_spec('a', 7, '/Desc (' . str_repeat('D', 12 << 20) . ')'),
    stream_obj('a'),
    file_spec('b', 9, '/Desc (small)'),
    stream_obj('b'),
]));
[$a, $b] = $doc->attachments();
try { $a->metadata(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump($b->metadata()->description);
?>
--EXPECT--
The attachment's description needs more memory than memory_limit leaves
string(5) "small"
