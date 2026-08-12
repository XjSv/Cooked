import fs from 'fs';
import path from 'path';
import type { Result } from 'axe-core';

export interface A11yScanRecord {
  testTitle: string;
  testFile: string;
  pageUrl: string;
  violations: Result[];
  violationCount: number;
  passed: boolean;
  scannedAt: string;
}

const REPORT_ROOT = path.join(process.cwd(), 'test-results');
export const SCAN_DIR = path.join(REPORT_ROOT, 'a11y-scans');
export const HTML_REPORT = path.join(REPORT_ROOT, 'a11y-report.html');
export const JSON_REPORT = path.join(REPORT_ROOT, 'a11y-report.json');

export function clearA11yScanFiles(): void {
  if (fs.existsSync(SCAN_DIR)) {
    for (const file of fs.readdirSync(SCAN_DIR)) {
      if (file.endsWith('.json')) {
        fs.unlinkSync(path.join(SCAN_DIR, file));
      }
    }
    return;
  }

  fs.mkdirSync(SCAN_DIR, { recursive: true });
}

export function recordA11yScan(
  record: A11yScanRecord,
  scanKey: string
): void {
  fs.mkdirSync(SCAN_DIR, { recursive: true });
  const filePath = path.join(SCAN_DIR, `${scanKey}.json`);
  fs.writeFileSync(filePath, JSON.stringify(record, null, 2), 'utf8');
}

export function loadA11yScans(): A11yScanRecord[] {
  if (!fs.existsSync(SCAN_DIR)) {
    return [];
  }

  return fs
    .readdirSync(SCAN_DIR)
    .filter((file) => file.endsWith('.json'))
    .map((file) => {
      const contents = fs.readFileSync(path.join(SCAN_DIR, file), 'utf8');
      return JSON.parse(contents) as A11yScanRecord;
    })
    .sort((a, b) => a.scannedAt.localeCompare(b.scannedAt));
}
