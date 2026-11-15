--TEST--
TextPage::chars() does not start the cycle collector while it holds PDFium, even when a collectable TextPage is waiting
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$content = 'BT /F1 12 Tf 72 700 Td ';
for ($i = 0; $i < 50; $i++) {
    $content .= sprintf('%.3F 0 0 rg 0 %.3F 0 RG (x) Tj ', $i / 50, $i / 50);
}
$doc = Pdfium\Document::fromString(page_pdf(612, 792, $content . 'ET'));
$page = $doc->page(0)->textPage();
$cycle = new stdClass;
$cycle->self = $cycle;
$cycle->page = $doc->page(0)->textPage();
unset($cycle);
$keep = [];
while (gc_status()['roots'] < gc_status()['threshold'] - 5) {
    $o = new stdClass;
    $keep[] = $o;
    unset($o);
}
$runs = gc_status()['runs'];
$chars = $page->chars();
var_dump(count($chars), gc_status()['runs'] === $runs);
var_dump(gc_collect_cycles() > 0);
?>
--EXPECT--
int(50)
bool(true)
bool(true)
