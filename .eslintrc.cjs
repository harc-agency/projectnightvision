module.exports = {
    'root': true,
    'env': {
        'browser': true,
        'node': true,
        'es2021': true
    },
    'extends': [
        'eslint:recommended',
        'plugin:vue/vue3-essential'
    ],
    'plugins': [
        'modules-newlines',
        'vue',
        'tailwindcss'
    ],
    'parserOptions': {
        'ecmaVersion': 12,
        'sourceType': 'module'
    },
    'globals': {
        'axios': true,
        'route': true,
        'Ziggy': true
    },
    'rules': {
        'semi': ['error', 'never'],
        'modules-newlines/import-declaration-newline': 'warn',
        'modules-newlines/export-declaration-newline': 'warn',
        'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
        '@typescript-eslint/no-explicit-any': 'off',
        'quotes': ['error', 'single'],
        'comma-dangle': ['error', 'never'],
        'indent': ['error', 2],
        'brace-style': ['error', '1tbs', {
            'allowSingleLine': true
        }],
        'object-curly-spacing': [
            'error', 'always'
        ],
        'object-curly-newline': ['error', {
            'ImportDeclaration': { 'multiline': true, 'minProperties': 3 },
            'ExportDeclaration': { 'multiline': true, 'minProperties': 3 }
        }],
        'sort-imports': ['error', {
            'ignoreCase': false,
            'ignoreDeclarationSort': true,
            'ignoreMemberSort': false,
            'allowSeparatedGroups': true
        }],
        'tailwindcss/classnames-order': 'warn',
        'tailwindcss/enforces-negative-arbitrary-values': 'warn',
        'tailwindcss/enforces-shorthand': 'warn',
        'tailwindcss/migration-from-tailwind-2': 'off',
        'tailwindcss/no-arbitrary-value': 'off',
        'tailwindcss/no-custom-classname': 'off',
        'tailwindcss/no-contradicting-classname': 'error',
        'vue/no-deprecated-slot-attribute': 'off',
        'vue/html-indent': [
            'error', 2, {
                'attribute': 1,
                'baseIndent': 1,
                'closeBracket': 0,
                'alignAttributesVertically': true
            }],
        'vue/script-indent': ['error', 2],
        'vue/multiline-html-element-content-newline': ['error', {
            'ignoreWhenEmpty': true,
            'ignores': ['pre', 'textarea'],
            'allowEmptyLines': false
        }],
        'vue/max-attributes-per-line': [
            'error', {
                'singleline': 2,
                'multiline': 1
            }
        ],
        'vue/first-attribute-linebreak': ['error', {
            'singleline': 'ignore',
            'multiline': 'below'
        }],
        'vue/no-reserved-component-names': 'off',
        'vue/multi-word-component-names': 'off',
        'vue/order-in-components': ['error'],
        'vue/attributes-order': ['error', {
            'alphabetical': false
        }],
        'vue/no-v-text-v-html-on-component': 'off',
        'vue/padding-line-between-tags': ['error', [
            { 'blankLine': 'always', 'prev': '*', 'next': '*' }
        ]],
        'vue/html-self-closing': ['error', {
            'html': {
                'void': 'never',
                'normal': 'always',
                'component': 'always'
            },
            'svg': 'always',
            'math': 'always'
        }],
        'vue/html-closing-bracket-newline': ['error', {
            'singleline': 'never',
            'multiline': 'never',
            'selfClosingTag': {
                'singleline': 'never',
                'multiline': 'never'
            }
        }],
        'vue/singleline-html-element-content-newline': ['error', {
            'ignoreWhenNoAttributes': true,
            'ignoreWhenEmpty': true
        }],
        'no-multiple-empty-lines': ['error', {
            max: 1,
            maxBOF: 0,
            maxEOF: 1
        }],
        'no-multi-spaces': 'error',
        'no-trailing-spaces': ['error']
    }
}
