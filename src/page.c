#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"

ZEND_METHOD(Pdfium_Page, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_PageIterator, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

ZEND_METHOD(Pdfium_PageIterator, current)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_throw_error(NULL, "Pdfium\\PageIterator is not implemented");
}

ZEND_METHOD(Pdfium_PageIterator, key)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_throw_error(NULL, "Pdfium\\PageIterator is not implemented");
}

ZEND_METHOD(Pdfium_PageIterator, next)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_throw_error(NULL, "Pdfium\\PageIterator is not implemented");
}

ZEND_METHOD(Pdfium_PageIterator, rewind)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_throw_error(NULL, "Pdfium\\PageIterator is not implemented");
}

ZEND_METHOD(Pdfium_PageIterator, valid)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_throw_error(NULL, "Pdfium\\PageIterator is not implemented");
}

void pdfium_page_free_obj(zend_object *obj)
{
	pdfium_page *p = pdfium_page_from_obj(obj);

	if (p->page) {
		FPDF_ClosePage(p->page);
	}
	if (p->doc) {
		OBJ_RELEASE(p->doc);
	}
	zend_object_std_dtor(obj);
}

void pdfium_page_iterator_free_obj(zend_object *obj)
{
	pdfium_page_iterator *it = pdfium_page_iterator_from_obj(obj);

	zval_ptr_dtor(&it->current);
	if (it->doc) {
		OBJ_RELEASE(it->doc);
	}
	zend_object_std_dtor(obj);
}
