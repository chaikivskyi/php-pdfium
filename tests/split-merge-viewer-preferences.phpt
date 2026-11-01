--TEST--
extractPages() keeps its document's viewer preferences and merge() the first document's
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
    $pdf = $doc->toString();
    $keys = array_filter(['Direction', 'HideToolbar', 'PageLayout', 'PageMode', 'ViewerPreferences'],
        fn (string $key): bool => str_contains($pdf, "/$key"));
    return $keys ? implode(' ', $keys) : 'none';
}
echo 'source: ', viewer_keys($doc), "\n";
$plain = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
echo 'extractPages: ', viewer_keys($doc->extractPages([0])), "\n";
echo 'merge, first has them: ', viewer_keys(Pdfium\Document::merge([$doc, $plain])), "\n";
echo 'merge, first has none: ', viewer_keys(Pdfium\Document::merge([$plain, $doc])), "\n";
?>
--EXPECT--
source: Direction HideToolbar PageLayout PageMode ViewerPreferences
extractPages: Direction HideToolbar ViewerPreferences
merge, first has them: Direction HideToolbar ViewerPreferences
merge, first has none: none
