import { test, expect } from '../../utils/fixtures';
import { deletePostsByTitle } from '../../utils/wp-cli';

const TIMER_SHORTCODE = '[cooked-timer minutes="5"]5 Minutes[/cooked-timer]';

declare global {
  interface Window {
    tinyMCE: {
      get: (id: string) => {
        getContent: (args?: { format?: string }) => string;
      } | null;
    };
  }
}

test.describe('Bulk Add directions with cooked-timer shortcode', () => {
  let title: string;

  test.afterEach(() => {
    if (title) {
      deletePostsByTitle(title, 'draft');
      deletePostsByTitle(title, 'auto-draft');
    }
  });

  test('keeps quoted timer shortcode intact in preview and direction field', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    title = 'Bulk Add Timer Playwright: ' + Date.now();

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    await adminPage.getByLabel('Recipe title ...').fill(title);

    await adminPage.click('#cooked-recipe-tab-directions', { force: true });
    await adminPage.locator('#cooked-recipe-tab-content-directions .cooked-bulk-add-button').click();

    const textarea = adminPage.locator('#cooked-bulk-add-textarea');
    await expect(textarea).toBeVisible();
    await textarea.fill(TIMER_SHORTCODE);

    const preview = adminPage.locator('.cooked-bulk-add-preview-text');
    await expect(preview).toHaveValue(TIMER_SHORTCODE);

    await adminPage.locator('.cooked-bulk-add-submit').click();
    await expect(adminPage.locator('#cooked-bulk-add-overlay')).toBeHidden();

    const directionContents = await adminPage.evaluate(() => {
      const values: string[] = [];
      const textareas = document.querySelectorAll(
        '#cooked-directions-builder textarea[data-direction-part="content"]'
      );
      textareas.forEach((el) => {
        const textareaEl = el as HTMLTextAreaElement;
        const editor = window.tinyMCE && textareaEl.id ? window.tinyMCE.get(textareaEl.id) : null;
        values.push(editor ? editor.getContent({ format: 'text' }) : textareaEl.value);
      });
      return values;
    });

    expect(directionContents.some((content) => content.includes(TIMER_SHORTCODE))).toBeTruthy();
  });
});
