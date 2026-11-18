#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <ctype.h>
#include <fpdf_attachment.h>
#include "zend_exceptions.h"

static unsigned long name_get(void *att, unsigned short *buf, unsigned long len)
{
	return FPDFAttachment_GetName(att, buf, len);
}

static unsigned long description_get(void *att, unsigned short *buf, unsigned long len)
{
	return FPDFAttachment_GetDescription(att, buf, len);
}

static unsigned long subtype_get(void *att, unsigned short *buf, unsigned long len)
{
	return FPDFAttachment_GetSubtype(att, buf, len);
}

static unsigned long relationship_get(void *att, unsigned short *buf, unsigned long len)
{
	return FPDFAttachment_GetAFRelationship(att, buf, len);
}

typedef struct { FPDF_ATTACHMENT att; FPDF_BYTESTRING key; } param_source;

static unsigned long param_get(void *source, unsigned short *buf, unsigned long len)
{
	param_source *p = source;

	return FPDFAttachment_GetStringValue(p->att, p->key, buf, len);
}

enum { META_DESCRIPTION, META_SUBTYPE, META_AF_RELATIONSHIP, META_CREATION_DATE, META_MOD_DATE, META_CHECKSUM, META_SLOTS };

static const struct { pdfium_utf16le_getter get; FPDF_BYTESTRING key; const char *what; } meta_fields[META_SLOTS] = {
	[META_DESCRIPTION] = {description_get, NULL, "description"},
	[META_SUBTYPE] = {subtype_get, NULL, "subtype"},
	[META_AF_RELATIONSHIP] = {relationship_get, NULL, "afRelationship"},
	[META_CREATION_DATE] = {param_get, "CreationDate", "creationDate"},
	[META_MOD_DATE] = {param_get, "ModDate", "modDate"},
	[META_CHECKSUM] = {param_get, "CheckSum", "checksum"},
};

ZEND_METHOD(Pdfium_Attachment, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_AttachmentMetadata, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

bool pdfium_attachment_slots_match(void)
{
	return pdfium_slots_named(pdfium_ce_attachment_metadata, "description subtype afRelationship creationDate modDate checksum");
}

static zend_object *attachment_new(pdfium_doc_handle *h, int index, FPDF_ATTACHMENT att)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_attachment);
	pdfium_attachment *a = pdfium_attachment_from_obj(Z_OBJ(zv));
	a->h = h;
	a->att = att;
	a->index = index;
	a->changes = h->attachment_changes;
	pdfium_doc_handle_addref(h);
	return Z_OBJ(zv);
}

static int attachment_count(FPDF_DOCUMENT doc)
{
	int count = FPDFDoc_GetAttachmentCount(doc);

	return count > 0 ? count : 0;
}

static pdfium_attachment *attachment_current(zval *self)
{
	pdfium_attachment *a = pdfium_attachment_from_obj(Z_OBJ_P(self));

	if (a->changes != a->h->attachment_changes) {
		zend_throw_exception(pdfium_ce_exception,
			"The document's attachments changed since this Attachment was fetched; fetch it again", 0);
		return NULL;
	}
	return a;
}

static FPDF_ATTACHMENT attachment_enter(zval *self)
{
	pdfium_attachment *a = attachment_current(self);

	return a && pdfium_enter() ? a->att : NULL;
}

ZEND_METHOD(Pdfium_Document, attachmentCount)
{
	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = attachment_count(pdfium_this_document(ZEND_THIS)->doc);

	pdfium_leave();
	RETURN_LONG(count);
}

ZEND_METHOD(Pdfium_Document, attachments)
{
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);
	const char *oom = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = attachment_count(h->doc);

	if (!pdfium_memory_fits((size_t) count * (sizeof(pdfium_attachment) + sizeof(Bucket)))) {
		oom = "The attachments need";
	} else {
		array_init_size(return_value, (uint32_t) count);
		for (int i = 0; i < count; i++) {
			FPDF_ATTACHMENT att = FPDFDoc_GetAttachment(h->doc, i);

			if (att) {
				zval zv;

				ZVAL_OBJ(&zv, attachment_new(h, i, att));
				add_index_zval(return_value, i, &zv);
			}
		}
	}
	pdfium_leave_or_throw(return_value, oom);
}

ZEND_METHOD(Pdfium_Document, attachment)
{
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);
	zend_long index;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(index)
	ZEND_PARSE_PARAMETERS_END();

	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = attachment_count(h->doc);
	bool in_range = index >= 0 && index < count;
	FPDF_ATTACHMENT att = in_range ? FPDFDoc_GetAttachment(h->doc, (int) index) : NULL;

	pdfium_leave();
	if (!in_range) {
		pdfium_index_error(1, count, "an attachment", "attachments");
		RETURN_THROWS();
	}
	if (!att) {
		zend_throw_exception_ex(pdfium_ce_format_exception, 0, "Cannot read attachment %d", (int) index);
		RETURN_THROWS();
	}
	RETURN_OBJ(attachment_new(h, (int) index, att));
}

static int attachment_index_of(FPDF_DOCUMENT doc, FPDF_ATTACHMENT att)
{
	int count = attachment_count(doc);

	for (int i = 0; i < count; i++) {
		if (FPDFDoc_GetAttachment(doc, i) == att) {
			return i;
		}
	}
	return -1;
}

ZEND_METHOD(Pdfium_Document, addAttachment)
{
	pdfium_doc_handle *h = pdfium_this_document(ZEND_THIS);
	zend_string *name, *contents, *description = NULL;
	size_t name_units, desc_units = 0;
	const char *bad;

	ZEND_PARSE_PARAMETERS_START(2, 3)
		Z_PARAM_STR(name)
		Z_PARAM_STR(contents)
		Z_PARAM_OPTIONAL
		Z_PARAM_STR_OR_NULL(description)
	ZEND_PARSE_PARAMETERS_END();

	if (!ZSTR_LEN(name)) {
		zend_argument_value_error(1, "must not be empty");
		RETURN_THROWS();
	}
	if ((bad = pdfium_utf8_to_utf16le(name, NULL, &name_units))) {
		zend_argument_value_error(1, "%s", bad);
		RETURN_THROWS();
	}
	if (ZSTR_LEN(contents) >= INT_MAX) {
		zend_argument_value_error(2, "must be at most %d bytes", INT_MAX - 1);
		RETURN_THROWS();
	}
	if (description && (bad = pdfium_utf8_to_utf16le(description, NULL, &desc_units))) {
		zend_argument_value_error(3, "%s", bad);
		RETURN_THROWS();
	}
	if (!pdfium_memory_fits((name_units + 1 + (description ? desc_units + 1 : 0)) * sizeof(unsigned short))) {
		zend_throw_exception_ex(pdfium_ce_memory_exception, 0, "The attachment %s more memory than memory_limit leaves",
			description ? "name and description need" : "name needs");
		RETURN_THROWS();
	}
	unsigned short *wname = pdfium_utf8_to_utf16le_alloc(name);
	unsigned short *wdesc = description ? pdfium_utf8_to_utf16le_alloc(description) : NULL;
	const char *fail = NULL;
	FPDF_ATTACHMENT att = NULL;
	bool entered = pdfium_enter();

	if (entered) {
		att = FPDFDoc_AddAttachment(h->doc, wname);
		if (!att) {
			fail = "Cannot add the attachment: the name may already be used, or the document's attachment list cannot take it";
		} else {
			h->attachment_changes++;
			if (!FPDFAttachment_SetFile(att, h->doc, ZSTR_VAL(contents), (unsigned long) ZSTR_LEN(contents))
				|| (wdesc && !FPDFAttachment_SetDescription(att, wdesc))) {
				int index = attachment_index_of(h->doc, att);

				fail = index >= 0 && FPDFDoc_DeleteAttachment(h->doc, index) ? "Cannot store the attachment's contents or description"
					: "Cannot store the attachment's contents or description, and the entry PDFium added stays in the document";
			}
		}
		pdfium_leave();
	}
	efree(wname);
	if (wdesc) {
		efree(wdesc);
	}
	if (!entered) {
		RETURN_THROWS();
	}
	if (fail) {
		zend_throw_exception(pdfium_ce_exception, fail, 0);
		RETURN_THROWS();
	}
	RETURN_OBJ(attachment_new(h, -1, att));
}

ZEND_METHOD(Pdfium_Attachment, index)
{
	ZEND_PARSE_PARAMETERS_NONE();
	pdfium_attachment *a = attachment_current(ZEND_THIS);

	if (!a) {
		RETURN_THROWS();
	}
	if (a->index >= 0) {
		RETURN_LONG(a->index);
	}
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int index = attachment_index_of(a->h->doc, a->att);

	pdfium_leave();
	if (index < 0) {
		zend_throw_exception(pdfium_ce_exception, "PDFium cannot find this attachment in the document's list", 0);
		RETURN_THROWS();
	}
	RETURN_LONG(index);
}

ZEND_METHOD(Pdfium_Attachment, name)
{
	size_t used = 0;
	bool oom = false;

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ATTACHMENT att = attachment_enter(ZEND_THIS);

	if (!att) {
		RETURN_THROWS();
	}
	zend_string *name = pdfium_utf16le_fetch_under_limit(name_get, att, &used, &oom);

	if (name) {
		RETVAL_STR(name);
	} else {
		RETVAL_EMPTY_STRING();
	}
	pdfium_leave_or_throw(return_value, oom ? "The attachment name needs" : NULL);
}

static const char *contents_of(FPDF_ATTACHMENT att, zval *return_value, zend_class_entry **ce)
{
	unsigned long len = 0, got = 0;

	if (!FPDFAttachment_GetFile(att, NULL, 0, &len)) {
		return NULL;
	}
	if (!len) {
		RETVAL_EMPTY_STRING();
		return NULL;
	}
	if (!pdfium_memory_fits(len)) {
		*ce = pdfium_ce_memory_exception;
		return "The attachment needs more memory than memory_limit leaves";
	}
	zend_string *s = zend_string_alloc(len, 0);

	if (!FPDFAttachment_GetFile(att, ZSTR_VAL(s), len, &got) || got != len) {
		zend_string_efree(s);
		*ce = pdfium_ce_exception;
		return "Cannot read the attachment's contents";
	}
	ZSTR_VAL(s)[len] = '\0';
	RETVAL_STR(s);
	return NULL;
}

ZEND_METHOD(Pdfium_Attachment, contents)
{
	zend_class_entry *ce = NULL;

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ATTACHMENT att = attachment_enter(ZEND_THIS);

	if (!att) {
		RETURN_THROWS();
	}
	const char *fail = contents_of(att, return_value, &ce);

	pdfium_leave();
	if (fail) {
		zend_throw_exception(ce, fail, 0);
	}
}

static zend_string *checksum_hex(const zend_string *s)
{
	const char *v = ZSTR_VAL(s);

	if (ZSTR_LEN(s) != 34 || v[0] != '<' || v[33] != '>') {
		return NULL;
	}
	for (int i = 1; i < 33; i++) {
		if (!isxdigit((unsigned char) v[i])) {
			return NULL;
		}
	}
	zend_string *hex = zend_string_init(v + 1, 32, 0);

	zend_str_tolower(ZSTR_VAL(hex), 32);
	return hex;
}

ZEND_METHOD(Pdfium_Attachment, metadata)
{
	zend_string *values[META_SLOTS] = {0};
	size_t used = 0;
	int i, failed = META_SLOTS;
	bool oom = false;

	ZEND_PARSE_PARAMETERS_NONE();
	FPDF_ATTACHMENT att = attachment_enter(ZEND_THIS);

	if (!att) {
		RETURN_THROWS();
	}
	for (i = 0; i < META_SLOTS && !oom; i++) {
		param_source p = {att, meta_fields[i].key};

		values[i] = pdfium_utf16le_fetch_under_limit(meta_fields[i].get, p.key ? (void *) &p : att, &used, &oom);
		if (oom) {
			failed = i;
		}
	}
	pdfium_leave();

	if (failed != META_SLOTS) {
		for (i = 0; i < META_SLOTS; i++) {
			if (values[i]) {
				zend_string_release(values[i]);
			}
		}
		zend_throw_exception_ex(pdfium_ce_memory_exception, 0,
			"The attachment's %s needs more memory than memory_limit leaves", meta_fields[failed].what);
		RETURN_THROWS();
	}
	if (values[META_CHECKSUM]) {
		zend_string *hex = checksum_hex(values[META_CHECKSUM]);

		zend_string_release(values[META_CHECKSUM]);
		values[META_CHECKSUM] = hex;
	}
	object_init_ex(return_value, pdfium_ce_attachment_metadata);
	for (i = 0; i < META_SLOTS; i++) {
		if (values[i]) {
			ZVAL_STR(pdfium_slot(Z_OBJ_P(return_value), i), values[i]);
		} else {
			ZVAL_NULL(pdfium_slot(Z_OBJ_P(return_value), i));
		}
	}
}

void pdfium_attachment_free_obj(zend_object *obj)
{
	pdfium_attachment *a = pdfium_attachment_from_obj(obj);

	if (a->h) {
		pdfium_doc_handle_release(a->h);
	}
	zend_object_std_dtor(obj);
}
