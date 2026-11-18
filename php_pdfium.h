#ifndef PHP_PDFIUM_H
#define PHP_PDFIUM_H

#include "php.h"
#include "zend_smart_str.h"

#include <fpdfview.h>
#include <fpdf_formfill.h>

#define PHP_PDFIUM_VERSION "0.2.0"

extern zend_module_entry pdfium_module_entry;
#define phpext_pdfium_ptr &pdfium_module_entry

#if defined(ZTS) && defined(COMPILE_DL_PDFIUM)
ZEND_TSRMLS_CACHE_EXTERN()
#endif

typedef struct pdfium_doc_handle {
	FPDF_DOCUMENT doc;
	FPDF_FORMHANDLE form;
	bool form_tried;
	FPDF_FORMFILLINFO form_info;
	zend_string *buffer;
	uint32_t refs;
	uint64_t attachment_changes;
	struct pdfium_page_handle *pages;
} pdfium_doc_handle;

typedef enum { PDFIUM_PAGE_PLACED, PDFIUM_PAGE_DELETED, PDFIUM_PAGE_LOST } pdfium_page_state;

typedef struct pdfium_page_handle {
	pdfium_doc_handle *h;
	FPDF_PAGE page;
	bool form_loaded;
	uint32_t refs;
	int index;
	pdfium_page_state state;
	struct pdfium_page_handle *prev, *next;
} pdfium_page_handle;

typedef struct { pdfium_doc_handle *h; zend_object std; } pdfium_document;
typedef struct { pdfium_page_handle *ph; zend_object std; } pdfium_page;
typedef struct { pdfium_doc_handle *h; int index; zval current; zend_object std; } pdfium_page_iterator;
typedef struct { unsigned char *pixels; int width, height; bool alpha, gray; zend_object std; } pdfium_bitmap;
typedef struct { pdfium_page_handle *ph; FPDF_TEXTPAGE tp; zend_object std; } pdfium_text_page;
typedef struct { pdfium_doc_handle *h; FPDF_ATTACHMENT att; int index; uint64_t changes; zend_object std; } pdfium_attachment;

typedef struct { smart_str s; bool oom; } pdfium_buf;
typedef struct { int index; int order; } pdfium_moved_page;

enum { RECT_LEFT, RECT_BOTTOM, RECT_RIGHT, RECT_TOP, RECT_SLOTS };

extern zend_class_entry *pdfium_ce_document;
extern zend_class_entry *pdfium_ce_page;
extern zend_class_entry *pdfium_ce_page_iterator;
extern zend_class_entry *pdfium_ce_text_page;
extern zend_class_entry *pdfium_ce_bitmap;
extern zend_class_entry *pdfium_ce_attachment;
extern zend_class_entry *pdfium_ce_attachment_metadata;
extern zend_class_entry *pdfium_ce_metadata;
extern zend_class_entry *pdfium_ce_permissions;
extern zend_class_entry *pdfium_ce_rect;
extern zend_class_entry *pdfium_ce_color;
extern zend_class_entry *pdfium_ce_matrix;
extern zend_class_entry *pdfium_ce_char;
extern zend_class_entry *pdfium_ce_text_match;
extern zend_class_entry *pdfium_ce_web_link;
extern zend_class_entry *pdfium_ce_bookmark;
extern zend_class_entry *pdfium_ce_link;
extern zend_class_entry *pdfium_ce_quad;
extern zend_class_entry *pdfium_ce_destination;
extern zend_class_entry *pdfium_ce_action;
extern zend_class_entry *pdfium_ce_exception;
extern zend_class_entry *pdfium_ce_file_exception;
extern zend_class_entry *pdfium_ce_format_exception;
extern zend_class_entry *pdfium_ce_password_exception;
extern zend_class_entry *pdfium_ce_unsupported_exception;
extern zend_class_entry *pdfium_ce_memory_exception;
extern zend_class_entry *pdfium_ce_busy_exception;

#ifdef ZTS
bool pdfium_enter(void);
void pdfium_enter_wait(void);
void pdfium_leave(void);
void pdfium_guard_startup(void);
#else
static inline bool pdfium_enter(void) { return true; }
static inline void pdfium_enter_wait(void) {}
static inline void pdfium_leave(void) {}
static inline void pdfium_guard_startup(void) {}
#endif

static inline pdfium_document *pdfium_document_from_obj(zend_object *obj)
{
	return (pdfium_document *)((char *)obj - XtOffsetOf(pdfium_document, std));
}

static inline pdfium_doc_handle *pdfium_this_document(zval *self)
{
	return pdfium_document_from_obj(Z_OBJ_P(self))->h;
}

static inline pdfium_page *pdfium_page_from_obj(zend_object *obj)
{
	return (pdfium_page *)((char *)obj - XtOffsetOf(pdfium_page, std));
}

static inline pdfium_page_handle *pdfium_this_page(zval *self)
{
	return pdfium_page_from_obj(Z_OBJ_P(self))->ph;
}

static inline pdfium_page_iterator *pdfium_page_iterator_from_obj(zend_object *obj)
{
	return (pdfium_page_iterator *)((char *)obj - XtOffsetOf(pdfium_page_iterator, std));
}

static inline pdfium_text_page *pdfium_text_page_from_obj(zend_object *obj)
{
	return (pdfium_text_page *)((char *)obj - XtOffsetOf(pdfium_text_page, std));
}

static inline pdfium_bitmap *pdfium_bitmap_from_obj(zend_object *obj)
{
	return (pdfium_bitmap *)((char *)obj - XtOffsetOf(pdfium_bitmap, std));
}

static inline pdfium_attachment *pdfium_attachment_from_obj(zend_object *obj)
{
	return (pdfium_attachment *)((char *)obj - XtOffsetOf(pdfium_attachment, std));
}

void pdfium_doc_handle_addref(pdfium_doc_handle *h);
void pdfium_doc_handle_release(pdfium_doc_handle *h);
FPDF_FORMHANDLE pdfium_doc_handle_form(pdfium_doc_handle *h);
void pdfium_document_free_obj(zend_object *obj);
bool pdfium_page_size_args(const double *size, float *sides);

void pdfium_page_handle_addref(pdfium_page_handle *ph);
void pdfium_page_handle_release(pdfium_page_handle *ph);
void pdfium_text_page_free_obj(zend_object *obj);
bool pdfium_text_slots_match(void);
zend_object *pdfium_page_new(pdfium_doc_handle *h);
zend_object *pdfium_page_attach(zend_object *obj, FPDF_PAGE page, int index);
zend_object *pdfium_page_load(pdfium_doc_handle *h, zend_long index, int *page_count);
bool pdfium_page_live(pdfium_page_handle *ph);
void pdfium_pages_deleted(pdfium_doc_handle *h, int index);
void pdfium_pages_inserted(pdfium_doc_handle *h, int index, int count);
void pdfium_pages_moved(pdfium_doc_handle *h, const pdfium_moved_page *sorted, int count, int to);
void pdfium_pages_lost(pdfium_doc_handle *h);
zend_object *pdfium_page_iterator_create(pdfium_doc_handle *h);
void pdfium_page_free_obj(zend_object *obj);
void pdfium_page_iterator_free_obj(zend_object *obj);
double pdfium_points(float value);
float pdfium_float_side(double value);
const char *pdfium_rect_fault(const zval *zr, double *sides);
zval *pdfium_slot(zend_object *obj, int index);
void pdfium_rect_init(zval *zv, double x1, double y1, double x2, double y2);
bool pdfium_slots_named(zend_class_entry *ce, const char *names);
void pdfium_leave_or_throw(zval *return_value, const char *oom);
void pdfium_index_error(uint32_t arg, int count, const char *one, const char *all);

typedef enum { PDFIUM_INDEXES_OK, PDFIUM_INDEXES_NO_PAGES, PDFIUM_INDEXES_OUT_OF_RANGE } pdfium_indexes_status;
pdfium_indexes_status pdfium_indexes_from(HashTable *ht, int page_count, int *out);
void pdfium_throw_indexes_error(pdfium_indexes_status status, int page_count, uint32_t arg);
void pdfium_indexes_memory_error(void);
int *pdfium_indexes_checked(HashTable *ht, uint32_t arg, uint32_t *count);
bool pdfium_is_encrypted(pdfium_doc_handle *h);

bool pdfium_navigation_slots_match(void);

void pdfium_bitmap_free_obj(zend_object *obj);

void pdfium_attachment_free_obj(zend_object *obj);
bool pdfium_attachment_slots_match(void);

bool pdfium_memory_fits(size_t bytes);
bool pdfium_buf_append(pdfium_buf *b, const void *data, size_t len);
bool pdfium_path_given(size_t len);
zend_string *pdfium_read_file(const char *path);
void pdfium_write_and_release(zend_string *data, const char *path);

static inline bool pdfium_high_surrogate(uint32_t u)
{
	return u >= 0xD800 && u <= 0xDBFF;
}

static inline bool pdfium_low_surrogate(uint32_t u)
{
	return u >= 0xDC00 && u <= 0xDFFF;
}

static inline uint32_t pdfium_surrogate_pair(uint32_t high, uint32_t low)
{
	return 0x10000 + ((high - 0xD800) << 10) + (low - 0xDC00);
}

zend_string *pdfium_utf16le_to_utf8(const unsigned short *units, size_t count);
typedef unsigned long (*pdfium_utf16le_getter)(void *source, unsigned short *buf, unsigned long bytes);
zend_string *pdfium_utf16le_fetch(pdfium_utf16le_getter get, void *source, unsigned long bytes);
zend_string *pdfium_utf16le_fetch_under_limit(pdfium_utf16le_getter get, void *source, size_t *used, bool *oom);
size_t pdfium_utf8_encode(uint32_t cp, char *out);
const char *pdfium_utf8_to_utf16le(const zend_string *s, unsigned short *units, size_t *count);
unsigned short *pdfium_utf8_to_utf16le_alloc(const zend_string *s);

#endif
