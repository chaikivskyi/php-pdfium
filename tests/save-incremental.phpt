--TEST--
incremental: true appends to the original bytes; with removeSecurity or on an extracted or merged document PDFium writes a full copy instead; leaving the options out is the same as passing their defaults, apart from the file identifier a full save renews; bytes before %PDF are dropped
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$sameBut = fn (string $s) => preg_replace('~/ID\s*\[\s*<[0-9A-Fa-f]*>\s*<[0-9A-Fa-f]*>\s*\]~', '/ID[]', $s);
$src = file_get_contents($f . 'tiny.pdf');
$doc = Pdfium\Document::fromString($src);
$before = array_map($sameBut, [$doc->toString(), $doc->toString(true)]);

$doc->addAttachment('one.txt', 'first');
$inc = $doc->toString(incremental: true);
var_dump(str_starts_with($inc, $src), strlen($inc) > strlen($src), substr_count($inc, '%%EOF'));
var_dump(Pdfium\Document::fromString($inc)->attachment(0)->contents());
var_dump(str_starts_with($doc->toString(), $src));

$doc->addAttachment('two.txt', 'second');
$inc2 = $doc->toString(incremental: true);
var_dump(str_starts_with($inc2, $src), Pdfium\Document::fromString($inc2)->attachmentCount());

$path = sys_get_temp_dir() . '/pdfium-save-incremental-' . getmypid() . '.pdf';
copy($f . 'tiny.pdf', $path);
$onDisk = Pdfium\Document::fromFile($path);
$onDisk->addAttachment('a.txt', 'on disk');
$onDisk->save($path, incremental: true);
var_dump(str_starts_with(file_get_contents($path), $src));
var_dump(Pdfium\Document::fromFile($path)->attachment(0)->contents());
unlink($path);

$encSrc = file_get_contents($f . 'encrypted-user.pdf');
$enc = Pdfium\Document::fromString($encSrc, 'user');
$enc->addAttachment('s.txt', 'secret');
$encInc = $enc->toString(incremental: true);
var_dump(str_starts_with($encInc, $encSrc));
try { Pdfium\Document::fromString($encInc); echo "opened\n"; } catch (Pdfium\PasswordException $e) { echo "PasswordException\n"; }
var_dump(Pdfium\Document::fromString($encInc, 'user')->attachment(0)->contents());

$open = $enc->toString(removeSecurity: true, incremental: true);
var_dump(str_starts_with($open, $encSrc), Pdfium\Document::fromString($open)->attachmentCount());

$three = Pdfium\Document::fromFile($f . 'text-3pages.pdf');
foreach ([$three->extractPages([0]), Pdfium\Document::merge([$three, $three])] as $made) {
    var_dump($sameBut($made->toString(incremental: true)) === $sameBut($made->toString()));
}

$fresh = Pdfium\Document::fromString($src);
var_dump(array_map($sameBut, [$fresh->toString(false, false, null), $fresh->toString(true, false, null)]) === $before);
$plain = $fresh->toString();
var_dump($sameBut($plain) !== $plain);
var_dump(strlen($fresh->toString(incremental: true)) > strlen($src));
var_dump((bool) preg_match('/(^|\s)1 0 obj/', substr($fresh->toString(incremental: true), strlen($src))));

$xsSrc = file_get_contents($f . 'xref-stream.pdf');
$xs = Pdfium\Document::fromString($xsSrc);
$xs->addAttachment('x.txt', 'xref');
$xsInc = $xs->toString(incremental: true);
var_dump(str_starts_with($xsInc, $xsSrc), (bool) preg_match('~/Type\s*/XRef~', substr($xsInc, strlen($xsSrc))));
var_dump(Pdfium\Document::fromString($xsInc)->attachment(0)->contents());
var_dump($xs->toString(incremental: true) === $xsInc);

$led = Pdfium\Document::fromString("junk\n" . $src);
$led->addAttachment('p.txt', 'prefixed');
$ledInc = $led->toString(incremental: true);
var_dump(str_starts_with($ledInc, "junk"), str_starts_with($ledInc, $src), substr_count($ledInc, '%%EOF'));
var_dump(Pdfium\Document::fromString($ledInc)->attachment(0)->contents());
?>
--EXPECT--
bool(true)
bool(true)
int(2)
string(5) "first"
bool(false)
bool(true)
int(2)
bool(true)
string(7) "on disk"
bool(true)
PasswordException
string(6) "secret"
bool(false)
int(1)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
string(4) "xref"
bool(false)
bool(false)
bool(true)
int(2)
string(8) "prefixed"
