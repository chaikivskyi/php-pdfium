#ifndef PHP_PDFIUM_H
#define PHP_PDFIUM_H

#include "php.h"
#include "zend_smart_str.h"

#ifdef ZTS
#error "pdfium supports NTS PHP only: PDFium is not thread-safe"
#endif

#include <fpdfview.h>
#include <fpdf_formfill.h>

#define PHP_PDFIUM_VERSION "0.1.0-dev"

extern zend_module_entry pdfium_module_entry;
#define phpext_pdfium_ptr &pdfium_module_entry

typedef struct pdfium_doc_handle {
	FPDF_DOCUMENT doc;
	FPDF_FORMHANDLE form;
	bool form_tried;
	FPDF_FORMFILLINFO form_info;
	zend_string *buffer;
	uint32_t refs;
} pdfium_doc_handle;

typedef struct { pdfium_doc_handle *h; zend_object std; } pdfium_document;
typedef struct { pdfium_doc_handle *h; FPDF_PAGE page; int index; bool form_loaded; zend_object std; } pdfium_page;
typedef struct { pdfium_doc_handle *h; int index; zval current; zend_object std; } pdfium_page_iterator;
typedef struct { FPDF_BITMAP bitmap; zend_object std; } pdfium_bitmap;

typedef struct { smart_str s; bool oom; } pdfium_buf;

extern zend_class_entry *pdfium_ce_document;
extern zend_class_entry *pdfium_ce_page;
extern zend_class_entry *pdfium_ce_page_iterator;
extern zend_class_entry *pdfium_ce_bitmap;
extern zend_class_entry *pdfium_ce_exception;
extern zend_class_entry *pdfium_ce_file_exception;
extern zend_class_entry *pdfium_ce_format_exception;
extern zend_class_entry *pdfium_ce_password_exception;
extern zend_class_entry *pdfium_ce_unsupported_exception;
extern zend_class_entry *pdfium_ce_memory_exception;

static inline pdfium_document *pdfium_document_from_obj(zend_object *obj)
{
	return (pdfium_document *)((char *)obj - XtOffsetOf(pdfium_document, std));
}

static inline pdfium_page *pdfium_page_from_obj(zend_object *obj)
{
	return (pdfium_page *)((char *)obj - XtOffsetOf(pdfium_page, std));
}

static inline pdfium_page_iterator *pdfium_page_iterator_from_obj(zend_object *obj)
{
	return (pdfium_page_iterator *)((char *)obj - XtOffsetOf(pdfium_page_iterator, std));
}

static inline pdfium_bitmap *pdfium_bitmap_from_obj(zend_object *obj)
{
	return (pdfium_bitmap *)((char *)obj - XtOffsetOf(pdfium_bitmap, std));
}

pdfium_doc_handle *pdfium_doc_handle_create(FPDF_DOCUMENT doc, zend_string *buffer);
void pdfium_doc_handle_addref(pdfium_doc_handle *h);
void pdfium_doc_handle_release(pdfium_doc_handle *h);
FPDF_FORMHANDLE pdfium_doc_handle_form(pdfium_doc_handle *h);

zend_object *pdfium_document_create(pdfium_doc_handle *h);

zend_object *pdfium_page_create(pdfium_doc_handle *h, int index);

zend_object *pdfium_page_iterator_create(pdfium_doc_handle *h);

bool pdfium_memory_fits(size_t bytes);

bool pdfium_buf_append(pdfium_buf *b, const void *data, size_t len);

zend_string *pdfium_utf16le_to_utf8(const unsigned short *units, size_t count);

void pdfium_document_free_obj(zend_object *obj);
void pdfium_page_free_obj(zend_object *obj);
void pdfium_page_iterator_free_obj(zend_object *obj);
void pdfium_bitmap_free_obj(zend_object *obj);

void pdfium_throw_last_error(const char *context);

void pdfium_write_and_release(zend_string *data, const char *path);

#endif
