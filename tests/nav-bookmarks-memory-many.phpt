--TEST--
bookmarks() throws MemoryException, never a fatal error, for a large outline at any memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=512M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$n = 70000;
$objs = ['<< /Type /Outlines /First 7 0 R /Last ' . ($n + 6) . ' 0 R >>'];
for ($i = 0; $i < $n; $i++) {
    $objs[] = '<< /Title (B) /Parent 6 0 R' . ($i + 1 < $n ? ' /Next ' . ($i + 8) . ' 0 R' : '') . ' >>';
}
$doc = Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', $objs));
unset($objs);
$base = memory_get_usage(true);
$thrown = 0;
for ($extra = 1 << 20; $extra <= 48 << 20; $extra += 256 << 10) {
    ini_set('memory_limit', (string) ($base + $extra));
    try {
        $count = count($doc->bookmarks());
    } catch (Pdfium\MemoryException $e) {
        $thrown++;
    }
}
ini_set('memory_limit', '512M');
var_dump($thrown > 0, count($doc->bookmarks()));
?>
--EXPECT--
bool(true)
int(70000)
