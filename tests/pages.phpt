--TEST--
Document::pages() iterates lazily, restarts on rewind and handles empty documents
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf');
foreach ($doc->pages() as $i => $page) echo $i, ':', $page->index(), ' ';
echo "\n";
foreach ($doc->pages() as $page) { echo $page->index(), ' '; break; }
foreach ($doc->pages() as $page) echo $page->index(), ' ';
echo "\n";
$it = $doc->pages();
foreach ($it as $p) {}
foreach ($it as $p) echo $p->index(), ' ';
echo "\n";
foreach (Pdfium\Document::open(__DIR__ . '/fixtures/empty.pdf')->pages() as $p) echo "never";
echo "done\n";
?>
--EXPECT--
0:0 1:1 2:2 
0 0 1 2 
0 1 2 
done
