import fs from 'fs';
import path from 'path';
import type {
  FullConfig,
  FullResult,
  Reporter,
} from '@playwright/test/reporter';
import type { Result } from 'axe-core';
import {
  clearA11yScanFiles,
  HTML_REPORT,
  JSON_REPORT,
  loadA11yScans,
  type A11yScanRecord,
} from '../utils/a11y-report-store';

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function impactClass(impact: Result['impact']): string {
  return impact ? `impact-${impact}` : 'impact-unknown';
}

function renderViolation(violation: Result): string {
  const nodes = violation.nodes
    .map((node) => {
      const target = escapeHtml(node.target.join(' '));
      const html = node.html ? escapeHtml(node.html) : '';
      const failure = node.failureSummary
        ? `<pre class="failure">${escapeHtml(node.failureSummary)}</pre>`
        : '';

      return `<li>
        <div class="target"><code>${target}</code></div>
        ${html ? `<pre class="html">${html}</pre>` : ''}
        ${failure}
      </li>`;
    })
    .join('');

  return `<article class="violation ${impactClass(violation.impact)}">
    <header>
      <span class="badge">${escapeHtml(violation.impact || 'unknown')}</span>
      <h3>${escapeHtml(violation.id)}</h3>
    </header>
    <p class="help">${escapeHtml(violation.help)}</p>
    <p><a href="${escapeHtml(violation.helpUrl)}" target="_blank" rel="noopener">Learn how to fix this</a></p>
    <ul class="nodes">${nodes}</ul>
  </article>`;
}

function renderHtmlReport(scans: A11yScanRecord[]): string {
  const totalViolations = scans.reduce((sum, scan) => sum + scan.violationCount, 0);
  const failedTests = scans.filter((scan) => !scan.passed).length;
  const generatedAt = new Date().toISOString();

  const sections = scans
    .map((scan) => {
      const status = scan.passed ? 'pass' : 'fail';
      const violations =
        scan.violations.length > 0
          ? scan.violations.map(renderViolation).join('')
          : '<p class="no-violations">No violations found.</p>';

      return `<section class="test ${status}">
        <header>
          <h2>${escapeHtml(scan.testTitle)}</h2>
          <div class="meta">
            <span class="status ${status}">${scan.passed ? 'PASS' : 'FAIL'}</span>
            <span>${scan.violationCount} violation(s)</span>
            <span>${escapeHtml(scan.pageUrl)}</span>
            <span>${escapeHtml(scan.testFile)}</span>
          </div>
        </header>
        <div class="violations">${violations}</div>
      </section>`;
    })
    .join('');

  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Accessibility Report</title>
  <style>
    :root {
      color-scheme: light dark;
      --bg: #f6f7f9;
      --card: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --border: #d1d5db;
      --pass: #166534;
      --fail: #b91c1c;
      --critical: #7f1d1d;
      --serious: #b45309;
      --moderate: #a16207;
      --minor: #1d4ed8;
    }
    @media (prefers-color-scheme: dark) {
      :root {
        --bg: #111827;
        --card: #1f2937;
        --text: #f9fafb;
        --muted: #9ca3af;
        --border: #374151;
      }
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
    }
    main { max-width: 1100px; margin: 0 auto; padding: 24px; }
    h1, h2, h3 { margin: 0 0 8px; }
    .summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
      margin-bottom: 24px;
    }
    .summary-card, .test, .violation {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 10px;
    }
    .summary-card { padding: 16px; }
    .summary-card strong { display: block; font-size: 1.5rem; }
    .test { margin-bottom: 20px; overflow: hidden; }
    .test > header { padding: 16px; border-bottom: 1px solid var(--border); }
    .meta { display: flex; flex-wrap: wrap; gap: 10px; color: var(--muted); font-size: 0.9rem; }
    .status { font-weight: 700; }
    .status.pass { color: var(--pass); }
    .status.fail { color: var(--fail); }
    .violations { padding: 16px; display: grid; gap: 16px; }
    .violation { padding: 16px; }
    .violation header { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .badge {
      text-transform: uppercase;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 999px;
      color: white;
    }
    .impact-critical .badge { background: var(--critical); }
    .impact-serious .badge { background: var(--serious); }
    .impact-moderate .badge { background: var(--moderate); }
    .impact-minor .badge { background: var(--minor); }
    .impact-unknown .badge { background: var(--muted); }
    .nodes { margin: 12px 0 0; padding-left: 20px; }
    pre, code {
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      font-size: 0.85rem;
    }
    pre {
      white-space: pre-wrap;
      word-break: break-word;
      background: rgba(127, 127, 127, 0.12);
      padding: 10px;
      border-radius: 6px;
      overflow-x: auto;
    }
    .no-violations { color: var(--pass); margin: 0; }
    a { color: #2563eb; }
  </style>
</head>
<body>
  <main>
    <h1>Accessibility Report</h1>
    <p>Generated ${escapeHtml(generatedAt)}</p>
    <div class="summary">
      <div class="summary-card"><span>Tests scanned</span><strong>${scans.length}</strong></div>
      <div class="summary-card"><span>Failed tests</span><strong>${failedTests}</strong></div>
      <div class="summary-card"><span>Total violations</span><strong>${totalViolations}</strong></div>
    </div>
    ${sections || '<p>No accessibility scans were recorded.</p>'}
  </main>
</body>
</html>`;
}

class A11yReporter implements Reporter {
  onBegin(_config: FullConfig): void {
    clearA11yScanFiles();
  }

  onEnd(_result: FullResult): void {
    const scans = loadA11yScans();

    if (scans.length === 0) {
      return;
    }

    fs.mkdirSync(path.dirname(HTML_REPORT), { recursive: true });
    fs.writeFileSync(HTML_REPORT, renderHtmlReport(scans), 'utf8');
    fs.writeFileSync(JSON_REPORT, JSON.stringify(scans, null, 2), 'utf8');

    const totalViolations = scans.reduce((sum, scan) => sum + scan.violationCount, 0);

    // eslint-disable-next-line no-console
    console.log(
      `\nAccessibility report: ${HTML_REPORT}\n` +
        `JSON summary: ${JSON_REPORT}\n` +
        `${scans.length} test(s) scanned, ${totalViolations} violation(s) found.\n`
    );
  }
}

export default A11yReporter;
