--TEST--
Document::insertPages() refuses another encrypted source unless removeSecurity is set; the encrypted document keeps its encryption
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$f = __DIR__ . '/fixtures/';
$enc = Pdfium\Document::fromFile($f . 'encrypted-user.pdf', password: 'user');
$doc = Pdfium\Document::fromString(letters_pdf(2));
$encCount = $enc->pageCount();
try { $doc->insertPages($enc); } catch (Pdfium\UnsupportedException $e) { echo $e->getMessage(), "\n"; }
var_dump($doc->pageCount());
$doc->insertPages($enc, removeSecurity: true);
var_dump($doc->pageCount() === 2 + $encCount);
$enc->insertPages($enc);
var_dump($enc->pageCount() === 2 * $encCount);
$enc->insertPages($doc);
$total = $enc->pageCount();
var_dump($total === 2 * $encCount + 2 + $encCount);
$kept = $enc->toString();
try { Pdfium\Document::fromString($kept); echo "opened\n"; } catch (Pdfium\PasswordException $e) { echo "PasswordException\n"; }
var_dump(Pdfium\Document::fromString($kept, password: 'user')->pageCount() === $total);
?>
--EXPECT--
The source document is encrypted and its pages leave its encryption; pass removeSecurity: true to insert them
int(2)
bool(true)
bool(true)
bool(true)
PasswordException
bool(true)
