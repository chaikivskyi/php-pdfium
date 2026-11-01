--TEST--
open failures throw the matching exception
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
foreach ([
    'missing'      => fn() => Pdfium\Document::fromFile($f . 'nope.pdf'),
    'not a pdf'    => fn() => Pdfium\Document::fromFile($f . 'not-a-pdf.txt'),
    'truncated'    => fn() => Pdfium\Document::fromString(file_get_contents($f . 'truncated.pdf')),
    'no password'  => fn() => Pdfium\Document::fromFile($f . 'encrypted-user.pdf'),
    'bad password' => fn() => Pdfium\Document::fromFile($f . 'encrypted-user.pdf', password: 'wrong'),
    'empty bytes'  => fn() => Pdfium\Document::fromString(''),
    'empty path'   => fn() => Pdfium\Document::fromFile(''),
    'unsupported security (open)'   => fn() => Pdfium\Document::fromFile($f . 'unsupported-security.pdf'),
    'unsupported security (string)' => fn() => Pdfium\Document::fromString(file_get_contents($f . 'unsupported-security.pdf')),
] as $name => $fn) {
    try { $fn(); echo "$name: opened\n"; } catch (Throwable $e) { echo "$name: ", get_class($e), "\n"; }
}
?>
--EXPECT--
missing: Pdfium\FileException
not a pdf: Pdfium\FormatException
truncated: Pdfium\FormatException
no password: Pdfium\PasswordException
bad password: Pdfium\PasswordException
empty bytes: ValueError
empty path: ValueError
unsupported security (open): Pdfium\UnsupportedException
unsupported security (string): Pdfium\UnsupportedException
