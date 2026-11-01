/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 0891255be2ad00ee6509775eaab0e4812223d1bb */

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_Pdfium_Document___construct, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_fromFile, 0, 1, Pdfium\\Document, 0)
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

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_metadata, 0, 0, Pdfium\\Metadata, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_permissions, 0, 0, Pdfium\\Permissions, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_page, 0, 1, Pdfium\\Page, 0)
	ZEND_ARG_TYPE_INFO(0, index, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_pages, 0, 0, Pdfium\\PageIterator, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Document_getIterator arginfo_class_Pdfium_Document_pages

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_extractPages, 0, 1, Pdfium\\Document, 0)
	ZEND_ARG_TYPE_INFO(0, indexes, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, removeSecurity, _IS_BOOL, 0, "false")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_merge, 0, 1, Pdfium\\Document, 0)
	ZEND_ARG_TYPE_INFO(0, documents, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, removeSecurity, _IS_BOOL, 0, "false")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Document_save, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, removeSecurity, _IS_BOOL, 0, "false")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Document_toString, 0, 0, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, removeSecurity, _IS_BOOL, 0, "false")
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Metadata___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Permissions___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Page___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Page_index arginfo_class_Pdfium_Document_pageCount

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Page_width, 0, 0, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Page_height arginfo_class_Pdfium_Page_width

#define arginfo_class_Pdfium_Page_rotation arginfo_class_Pdfium_Document_pageCount

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Page_text, 0, 0, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Page_render, 0, 0, Pdfium\\Bitmap, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, dpi, IS_LONG, 0, "150")
ZEND_END_ARG_INFO()

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

#define arginfo_class_Pdfium_Bitmap_width arginfo_class_Pdfium_Document_pageCount

#define arginfo_class_Pdfium_Bitmap_height arginfo_class_Pdfium_Document_pageCount

#define arginfo_class_Pdfium_Bitmap_toPng arginfo_class_Pdfium_Page_text

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Bitmap_toJpeg, 0, 0, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, quality, IS_LONG, 0, "85")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Bitmap_savePng, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Bitmap_saveJpeg, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, quality, IS_LONG, 0, "85")
ZEND_END_ARG_INFO()

ZEND_METHOD(Pdfium_Document, __construct);
ZEND_METHOD(Pdfium_Document, fromFile);
ZEND_METHOD(Pdfium_Document, fromString);
ZEND_METHOD(Pdfium_Document, pageCount);
ZEND_METHOD(Pdfium_Document, metadata);
ZEND_METHOD(Pdfium_Document, permissions);
ZEND_METHOD(Pdfium_Document, page);
ZEND_METHOD(Pdfium_Document, pages);
ZEND_METHOD(Pdfium_Document, extractPages);
ZEND_METHOD(Pdfium_Document, merge);
ZEND_METHOD(Pdfium_Document, save);
ZEND_METHOD(Pdfium_Document, toString);
ZEND_METHOD(Pdfium_Metadata, __construct);
ZEND_METHOD(Pdfium_Permissions, __construct);
ZEND_METHOD(Pdfium_Page, __construct);
ZEND_METHOD(Pdfium_Page, index);
ZEND_METHOD(Pdfium_Page, width);
ZEND_METHOD(Pdfium_Page, height);
ZEND_METHOD(Pdfium_Page, rotation);
ZEND_METHOD(Pdfium_Page, text);
ZEND_METHOD(Pdfium_Page, render);
ZEND_METHOD(Pdfium_PageIterator, __construct);
ZEND_METHOD(Pdfium_PageIterator, current);
ZEND_METHOD(Pdfium_PageIterator, key);
ZEND_METHOD(Pdfium_PageIterator, next);
ZEND_METHOD(Pdfium_PageIterator, rewind);
ZEND_METHOD(Pdfium_PageIterator, valid);
ZEND_METHOD(Pdfium_Bitmap, __construct);
ZEND_METHOD(Pdfium_Bitmap, width);
ZEND_METHOD(Pdfium_Bitmap, height);
ZEND_METHOD(Pdfium_Bitmap, toPng);
ZEND_METHOD(Pdfium_Bitmap, toJpeg);
ZEND_METHOD(Pdfium_Bitmap, savePng);
ZEND_METHOD(Pdfium_Bitmap, saveJpeg);

static const zend_function_entry class_Pdfium_Document_methods[] = {
	ZEND_ME(Pdfium_Document, __construct, arginfo_class_Pdfium_Document___construct, ZEND_ACC_PRIVATE)
	ZEND_ME(Pdfium_Document, fromFile, arginfo_class_Pdfium_Document_fromFile, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Pdfium_Document, fromString, arginfo_class_Pdfium_Document_fromString, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Pdfium_Document, pageCount, arginfo_class_Pdfium_Document_pageCount, ZEND_ACC_PUBLIC)
#if (PHP_VERSION_ID >= 80400)
	ZEND_RAW_FENTRY("count", zim_Pdfium_Document_pageCount, arginfo_class_Pdfium_Document_count, ZEND_ACC_PUBLIC, NULL, NULL)
#else
	ZEND_RAW_FENTRY("count", zim_Pdfium_Document_pageCount, arginfo_class_Pdfium_Document_count, ZEND_ACC_PUBLIC)
#endif
	ZEND_ME(Pdfium_Document, metadata, arginfo_class_Pdfium_Document_metadata, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, permissions, arginfo_class_Pdfium_Document_permissions, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, page, arginfo_class_Pdfium_Document_page, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, pages, arginfo_class_Pdfium_Document_pages, ZEND_ACC_PUBLIC)
#if (PHP_VERSION_ID >= 80400)
	ZEND_RAW_FENTRY("getIterator", zim_Pdfium_Document_pages, arginfo_class_Pdfium_Document_getIterator, ZEND_ACC_PUBLIC, NULL, NULL)
#else
	ZEND_RAW_FENTRY("getIterator", zim_Pdfium_Document_pages, arginfo_class_Pdfium_Document_getIterator, ZEND_ACC_PUBLIC)
#endif
	ZEND_ME(Pdfium_Document, extractPages, arginfo_class_Pdfium_Document_extractPages, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, merge, arginfo_class_Pdfium_Document_merge, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Pdfium_Document, save, arginfo_class_Pdfium_Document_save, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, toString, arginfo_class_Pdfium_Document_toString, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Metadata_methods[] = {
	ZEND_ME(Pdfium_Metadata, __construct, arginfo_class_Pdfium_Metadata___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Permissions_methods[] = {
	ZEND_ME(Pdfium_Permissions, __construct, arginfo_class_Pdfium_Permissions___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Page_methods[] = {
	ZEND_ME(Pdfium_Page, __construct, arginfo_class_Pdfium_Page___construct, ZEND_ACC_PRIVATE)
	ZEND_ME(Pdfium_Page, index, arginfo_class_Pdfium_Page_index, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Page, width, arginfo_class_Pdfium_Page_width, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Page, height, arginfo_class_Pdfium_Page_height, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Page, rotation, arginfo_class_Pdfium_Page_rotation, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Page, text, arginfo_class_Pdfium_Page_text, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Page, render, arginfo_class_Pdfium_Page_render, ZEND_ACC_PUBLIC)
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
	ZEND_ME(Pdfium_Bitmap, width, arginfo_class_Pdfium_Bitmap_width, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Bitmap, height, arginfo_class_Pdfium_Bitmap_height, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Bitmap, toPng, arginfo_class_Pdfium_Bitmap_toPng, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Bitmap, toJpeg, arginfo_class_Pdfium_Bitmap_toJpeg, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Bitmap, savePng, arginfo_class_Pdfium_Bitmap_savePng, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Bitmap, saveJpeg, arginfo_class_Pdfium_Bitmap_saveJpeg, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static zend_class_entry *register_class_Pdfium_PdfiumException(zend_class_entry *class_entry_RuntimeException)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "PdfiumException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_RuntimeException, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_RuntimeException);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_FileException(zend_class_entry *class_entry_Pdfium_PdfiumException)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "FileException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_PdfiumException, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_PdfiumException);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_FormatException(zend_class_entry *class_entry_Pdfium_PdfiumException)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "FormatException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_PdfiumException, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_PdfiumException);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_PasswordException(zend_class_entry *class_entry_Pdfium_PdfiumException)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "PasswordException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_PdfiumException, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_PdfiumException);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_UnsupportedException(zend_class_entry *class_entry_Pdfium_PdfiumException)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "UnsupportedException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_PdfiumException, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_PdfiumException);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_MemoryException(zend_class_entry *class_entry_Pdfium_PdfiumException)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "MemoryException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_PdfiumException, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_PdfiumException);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_BusyException(zend_class_entry *class_entry_Pdfium_PdfiumException)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "BusyException", NULL);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_Pdfium_PdfiumException, 0);
#else
	class_entry = zend_register_internal_class_ex(&ce, class_entry_Pdfium_PdfiumException);
#endif

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Document(zend_class_entry *class_entry_Countable, zend_class_entry *class_entry_IteratorAggregate)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Document", class_Pdfium_Document_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES|ZEND_ACC_NOT_SERIALIZABLE;
#endif
	zend_class_implements(class_entry, 2, class_entry_Countable, class_entry_IteratorAggregate);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Metadata(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Metadata", class_Pdfium_Metadata_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_title_default_value;
	ZVAL_UNDEF(&property_title_default_value);
	zend_string *property_title_name = zend_string_init("title", sizeof("title") - 1, 1);
	zend_declare_typed_property(class_entry, property_title_name, &property_title_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_title_name);

	zval property_author_default_value;
	ZVAL_UNDEF(&property_author_default_value);
	zend_string *property_author_name = zend_string_init("author", sizeof("author") - 1, 1);
	zend_declare_typed_property(class_entry, property_author_name, &property_author_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_author_name);

	zval property_subject_default_value;
	ZVAL_UNDEF(&property_subject_default_value);
	zend_string *property_subject_name = zend_string_init("subject", sizeof("subject") - 1, 1);
	zend_declare_typed_property(class_entry, property_subject_name, &property_subject_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_subject_name);

	zval property_keywords_default_value;
	ZVAL_UNDEF(&property_keywords_default_value);
	zend_string *property_keywords_name = zend_string_init("keywords", sizeof("keywords") - 1, 1);
	zend_declare_typed_property(class_entry, property_keywords_name, &property_keywords_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_keywords_name);

	zval property_creator_default_value;
	ZVAL_UNDEF(&property_creator_default_value);
	zend_string *property_creator_name = zend_string_init("creator", sizeof("creator") - 1, 1);
	zend_declare_typed_property(class_entry, property_creator_name, &property_creator_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_creator_name);

	zval property_producer_default_value;
	ZVAL_UNDEF(&property_producer_default_value);
	zend_string *property_producer_name = zend_string_init("producer", sizeof("producer") - 1, 1);
	zend_declare_typed_property(class_entry, property_producer_name, &property_producer_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_producer_name);

	zval property_creationDate_default_value;
	ZVAL_UNDEF(&property_creationDate_default_value);
	zend_string *property_creationDate_name = zend_string_init("creationDate", sizeof("creationDate") - 1, 1);
	zend_declare_typed_property(class_entry, property_creationDate_name, &property_creationDate_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_creationDate_name);

	zval property_modDate_default_value;
	ZVAL_UNDEF(&property_modDate_default_value);
	zend_string *property_modDate_name = zend_string_init("modDate", sizeof("modDate") - 1, 1);
	zend_declare_typed_property(class_entry, property_modDate_name, &property_modDate_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_modDate_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Permissions(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Permissions", class_Pdfium_Permissions_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_print_default_value;
	ZVAL_UNDEF(&property_print_default_value);
	zend_string *property_print_name = zend_string_init("print", sizeof("print") - 1, 1);
	zend_declare_typed_property(class_entry, property_print_name, &property_print_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_print_name);

	zval property_modify_default_value;
	ZVAL_UNDEF(&property_modify_default_value);
	zend_string *property_modify_name = zend_string_init("modify", sizeof("modify") - 1, 1);
	zend_declare_typed_property(class_entry, property_modify_name, &property_modify_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_modify_name);

	zval property_copy_default_value;
	ZVAL_UNDEF(&property_copy_default_value);
	zend_string *property_copy_name = zend_string_init("copy", sizeof("copy") - 1, 1);
	zend_declare_typed_property(class_entry, property_copy_name, &property_copy_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_copy_name);

	zval property_annotate_default_value;
	ZVAL_UNDEF(&property_annotate_default_value);
	zend_string *property_annotate_name = zend_string_init("annotate", sizeof("annotate") - 1, 1);
	zend_declare_typed_property(class_entry, property_annotate_name, &property_annotate_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_annotate_name);

	zval property_fillForms_default_value;
	ZVAL_UNDEF(&property_fillForms_default_value);
	zend_string *property_fillForms_name = zend_string_init("fillForms", sizeof("fillForms") - 1, 1);
	zend_declare_typed_property(class_entry, property_fillForms_name, &property_fillForms_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_fillForms_name);

	zval property_extractForAccessibility_default_value;
	ZVAL_UNDEF(&property_extractForAccessibility_default_value);
	zend_string *property_extractForAccessibility_name = zend_string_init("extractForAccessibility", sizeof("extractForAccessibility") - 1, 1);
	zend_declare_typed_property(class_entry, property_extractForAccessibility_name, &property_extractForAccessibility_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_extractForAccessibility_name);

	zval property_assemble_default_value;
	ZVAL_UNDEF(&property_assemble_default_value);
	zend_string *property_assemble_name = zend_string_init("assemble", sizeof("assemble") - 1, 1);
	zend_declare_typed_property(class_entry, property_assemble_name, &property_assemble_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_assemble_name);

	zval property_printHighQuality_default_value;
	ZVAL_UNDEF(&property_printHighQuality_default_value);
	zend_string *property_printHighQuality_name = zend_string_init("printHighQuality", sizeof("printHighQuality") - 1, 1);
	zend_declare_typed_property(class_entry, property_printHighQuality_name, &property_printHighQuality_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_printHighQuality_name);

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
