#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include "fopen_wrappers.h"
#include <fpdf_doc.h>
#include "zend_exceptions.h"

ZEND_METHOD(Pdfium_Document, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

pdfium_doc_handle *pdfium_doc_handle_create(FPDF_DOCUMENT doc, zend_string *buffer)
{
	pdfium_doc_handle *h = ecalloc(1, sizeof(*h));

	h->doc = doc;
	h->buffer = buffer ? zend_string_copy(buffer) : NULL;
	h->refs = 1;
	return h;
}

FPDF_FORMHANDLE pdfium_doc_handle_form(pdfium_doc_handle *h)
{
	if (!h->form_tried && FPDF_GetFormType(h->doc) != FORMTYPE_NONE) {
		h->form_tried = true;
		h->form_info.version = 2;
		h->form = FPDFDOC_InitFormFillEnvironment(h->doc, &h->form_info);
	}
	return h->form;
}

void pdfium_doc_handle_addref(pdfium_doc_handle *h)
{
	h->refs++;
}

void pdfium_doc_handle_release(pdfium_doc_handle *h)
{
	if (--h->refs) {
		return;
	}
	if (h->form) {
		FPDFDOC_ExitFormFillEnvironment(h->form);
	}
	if (h->doc) {
		FPDF_CloseDocument(h->doc);
	}
	if (h->buffer) {
		zend_string_release(h->buffer);
	}
	for (uint32_t i = 0; i < h->nsources; i++) {
		pdfium_doc_handle_release(h->sources[i]);
	}
	efree(h->sources);
	efree(h);
}

void pdfium_document_free_obj(zend_object *obj)
{
	pdfium_document *d = pdfium_document_from_obj(obj);

	if (d->h) {
		pdfium_doc_handle_release(d->h);
	}
	zend_object_std_dtor(obj);
}

zend_object *pdfium_document_create(pdfium_doc_handle *h)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_document);
	pdfium_document_from_obj(Z_OBJ(zv))->h = h;
	return Z_OBJ(zv);
}

ZEND_METHOD(Pdfium_Document, open)
{
	char *path;
	size_t path_len;
	zend_string *password = NULL;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_PATH(path, path_len)
		Z_PARAM_OPTIONAL
		Z_PARAM_PATH_STR_OR_NULL(password)
	ZEND_PARSE_PARAMETERS_END();

	if (php_check_open_basedir_ex(path, 0)) {
		zend_throw_exception_ex(pdfium_ce_file_exception, FPDF_ERR_FILE, "Cannot open %s", path);
		RETURN_THROWS();
	}

	FPDF_DOCUMENT doc = FPDF_LoadDocument(path, password ? ZSTR_VAL(password) : NULL);
	if (!doc) {
		pdfium_throw_last_error("Cannot open document");
		RETURN_THROWS();
	}
	RETURN_OBJ(pdfium_document_create(pdfium_doc_handle_create(doc, NULL)));
}

ZEND_METHOD(Pdfium_Document, fromString)
{
	zend_string *bytes, *password = NULL;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_STR(bytes)
		Z_PARAM_OPTIONAL
		Z_PARAM_PATH_STR_OR_NULL(password)
	ZEND_PARSE_PARAMETERS_END();

	if (!ZSTR_LEN(bytes)) {
		zend_argument_value_error(1, "must not be empty");
		RETURN_THROWS();
	}

	FPDF_DOCUMENT doc = FPDF_LoadMemDocument64(ZSTR_VAL(bytes), ZSTR_LEN(bytes), password ? ZSTR_VAL(password) : NULL);
	if (!doc) {
		pdfium_throw_last_error("Cannot open document");
		RETURN_THROWS();
	}
	RETURN_OBJ(pdfium_document_create(pdfium_doc_handle_create(doc, bytes)));
}

static pdfium_doc_handle *this_handle(zval *self)
{
	return pdfium_document_from_obj(Z_OBJ_P(self))->h;
}

ZEND_METHOD(Pdfium_Document, pageCount)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_LONG(FPDF_GetPageCount(this_handle(ZEND_THIS)->doc));
}

ZEND_METHOD(Pdfium_Document, metadata)
{
	static const struct { const char *tag, *key; } entries[] = {
		{"Title", "title"}, {"Author", "author"}, {"Subject", "subject"}, {"Keywords", "keywords"},
		{"Creator", "creator"}, {"Producer", "producer"}, {"CreationDate", "creationDate"}, {"ModDate", "modDate"},
	};
	FPDF_DOCUMENT doc = this_handle(ZEND_THIS)->doc;

	ZEND_PARSE_PARAMETERS_NONE();
	array_init(return_value);
	for (size_t i = 0; i < sizeof(entries) / sizeof(entries[0]); i++) {
		unsigned long bytes = FPDF_GetMetaText(doc, entries[i].tag, NULL, 0);

		if (bytes <= 2) {
			continue;
		}
		unsigned short *buf = emalloc(bytes);
		size_t units = 0;

		FPDF_GetMetaText(doc, entries[i].tag, buf, bytes);
		while (units < bytes / 2 && buf[units]) {
			units++;
		}
		zend_string *value = pdfium_utf16le_to_utf8(buf, units);
		efree(buf);
		if (ZSTR_LEN(value)) {
			add_assoc_str(return_value, entries[i].key, value);
		} else {
			zend_string_release(value);
		}
	}
}

ZEND_METHOD(Pdfium_Document, permissions)
{
	static const struct { int bit, r2_bit; const char *key; } flags[] = {
		{3, 3, "print"}, {4, 4, "modify"}, {5, 5, "copy"}, {6, 6, "annotate"}, {9, 6, "fillForms"},
		{10, 5, "extractForAccessibility"}, {11, 4, "assemble"}, {12, 3, "printHighQuality"},
	};
	FPDF_DOCUMENT doc = this_handle(ZEND_THIS)->doc;
	unsigned long perms = FPDF_GetDocUserPermissions(doc);
	bool r2 = FPDF_GetSecurityHandlerRevision(doc) == 2;

	ZEND_PARSE_PARAMETERS_NONE();
	array_init(return_value);
	for (size_t i = 0; i < sizeof(flags) / sizeof(flags[0]); i++) {
		int bit = r2 ? flags[i].r2_bit : flags[i].bit;
		bool allowed = (perms >> (bit - 1)) & 1;

		if (bit == 12) {
			allowed = allowed && ((perms >> 2) & 1);
		}
		add_assoc_bool(return_value, flags[i].key, allowed);
	}
}

ZEND_METHOD(Pdfium_Document, page)
{
	zend_long index;
	pdfium_doc_handle *h = this_handle(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(index)
	ZEND_PARSE_PARAMETERS_END();

	int page_count = FPDF_GetPageCount(h->doc);

	if (!page_count) {
		zend_argument_value_error(1, "must be a page index, but the document has no pages");
		RETURN_THROWS();
	}
	if (index < 0 || index >= page_count) {
		zend_argument_value_error(1, "must be between 0 and %d", page_count - 1);
		RETURN_THROWS();
	}
	zend_object *page = pdfium_page_create(h, (int) index);

	if (!page) {
		RETURN_THROWS();
	}
	RETURN_OBJ(page);
}

ZEND_METHOD(Pdfium_Document, pages)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ(pdfium_page_iterator_create(this_handle(ZEND_THIS)));
}
