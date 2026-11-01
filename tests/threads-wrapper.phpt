--TEST--
A stream wrapper written in PHP runs without the PDFium guard held, so another thread can use PDFium while open() reads and save() writes through it
--EXTENSIONS--
pdfium
--SKIPIF--
<?php require __DIR__ . '/threads.inc'; threads_skipif(); ?>
--FILE--
<?php
require __DIR__ . '/threads.inc';

function page_count_in_another_thread(): int
{
    $runtime = new parallel\Runtime(__DIR__ . '/threads.inc');
    $count = $runtime->run(static fn (string $path): int => Pdfium\Document::open($path)->pageCount(),
        [__DIR__ . '/fixtures/a4.pdf'])->value();
    $runtime->close();
    return $count;
}

class ProbingWrapper
{
    public $context;
    private string $data = '';
    private int $at = 0;
    private bool $probed = false;

    public function stream_open($path, $mode, $options, &$opened): bool
    {
        if ($mode[0] === 'r') {
            $this->data = file_get_contents(__DIR__ . '/fixtures/tiny.pdf');
        }
        return true;
    }

    public function stream_read(int $count): string
    {
        $this->probe('read');
        $chunk = substr($this->data, $this->at, $count);
        $this->at += strlen($chunk);
        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->at >= strlen($this->data);
    }

    public function stream_write(string $data): int
    {
        $this->probe('write');
        return strlen($data);
    }

    private function probe(string $during): void
    {
        if (!$this->probed) {
            $this->probed = true;
            echo "$during: ";
            var_dump(page_count_in_another_thread());
        }
    }
}
stream_wrapper_register('probe', ProbingWrapper::class);

$doc = Pdfium\Document::open('probe://in.pdf');
$doc->save('probe://out.pdf');
?>
--EXPECT--
read: int(1)
write: int(1)
