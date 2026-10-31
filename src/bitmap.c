#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"

ZEND_METHOD(Pdfium_Bitmap, __construct)
{
	ZEND_PARSE_PARAMETERS_NONE();
}

void pdfium_bitmap_free_obj(zend_object *obj)
{
	pdfium_bitmap *img = pdfium_bitmap_from_obj(obj);

	if (img->bitmap) {
		FPDFBitmap_Destroy(img->bitmap);
	}
	zend_object_std_dtor(obj);
}
