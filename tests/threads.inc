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
