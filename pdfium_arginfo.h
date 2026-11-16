/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 7de8083176536e69054741e49aaced1e394a8176 */

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

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_Pdfium_Rect___construct, 0, 0, 4)
	ZEND_ARG_TYPE_INFO(0, left, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, bottom, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, right, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, top, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Color___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Matrix___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Char___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_TextMatch___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_WebLink___construct arginfo_class_Pdfium_Document___construct

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
	ZEND_ARG_OBJ_INFO_WITH_DEFAULT_VALUE(0, region, Pdfium\\Rect, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, transparent, _IS_BOOL, 0, "false")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, annotations, _IS_BOOL, 0, "true")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, grayscale, _IS_BOOL, 0, "false")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Page_textPage, 0, 0, Pdfium\\TextPage, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_TextPage___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_TextPage_text arginfo_class_Pdfium_Page_text

#define arginfo_class_Pdfium_TextPage_charCount arginfo_class_Pdfium_Document_pageCount

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_TextPage_chars, 0, 0, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, start, IS_LONG, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, length, IS_LONG, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_TextPage_charIndexAt, 0, 2, IS_LONG, 1)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, xTolerance, IS_DOUBLE, 0, "0.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, yTolerance, IS_DOUBLE, 0, "0.0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_TextPage_textIn, 0, 1, IS_STRING, 0)
	ZEND_ARG_OBJ_INFO(0, rect, Pdfium\\Rect, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_TextPage_textInEach, 0, 1, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, rects, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_TextPage_search, 0, 1, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, needle, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, matchCase, _IS_BOOL, 0, "false")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, wholeWord, _IS_BOOL, 0, "false")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, overlapping, _IS_BOOL, 0, "false")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_TextPage_webLinks, 0, 0, IS_ARRAY, 0)
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
ZEND_METHOD(Pdfium_Rect, __construct);
ZEND_METHOD(Pdfium_Color, __construct);
ZEND_METHOD(Pdfium_Matrix, __construct);
ZEND_METHOD(Pdfium_Char, __construct);
ZEND_METHOD(Pdfium_TextMatch, __construct);
ZEND_METHOD(Pdfium_WebLink, __construct);
ZEND_METHOD(Pdfium_Page, __construct);
ZEND_METHOD(Pdfium_Page, index);
ZEND_METHOD(Pdfium_Page, width);
ZEND_METHOD(Pdfium_Page, height);
ZEND_METHOD(Pdfium_Page, rotation);
ZEND_METHOD(Pdfium_Page, text);
ZEND_METHOD(Pdfium_Page, render);
ZEND_METHOD(Pdfium_Page, textPage);
ZEND_METHOD(Pdfium_TextPage, __construct);
ZEND_METHOD(Pdfium_TextPage, text);
ZEND_METHOD(Pdfium_TextPage, charCount);
ZEND_METHOD(Pdfium_TextPage, chars);
ZEND_METHOD(Pdfium_TextPage, charIndexAt);
ZEND_METHOD(Pdfium_TextPage, textIn);
ZEND_METHOD(Pdfium_TextPage, textInEach);
ZEND_METHOD(Pdfium_TextPage, search);
ZEND_METHOD(Pdfium_TextPage, webLinks);
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

static const zend_function_entry class_Pdfium_Rect_methods[] = {
	ZEND_ME(Pdfium_Rect, __construct, arginfo_class_Pdfium_Rect___construct, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Color_methods[] = {
	ZEND_ME(Pdfium_Color, __construct, arginfo_class_Pdfium_Color___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Matrix_methods[] = {
	ZEND_ME(Pdfium_Matrix, __construct, arginfo_class_Pdfium_Matrix___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Char_methods[] = {
	ZEND_ME(Pdfium_Char, __construct, arginfo_class_Pdfium_Char___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_TextMatch_methods[] = {
	ZEND_ME(Pdfium_TextMatch, __construct, arginfo_class_Pdfium_TextMatch___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_WebLink_methods[] = {
	ZEND_ME(Pdfium_WebLink, __construct, arginfo_class_Pdfium_WebLink___construct, ZEND_ACC_PRIVATE)
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
	ZEND_ME(Pdfium_Page, textPage, arginfo_class_Pdfium_Page_textPage, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_TextPage_methods[] = {
	ZEND_ME(Pdfium_TextPage, __construct, arginfo_class_Pdfium_TextPage___construct, ZEND_ACC_PRIVATE)
	ZEND_ME(Pdfium_TextPage, text, arginfo_class_Pdfium_TextPage_text, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_TextPage, charCount, arginfo_class_Pdfium_TextPage_charCount, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_TextPage, chars, arginfo_class_Pdfium_TextPage_chars, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_TextPage, charIndexAt, arginfo_class_Pdfium_TextPage_charIndexAt, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_TextPage, textIn, arginfo_class_Pdfium_TextPage_textIn, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_TextPage, textInEach, arginfo_class_Pdfium_TextPage_textInEach, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_TextPage, search, arginfo_class_Pdfium_TextPage_search, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_TextPage, webLinks, arginfo_class_Pdfium_TextPage_webLinks, ZEND_ACC_PUBLIC)
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

static zend_class_entry *register_class_Pdfium_Rect(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Rect", class_Pdfium_Rect_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_left_default_value;
	ZVAL_UNDEF(&property_left_default_value);
	zend_string *property_left_name = zend_string_init("left", sizeof("left") - 1, 1);
	zend_declare_typed_property(class_entry, property_left_name, &property_left_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_left_name);

	zval property_bottom_default_value;
	ZVAL_UNDEF(&property_bottom_default_value);
	zend_string *property_bottom_name = zend_string_init("bottom", sizeof("bottom") - 1, 1);
	zend_declare_typed_property(class_entry, property_bottom_name, &property_bottom_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_bottom_name);

	zval property_right_default_value;
	ZVAL_UNDEF(&property_right_default_value);
	zend_string *property_right_name = zend_string_init("right", sizeof("right") - 1, 1);
	zend_declare_typed_property(class_entry, property_right_name, &property_right_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_right_name);

	zval property_top_default_value;
	ZVAL_UNDEF(&property_top_default_value);
	zend_string *property_top_name = zend_string_init("top", sizeof("top") - 1, 1);
	zend_declare_typed_property(class_entry, property_top_name, &property_top_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_top_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Color(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Color", class_Pdfium_Color_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_red_default_value;
	ZVAL_UNDEF(&property_red_default_value);
	zend_string *property_red_name = zend_string_init("red", sizeof("red") - 1, 1);
	zend_declare_typed_property(class_entry, property_red_name, &property_red_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_red_name);

	zval property_green_default_value;
	ZVAL_UNDEF(&property_green_default_value);
	zend_string *property_green_name = zend_string_init("green", sizeof("green") - 1, 1);
	zend_declare_typed_property(class_entry, property_green_name, &property_green_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_green_name);

	zval property_blue_default_value;
	ZVAL_UNDEF(&property_blue_default_value);
	zend_string *property_blue_name = zend_string_init("blue", sizeof("blue") - 1, 1);
	zend_declare_typed_property(class_entry, property_blue_name, &property_blue_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_blue_name);

	zval property_alpha_default_value;
	ZVAL_UNDEF(&property_alpha_default_value);
	zend_string *property_alpha_name = zend_string_init("alpha", sizeof("alpha") - 1, 1);
	zend_declare_typed_property(class_entry, property_alpha_name, &property_alpha_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_alpha_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Matrix(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Matrix", class_Pdfium_Matrix_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_a_default_value;
	ZVAL_UNDEF(&property_a_default_value);
	zend_string *property_a_name = zend_string_init("a", sizeof("a") - 1, 1);
	zend_declare_typed_property(class_entry, property_a_name, &property_a_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_a_name);

	zval property_b_default_value;
	ZVAL_UNDEF(&property_b_default_value);
	zend_string *property_b_name = zend_string_init("b", sizeof("b") - 1, 1);
	zend_declare_typed_property(class_entry, property_b_name, &property_b_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_b_name);

	zval property_c_default_value;
	ZVAL_UNDEF(&property_c_default_value);
	zend_string *property_c_name = zend_string_init("c", sizeof("c") - 1, 1);
	zend_declare_typed_property(class_entry, property_c_name, &property_c_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_c_name);

	zval property_d_default_value;
	ZVAL_UNDEF(&property_d_default_value);
	zend_string *property_d_name = zend_string_init("d", sizeof("d") - 1, 1);
	zend_declare_typed_property(class_entry, property_d_name, &property_d_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_d_name);

	zval property_e_default_value;
	ZVAL_UNDEF(&property_e_default_value);
	zend_string *property_e_name = zend_string_init("e", sizeof("e") - 1, 1);
	zend_declare_typed_property(class_entry, property_e_name, &property_e_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_e_name);

	zval property_f_default_value;
	ZVAL_UNDEF(&property_f_default_value);
	zend_string *property_f_name = zend_string_init("f", sizeof("f") - 1, 1);
	zend_declare_typed_property(class_entry, property_f_name, &property_f_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_f_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Char(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Char", class_Pdfium_Char_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_text_default_value;
	ZVAL_UNDEF(&property_text_default_value);
	zend_string *property_text_name = zend_string_init("text", sizeof("text") - 1, 1);
	zend_declare_typed_property(class_entry, property_text_name, &property_text_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release(property_text_name);

	zval property_box_default_value;
	ZVAL_UNDEF(&property_box_default_value);
	zend_string *property_box_name = zend_string_init("box", sizeof("box") - 1, 1);
	zend_string *property_box_class_Pdfium_Rect = zend_string_init("Pdfium\\Rect", sizeof("Pdfium\\Rect")-1, 1);
	zend_declare_typed_property(class_entry, property_box_name, &property_box_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_box_class_Pdfium_Rect, 0, 0));
	zend_string_release(property_box_name);

	zval property_looseBox_default_value;
	ZVAL_UNDEF(&property_looseBox_default_value);
	zend_string *property_looseBox_name = zend_string_init("looseBox", sizeof("looseBox") - 1, 1);
	zend_string *property_looseBox_class_Pdfium_Rect = zend_string_init("Pdfium\\Rect", sizeof("Pdfium\\Rect")-1, 1);
	zend_declare_typed_property(class_entry, property_looseBox_name, &property_looseBox_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_looseBox_class_Pdfium_Rect, 0, 0));
	zend_string_release(property_looseBox_name);

	zval property_x_default_value;
	ZVAL_UNDEF(&property_x_default_value);
	zend_string *property_x_name = zend_string_init("x", sizeof("x") - 1, 1);
	zend_declare_typed_property(class_entry, property_x_name, &property_x_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_x_name);

	zval property_y_default_value;
	ZVAL_UNDEF(&property_y_default_value);
	zend_string *property_y_name = zend_string_init("y", sizeof("y") - 1, 1);
	zend_declare_typed_property(class_entry, property_y_name, &property_y_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_y_name);

	zval property_fontSize_default_value;
	ZVAL_UNDEF(&property_fontSize_default_value);
	zend_string *property_fontSize_name = zend_string_init("fontSize", sizeof("fontSize") - 1, 1);
	zend_declare_typed_property(class_entry, property_fontSize_name, &property_fontSize_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_fontSize_name);

	zval property_fontName_default_value;
	ZVAL_UNDEF(&property_fontName_default_value);
	zend_string *property_fontName_name = zend_string_init("fontName", sizeof("fontName") - 1, 1);
	zend_declare_typed_property(class_entry, property_fontName_name, &property_fontName_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_fontName_name);

	zval property_fontWeight_default_value;
	ZVAL_UNDEF(&property_fontWeight_default_value);
	zend_string *property_fontWeight_name = zend_string_init("fontWeight", sizeof("fontWeight") - 1, 1);
	zend_declare_typed_property(class_entry, property_fontWeight_name, &property_fontWeight_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG|MAY_BE_NULL));
	zend_string_release(property_fontWeight_name);

	zval property_fontFlags_default_value;
	ZVAL_UNDEF(&property_fontFlags_default_value);
	zend_string *property_fontFlags_name = zend_string_init("fontFlags", sizeof("fontFlags") - 1, 1);
	zend_declare_typed_property(class_entry, property_fontFlags_name, &property_fontFlags_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_fontFlags_name);

	zval property_fillColor_default_value;
	ZVAL_UNDEF(&property_fillColor_default_value);
	zend_string *property_fillColor_name = zend_string_init("fillColor", sizeof("fillColor") - 1, 1);
	zend_string *property_fillColor_class_Pdfium_Color = zend_string_init("Pdfium\\Color", sizeof("Pdfium\\Color")-1, 1);
	zend_declare_typed_property(class_entry, property_fillColor_name, &property_fillColor_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_fillColor_class_Pdfium_Color, 0, MAY_BE_NULL));
	zend_string_release(property_fillColor_name);

	zval property_strokeColor_default_value;
	ZVAL_UNDEF(&property_strokeColor_default_value);
	zend_string *property_strokeColor_name = zend_string_init("strokeColor", sizeof("strokeColor") - 1, 1);
	zend_string *property_strokeColor_class_Pdfium_Color = zend_string_init("Pdfium\\Color", sizeof("Pdfium\\Color")-1, 1);
	zend_declare_typed_property(class_entry, property_strokeColor_name, &property_strokeColor_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_strokeColor_class_Pdfium_Color, 0, MAY_BE_NULL));
	zend_string_release(property_strokeColor_name);

	zval property_angle_default_value;
	ZVAL_UNDEF(&property_angle_default_value);
	zend_string *property_angle_name = zend_string_init("angle", sizeof("angle") - 1, 1);
	zend_declare_typed_property(class_entry, property_angle_name, &property_angle_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_angle_name);

	zval property_matrix_default_value;
	ZVAL_UNDEF(&property_matrix_default_value);
	zend_string *property_matrix_name = zend_string_init("matrix", sizeof("matrix") - 1, 1);
	zend_string *property_matrix_class_Pdfium_Matrix = zend_string_init("Pdfium\\Matrix", sizeof("Pdfium\\Matrix")-1, 1);
	zend_declare_typed_property(class_entry, property_matrix_name, &property_matrix_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_matrix_class_Pdfium_Matrix, 0, MAY_BE_NULL));
	zend_string_release(property_matrix_name);

	zval property_generated_default_value;
	ZVAL_UNDEF(&property_generated_default_value);
	zend_string *property_generated_name = zend_string_init("generated", sizeof("generated") - 1, 1);
	zend_declare_typed_property(class_entry, property_generated_name, &property_generated_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_generated_name);

	zval property_hyphen_default_value;
	ZVAL_UNDEF(&property_hyphen_default_value);
	zend_string *property_hyphen_name = zend_string_init("hyphen", sizeof("hyphen") - 1, 1);
	zend_declare_typed_property(class_entry, property_hyphen_name, &property_hyphen_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_hyphen_name);

	zval property_unicodeMapError_default_value;
	ZVAL_UNDEF(&property_unicodeMapError_default_value);
	zend_string *property_unicodeMapError_name = zend_string_init("unicodeMapError", sizeof("unicodeMapError") - 1, 1);
	zend_declare_typed_property(class_entry, property_unicodeMapError_name, &property_unicodeMapError_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_unicodeMapError_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_TextMatch(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "TextMatch", class_Pdfium_TextMatch_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_index_default_value;
	ZVAL_UNDEF(&property_index_default_value);
	zend_string *property_index_name = zend_string_init("index", sizeof("index") - 1, 1);
	zend_declare_typed_property(class_entry, property_index_name, &property_index_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_index_name);

	zval property_length_default_value;
	ZVAL_UNDEF(&property_length_default_value);
	zend_string *property_length_name = zend_string_init("length", sizeof("length") - 1, 1);
	zend_declare_typed_property(class_entry, property_length_name, &property_length_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_length_name);

	zval property_text_default_value;
	ZVAL_UNDEF(&property_text_default_value);
	zend_string *property_text_name = zend_string_init("text", sizeof("text") - 1, 1);
	zend_declare_typed_property(class_entry, property_text_name, &property_text_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release(property_text_name);

	zval property_rects_default_value;
	ZVAL_UNDEF(&property_rects_default_value);
	zend_string *property_rects_name = zend_string_init("rects", sizeof("rects") - 1, 1);
	zend_declare_typed_property(class_entry, property_rects_name, &property_rects_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_ARRAY));
	zend_string_release(property_rects_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_WebLink(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "WebLink", class_Pdfium_WebLink_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_url_default_value;
	ZVAL_UNDEF(&property_url_default_value);
	zend_string *property_url_name = zend_string_init("url", sizeof("url") - 1, 1);
	zend_declare_typed_property(class_entry, property_url_name, &property_url_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release(property_url_name);

	zval property_index_default_value;
	ZVAL_UNDEF(&property_index_default_value);
	zend_string *property_index_name = zend_string_init("index", sizeof("index") - 1, 1);
	zend_declare_typed_property(class_entry, property_index_name, &property_index_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_index_name);

	zval property_length_default_value;
	ZVAL_UNDEF(&property_length_default_value);
	zend_string *property_length_name = zend_string_init("length", sizeof("length") - 1, 1);
	zend_declare_typed_property(class_entry, property_length_name, &property_length_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_length_name);

	zval property_rects_default_value;
	ZVAL_UNDEF(&property_rects_default_value);
	zend_string *property_rects_name = zend_string_init("rects", sizeof("rects") - 1, 1);
	zend_declare_typed_property(class_entry, property_rects_name, &property_rects_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_ARRAY));
	zend_string_release(property_rects_name);

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

static zend_class_entry *register_class_Pdfium_TextPage(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "TextPage", class_Pdfium_TextPage_methods);
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
