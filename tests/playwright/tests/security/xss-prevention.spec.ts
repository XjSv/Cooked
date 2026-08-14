import { test, expect } from '../../utils/fixtures';
import { deletePost } from '../../utils/wp-cli';

var postId: number;

declare global {
  interface Window {
    tinyMCE: {
      get: (id: string) => {
        setContent: (content: string) => void;
      };
      editors: { [key: string]: any };
    };
    xssTriggered?: boolean;
  }
}

const XSS_PAYLOADS = {
  scriptTag: '<script>window.xssTriggered=true;alert("XSS")</script>',
  imgOnerror: '<img src=x onerror="window.xssTriggered=true;alert(\'XSS\')">',
  svgOnload: '<svg onload="window.xssTriggered=true;alert(\'XSS\')">',
  iframePayload: '<iframe src="javascript:window.xssTriggered=true;alert(\'XSS\')"></iframe>',
  eventHandler: '<div onclick="window.xssTriggered=true;alert(\'XSS\')">click me</div>',
  bodyOnload: '<body onload="window.xssTriggered=true;alert(\'XSS\')">',
  encodedScript: '&lt;script&gt;window.xssTriggered=true;alert("XSS")&lt;/script&gt;',
  nestedScript: '<<script>script>window.xssTriggered=true;alert("XSS")<</script>/script>',
};

test.describe('XSS Prevention Tests', () => {
  test('Recipe title should be escaped against XSS on output', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();

    let xssAlertTriggered = false;
    adminPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        xssAlertTriggered = true;
      }
      await dialog.dismiss();
    });

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

    const xssTitle = 'Test XSS ' + XSS_PAYLOADS.scriptTag + ' ' + Date.now();

    await adminPage.getByLabel('Recipe title ...').fill(xssTitle);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);

    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();

    const frontendPage = await adminContext.newPage();

    let frontendXssTriggered = false;
    frontendPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        frontendXssTriggered = true;
      }
      await dialog.dismiss();
    });

    await frontendPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    const xssTriggeredOnPage = await frontendPage.evaluate(() => window.xssTriggered === true);

    expect(xssAlertTriggered).toBe(false);
    expect(frontendXssTriggered).toBe(false);
    expect(xssTriggeredOnPage).toBe(false);

    await frontendPage.close();
    deletePost(postId);
  });

  test('Recipe excerpt should be sanitized against XSS', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();

    let xssAlertTriggered = false;
    adminPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        xssAlertTriggered = true;
      }
      await dialog.dismiss();
    });

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

    const title = 'Test Recipe XSS Excerpt: ' + Date.now();

    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');

    await adminPage.evaluate((payload) => {
      const excerptEditor = window.tinyMCE.get('_recipe_settings_excerpt');
      if (excerptEditor) {
        excerptEditor.setContent(payload);
      }
    }, XSS_PAYLOADS.imgOnerror);

    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);

    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();

    const frontendPage = await adminContext.newPage();

    let frontendXssTriggered = false;
    frontendPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        frontendXssTriggered = true;
      }
      await dialog.dismiss();
    });

    await frontendPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    const xssTriggeredOnPage = await frontendPage.evaluate(() => window.xssTriggered === true);

    expect(xssAlertTriggered).toBe(false);
    expect(frontendXssTriggered).toBe(false);
    expect(xssTriggeredOnPage).toBe(false);

    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('onerror="window.xssTriggered');
    expect(pageContent).not.toContain("onerror='window.xssTriggered");

    await frontendPage.close();
    deletePost(postId);
  });

  test('Recipe notes should be sanitized against XSS', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();

    let xssAlertTriggered = false;
    adminPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        xssAlertTriggered = true;
      }
      await dialog.dismiss();
    });

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

    const title = 'Test Recipe XSS Notes: ' + Date.now();

    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');

    await adminPage.evaluate((payload) => {
      const notesEditor = window.tinyMCE.get('_recipe_settings_notes');
      if (notesEditor) {
        notesEditor.setContent(payload);
      }
    }, XSS_PAYLOADS.svgOnload);

    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);

    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();

    const frontendPage = await adminContext.newPage();

    let frontendXssTriggered = false;
    frontendPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        frontendXssTriggered = true;
      }
      await dialog.dismiss();
    });

    await frontendPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    const xssTriggeredOnPage = await frontendPage.evaluate(() => window.xssTriggered === true);

    expect(xssAlertTriggered).toBe(false);
    expect(frontendXssTriggered).toBe(false);
    expect(xssTriggeredOnPage).toBe(false);

    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('onload="window.xssTriggered');
    expect(pageContent).not.toContain("onload='window.xssTriggered");

    await frontendPage.close();
    deletePost(postId);
  });

  test('Recipe directions should be sanitized against XSS', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();

    let xssAlertTriggered = false;
    adminPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        xssAlertTriggered = true;
      }
      await dialog.dismiss();
    });

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

    const title = 'Test Recipe XSS Directions: ' + Date.now();

    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');

    await adminPage.evaluate((payload) => {
      const directionEditors = Object.keys(window.tinyMCE.editors).filter(id => /^direction-\d+-content$/.test(id));
      if (directionEditors.length > 0) {
        const directionEditor = window.tinyMCE.get(directionEditors[0]);
        if (directionEditor) {
          directionEditor.setContent(payload);
        }
      }
    }, XSS_PAYLOADS.eventHandler);

    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);

    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();

    const frontendPage = await adminContext.newPage();

    let frontendXssTriggered = false;
    frontendPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        frontendXssTriggered = true;
      }
      await dialog.dismiss();
    });

    await frontendPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    const xssTriggeredOnPage = await frontendPage.evaluate(() => window.xssTriggered === true);

    expect(xssAlertTriggered).toBe(false);
    expect(frontendXssTriggered).toBe(false);
    expect(xssTriggeredOnPage).toBe(false);

    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('onclick="window.xssTriggered');
    expect(pageContent).not.toContain("onclick='window.xssTriggered");

    await frontendPage.close();
    deletePost(postId);
  });

  test('Recipe ingredient names should be sanitized against XSS', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();

    let xssAlertTriggered = false;
    adminPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        xssAlertTriggered = true;
      }
      await dialog.dismiss();
    });

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

    const title = 'Test Recipe XSS Ingredients: ' + Date.now();

    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');

    await adminPage.evaluate((payload) => {
      const ingredientBlocks = document.querySelectorAll('.cooked-ingredient-block');
      if (ingredientBlocks.length > 0) {
        const firstIngredient = ingredientBlocks[0];
        const amountInput = firstIngredient.querySelector('input[data-ingredient-part="amount"]') as HTMLInputElement;
        const itemInput = firstIngredient.querySelector('input[data-ingredient-part="name"]') as HTMLInputElement;

        if (amountInput) amountInput.value = '1';
        if (itemInput) itemInput.value = payload;
      }
    }, XSS_PAYLOADS.scriptTag);

    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);

    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();

    const frontendPage = await adminContext.newPage();

    let frontendXssTriggered = false;
    frontendPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        frontendXssTriggered = true;
      }
      await dialog.dismiss();
    });

    await frontendPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    const xssTriggeredOnPage = await frontendPage.evaluate(() => window.xssTriggered === true);

    expect(xssAlertTriggered).toBe(false);
    expect(frontendXssTriggered).toBe(false);
    expect(xssTriggeredOnPage).toBe(false);

    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('<script>window.xssTriggered');

    await frontendPage.close();
    deletePost(postId);
  });

  test('Multiple XSS vectors in single recipe should all be sanitized', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();

    let xssAlertTriggered = false;
    adminPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        xssAlertTriggered = true;
      }
      await dialog.dismiss();
    });

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

    const title = 'XSS Multi Test: ' + Date.now();
    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');

    await adminPage.evaluate((payloads) => {
      const excerptEditor = window.tinyMCE.get('_recipe_settings_excerpt');
      if (excerptEditor) {
        excerptEditor.setContent(payloads.imgOnerror);
      }

      const notesEditor = window.tinyMCE.get('_recipe_settings_notes');
      if (notesEditor) {
        notesEditor.setContent(payloads.svgOnload);
      }

      const directionEditors = Object.keys(window.tinyMCE.editors).filter(id => /^direction-\d+-content$/.test(id));
      if (directionEditors.length > 0) {
        const directionEditor = window.tinyMCE.get(directionEditors[0]);
        if (directionEditor) {
          directionEditor.setContent(payloads.eventHandler);
        }
      }

      const ingredientBlocks = document.querySelectorAll('.cooked-ingredient-block');
      if (ingredientBlocks.length > 0) {
        const firstIngredient = ingredientBlocks[0];
        const amountInput = firstIngredient.querySelector('input[data-ingredient-part="amount"]') as HTMLInputElement;
        const itemInput = firstIngredient.querySelector('input[data-ingredient-part="name"]') as HTMLInputElement;

        if (amountInput) amountInput.value = '1';
        if (itemInput) itemInput.value = payloads.scriptTag;
      }
    }, XSS_PAYLOADS);

    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);

    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();

    const frontendPage = await adminContext.newPage();

    let frontendXssTriggered = false;
    frontendPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        frontendXssTriggered = true;
      }
      await dialog.dismiss();
    });

    await frontendPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });
    await frontendPage.waitForTimeout(1000);

    const xssTriggeredOnPage = await frontendPage.evaluate(() => window.xssTriggered === true);

    expect(xssAlertTriggered).toBe(false);
    expect(frontendXssTriggered).toBe(false);
    expect(xssTriggeredOnPage).toBe(false);

    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('<script>window.xssTriggered');
    expect(pageContent).not.toContain('onerror="window.xssTriggered');
    expect(pageContent).not.toContain("onerror='window.xssTriggered");
    expect(pageContent).not.toContain('onload="window.xssTriggered');
    expect(pageContent).not.toContain("onload='window.xssTriggered");
    expect(pageContent).not.toContain('onclick="window.xssTriggered');
    expect(pageContent).not.toContain("onclick='window.xssTriggered");

    await frontendPage.close();
    deletePost(postId);
  });

  test('Double-encoded XSS should be sanitized', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();

    let xssAlertTriggered = false;
    adminPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        xssAlertTriggered = true;
      }
      await dialog.dismiss();
    });

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

    const title = 'Test Recipe Double Encode XSS: ' + Date.now();

    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');

    await adminPage.evaluate((payload) => {
      const excerptEditor = window.tinyMCE.get('_recipe_settings_excerpt');
      if (excerptEditor) {
        excerptEditor.setContent(payload);
      }
    }, XSS_PAYLOADS.encodedScript);

    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);

    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();

    const frontendPage = await adminContext.newPage();

    let frontendXssTriggered = false;
    frontendPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        frontendXssTriggered = true;
      }
      await dialog.dismiss();
    });

    await frontendPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    const xssTriggeredOnPage = await frontendPage.evaluate(() => window.xssTriggered === true);

    expect(xssAlertTriggered).toBe(false);
    expect(frontendXssTriggered).toBe(false);
    expect(xssTriggeredOnPage).toBe(false);

    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('<script>window.xssTriggered');

    await frontendPage.close();
    deletePost(postId);
  });
});
