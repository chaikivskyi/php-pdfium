--TEST--
A fatal error inside a stream wrapper during save() is still shown and leaves error_reporting as it was, and error_get_last() survives a save
--EXTENSIONS--
pdfium
--INI--
display_errors=1
error_reporting=E_ALL
memory_limit=32M
--FILE--
<?php
class Fatal {
    public $context;
    public function stream_open($p, $m, $o, &$x) { return true; }
    public function stream_write($d) { return strlen($d); }
    public function stream_close() {}
    public function url_stat($p, $f) { return false; }
}
class Dies extends Fatal {
    public function stream_write($d) { register_shutdown_function(function () { echo "\nshutdown: error_reporting=", error_reporting() === E_ALL ? 'E_ALL' : error_reporting(), ", last error is the fatal: ", str_contains(error_get_last()['message'] ?? '', 'Allowed memory size') ? 'yes' : 'no', "\n"; }); $x = str_repeat('x', 64 << 20); }
}
stream_wrapper_register('fine', Fatal::class);
stream_wrapper_register('dies', Dies::class);
$doc = Pdfium\Document::open(__DIR__ . '/fixtures/tiny.pdf');
@trigger_error('before', E_USER_NOTICE);
$doc->save('fine://x.pdf');
echo error_get_last()['message'], "\n";
$doc->save('dies://x.pdf');
?>
--EXPECTF--
before

Fatal error: Allowed memory size of %d bytes exhausted%s(tried to allocate %d bytes) in %s on line %d

shutdown: error_reporting=E_ALL, last error is the fatal: yes
