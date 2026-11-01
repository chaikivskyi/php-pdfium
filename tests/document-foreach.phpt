--TEST--
A Document is iterable with foreach, the same as Document::pages()
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf');
$seen = [];
foreach ($doc as $i => $page) $seen[] = "$i:" . $page->index();
echo implode(' ', $seen), "\n";
var_dump($doc instanceof IteratorAggregate, $doc->getIterator() instanceof Pdfium\PageIterator);
var_dump(array_keys(iterator_to_array($doc)));
foreach (Pdfium\Document::fromFile(__DIR__ . '/fixtures/empty.pdf') as $p) echo "never";
echo "done\n";
?>
--EXPECT--
0:0 1:1 2:2
bool(true)
bool(true)
array(3) {
  [0]=>
  int(0)
  [1]=>
  int(1)
  [2]=>
  int(2)
}
done
