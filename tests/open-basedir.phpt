--TEST--
open() and save*() honour open_basedir without warnings
--EXTENSIONS--
pdfium
--INI--
open_basedir={PWD}/fixtures
--FILE--
<?php
var_dump(Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf')->pageCount());
try { Pdfium\Document::open('/etc/hostname'); } catch (Pdfium\FileException $e) { echo "FileException\n"; }
$img = Pdfium\Document::open(__DIR__ . '/fixtures/tiny.pdf')->page(0)->render(dpi: 1);
foreach (['/tmp/pdfium-basedir.png', 'nosuchwrapper://x.png'] as $path) {
    try { $img->savePng($path); } catch (Pdfium\FileException $e) { echo $e->getMessage(), "\n"; }
}
?>
--EXPECT--
int(3)
FileException
Cannot write /tmp/pdfium-basedir.png
Cannot write nosuchwrapper://x.png
