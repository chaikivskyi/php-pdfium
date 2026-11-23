<?php

function threads_skipif(): void
{
    if (!PHP_ZTS) {
        die('skip needs ZTS PHP');
    }
    if (!extension_loaded('parallel')) {
        die('skip parallel not loaded');
    }
}

function with_retry(callable $fn): mixed
{
    $end = microtime(true) + 30;
    for (;;) {
        try {
            return $fn();
        } catch (Pdfium\BusyException $e) {
            if (microtime(true) > $end) {
                throw $e;
            }
            $GLOBALS['busy'] = ($GLOBALS['busy'] ?? 0) + 1;
            usleep(50);
        }
    }
}

/** @return array{parallel\Runtime, parallel\Future, string} */
function renderer_start(): array
{
    $stop = sys_get_temp_dir() . '/pdfium-renderer-stop-' . getmypid();
    @unlink($stop);
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
    return [$runtime, $future, $stop];
}

/** @param array{parallel\Runtime, parallel\Future, string} $renderer */
function renderer_stop(array $renderer): int
{
    [, $future, $stop] = $renderer;
    touch($stop);
    $renders = $future->value();
    unlink($stop);
    return $renders;
}

function stress_workload(string $dir, int $seed): array
{
    $busyBefore = $GLOBALS['busy'] ?? 0;
    $names = ['text-3pages.pdf', 'rotated.pdf', 'unicode.pdf', 'form.pdf', 'a4.pdf'];
    $dpi = 72 + 24 * ($seed % 4);
    $open = static function (string $bytes, string $path, bool $fromString): Pdfium\Document {
        return with_retry(fn () => $fromString ? Pdfium\Document::fromString($bytes) : Pdfium\Document::fromFile($path));
    };
    $results = [];
    $tmp = sys_get_temp_dir() . '/pdfium-stress-' . getmypid() . '-' . $seed . '.pdf';
    foreach ($names as $k => $name) {
        $path = "$dir/$name";
        $bytes = file_get_contents($path);
        $doc = $open($bytes, $path, $seed % 2 === 1);
        $count = with_retry(fn () => $doc->pageCount());
        $results["$name count"] = $count;
        for ($i = 0; $i < $count; $i++) {
            $page = with_retry(fn () => $doc->page($i));
            $results["$name $i png"] = md5(with_retry(fn () => $page->render($dpi))->toPng());
            $results["$name $i text"] = md5(with_retry(fn () => $page->text()));
            unset($page);
        }
        $one = with_retry(fn () => $doc->extractPages([0]));
        $copy = $open(with_retry(fn () => $one->toString()), '', true);
        $results["$name extract"] = with_retry(fn () => $copy->pageCount());
        unset($one, $copy);
        $merged = with_retry(fn () => Pdfium\Document::merge([$doc, $doc]));
        $results["$name merge"] = with_retry(fn () => $merged->pageCount());
        unset($merged);
        with_retry(fn () => $doc->save($tmp));
        $saved = $open('', $tmp, false);
        $results["$name save"] = with_retry(fn () => $saved->pageCount());
        unset($saved, $doc);
        unlink($tmp);
    }
    return ['results' => $results, 'busy' => ($GLOBALS['busy'] ?? 0) - $busyBefore];
}
