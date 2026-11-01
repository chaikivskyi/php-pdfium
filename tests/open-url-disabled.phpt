--TEST--
fromFile() and save() through a URL wrapper disabled by allow_url_fopen say why as the previous exception
--EXTENSIONS--
pdfium
--INI--
allow_url_fopen=0
--FILE--
<?php
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
class Admin { public $context; }
stream_wrapper_register('admin', Admin::class);
stream_wrapper_unregister('data');
stream_wrapper_register('data', Admin::class);
stream_wrapper_unregister('file');
stream_wrapper_register('file', Admin::class);
foreach ([fn() => Pdfium\Document::fromFile('http://127.0.0.1:9/x.pdf'),
          fn() => $doc->save('ftp://admin:secret@127.0.0.1:9/x.pdf'),
          fn() => $doc->save('ftp://data:pw@127.0.0.1:9/x.pdf'),
          fn() => $doc->save('php://filter/write=string.rot13/resource=ftp://u:p@127.0.0.1:9/y.pdf'),
          fn() => $doc->save('ftp://127.0.0.1:9/x.pdf')] as $fn) {
    try { $fn(); } catch (Pdfium\FileException $e) { echo $e->getMessage(), ' <- ', $e->getPrevious()?->getMessage() ?? 'none', "\n"; }
}
?>
--EXPECTF--
Cannot open http://127.0.0.1:9/x.pdf <- %shttp:// wrapper is disabled in the server configuration by allow_url_fopen=0
Cannot write ftp://...@127.0.0.1:9/x.pdf <- %sftp:// wrapper is disabled in the server configuration by allow_url_fopen=0
Cannot write ftp://...@127.0.0.1:9/x.pdf <- %sftp:// wrapper is disabled in the server configuration by allow_url_fopen=0
Cannot write php://filter/write=string.rot13/resource=ftp://...@127.0.0.1:9/y.pdf <- %s
Cannot write ftp://127.0.0.1:9/x.pdf <- %sftp:// wrapper is disabled in the server configuration by allow_url_fopen=0
