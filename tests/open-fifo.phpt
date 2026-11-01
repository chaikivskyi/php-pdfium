--TEST--
fromFile() refuses a FIFO without blocking on it
--EXTENSIONS--
pdfium
--SKIPIF--
<?php if (!function_exists('posix_mkfifo')) die('skip posix_mkfifo is not available'); ?>
--FILE--
<?php
$fifo = sys_get_temp_dir() . '/pdfium-fifo-' . getmypid();
posix_mkfifo($fifo, 0600);
try { Pdfium\Document::fromFile($fifo); } catch (Pdfium\FileException $e) { echo $e->getPrevious()->getMessage(), "\n"; }
unlink($fifo);
?>
--EXPECTF--
Pdfium\Document::fromFile(%spdfium-fifo-%d): Failed to open stream: Not a regular file
