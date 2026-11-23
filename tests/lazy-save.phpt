--TEST--
A lazy document saves fully and incrementally, and can be saved over its source by replacing the file
--EXTENSIONS--
pdfium
--FILE--
<?php
$dir = sys_get_temp_dir() . '/pdfium-lazy-save-' . getmypid();
mkdir($dir);
$src = file_get_contents(__DIR__ . '/fixtures/text-3pages.pdf');
file_put_contents("$dir/a.pdf", $src);
$doc = Pdfium\Document::fromFile("$dir/a.pdf", lazy: true);
$text = $doc->page(2)->text();
$doc->addAttachment('n.txt', 'note');
var_dump(Pdfium\Document::fromString($doc->toString())->attachmentCount());
$appended = $doc->toString(incremental: true);
var_dump(str_starts_with($appended, $src), Pdfium\Document::fromString($appended)->attachment(0)->contents());

$doc->save("$dir/a.pdf");
var_dump(Pdfium\Document::fromFile("$dir/a.pdf")->attachmentCount());
var_dump($doc->page(2)->text() === $text, $doc->pageCount());

array_map('unlink', glob("$dir/*"));
rmdir($dir);
?>
--CLEAN--
<?php
foreach (glob(sys_get_temp_dir() . '/pdfium-lazy-save-*', GLOB_ONLYDIR) as $dir) {
    array_map('unlink', glob("$dir/*"));
    rmdir($dir);
}
?>
--EXPECT--
int(1)
bool(true)
string(4) "note"
int(1)
bool(true)
int(3)
