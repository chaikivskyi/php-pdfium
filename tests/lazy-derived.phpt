--TEST--
extractPages() and merge() refuse a lazy source whose file could not be read in full, with FormatException whether or not PDFium could copy the pages, rather than build an incomplete document; an intact lazy source works
--EXTENSIONS--
pdfium
--FILE--
<?php
$dir = sys_get_temp_dir() . '/pdfium-lazy-derived-' . getmypid();
mkdir($dir);
$fixture = __DIR__ . '/fixtures/text-3pages.pdf';
$whole = Pdfium\Document::fromFile($fixture);

copy($fixture, "$dir/ok.pdf");
$ok = Pdfium\Document::fromFile("$dir/ok.pdf", lazy: true);
$extracted = Pdfium\Document::fromString($ok->extractPages([2, 0])->toString());
var_dump($extracted->page(0)->text() === $whole->page(2)->text(), Pdfium\Document::merge([$whole, $ok])->pageCount());

foreach ([500, 1000, 1100, 1300] as $size) {
    copy($fixture, "$dir/cut.pdf");
    $lazy = Pdfium\Document::fromFile("$dir/cut.pdf", lazy: true);
    $fh = fopen("$dir/cut.pdf", 'r+');
    ftruncate($fh, $size);
    fclose($fh);
    foreach ([
        'extractPages' => fn () => $lazy->extractPages([0, 1, 2]),
        'merge alone' => fn () => Pdfium\Document::merge([$lazy]),
        'merge after another' => fn () => Pdfium\Document::merge([$whole, $lazy]),
    ] as $what => $fn) {
        try { $fn(); echo "$size $what: built\n"; } catch (Pdfium\PdfiumException $e) { echo "$size $what: ", get_class($e), ': ', $e->getMessage(), "\n"; }
    }
    unset($lazy);
    unlink("$dir/cut.pdf");
}
unset($ok);
unlink("$dir/ok.pdf");
rmdir($dir);
?>
--CLEAN--
<?php
foreach (glob(sys_get_temp_dir() . '/pdfium-lazy-derived-*', GLOB_ONLYDIR) as $dir) {
    array_map('unlink', glob("$dir/*"));
    rmdir($dir);
}
?>
--EXPECT--
bool(true)
int(6)
500 extractPages: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
500 merge alone: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
500 merge after another: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1000 extractPages: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1000 merge alone: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1000 merge after another: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1100 extractPages: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1100 merge alone: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1100 merge after another: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1300 extractPages: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1300 merge alone: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1300 merge after another: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
