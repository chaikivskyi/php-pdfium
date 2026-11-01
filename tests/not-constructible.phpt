--TEST--
Document, Page, PageIterator and Bitmap cannot be created except through the API; the read-only Metadata and Permissions values cannot be created with new but can be cloned and serialized
--EXTENSIONS--
pdfium
--FILE--
<?php
foreach (['Document', 'Page', 'PageIterator', 'Bitmap', 'Metadata', 'Permissions'] as $c) {
    $fq = "Pdfium\\$c";
    $r = new ReflectionClass($fq);
    echo $c, ' final=', var_export($r->isFinal(), true), ' cloneable=', var_export($r->isCloneable(), true),
        ' instantiable=', var_export($r->isInstantiable(), true), ' abstract=', var_export($r->isAbstract(), true), "\n";
    foreach ([
        fn() => new $fq(),
        fn() => $r->newInstanceWithoutConstructor(),
        fn() => unserialize(sprintf('O:%d:"%s":0:{}', strlen($fq), $fq)),
    ] as $make) {
        try { $make(); echo "  created\n"; } catch (Throwable $e) { echo '  ', get_class($e), "\n"; }
    }
}
?>
--EXPECT--
Document final=true cloneable=false instantiable=false abstract=false
  Error
  ReflectionException
  Exception
Page final=true cloneable=false instantiable=false abstract=false
  Error
  ReflectionException
  Exception
PageIterator final=true cloneable=false instantiable=false abstract=false
  Error
  ReflectionException
  Exception
Bitmap final=true cloneable=false instantiable=false abstract=false
  Error
  ReflectionException
  Exception
Metadata final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Permissions final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
