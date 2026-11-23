#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_catalog.h>
#include <fpdf_doc.h>
#include <fpdf_edit.h>
#include <fpdf_ppo.h>
#include <fpdf_save.h>
#include "zend_exceptions.h"
#include <limits.h>

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

const char pdfium_read_failed_message[] = "The document's file could not be read in full: "
	"a read failed, or the file changed or was truncated after the document was opened";

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
	pdfium_local_close(&h->file);
	if (h->buffer) {
		zend_string_release(h->buffer);
	}
	efree(h);
}

static zend_object *new_document(pdfium_doc_handle *h)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_document);
	pdfium_document_from_obj(Z_OBJ(zv))->h = h;
	return Z_OBJ(zv);
}

static void load_into(zend_object *obj, zend_string *password, zval *return_value)
{
	pdfium_doc_handle *h = pdfium_document_from_obj(obj)->h;
	const char *pw = password ? ZSTR_VAL(password) : NULL;

	if (!pdfium_enter()) {
		OBJ_RELEASE(obj);
		RETURN_THROWS();
	}
	h->doc = h->buffer ? FPDF_LoadMemDocument64(ZSTR_VAL(h->buffer), ZSTR_LEN(h->buffer), pw) : FPDF_LoadCustomDocument(&h->file.access, pw);
	unsigned long err = h->doc ? 0 : FPDF_GetLastError();

	pdfium_leave();
	bool unread = h->file.read_failed;

	if (!h->doc || unread) {
		OBJ_RELEASE(obj);
		if (unread) {
			zend_throw_exception(pdfium_ce_file_exception, "Cannot open document: the file could not be read", 0);
		} else {
			throw_error_code(err, "Cannot open document");
		}
		RETURN_THROWS();
	}
	RETURN_OBJ(obj);
}

static void load_lazily(const char *path, zend_string *password, zval *return_value)
{
	pdfium_doc_handle *h = doc_handle_create(NULL);
	zend_object *obj = new_document(h);

	if (!pdfium_local_open(&h->file, path)) {
		OBJ_RELEASE(obj);
		RETURN_THROWS();
	}
	load_into(obj, password, return_value);
}

ZEND_METHOD(Pdfium_Document, fromFile)
{
	char *path;
	size_t path_len;
	zend_string *password = NULL;
	bool lazy = false;

	ZEND_PARSE_PARAMETERS_START(1, 3)
		Z_PARAM_PATH(path, path_len)
		Z_PARAM_OPTIONAL
		Z_PARAM_PATH_STR_OR_NULL(password)
		Z_PARAM_BOOL(lazy)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_path_given(path_len)) {
		RETURN_THROWS();
	}
	if (lazy) {
		load_lazily(path, password, return_value);
		return;
	}

	zend_string *bytes = pdfium_read_file(path);
	if (!bytes) {
		RETURN_THROWS();
	}
	load_into(new_document(doc_handle_create(bytes)), password, return_value);
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

	load_into(new_document(doc_handle_create(zend_string_copy(bytes))), password, return_value);
}

ZEND_METHOD(Pdfium_Document, pageCount)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = FPDF_GetPageCount(pdfium_this_document(ZEND_THIS)->doc);
	pdfium_leave();
	RETURN_LONG(count);
}

typedef struct { FPDF_DOCUMENT doc; const char *tag; } meta_source;

static unsigned long meta_get(void *source, unsigned short *buf, unsigned long bytes)
{
	meta_source *m = source;

	return FPDF_GetMetaText(m->doc, m->tag, buf, bytes);
}

ZEND_METHOD(Pdfium_Document, metadata)
{
	static const struct { const char *tag, *key; } entries[] = {
		{"Title", "title"}, {"Author", "author"}, {"Subject", "subject"}, {"Keywords", "keywords"},
		{"Creator", "creator"}, {"Producer", "producer"}, {"CreationDate", "creationDate"}, {"ModDate", "modDate"},
	};
	FPDF_DOCUMENT doc = pdfium_this_document(ZEND_THIS)->doc;
	zend_string *values[sizeof(entries) / sizeof(entries[0])] = {0};
	size_t n = sizeof(entries) / sizeof(entries[0]), i, failed = n, converted = 0;
	bool oom = false;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	for (i = 0; i < n && !oom; i++) {
		meta_source m = {doc, entries[i].tag};

		values[i] = pdfium_utf16le_fetch_under_limit(meta_get, &m, &converted, &oom);
		if (oom) {
			failed = i;
		}
	}
	pdfium_leave();

	if (failed != n) {
		for (i = 0; i < n; i++) {
			if (values[i]) {
				zend_string_release(values[i]);
			}
		}
		zend_throw_exception_ex(pdfium_ce_memory_exception, 0,
			"The %s entry needs more memory than memory_limit leaves", entries[failed].tag);
		RETURN_THROWS();
	}
	object_init_ex(return_value, pdfium_ce_metadata);
	for (i = 0; i < n; i++) {
		zval value;

		if (values[i]) {
			ZVAL_STR(&value, values[i]);
		} else {
			ZVAL_NULL(&value);
		}
		zend_update_property(pdfium_ce_metadata, Z_OBJ_P(return_value), entries[i].key, strlen(entries[i].key), &value);
		zval_ptr_dtor(&value);
	}
}

ZEND_METHOD(Pdfium_Document, isTagged)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	bool tagged = FPDFCatalog_IsTagged(pdfium_this_document(ZEND_THIS)->doc);
	pdfium_leave();
	RETURN_BOOL(tagged);
}

static unsigned long language_get(void *source, unsigned short *buf, unsigned long bytes)
{
	return FPDFCatalog_GetLanguage((FPDF_DOCUMENT) source, buf, bytes);
}

ZEND_METHOD(Pdfium_Document, language)
{
	size_t used = 0;
	bool oom = false;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	zend_string *language = pdfium_utf16le_fetch_under_limit(language_get, pdfium_this_document(ZEND_THIS)->doc, &used, &oom);
	pdfium_leave();

	if (oom) {
		zend_throw_exception(pdfium_ce_memory_exception, "The document language needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	if (!language) {
		RETURN_NULL();
	}
	RETURN_STR(language);
}

ZEND_METHOD(Pdfium_Document, setLanguage)
{
	FPDF_DOCUMENT doc = pdfium_this_document(ZEND_THIS)->doc;
	zend_string *language;
	size_t units = 0;
	const char *bad;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_STR(language)
	ZEND_PARSE_PARAMETERS_END();

	if ((bad = pdfium_pdf_text_fault(language, &units))) {
		zend_argument_value_error(1, "%s", bad);
		RETURN_THROWS();
	}
	if (!pdfium_memory_fits((units + 1) * sizeof(unsigned short))) {
		zend_throw_exception(pdfium_ce_memory_exception, "The language needs more memory than memory_limit leaves", 0);
		RETURN_THROWS();
	}
	unsigned short *wide = pdfium_utf8_to_utf16le_sized(language, units);
	bool entered = pdfium_enter(), set = false;

	if (entered) {
		set = FPDFCatalog_SetLanguage(doc, wide);
		pdfium_leave();
	}
	efree(wide);
	if (!entered) {
		RETURN_THROWS();
	}
	if (!set) {
		zend_throw_exception(pdfium_ce_exception, "Cannot set the document language", 0);
		RETURN_THROWS();
	}
}

ZEND_METHOD(Pdfium_Document, permissions)
{
	static const struct { int bit, r2_bit; const char *key; } flags[] = {
		{3, 3, "print"}, {4, 4, "modify"}, {5, 5, "copy"}, {6, 6, "annotate"}, {9, 6, "fillForms"},
		{10, 5, "extractForAccessibility"}, {11, 4, "assemble"}, {12, 3, "printHighQuality"},
	};
	FPDF_DOCUMENT doc = pdfium_this_document(ZEND_THIS)->doc;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int revision = FPDF_GetSecurityHandlerRevision(doc);
	unsigned long perms = revision == -1 ? 0xFFFFFFFFUL : FPDF_GetDocUserPermissions(doc);
	bool r2 = revision == 2;
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

static void file_id(INTERNAL_FUNCTION_PARAMETERS, FPDF_FILEIDTYPE type)
{
	FPDF_DOCUMENT doc = pdfium_this_document(ZEND_THIS)->doc;
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	unsigned long bytes = FPDF_GetFileIdentifier(doc, type, NULL, 0);

	if (bytes > 1) {
		if (!pdfium_memory_fits(bytes)) {
			oom = "The file identifier needs";
		} else {
			zend_string *s = zend_string_alloc(bytes - 1, 0);

			if (FPDF_GetFileIdentifier(doc, type, ZSTR_VAL(s), bytes) == bytes) {
				ZSTR_VAL(s)[bytes - 1] = '\0';
				RETVAL_STR(s);
			} else {
				zend_string_efree(s);
			}
		}
	}
	pdfium_leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_Document, permanentId)
{
	file_id(INTERNAL_FUNCTION_PARAM_PASSTHRU, FILEIDTYPE_PERMANENT);
}

ZEND_METHOD(Pdfium_Document, changingId)
{
	file_id(INTERNAL_FUNCTION_PARAM_PASSTHRU, FILEIDTYPE_CHANGING);
}

ZEND_METHOD(Pdfium_Document, page)
{
	zend_long index;
	int page_count;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(index)
	ZEND_PARSE_PARAMETERS_END();

	zend_object *obj = pdfium_page_load(pdfium_this_document(ZEND_THIS), index, &page_count);

	if (obj) {
		RETURN_OBJ(obj);
	}
	if (!EG(exception)) {
		pdfium_index_error(1, page_count, "a page index", "the document has no pages");
	}
	RETURN_THROWS();
}

ZEND_METHOD(Pdfium_Document, pages)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ(pdfium_page_iterator_create(pdfium_this_document(ZEND_THIS)));
}

typedef enum { ASSEMBLED, CANNOT_CREATE, CANNOT_IMPORT, SOURCE_UNREADABLE } assemble_status;

static bool any_read_failed(pdfium_doc_handle **sources, uint32_t nsources)
{
	for (uint32_t i = 0; i < nsources; i++) {
		if (sources[i]->file.read_failed) {
			return true;
		}
	}
	return false;
}

static assemble_status assemble(pdfium_doc_handle **sources, uint32_t nsources, const int *indexes, unsigned long count,
	pdfium_doc_handle *h)
{
	FPDF_DOCUMENT doc = FPDF_CreateNewDocument();
	bool imported = true;

	if (!doc) {
		return CANNOT_CREATE;
	}
	for (uint32_t i = 0; imported && i < nsources; i++) {
		imported = FPDF_ImportPagesByIndex(doc, sources[i]->doc, indexes, count, FPDF_GetPageCount(doc));
	}
	if (imported) {
		FPDF_CopyViewerPreferences(doc, sources[0]->doc);
	}
	assemble_status status = any_read_failed(sources, nsources) ? SOURCE_UNREADABLE : imported ? ASSEMBLED : CANNOT_IMPORT;

	if (status != ASSEMBLED) {
		FPDF_CloseDocument(doc);
		return status;
	}
	h->doc = doc;
	return ASSEMBLED;
}

static void throw_assemble_error(assemble_status status)
{
	if (status == CANNOT_CREATE) {
		zend_throw_exception(pdfium_ce_memory_exception, "Cannot create a new document", 0);
	} else if (status == SOURCE_UNREADABLE) {
		zend_throw_exception(pdfium_ce_format_exception, pdfium_read_failed_message, 0);
	} else {
		zend_throw_exception(pdfium_ce_exception, "Cannot import pages", 0);
	}
}

pdfium_indexes_status pdfium_indexes_from(HashTable *ht, int page_count, int *out)
{
	zval *zv;

	if (!page_count) {
		return PDFIUM_INDEXES_NO_PAGES;
	}
	ZEND_HASH_FOREACH_VAL(ht, zv) {
		ZVAL_DEREF(zv);
		if (Z_LVAL_P(zv) < 0 || Z_LVAL_P(zv) >= page_count) {
			return PDFIUM_INDEXES_OUT_OF_RANGE;
		}
		*out++ = (int) Z_LVAL_P(zv);
	} ZEND_HASH_FOREACH_END();
	return PDFIUM_INDEXES_OK;
}

void pdfium_throw_indexes_error(pdfium_indexes_status status, int page_count, uint32_t arg)
{
	if (status == PDFIUM_INDEXES_NO_PAGES) {
		zend_argument_value_error(arg, "must contain only page indexes, but the document has no pages");
	} else {
		zend_argument_value_error(arg, "must contain only indexes between 0 and %d", page_count - 1);
	}
}

static bool indexes_are_ints(HashTable *ht, uint32_t arg)
{
	zval *zv;

	ZEND_HASH_FOREACH_VAL(ht, zv) {
		ZVAL_DEREF(zv);
		if (Z_TYPE_P(zv) != IS_LONG) {
			zend_argument_type_error(arg, "must contain only int, %s given", zend_zval_type_name(zv));
			return false;
		}
	} ZEND_HASH_FOREACH_END();
	return true;
}

void pdfium_indexes_memory_error(void)
{
	zend_throw_exception(pdfium_ce_memory_exception, "The page indexes need more memory than memory_limit leaves", 0);
}

int *pdfium_indexes_checked(HashTable *ht, uint32_t arg, uint32_t *count)
{
	uint32_t n = zend_hash_num_elements(ht);

	if (!n) {
		zend_argument_value_error(arg, "must not be empty");
		return NULL;
	}
	if (!indexes_are_ints(ht, arg)) {
		return NULL;
	}
	if (!pdfium_memory_fits((size_t) n * sizeof(int))) {
		pdfium_indexes_memory_error();
		return NULL;
	}
	*count = n;
	return safe_emalloc(n, sizeof(int), 0);
}

bool pdfium_is_encrypted(pdfium_doc_handle *h)
{
	return FPDF_GetSecurityHandlerRevision(h->doc) != -1;
}

ZEND_METHOD(Pdfium_Document, extractPages)
{
	HashTable *ht;
	bool remove_security = false;
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_ARRAY_HT(ht)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(remove_security)
	ZEND_PARSE_PARAMETERS_END();

	uint32_t n;
	int *indexes = pdfium_indexes_checked(ht, 1, &n);

	if (!indexes) {
		RETURN_THROWS();
	}
	pdfium_doc_handle *out = doc_handle_create(NULL);
	zend_object *obj = new_document(out);

	if (!pdfium_enter()) {
		OBJ_RELEASE(obj);
		efree(indexes);
		RETURN_THROWS();
	}
	int page_count = FPDF_GetPageCount(h->doc);
	pdfium_indexes_status valid = pdfium_indexes_from(ht, page_count, indexes);
	bool refused = valid == PDFIUM_INDEXES_OK && !remove_security && pdfium_is_encrypted(h);

	if (valid != PDFIUM_INDEXES_OK || refused) {
		pdfium_leave();
		OBJ_RELEASE(obj);
		efree(indexes);
		if (refused) {
			zend_throw_exception(pdfium_ce_unsupported_exception, "The document is encrypted and PDFium cannot "
				"encrypt the result; pass removeSecurity: true to extract its pages unencrypted", 0);
		} else {
			pdfium_throw_indexes_error(valid, page_count, 1);
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
		sources[n++] = pdfium_document_from_obj(Z_OBJ_P(zv))->h;
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
		if (pdfium_is_encrypted(sources[i])) {
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

ZEND_METHOD(Pdfium_Document, create)
{
	ZEND_PARSE_PARAMETERS_NONE();

	pdfium_doc_handle *out = doc_handle_create(NULL);
	zend_object *obj = new_document(out);

	if (!pdfium_enter()) {
		OBJ_RELEASE(obj);
		RETURN_THROWS();
	}
	out->doc = FPDF_CreateNewDocument();
	pdfium_leave();
	if (!out->doc) {
		OBJ_RELEASE(obj);
		zend_throw_exception(pdfium_ce_memory_exception, "Cannot create a document", 0);
		RETURN_THROWS();
	}
	RETURN_OBJ(obj);
}

bool pdfium_page_size_args(const double *size, float *sides)
{
	for (uint32_t k = 0; k < 2; k++) {
		sides[k] = pdfium_float_side(size[k]);
		if (!zend_finite(size[k]) || sides[k] <= 0) {
			zend_argument_value_error(k + 1, "must be finite and greater than 0");
			return false;
		}
	}
	return true;
}

ZEND_METHOD(Pdfium_Document, nUp)
{
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);
	double size[2];
	float sides[2];
	zend_long columns, rows;
	bool remove_security = false;

	ZEND_PARSE_PARAMETERS_START(4, 5)
		Z_PARAM_DOUBLE(size[0])
		Z_PARAM_DOUBLE(size[1])
		Z_PARAM_LONG(columns)
		Z_PARAM_LONG(rows)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(remove_security)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_page_size_args(size, sides)) {
		RETURN_THROWS();
	}
	if (columns < 1) {
		zend_argument_value_error(3, "must be at least 1");
		RETURN_THROWS();
	}
	if (rows < 1) {
		zend_argument_value_error(4, "must be at least 1");
		RETURN_THROWS();
	}
	pdfium_doc_handle *out = doc_handle_create(NULL);
	zend_object *obj = new_document(out);

	if (!pdfium_enter()) {
		OBJ_RELEASE(obj);
		RETURN_THROWS();
	}
	if (!remove_security && pdfium_is_encrypted(h)) {
		pdfium_leave();
		OBJ_RELEASE(obj);
		zend_throw_exception(pdfium_ce_unsupported_exception, "The document is encrypted and PDFium cannot "
			"encrypt the result; pass removeSecurity: true to put its pages on sheets unencrypted", 0);
		RETURN_THROWS();
	}
	out->doc = FPDF_ImportNPagesToOne(h->doc, sides[0], sides[1], (size_t) columns, (size_t) rows);
	bool unread = h->file.read_failed;

	if (unread && out->doc) {
		FPDF_CloseDocument(out->doc);
		out->doc = NULL;
	}
	pdfium_leave();
	if (!out->doc) {
		OBJ_RELEASE(obj);
		if (unread) {
			zend_throw_exception(pdfium_ce_format_exception, pdfium_read_failed_message, 0);
		} else {
			zend_throw_exception(pdfium_ce_exception, "Cannot put the pages on sheets", 0);
		}
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

static bool version_fits(zend_long version, uint32_t arg)
{
	if (version < INT_MIN || version > INT_MAX) {
		zend_argument_value_error(arg, "must be between %d and %d", INT_MIN, INT_MAX);
		return false;
	}
	return true;
}

static zend_string *serialise(pdfium_doc_handle *h, bool remove_security, bool incremental, bool has_version, zend_long version)
{
	string_writer w = {.fw = {.version = 1, .WriteBlock = string_writer_write}};
	FPDF_DWORD flags = (incremental ? FPDF_INCREMENTAL : FPDF_NO_INCREMENTAL) | (remove_security ? FPDF_REMOVE_SECURITY : 0);

	if (!pdfium_enter()) {
		return NULL;
	}
	bool saved = has_version ? FPDF_SaveWithVersion(h->doc, &w.fw, flags, (int) version) : FPDF_SaveAsCopy(h->doc, &w.fw, flags);

	pdfium_leave();
	if (!w.out.oom && h->file.read_failed) {
		smart_str_free(&w.out.s);
		zend_throw_exception(pdfium_ce_format_exception, pdfium_read_failed_message, 0);
		return NULL;
	}
	if (!saved || w.out.oom) {
		smart_str_free(&w.out.s);
		zend_throw_exception(w.out.oom ? pdfium_ce_memory_exception : pdfium_ce_exception, w.out.oom
			? "Serialising the document needs more memory than memory_limit leaves" : "Cannot serialise the document", 0);
		return NULL;
	}
	return smart_str_extract(&w.out.s);
}

ZEND_METHOD(Pdfium_Document, save)
{
	char *path;
	size_t path_len;
	bool remove_security = false, incremental = false, version_is_null = true;
	zend_long version = 0;

	ZEND_PARSE_PARAMETERS_START(1, 4)
		Z_PARAM_PATH(path, path_len)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(remove_security)
		Z_PARAM_BOOL(incremental)
		Z_PARAM_LONG_OR_NULL(version, version_is_null)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_path_given(path_len) || !version_fits(version, 4)) {
		RETURN_THROWS();
	}
	pdfium_write_and_release(serialise(pdfium_this_document(ZEND_THIS), remove_security, incremental, !version_is_null, version), path);
}

ZEND_METHOD(Pdfium_Document, toString)
{
	bool remove_security = false, incremental = false, version_is_null = true;
	zend_long version = 0;

	ZEND_PARSE_PARAMETERS_START(0, 3)
		Z_PARAM_OPTIONAL
		Z_PARAM_BOOL(remove_security)
		Z_PARAM_BOOL(incremental)
		Z_PARAM_LONG_OR_NULL(version, version_is_null)
	ZEND_PARSE_PARAMETERS_END();

	if (!version_fits(version, 3)) {
		RETURN_THROWS();
	}
	zend_string *bytes = serialise(pdfium_this_document(ZEND_THIS), remove_security, incremental, !version_is_null, version);
	if (!bytes) {
		RETURN_THROWS();
	}
	RETURN_STR(bytes);
}

void pdfium_document_free_obj(zend_object *obj)
{
	pdfium_document *d = pdfium_document_from_obj(obj);

	if (d->h) {
		pdfium_doc_handle_release(d->h);
	}
	zend_object_std_dtor(obj);
}
