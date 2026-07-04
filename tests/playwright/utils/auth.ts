import { Page } from '@playwright/test';
import path from 'path';
import fs from 'fs';

const AUTH_DIR = path.join(__dirname, '../tests/.auth');

// Ensure auth directory exists
if (!fs.existsSync(AUTH_DIR)) {
  fs.mkdirSync(AUTH_DIR, { recursive: true });
}

export function getAuthPath(username: string) {
  return path.join(AUTH_DIR, `${username}.json`);
}

export async function ensureValidAuth(page: Page, username: string, password: string) {
  try {
    await page.goto('/wp-admin/', { waitUntil: 'networkidle' });
    const adminBar = await page.$('#wpadminbar');

    if (!adminBar) {
      await performLogin(page, username, password);
    } else {
      // Check if we're logged in as the correct user
      const currentUser = await getCurrentUser(page);
      if (currentUser !== username) {
        await performLogout(page);
        await performLogin(page, username, password);
      }
    }
    // Save the auth state
    await page.context().storageState({ path: getAuthPath(username) });
  } catch (error) {
    await performLogin(page, username, password);
  }
}

async function getCurrentUser(page: Page): Promise<string> {
  const userElement = await page.locator('#wp-admin-bar-my-account .display-name').first();
  return await userElement.innerText();
}

async function performLogout(page: Page) {
  await page.goto('/wp-login.php?action=logout', { waitUntil: 'networkidle' });
  await page.getByRole('link', { name: 'log out' }).click();
  await page.waitForURL(/\/wp-login\.php\?loggedout=true/);
}

async function performLogin(page: Page, username: string, password: string) {
  await page.goto('/wp-login.php', { waitUntil: 'networkidle' });
  await page.locator('#user_login').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.getByRole('button', { name: 'Log In' }).click();
  await page.waitForURL((url) => {
    const pathname = new URL(url).pathname;
    return pathname.includes('/wp-admin') || pathname.includes('/profile');
  }, { waitUntil: 'networkidle' });
}