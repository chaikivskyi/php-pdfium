#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_doc.h>
#include <fpdf_edit.h>
#include <fpdf_ppo.h>
#include <fpdf_save.h>
#include "zend_exceptions.h"

static void throw_error_code(unsigned long err, const char *context)
{
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
	zend_throw_exception_ex(ce, 0, "%s: %s", context, reason);
}

ZEND_METHOD(Pdfium_Document, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Metadata, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_Permissions, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

static pdfium_doc_handle *doc_handle_create(zend_string *buffer)
{
	pdfium_doc_handle *h = ecalloc(1, sizeof(*h));

	h->buffer = buffer;
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
	if (h->doc) {
		pdfium_enter_wait();
		if (h->form) {
			FPDFDOC_ExitFormFillEnvironment(h->form);
		}
		FPDF_CloseDocument(h->doc);
		pdfium_leave();
	}
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

static zend_object *new_document(pdfium_doc_handle *h)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_document);
	pdfium_document_from_obj(Z_OBJ(zv))->h = h;
	return Z_OBJ(zv);
}

static void load_document(pdfium_doc_handle *h, zend_string *password, zval *return_value)
{
	zend_object *obj = new_document(h);

	if (!pdfium_enter()) {
		OBJ_RELEASE(obj);
		RETURN_THROWS();
	}
	h->doc = FPDF_LoadMemDocument64(ZSTR_VAL(h->buffer), ZSTR_LEN(h->buffer), password ? ZSTR_VAL(password) : NULL);
	unsigned long err = h->doc ? 0 : FPDF_GetLastError();

	pdfium_leave();
	if (!h->doc) {
		OBJ_RELEASE(obj);
		throw_error_code(err, "Cannot open document");
		RETURN_THROWS();
	}
	RETURN_OBJ(obj);
}

ZEND_METHOD(Pdfium_Document, fromFile)
{
	char *path;
	size_t path_len;
	zend_string *password = NULL;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_PATH(path, path_len)
		Z_PARAM_OPTIONAL
		Z_PARAM_PATH_STR_OR_NULL(password)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_path_given(path_len)) {
		RETURN_THROWS();
	}

	zend_string *bytes = pdfium_read_file(path);
	if (!bytes) {
		RETURN_THROWS();
	}
	load_document(doc_handle_create(bytes), password, return_value);
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

	load_document(doc_handle_create(zend_string_copy(bytes)), password, return_value);
}

static pdfium_doc_handle *this_handle(zval *self)
{
	return pdfium_document_from_obj(Z_OBJ_P(self))->h;
}

ZEND_METHOD(Pdfium_Document, pageCount)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = FPDF_GetPageCount(this_handle(ZEND_THIS)->doc);
	pdfium_leave();
	RETURN_LONG(count);
}

ZEND_METHOD(Pdfium_Document, metadata)
{
	static const struct { const char *tag, *key; } entries[] = {
		{"Title", "title"}, {"Author", "author"}, {"Subject", "subject"}, {"Keywords", "keywords"},
		{"Creator", "creator"}, {"Producer", "producer"}, {"CreationDate", "creationDate"}, {"ModDate", "modDate"},
	};
	FPDF_DOCUMENT doc = this_handle(ZEND_THIS)->doc;
	unsigned short *bufs[sizeof(entries) / sizeof(entries[0])] = {0};
	size_t units[sizeof(entries) / sizeof(entries[0])] = {0};
	size_t n = sizeof(entries) / sizeof(entries[0]), i, failed = n, converted = 0;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	for (i = 0; i < n; i++) {
		unsigned long bytes = FPDF_GetMetaText(doc, entries[i].tag, NULL, 0);

		if (bytes <= 2) {
			continue;
		}
		converted += (size_t) bytes / 2 * 3;
		if (!pdfium_memory_fits(bytes + converted)) {
			failed = i;
			break;
		}
		bufs[i] = emalloc(bytes);
		FPDF_GetMetaText(doc, entries[i].tag, bufs[i], bytes);
		while (units[i] < bytes / 2 && bufs[i][units[i]]) {
			units[i]++;
		}
	}
	pdfium_leave();

	if (failed == n) {
		object_init_ex(return_value, pdfium_ce_metadata);
	}
	for (i = 0; i < n; i++) {
		if (failed == n) {
			zval value;

			if (units[i]) {
				ZVAL_STR(&value, pdfium_utf16le_to_utf8(bufs[i], units[i]));
			} else {
				ZVAL_NULL(&value);
			}
			zend_update_property(pdfium_ce_metadata, Z_OBJ_P(return_value), entries[i].key, strlen(entries[i].key), &value);
			zval_ptr_dtor(&value);
		}
		if (bufs[i]) {
			efree(bufs[i]);
		}
	}
	if (failed != n) {
		zend_throw_exception_ex(pdfium_ce_memory_exception, 0,
			"The %s entry needs more memory than memory_limit leaves", entries[failed].tag);
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Document, permissions)
{
	static const struct { int bit, r2_bit; const char *key; } flags[] = {
		{3, 3, "print"}, {4, 4, "modify"}, {5, 5, "copy"}, {6, 6, "annotate"}, {9, 6, "fillForms"},
		{10, 5, "extractForAccessibility"}, {11, 4, "assemble"}, {12, 3, "printHighQuality"},
	};
	FPDF_DOCUMENT doc = this_handle(ZEND_THIS)->doc;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	unsigned long perms = FPDF_GetDocUserPermissions(doc);
	bool r2 = FPDF_GetSecurityHandlerRevision(doc) == 2;
	pdfium_leave();

	object_init_ex(return_value, pdfium_ce_permissions);
	for (size_t i = 0; i < sizeof(flags) / sizeof(flags[0]); i++) {
		int bit = r2 ? flags[i].r2_bit : flags[i].bit;
		bool allowed = (perms >> (bit - 1)) & 1;

		if (bit == 12) {
			allowed = allowed && ((perms >> 2) & 1);
		}
		zend_update_property_bool(pdfium_ce_permissions, Z_OBJ_P(return_value), flags[i].key, strlen(flags[i].key), allowed);
	}
}

ZEND_METHOD(Pdfium_Document, page)
{
	zend_long index;
	int page_count;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(index)
	ZEND_PARSE_PARAMETERS_END();

	zend_object *obj = pdfium_page_load(this_handle(ZEND_THIS), index, &page_count);

	if (obj) {
		RETURN_OBJ(obj);
	}
	if (!EG(exception)) {
		if (!page_count) {
			zend_argument_value_error(1, "must be a page index, but the document has no pages");
		} else {
			zend_argument_value_error(1, "must be between 0 and %d", page_count - 1);
		}
	}
	RETURN_THROWS();
}

ZEND_METHOD(Pdfium_Document, pages)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ(pdfium_page_iterator_create(this_handle(ZEND_THIS)));
}

typedef enum { ASSEMBLED, CANNOT_CREATE, CANNOT_IMPORT } assemble_status;

static assemble_status assemble(pdfium_doc_handle **sources, uint32_t nsources, const int *indexes, unsigned long count,
	pdfium_doc_handle *h)
{
	FPDF_DOCUMENT doc = FPDF_CreateNewDocument();

	if (!doc) {
		return CANNOT_CREATE;
	}
	for (uint32_t i = 0; i < nsources; i++) {
		if (!FPDF_ImportPagesByIndex(doc, sources[i]->doc, indexes, count, FPDF_GetPageCount(doc))) {
			FPDF_CloseDocument(doc);
			return CANNOT_IMPORT;
		}
	}
	FPDF_CopyViewerPreferences(doc, sources[0]->doc);
	h->doc = doc;
	return ASSEMBLED;
}

static void throw_assemble_error(assemble_status status)
{
	if (status == CANNOT_CREATE) {
		zend_throw_exception(pdfium_ce_memory_exception, "Cannot create a new document", 0);
	} else {
		zend_throw_exception(pdfium_ce_exception, "Cannot import pages", 0);
	}
}

typedef enum { INDEXES_OK, INDEXES_NO_PAGES, INDEXES_OUT_OF_RANGE } indexes_status;

static indexes_status indexes_from(HashTable *ht, int page_count, int *out)
{
	zval *zv;

	if (!page_count) {
		return INDEXES_NO_PAGES;
	}
	ZEND_HASH_FOREACH_VAL(ht, zv) {
		ZVAL_DEREF(zv);
		if (Z_LVAL_P(zv) < 0 || Z_LVAL_P(zv) >= page_count) {
			return INDEXES_OUT_OF_RANGE;
		}
		*out++ = (int) Z_LVAL_P(zv);
	} ZEND_HASH_FOREACH_END();
	return INDEXES_OK;
}

static void throw_indexes_error(indexes_status status, int page_count)
{
	if (status == INDEXES_NO_PAGES) {
		zend_argument_value_error(1, "must contain only page indexes, but the document has no pages");
	} else {
		zend_argument_value_error(1, "must contain only indexes between 0 and %d", page_count - 1);
	}
}

static bool is_encrypted(pdfium_doc_handle *h)
{
	return FPDF_GetSecurityHandlerRevision(h->doc) != -1;
}

ZEND_METHOD(Pdfium_Document, extractPages)
{
	HashTable *ht;
	zval *zv;
	bool remove_security = false;
	pdfium_doc_handle *h = this_handle(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_ARRAY_HT(ht)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(remove_security)
	ZEND_PARSE_PARAMETERS_END();

	uint32_t n = zend_hash_num_elements(ht);
	if (!n) {
		zend_argument_value_error(1, "must not be empty");
		RETURN_THROWS();
	}
	ZEND_HASH_FOREACH_VAL(ht, zv) {
		ZVAL_DEREF(zv);
		if (Z_TYPE_P(zv) != IS_LONG) {
			zend_argument_type_error(1, "must contain only int, %s given", zend_zval_type_name(zv));
			RETURN_THROWS();
		}
	} ZEND_HASH_FOREACH_END();
	int *indexes = safe_emalloc(n, sizeof(int), 0);
	pdfium_doc_handle *out = doc_handle_create(NULL);
	zend_object *obj = new_document(out);

	if (!pdfium_enter()) {
		OBJ_RELEASE(obj);
		efree(indexes);
		RETURN_THROWS();
	}
	int page_count = FPDF_GetPageCount(h->doc);
	indexes_status valid = indexes_from(ht, page_count, indexes);
	bool refused = valid == INDEXES_OK && !remove_security && is_encrypted(h);

	if (valid != INDEXES_OK || refused) {
		pdfium_leave();
		OBJ_RELEASE(obj);
		efree(indexes);
		if (refused) {
			zend_throw_exception(pdfium_ce_unsupported_exception, "The document is encrypted and PDFium cannot "
				"encrypt the result; pass removeSecurity: true to extract its pages unencrypted", 0);
		} else {
			throw_indexes_error(valid, page_count);
		}
		RETURN_THROWS();
	}
	assemble_status status = assemble(&h, 1, indexes, n, out);

	pdfium_leave();
	efree(indexes);
	if (status != ASSEMBLED) {
		OBJ_RELEASE(obj);
		throw_assemble_error(status);
		RETURN_THROWS();
	}
	RETURN_OBJ(obj);
}

static void throw_merge_encrypted(HashTable *ht, uint32_t position)
{
	zend_ulong num = 0;
	zend_string *key = NULL;
	uint32_t i = 0;

	ZEND_HASH_FOREACH_KEY(ht, num, key) {
		if (i++ == position) {
			break;
		}
	} ZEND_HASH_FOREACH_END();
	if (key) {
		zend_throw_exception_ex(pdfium_ce_unsupported_exception, 0, "The document at index \"%s\" is encrypted and "
			"PDFium cannot encrypt the result; pass removeSecurity: true to merge it unencrypted", ZSTR_VAL(key));
	} else {
		zend_throw_exception_ex(pdfium_ce_unsupported_exception, 0, "The document at index " ZEND_ULONG_FMT " is "
			"encrypted and PDFium cannot encrypt the result; pass removeSecurity: true to merge it unencrypted", num);
	}
}

ZEND_METHOD(Pdfium_Document, merge)
{
	HashTable *ht;
	zval *zv;
	bool remove_security = false;
	uint32_t n = 0;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_ARRAY_HT(ht)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(remove_security)
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

	pdfium_doc_handle *out = doc_handle_create(NULL);
	zend_object *obj = new_document(out);

	if (!pdfium_enter()) {
		OBJ_RELEASE(obj);
		efree(sources);
		RETURN_THROWS();
	}
	uint32_t encrypted = n;

	for (uint32_t i = 0; !remove_security && i < n && encrypted == n; i++) {
		if (is_encrypted(sources[i])) {
			encrypted = i;
		}
	}
	if (encrypted < n) {
		pdfium_leave();
		OBJ_RELEASE(obj);
		efree(sources);
		throw_merge_encrypted(ht, encrypted);
		RETURN_THROWS();
	}
	assemble_status status = assemble(sources, n, NULL, 0, out);

	pdfium_leave();
	efree(sources);
	if (status != ASSEMBLED) {
		OBJ_RELEASE(obj);
		throw_assemble_error(status);
		RETURN_THROWS();
	}
	RETURN_OBJ(obj);
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

	if (!pdfium_enter()) {
		return NULL;
	}
	bool saved = FPDF_SaveAsCopy(h->doc, &w.fw, FPDF_NO_INCREMENTAL | (remove_security ? FPDF_REMOVE_SECURITY : 0));

	pdfium_leave();
	if (!saved || w.out.oom) {
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

	if (!pdfium_path_given(path_len)) {
		RETURN_THROWS();
	}
	pdfium_write_and_release(serialise(this_handle(ZEND_THIS), remove_security), path);
}
