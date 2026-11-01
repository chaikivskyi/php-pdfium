--TEST--
A failed save through a stream wrapper written in PHP throws FileException and leaves cleanup to the wrapper; a successful one keeps the file
--EXTENSIONS--
pdfium
--FILE--
<?php
class FailingWrites {
    public $context;
    public function stream_open($path, $mode, $options, &$opened) { return true; }
    public function stream_write($data) { return 0; }
    public function unlink($path) { echo "unlink $path\n"; return true; }
}
class FailingWritesNoUnlink {
    public $context;
    public function stream_open($path, $mode, $options, &$opened) { return true; }
    public function stream_write($data) { return 0; }
}
class Writes extends FailingWrites {
    public function stream_write($data) { return strlen($data); }
}
stream_wrapper_register('failing', FailingWrites::class);
stream_wrapper_register('nounlink', FailingWritesNoUnlink::class);
stream_wrapper_register('writes', Writes::class);
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
$img = $doc->page(0)->render(dpi: 1);
foreach ([fn() => $doc->save('failing://a.pdf'), fn() => $img->savePng('failing://b.png'),
          fn() => $doc->save('nounlink://c.pdf'), fn() => $doc->save('writes://d.pdf')] as $fn) {
    try { $fn(); echo "saved\n"; } catch (Throwable $e) { echo get_class($e), "\n"; }
}
?>
--EXPECT--
Pdfium\FileException
Pdfium\FileException
Pdfium\FileException
saved
