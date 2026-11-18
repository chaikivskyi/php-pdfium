--TEST--
attachmentCount(), attachments() and attachment() list embedded files with their names and decoded contents, null for an external file; attachments() keys each by its index and leaves out an entry PDFium cannot read
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(attach_pdf(
    '(a.txt) 6 0 R (b.bin) 8 0 R (c.url) 10 0 R (d.txt) << /Type /Filespec /UF <FEFF00E9002E007400780074> /EF << /UF 11 0 R >> >> (e.txt) 12 0 R',
    [
        file_spec('a.txt', 7),
        stream_obj('hello'),
        file_spec('b.bin', 9),
        stream_obj(gzcompress("\0\1\2"), '/Filter /FlateDecode '),
        '<< /Type /Filespec /F (http://example.com/x) >>',
        stream_obj('direct'),
        file_spec('e.txt', 13),
        stream_obj(''),
    ],
));
var_dump($doc->attachmentCount());
$hex = fn (?string $s) => $s === null ? null : bin2hex($s);
var_dump(array_map(fn ($a) => [$a->index(), $a->name(), $hex($a->contents())], $doc->attachments()));
var_dump(bin2hex($doc->attachment(1)->contents()));
foreach ([5, -1] as $i) {
    try { $doc->attachment($i); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$gap = Pdfium\Document::fromString(attach_pdf('(a) 6 0 R (b) 99 0 R (c) 8 0 R', [
    file_spec('a', 7),
    stream_obj('a'),
    file_spec('c', 9),
    stream_obj('c'),
]));
var_dump($gap->attachmentCount(), array_map(fn ($a) => $a->index() . ':' . $a->name(), $gap->attachments()));
try { $gap->attachment(1); } catch (Pdfium\PdfiumException $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
$none = Pdfium\Document::fromString(nav_pdf());
var_dump($none->attachmentCount(), $none->attachments());
try { $none->attachment(0); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
int(5)
array(5) {
  [0]=>
  array(3) {
    [0]=>
    int(0)
    [1]=>
    string(5) "a.txt"
    [2]=>
    string(10) "68656c6c6f"
  }
  [1]=>
  array(3) {
    [0]=>
    int(1)
    [1]=>
    string(5) "b.bin"
    [2]=>
    string(6) "000102"
  }
  [2]=>
  array(3) {
    [0]=>
    int(2)
    [1]=>
    string(20) "http://example.com/x"
    [2]=>
    NULL
  }
  [3]=>
  array(3) {
    [0]=>
    int(3)
    [1]=>
    string(6) "é.txt"
    [2]=>
    string(12) "646972656374"
  }
  [4]=>
  array(3) {
    [0]=>
    int(4)
    [1]=>
    string(5) "e.txt"
    [2]=>
    string(0) ""
  }
}
string(6) "000102"
Pdfium\Document::attachment(): Argument #1 ($index) must be between 0 and 4
Pdfium\Document::attachment(): Argument #1 ($index) must be between 0 and 4
int(3)
array(2) {
  [0]=>
  string(3) "0:a"
  [2]=>
  string(3) "2:c"
}
Pdfium\FormatException: Cannot read attachment 1
int(0)
array(0) {
}
Pdfium\Document::attachment(): Argument #1 ($index) must be an attachment index, but the document has no attachments
