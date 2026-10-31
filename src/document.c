#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_doc.h>
#include <fpdf_edit.h>
#include <fpdf_ppo.h>
#include <fpdf_save.h>
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
	if (!h->form_tried) {
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
	FPDF_CloseDocument(h->doc);
	if (h->buffer) {
		zend_string_release(h->buffer);
	}
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

	zend_string *bytes = pdfium_read_file(path);
	if (!bytes) {
		RETURN_THROWS();
	}
	FPDF_DOCUMENT doc = FPDF_LoadMemDocument64(ZSTR_VAL(bytes), ZSTR_LEN(bytes), password ? ZSTR_VAL(password) : NULL);
	if (!doc) {
		zend_string_release(bytes);
		pdfium_throw_last_error("Cannot open document");
		RETURN_THROWS();
	}
	zend_object *obj = pdfium_document_create(pdfium_doc_handle_create(doc, bytes));
	zend_string_release(bytes);
	RETURN_OBJ(obj);
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
		if (!pdfium_memory_fits((size_t) bytes / 2 * 5)) {
			zend_throw_exception_ex(pdfium_ce_memory_exception, 0,
				"The %s entry needs more memory than memory_limit leaves", entries[i].tag);
			RETURN_THROWS();
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

static zend_object *assemble(pdfium_doc_handle **sources, uint32_t nsources, const int *indexes, unsigned long count)
{
	FPDF_DOCUMENT doc = FPDF_CreateNewDocument();

	if (!doc) {
		zend_throw_exception(pdfium_ce_memory_exception, "Cannot create a new document", 0);
		return NULL;
	}
	pdfium_doc_handle *h = pdfium_doc_handle_create(doc, NULL);

	for (uint32_t i = 0; i < nsources; i++) {
		if (!FPDF_ImportPagesByIndex(h->doc, sources[i]->doc, indexes, count, FPDF_GetPageCount(h->doc))) {
			pdfium_doc_handle_release(h);
			zend_throw_exception(pdfium_ce_exception, "Cannot import pages", 0);
			return NULL;
		}
	}
	return pdfium_document_create(h);
}

static bool indexes_from(HashTable *ht, int page_count, int *out)
{
	zval *zv;

	ZEND_HASH_FOREACH_VAL(ht, zv) {
		ZVAL_DEREF(zv);
		if (Z_TYPE_P(zv) != IS_LONG) {
			zend_argument_type_error(1, "must contain only int, %s given", zend_zval_type_name(zv));
			return false;
		}
		if (!page_count) {
			zend_argument_value_error(1, "must contain only page indexes, but the document has no pages");
			return false;
		}
		if (Z_LVAL_P(zv) < 0 || Z_LVAL_P(zv) >= page_count) {
			zend_argument_value_error(1, "must contain only indexes between 0 and %d", page_count - 1);
			return false;
		}
		*out++ = (int) Z_LVAL_P(zv);
	} ZEND_HASH_FOREACH_END();
	return true;
}

ZEND_METHOD(Pdfium_Document, extractPages)
{
	HashTable *ht;
	pdfium_doc_handle *h = this_handle(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_ARRAY_HT(ht)
	ZEND_PARSE_PARAMETERS_END();

	uint32_t n = zend_hash_num_elements(ht);
	if (!n) {
		zend_argument_value_error(1, "must not be empty");
		RETURN_THROWS();
	}
	int *indexes = safe_emalloc(n, sizeof(int), 0);
	if (!indexes_from(ht, FPDF_GetPageCount(h->doc), indexes)) {
		efree(indexes);
		RETURN_THROWS();
	}
	zend_object *doc = assemble(&h, 1, indexes, n);
	efree(indexes);
	if (!doc) {
		RETURN_THROWS();
	}
	RETURN_OBJ(doc);
}

ZEND_METHOD(Pdfium_Document, merge)
{
	HashTable *ht;
	zval *zv;
	uint32_t n = 0;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_ARRAY_HT(ht)
	ZEND_PARSE_PARAMETERS_END();

	if (!zend_hash_num_elements(ht)) {
		zend_argument_value_error(1, "must not be empty");
		RETURN_THROWS();
	}
	pdfium_doc_handle **sources = safe_emalloc(zend_hash_num_elements(ht), sizeof(*sources), 0);
	ZEND_HASH_FOREACH_VAL(ht, zv) {
		ZVAL_DEREF(zv);
		if (Z_TYPE_P(zv) != IS_OBJECT || Z_OBJCE_P(zv) != pdfium_ce_document) {
			zend_argument_type_error(1, "must contain only Pdfium\\Document, %s given", zend_zval_type_name(zv));
			efree(sources);
			RETURN_THROWS();
		}
		sources[n++] = this_handle(zv);
	} ZEND_HASH_FOREACH_END();

	zend_object *doc = assemble(sources, n, NULL, 0);
	efree(sources);
	if (!doc) {
		RETURN_THROWS();
	}
	RETURN_OBJ(doc);
}

typedef struct {
	FPDF_FILEWRITE fw;
	pdfium_buf out;
} string_writer;

static int string_writer_write(FPDF_FILEWRITE *self, const void *data, unsigned long size)
{
	return pdfium_buf_append(&((string_writer *) self)->out, data, size);
}

static zend_string *serialise(pdfium_doc_handle *h, bool remove_security)
{
	string_writer w = {.fw = {.version = 1, .WriteBlock = string_writer_write}};

	if (!FPDF_SaveAsCopy(h->doc, &w.fw, FPDF_NO_INCREMENTAL | (remove_security ? FPDF_REMOVE_SECURITY : 0))
		|| w.out.oom) {
		smart_str_free(&w.out.s);
		zend_throw_exception(w.out.oom ? pdfium_ce_memory_exception : pdfium_ce_exception, w.out.oom
			? "Serialising the document needs more memory than memory_limit leaves" : "Cannot serialise the document", 0);
		return NULL;
	}
	return smart_str_extract(&w.out.s);
}

ZEND_METHOD(Pdfium_Document, toString)
{
	bool remove_security = false;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(remove_security)
	ZEND_PARSE_PARAMETERS_END();

	zend_string *bytes = serialise(this_handle(ZEND_THIS), remove_security);
	if (!bytes) {
		RETURN_THROWS();
	}
	RETURN_STR(bytes);
}

ZEND_METHOD(Pdfium_Document, save)
{
	char *path;
	size_t path_len;
	bool remove_security = false;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_PATH(path, path_len)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(remove_security)
	ZEND_PARSE_PARAMETERS_END();

	pdfium_write_and_release(serialise(this_handle(ZEND_THIS), remove_security), path);
}
