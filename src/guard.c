#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php_pdfium.h"

#ifdef ZTS
#include <pthread.h>
#include "zend_exceptions.h"

static pthread_mutex_t lock = PTHREAD_MUTEX_INITIALIZER;
ZEND_TLS unsigned depth;

bool pdfium_enter(void)
{
	if (depth) {
		depth++;
		return true;
	}
	if (pthread_mutex_trylock(&lock) != 0) {
		zend_throw_exception(pdfium_ce_busy_exception, "PDFium is in use by another thread", 0);
		return false;
	}
	depth = 1;
	return true;
}

void pdfium_enter_wait(void)
{
	if (depth) {
		depth++;
		return;
	}
	pthread_mutex_lock(&lock);
	depth = 1;
}

void pdfium_leave(void)
{
	ZEND_ASSERT(depth > 0);
	if (--depth == 0) {
		pthread_mutex_unlock(&lock);
	}
}

void pdfium_guard_reset(void)
{
	if (depth) {
		depth = 0;
		pthread_mutex_unlock(&lock);
	}
}
#endif
