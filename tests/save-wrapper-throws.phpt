--TEST--
A stream wrapper that throws while save*() writes: its exception is the reason, and the wrapper is not asked to unlink
--EXTENSIONS--
pdfium
--FILE--
<?php
class ThrowingWrites {
    public $context;
    public function stream_open($path, $mode, $options, &$opened) { return true; }
    public function stream_write($data) { throw new RuntimeException('disk gone'); }
    public function unlink($path) { echo "unlink $path\n"; return true; }
}
class ThrowingUnlink extends ThrowingWrites {
    public function unlink($path) { echo "unlink $path\n"; throw new LogicException('cleanup failed'); }
}
stream_wrapper_register('throwing', ThrowingWrites::class);
class ShortThenThrowingUnlink extends ThrowingUnlink {
    public function stream_write($data) { return 0; }
}
stream_wrapper_register('throwing2', ThrowingUnlink::class);
class ExistingObject extends ThrowingWrites {
    public function url_stat($path, $flags) { return ['size' => 1]; }
    public function stream_write($data) { return strlen($data); }
    public function stream_flush() { return false; }
    public function stream_close() { throw new RuntimeException('commit failed'); }
}
stream_wrapper_register('short', ShortThenThrowingUnlink::class);
stream_wrapper_register('existing', ExistingObject::class);
$doc = Pdfium\Document::open(__DIR__ . '/fixtures/tiny.pdf');
foreach ([fn() => $doc->save('throwing://a.pdf'), fn() => $doc->page(0)->render(dpi: 1)->savePng('throwing://b.png'), fn() => $doc->save('throwing2://c.pdf'), fn() => $doc->save('short://d.pdf'), fn() => $doc->save('existing://e.pdf')] as $fn) {
    try { $fn(); } catch (Pdfium\FileException $e) {
        echo get_class($e), ': ', $e->getMessage();
        for ($p = $e->getPrevious(); $p; $p = $p->getPrevious()) { echo ' <- ', get_class($p), ': ', $p->getMessage(); }
        echo "\n";
    }
}
?>
--EXPECT--
Pdfium\FileException: Cannot write throwing://a.pdf <- RuntimeException: disk gone
Pdfium\FileException: Cannot write throwing://b.png <- RuntimeException: disk gone
Pdfium\FileException: Cannot write throwing2://c.pdf <- RuntimeException: disk gone
Pdfium\FileException: Cannot write short://d.pdf
Pdfium\FileException: Cannot write existing://e.pdf <- RuntimeException: commit failed
