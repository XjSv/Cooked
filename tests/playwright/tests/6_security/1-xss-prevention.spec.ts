import { test as base, expect } from '@playwright/test';
import { ensureValidAuth, getAuthPath } from '../../utils/auth';
import { execSync } from 'child_process';

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

// XSS payloads to test
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

// Create a fixture for authentication
const test = base.extend({
  adminContext: async ({ browser }, use) => {
    const context = await browser.newContext({
      storageState: getAuthPath('mtresova')
    });
    const page = await context.newPage();
    await ensureValidAuth(page, 'mtresova', 'password');
    await use(context);
    await page.close();
    await context.close();
  }
});

test.describe('XSS Prevention Tests', () => {
  // These tests verify that XSS payloads are properly sanitized on save or escaped on output
  
  test('Recipe title should be escaped against XSS on output', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    
    // Track if XSS is triggered via alert dialogs
    let xssAlertTriggered = false;
    adminPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        xssAlertTriggered = true;
      }
      await dialog.dismiss();
    });

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    
    const xssTitle = 'Test XSS ' + XSS_PAYLOADS.scriptTag + ' ' + Date.now();
    
    // Set recipe title with XSS payload
    await adminPage.getByLabel('Recipe title ...').fill(xssTitle);
    
    // Set minimum required fields
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');
    
    // Publish the recipe
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();
    
    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);
    
    // Get the post ID from the URL
    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();
    
    // View the recipe on the frontend
    const frontendPage = await adminContext.newPage();
    
    // Track XSS on frontend
    let frontendXssTriggered = false;
    frontendPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        frontendXssTriggered = true;
      }
      await dialog.dismiss();
    });
    
    await frontendPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });
    
    // Check that the XSS was not triggered via window variable or dialogs
    const xssTriggeredOnPage = await frontendPage.evaluate(() => window.xssTriggered === true);
    
    expect(xssAlertTriggered).toBe(false);
    expect(frontendXssTriggered).toBe(false);
    expect(xssTriggeredOnPage).toBe(false);
    
    // Cleanup
    await frontendPage.close();
    
    // Delete the recipe
    execSync(`wp post delete ${postId} --force`);
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
    
    // Set recipe title
    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');
    
    // Set excerpt with XSS payloads via TinyMCE
    await adminPage.evaluate((payload) => {
      const excerptEditor = window.tinyMCE.get('_recipe_settings_excerpt');
      if (excerptEditor) {
        excerptEditor.setContent(payload);
      }
    }, XSS_PAYLOADS.imgOnerror);
    
    // Publish the recipe
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();
    
    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);
    
    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();
    
    // View the recipe on the frontend
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
    
    // Verify onerror handler is not present
    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('onerror="window.xssTriggered');
    expect(pageContent).not.toContain("onerror='window.xssTriggered");
    
    await frontendPage.close();
    execSync(`wp post delete ${postId} --force`);
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
    
    // Set recipe title
    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');
    
    // Set notes with SVG XSS payload via TinyMCE
    await adminPage.evaluate((payload) => {
      const notesEditor = window.tinyMCE.get('_recipe_settings_notes');
      if (notesEditor) {
        notesEditor.setContent(payload);
      }
    }, XSS_PAYLOADS.svgOnload);
    
    // Publish the recipe
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();
    
    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);
    
    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();
    
    // View the recipe on the frontend
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
    
    // Verify onload handler is not present
    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('onload="window.xssTriggered');
    expect(pageContent).not.toContain("onload='window.xssTriggered");
    
    await frontendPage.close();
    execSync(`wp post delete ${postId} --force`);
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
    
    // Set recipe title
    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');
    
    // Set direction content with XSS payload
    await adminPage.evaluate((payload) => {
      const directionEditors = Object.keys(window.tinyMCE.editors).filter(id => /^direction-\d+-content$/.test(id));
      if (directionEditors.length > 0) {
        const directionEditor = window.tinyMCE.get(directionEditors[0]);
        if (directionEditor) {
          directionEditor.setContent(payload);
        }
      }
    }, XSS_PAYLOADS.eventHandler);
    
    // Publish the recipe
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();
    
    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);
    
    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();
    
    // View the recipe on the frontend
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
    
    // Verify onclick handler is not present
    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('onclick="window.xssTriggered');
    expect(pageContent).not.toContain("onclick='window.xssTriggered");
    
    await frontendPage.close();
    execSync(`wp post delete ${postId} --force`);
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
    
    // Set recipe title
    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');
    
    // Set ingredient with XSS payload
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
    
    // Publish the recipe
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();
    
    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);
    
    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();
    
    // View the recipe on the frontend
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
    
    // Verify the script tag is not present as executable
    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('<script>window.xssTriggered');
    
    await frontendPage.close();
    execSync(`wp post delete ${postId} --force`);
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
    
    // Set multiple fields with different XSS payloads
    await adminPage.evaluate((payloads) => {
      // Excerpt with img onerror
      const excerptEditor = window.tinyMCE.get('_recipe_settings_excerpt');
      if (excerptEditor) {
        excerptEditor.setContent(payloads.imgOnerror);
      }
      
      // Notes with svg onload
      const notesEditor = window.tinyMCE.get('_recipe_settings_notes');
      if (notesEditor) {
        notesEditor.setContent(payloads.svgOnload);
      }
      
      // Directions with event handler
      const directionEditors = Object.keys(window.tinyMCE.editors).filter(id => /^direction-\d+-content$/.test(id));
      if (directionEditors.length > 0) {
        const directionEditor = window.tinyMCE.get(directionEditors[0]);
        if (directionEditor) {
          directionEditor.setContent(payloads.eventHandler);
        }
      }
      
      // Ingredient with script tag
      const ingredientBlocks = document.querySelectorAll('.cooked-ingredient-block');
      if (ingredientBlocks.length > 0) {
        const firstIngredient = ingredientBlocks[0];
        const amountInput = firstIngredient.querySelector('input[data-ingredient-part="amount"]') as HTMLInputElement;
        const itemInput = firstIngredient.querySelector('input[data-ingredient-part="name"]') as HTMLInputElement;
        
        if (amountInput) amountInput.value = '1';
        if (itemInput) itemInput.value = payloads.scriptTag;
      }
    }, XSS_PAYLOADS);
    
    // Publish the recipe
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();
    
    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);
    
    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();
    
    // View the recipe on the frontend
    const frontendPage = await adminContext.newPage();
    
    let frontendXssTriggered = false;
    frontendPage.on('dialog', async (dialog) => {
      if (dialog.message().includes('XSS')) {
        frontendXssTriggered = true;
      }
      await dialog.dismiss();
    });
    
    await frontendPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });
    
    // Wait a bit to ensure any scripts have time to execute
    await frontendPage.waitForTimeout(1000);
    
    const xssTriggeredOnPage = await frontendPage.evaluate(() => window.xssTriggered === true);
    
    expect(xssAlertTriggered).toBe(false);
    expect(frontendXssTriggered).toBe(false);
    expect(xssTriggeredOnPage).toBe(false);
    
    // Verify none of the dangerous content is present
    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('<script>window.xssTriggered');
    expect(pageContent).not.toContain('onerror="window.xssTriggered');
    expect(pageContent).not.toContain("onerror='window.xssTriggered");
    expect(pageContent).not.toContain('onload="window.xssTriggered');
    expect(pageContent).not.toContain("onload='window.xssTriggered");
    expect(pageContent).not.toContain('onclick="window.xssTriggered');
    expect(pageContent).not.toContain("onclick='window.xssTriggered");
    
    await frontendPage.close();
    execSync(`wp post delete ${postId} --force`);
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
    
    // Set recipe title
    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');
    
    // Set excerpt with already-encoded XSS payload
    // This tests the scenario where someone tries to bypass by pre-encoding
    await adminPage.evaluate((payload) => {
      const excerptEditor = window.tinyMCE.get('_recipe_settings_excerpt');
      if (excerptEditor) {
        excerptEditor.setContent(payload);
      }
    }, XSS_PAYLOADS.encodedScript);
    
    // Publish the recipe
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();
    
    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);
    
    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();
    
    // View the recipe on the frontend
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
    
    // Verify script is not present as executable
    const pageContent = await frontendPage.content();
    expect(pageContent).not.toContain('<script>window.xssTriggered');
    
    await frontendPage.close();
    execSync(`wp post delete ${postId} --force`);
  });
});
