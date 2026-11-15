--TEST--
TextPage::search() folds case and finds whole words beyond ASCII: a final sigma matches a capital one, and the characters of scripts written without spaces do not bound a word; a letter outside the Basic Multilingual Plane bounds a word and an emoji does not
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$show = fn($matches) => json_encode(array_map(fn($m) => [$m->index, $m->length, $m->text], $matches), JSON_UNESCAPED_UNICODE);
$page = Pdfium\Document::fromString(cmap_pdf('BT /F2 12 Tf 72 700 Td (ABC ABCAD EF HIH J9 x_y) Tj ET', '<41> <043A>', '<42> <0456>',
    '<43> <0442>', '<44> <0430>', '<45> <03C3>', '<46> <03C2>', '<48> <4E2D>', '<49> <6587>', '<4A> <00E9>'))->page(0)->textPage();
echo $page->text(), "\n";
foreach ([
    ['кіт'], ['кіт', false, true], ['КІТ', false, true], ['КІТ', true], ['ΣΣ'], ['ΣΣ', true], ['文', false, true],
    ['中文', false, true], ['é', false, true], ['9', false, true], ['é9', false, true], ['x', false, true],
] as $args) {
    echo $show($page->search(...$args)), "\n";
}
$page = Pdfium\Document::fromString(cmap_pdf('BT /F2 12 Tf 72 700 Td (fooA Afoo fooB Bfoo A) Tj ET', '<41> <D835DC00>', '<42> <D83DDE00>'))->page(0)->textPage();
echo $page->text(), "\n", $show($page->search('foo', false, true)), $show($page->search("\u{1D400}", false, true)), "\n";
?>
--EXPECT--
кіт кітка σς 中文中 é9 x_y
[[0,3,"кіт"],[4,3,"кіт"]]
[[0,3,"кіт"]]
[[0,3,"кіт"]]
[]
[[10,2,"σς"]]
[]
[[14,1,"文"]]
[[13,2,"中文"]]
[]
[]
[[17,2,"é9"]]
[[20,1,"x"]]
foo𝐀 𝐀foo foo😀 😀foo 𝐀
[[12,3,"foo"],[20,3,"foo"]][[24,2,"𝐀"]]
