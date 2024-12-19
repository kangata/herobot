PHP_ARG_ENABLE(vector_search, whether to enable vector_search support,
[  --enable-vector_search   Enable vector_search support])

if test "$PHP_VECTOR_SEARCH" != "no"; then
  PHP_NEW_EXTENSION(vector_search, vector_search.c, $ext_shared)
fi
