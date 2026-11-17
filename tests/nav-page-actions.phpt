--TEST--
Page::openAction() and closeAction() return the page's open and close actions with their destinations
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf(pageExtra: [
    '/AA << /O << /S /URI /URI (https://example.com/open) >> /C << /S /GoTo /D [4 0 R /XYZ 72 700 2] >> >>',
    '',
    '/AA << /O << /S /JavaScript /JS (app.alert\(1\)) >> /C << /S /Launch /F (app.exe) >> >>',
]));
$p0 = $doc->page(0);
var_dump($p0->openAction()->type, $p0->openAction()->uri, $p0->openAction()->filePath, $p0->openAction()->destination);
$d = $p0->closeAction()->destination;
var_dump($p0->closeAction()->type, $d->pageIndex, $d->view, $d->params, $d->x, $d->y, $d->zoom);
var_dump($doc->page(1)->openAction(), $doc->page(1)->closeAction());
$js = $doc->page(2)->openAction();
var_dump($js->type, $js->uri, $js->filePath, $js->destination);
var_dump($doc->page(2)->closeAction()->type, $doc->page(2)->closeAction()->filePath);
var_dump(unserialize(serialize($p0->closeAction())) == $p0->closeAction());
?>
--EXPECT--
string(3) "URI"
string(24) "https://example.com/open"
NULL
NULL
string(4) "GoTo"
int(1)
string(3) "XYZ"
array(3) {
  [0]=>
  float(72)
  [1]=>
  float(700)
  [2]=>
  float(2)
}
float(72)
float(700)
float(2)
NULL
NULL
string(11) "Unsupported"
NULL
NULL
NULL
string(6) "Launch"
string(7) "app.exe"
bool(true)
