--TEST--
open() reads the whole file through PHP streams: wrappers apply, directories fail, and memory_limit is honoured
--EXTENSIONS--
pdfium
--INI--
memory_limit=32M
--FILE--
<?php
class FixtureStream {
    public $context;
    private $data;
    private $pos = 0;
    public function stream_open($path, $mode, $options, &$opened) {
        $this->data = file_get_contents(__DIR__ . '/fixtures/' . parse_url($path, PHP_URL_HOST));
        return true;
    }
    public function stream_read($n) { $r = substr($this->data, $this->pos, $n); $this->pos += strlen($r); return $r; }
    public function stream_eof() { return $this->pos >= strlen($this->data); }
    public function stream_stat() { return ['size' => strlen($this->data)]; }
}
class CloseThrows extends FixtureStream {
    public function stream_open($path, $mode, $options, &$opened) {
        return parent::stream_open('fixture://tiny.pdf', $mode, $options, $opened);
    }
    public function stream_close() { throw new RuntimeException('close failed'); }
}
class StopsEarly extends CloseThrows {
    public function stream_close() {}
    public function stream_read($n) { return ''; }
    public function stream_eof() { return false; }
}
class Minimal {
    public $context;
    private $data;
    public function stream_open($path, $mode, $options, &$opened) { $this->data = file_get_contents(__DIR__ . '/fixtures/tiny.pdf'); return true; }
    public function stream_read($n) { $r = substr($this->data, 0, $n); $this->data = substr($this->data, strlen($r)); return $r; }
    public function stream_eof() { return $this->data === ''; }
}
stream_wrapper_register('minimal', Minimal::class);
set_error_handler(function ($no, $msg) { throw new ErrorException($msg); });
var_dump(Pdfium\Document::open('minimal://x')->pageCount());
restore_error_handler();
class ContextSeen extends Minimal {
    public function stream_open($path, $mode, $options, &$opened) {
        echo "$mode sees context: ", stream_context_get_options($this->context)['ctx']['mark'] ?? 'none', "\n";
        return parent::stream_open($path, $mode, $options, $opened);
    }
    public function stream_write($d) { return strlen($d); }
}
stream_wrapper_register('ctx', ContextSeen::class);
stream_context_set_default(['ctx' => ['mark' => 'default']]);
Pdfium\Document::open('ctx://x')->save('ctx://y');
stream_wrapper_register('fixture', FixtureStream::class);
stream_wrapper_register('closethrows', CloseThrows::class);
stream_wrapper_register('stopsearly', StopsEarly::class);
foreach (['closethrows://x', 'stopsearly://x'] as $url) {
    try { Pdfium\Document::open($url); echo "opened\n"; }
    catch (Pdfium\FileException $e) { echo get_class($e), ' <- ', $e->getPrevious() ? get_class($e->getPrevious()) : 'none', "\n"; }
}
var_dump(Pdfium\Document::open('fixture://text-3pages.pdf?' . str_repeat('a', 5000))->pageCount() === 3);
var_dump(Pdfium\Document::open('fixture://text-3pages.pdf')->pageCount());
var_dump(Pdfium\Document::open('file://' . __DIR__ . '/fixtures/tiny.pdf')->pageCount());
ini_set('memory_limit', '-1');
try { Pdfium\Document::open('/dev/zero'); } catch (Pdfium\FileException $e) { echo get_class($e), ' <- ', $e->getPrevious()->getMessage(), "\n"; }
ini_set('memory_limit', '32M');
try { Pdfium\Document::open(__DIR__ . '/fixtures'); } catch (Pdfium\FileException $e) { echo get_class($e), ' ', $e->getCode(), "\n"; }
$big = sys_get_temp_dir() . '/pdfium-open-big-' . getmypid() . '.pdf';
$fh = fopen($big, 'wb');
fwrite($fh, file_get_contents(__DIR__ . '/fixtures/tiny.pdf'));
for ($i = 0; $i < 40; $i++) { fwrite($fh, str_repeat("%\n", 512 << 10)); }
fclose($fh);
try { Pdfium\Document::open($big); } catch (Pdfium\MemoryException $e) { echo get_class($e), "\n"; }
unlink($big);
?>
--EXPECTF--
int(1)
rb sees context: default
wb sees context: default
Pdfium\FileException <- RuntimeException
Pdfium\FileException <- Pdfium\FileException
bool(true)
int(3)
int(1)
Pdfium\FileException <- %s(/dev/zero): Failed to open stream: Not a regular file
Pdfium\FileException 2
Pdfium\MemoryException
