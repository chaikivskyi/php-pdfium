--TEST--
Threads that end, exit() or die of a fatal error with live documents, pages, bitmaps and iterators free them while another thread uses PDFium
--EXTENSIONS--
pdfium
--SKIPIF--
<?php require __DIR__ . '/threads.inc'; threads_skipif(); ?>
--FILE--
<?php
require __DIR__ . '/threads.inc';
$fixtures = __DIR__ . '/fixtures';

$task = static function (string $fixtures, string $how): array {
    $doc = with_retry(fn () => Pdfium\Document::open("$fixtures/text-3pages.pdf"));
    $page = with_retry(fn () => $doc->page(1));
    $bitmap = with_retry(fn () => $page->render(150));
    $it = $doc->pages();
    $current = with_retry(fn () => $it->current());
    $GLOBALS['keep'] = [
        with_retry(fn () => Pdfium\Document::open("$fixtures/form.pdf")),
        with_retry(fn () => Pdfium\Document::open("$fixtures/a4.pdf")->page(0)),
        with_retry(fn () => Pdfium\Document::open("$fixtures/form.pdf")->page(0)->render(100)),
        with_retry(fn () => $doc->pages()),
        $doc, $page, $bitmap, $it, $current,
    ];
    $form = with_retry(fn () => Pdfium\Document::open("$fixtures/form.pdf")->page(0));
    with_retry(fn () => $form->render(72));
    $GLOBALS['keep'][] = $form;
    $end = microtime(true) + 10;
    while (!isset($GLOBALS['busy']) && microtime(true) < $end) {
        with_retry(fn () => $page->render(72));
    }
    if ($how === 'exit') {
        exit();
    }
    if ($how === 'fatal') {
        ini_set('memory_limit', '64M');
        $GLOBALS['keep'][] = str_repeat('x', 128 << 20);
    }
    return [$how, $GLOBALS['busy'] ?? 0];
};

$runtimes = $futures = [];
foreach (['return', 'return', 'exit', 'fatal'] as $how) {
    $runtimes[] = $runtime = new parallel\Runtime(__DIR__ . '/threads.inc');
    $futures[] = $runtime->run($task, [$fixtures, $how]);
}

$page = with_retry(fn () => Pdfium\Document::open("$fixtures/a4.pdf")->page(0));
$renders = 0;
do {
    with_retry(fn () => $page->render(100));
    $renders++;
    $running = count(array_filter($futures, fn ($f) => !$f->done()));
} while ($running || $renders < 20);

[[$how0, $busy0], [$how1, $busy1]] = [$futures[0]->value(), $futures[1]->value()];
var_dump($how0, $how1);
echo 'overlapped: ', ($GLOBALS['busy'] ?? 0) + $busy0 + $busy1 > 0 ? 'yes' : 'no', "\n";

$stop = sys_get_temp_dir() . '/pdfium-threads-teardown-stop-' . getmypid();
@unlink($stop);
@unlink("$stop.started");
$renderer = new parallel\Runtime(__DIR__ . '/threads.inc');
$rendering = $renderer->run(static function (string $fixtures, string $stop): bool {
    $page = with_retry(fn () => Pdfium\Document::open("$fixtures/a4.pdf")->page(0));
    $end = microtime(true) + 10;
    while (!file_exists($stop) && microtime(true) < $end) {
        with_retry(fn () => $page->render(100));
        @touch("$stop.started");
    }
    return true;
}, [$fixtures, $stop]);
for ($end = microtime(true) + 10; !file_exists("$stop.started") && microtime(true) < $end; usleep(1000));
echo 'renderer started: ', file_exists("$stop.started") ? 'yes' : 'no', "\n";
foreach ($runtimes as $runtime) {
    $runtime->close();
}
unset($runtimes, $futures);
touch($stop);
var_dump($rendering->value());
$renderer->close();
unlink($stop);
unlink("$stop.started");

$bitmap = Pdfium\Document::open("$fixtures/text-3pages.pdf")->page(0)->render(72);
var_dump($bitmap->width());
echo "done\n";
?>
--EXPECTF--
%A
string(6) "return"
string(6) "return"
overlapped: yes
renderer started: yes
bool(true)
int(612)
done
