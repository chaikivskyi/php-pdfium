--TEST--
save() writes in place when replacing the file would change it: hard links, read-only files and directories, dangling symlinks, long names
--EXTENSIONS--
pdfium
--SKIPIF--
<?php
$f = tempnam(sys_get_temp_dir(), 'pdfium');
chmod($f, 0444);
$root = is_writable($f);
unlink($f);
if ($root) die('skip running as root');
?>
--FILE--
<?php
$dir = sys_get_temp_dir() . '/pdfium-fallbacks-' . getmypid();
mkdir($dir);
$doc = Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf');
$count = fn($p) => Pdfium\Document::open($p)->pageCount();

copy(__DIR__ . '/fixtures/tiny.pdf', "$dir/a.pdf");
link("$dir/a.pdf", "$dir/b.pdf");
$doc->save("$dir/a.pdf");
echo 'hard link: ', $count("$dir/b.pdf"), ' pages, ', fileinode("$dir/a.pdf") === fileinode("$dir/b.pdf") ? 'same inode' : 'split', "\n";

copy(__DIR__ . '/fixtures/tiny.pdf', "$dir/ro.pdf");
chmod("$dir/ro.pdf", 0444);
try { $doc->save("$dir/ro.pdf"); } catch (Pdfium\FileException $e) { echo 'read-only: ', str_replace($dir, 'DIR', $e->getPrevious()->getMessage()), "\n"; }
echo 'read-only file still has ', $count("$dir/ro.pdf"), " page\n";

symlink("$dir/target.pdf", "$dir/dangling.pdf");
$doc->save("$dir/dangling.pdf");
clearstatcache();
echo 'dangling symlink: ', is_link("$dir/dangling.pdf") ? 'still a link' : 'replaced', ', target has ', $count("$dir/target.pdf"), " pages\n";

$long = "$dir/" . str_repeat('a', 250) . '.pdf';
$doc->save($long);
echo 'long name: ', $count($long), " pages\n";

mkdir("$dir/locked");
copy(__DIR__ . '/fixtures/tiny.pdf', "$dir/locked/c.pdf");
chmod("$dir/locked", 0555);
$doc->save("$dir/locked/c.pdf");
echo 'read-only directory: ', $count("$dir/locked/c.pdf"), " pages, entries ", count(scandir("$dir/locked")), "\n";

chmod("$dir/locked", 0755);
array_map('unlink', array_merge(glob("$dir/locked/*"), glob("$dir/*.pdf")));
rmdir("$dir/locked");
var_dump(scandir($dir));
rmdir($dir);
?>
--EXPECT--
hard link: 3 pages, same inode
read-only: Pdfium\Document::save(DIR/ro.pdf): Failed to open stream: Permission denied
read-only file still has 1 page
dangling symlink: still a link, target has 3 pages
long name: 3 pages
read-only directory: 3 pages, entries 3
array(2) {
  [0]=>
  string(1) "."
  [1]=>
  string(2) ".."
}
