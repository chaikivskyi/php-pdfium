--TEST--
A broken attachment list (a /Kids loop, a node listing itself twice, 40 levels of nesting, or /EmbeddedFiles of the wrong type) neither crashes nor hangs any attachment method
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$deep = [];
for ($i = 0; $i < 40; $i++) {
    $deep[] = '<< /Kids [' . (7 + $i) . ' 0 R] >>';
}
$deep[] = '<< /Names [(a) 47 0 R] >>';
$deep[] = file_spec('a', 48);
$deep[] = stream_obj('a');
$cases = [
    'loop' => nav_pdf('/Names << /EmbeddedFiles 6 0 R >>', [
        '<< /Kids [6 0 R 7 0 R] >>',
        '<< /Names [(a) 8 0 R] >>',
        file_spec('a', 9),
        stream_obj('a'),
    ]),
    'self twice' => nav_pdf('/Names << /EmbeddedFiles 6 0 R >>', [
        '<< /Kids [6 0 R 6 0 R 7 0 R] >>',
        '<< /Names [(a) 8 0 R] >>',
        file_spec('a', 9),
        stream_obj('a'),
    ]),
    'deep' => nav_pdf('/Names << /EmbeddedFiles 6 0 R >>', $deep),
    'wrong type' => nav_pdf('/Names << /EmbeddedFiles 5 >>'),
];
$try = function (callable $f): string {
    try {
        $r = $f();
        return is_object($r) ? get_class($r) : var_export($r, true);
    } catch (Throwable $e) {
        return get_class($e) . ': ' . $e->getMessage();
    }
};
foreach ($cases as $name => $pdf) {
    $doc = Pdfium\Document::fromString($pdf);
    echo "$name\n";
    echo '  count: ', $try(fn () => $doc->attachmentCount()), "\n";
    echo '  list: ', $try(fn () => count($doc->attachments())), "\n";
    echo '  get: ', $try(fn () => $doc->attachment(0)->contents()), "\n";
    echo '  add: ', $try(fn () => $doc->addAttachment('z', 'z')->contents()), "\n";
    echo '  delete: ', $try(fn () => $doc->deleteAttachment(0)), "\n";
    echo '  count: ', $try(fn () => $doc->attachmentCount()), "\n";
}
?>
--EXPECT--
loop
  count: 1
  list: 1
  get: 'a'
  add: 'z'
  delete: NULL
  count: 1
self twice
  count: 1
  list: 1
  get: 'a'
  add: 'z'
  delete: NULL
  count: 1
deep
  count: 0
  list: 0
  get: ValueError: Pdfium\Document::attachment(): Argument #1 ($index) must be an attachment index, but the document has no attachments
  add: Pdfium\PdfiumException: Cannot add the attachment: the name may already be used, or the document's attachment list cannot take it
  delete: ValueError: Pdfium\Document::deleteAttachment(): Argument #1 ($index) must be an attachment index, but the document has no attachments
  count: 0
wrong type
  count: 0
  list: 0
  get: ValueError: Pdfium\Document::attachment(): Argument #1 ($index) must be an attachment index, but the document has no attachments
  add: 'z'
  delete: NULL
  count: 0
