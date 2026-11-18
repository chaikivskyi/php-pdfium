--TEST--
A NUL in a bookmark title comes back as a space, as PDFium gives it, and does not cut the title short
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', [
    '<< /Type /Outlines /First 7 0 R /Last 8 0 R /Count 2 >>',
    '<< /Title (Chapter\000 1) /Parent 6 0 R /Next 8 0 R >>',
    '<< /Title (\000Lead) /Parent 6 0 R /Prev 7 0 R >>',
]));
foreach ($doc->bookmarks() as $b) {
    var_dump(bin2hex($b->title));
}
?>
--EXPECT--
string(20) "43686170746572202031"
string(10) "204c656164"
