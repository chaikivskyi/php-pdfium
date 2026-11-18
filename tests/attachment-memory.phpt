--TEST--
contents() throws MemoryException, not a fatal error, when the decoded file would not fit under memory_limit, and a smaller one still reads
--EXTENSIONS--
pdfium
--INI--
memory_limit=512M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(attach_pdf('(big) 6 0 R (small) 8 0 R', [
    file_spec('big', 7),
    stream_obj(gzcompress(str_repeat('a', 64 << 20)), '/Filter /FlateDecode '),
    file_spec('small', 9),
    stream_obj('tiny'),
]));
[$big, $small] = $doc->attachments();
ini_set('memory_limit', (string) (memory_get_usage(true) + (16 << 20)));
try { $big->contents(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump($small->contents());
?>
--EXPECT--
The attachment needs more memory than memory_limit leaves
string(4) "tiny"
