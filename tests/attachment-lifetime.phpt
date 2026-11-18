--TEST--
An Attachment keeps its document and source bytes alive, and cannot be cloned or serialized, and equals only itself
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$bytes = attach_pdf('(a.txt) 6 0 R', [file_spec('a.txt', 7), stream_obj('hello')]);
$a = Pdfium\Document::fromString($bytes)->attachment(0);
$len = strlen($bytes);
unset($bytes);
gc_collect_cycles();
$junk = str_repeat("\xFF", $len);
var_dump($a->name(), $a->contents());
$a2 = Pdfium\Document::fromString(attach_pdf('(a.txt) 6 0 R', [file_spec('a.txt', 7), stream_obj('hello')]))->attachment(0);
try { clone $a; } catch (Error $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
try { serialize($a); } catch (Exception $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
var_dump($a == $a2, $a == $a);
?>
--EXPECT--
string(5) "a.txt"
string(5) "hello"
Error: Trying to clone an uncloneable object of class Pdfium\Attachment
Exception: Serialization of 'Pdfium\Attachment' is not allowed
bool(false)
bool(true)
