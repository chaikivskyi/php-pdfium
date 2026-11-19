--TEST--
While another thread is inside PDFium, a method that needs it throws BusyException and does nothing, addAttachment(), signatures() and signatureCount() included; Bitmap methods, an iterator's already loaded page and releasing objects never do
--EXTENSIONS--
pdfium
--SKIPIF--
<?php require __DIR__ . '/threads.inc'; threads_skipif(); ?>
--FILE--
<?php
require __DIR__ . '/threads.inc';
require __DIR__ . '/pdf.inc';
$stop = sys_get_temp_dir() . '/pdfium-threads-busy-stop-' . getmypid();
$target = sys_get_temp_dir() . '/pdfium-threads-busy-' . getmypid() . '.pdf';
@unlink($stop);
@unlink($target);

$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf');
$bitmap = $doc->page(0)->render(dpi: 72);
$it = $doc->pages();
$page = $it->current();
$adoc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
$held = $adoc->addAttachment('held', 'h');
$sdoc = Pdfium\Document::fromString(sig_pdf(sig_field('<< /Contents <01> >>')));

$runtime = new parallel\Runtime(__DIR__ . '/threads.inc');
$future = $runtime->run(static function (string $fixture, string $stop): int {
    $page = with_retry(fn () => Pdfium\Document::fromFile($fixture)->page(0));
    $renders = 0;
    $end = microtime(true) + 10;
    while (!file_exists($stop) && microtime(true) < $end) {
        with_retry(fn () => $page->render(200));
        $renders++;
    }
    return $renders;
}, [__DIR__ . '/fixtures/a4.pdf', $stop]);

$busyMessage = $saveBusy = $addBusy = $addBusyChangedNothing = $sigBusy = $sigCountBusy = false;
$noFileAfterSaveBusy = $bitmapNeverBusy = $releaseNeverBusy = $currentNeverBusy = $typeErrorNeverBusy = true;
$end = microtime(true) + 10;
while ((!$busyMessage || !$saveBusy || !$addBusy || !$sigBusy || !$sigCountBusy) && microtime(true) < $end) {
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
        $doc->extractPages(['x']);
    } catch (TypeError $e) {
    } catch (Pdfium\BusyException $e) {
        $typeErrorNeverBusy = false;
    }
    try {
        $doc->pageCount();
    } catch (Pdfium\BusyException $e) {
        $busyMessage = $busyMessage || $e->getMessage() === 'PDFium is in use by another thread';
    }
    if (!$addBusy) {
        try {
            $added = $adoc->addAttachment('busy', 'x');
            with_retry(fn () => $adoc->deleteAttachment($added->index()));
            $held = with_retry(fn () => $adoc->attachment(0));
        } catch (Pdfium\BusyException $e) {
            $addBusy = true;
            $addBusyChangedNothing = with_retry(fn () => $adoc->attachmentCount()) === 1
                && with_retry(fn () => $held->name()) === 'held';
        }
    }
    try {
        $sdoc->signatures();
    } catch (Pdfium\BusyException $e) {
        $sigBusy = true;
    }
    try {
        $sdoc->signatureCount();
    } catch (Pdfium\BusyException $e) {
        $sigCountBusy = true;
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
echo 'busy: ', $busyMessage ? 'yes' : 'no', "\n";
echo 'save busy left no file: ', $saveBusy && $noFileAfterSaveBusy ? 'yes' : 'no', "\n";
echo 'busy add changed nothing: ', $addBusy && $addBusyChangedNothing ? 'yes' : 'no', "\n";
echo 'signatures busy, then listed: ', $sigBusy && with_retry(fn () => $sdoc->signatures())[0]->contents === "\x01" ? 'yes' : 'no', "\n";
echo 'signature count busy, then counted: ', $sigCountBusy && with_retry(fn () => $sdoc->signatureCount()) === 1 ? 'yes' : 'no', "\n";
echo 'bitmap never busy: ', $bitmapNeverBusy ? 'yes' : 'no', "\n";
echo 'release never busy: ', $releaseNeverBusy ? 'yes' : 'no', "\n";
echo 'loaded current never busy: ', $currentNeverBusy ? 'yes' : 'no', "\n";
echo 'type error never busy: ', $typeErrorNeverBusy ? 'yes' : 'no', "\n";
?>
--EXPECT--
bool(true)
busy: yes
save busy left no file: yes
busy add changed nothing: yes
signatures busy, then listed: yes
signature count busy, then counted: yes
bitmap never busy: yes
release never busy: yes
loaded current never busy: yes
type error never busy: yes
