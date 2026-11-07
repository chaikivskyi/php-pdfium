PHP_ARG_WITH([pdfium],
  [for pdfium support],
  [AS_HELP_STRING([[--with-pdfium[=DIR]]], [Path to a pdfium-binaries tree (include/ and lib/); without DIR the release in PDFIUM_VERSION is downloaded])],
  [yes])

if test "$PHP_PDFIUM" != "no"; then
  PHP_NEW_EXTENSION([pdfium], [pdfium.c src/document.c src/page.c src/bitmap.c src/io.c src/utf16.c src/guard.c], [$ext_shared],, [-DZEND_ENABLE_STATIC_TSRMLS_CACHE=1])
  PHP_ADD_BUILD_DIR([$ext_builddir/src])

  pdfium_version=`cat "$ext_srcdir/PDFIUM_VERSION" 2>/dev/null | tr -d '\r\n'`
  if test -z "$pdfium_version"; then
    AC_MSG_ERROR([cannot read $ext_srcdir/PDFIUM_VERSION])
  fi
  AC_DEFINE_UNQUOTED([PHP_PDFIUM_LIB_VERSION], ["$pdfium_version"], [pdfium-binaries release])

  if test "$PHP_PDFIUM" = "yes"; then
    PHP_PDFIUM="$ext_srcdir/src/libpdfium"
    have_version=`cat "$PHP_PDFIUM/PDFIUM_VERSION" 2>/dev/null | tr -d '\r\n'`
    if test "$have_version" != "$pdfium_version" || { test ! -f "$PHP_PDFIUM/lib/libpdfium.so" && test ! -f "$PHP_PDFIUM/lib/libpdfium.dylib"; }; then
      AC_MSG_NOTICE([downloading PDFium $pdfium_version])
      sh "$ext_srcdir/scripts/fetch-pdfium.sh" || AC_MSG_ERROR([cannot download PDFium (see the message above); download a pdfium-binaries release yourself and pass --with-pdfium=DIR])
    fi
  fi

  if test ! -f "$PHP_PDFIUM/include/fpdfview.h"; then
    AC_MSG_ERROR([--with-pdfium=DIR must point at a pdfium-binaries tree: DIR/include/fpdfview.h not found])
  fi

  if test -f "$PHP_PDFIUM/lib/libpdfium.dylib"; then
    PDFIUM_LIB_NAME=libpdfium.dylib
    pdfium_rpath="-Wl,-rpath,@loader_path"
  elif test -f "$PHP_PDFIUM/lib/libpdfium.so"; then
    PDFIUM_LIB_NAME=libpdfium.so
    pdfium_rpath="-Wl,-rpath,'\$\$ORIGIN'"
  else
    AC_MSG_ERROR([no libpdfium.so or libpdfium.dylib in $PHP_PDFIUM/lib])
  fi
  PDFIUM_LIB_DIR=`cd "$PHP_PDFIUM/lib" && pwd`

  PHP_ADD_INCLUDE([$PHP_PDFIUM/include])
  PDFIUM_SHARED_LIBADD="$pdfium_rpath -L$PDFIUM_LIB_DIR -lpdfium $PDFIUM_SHARED_LIBADD"

  PKG_CHECK_MODULES([LIBPNG], [libpng])
  PKG_CHECK_MODULES([LIBJPEG], [libjpeg])
  PHP_EVAL_INCLINE([$LIBPNG_CFLAGS $LIBJPEG_CFLAGS])
  PHP_EVAL_LIBLINE([$LIBPNG_LIBS $LIBJPEG_LIBS], [PDFIUM_SHARED_LIBADD])

  AC_CHECK_HEADERS([sys/xattr.h])
  AC_CHECK_FUNCS([posix_fallocate mkostemp])

  PHP_SUBST([PDFIUM_SHARED_LIBADD])
  PHP_SUBST([PDFIUM_LIB_DIR])
  PHP_SUBST([PDFIUM_LIB_NAME])
  PHP_ADD_MAKEFILE_FRAGMENT
fi
