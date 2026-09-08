import { test as base, BrowserContext } from '@playwright/test';
import fs from 'fs';
import { ensureValidAuth, getAuthPath } from './auth';
import { ADMIN_USER, CONTRIB_USER } from './users';

function storageStateFor(username: string) {
  const authPath = getAuthPath(username);
  return fs.existsSync(authPath) ? { storageState: authPath } : {};
}

export const test = base.extend<{
  adminContext: BrowserContext;
  contribContext: BrowserContext;
}>({
  adminContext: async ({ browser }, use) => {
    const context = await browser.newContext(storageStateFor(ADMIN_USER.user));
    const page = await context.newPage();
    await ensureValidAuth(page, ADMIN_USER.user, ADMIN_USER.password);
    await use(context);
    await page.close();
    await context.close();
  },
  contribContext: async ({ browser }, use) => {
    const context = await browser.newContext(storageStateFor(CONTRIB_USER.user));
    const page = await context.newPage();
    await ensureValidAuth(page, CONTRIB_USER.user, CONTRIB_USER.password);
    await use(context);
    await page.close();
    await context.close();
  },
});

export { expect } from '@playwright/test';
