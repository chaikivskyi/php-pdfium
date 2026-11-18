--TEST--
addAttachment() embeds a file in place, sorted by name; the returned Attachment is the new file, even when an older entry shows the same name; toString() writes it out
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf());
$a = $doc->addAttachment('notes.txt', "line\n", 'My notes');
$m = $a->metadata();
var_dump($a->index(), $a->name(), $a->contents(), $m->description, $m->checksum === md5("line\n"),
    (bool) preg_match('/^D:\d{14}/', $m->creationDate));
var_dump($doc->addAttachment('b.txt', 'b')->index(), $doc->addAttachment('a.txt', 'a')->index());
var_dump(array_map(fn ($a) => $a->name(), $doc->attachments()));
$copy = Pdfium\Document::fromString($doc->toString());
var_dump(array_map(fn ($a) => [$a->name(), $a->contents()], $copy->attachments()));
var_dump($doc->addAttachment('café.txt', 'x')->name());
try { $doc->addAttachment('a.txt', 'y'); } catch (Pdfium\PdfiumException $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
var_dump($doc->attachmentCount());
var_dump($doc->addAttachment('e', '')->contents());

$doc = Pdfium\Document::fromString(attach_pdf('(aaa) 6 0 R', [file_spec('m.txt', 7), stream_obj('old')]));
$new = $doc->addAttachment('m.txt', 'new');
var_dump($new->index(), $new->contents(), array_map(fn ($a) => $a->name(), $doc->attachments()));

foreach ([['', 'x', null], ["a\0b", 'x', null], ["\xFF", 'x', null], ['ok', 'x', "\xFF"], ['ok', 'x', "a\0"]] as [$n, $c, $d]) {
    try { $doc->addAttachment($n, $c, $d); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
var_dump($doc->attachmentCount());
?>
--EXPECT--
int(0)
string(9) "notes.txt"
string(5) "line
"
string(8) "My notes"
bool(true)
bool(true)
int(0)
int(0)
array(3) {
  [0]=>
  string(5) "a.txt"
  [1]=>
  string(5) "b.txt"
  [2]=>
  string(9) "notes.txt"
}
array(3) {
  [0]=>
  array(2) {
    [0]=>
    string(5) "a.txt"
    [1]=>
    string(1) "a"
  }
  [1]=>
  array(2) {
    [0]=>
    string(5) "b.txt"
    [1]=>
    string(1) "b"
  }
  [2]=>
  array(2) {
    [0]=>
    string(9) "notes.txt"
    [1]=>
    string(5) "line
"
  }
}
string(9) "café.txt"
Pdfium\PdfiumException: Cannot add the attachment: the name may already be used, or the document's attachment list cannot take it
int(4)
string(0) ""
int(1)
string(3) "new"
array(2) {
  [0]=>
  string(5) "m.txt"
  [1]=>
  string(5) "m.txt"
}
Pdfium\Document::addAttachment(): Argument #1 ($name) must not be empty
Pdfium\Document::addAttachment(): Argument #1 ($name) must not contain any null bytes
Pdfium\Document::addAttachment(): Argument #1 ($name) must be valid UTF-8
Pdfium\Document::addAttachment(): Argument #3 ($description) must be valid UTF-8
Pdfium\Document::addAttachment(): Argument #3 ($description) must not contain any null bytes
int(2)
