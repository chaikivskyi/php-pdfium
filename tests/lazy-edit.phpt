--TEST--
nUp() and insertPages() refuse a lazy document whose file could not be read in full, as source or as target, with FormatException, and insertPages() leaves the target and its held pages as they were; with an intact file the page edits work and an incremental save appends to the original; documents from create() and nUp() are saved whole
--EXTENSIONS--
pdfium
--FILE--
<?php
$dir = sys_get_temp_dir() . '/pdfium-lazy-edit-' . getmypid();
mkdir($dir);
$fixture = __DIR__ . '/fixtures/text-3pages.pdf';
$whole = Pdfium\Document::fromFile($fixture);

function cut(string $fixture, string $path, int $size): Pdfium\Document
{
    copy($fixture, $path);
    $lazy = Pdfium\Document::fromFile($path, lazy: true);
    $fh = fopen($path, 'r+');
    ftruncate($fh, $size);
    fclose($fh);
    return $lazy;
}

foreach ([500, 1100] as $size) {
    $target = Pdfium\Document::fromFile($fixture);
    $held = $target->page(1);
    foreach ([
        'nUp' => fn ($lazy) => $lazy->nUp(612, 792, 2, 1),
        'nUp one per sheet' => fn ($lazy) => $lazy->nUp(612, 792, 1, 1),
        'insertPages from it' => fn ($lazy) => $target->insertPages($lazy, [2], 0),
        'insertPages from it into a new document' => fn ($lazy) => Pdfium\Document::create()->insertPages($lazy),
        'insertPages within it' => fn ($lazy) => $lazy->insertPages($lazy, [0]),
        'insertPages into it' => fn ($lazy) => $lazy->insertPages($whole, [0]),
    ] as $what => $fn) {
        $lazy = cut($fixture, "$dir/cut.pdf", $size);
        try { $fn($lazy); echo "$size $what: done\n"; } catch (Pdfium\PdfiumException $e) { echo "$size $what: ", get_class($e), ': ', $e->getMessage(), "\n"; }
        unset($lazy);
        unlink("$dir/cut.pdf");
    }
    var_dump($target->pageCount(), $held->index(), trim($held->text()) === trim($whole->page(1)->text()));
}

copy($fixture, "$dir/ok.pdf");
$original = file_get_contents("$dir/ok.pdf");
$ok = Pdfium\Document::fromFile("$dir/ok.pdf", lazy: true);
$sheets = $ok->nUp(612, 792, 2, 1);
$ok->insertPages($whole, [0], 3);
$ok->movePages([3], 0);
$ok->deletePage(1);
$appended = $ok->toString(incremental: true);
$reread = Pdfium\Document::fromString($appended);
var_dump($sheets->pageCount(), str_starts_with($appended, substr($original, strpos($original, '%PDF'))), $reread->pageCount(),
    trim($reread->page(0)->text()) === trim($whole->page(0)->text()));

foreach (['create' => function () { $d = Pdfium\Document::create(); $d->addPage(100, 100); return $d; }, 'nUp' => fn () => $whole->nUp(612, 792, 2, 1)] as $what => $make) {
    $built = $make()->toString(incremental: true);
    echo "$what: ", substr($built, 0, 8), ' ', substr_count($built, 'startxref'), "\n";
}
unset($ok);
unlink("$dir/ok.pdf");
rmdir($dir);
?>
--CLEAN--
<?php
foreach (glob(sys_get_temp_dir() . '/pdfium-lazy-edit-*', GLOB_ONLYDIR) as $dir) {
    array_map('unlink', glob("$dir/*"));
    rmdir($dir);
}
?>
--EXPECT--
500 nUp: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
500 nUp one per sheet: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
500 insertPages from it: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
500 insertPages from it into a new document: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
500 insertPages within it: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened: the document's page order is now undefined; load the document again
500 insertPages into it: done
int(3)
int(1)
bool(true)
1100 nUp: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1100 nUp one per sheet: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1100 insertPages from it: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1100 insertPages from it into a new document: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened
1100 insertPages within it: Pdfium\FormatException: The document's file could not be read in full: a read failed, or the file changed or was truncated after the document was opened: the document's page order is now undefined; load the document again
1100 insertPages into it: done
int(3)
int(1)
bool(true)
int(2)
bool(true)
int(3)
bool(true)
create: %PDF-1.7 1
nUp: %PDF-1.7 1
