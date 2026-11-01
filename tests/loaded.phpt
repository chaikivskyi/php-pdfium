--TEST--
pdfium loads and registers its exception hierarchy
--EXTENSIONS--
pdfium
--FILE--
<?php
var_dump(is_subclass_of(Pdfium\Exception::class, RuntimeException::class));
foreach (['File', 'Format', 'Password', 'Unsupported', 'Memory', 'Busy'] as $c) {
    var_dump(is_subclass_of("Pdfium\\{$c}Exception", Pdfium\Exception::class));
}
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
