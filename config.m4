PHP_ARG_WITH([pdfium],
  [for pdfium support],
  [AS_HELP_STRING([--with-pdfium=DIR], [Path to a pdfium-binaries tree (include/ and lib/)])])

if test "$PHP_PDFIUM" != "no"; then
  if test "$PHP_PDFIUM" = "yes" || test ! -f "$PHP_PDFIUM/include/fpdfview.h"; then
    AC_MSG_ERROR([--with-pdfium=DIR must point at a pdfium-binaries tree: DIR/include/fpdfview.h not found])
  fi

  PHP_ADD_INCLUDE([$PHP_PDFIUM/include])
  PHP_ADD_LIBRARY_WITH_PATH([pdfium], [$PHP_PDFIUM/lib], [PDFIUM_SHARED_LIBADD])

  PKG_CHECK_MODULES([LIBPNG], [libpng])
  PKG_CHECK_MODULES([LIBJPEG], [libjpeg])
  PHP_EVAL_INCLINE([$LIBPNG_CFLAGS $LIBJPEG_CFLAGS])
  PHP_EVAL_LIBLINE([$LIBPNG_LIBS $LIBJPEG_LIBS], [PDFIUM_SHARED_LIBADD])

  AC_CHECK_HEADERS([sys/xattr.h])
  AC_CHECK_FUNCS([posix_fallocate mkostemp])

  PHP_SUBST([PDFIUM_SHARED_LIBADD])
  PHP_NEW_EXTENSION([pdfium], [pdfium.c src/utf16.c src/document.c src/page.c src/bitmap.c], [$ext_shared])
  PHP_ADD_BUILD_DIR([$ext_builddir/src])

  pdfium_version=`cat "$ext_srcdir/PDFIUM_VERSION" 2>/dev/null | tr -d '\r\n'`
  if test -z "$pdfium_version"; then
    AC_MSG_ERROR([cannot read $ext_srcdir/PDFIUM_VERSION])
  fi
  AC_DEFINE_UNQUOTED([PHP_PDFIUM_LIB_VERSION], ["$pdfium_version"], [pdfium-binaries release])
fi
