--TEST--
A joined hyphen reads as "-" and a character mapped to no text is left out, in text(), textIn(), chars() and search(); search() finds what text() shows; text() and textIn() leave a U+0002 character out; a character outside the Basic Multilingual Plane before a joined hyphen does not shift it
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$pdfs = [
    lines_pdf('some hyphen-', 'ated'),
    cmap_pdf('BT /F2 12 Tf 72 700 Td (aAb) Tj ET', '<41> <00410000>'),
    lines_pdf('a\002b hyphen-', 'ated'),
];
foreach ($pdfs as $i => $pdf) {
    $page = Pdfium\Document::fromString($pdf)->page(0)->textPage();
    echo json_encode([$page->text(), $page->textIn(new Pdfium\Rect(0, 0, 612, 792))]), "\n";
    echo json_encode(array_map(fn($c) => $c->text, $page->chars())), "\n";
    foreach ($page->search(['n-a', 'Ab', 'n-a'][$i]) as $m) {
        var_dump($m->index, $m->length, $m->text);
    }
    var_dump(count($page->search("n\u{FFFE}a")));
}
$show = fn($matches) => json_encode(array_map(fn($m) => [$m->index, $m->length, $m->text], $matches));
$page = Pdfium\Document::fromString(lines_pdf('some hyphen-', 'ated', 'more exam-', 'ple a-b Hyphen-ated - -', '-'))->page(0)->textPage();
echo json_encode($page->text()), "\n";
foreach ([
    ['hyphen-ated'], ['HYPHEN-ATED', true], ['hyphen-ated', false, true], ['hyphen-at', false, true], ['hyphenated'],
    ['some hyphen-ated more exam-ple a-b'], ['-'], ['- -'], ['- -', false, false, true], ['hyphen -ated'], ['hyphen- ated'],
] as $args) {
    echo $show($page->search(...$args)), "\n";
}
$page = Pdfium\Document::fromString(cmap_pdf('BT /F2 12 Tf 72 700 Td (aAb xAAb) Tj ET', '<41> <00410000>'))->page(0)->textPage();
echo $show($page->search('aab')), $show($page->search('aab', true)), $show($page->search('xAAb', true)), "\n";
$page = Pdfium\Document::fromString(cmap_pdf('BT /F2 12 Tf 72 700 Td (xA yz bA) Tj ET', '<41> <00410000>'))->page(0)->textPage();
echo json_encode($page->text()), "\n";
foreach (['Ay', 'xAyz', 'xA ', 'A yz', 'a  y', 'bA '] as $needle) {
    echo $show($page->search($needle)), "\n";
}
$page = Pdfium\Document::fromString(cmap_pdf('BT /F2 12 Tf 72 700 Td (A some hyphen-) Tj ET BT /F2 12 Tf 72 600 Td (ated A) Tj ET', '<41> <D83DDE00>'))->page(0)->textPage();
echo json_encode([$page->text(), $page->textIn(new Pdfium\Rect(0, 0, 612, 792))]), $show($page->search('hyphen-ated')),
    $show($page->search("\u{1F600} SOME")), "\n";
?>
--EXPECT--
["some hyphen-ated","some hyphen-ated"]
["s","o","m","e"," ","h","y","p","h","e","n","-","a","t","e","d"]
int(10)
int(3)
string(3) "n-a"
int(0)
["aAb","aAb"]
["a","A","","b"]
int(1)
int(3)
string(2) "Ab"
int(0)
["ab hyphen-ated","ab hyphen-ated"]
["a","\u0002","b"," ","h","y","p","h","e","n","-","a","t","e","d"]
int(9)
int(3)
string(3) "n-a"
int(0)
"some hyphen-ated\r\nmore exam-ple a-b Hyphen-ated - -\r\n-"
[[5,11,"hyphen-ated"],[36,11,"Hyphen-ated"]]
[]
[[5,11,"hyphen-ated"],[36,11,"Hyphen-ated"]]
[]
[]
[[0,35,"some hyphen-ated\r\nmore exam-ple a-b"]]
[[11,1,"-"],[27,1,"-"],[33,1,"-"],[42,1,"-"],[48,1,"-"],[50,1,"-"],[53,1,"-"]]
[[48,3,"- -"]]
[[48,3,"- -"],[50,4,"-\r\n-"]]
[]
[]
[[0,4,"aAb"],[6,5,"AAb"]][][[5,6,"xAAb"]]
"xA yz bA"
[]
[]
[[0,4,"xA "]]
[[1,5,"A yz"]]
[[1,4,"A y"]]
[]
["\ud83d\ude00 some hyphen-ated \ud83d\ude00","\ud83d\ude00 some hyphen-ated \ud83d\ude00"][[8,11,"hyphen-ated"]][[0,7,"\ud83d\ude00 some"]]
