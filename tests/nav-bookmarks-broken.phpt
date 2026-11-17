--TEST--
Document::bookmarks() lists each entry of a looping outline once and stops at 100 levels
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
function titles(array $bookmarks): string
{
    return implode(',', array_map(fn($b) => $b->title . ($b->children ? '[' . titles($b->children) . ']' : ''), $bookmarks));
}
echo titles(Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', [
    '<< /Type /Outlines /First 7 0 R /Last 8 0 R >>',
    '<< /Title (A) /Parent 6 0 R /Next 8 0 R >>',
    '<< /Title (B) /Parent 6 0 R /Next 7 0 R >>',
]))->bookmarks()), "\n";
echo titles(Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', [
    '<< /Type /Outlines /First 7 0 R /Last 7 0 R >>',
    '<< /Title (A) /Parent 6 0 R /First 8 0 R /Last 8 0 R >>',
    '<< /Title (B) /Parent 7 0 R /First 7 0 R /Last 7 0 R >>',
]))->bookmarks()), "\n";

$chain = ['<< /Type /Outlines /First 7 0 R /Last 7 0 R >>'];
for ($i = 1; $i <= 150; $i++) {
    $chain[] = "<< /Title (L$i) /Parent " . ($i + 5) . ' 0 R' . ($i < 150 ? ' /First ' . ($i + 7) . ' 0 R /Last ' . ($i + 7) . ' 0 R' : '') . ' >>';
}
$b = Pdfium\Document::fromString(nav_pdf('/Outlines 6 0 R', $chain))->bookmarks();
$deepest = $b[0];
while ($deepest->children) {
    $deepest = $deepest->children[0];
}
echo $deepest->title, "\n";
var_dump(strlen(serialize($b)) > 0);
var_dump($deepest);
unset($b, $deepest);
echo "done\n";
?>
--EXPECTF--
A,B
A[B]
L100
bool(true)
object(Pdfium\Bookmark)#%d (8) {
  ["title"]=>
  string(4) "L100"
  ["open"]=>
  bool(false)
  ["color"]=>
  NULL
  ["italic"]=>
  bool(false)
  ["bold"]=>
  bool(false)
  ["destination"]=>
  NULL
  ["action"]=>
  NULL
  ["children"]=>
  array(0) {
  }
}
done
