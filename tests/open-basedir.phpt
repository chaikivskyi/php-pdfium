--TEST--
fromFile() and save*() honour open_basedir without warnings
--EXTENSIONS--
pdfium
--INI--
open_basedir={PWD}/fixtures
--FILE--
<?php
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf')->pageCount());
foreach (['/etc/hostname', '/etc', '/nosuch'] as $path) {
    try { Pdfium\Document::fromFile($path); } catch (Pdfium\FileException $e) { echo strtok($e->getPrevious()->getMessage(), '.'), "\n"; }
}
$img = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf')->page(0)->render(dpi: 1);
foreach (['/tmp/pdfium-basedir.png', 'nosuchwrapper://x.png'] as $path) {
    try { $img->savePng($path); } catch (Pdfium\FileException $e) { echo $e->getMessage(), "\n"; }
}
?>
--EXPECT--
int(3)
Pdfium\Document::fromFile(): open_basedir restriction in effect
Pdfium\Document::fromFile(): open_basedir restriction in effect
Pdfium\Document::fromFile(): open_basedir restriction in effect
Cannot write /tmp/pdfium-basedir.png
Cannot write nosuchwrapper://x.png
