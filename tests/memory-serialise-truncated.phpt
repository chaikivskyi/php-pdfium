--TEST--
toString() never returns a truncated document when PDFium's last buffered write passes memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=256M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(noise_pdf(200));
$n = strlen($doc->toString());
gc_collect_cycles();
$attempt = function (int $limit) use ($doc): ?int {
    ini_set('memory_limit', (string) $limit);
    try {
        return strlen($doc->toString());
    } catch (Pdfium\MemoryException $e) {
        return null;
    } finally {
        ini_set('memory_limit', '256M');
    }
};
// The margin keeps the limit above current usage under the tracked allocator, where usage is not chunk-rounded.
$lo = memory_get_usage(true) + (32 << 10);
$hi = $lo + 2 * $n + (4 << 20);
var_dump($attempt($lo), $attempt($hi) === $n);
while ($hi - $lo > 1024) {
    $mid = intdiv($lo + $hi, 2);
    $attempt($mid) === null ? $lo = $mid : $hi = $mid;
}
$sizes = [];
for ($limit = $hi; $limit < $hi + (64 << 10); $limit += 1024) {
    $sizes[] = $attempt($limit) ?? $n;
}
var_dump(array_unique($sizes) === [$n]);
?>
--EXPECT--
NULL
bool(true)
bool(true)
