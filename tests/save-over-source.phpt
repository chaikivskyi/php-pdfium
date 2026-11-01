--TEST--
Saving over the file a document was opened from replaces it without disturbing the open document
--EXTENSIONS--
pdfium
--FILE--
<?php
$dir = sys_get_temp_dir() . '/pdfium-save-over-' . getmypid();
mkdir($dir);
$path = "$dir/doc.pdf";
copy(__DIR__ . '/fixtures/text-3pages.pdf', $path);
chmod($path, 0640);
symlink($path, "$dir/link.pdf");
$doc = Pdfium\Document::fromFile($path);
$doc->extractPages([0])->save("$dir/link.pdf");
var_dump($doc->page(2)->text(), Pdfium\Document::fromFile($path)->pageCount());
clearstatcache();
var_dump(is_link("$dir/link.pdf"), decoct(fileperms($path) & 0777), scandir($dir));
link($path, "$dir/hard.pdf");
$linked = Pdfium\Document::fromFile($path);
$doc->save($path);
var_dump($linked->page(0)->text(), Pdfium\Document::fromFile("$dir/hard.pdf")->pageCount());
unlink("$dir/hard.pdf");
try { $doc->save("$dir/missing/doc.pdf"); } catch (Pdfium\FileException $e) { echo "FileException\n"; }
var_dump(Pdfium\Document::fromFile($path)->pageCount(), scandir($dir));
unlink("$dir/link.pdf");
unlink($path);
rmdir($dir);
?>
--EXPECT--
string(12) "Page 3 gamma"
int(1)
bool(true)
string(3) "640"
array(4) {
  [0]=>
  string(1) "."
  [1]=>
  string(2) ".."
  [2]=>
  string(7) "doc.pdf"
  [3]=>
  string(8) "link.pdf"
}
string(12) "Page 1 alpha"
int(3)
FileException
int(3)
array(4) {
  [0]=>
  string(1) "."
  [1]=>
  string(2) ".."
  [2]=>
  string(7) "doc.pdf"
  [3]=>
  string(8) "link.pdf"
}
