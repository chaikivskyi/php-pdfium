--TEST--
While another thread is inside PDFium, a method that needs it throws BusyException and does nothing, addAttachment() and the page edits included; Bitmap methods, an iterator's already loaded page and releasing objects never do
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

$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf');
$bitmap = $doc->page(0)->render(dpi: 72);
$it = $doc->pages();
$page = $it->current();
$adoc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
$held = $adoc->addAttachment('held', 'h');
$efixture = __DIR__ . '/fixtures/text-3pages.pdf';
$edoc = Pdfium\Document::fromFile($efixture);
$epage = $edoc->page(2);
$eorder = static fn (Pdfium\Document $doc): string => with_retry(fn () => implode('|', array_map(
    fn (int $i) => trim($doc->page($i)->text()), range(0, $doc->pageCount() - 1))));
$order = $eorder($edoc);
$ecrop = $epage->cropBox();
$editBusy = ['deletePage' => false, 'movePages' => false, 'addPage' => false, 'insertPages' => false, 'nUp' => false,
    'create' => false, 'setRotation' => false, 'setCropBox' => false, 'cropBox' => false];
$editBusyChangedNothing = true;

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

$busyMessage = $saveBusy = $addBusy = $addBusyChangedNothing = false;
$noFileAfterSaveBusy = $bitmapNeverBusy = $releaseNeverBusy = $currentNeverBusy = $typeErrorNeverBusy = true;
$end = microtime(true) + 10;
while ((!$busyMessage || !$saveBusy || !$addBusy || in_array(false, $editBusy, true)) && microtime(true) < $end) {
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
    foreach ($editBusy as $edit => $seen) {
        if ($seen) {
            continue;
        }
        try {
            match ($edit) {
                'deletePage' => $edoc->deletePage(0),
                'movePages' => $edoc->movePages([0], 1),
                'addPage' => $edoc->addPage(1, 1),
                'insertPages' => $edoc->insertPages($edoc, [0]),
                'nUp' => $edoc->nUp(100, 100, 1, 1),
                'create' => Pdfium\Document::create(),
                'setRotation' => $epage->setRotation(90),
                'setCropBox' => $epage->setCropBox(new Pdfium\Rect(0, 0, 10, 10)),
                'cropBox' => $epage->cropBox(),
            };
            $edoc = with_retry(fn () => Pdfium\Document::fromFile($efixture));
            $epage = with_retry(fn () => $edoc->page(2));
        } catch (Pdfium\BusyException $e) {
            $editBusy[$edit] = true;
            $editBusyChangedNothing = $editBusyChangedNothing && with_retry(fn () => $edoc->pageCount()) === 3
                && $eorder($edoc) === $order && $epage->index() === 2 && with_retry(fn () => $epage->rotation()) === 0
                && with_retry(fn () => $epage->cropBox()) == $ecrop;
        }
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
echo 'busy edits changed nothing: ', !in_array(false, $editBusy, true) && $editBusyChangedNothing ? 'yes' : 'no', "\n";
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
busy edits changed nothing: yes
bitmap never busy: yes
release never busy: yes
loaded current never busy: yes
type error never busy: yes
