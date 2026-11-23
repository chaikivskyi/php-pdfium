--TEST--
signatures() throws MemoryException, never a fatal error, when thousands of unsigned fields follow a large signature, at any memory_limit
--EXTENSIONS--
pdfium
--SKIPIF--
<?php
if (getenv('USE_ZEND_ALLOC') === '0') die('skip the sweep is tuned to the Zend allocator');
if (getenv('SKIP_SLOW_TESTS')) die('skip slow test');
?>
--INI--
memory_limit=512M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$fields = 12500;
$doc = Pdfium\Document::fromString(sig_pdf(sig_field('6 0 R') . str_repeat(' << /FT /Sig >>', $fields), [
    '<< /Contents (' . str_repeat('a', 4 << 20) . ') >>',
]));
$base = memory_get_usage(true);
$thrown = 0;
$listed = 0;
for ($extra = 4 << 20; $extra <= 12 << 20; $extra += 2 << 20) {
    ini_set('memory_limit', (string) ($base + $extra));
    try {
        $listed = count($doc->signatures());
        break;
    } catch (Pdfium\MemoryException $e) {
        $thrown++;
    }
}
var_dump($thrown > 0, $listed === $fields + 1);
?>
--EXPECT--
bool(true)
bool(true)
