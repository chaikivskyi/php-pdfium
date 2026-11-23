--TEST--
contents and byteRange from signatures() verify with ext-openssl, and stop verifying once a signed byte changes
--EXTENSIONS--
pdfium
openssl
--FILE--
<?php
$file = file_get_contents(__DIR__ . '/fixtures/signed-cms.pdf');
$tmp = sys_get_temp_dir() . '/pdfium-signature-verify-' . getmypid();

function verify(string $pdf, string $tmp): bool
{
    [$s] = Pdfium\Document::fromString($pdf)->signatures();
    [[$from, $first], [$at, $second]] = $s->byteRange;
    var_dump(count($s->byteRange), $from, $at + $second === strlen($pdf), $pdf[$first], $pdf[$at - 1]);
    file_put_contents("$tmp.bin", substr($pdf, $from, $first) . substr($pdf, $at, $second));
    file_put_contents("$tmp.der", $s->contents);
    $ok = openssl_cms_verify("$tmp.bin", OPENSSL_CMS_DETACHED | OPENSSL_CMS_BINARY, null, [__DIR__ . '/fixtures/signer.pem'],
        null, null, null, "$tmp.der", OPENSSL_ENCODING_DER);
    while (openssl_error_string() !== false);
    return $ok;
}

var_dump(verify($file, $tmp));
var_dump(verify(str_replace('(Tested)', '(Forged)', $file), $tmp));
unlink("$tmp.bin");
unlink("$tmp.der");
?>
--EXPECT--
int(2)
int(0)
bool(true)
string(1) "<"
string(1) ">"
bool(true)
int(2)
int(0)
bool(true)
string(1) "<"
string(1) ">"
bool(false)
