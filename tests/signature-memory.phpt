--TEST--
signatures() throws MemoryException, not a fatal error, when the contents or byte ranges would not fit under memory_limit; signatureCount() and smaller documents still work
--EXTENSIONS--
pdfium
--INI--
memory_limit=128M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$big = Pdfium\Document::fromString(sig_pdf(str_repeat(sig_field('6 0 R') . ' ', 64), [
    '<< /Contents (' . str_repeat('a', 4 << 20) . ') >>',
]));
var_dump($big->signatureCount());
try { $big->signatures(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
unset($big);

$ranges = Pdfium\Document::fromString(sig_pdf(sig_field('<< /ByteRange [' . str_repeat('0 1 ', 1 << 18) . '] >>')));
var_dump(count($ranges->signatures()[0]->byteRange));
$limit = ini_get('memory_limit');
ini_set('memory_limit', (string) (memory_get_usage(true) + (8 << 20)));
try { $ranges->signatures(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
ini_set('memory_limit', $limit);
unset($ranges);

$small = Pdfium\Document::fromString(sig_pdf(sig_field('<< /Contents <01> >>')));
var_dump(count($small->signatures()));
?>
--EXPECT--
int(64)
The signatures need more memory than memory_limit leaves
int(262144)
The signatures need more memory than memory_limit leaves
int(1)
