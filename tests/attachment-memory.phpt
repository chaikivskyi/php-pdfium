--TEST--
contents() throws MemoryException, not a fatal error, when the decoded file would not fit under memory_limit, and a smaller one still reads; a stream PDFium cannot decode comes back as stored
--EXTENSIONS--
pdfium
--INI--
memory_limit=128M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$runs = fn (int $mb) => str_repeat("\x81a", $mb << 13) . "\x80";
$doc = Pdfium\Document::fromString(attach_pdf('(big) 6 0 R (huge) 10 0 R (small) 8 0 R', [
    file_spec('big', 7),
    stream_obj($runs(16), '/Filter /RunLengthDecode '),
    file_spec('small', 9),
    stream_obj('tiny'),
    file_spec('huge', 11),
    stream_obj($runs(24), '/Filter /RunLengthDecode '),
]));
[$big, $huge, $small] = $doc->attachments();
var_dump(strlen($big->contents()), strlen($huge->contents()) === strlen($runs(24)));
ini_set('memory_limit', (string) (memory_get_usage(true) + (8 << 20)));
try { $big->contents(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump($small->contents());
?>
--EXPECT--
int(16777216)
bool(true)
The attachment needs more memory than memory_limit leaves
string(4) "tiny"
