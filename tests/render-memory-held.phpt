--TEST--
Bitmaps kept alive count against memory_limit, so holding many renders throws MemoryException
--EXTENSIONS--
pdfium
--INI--
memory_limit=32M
--FILE--
<?php
$page = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf')->page(0);
$before = memory_get_usage();
$img = $page->render(dpi: 72);
var_dump(memory_get_usage() - $before >= 612 * 792 * 4);
$bitmaps = [];
try {
    for ($i = 0; $i < 40; $i++) {
        $bitmaps[] = $page->render(dpi: 72);
    }
} catch (Pdfium\MemoryException $e) {
    echo "MemoryException\n";
}
var_dump(count($bitmaps) < 40);
unset($bitmaps);
var_dump($page->render(dpi: 72)->width());
?>
--EXPECT--
bool(true)
MemoryException
bool(true)
int(612)
