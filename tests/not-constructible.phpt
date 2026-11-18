--TEST--
Document, Page, PageIterator, Bitmap, Attachment and Annotation cannot be created except through the API; the read-only values cannot be created with new, except Rect, Quad and Point, but can be cloned and serialized
--EXTENSIONS--
pdfium
--FILE--
<?php
foreach (['Document', 'Page', 'PageIterator', 'TextPage', 'Bitmap', 'Attachment', 'Annotation', 'Metadata', 'AttachmentMetadata', 'Signature', 'Permissions', 'Color', 'Matrix', 'Char', 'TextMatch', 'WebLink', 'Bookmark', 'Link', 'Quad', 'Destination', 'Action', 'Border', 'Rect', 'Point'] as $c) {
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
TextPage final=true cloneable=false instantiable=false abstract=false
  Error
  ReflectionException
  Exception
Bitmap final=true cloneable=false instantiable=false abstract=false
  Error
  ReflectionException
  Exception
Attachment final=true cloneable=false instantiable=false abstract=false
  Error
  ReflectionException
  Exception
Annotation final=true cloneable=false instantiable=false abstract=false
  Error
  ReflectionException
  Exception
Metadata final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
AttachmentMetadata final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Signature final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Permissions final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Color final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Matrix final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Char final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
TextMatch final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
WebLink final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Bookmark final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Link final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Quad final=true cloneable=true instantiable=true abstract=false
  ArgumentCountError
  created
  created
Destination final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Action final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Border final=true cloneable=true instantiable=false abstract=false
  Error
  created
  created
Rect final=true cloneable=true instantiable=true abstract=false
  ArgumentCountError
  created
  created
Point final=true cloneable=true instantiable=true abstract=false
  ArgumentCountError
  created
  created
