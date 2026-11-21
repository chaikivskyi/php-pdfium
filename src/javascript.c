#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"
#include <fpdf_javascript.h>

enum { JS_NAME, JS_SCRIPT };

ZEND_METHOD(Pdfium_JavaScript, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

bool pdfium_javascript_slots_match(void)
{
	return pdfium_slots_named(pdfium_ce_javascript, "name script");
}

typedef unsigned long (*text_getter)(FPDF_JAVASCRIPT_ACTION js, FPDF_WCHAR *buf, unsigned long bytes);

static zend_string *text_fetch(text_getter get, FPDF_JAVASCRIPT_ACTION js, bool *oom)
{
	unsigned long bytes = get(js, NULL, 0);

	if (bytes <= 2) {
		return NULL;
	}
	if (!pdfium_memory_fits((size_t) bytes + (size_t) bytes / 2 * 3)) {
		*oom = true;
		return NULL;
	}
	unsigned short *units = emalloc(bytes);
	zend_string *s = get(js, units, bytes) == bytes ? pdfium_utf16le_to_utf8(units, bytes / 2 - 1) : NULL;

	efree(units);
	return s;
}

static void javascript_append(zval *into, zend_string *name, zend_string *script)
{
	zval zv;

	object_init_ex(&zv, pdfium_ce_javascript);
	ZVAL_STR(pdfium_slot(Z_OBJ(zv), JS_NAME), name ? name : ZSTR_EMPTY_ALLOC());
	ZVAL_STR(pdfium_slot(Z_OBJ(zv), JS_SCRIPT), script ? script : ZSTR_EMPTY_ALLOC());
	add_next_index_zval(into, &zv);
}

ZEND_METHOD(Pdfium_Document, javaScripts)
{
	FPDF_DOCUMENT doc = pdfium_this_document(ZEND_THIS)->doc;
	bool oom = false;

	ZEND_PARSE_PARAMETERS_NONE();
	if (!pdfium_enter()) {
		RETURN_THROWS();
	}
	int count = FPDFDoc_GetJavaScriptActionCount(doc);

	if (count > 0 && !pdfium_memory_fits((size_t) count * (sizeof(zend_object) + 2 * sizeof(zval) + sizeof(Bucket)))) {
		oom = true;
	} else {
		array_init(return_value);
	}
	for (int i = 0; i < count && !oom; i++) {
		FPDF_JAVASCRIPT_ACTION js = FPDFDoc_GetJavaScriptAction(doc, i);

		if (!js) {
			continue;
		}
		zend_string *name = text_fetch(FPDFJavaScriptAction_GetName, js, &oom);
		zend_string *script = oom ? NULL : text_fetch(FPDFJavaScriptAction_GetScript, js, &oom);

		FPDFDoc_CloseJavaScriptAction(js);
		if (!oom) {
			javascript_append(return_value, name, script);
		} else if (name) {
			zend_string_release(name);
		}
	}
	pdfium_leave_or_throw(return_value, oom ? "The document's JavaScript needs" : NULL);
}
