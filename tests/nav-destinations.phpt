--TEST--
Destination gives every view with its parameters, null for an unknown view and nulls for absent XYZ values; a link's action wins over its /Dest
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$entries = [
    '/Dest [4 0 R /FitR 10 20 300 400]',
    '/Dest [4 0 R /FitB]',
    '/Dest [4 0 R /FitBH 500]',
    '/Dest [4 0 R /FitBV 60]',
    '/Dest [4 0 R /FitV 80]',
    '/Dest [4 0 R /Foo 1]',
    '/Dest [3 0 R /XYZ null 700 null]',
];
$objects = ['<< /Type /Outlines /First 7 0 R /Last ' . (6 + count($entries)) . ' 0 R /Count ' . count($entries) . ' >>'];
foreach ($entries as $i => $entry) {
    $next = $i + 1 < count($entries) ? ' /Next ' . (8 + $i) . ' 0 R' : '';
    $objects[] = "<< /Title (E$i) /Parent 6 0 R$next $entry >>";
}
$b = Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', $objects))->bookmarks();
foreach ($b as $e) {
    $d = $e->destination;
    echo $e->title, ' ', $d->pageIndex, ' ', var_export($d->view, true), ' [', implode(' ', $d->params), '] ',
        var_export($d->x, true), ' ', var_export($d->y, true), ' ', var_export($d->zoom, true), "\n";
}
$links = Pdfium\Document::fromString(nav_pdf(
    extra: ['<< /Type /Annot /Subtype /Link /Rect [0 0 100 100] /Dest [4 0 R /Fit] /A << /S /URI /URI (https://example.com/) >> >>'],
    pageExtra: ['/Annots [6 0 R]', '', ''],
))->page(0)->links();
var_dump($links[0]->destination, $links[0]->action->type);
?>
--EXPECT--
E0 1 'FitR' [10 20 300 400] NULL NULL NULL
E1 1 'FitB' [] NULL NULL NULL
E2 1 'FitBH' [500] NULL NULL NULL
E3 1 'FitBV' [60] NULL NULL NULL
E4 1 'FitV' [80] NULL NULL NULL
E5 1 NULL [] NULL NULL NULL
E6 0 'XYZ' [0 700 0] NULL 700.0 NULL
NULL
string(3) "URI"
