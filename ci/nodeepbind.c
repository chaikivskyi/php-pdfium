/* PHP loads extensions with RTLD_DEEPBIND, which the ASan runtime refuses (google/sanitizers#611). */
#define _GNU_SOURCE
#include <dlfcn.h>

void *dlopen(const char *file, int flags)
{
	return dlmopen(LM_ID_BASE, file, flags & ~RTLD_DEEPBIND);
}
