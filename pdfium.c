#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include "ext/spl/spl_exceptions.h"
#include "ext/standard/info.h"
#include "zend_interfaces.h"
#include "pdfium_arginfo.h"

zend_class_entry *pdfium_ce_document;
zend_class_entry *pdfium_ce_page;
zend_class_entry *pdfium_ce_page_iterator;
zend_class_entry *pdfium_ce_bitmap;
zend_class_entry *pdfium_ce_metadata;
zend_class_entry *pdfium_ce_permissions;
zend_class_entry *pdfium_ce_exception;
zend_class_entry *pdfium_ce_file_exception;
zend_class_entry *pdfium_ce_format_exception;
zend_class_entry *pdfium_ce_password_exception;
zend_class_entry *pdfium_ce_unsupported_exception;
zend_class_entry *pdfium_ce_memory_exception;
zend_class_entry *pdfium_ce_busy_exception;

static zend_object_handlers document_handlers;
static zend_object_handlers page_handlers;
static zend_object_handlers page_iterator_handlers;
static zend_object_handlers bitmap_handlers;

static zend_object *object_create(zend_class_entry *ce, size_t size, const zend_object_handlers *handlers)
{
	zend_object *obj = (zend_object *) ((char *) zend_object_alloc(size, ce) + handlers->offset);

	zend_object_std_init(obj, ce);
	object_properties_init(obj, ce);
	obj->handlers = handlers;
	return obj;
}

static zend_object *document_create(zend_class_entry *ce)
{
	return object_create(ce, sizeof(pdfium_document), &document_handlers);
}

static zend_object *page_create(zend_class_entry *ce)
{
	return object_create(ce, sizeof(pdfium_page), &page_handlers);
}

static zend_object *page_iterator_create(zend_class_entry *ce)
{
	return object_create(ce, sizeof(pdfium_page_iterator), &page_iterator_handlers);
}

static zend_object *bitmap_create(zend_class_entry *ce)
{
	return object_create(ce, sizeof(pdfium_bitmap), &bitmap_handlers);
}

static void init_handlers(zend_object_handlers *h, int offset, zend_object_free_obj_t free_obj)
{
	memcpy(h, &std_object_handlers, sizeof(*h));
	h->offset = offset;
	h->free_obj = free_obj;
	h->clone_obj = NULL;
	h->compare = zend_objects_not_comparable;
}

PHP_MINIT_FUNCTION(pdfium)
{
	FPDF_LIBRARY_CONFIG config = {.version = 2};

#ifdef ZTS
	if (type != MODULE_PERSISTENT) {
		php_error_docref(NULL, E_WARNING, "pdfium cannot be loaded with dl() on ZTS PHP; load it with extension=pdfium");
		return FAILURE;
	}
#endif
	ZEND_TSRMLS_CACHE_UPDATE();
	FPDF_InitLibraryWithConfig(&config);
	pdfium_guard_startup();

	pdfium_ce_exception = register_class_Pdfium_PdfiumException(spl_ce_RuntimeException);
	pdfium_ce_file_exception = register_class_Pdfium_FileException(pdfium_ce_exception);
	pdfium_ce_format_exception = register_class_Pdfium_FormatException(pdfium_ce_exception);
	pdfium_ce_password_exception = register_class_Pdfium_PasswordException(pdfium_ce_exception);
	pdfium_ce_unsupported_exception = register_class_Pdfium_UnsupportedException(pdfium_ce_exception);
	pdfium_ce_memory_exception = register_class_Pdfium_MemoryException(pdfium_ce_exception);
	pdfium_ce_busy_exception = register_class_Pdfium_BusyException(pdfium_ce_exception);
	pdfium_ce_metadata = register_class_Pdfium_Metadata();
	pdfium_ce_permissions = register_class_Pdfium_Permissions();

	/* Before Document: its getIterator() return type narrows IteratorAggregate's to PageIterator. */
	init_handlers(&page_iterator_handlers, XtOffsetOf(pdfium_page_iterator, std), pdfium_page_iterator_free_obj);
	pdfium_ce_page_iterator = register_class_Pdfium_PageIterator(zend_ce_iterator);
	pdfium_ce_page_iterator->create_object = page_iterator_create;

	init_handlers(&document_handlers, XtOffsetOf(pdfium_document, std), pdfium_document_free_obj);
	pdfium_ce_document = register_class_Pdfium_Document(zend_ce_countable, zend_ce_aggregate);
	pdfium_ce_document->create_object = document_create;

	init_handlers(&page_handlers, XtOffsetOf(pdfium_page, std), pdfium_page_free_obj);
	pdfium_ce_page = register_class_Pdfium_Page();
	pdfium_ce_page->create_object = page_create;

	init_handlers(&bitmap_handlers, XtOffsetOf(pdfium_bitmap, std), pdfium_bitmap_free_obj);
	pdfium_ce_bitmap = register_class_Pdfium_Bitmap();
	pdfium_ce_bitmap->create_object = bitmap_create;

	return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(pdfium)
{
	FPDF_DestroyLibrary();
	return SUCCESS;
}

#ifdef ZTS
PHP_RINIT_FUNCTION(pdfium)
{
	ZEND_TSRMLS_CACHE_UPDATE();
	return SUCCESS;
}
#define PDFIUM_RINIT PHP_RINIT(pdfium)
#else
#define PDFIUM_RINIT NULL
#endif

PHP_MINFO_FUNCTION(pdfium)
{
	php_info_print_table_start();
	php_info_print_table_row(2, "pdfium support", "enabled");
	php_info_print_table_row(2, "Extension version", PHP_PDFIUM_VERSION);
	php_info_print_table_row(2, "PDFium version", PHP_PDFIUM_LIB_VERSION);
	php_info_print_table_end();
}

static const zend_module_dep pdfium_deps[] = {
	ZEND_MOD_REQUIRED("spl")
	ZEND_MOD_END
};

zend_module_entry pdfium_module_entry = {
	STANDARD_MODULE_HEADER_EX,
	NULL,
	pdfium_deps,
	"pdfium",
	NULL,
	PHP_MINIT(pdfium),
	PHP_MSHUTDOWN(pdfium),
	PDFIUM_RINIT,
	NULL,
	PHP_MINFO(pdfium),
	PHP_PDFIUM_VERSION,
	STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_PDFIUM
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif
ZEND_GET_MODULE(pdfium)
#endif
