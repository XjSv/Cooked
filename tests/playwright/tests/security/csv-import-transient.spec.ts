import { test, expect } from '../../utils/fixtures';
import { wpCliArgs } from '../../utils/wp-cli';

const TRANSIENT_KEY = 'some_other_plugin_cached_path';
const VICTIM_NAME = 'victim-not-a-csv.txt';

function setupVictim(): string {
  const php = `
    $uploads = wp_upload_dir();
    if ( ! empty( $uploads['error'] ) ) {
      WP_CLI::error( $uploads['error'] );
    }
    $path = trailingslashit( $uploads['basedir'] ) . ${JSON.stringify(VICTIM_NAME)};
    if ( false === file_put_contents( $path, 'victim' ) ) {
      WP_CLI::error( 'Could not write victim file.' );
    }
    set_transient( ${JSON.stringify(TRANSIENT_KEY)}, $path, 3600 );
    echo $path;
  `;
  return wpCliArgs(['eval', php]);
}

function victimExists(path: string): boolean {
  const php = `echo file_exists( ${JSON.stringify(path)} ) ? 'yes' : 'no';`;
  return wpCliArgs(['eval', php]) === 'yes';
}

function cleanupVictim(path: string): void {
  const php = `
    $path = ${JSON.stringify(path)};
    if ( is_string( $path ) && '' !== $path && file_exists( $path ) ) {
      unlink( $path );
    }
    delete_transient( ${JSON.stringify(TRANSIENT_KEY)} );
    $uploads = wp_upload_dir();
    $fallback = trailingslashit( $uploads['basedir'] ) . ${JSON.stringify(VICTIM_NAME)};
    if ( $fallback !== $path && file_exists( $fallback ) ) {
      unlink( $fallback );
    }
  `;
  wpCliArgs(['eval', php]);
}

test.describe('CSV import transient', () => {
  let victimPath = '';

  test.beforeAll(() => {
    victimPath = setupVictim();
  });

  test.afterAll(() => {
    cleanupVictim(victimPath);
  });

  test('does not delete a file stored in a foreign transient', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/admin.php?page=cooked_import', { waitUntil: 'load' });
    const nonce = await page.evaluate(() => {
      const vars = (window as { cooked_migration_js_vars?: { cooked_import_nonce?: string } }).cooked_migration_js_vars;
      return vars?.cooked_import_nonce || '';
    });
    expect(nonce).not.toBe('');

    const response = await page.request.post('/wp-admin/admin-ajax.php', {
      form: {
        action: 'cooked_process_csv',
        nonce,
        transient_key: TRANSIENT_KEY,
      },
    });
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.success).toBe(false);
    expect(body.data.message).toContain('CSV file not found');
    expect(victimExists(victimPath)).toBe(true);

    await page.close();
  });
});
