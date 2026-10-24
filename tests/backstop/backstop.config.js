function getBaseUrl() {
  if (process.env.WP_BASE_URL) {
    return process.env.WP_BASE_URL.replace(/\/$/, '');
  }
  if (process.env.DDEV_HOSTNAME || process.env.IS_DDEV_PROJECT) {
    return 'https://dev.mimisrecipes.ddev.site';
  }
  return 'http://localhost:8888';
}

const baseUrl = getBaseUrl();
const recipeUrl = `${baseUrl}/recipes/vrt-golden-recipe/`;
const browseUrl = `${baseUrl}/browse-recipes/recipe-category/vrt`;

const hideSelectors = [
  '#wpadminbar',
  '.cmplz-cookiebanner',
  '#cmplz-cookiebanner-container',
  '.cmplz-manage-consent',
  '.cmplz-blocker',
];

const removeSelectors = [
  '.cmplz-cookiebanner',
  '.cmplz-overlay',
  '#cmplz-manage-consent-container',
];

const scenarioDefaults = {
  hideSelectors,
  removeSelectors,
  delay: 1000,
  misMatchThreshold: 0.5,
  requireSameDimensions: true,
};

module.exports = {
  id: 'cooked',
  viewports: [
    { label: 'phone', width: 375, height: 667 },
    { label: 'desktop', width: 1280, height: 720 },
  ],
  onBeforeScript: 'onBefore.js',
  onReadyScript: 'onReady.js',
  scenarios: [
    {
      label: 'recipe-single-light',
      url: recipeUrl,
      selectors: ['.entry-content'],
      readySelector: '.cooked-recipe-info',
      ...scenarioDefaults,
    },
    {
      label: 'recipe-single-dark',
      url: `${recipeUrl}?vrt_dark=enabled`,
      selectors: ['.entry-content'],
      readySelector: '.cooked-recipe-info',
      ...scenarioDefaults,
    },
    {
      label: 'browse-light',
      url: `${browseUrl}?vrt_list=grid`,
      selectors: ['[id^="cooked-recipe-list-"]'],
      readySelector: '[id^="cooked-recipe-list-"]',
      ...scenarioDefaults,
    },
    {
      label: 'browse-dark',
      url: `${browseUrl}?vrt_list=grid&vrt_dark=enabled`,
      selectors: ['[id^="cooked-recipe-list-"]'],
      readySelector: '[id^="cooked-recipe-list-"]',
      ...scenarioDefaults,
    },
  ],
  paths: {
    bitmaps_reference: 'tests/backstop/backstop_data/bitmaps_reference',
    bitmaps_test: 'tests/backstop/backstop_data/bitmaps_test',
    engine_scripts: 'tests/backstop/engine_scripts',
    html_report: 'tests/backstop/backstop_data/html_report',
    ci_report: 'tests/backstop/backstop_data/ci_report',
  },
  report: ['browser'],
  engine: 'playwright',
  engineOptions: {
    browser: 'chromium',
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--ignore-certificate-errors'],
    ignoreHTTPSErrors: true,
  },
  gotoParameters: {
    waitUntil: 'load',
    timeout: 60000,
  },
  asyncCaptureLimit: 3,
  asyncCompareLimit: 50,
  debug: false,
  debugWindow: false,
};
