#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_signature.h>

#define SIGNATURE_BYTES 256
#define RANGE_PAIR_BYTES 256

enum { SIG_CONTENTS, SIG_BYTE_RANGE, SIG_SUB_FILTER, SIG_REASON, SIG_TIME, SIG_DOC_MDP_PERMISSION, SIG_SLOTS };

typedef unsigned long (*ascii_getter)(FPDF_SIGNATURE sig, char *buf, unsigned long len);

ZEND_METHOD(Pdfium_Signature, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

bool pdfium_signature_slots_match(void)
{
	return pdfium_slots_named(pdfium_ce_signature, "contents byteRange subFilter reason time docMdpPermission");
}

static unsigned long reason_get(void *sig, unsigned short *buf, unsigned long len)
{
	return FPDFSignatureObj_GetReason(sig, buf, len);
}

static int signature_count(FPDF_DOCUMENT doc)
{
	int count = FPDF_GetSignatureCount(doc);

	return count > 0 ? count : 0;
}

static bool contents_fill(FPDF_SIGNATURE sig, zval *slot)
{
	unsigned long len = FPDFSignatureObj_GetContents(sig, NULL, 0);

	if (!len) {
		return true;
	}
	if (!pdfium_memory_fits(len)) {
		return false;
	}
	zend_string *s = zend_string_alloc(len, 0);

	if (FPDFSignatureObj_GetContents(sig, ZSTR_VAL(s), len) != len) {
		zend_string_efree(s);
		return true;
	}
	ZSTR_VAL(s)[len] = '\0';
	ZVAL_STR(slot, s);
	return true;
}

static bool byte_range_fill(FPDF_SIGNATURE sig, zval *slot)
{
	unsigned long count = FPDFSignatureObj_GetByteRange(sig, NULL, 0);
	unsigned long pairs = count / 2;

	if (!pairs) {
		return true;
	}
	if (!pdfium_memory_fits((size_t) count * sizeof(int) + (size_t) pairs * RANGE_PAIR_BYTES)) {
		return false;
	}
	int *values = safe_emalloc(count, sizeof(int), 0);

	if (FPDFSignatureObj_GetByteRange(sig, values, count) == count) {
		array_init_size(slot, (uint32_t) pairs);
		for (unsigned long i = 0; i < pairs; i++) {
			zval offset, length, pair;

			ZVAL_LONG(&offset, values[2 * i]);
			ZVAL_LONG(&length, values[2 * i + 1]);
			ZVAL_ARR(&pair, zend_new_pair(&offset, &length));
			add_next_index_zval(slot, &pair);
		}
	}
	efree(values);
	return true;
}

static bool ascii_fill(ascii_getter get, FPDF_SIGNATURE sig, zval *slot)
{
	unsigned long len = get(sig, NULL, 0);

	if (len <= 1) {
		return true;
	}
	if (!pdfium_memory_fits(len)) {
		return false;
	}
	zend_string *s = zend_string_alloc(len - 1, 0);

	if (get(sig, ZSTR_VAL(s), len) != len) {
		zend_string_efree(s);
		return true;
	}
	ZSTR_VAL(s)[len - 1] = '\0';
	ZVAL_STR(slot, s);
	return true;
}

static bool signature_fill(FPDF_SIGNATURE sig, zend_object *obj)
{
	size_t used = 0;
	bool oom = false;

	for (int i = 0; i < SIG_SLOTS; i++) {
		ZVAL_NULL(pdfium_slot(obj, i));
	}
	ZVAL_EMPTY_ARRAY(pdfium_slot(obj, SIG_BYTE_RANGE));

	if (!contents_fill(sig, pdfium_slot(obj, SIG_CONTENTS))
		|| !byte_range_fill(sig, pdfium_slot(obj, SIG_BYTE_RANGE))
		|| !ascii_fill(FPDFSignatureObj_GetSubFilter, sig, pdfium_slot(obj, SIG_SUB_FILTER))
		|| !ascii_fill(FPDFSignatureObj_GetTime, sig, pdfium_slot(obj, SIG_TIME))) {
		return false;
	}
	zend_string *reason = pdfium_utf16le_fetch_under_limit(reason_get, (void *) sig, &used, &oom);

	if (oom) {
		return false;
	}
	if (reason) {
		ZVAL_STR(pdfium_slot(obj, SIG_REASON), reason);
	}
	unsigned int permission = FPDFSignatureObj_GetDocMDPPermission(sig);

	if (permission >= 1 && permission <= 3) {
		ZVAL_LONG(pdfium_slot(obj, SIG_DOC_MDP_PERMISSION), permission);
	}
	return true;
}

ZEND_METHOD(Pdfium_Document, signatureCount)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = signature_count(pdfium_this_document(ZEND_THIS)->doc);

	pdfium_leave();
	RETURN_LONG(count);
}

ZEND_METHOD(Pdfium_Document, signatures)
{
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = signature_count(h->doc);
	bool oom = !pdfium_memory_fits((size_t) count * sizeof(Bucket));

	if (!oom) {
		array_init_size(return_value, (uint32_t) count);
	}
	for (int i = 0; i < count && !oom && !zend_atomic_bool_load_ex(&EG(timed_out)); i++) {
		FPDF_SIGNATURE sig = FPDF_GetSignatureObject(h->doc, i);
		zval zv;

		if (!sig) {
			continue;
		}
		oom = !pdfium_memory_fits(SIGNATURE_BYTES);
		if (oom) {
			break;
		}
		object_init_ex(&zv, pdfium_ce_signature);
		oom = !signature_fill(sig, Z_OBJ(zv));
		add_next_index_zval(return_value, &zv);
	}
	pdfium_leave_or_throw(return_value, oom ? "The signatures need" : NULL);
}
