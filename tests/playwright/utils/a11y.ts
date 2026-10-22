import { expect, Page, test } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import type { Result } from 'axe-core';
import { recordA11yScan } from './a11y-report-store';

export type ScanScope = {
  /** Only scan inside these selectors (e.g. a modal). */
  include?: string[];
  /** Also skip these selectors (the admin bar is always skipped). */
  exclude?: string[];
};

export function scanPage(page: Page, scope: ScanScope = {}) {
  const builder = new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    .exclude('#wpadminbar');
  (scope.include || []).forEach((selector) => builder.include(selector));
  (scope.exclude || []).forEach((selector) => builder.exclude(selector));
  return builder;
}

function formatViolations(violations: Result[]): string {
  return violations
    .map((violation) => {
      const targets = violation.nodes
        .map((node) => node.target.join(' '))
        .join(', ');

      return [
        `[${violation.impact}] ${violation.id}`,
        violation.help,
        violation.helpUrl,
        `Targets: ${targets}`,
      ].join('\n');
    })
    .join('\n\n');
}

export async function expectAccessible(page: Page, scope: ScanScope = {}): Promise<void> {
  const results = await scanPage(page, scope).analyze();
  const testInfo = test.info();

  recordA11yScan(
    {
      testTitle: testInfo.title,
      testFile: testInfo.file,
      pageUrl: page.url(),
      violations: results.violations,
      violationCount: results.violations.length,
      passed: results.violations.length === 0,
      scannedAt: new Date().toISOString(),
    },
    `${testInfo.testId}-r${testInfo.retry}`
  );

  if (results.violations.length > 0) {
    await testInfo.attach('accessibility-violations', {
      body: JSON.stringify(results.violations, null, 2),
      contentType: 'application/json',
    });
  }

  expect(
    results.violations,
    results.violations.length
      ? `Accessibility violations found:\n\n${formatViolations(results.violations)}\n\nSee test-results/a11y-report.html for the full report.`
      : undefined
  ).toEqual([]);
}
