module.exports = {
  'extends': [
    'eslint:recommended',
    'plugin:sonarjs/recommended',
    'plugin:unicorn/recommended',
  ],
  'parserOptions': {
    'ecmaVersion': 2018,
    'sourceType': 'module',
  },
  'plugins': [
    'import',
    'sonarjs',
    'unicorn',
  ],
  'settings': {
    'import/core-modules': [],
    'import/ignore': [
      'node_modules',
      '\\.(coffee|scss|css|less|hbs|svg|json)$',
    ],
  },
  'rules': {
    'quotes': ['error', 'single'],
    'comma-dangle': [
      'error',
      {
      'arrays': 'always-multiline',
      'objects': 'always-multiline',
      'imports': 'always-multiline',
      'exports': 'always-multiline',
      'functions': 'ignore',
      },
    ],
    'unicorn/filename-case': [
      'error',
      {
      'case': 'camelCase',
      },
    ],
    'linebreak-style': 0,
    'id-length': [ 2, {
      min: 2,
      max: Number.infinity,
      properties: 'always',
      exceptions: [ '_', 'i', 'j', 'x', 'y', 'z', '$' ]
    }],
    'sort-imports': [ 2, {
      ignoreCase: true,
      ignoreMemberSort: false,
      memberSyntaxSortOrder: [ 'none', 'single', 'multiple', 'all' ]
    }],
    'space-before-function-paren': ['error', {
      'anonymous': 'always',
      'named': 'never',
      'asyncArrow': 'always',
    }],
    'dot-location': ['error', 'property'],
  },
  'env': {
    'browser': true,
    'es6': true,
  },
};
