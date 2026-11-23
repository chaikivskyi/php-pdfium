--TEST--
No save of this extension overwrites in place a file that a lazy document is reading: not its own, not a document made from it, not another document, not a bitmap; once the lazy document is freed, the overwrite is allowed
--EXTENSIONS--
pdfium
--FILE--
<?php
$dir = sys_get_temp_dir() . '/pdfium-lazy-guard-' . getmypid();
mkdir($dir);
$src = file_get_contents(__DIR__ . '/fixtures/text-3pages.pdf');
file_put_contents("$dir/a.pdf", $src);
link("$dir/a.pdf", "$dir/a-link.pdf");
$lazy = Pdfium\Document::fromFile("$dir/a.pdf", lazy: true);
$text = $lazy->page(2)->text();
$other = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
foreach ([
    'itself' => fn () => $lazy->save("$dir/a.pdf"),
    'extracted' => fn () => $lazy->extractPages([0])->save("$dir/a.pdf"),
    'merged' => fn () => Pdfium\Document::merge([$lazy, $other])->save("$dir/a.pdf"),
    'another document' => fn () => $other->save("$dir/a.pdf"),
    'bitmap' => fn () => $other->page(0)->render(dpi: 10)->savePng("$dir/a.pdf"),
    'through the link' => fn () => $other->save("$dir/a-link.pdf"),
] as $what => $fn) {
    try {
        $fn();
        echo "$what: written\n";
    } catch (Pdfium\FileException $e) {
        echo "$what: refused, ", substr($e->getPrevious()->getMessage(), strrpos($e->getPrevious()->getMessage(), ': ') + 2), "\n";
    }
}
var_dump(file_get_contents("$dir/a.pdf") === $src, $lazy->page(2)->text() === $text);
unset($lazy);
$other->save("$dir/a.pdf");
var_dump(Pdfium\Document::fromFile("$dir/a-link.pdf")->pageCount(), fileinode("$dir/a.pdf") === fileinode("$dir/a-link.pdf"));
array_map('unlink', glob("$dir/*"));
rmdir($dir);
?>
--CLEAN--
<?php
foreach (glob(sys_get_temp_dir() . '/pdfium-lazy-guard-*', GLOB_ONLYDIR) as $dir) {
    array_map('unlink', glob("$dir/*"));
    rmdir($dir);
}
?>
--EXPECT--
itself: refused, A lazily opened document reads from this file, and it can only be overwritten in place
extracted: refused, A lazily opened document reads from this file, and it can only be overwritten in place
merged: refused, A lazily opened document reads from this file, and it can only be overwritten in place
another document: refused, A lazily opened document reads from this file, and it can only be overwritten in place
bitmap: refused, A lazily opened document reads from this file, and it can only be overwritten in place
through the link: refused, A lazily opened document reads from this file, and it can only be overwritten in place
bool(true)
bool(true)
int(1)
bool(true)
