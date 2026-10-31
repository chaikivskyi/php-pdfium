--TEST--
A local path that only looks like a user wrapper URL is still saved as a local file and replaced atomically
--EXTENSIONS--
pdfium
--FILE--
<?php
class Quiet { public $context; public function stream_open($p, $m, $o, &$x) { echo "wrapper called\n"; return false; } }
stream_wrapper_register('quiet', Quiet::class);
$dir = sys_get_temp_dir() . '/pdfium-colon-' . getmypid();
mkdir("$dir/quiet:", 0777, true);
$path = "$dir/quiet://x.pdf";
copy(__DIR__ . '/fixtures/tiny.pdf', "$dir/quiet:/x.pdf");
$inode = fileinode("$dir/quiet:/x.pdf");
Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf')->save($path);
clearstatcache();
var_dump(fileinode("$dir/quiet:/x.pdf") !== $inode, Pdfium\Document::open("$dir/quiet:/x.pdf")->pageCount());
unlink("$dir/quiet:/x.pdf");
rmdir("$dir/quiet:");
rmdir($dir);
?>
--EXPECT--
bool(true)
int(3)
