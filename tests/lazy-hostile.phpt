--TEST--
A lazy document whose file is deleted keeps working; one whose file is truncated or shrunk fails cleanly
--EXTENSIONS--
pdfium
--FILE--
<?php
$dir = sys_get_temp_dir() . '/pdfium-lazy-hostile-' . getmypid();
mkdir($dir);
copy(__DIR__ . '/fixtures/text-3pages.pdf', "$dir/gone.pdf");
$gone = Pdfium\Document::fromFile("$dir/gone.pdf", lazy: true);
unlink("$dir/gone.pdf");
var_dump($gone->pageCount(), strlen($gone->page(1)->text()) > 0, strlen($gone->toString()) > 0);

foreach ([0, 100, 700, 1000] as $size) {
    copy(__DIR__ . '/fixtures/text-3pages.pdf', "$dir/cut.pdf");
    $cut = Pdfium\Document::fromFile("$dir/cut.pdf", lazy: true);
    $fh = fopen("$dir/cut.pdf", 'r+');
    ftruncate($fh, $size);
    fclose($fh);
    foreach ([
        'page' => fn () => $cut->page(2),
        'text' => fn () => $cut->page(1)->text(),
        'render' => fn () => $cut->page(0)->render(dpi: 20),
        'save' => fn () => $cut->toString(),
        'incremental' => fn () => $cut->toString(incremental: true),
        'attachments' => fn () => $cut->attachments(),
        'bookmarks' => fn () => $cut->bookmarks(),
    ] as $what => $f) {
        try {
            $f();
        } catch (Pdfium\PdfiumException | ValueError) {
        }
    }
    echo "$size done\n";
    unset($cut);
    unlink("$dir/cut.pdf");
}
rmdir($dir);
?>
--CLEAN--
<?php
foreach (glob(sys_get_temp_dir() . '/pdfium-lazy-hostile-*', GLOB_ONLYDIR) as $dir) {
    array_map('unlink', glob("$dir/*"));
    rmdir($dir);
}
?>
--EXPECT--
int(3)
bool(true)
bool(true)
0 done
100 done
700 done
1000 done
