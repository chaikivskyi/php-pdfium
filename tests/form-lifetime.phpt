--TEST--
A FormField keeps its page and document alive, and cannot be cloned or serialized, and equals only itself
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';
$doc = Pdfium\Document::fromString(standard_form());
$page = $doc->page(0);
$fields = $page->formFields();
$f = $fields[0];
unset($page, $doc, $fields);
gc_collect_cycles();
$junk = str_repeat("\xFF", 100000);
var_dump($f->name(), $f->value());
$g = Pdfium\Document::fromString(standard_form())->page(0)->formFields()[0];
try { clone $f; } catch (Error $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
try { serialize($f); } catch (Exception $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
try { $f->extra = 1; } catch (Error $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
var_dump($f == $g, $f == $f);
?>
--EXPECT--
string(4) "name"
string(3) "old"
Error: Trying to clone an uncloneable object of class Pdfium\FormField
Exception: Serialization of 'Pdfium\FormField' is not allowed
Error: Cannot create dynamic property Pdfium\FormField::$extra
bool(false)
bool(true)
