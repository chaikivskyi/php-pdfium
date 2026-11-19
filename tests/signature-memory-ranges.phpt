--TEST--
signatures() throws MemoryException, never a fatal error, for a long byte range at any memory_limit
--EXTENSIONS--
pdfium
--SKIPIF--
<?php if (getenv('USE_ZEND_ALLOC') === '0') die('skip the sweep is tuned to the Zend allocator'); ?>
--INI--
memory_limit=512M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$pairs = 1 << 18;
$doc = Pdfium\Document::fromString(sig_pdf(sig_field('<< /ByteRange [' . str_repeat('0 1 ', $pairs) . '] >>')));
$base = memory_get_usage(true);
$thrown = 0;
for ($extra = 32 << 20; $extra <= 96 << 20; $extra += 256 << 10) {
    ini_set('memory_limit', (string) ($base + $extra));
    try {
        $doc->signatures();
        break;
    } catch (Pdfium\MemoryException $e) {
        $thrown++;
    }
}
ini_set('memory_limit', '512M');
var_dump($thrown > 0, count($doc->signatures()[0]->byteRange) === $pairs);
?>
--EXPECT--
bool(true)
bool(true)
