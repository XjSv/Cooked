import { test, expect } from '../../utils/fixtures';
import { isDdev, wpCliArgs } from '../../utils/wp-cli';

type StoredCsv = {
  path: string;
  underTemp: boolean;
  prefixed: boolean;
  exists: boolean;
};

function storedCsv(transientKey: string): StoredCsv {
  const php = `
    $path = get_transient( ${JSON.stringify(transientKey)} );
    $temp = rtrim( wp_normalize_path( get_temp_dir() ), '/' );
    $normal = is_string( $path ) ? wp_normalize_path( $path ) : '';
    echo wp_json_encode( array(
      'path' => is_string( $path ) ? $path : '',
      'underTemp' => '' !== $normal && 0 === strpos( $normal, $temp . '/' ),
      'prefixed' => is_string( $path ) && 0 === strpos( basename( $path ), 'cooked-csv-' ),
      'exists' => is_string( $path ) && file_exists( $path ),
    ) );
  `;
  return JSON.parse(wpCliArgs(['eval', php])) as StoredCsv;
}

function cleanupUpload(transientKey: string, path: string): void {
  const php = `
    $key = ${JSON.stringify(transientKey)};
    $path = ${JSON.stringify(path)};
    if ( is_string( $path ) && '' !== $path && 0 === strpos( basename( $path ), 'cooked-csv-' ) && file_exists( $path ) ) {
      unlink( $path );
    }
    if ( '' !== $key ) {
      delete_transient( $key );
    }
  `;
  wpCliArgs(['eval', php]);
}

test.describe('CSV import path', () => {
  let transientKey = '';
  let storedPath = '';

  test.afterAll(() => {
    cleanupUpload(transientKey, storedPath);
  });

  test('returns only the transient key and stores the CSV outside uploads', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/admin.php?page=cooked_import', { waitUntil: 'load' });
    const nonce = await page.evaluate(() => {
      const vars = (window as { cooked_admin_functions_js_vars?: { cooked_import_nonce?: string } }).cooked_admin_functions_js_vars;
      return vars?.cooked_import_nonce || '';
    });
    expect(nonce).not.toBe('');

    const response = await page.request.post('/wp-admin/admin-ajax.php', {
      multipart: {
        action: 'cooked_upload_csv',
        nonce,
        csv_file: {
          name: 'recipes.csv',
          mimeType: 'text/csv',
          buffer: Buffer.from('title\nTest Recipe\n'),
        },
      },
    });
    expect(response.ok()).toBeTruthy();
    const raw = await response.text();
    expect(raw).not.toContain('wp-content/uploads');
    const body = JSON.parse(raw) as { success?: boolean; data?: { transient_key?: string; file_path?: string } };
    expect(body.success).toBe(true);
    expect(body.data?.transient_key || '').toMatch(/^cooked_csv_import_/);
    expect(body.data?.file_path).toBeUndefined();

    transientKey = body.data?.transient_key || '';
    const stored = storedCsv(transientKey);
    storedPath = stored.path;
    expect(stored.underTemp).toBe(true);
    expect(stored.prefixed).toBe(true);
    // Under wp-env the cli container has its own /tmp, so only DDEV can see the file.
    if (isDdev()) {
      expect(stored.exists).toBe(true);
    }

    await page.close();
  });
});
