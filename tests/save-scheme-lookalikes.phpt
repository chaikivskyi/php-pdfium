--TEST--
User wrapper names inside other URLs are not mistaken for wrappers, and an unknown wrapper says so
--EXTENSIONS--
pdfium
--FILE--
<?php
$dir = sys_get_temp_dir() . '/pdfium-lookalike-' . getmypid();
mkdir("$dir/out:", 0777, true);
chdir($dir);
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf');
try { $doc->save('out://report.pdf'); } catch (Pdfium\FileException $e) { echo $e->getPrevious()->getMessage(), "\n"; }

class Quiet { public $context; public function stream_open($p, $m, $o, &$x) { echo "wrapper called\n"; return false; } }
stream_wrapper_register('quiet', Quiet::class);
try { $doc->save("compress.zlib://$dir/missing/quiet://x.pdf.gz"); }
catch (Pdfium\FileException $e) { echo 'reason given: ', $e->getPrevious() ? 'yes' : 'no', "\n"; }

try { $doc->save('php://filter/write=string.rot13/resource=' . $dir . '/missing/bob@example.com.pdf'); }
catch (Pdfium\FileException $e) { echo str_replace($dir, 'DIR', $e->getMessage()), "\n"; }
symlink('php://stdout', "$dir/stdout.pdf");
try { $doc->save('stdout.pdf'); echo "saved\n"; } catch (Pdfium\FileException $e) { echo "dangling link to php://stdout: FileException\n"; }
unlink("$dir/stdout.pdf");
rmdir("$dir/out:");
chdir('/');
rmdir($dir);
?>
--EXPECTF--
%s: Unable to find the wrapper "out" - did you forget to enable it when you configured PHP?
reason given: yes
Cannot write php://filter/write=string.rot13/resource=DIR/missing/bob@example.com.pdf
dangling link to php://stdout: FileException
