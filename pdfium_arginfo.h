/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: c22279e7de9197122a557d5d472423965983a9b0 */

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_Pdfium_Document___construct, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_open, 0, 1, Pdfium\\Document, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, password, IS_STRING, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_fromString, 0, 1, Pdfium\\Document, 0)
	ZEND_ARG_TYPE_INFO(0, bytes, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, password, IS_STRING, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Document_pageCount, 0, 0, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Document_count arginfo_class_Pdfium_Document_pageCount

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Document_metadata, 0, 0, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Document_permissions arginfo_class_Pdfium_Document_metadata

#define arginfo_class_Pdfium_Page___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_PageIterator___construct arginfo_class_Pdfium_Document___construct

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_PageIterator_current, 0, 0, Pdfium\\Page, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_PageIterator_key arginfo_class_Pdfium_Document_pageCount

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_PageIterator_next, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_PageIterator_rewind arginfo_class_Pdfium_PageIterator_next

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_PageIterator_valid, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Bitmap___construct arginfo_class_Pdfium_Document___construct

ZEND_METHOD(Pdfium_Document, __construct);
ZEND_METHOD(Pdfium_Document, open);
ZEND_METHOD(Pdfium_Document, fromString);
ZEND_METHOD(Pdfium_Document, pageCount);
ZEND_METHOD(Pdfium_Document, metadata);
ZEND_METHOD(Pdfium_Document, permissions);
ZEND_METHOD(Pdfium_Page, __construct);
ZEND_METHOD(Pdfium_PageIterator, __construct);
ZEND_METHOD(Pdfium_PageIterator, current);
ZEND_METHOD(Pdfium_PageIterator, key);
ZEND_METHOD(Pdfium_PageIterator, next);
ZEND_METHOD(Pdfium_PageIterator, rewind);
ZEND_METHOD(Pdfium_PageIterator, valid);
ZEND_METHOD(Pdfium_Bitmap, __construct);

static const zend_function_entry class_Pdfium_Document_methods[] = {
	ZEND_ME(Pdfium_Document, __construct, arginfo_class_Pdfium_Document___construct, ZEND_ACC_PRIVATE)
	ZEND_ME(Pdfium_Document, open, arginfo_class_Pdfium_Document_open, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Pdfium_Document, fromString, arginfo_class_Pdfium_Document_fromString, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Pdfium_Document, pageCount, arginfo_class_Pdfium_Document_pageCount, ZEND_ACC_PUBLIC)
#if (PHP_VERSION_ID >= 80400)
	ZEND_RAW_FENTRY("count", zim_Pdfium_Document_pageCount, arginfo_class_Pdfium_Document_count, ZEND_ACC_PUBLIC, NULL, NULL)
#else
	ZEND_RAW_FENTRY("count", zim_Pdfium_Document_pageCount, arginfo_class_Pdfium_Document_count, ZEND_ACC_PUBLIC)
#endif
	ZEND_ME(Pdfium_Document, metadata, arginfo_class_Pdfium_Document_metadata, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, permissions, arginfo_class_Pdfium_Document_permissions, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Page_methods[] = {
	ZEND_ME(Pdfium_Page, __construct, arginfo_class_Pdfium_Page___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_PageIterator_methods[] = {
	ZEND_ME(Pdfium_PageIterator, __construct, arginfo_class_Pdfium_PageIterator___construct, ZEND_ACC_PRIVATE)
	ZEND_ME(Pdfium_PageIterator, current, arginfo_class_Pdfium_PageIterator_current, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_PageIterator, key, arginfo_class_Pdfium_PageIterator_key, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_PageIterator, next, arginfo_class_Pdfium_PageIterator_next, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_PageIterator, rewind, arginfo_class_Pdfium_PageIterator_rewind, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_PageIterator, valid, arginfo_class_Pdfium_PageIterator_valid, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Bitmap_methods[] = {
	ZEND_ME(Pdfium_Bitmap, __construct, arginfo_class_Pdfium_Bitmap___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static zend_class_entry *register_class_Pdfium_Exception(zend_class_entry *class_entry_RuntimeException)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Exception", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_RuntimeException, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_RuntimeException);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_FileException(zend_class_entry *class_entry_Pdfium_Exception)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "FileException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_Exception, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_Exception);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_FormatException(zend_class_entry *class_entry_Pdfium_Exception)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "FormatException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_Exception, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_Exception);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_PasswordException(zend_class_entry *class_entry_Pdfium_Exception)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "PasswordException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_Exception, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_Exception);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_UnsupportedException(zend_class_entry *class_entry_Pdfium_Exception)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "UnsupportedException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_Exception, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_Exception);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_MemoryException(zend_class_entry *class_entry_Pdfium_Exception)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "MemoryException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_Exception, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_Exception);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Document(zend_class_entry *class_entry_Countable)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Document", class_Pdfium_Document_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE;
#endif
	zend_class_implements(class_entry, 1, class_entry_Countable);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Page(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Page", class_Pdfium_Page_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE;
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_PageIterator(zend_class_entry *class_entry_Iterator)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "PageIterator", class_Pdfium_PageIterator_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE;
#endif
	zend_class_implements(class_entry, 1, class_entry_Iterator);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Bitmap(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Bitmap", class_Pdfium_Bitmap_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE;
#endif

	return class_entry;
}
