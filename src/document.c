#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"

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
