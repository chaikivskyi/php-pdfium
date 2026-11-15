--TEST--
TextPage::webLinks() returns the URLs written in the text with their character ranges and rectangles
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(lines_pdf('Visit https://example.com/a?b=1 now', 'or www.example.org and mail me@example.net'))->page(0)->textPage();
$chars = $page->chars();
foreach ($page->webLinks() as $l) {
    echo $l->url, ' ', $l->index, ' ', $l->length, ' ', count($l->rects), ' ',
        implode('', array_map(fn($c) => $c->text, array_slice($chars, $l->index, $l->length))), "\n";
}
var_dump(Pdfium\Document::fromString(lines_pdf('no links here'))->page(0)->textPage()->webLinks());
?>
--EXPECT--
https://example.com/a?b=1 6 25 1 https://example.com/a?b=1
http://www.example.org 40 15 1 www.example.org
mailto:me@example.net 65 14 1 me@example.net
array(0) {
}
