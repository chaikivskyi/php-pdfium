/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: b76ffe263b7ad8cb866107242cb59828f6c6e1b9 */

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

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Document_isTagged, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Document_language, 0, 0, IS_STRING, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Document_setLanguage, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, language, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Document_permanentId arginfo_class_Pdfium_Document_language

#define arginfo_class_Pdfium_Document_changingId arginfo_class_Pdfium_Document_language

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Document_bookmarks, 0, 0, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Document_attachmentCount arginfo_class_Pdfium_Document_pageCount

#define arginfo_class_Pdfium_Document_attachments arginfo_class_Pdfium_Document_bookmarks

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_attachment, 0, 1, Pdfium\\Attachment, 0)
	ZEND_ARG_TYPE_INFO(0, index, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Document_addAttachment, 0, 2, Pdfium\\Attachment, 0)
	ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, contents, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, description, IS_STRING, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_Document_deleteAttachment, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, index, IS_LONG, 0)
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

#define arginfo_class_Pdfium_AttachmentMetadata___construct arginfo_class_Pdfium_Document___construct

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

#define arginfo_class_Pdfium_Bookmark___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Link___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Quad___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Destination___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Action___construct arginfo_class_Pdfium_Document___construct

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

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Page_openAction, 0, 0, Pdfium\\Action, 1)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Page_closeAction arginfo_class_Pdfium_Page_openAction

#define arginfo_class_Pdfium_Page_links arginfo_class_Pdfium_Document_bookmarks

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Page_linkAt, 0, 2, Pdfium\\Link, 1)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_Attachment___construct arginfo_class_Pdfium_Document___construct

#define arginfo_class_Pdfium_Attachment_index arginfo_class_Pdfium_Document_pageCount

#define arginfo_class_Pdfium_Attachment_name arginfo_class_Pdfium_Page_text

#define arginfo_class_Pdfium_Attachment_contents arginfo_class_Pdfium_Document_language

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_Attachment_metadata, 0, 0, Pdfium\\AttachmentMetadata, 0)
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

#define arginfo_class_Pdfium_TextPage_webLinks arginfo_class_Pdfium_Document_bookmarks

#define arginfo_class_Pdfium_PageIterator___construct arginfo_class_Pdfium_Document___construct

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_Pdfium_PageIterator_current, 0, 0, Pdfium\\Page, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_PageIterator_key arginfo_class_Pdfium_Document_pageCount

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Pdfium_PageIterator_next, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Pdfium_PageIterator_rewind arginfo_class_Pdfium_PageIterator_next

#define arginfo_class_Pdfium_PageIterator_valid arginfo_class_Pdfium_Document_isTagged

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
ZEND_METHOD(Pdfium_Document, isTagged);
ZEND_METHOD(Pdfium_Document, language);
ZEND_METHOD(Pdfium_Document, setLanguage);
ZEND_METHOD(Pdfium_Document, permanentId);
ZEND_METHOD(Pdfium_Document, changingId);
ZEND_METHOD(Pdfium_Document, bookmarks);
ZEND_METHOD(Pdfium_Document, attachmentCount);
ZEND_METHOD(Pdfium_Document, attachments);
ZEND_METHOD(Pdfium_Document, attachment);
ZEND_METHOD(Pdfium_Document, addAttachment);
ZEND_METHOD(Pdfium_Document, deleteAttachment);
ZEND_METHOD(Pdfium_Document, page);
ZEND_METHOD(Pdfium_Document, pages);
ZEND_METHOD(Pdfium_Document, extractPages);
ZEND_METHOD(Pdfium_Document, merge);
ZEND_METHOD(Pdfium_Document, save);
ZEND_METHOD(Pdfium_Document, toString);
ZEND_METHOD(Pdfium_Metadata, __construct);
ZEND_METHOD(Pdfium_AttachmentMetadata, __construct);
ZEND_METHOD(Pdfium_Permissions, __construct);
ZEND_METHOD(Pdfium_Rect, __construct);
ZEND_METHOD(Pdfium_Color, __construct);
ZEND_METHOD(Pdfium_Matrix, __construct);
ZEND_METHOD(Pdfium_Char, __construct);
ZEND_METHOD(Pdfium_TextMatch, __construct);
ZEND_METHOD(Pdfium_WebLink, __construct);
ZEND_METHOD(Pdfium_Bookmark, __construct);
ZEND_METHOD(Pdfium_Link, __construct);
ZEND_METHOD(Pdfium_Quad, __construct);
ZEND_METHOD(Pdfium_Destination, __construct);
ZEND_METHOD(Pdfium_Action, __construct);
ZEND_METHOD(Pdfium_Page, __construct);
ZEND_METHOD(Pdfium_Page, index);
ZEND_METHOD(Pdfium_Page, width);
ZEND_METHOD(Pdfium_Page, height);
ZEND_METHOD(Pdfium_Page, rotation);
ZEND_METHOD(Pdfium_Page, text);
ZEND_METHOD(Pdfium_Page, render);
ZEND_METHOD(Pdfium_Page, textPage);
ZEND_METHOD(Pdfium_Page, openAction);
ZEND_METHOD(Pdfium_Page, closeAction);
ZEND_METHOD(Pdfium_Page, links);
ZEND_METHOD(Pdfium_Page, linkAt);
ZEND_METHOD(Pdfium_Attachment, __construct);
ZEND_METHOD(Pdfium_Attachment, index);
ZEND_METHOD(Pdfium_Attachment, name);
ZEND_METHOD(Pdfium_Attachment, contents);
ZEND_METHOD(Pdfium_Attachment, metadata);
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
	ZEND_ME(Pdfium_Document, isTagged, arginfo_class_Pdfium_Document_isTagged, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, language, arginfo_class_Pdfium_Document_language, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, setLanguage, arginfo_class_Pdfium_Document_setLanguage, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, permanentId, arginfo_class_Pdfium_Document_permanentId, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, changingId, arginfo_class_Pdfium_Document_changingId, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, bookmarks, arginfo_class_Pdfium_Document_bookmarks, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, attachmentCount, arginfo_class_Pdfium_Document_attachmentCount, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, attachments, arginfo_class_Pdfium_Document_attachments, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, attachment, arginfo_class_Pdfium_Document_attachment, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, addAttachment, arginfo_class_Pdfium_Document_addAttachment, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Document, deleteAttachment, arginfo_class_Pdfium_Document_deleteAttachment, ZEND_ACC_PUBLIC)
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

static const zend_function_entry class_Pdfium_AttachmentMetadata_methods[] = {
	ZEND_ME(Pdfium_AttachmentMetadata, __construct, arginfo_class_Pdfium_AttachmentMetadata___construct, ZEND_ACC_PRIVATE)
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

static const zend_function_entry class_Pdfium_Bookmark_methods[] = {
	ZEND_ME(Pdfium_Bookmark, __construct, arginfo_class_Pdfium_Bookmark___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Link_methods[] = {
	ZEND_ME(Pdfium_Link, __construct, arginfo_class_Pdfium_Link___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Quad_methods[] = {
	ZEND_ME(Pdfium_Quad, __construct, arginfo_class_Pdfium_Quad___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Destination_methods[] = {
	ZEND_ME(Pdfium_Destination, __construct, arginfo_class_Pdfium_Destination___construct, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Action_methods[] = {
	ZEND_ME(Pdfium_Action, __construct, arginfo_class_Pdfium_Action___construct, ZEND_ACC_PRIVATE)
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
	ZEND_ME(Pdfium_Page, openAction, arginfo_class_Pdfium_Page_openAction, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Page, closeAction, arginfo_class_Pdfium_Page_closeAction, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Page, links, arginfo_class_Pdfium_Page_links, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Page, linkAt, arginfo_class_Pdfium_Page_linkAt, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_Pdfium_Attachment_methods[] = {
	ZEND_ME(Pdfium_Attachment, __construct, arginfo_class_Pdfium_Attachment___construct, ZEND_ACC_PRIVATE)
	ZEND_ME(Pdfium_Attachment, index, arginfo_class_Pdfium_Attachment_index, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Attachment, name, arginfo_class_Pdfium_Attachment_name, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Attachment, contents, arginfo_class_Pdfium_Attachment_contents, ZEND_ACC_PUBLIC)
	ZEND_ME(Pdfium_Attachment, metadata, arginfo_class_Pdfium_Attachment_metadata, ZEND_ACC_PUBLIC)
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

static zend_class_entry *register_class_Pdfium_AttachmentMetadata(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "AttachmentMetadata", class_Pdfium_AttachmentMetadata_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_description_default_value;
	ZVAL_UNDEF(&property_description_default_value);
	zend_string *property_description_name = zend_string_init("description", sizeof("description") - 1, 1);
	zend_declare_typed_property(class_entry, property_description_name, &property_description_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_description_name);

	zval property_subtype_default_value;
	ZVAL_UNDEF(&property_subtype_default_value);
	zend_string *property_subtype_name = zend_string_init("subtype", sizeof("subtype") - 1, 1);
	zend_declare_typed_property(class_entry, property_subtype_name, &property_subtype_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_subtype_name);

	zval property_afRelationship_default_value;
	ZVAL_UNDEF(&property_afRelationship_default_value);
	zend_string *property_afRelationship_name = zend_string_init("afRelationship", sizeof("afRelationship") - 1, 1);
	zend_declare_typed_property(class_entry, property_afRelationship_name, &property_afRelationship_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_afRelationship_name);

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

	zval property_checksum_default_value;
	ZVAL_UNDEF(&property_checksum_default_value);
	zend_string *property_checksum_name = zend_string_init("checksum", sizeof("checksum") - 1, 1);
	zend_declare_typed_property(class_entry, property_checksum_name, &property_checksum_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_checksum_name);

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

static zend_class_entry *register_class_Pdfium_Bookmark(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Bookmark", class_Pdfium_Bookmark_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_title_default_value;
	ZVAL_UNDEF(&property_title_default_value);
	zend_string *property_title_name = zend_string_init("title", sizeof("title") - 1, 1);
	zend_declare_typed_property(class_entry, property_title_name, &property_title_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release(property_title_name);

	zval property_open_default_value;
	ZVAL_UNDEF(&property_open_default_value);
	zend_string *property_open_name = zend_string_init("open", sizeof("open") - 1, 1);
	zend_declare_typed_property(class_entry, property_open_name, &property_open_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_open_name);

	zval property_color_default_value;
	ZVAL_UNDEF(&property_color_default_value);
	zend_string *property_color_name = zend_string_init("color", sizeof("color") - 1, 1);
	zend_string *property_color_class_Pdfium_Color = zend_string_init("Pdfium\\Color", sizeof("Pdfium\\Color")-1, 1);
	zend_declare_typed_property(class_entry, property_color_name, &property_color_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_color_class_Pdfium_Color, 0, MAY_BE_NULL));
	zend_string_release(property_color_name);

	zval property_italic_default_value;
	ZVAL_UNDEF(&property_italic_default_value);
	zend_string *property_italic_name = zend_string_init("italic", sizeof("italic") - 1, 1);
	zend_declare_typed_property(class_entry, property_italic_name, &property_italic_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_italic_name);

	zval property_bold_default_value;
	ZVAL_UNDEF(&property_bold_default_value);
	zend_string *property_bold_name = zend_string_init("bold", sizeof("bold") - 1, 1);
	zend_declare_typed_property(class_entry, property_bold_name, &property_bold_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release(property_bold_name);

	zval property_destination_default_value;
	ZVAL_UNDEF(&property_destination_default_value);
	zend_string *property_destination_name = zend_string_init("destination", sizeof("destination") - 1, 1);
	zend_string *property_destination_class_Pdfium_Destination = zend_string_init("Pdfium\\Destination", sizeof("Pdfium\\Destination")-1, 1);
	zend_declare_typed_property(class_entry, property_destination_name, &property_destination_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_destination_class_Pdfium_Destination, 0, MAY_BE_NULL));
	zend_string_release(property_destination_name);

	zval property_action_default_value;
	ZVAL_UNDEF(&property_action_default_value);
	zend_string *property_action_name = zend_string_init("action", sizeof("action") - 1, 1);
	zend_string *property_action_class_Pdfium_Action = zend_string_init("Pdfium\\Action", sizeof("Pdfium\\Action")-1, 1);
	zend_declare_typed_property(class_entry, property_action_name, &property_action_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_action_class_Pdfium_Action, 0, MAY_BE_NULL));
	zend_string_release(property_action_name);

	zval property_children_default_value;
	ZVAL_UNDEF(&property_children_default_value);
	zend_string *property_children_name = zend_string_init("children", sizeof("children") - 1, 1);
	zend_declare_typed_property(class_entry, property_children_name, &property_children_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_ARRAY));
	zend_string_release(property_children_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Link(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Link", class_Pdfium_Link_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_rect_default_value;
	ZVAL_UNDEF(&property_rect_default_value);
	zend_string *property_rect_name = zend_string_init("rect", sizeof("rect") - 1, 1);
	zend_string *property_rect_class_Pdfium_Rect = zend_string_init("Pdfium\\Rect", sizeof("Pdfium\\Rect")-1, 1);
	zend_declare_typed_property(class_entry, property_rect_name, &property_rect_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_rect_class_Pdfium_Rect, 0, 0));
	zend_string_release(property_rect_name);

	zval property_quadPoints_default_value;
	ZVAL_UNDEF(&property_quadPoints_default_value);
	zend_string *property_quadPoints_name = zend_string_init("quadPoints", sizeof("quadPoints") - 1, 1);
	zend_declare_typed_property(class_entry, property_quadPoints_name, &property_quadPoints_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_ARRAY));
	zend_string_release(property_quadPoints_name);

	zval property_destination_default_value;
	ZVAL_UNDEF(&property_destination_default_value);
	zend_string *property_destination_name = zend_string_init("destination", sizeof("destination") - 1, 1);
	zend_string *property_destination_class_Pdfium_Destination = zend_string_init("Pdfium\\Destination", sizeof("Pdfium\\Destination")-1, 1);
	zend_declare_typed_property(class_entry, property_destination_name, &property_destination_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_destination_class_Pdfium_Destination, 0, MAY_BE_NULL));
	zend_string_release(property_destination_name);

	zval property_action_default_value;
	ZVAL_UNDEF(&property_action_default_value);
	zend_string *property_action_name = zend_string_init("action", sizeof("action") - 1, 1);
	zend_string *property_action_class_Pdfium_Action = zend_string_init("Pdfium\\Action", sizeof("Pdfium\\Action")-1, 1);
	zend_declare_typed_property(class_entry, property_action_name, &property_action_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_action_class_Pdfium_Action, 0, MAY_BE_NULL));
	zend_string_release(property_action_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Quad(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Quad", class_Pdfium_Quad_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_x1_default_value;
	ZVAL_UNDEF(&property_x1_default_value);
	zend_string *property_x1_name = zend_string_init("x1", sizeof("x1") - 1, 1);
	zend_declare_typed_property(class_entry, property_x1_name, &property_x1_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_x1_name);

	zval property_y1_default_value;
	ZVAL_UNDEF(&property_y1_default_value);
	zend_string *property_y1_name = zend_string_init("y1", sizeof("y1") - 1, 1);
	zend_declare_typed_property(class_entry, property_y1_name, &property_y1_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_y1_name);

	zval property_x2_default_value;
	ZVAL_UNDEF(&property_x2_default_value);
	zend_string *property_x2_name = zend_string_init("x2", sizeof("x2") - 1, 1);
	zend_declare_typed_property(class_entry, property_x2_name, &property_x2_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_x2_name);

	zval property_y2_default_value;
	ZVAL_UNDEF(&property_y2_default_value);
	zend_string *property_y2_name = zend_string_init("y2", sizeof("y2") - 1, 1);
	zend_declare_typed_property(class_entry, property_y2_name, &property_y2_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_y2_name);

	zval property_x3_default_value;
	ZVAL_UNDEF(&property_x3_default_value);
	zend_string *property_x3_name = zend_string_init("x3", sizeof("x3") - 1, 1);
	zend_declare_typed_property(class_entry, property_x3_name, &property_x3_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_x3_name);

	zval property_y3_default_value;
	ZVAL_UNDEF(&property_y3_default_value);
	zend_string *property_y3_name = zend_string_init("y3", sizeof("y3") - 1, 1);
	zend_declare_typed_property(class_entry, property_y3_name, &property_y3_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_y3_name);

	zval property_x4_default_value;
	ZVAL_UNDEF(&property_x4_default_value);
	zend_string *property_x4_name = zend_string_init("x4", sizeof("x4") - 1, 1);
	zend_declare_typed_property(class_entry, property_x4_name, &property_x4_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_x4_name);

	zval property_y4_default_value;
	ZVAL_UNDEF(&property_y4_default_value);
	zend_string *property_y4_name = zend_string_init("y4", sizeof("y4") - 1, 1);
	zend_declare_typed_property(class_entry, property_y4_name, &property_y4_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_y4_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Destination(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Destination", class_Pdfium_Destination_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_pageIndex_default_value;
	ZVAL_UNDEF(&property_pageIndex_default_value);
	zend_string *property_pageIndex_name = zend_string_init("pageIndex", sizeof("pageIndex") - 1, 1);
	zend_declare_typed_property(class_entry, property_pageIndex_name, &property_pageIndex_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_pageIndex_name);

	zval property_view_default_value;
	ZVAL_UNDEF(&property_view_default_value);
	zend_string *property_view_name = zend_string_init("view", sizeof("view") - 1, 1);
	zend_declare_typed_property(class_entry, property_view_name, &property_view_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_view_name);

	zval property_params_default_value;
	ZVAL_UNDEF(&property_params_default_value);
	zend_string *property_params_name = zend_string_init("params", sizeof("params") - 1, 1);
	zend_declare_typed_property(class_entry, property_params_name, &property_params_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_ARRAY));
	zend_string_release(property_params_name);

	zval property_x_default_value;
	ZVAL_UNDEF(&property_x_default_value);
	zend_string *property_x_name = zend_string_init("x", sizeof("x") - 1, 1);
	zend_declare_typed_property(class_entry, property_x_name, &property_x_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE|MAY_BE_NULL));
	zend_string_release(property_x_name);

	zval property_y_default_value;
	ZVAL_UNDEF(&property_y_default_value);
	zend_string *property_y_name = zend_string_init("y", sizeof("y") - 1, 1);
	zend_declare_typed_property(class_entry, property_y_name, &property_y_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE|MAY_BE_NULL));
	zend_string_release(property_y_name);

	zval property_zoom_default_value;
	ZVAL_UNDEF(&property_zoom_default_value);
	zend_string *property_zoom_name = zend_string_init("zoom", sizeof("zoom") - 1, 1);
	zend_declare_typed_property(class_entry, property_zoom_name, &property_zoom_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE|MAY_BE_NULL));
	zend_string_release(property_zoom_name);

	return class_entry;
}

static zend_class_entry *register_class_Pdfium_Action(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Action", class_Pdfium_Action_methods);
#if (PHP_VERSION_ID >= 80400)
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS);
#else
	class_entry = zend_register_internal_class_ex(&ce, NULL);
	class_entry->ce_flags |= ZEND_ACC_FINAL|ZEND_ACC_READONLY_CLASS;
#endif

	zval property_type_default_value;
	ZVAL_UNDEF(&property_type_default_value);
	zend_string *property_type_name = zend_string_init("type", sizeof("type") - 1, 1);
	zend_declare_typed_property(class_entry, property_type_name, &property_type_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release(property_type_name);

	zval property_uri_default_value;
	ZVAL_UNDEF(&property_uri_default_value);
	zend_string *property_uri_name = zend_string_init("uri", sizeof("uri") - 1, 1);
	zend_declare_typed_property(class_entry, property_uri_name, &property_uri_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_uri_name);

	zval property_filePath_default_value;
	ZVAL_UNDEF(&property_filePath_default_value);
	zend_string *property_filePath_name = zend_string_init("filePath", sizeof("filePath") - 1, 1);
	zend_declare_typed_property(class_entry, property_filePath_name, &property_filePath_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING|MAY_BE_NULL));
	zend_string_release(property_filePath_name);

	zval property_destination_default_value;
	ZVAL_UNDEF(&property_destination_default_value);
	zend_string *property_destination_name = zend_string_init("destination", sizeof("destination") - 1, 1);
	zend_string *property_destination_class_Pdfium_Destination = zend_string_init("Pdfium\\Destination", sizeof("Pdfium\\Destination")-1, 1);
	zend_declare_typed_property(class_entry, property_destination_name, &property_destination_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_destination_class_Pdfium_Destination, 0, MAY_BE_NULL));
	zend_string_release(property_destination_name);

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

static zend_class_entry *register_class_Pdfium_Attachment(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "Pdfium", "Attachment", class_Pdfium_Attachment_methods);
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
