#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include "ext/spl/spl_exceptions.h"
#include "ext/standard/info.h"
#include "fopen_wrappers.h"
#include "zend_exceptions.h"
#include "zend_interfaces.h"
#include "pdfium_arginfo.h"
#include <ctype.h>

zend_class_entry *pdfium_ce_document;
zend_class_entry *pdfium_ce_page;
zend_class_entry *pdfium_ce_page_iterator;
zend_class_entry *pdfium_ce_bitmap;
zend_class_entry *pdfium_ce_exception;
zend_class_entry *pdfium_ce_file_exception;
zend_class_entry *pdfium_ce_format_exception;
zend_class_entry *pdfium_ce_password_exception;
zend_class_entry *pdfium_ce_unsupported_exception;
zend_class_entry *pdfium_ce_memory_exception;

static zend_object_handlers document_handlers;
static zend_object_handlers page_handlers;
static zend_object_handlers page_iterator_handlers;
static zend_object_handlers bitmap_handlers;

void pdfium_throw_last_error(const char *context)
{
	unsigned long err = FPDF_GetLastError();
	zend_class_entry *ce = pdfium_ce_exception;
	const char *reason = "unknown error";

	switch (err) {
		case FPDF_ERR_FILE:
			ce = pdfium_ce_file_exception;
			reason = "file not found or could not be opened";
			break;
		case FPDF_ERR_FORMAT:
			ce = pdfium_ce_format_exception;
			reason = "not a PDF or corrupted";
			break;
		case FPDF_ERR_PAGE:
			ce = pdfium_ce_format_exception;
			reason = "page not found or content error";
			break;
		case FPDF_ERR_PASSWORD:
			ce = pdfium_ce_password_exception;
			reason = "password required or incorrect";
			break;
		case FPDF_ERR_SECURITY:
			ce = pdfium_ce_unsupported_exception;
			reason = "unsupported security scheme";
			break;
	}
	zend_throw_exception_ex(ce, (zend_long) err, "%s: %s", context, reason);
}

bool pdfium_memory_fits(size_t bytes)
{
	return PG(memory_limit) == -1 || zend_memory_usage(true) + bytes + ZEND_MM_CHUNK_SIZE <= (size_t) PG(memory_limit);
}

bool pdfium_buf_append(pdfium_buf *b, const void *data, size_t len)
{
	size_t used = smart_str_get_len(&b->s);

	if (b->oom || (used + len >= b->s.a && !pdfium_memory_fits(used + len))) {
		b->oom = true;
		return false;
	}
	smart_str_appendl(&b->s, data, len);
	return true;
}

static bool scheme_absent_or_registered(const char *path)
{
	HashTable *wrappers = php_stream_get_url_stream_wrappers_hash();
	size_t n = 0;

	while (isalnum((unsigned char) path[n]) || path[n] == '+' || path[n] == '-' || path[n] == '.') {
		n++;
	}
	if (n < 2 || path[n] != ':' || (strncmp(path + n + 1, "//", 2) != 0 && !(n == 4 && !memcmp(path, "data:", 5)))) {
		return true;
	}
	return zend_hash_str_find_ptr(wrappers, path, n) || zend_hash_str_find_ptr_lc(wrappers, path, n);
}

static bool removable_on_failure(php_stream_wrapper *wrapper, php_stream *stream)
{
	php_stream_statbuf ssb;

	if (!wrapper->wops->unlink) {
		return false;
	}
	return wrapper != &php_plain_files_wrapper || (php_stream_stat(stream, &ssb) == 0 && S_ISREG(ssb.sb.st_mode));
}

void pdfium_write_and_release(zend_string *data, const char *path)
{
	const char *local_path;
	php_stream_wrapper *wrapper = NULL;
	php_stream *stream = NULL;

	if (!data) {
		return;
	}
	if (scheme_absent_or_registered(path)) {
		wrapper = php_stream_locate_url_wrapper(path, &local_path, 0);
	}
	if (wrapper && (wrapper != &php_plain_files_wrapper || php_check_open_basedir_ex(local_path, 0) == 0)) {
		stream = php_stream_open_wrapper(path, "wb", 0, NULL);
	}
	if (stream) {
		stream->flags |= PHP_STREAM_FLAG_SUPPRESS_ERRORS;
		bool removable = removable_on_failure(wrapper, stream);
		bool written = php_stream_write(stream, ZSTR_VAL(data), ZSTR_LEN(data)) == (ssize_t) ZSTR_LEN(data);

		if (php_stream_close(stream) == 0 && written) {
			zend_string_release(data);
			return;
		}
		if (removable) {
			int error_reporting = EG(error_reporting);

			EG(error_reporting) = 0;
			wrapper->wops->unlink(wrapper, path, 0, NULL);
			EG(error_reporting) = error_reporting;
		}
	}
	zend_string_release(data);
	zend_throw_exception_ex(pdfium_ce_file_exception, 0, "Cannot write %s", path);
}

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

	FPDF_InitLibraryWithConfig(&config);

	pdfium_ce_exception = register_class_Pdfium_Exception(spl_ce_RuntimeException);
	pdfium_ce_file_exception = register_class_Pdfium_FileException(pdfium_ce_exception);
	pdfium_ce_format_exception = register_class_Pdfium_FormatException(pdfium_ce_exception);
	pdfium_ce_password_exception = register_class_Pdfium_PasswordException(pdfium_ce_exception);
	pdfium_ce_unsupported_exception = register_class_Pdfium_UnsupportedException(pdfium_ce_exception);
	pdfium_ce_memory_exception = register_class_Pdfium_MemoryException(pdfium_ce_exception);

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
	NULL,
	NULL,
	PHP_MINFO(pdfium),
	PHP_PDFIUM_VERSION,
	STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_PDFIUM
ZEND_GET_MODULE(pdfium)
#endif
