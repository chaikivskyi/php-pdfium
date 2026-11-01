#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"

#ifdef ZTS
#include <pthread.h>
#include "zend_exceptions.h"
#include "zend_observer.h"

static pthread_mutex_t lock = PTHREAD_MUTEX_INITIALIZER;
ZEND_TLS bool held;

bool pdfium_enter(void)
{
	ZEND_ASSERT(!held);
	if (pthread_mutex_trylock(&lock) != 0) {
		zend_throw_exception(pdfium_ce_busy_exception, "PDFium is in use by another thread", 0);
		return false;
	}
	held = true;
	return true;
}

void pdfium_enter_wait(void)
{
	ZEND_ASSERT(!held);
	pthread_mutex_lock(&lock);
	held = true;
}

void pdfium_leave(void)
{
	ZEND_ASSERT(held);
	held = false;
	pthread_mutex_unlock(&lock);
}

static void on_error(int type, zend_string *file, uint32_t line, zend_string *message)
{
	if ((type & E_FATAL_ERRORS) && held) {
		pdfium_leave();
	}
}

void pdfium_guard_startup(void)
{
	zend_observer_error_register(on_error);
}
#endif
