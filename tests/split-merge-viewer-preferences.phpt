--TEST--
extractPages() and merge() copy no viewer preferences, like PDFium's page import
--EXTENSIONS--
pdfium
--FILE--
<?php
$objs = [
    '<< /Type /Catalog /Pages 2 0 R /PageLayout /TwoColumnRight /PageMode /UseThumbs /ViewerPreferences << /HideToolbar true /Direction /R2L >> >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] >>',
];
$pdf = "%PDF-1.7\n";
$xref = '';
foreach ($objs as $i => $body) {
    $xref .= sprintf("%010d 00000 n \n", strlen($pdf));
    $pdf .= ($i + 1) . " 0 obj\n$body\nendobj\n";
}
$pdf .= "xref\n0 4\n0000000000 65535 f \n{$xref}trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n" . strlen($pdf) . "\n%%EOF\n";
$doc = Pdfium\Document::fromString($pdf);
function viewer_keys(Pdfium\Document $doc): string
{
    preg_match_all('~/(ViewerPreferences|PageLayout|PageMode|HideToolbar|Direction)\b~', $doc->toString(), $m);
    $keys = array_unique($m[1]);
    sort($keys);
    return $keys ? implode(' ', $keys) : 'none';
}
echo 'source: ', viewer_keys($doc), "\n";
echo 'extractPages: ', viewer_keys($doc->extractPages([0])), "\n";
echo 'merge: ', viewer_keys(Pdfium\Document::merge([$doc])), "\n";
?>
--EXPECT--
source: Direction HideToolbar PageLayout PageMode ViewerPreferences
extractPages: none
merge: none
