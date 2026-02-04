module.exports = {
  env: {
    browser: true,
    es2021: true,
  },
  extends: [
    '.eslintrc-auto-import.json',
    'plugin:vue/vue3-recommended',
    'plugin:import/recommended',
    'plugin:promise/recommended',
    'plugin:case-police/recommended',
    'plugin:regexp/recommended',

    // 'plugin:unicorn/recommended',
  ],
  parser: 'vue-eslint-parser',
  parserOptions: {
    ecmaVersion: 13,
    sourceType: 'module',
  },
  plugins: [
    'vue',
    'regex',
    'regexp',
  ],
  ignorePatterns: ['resources/js/plugins/iconify/*.js', 'node_modules', 'dist', '*.d.ts', 'vendor', '*.json'],
  rules: {
    'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',

    // indentation (Already present in TypeScript)
    'comma-spacing': ['error', { before: false, after: true }],
    'key-spacing': ['error', { afterColon: true }],
    'n/prefer-global/process': ['off'],
    'sonarjs/cognitive-complexity': ['off'],

    'vue/first-attribute-linebreak': ['off', {
      singleline: 'beside',
      multiline: 'below',
    }],


    // indentation (Already present in TypeScript)
    'indent': ['error', 2],

    // Enforce trailing comma (Already present in TypeScript)
    'comma-dangle': ['error', 'always-multiline'],

    // Enforce consistent spacing inside braces of object (Already present in TypeScript)
    'object-curly-spacing': ['error', 'always'],

    // Enforce camelCase naming convention
    'camelcase': 'off',

    // Disable max-len
    'max-len': 'off',

    // we don't want it
    'semi': ['error', 'never'],

    // add parens ony when required in arrow function
    'arrow-parens': ['error', 'as-needed'],

    // add new line above comment
    'newline-before-return': 'error',

    // add new line above comment
    'lines-around-comment': 'off',
    'eqeqeq': 'error',
    'consistent-return': 'error',
    'curly': 'error',
    'default-case-last': 'error',
    'dot-notation': 'error',
    'no-array-constructor': 'error',
    'no-caller': 'error',
    'no-constructor-return': 'error',
    'no-eval': 'error',
    'no-useless-rename': 'error',
    'no-constant-condition': 'error',
    'no-else-return': 'error',
    'no-implied-eval': 'error',
    'no-lonely-if': 'error',
    'no-multi-str': 'error',
    'no-new-wrappers': 'error',
    'no-script-url': 'error',
    'no-shadow-restricted-names': 'error',
    'no-throw-literal': 'error',
    'no-return-await': 'error',
    'no-implicit-coercion': 'error',
    'no-param-reassign': 'error',
    'no-extra-bind': 'error',
    'no-new-object': 'error',
    'no-new-func': 'error',
    'no-restricted-globals': 'error',
    'no-template-curly-in-string': 'error',
    'no-useless-call': 'error',
    'no-useless-catch': 'error',
    'no-useless-constructor': 'error',
    'no-unneeded-ternary': 'error',
    'no-useless-computed-key': 'error',
    'no-useless-concat': 'error',
    'no-useless-escape': 'error',
    'no-useless-return': 'error',
    'no-proto': 'error',
    'no-sequences': 'error',
    'no-void': 'error',
    'no-var': 'error',
    'prefer-exponentiation-operator': 'error',
    'prefer-destructuring': 'error',
    'symbol-description': 'error',
    'prefer-rest-params': 'error',
    'prefer-spread': 'error',
    'object-shorthand': 'error',
    'prefer-object-has-own': 'error',
    'prefer-object-spread': 'error',
    'prefer-arrow-callback': 'error',
    'prefer-const': 'error',
    'prefer-numeric-literals': 'error',
    'prefer-regex-literals': 'error',
    'prefer-promise-reject-errors': 'error',
    'prefer-template': 'error',
    'radix': 'error',
    'yoda': 'error',

    // Ignore _ as unused variable

    'array-element-newline': ['error', 'consistent'],
    'array-bracket-newline': ['error', 'consistent'],

    'vue/multi-word-component-names': 'off',

    'padding-line-between-statements': [
      'error',
      { blankLine: 'always', prev: 'expression', next: 'const' },
      { blankLine: 'always', prev: 'const', next: 'expression' },
      { blankLine: 'always', prev: 'multiline-const', next: '*' },
      { blankLine: 'always', prev: '*', next: 'multiline-const' },
    ],

    // Plugin: eslint-plugin-import
    'import/prefer-default-export': 'off',
    'import/newline-after-import': ['error', { count: 1 }],
    'no-restricted-imports': ['error', 'vuetify/components', {
      name: 'vue3-apexcharts',
      message: 'apexcharts are auto imported',
    }],

    // For omitting extension for ts files
    'import/extensions': [
      'error',
      'ignorePackages',
      {
        js: 'never',
        jsx: 'never',
        ts: 'never',
        tsx: 'never',
      },
    ],

    // ignore virtual files
    'import/no-unresolved': ['error', {
      ignore: [
        '~pages$',
        'virtual:meta-layouts',
        '#auth$',
        '#components$',

        // Ignore vite's ?raw imports
        '.*\\?raw',
      ],
    }],

    // Thanks: https://stackoverflow.com/a/63961972/10796681
    'no-shadow': 'error',


    // Plugin: eslint-plugin-promise
    'promise/always-return': 'error',
    'promise/catch-or-return': 'error',

    // ESLint plugin vue
    'vue/block-tag-newline': 'off',
    'vue/component-api-style': 'off',
    'vue/component-name-in-template-casing': ['error', 'PascalCase', { registeredComponentsOnly: false, ignores: ['/^swiper-/'] }],
    'vue/custom-event-name-casing': ['off', 'camelCase', {
      ignores: [
        '/^(click):[a-z]+((\\d)|([A-Z0-9][a-z0-9]+))*([A-Z])?/',
      ],
    }],
    'vue/define-macros-order': 'error',
    'vue/html-comment-content-newline': 'error',
    'vue/html-comment-content-spacing': 'error',
    'vue/html-comment-indent': 'error',
    'vue/match-component-file-name': 'error',
    'vue/no-child-content': 'error',
    'vue/require-default-prop': 'off',
    'vue/require-prop-types': 'off',
    'vue/prop-name-casing': 'off',
    'vue/no-v-html': 'off',
    'vue/order-in-components': 'off',

    'vue/no-duplicate-attr-inheritance': 'error',
    'vue/no-empty-component-block': 'error',
    'vue/no-multiple-objects-in-class': 'error',
    'vue/no-reserved-component-names': 'error',
    'vue/no-template-target-blank': 'error',
    'vue/no-mutating-props': 'error',
    'vue/no-unused-vars': 'error',
    'vue/no-useless-mustaches': 'error',
    'vue/no-useless-v-bind': 'error',
    'vue/padding-line-between-blocks': 'error',
    'vue/prefer-separate-static-class': 'error',
    'vue/prefer-true-attribute-shorthand': 'error',
    'vue/v-on-function-call': 'error',
    'vue/no-restricted-class': 'off',
    'vue/valid-v-slot': ['error', {
      allowModifiers: true,
    }],

    // -- Extension Rules
    'vue/no-irregular-whitespace': 'error',
    'vue/template-curly-spacing': 'error',
    'vue/html-indent': 'error',
    'vue/max-attributes-per-line': 'error',
    'vue/html-closing-bracket-spacing': 'error',
    'vue/html-closing-bracket-newline': 'error',
    'vue/attributes-order': 'error',
    'vue/singleline-html-element-content-newline': 'error',
    'vue/html-self-closing': 'error',
    'vue/v-on-event-hyphenation': 'error',
    'vue/html-quotes': 'error',
    'vue/attribute-hyphenation': 'error',
    'vue/multiline-html-element-content-newline': 'error',
    'vue/no-multi-spaces': 'error',

    // -- Sonarlint
    'sonarjs/no-duplicate-string': 'off',
    'sonarjs/no-nested-template-literals': 'off',
    'sonarjs/todo-tag': 'off',
    'sonarjs/slow-regex': 'off',
    'sonarjs/regex-complexity': 'off',
    'sonarjs/no-nested-conditional': 'off',
    'sonarjs/no-nested-assignment': 'off',
    'sonarjs/no-hardcoded-passwords': 'off',
    'sonarjs/pseudo-random': 'off',
    'sonarjs/no-ignored-exceptions': 'off',
    'sonarjs/no-invariant-returns': 'off',
    'sonarjs/no-all-duplicated-branches': 'off',
    'sonarjs/no-use-of-empty-return-value': 'off',
    'sonarjs/no-unenclosed-multiline-block': 'off',
    'sonarjs/no-same-line-conditional': 'off',
    'sonarjs/no-inverted-boolean-check': 'off',
    'sonarjs/anchor-precedence': 'off',
    'sonarjs/no-identical-functions': 'off',
    'sonarjs/unused-import': 'off',
    'import/default': 'error',
    'regexp/prefer-d': 'error',
    'regexp/no-useless-non-capturing-group': 'error',
    'regexp/no-useless-flag': 'error',

    // -- Unicorn
    // 'unicorn/filename-case': 'off',
    // 'unicorn/prevent-abbreviations': ['error', {
    //   replacements: {
    //     props: false,
    //   },
    // }],

    // https://github.com/gmullerb/eslint-plugin-regex
    'regex/invalid': [
      'error',
      [
        {
          regex: '@/assets/images',
          replacement: '@images',
          message: 'Use \'@images\' path alias for image imports',
        },
        {
          regex: '@/assets/styles',
          replacement: '@styles',
          message: 'Use \'@styles\' path alias for importing styles from \'resources/js/assets/styles\'',
        },
        {
          regex: '@core/\\w',
          message: 'You can\'t use @core when you are in @layouts module',
          files: {
            inspect: '@layouts/.*',
          },
        },
        {
          regex: 'useLayouts\\(',
          message: '`useLayouts` composable is only allowed in @layouts & @core directory. Please use `useThemeConfig` composable instead.',
          files: {
            inspect: '^(?!.*(@core|@layouts)).*',
          },
        },
      ],

      // Ignore files
      '\\.eslintrc\\.cjs',
    ],
  },
  settings: {
    'import/resolver': {
      node: true,
      typescript: { project: './jsconfig.json' },
    },
  },
}
