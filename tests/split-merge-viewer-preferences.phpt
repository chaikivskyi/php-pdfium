--TEST--
extractPages() and merge() copy no viewer preferences, like PDFium's page import
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(build_pdf([
    '<< /Type /Catalog /Pages 2 0 R /PageLayout /TwoColumnRight /PageMode /UseThumbs /ViewerPreferences << /HideToolbar true /Direction /R2L >> >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] >>',
]));
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
