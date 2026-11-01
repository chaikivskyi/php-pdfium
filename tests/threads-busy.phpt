--TEST--
While another thread is inside PDFium, a method that needs it throws BusyException and does nothing; Bitmap methods, an iterator's already loaded page and releasing objects never do
--EXTENSIONS--
pdfium
--SKIPIF--
<?php require __DIR__ . '/threads.inc'; threads_skipif(); ?>
--FILE--
<?php
require __DIR__ . '/threads.inc';
$stop = sys_get_temp_dir() . '/pdfium-threads-busy-stop-' . getmypid();
$target = sys_get_temp_dir() . '/pdfium-threads-busy-' . getmypid() . '.pdf';
@unlink($stop);
@unlink($target);

$doc = Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf');
$bitmap = $doc->page(0)->render(dpi: 72);
$it = $doc->pages();
$page = $it->current();

$runtime = new parallel\Runtime(__DIR__ . '/threads.inc');
$future = $runtime->run(static function (string $fixture, string $stop): int {
    $page = with_retry(fn () => Pdfium\Document::open($fixture)->page(0));
    $renders = 0;
    $end = microtime(true) + 10;
    while (!file_exists($stop) && microtime(true) < $end) {
        with_retry(fn () => $page->render(200));
        $renders++;
    }
    return $renders;
}, [__DIR__ . '/fixtures/a4.pdf', $stop]);

$busy = $saveBusy = false;
$noFileAfterSaveBusy = $bitmapNeverBusy = $releaseNeverBusy = $currentNeverBusy = true;
$end = microtime(true) + 10;
while ((!$busy || !$saveBusy) && microtime(true) < $end) {
    try {
        $it->next();
        unset($page);
        $it->rewind();
    } catch (Pdfium\BusyException $e) {
        $releaseNeverBusy = false;
    }
    $page = with_retry(fn () => $it->current());
    try {
        $bitmap->width();
        $bitmap->toPng();
        $bitmap->toJpeg();
    } catch (Pdfium\BusyException $e) {
        $bitmapNeverBusy = false;
    }
    usleep(random_int(0, 5000));
    try {
        $it->current();
    } catch (Pdfium\BusyException $e) {
        $currentNeverBusy = false;
    }
    try {
        $doc->pageCount();
    } catch (Pdfium\BusyException $e) {
        $busy = $busy || $e->getMessage() === 'PDFium is in use by another thread';
    }
    try {
        $doc->save($target);
        unlink($target);
    } catch (Pdfium\BusyException $e) {
        $saveBusy = true;
        $noFileAfterSaveBusy = $noFileAfterSaveBusy && file_exists($target) === false;
    }
}

touch($stop);
$renders = $future->value();
unlink($stop);
var_dump($renders > 0);
echo 'busy: ', $busy ? 'yes' : 'no', "\n";
echo 'save busy left no file: ', $saveBusy && $noFileAfterSaveBusy ? 'yes' : 'no', "\n";
echo 'bitmap never busy: ', $bitmapNeverBusy ? 'yes' : 'no', "\n";
echo 'release never busy: ', $releaseNeverBusy ? 'yes' : 'no', "\n";
echo 'loaded current never busy: ', $currentNeverBusy ? 'yes' : 'no', "\n";
?>
--EXPECT--
bool(true)
busy: yes
save busy left no file: yes
bitmap never busy: yes
release never busy: yes
loaded current never busy: yes
