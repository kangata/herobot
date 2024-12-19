#include <php.h>
#include <math.h>

// Function declarations
PHP_FUNCTION(fast_cosine_similarity);

// Arginfo
ZEND_BEGIN_ARG_INFO_EX(arginfo_fast_cosine_similarity, 0, 0, 2)
    ZEND_ARG_ARRAY_INFO(0, vector1, 0)
    ZEND_ARG_ARRAY_INFO(0, vector2, 0)
ZEND_END_ARG_INFO()

// Function entry
static const zend_function_entry vector_search_functions[] = {
    PHP_FE(fast_cosine_similarity, arginfo_fast_cosine_similarity)
    PHP_FE_END
};

// Module entry definition
zend_module_entry vector_search_module_entry = {
    STANDARD_MODULE_HEADER,
    "vector_search",
    vector_search_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    "1.0",
    STANDARD_MODULE_PROPERTIES
};

// Add module entry point
ZEND_GET_MODULE(vector_search)

// Cosine similarity implementation in C
PHP_FUNCTION(fast_cosine_similarity) {
    zval *vector1, *vector2;
    HashTable *hash1, *hash2;
    double dot_product = 0.0;
    double norm1 = 0.0;
    double norm2 = 0.0;
    
    ZEND_PARSE_PARAMETERS_START(2, 2)
        Z_PARAM_ARRAY(vector1)
        Z_PARAM_ARRAY(vector2)
    ZEND_PARSE_PARAMETERS_END();

    hash1 = Z_ARRVAL_P(vector1);
    hash2 = Z_ARRVAL_P(vector2);

    zval *val1, *val2;
    zend_string *key;
    zend_ulong idx;

    ZEND_HASH_FOREACH_KEY_VAL(hash1, idx, key, val1) {
        val2 = zend_hash_index_find(hash2, idx);
        if (val2) {
            double v1 = zval_get_double(val1);
            double v2 = zval_get_double(val2);
            dot_product += v1 * v2;
            norm1 += v1 * v1;
            norm2 += v2 * v2;
        }
    } ZEND_HASH_FOREACH_END();

    norm1 = sqrt(norm1);
    norm2 = sqrt(norm2);
    
    RETURN_DOUBLE(dot_product / (norm1 * norm2));
}