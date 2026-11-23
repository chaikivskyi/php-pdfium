--TEST--
A string value, an appearance and a file set on annotations of an encrypted document are encrypted when it is saved with encryption kept, and read back with the password
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-user.pdf', password: 'user');
$page = $doc->page(0);
$text = $page->addAnnotation('Text', new Pdfium\Rect(10, 10, 30, 30));
$text->setStringValue('Contents', 'secret');
$stamp = $page->addAnnotation('Stamp', new Pdfium\Rect(100, 650, 300, 700));
$stamp->setAppearance('0 1 0 rg 100 650 200 50 re f % secret');
$clip = $page->addAnnotation('FileAttachment', new Pdfium\Rect(10, 40, 30, 60));
$clip->setAttachment('secret.txt', 'secret file contents', 'secret description');

$kept = $doc->toString();
$clear = $doc->toString(removeSecurity: true);
var_dump(substr_count($kept, 'secret'), substr_count($clear, 'secret') > 0);
$long = [];
for ($at = 0; ($start = strpos($clear, "stream\r\n", $at)) !== false; $at = $end + 11) {
    $start += 8;
    $end = strpos($clear, "\r\nendstream", $start);
    if ($end === false) {
        break;
    }
    if ($end - $start >= 16) {
        $long[] = substr($clear, $start, $end - $start);
    }
}
var_dump(count($long));
foreach ($long as $s) {
    if (str_contains($kept, $s)) {
        echo "a stream is stored unencrypted\n";
    }
}

try { Pdfium\Document::fromString($kept); } catch (Pdfium\PasswordException $e) { echo "password needed\n"; }
$back = Pdfium\Document::fromString($kept, password: 'user')->page(0)->annotations();
echo implode(' ', array_map(fn($a) => $a->type(), $back)), "\n";
var_dump($back[0]->stringValue('Contents'), $back[1]->appearance());
$file = $back[2]->attachment();
var_dump($file->name(), $file->contents(), $file->metadata()->description);
?>
--EXPECT--
int(0)
bool(true)
int(5)
password needed
Text Stamp FileAttachment
string(6) "secret"
string(37) "0 1 0 rg 100 650 200 50 re f % secret"
string(10) "secret.txt"
string(20) "secret file contents"
string(18) "secret description"
