import { execFileSync, execSync } from 'child_process';
import path from 'path';

const PLUGIN_ROOT = path.resolve(__dirname, '../../..');

export function isDdev(): boolean {
  return Boolean(process.env.DDEV_HOSTNAME || process.env.IS_DDEV_PROJECT);
}

/**
 * Run WP-CLI. `cmd` includes the `wp` binary, e.g. `wp post list ...`.
 * Inside DDEV, `wp` is on PATH. Otherwise wp-env is used from the plugin root.
 */
export function wpCli(cmd: string): string {
  const command = isDdev() ? cmd : `bunx wp-env run cli ${cmd}`;
  return execSync(command, {
    encoding: 'utf8',
    stdio: 'pipe',
    cwd: isDdev() ? undefined : PLUGIN_ROOT,
  }).trim();
}

/**
 * Run WP-CLI with argv (no shell). Safer for JSON option updates.
 * `args` are WP-CLI arguments without the `wp` binary.
 */
export function wpCliArgs(args: string[]): string {
  if (isDdev()) {
    return execFileSync('wp', args, {
      encoding: 'utf8',
      stdio: 'pipe',
    }).trim();
  }

  const quoted = args.map((arg) => JSON.stringify(arg)).join(' ');
  return execSync(`bunx wp-env run cli wp ${quoted}`, {
    encoding: 'utf8',
    stdio: 'pipe',
    cwd: PLUGIN_ROOT,
  }).trim();
}

export function getCookedSettings(): Record<string, unknown> {
  try {
    return JSON.parse(wpCliArgs(['option', 'get', 'cooked_settings', '--format=json'])) as Record<
      string,
      unknown
    >;
  } catch {
    return {};
  }
}

export function setCookedSettings(settings: Record<string, unknown>): void {
  wpCliArgs(['option', 'update', 'cooked_settings', JSON.stringify(settings), '--format=json']);
}

export function deletePostsByTitle(title: string, status = 'draft'): void {
  try {
    const result = wpCli(
      `wp post list --post_type=cp_recipe --post_status=${status} --field=ID --title="${title}"`
    );
    if (!result) {
      return;
    }
    for (const id of result.split('\n').filter((line) => line.trim())) {
      deletePost(id);
    }
  } catch {
    // already deleted or never created
  }
}

export function deletePost(id: string | number): void {
  if (!id) {
    return;
  }
  try {
    wpCli(`wp post delete ${id} --force`);
  } catch {
    // already deleted
  }
}

export function findTermIdByName(taxonomy: string, name: string): number | null {
  if (!taxonomy || !name) {
    return null;
  }
  try {
    const json = wpCliArgs(['term', 'get', taxonomy, name, '--by=name', '--format=json']);
    const term = JSON.parse(json) as { term_id: number | string };
    const id = Number(term.term_id);
    return id > 0 ? id : null;
  } catch {
    return null;
  }
}

export function deleteTerm(taxonomy: string, id: number): void {
  if (!taxonomy || !id) {
    return;
  }
  try {
    wpCliArgs(['term', 'delete', taxonomy, String(id)]);
  } catch {
    // already deleted, or taxonomy is not registered
  }
}

export function userExists(login: string): boolean {
  try {
    wpCli(`wp user get ${login} --field=ID`);
    return true;
  } catch {
    return false;
  }
}

export function ensureUser(account: { user: string; password: string; email: string; role: string }): void {
  if (userExists(account.user)) {
    wpCli(
      `wp user update ${account.user} --user_pass=${account.password} --role=${account.role} --display_name=${account.user}`
    );
    return;
  }

  wpCli(
    `wp user create ${account.user} ${account.email} --role=${account.role} --user_pass=${account.password} --display_name=${account.user}`
  );
}
