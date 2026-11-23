--TEST--
fromFile(lazy: true) reads the same document as a whole-file load, fails the same way, and refuses stream wrappers without opening them
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
foreach (['text-3pages.pdf', 'unicode.pdf', 'form.pdf', 'rotated.pdf'] as $name) {
    $whole = Pdfium\Document::fromFile($f . $name);
    $lazy = Pdfium\Document::fromFile($f . $name, lazy: true);
    var_dump($lazy->pageCount() === $whole->pageCount()
        && $lazy->page(0)->text() === $whole->page(0)->text()
        && $lazy->metadata() == $whole->metadata()
        && $lazy->page(0)->render(dpi: 30)->toPng() === $whole->page(0)->render(dpi: 30)->toPng());
}
var_dump(Pdfium\Document::fromFile($f . 'encrypted-user.pdf', 'user', lazy: true)->pageCount());

class W
{
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened): bool
    {
        echo "wrapper opened\n";
        return false;
    }
}
stream_wrapper_register('wrap', W::class);

foreach ([
    fn () => Pdfium\Document::fromFile($f . 'encrypted-user.pdf', lazy: true),
    fn () => Pdfium\Document::fromFile($f . 'encrypted-user.pdf', 'wrong', true),
    fn () => Pdfium\Document::fromFile($f . 'not-a-pdf.txt', lazy: true),
    fn () => Pdfium\Document::fromFile($f . 'nope.pdf', lazy: true),
    fn () => Pdfium\Document::fromFile($f, lazy: true),
    fn () => Pdfium\Document::fromFile('', lazy: true),
    fn () => Pdfium\Document::fromFile($f . 'unsupported-security.pdf', lazy: true),
    fn () => Pdfium\Document::fromFile('data://application/pdf;base64,' . base64_encode(file_get_contents($f . 'tiny.pdf')), lazy: true),
    fn () => Pdfium\Document::fromFile('php://memory', lazy: true),
    fn () => Pdfium\Document::fromFile('wrap://x', lazy: true),
] as $fn) {
    try { $fn(); echo "opened\n"; } catch (Throwable $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
}
var_dump(Pdfium\Document::fromFile('file://' . $f . 'tiny.pdf', lazy: true)->pageCount());
?>
--EXPECTF--
bool(true)
bool(true)
bool(true)
bool(true)
int(3)
Pdfium\PasswordException: Cannot open document: password required or incorrect
Pdfium\PasswordException: Cannot open document: password required or incorrect
Pdfium\FormatException: Cannot open document: not a PDF or corrupted
Pdfium\FileException: Cannot open %snope.pdf
Pdfium\FileException: Cannot open %sfixtures/
ValueError: Pdfium\Document::fromFile(): Argument #1 ($path) must not be empty
Pdfium\UnsupportedException: Cannot open document: unsupported security scheme
Pdfium\UnsupportedException: Lazy loading needs a local file; data:... is read through a stream wrapper
Pdfium\UnsupportedException: Lazy loading needs a local file; php://memory is read through a stream wrapper
Pdfium\UnsupportedException: Lazy loading needs a local file; wrap://x is read through a stream wrapper
int(1)
