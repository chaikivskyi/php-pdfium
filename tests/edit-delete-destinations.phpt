--TEST--
After deletePage(), a destination that refers to a page object follows that page; one stored as a page number is returned as written, so it names another page or none; one that refers to the deleted page becomes null, also in a GoTo action and a bookmark
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf(
    catalog: '/Outlines 11 0 R',
    extra: [
        '<< /Type /Annot /Subtype /Link /Rect [0 0 10 10] /Dest [4 0 R /Fit] >>',
        '<< /Type /Annot /Subtype /Link /Rect [20 0 30 10] /Dest [1 /Fit] >>',
        '<< /Type /Annot /Subtype /Link /Rect [40 0 50 10] /Dest [2 /Fit] >>',
        '<< /Type /Annot /Subtype /Link /Rect [60 0 70 10] /Dest [3 0 R /Fit] >>',
        '<< /Type /Annot /Subtype /Link /Rect [80 0 90 10] /A << /S /GoTo /D [3 0 R /Fit] >> >>',
        '<< /Type /Outlines /First 12 0 R /Last 12 0 R /Count 1 >>',
        '<< /Title (First) /Parent 11 0 R /Dest [3 0 R /Fit] >>',
    ],
    pageExtra: ['', '', '/Annots [6 0 R 7 0 R 8 0 R 9 0 R 10 0 R]'],
));
$target = fn (Pdfium\Link|Pdfium\Bookmark $l) => json_encode(($l->destination ?? $l->action?->destination)?->pageIndex);
$show = fn (Pdfium\Document $doc) => implode(', ', array_map($target, $doc->page($doc->pageCount() - 1)->links()))
    . '; bookmark ' . $target($doc->bookmarks()[0]);
echo 'before: ', $show($doc), "\n";
$doc->deletePage(0);
echo 'after deletePage(0): ', $show($doc), "\n";
echo 'saved: ', $show(Pdfium\Document::fromString($doc->toString())), "\n";
?>
--EXPECT--
before: 1, 1, 2, 0, 0; bookmark 0
after deletePage(0): 0, 1, null, null, null; bookmark null
saved: 0, 1, null, null, null; bookmark null
