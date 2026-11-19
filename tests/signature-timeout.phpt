--TEST--
signatures() stops soon after max_execution_time runs out on a document with thousands of signature fields
--EXTENSIONS--
pdfium
--SKIPIF--
<?php if (getenv('USE_ZEND_ALLOC') === '0') die('skip timing differs without the Zend allocator'); ?>
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(sig_pdf(str_repeat('<< /FT /Sig >> ', 15000)));
$start = hrtime(true);
register_shutdown_function(function () use ($start) {
    echo (hrtime(true) - $start) / 1e9 < 6 ? "stopped early\n" : "ran to the end\n";
});
set_time_limit(1);
$doc->signatures();
echo "not reached\n";
?>
--EXPECTF--
Fatal error: Maximum execution time of 1 second exceeded in %s on line %d
stopped early
