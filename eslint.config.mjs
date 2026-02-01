import path from 'node:path'
import { createRequire } from 'node:module'
import { fileURLToPath } from 'node:url'
import js from '@eslint/js'
import { FlatCompat } from '@eslint/eslintrc'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const require = createRequire(import.meta.url)
const legacyConfig = require('./.eslintrc.cjs')
const casePolice = require('eslint-plugin-case-police')

const compat = new FlatCompat({
  baseDirectory: __dirname,
  recommendedConfig: js.configs.recommended,
  allConfig: js.configs.all,
})

const legacyIgnorePatterns = Array.isArray(legacyConfig.ignorePatterns) ? legacyConfig.ignorePatterns : []

const legacyExtends = Array.isArray(legacyConfig.extends)
  ? legacyConfig.extends
    .filter(entry => entry !== 'plugin:case-police/recommended')
    .map(entry => (entry === 'plugin:sonarjs/recommended' ? 'plugin:sonarjs/recommended-legacy' : entry))
  : legacyConfig.extends

export default [
  {
    ignores: [
      ...legacyIgnorePatterns,
      'vendor/**',
      'public/build/**',
      '**/*.d.ts',
      'auto-imports.d.ts',
      'components.d.ts',
      'node_modules/**',
      'dist/**',
      '.eslintrc.js',
      'src/assets/*',
      'src/fake-db/*',
      'src/layouts/components/vx-tooltip/VxTooltip.vue',
      'tailwind.config.js',
      'tailwind-v0.js',
    ],
  },
  {
    linterOptions: {
      reportUnusedDisableDirectives: 'off',
    },
  },
  ...casePolice.configs.recommended,
  ...compat.config({
    ...legacyConfig,
    extends: legacyExtends,
    ignorePatterns: undefined,
  }),
]
