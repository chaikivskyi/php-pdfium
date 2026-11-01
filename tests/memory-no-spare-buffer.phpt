--TEST--
fromFile(), toString() and toPng() keep no spare buffer beyond their result
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
class Unsized {
    public $context;
    private $data;
    public function stream_open($path, $mode, $options, &$opened) { $this->data = file_get_contents(__DIR__ . '/fixtures/tiny.pdf'); return true; }
    public function stream_read($n) { $r = substr($this->data, 0, $n); $this->data = substr($this->data, strlen($r)); return $r; }
    public function stream_eof() { return $this->data === ''; }
}
stream_wrapper_register('unsized', Unsized::class);
$before = memory_get_usage();
$small = Pdfium\Document::fromFile('unsized://x');
var_dump(memory_get_usage() - $before < 16384);
$noise = Pdfium\Document::fromString(noise_pdf(300));
$before = memory_get_usage();
$bytes = $noise->toString();
var_dump(memory_get_usage() - $before < strlen($bytes) + 8192);
$bitmap = $noise->page(0)->render(dpi: 72);
$before = memory_get_usage();
$png = $bitmap->toPng();
var_dump(memory_get_usage() - $before < strlen($png) + 8192);
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
