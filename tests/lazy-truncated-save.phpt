--TEST--
A lazy document whose file could not be read in full refuses to save or serialise, rather than writing an incomplete copy
--EXTENSIONS--
pdfium
--FILE--
<?php
$dir = sys_get_temp_dir() . '/pdfium-lazy-truncated-' . getmypid();
mkdir($dir);
foreach ([700, 1000] as $size) {
    copy(__DIR__ . '/fixtures/text-3pages.pdf', "$dir/src.pdf");
    $doc = Pdfium\Document::fromFile("$dir/src.pdf", lazy: true);
    $fh = fopen("$dir/src.pdf", 'r+');
    ftruncate($fh, $size);
    fclose($fh);
    $cut = file_get_contents("$dir/src.pdf");
    foreach ([
        'toString' => fn () => $doc->toString(),
        'toString again' => fn () => $doc->toString(),
        'incremental' => fn () => $doc->toString(incremental: true),
        'save elsewhere' => fn () => $doc->save("$dir/out.pdf"),
        'save over source' => fn () => $doc->save("$dir/src.pdf"),
    ] as $what => $fn) {
        try { $fn(); echo "$size $what: saved\n"; } catch (Pdfium\PdfiumException $e) { echo "$size $what: ", get_class($e), ': ', $e->getMessage(), "\n"; }
    }
    var_dump(file_exists("$dir/out.pdf"), file_get_contents("$dir/src.pdf") === $cut);
    unset($doc);
    unlink("$dir/src.pdf");
}
rmdir($dir);
?>
--CLEAN--
<?php
foreach (glob(sys_get_temp_dir() . '/pdfium-lazy-truncated-*', GLOB_ONLYDIR) as $dir) {
    array_map('unlink', glob("$dir/*"));
    rmdir($dir);
}
?>
--EXPECT--
700 toString: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
700 toString again: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
700 incremental: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
700 save elsewhere: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
700 save over source: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
bool(false)
bool(true)
1000 toString: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1000 toString again: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1000 incremental: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1000 save elsewhere: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1000 save over source: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
bool(false)
bool(true)
