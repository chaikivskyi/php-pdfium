--TEST--
A stream wrapper written in PHP runs as it would under @fopen(): its warnings reach the error handler and do not become exceptions
--EXTENSIONS--
pdfium
--FILE--
<?php
class NoisyWrites {
    public $context;
    public function stream_open($path, $mode, $options, &$opened) { return true; }
    public function stream_write($data) {
        echo 'error_reporting inside: ', error_reporting() === (E_ALL & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE)) ? 'as under @' : error_reporting(), "\n";
        trigger_error('noisy', E_USER_WARNING);
        return strlen($data);
    }
}
stream_wrapper_register('noisy', NoisyWrites::class);
error_reporting(E_ALL);
set_error_handler(function ($no, $msg) { echo "handled: $msg\n"; return true; });
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
$doc->save('noisy://x.pdf');
echo "saved\n";
$doc->page(0)->render(dpi: 1)->savePng('noisy://x.png');
echo "saved\n";
$doc->save('php://filter/write=string.rot13/resource=noisy://y.pdf');
echo "saved through a filter\n";
stream_wrapper_unregister('data');
stream_wrapper_register('data', NoisyWrites::class);
$doc->save('php://filter/write=string.rot13/resource=data:,x');
echo "saved through a filter to data:\n";
stream_wrapper_restore('data');
stream_wrapper_unregister('file');
stream_wrapper_register('file', NoisyWrites::class);
$doc->save('php://filter/write=string.rot13/resource=/tmp/pdfium-file-override.pdf');
stream_wrapper_restore('file');
echo "saved through an overridden file wrapper\n";
restore_error_handler();
class Quiet { public $context; public function stream_open($p, $m, $o, &$x) { return true; } public function stream_write($d) { return strlen($d); } }
stream_wrapper_register('quiet', Quiet::class);
$doc->save('quiet://' . str_repeat('a', 5000) . '.pdf');
echo "saved long URL\n";
?>
--EXPECT--
error_reporting inside: as under @
handled: noisy
saved
error_reporting inside: as under @
handled: noisy
saved
error_reporting inside: as under @
handled: noisy
saved through a filter
error_reporting inside: as under @
handled: noisy
saved through a filter to data:
error_reporting inside: as under @
handled: noisy
saved through an overridden file wrapper
saved long URL
