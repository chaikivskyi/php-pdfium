--TEST--
TextPage::search() returns each match with its character range, text and rectangles; whitespace in the needle matches any run of spaces and line breaks; case is folded beyond ASCII
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(lines_pdf('Hello World', 'world worldly aaa'))->page(0)->textPage();
$chars = $page->chars();
foreach ($page->search('world') as $m) {
    echo $m->index, ' ', $m->length, ' ', $m->text, ' ', count($m->rects), ' ',
        implode('', array_map(fn($c) => $c->text, array_slice($chars, $m->index, $m->length))), "\n";
}
$m = $page->search('World')[0];
$r = $m->rects[0];
var_dump($r->left <= $chars[6]->box->left && $r->right >= $chars[10]->box->right
    && $r->bottom <= $chars[6]->box->bottom && $r->top >= $chars[6]->box->top);
echo count($page->search('world', matchCase: true)), ' ', count($page->search('world', wholeWord: true)), ' ',
    count($page->search('aa')), ' ', count($page->search('aa', overlapping: true)), ' ', count($page->search('nothing')), "\n";
$u = Pdfium\Document::fromFile(__DIR__ . '/fixtures/unicode.pdf')->page(0)->textPage()->search('a')[0];
echo $u->index, ' ', $u->length, ' ', $u->text, "\n";
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/rotated.pdf')->page(0)->textPage()->search('x'));
foreach (['', "\xFF", "a\0b", "a\u{1F600}"] as $needle) {
    try { $page->search($needle); echo "searched\n"; } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$show = fn($matches) => json_encode(array_map(fn($m) => [$m->index, $m->length, $m->text], $matches));
$page = Pdfium\Document::fromString(lines_pdf('b', 'c b aa a b', 'bbaaaa', 'aa bbcb'))->page(0)->textPage();
echo json_encode($page->text()), "\n";
foreach ([
    [' c b'], [' c b', false, false, true], ['b '], ['aaa aa'], ['aaa  aa'], ["aaa\naa"], ['a', false, true], ['aa a', false, true],
    ['aa', false, false, true], ['AA A', true], [' '],
] as $args) {
    echo $show($page->search(...$args)), "\n";
}
$page = Pdfium\Document::fromString(cmap_pdf('BT /F2 12 Tf 72 700 Td (A B C D) Tj ET', '<41> <00C9>', '<42> <042F>', '<43> <03A3>', '<44> <0130>'))->page(0)->textPage();
echo $page->text(), ' ', count($page->search("\u{E9} \u{44F} \u{3C3} i")), ' ', count($page->search("\u{E9}", true)), "\n";
var_dump($m);
?>
--EXPECTF--
6 5 World 1 World
13 5 world 1 world
19 5 world 1 world
bool(true)
2 2 1 2 0
3 1 A
array(0) {
}
Pdfium\TextPage::search(): Argument #1 ($needle) must not be empty
Pdfium\TextPage::search(): Argument #1 ($needle) must be valid UTF-8
Pdfium\TextPage::search(): Argument #1 ($needle) must not contain any null bytes
searched
"b\r\nc b aa a b\r\nbbaaaa\r\naa bbcb"
[[1,5,"\r\nc b"]]
[[1,5,"\r\nc b"]]
[[0,3,"b\r\n"],[5,2,"b "],[12,3,"b\r\n"]]
[[18,7,"aaa\r\naa"]]
[[18,7,"aaa\r\naa"]]
[[18,7,"aaa\r\naa"]]
[[10,1,"a"]]
[[7,4,"aa a"]]
[[7,2,"aa"],[17,2,"aa"],[18,2,"aa"],[19,2,"aa"],[23,2,"aa"]]
[]
[[1,2,"\r\n"],[4,1," "],[6,1," "],[9,1," "],[11,1," "],[13,2,"\r\n"],[21,2,"\r\n"],[25,1," "]]
É Я Σ İ 1 0
object(Pdfium\TextMatch)#%d (4) {
  ["index"]=>
  int(6)
  ["length"]=>
  int(5)
  ["text"]=>
  string(5) "World"
  ["rects"]=>
  array(1) {
    [0]=>
    object(Pdfium\Rect)#%d (4) {
      ["left"]=>
      float(%f)
      ["bottom"]=>
      float(%f)
      ["right"]=>
      float(%f)
      ["top"]=>
      float(%f)
    }
  }
}
