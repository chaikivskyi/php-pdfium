#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"

ZEND_METHOD(Pdfium_Document, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

void pdfium_document_free_obj(zend_object *obj)
{
	pdfium_document *d = pdfium_document_from_obj(obj);

	if (d->form) {
		FPDFDOC_ExitFormFillEnvironment(d->form);
	}
	if (d->doc) {
		FPDF_CloseDocument(d->doc);
	}
	if (d->buffer) {
		zend_string_release(d->buffer);
	}
	zval_ptr_dtor(&d->sources);
	zend_object_std_dtor(obj);
}
