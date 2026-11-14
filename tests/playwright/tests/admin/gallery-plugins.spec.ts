import { test, expect } from '../../utils/fixtures';
import { deletePost, wpCliArgs } from '../../utils/wp-cli';

const REQUIRED_PLUGINS = ['envira-gallery-lite', 'soliloquy-lite', 'revslider'];

type SeededGalleries = {
  envira: number;
  soliloquy: number;
  slider: number;
  alias: string;
  recipe: number;
  title: string;
};

let seeded: SeededGalleries | null = null;

function activePluginNames(): string[] {
  return wpCliArgs(['plugin', 'list', '--status=active', '--field=name'])
    .split('\n')
    .map((name) => name.trim())
    .filter(Boolean);
}

function seedGalleries(alias: string): SeededGalleries {
  const php = `
    global $wpdb;
    $alias = ${JSON.stringify(alias)};
    $wpdb->insert( $wpdb->prefix . 'revslider_sliders', array(
      'title' => 'Cooked PW Slider',
      'alias' => $alias,
      'params' => '{}',
      'settings' => '',
      'type' => '',
    ) );
    $slider_id = (int) $wpdb->insert_id;
    echo wp_json_encode( array(
      'envira' => (int) wp_insert_post( array(
        'post_type' => 'envira',
        'post_status' => 'publish',
        'post_title' => 'Cooked PW Envira',
      ) ),
      'soliloquy' => (int) wp_insert_post( array(
        'post_type' => 'soliloquy',
        'post_status' => 'publish',
        'post_title' => 'Cooked PW Soliloquy',
      ) ),
      'slider' => $slider_id,
      'alias' => $alias,
      'recipe' => 0,
      'title' => '',
    ) );
  `;
  return JSON.parse(wpCliArgs(['eval', php])) as SeededGalleries;
}

function cleanupGalleries(data: SeededGalleries | null): void {
  if (!data) {
    return;
  }
  const php = `
    global $wpdb;
    wp_delete_post( ${Number(data.envira) || 0}, true );
    wp_delete_post( ${Number(data.soliloquy) || 0}, true );
    wp_delete_post( ${Number(data.recipe) || 0}, true );
    $title = ${JSON.stringify(data.title || '')};
    if ( $title ) {
      $ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s", $title ) );
      foreach ( $ids as $id ) {
        wp_delete_post( (int) $id, true );
      }
    }
    $wpdb->delete( $wpdb->prefix . 'revslider_sliders', array( 'id' => ${Number(data.slider) || 0} ) );
  `;
  try {
    wpCliArgs(['eval', php]);
  } catch {
    deletePost(data.recipe);
  }
}

test.describe('Third-party recipe galleries', () => {
  test.beforeAll(() => {
    const missing = REQUIRED_PLUGINS.filter((name) => !activePluginNames().includes(name));
    test.skip(missing.length > 0, `Inactive gallery plugins: ${missing.join(', ')}`);
    seeded = seedGalleries(`cooked-pw-${Date.now()}`);
  });

  test.afterAll(() => {
    cleanupGalleries(seeded);
    seeded = null;
  });

  test('offers Envira, Soliloquy, and Slider Revolution and saves the alias', async ({ adminContext }) => {
    expect(seeded).toBeTruthy();
    const data = seeded as SeededGalleries;
    const adminPage = await adminContext.newPage();
    const title = `Gallery plugins ${Date.now()}`;
    data.title = title;

    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    await adminPage.getByLabel('Recipe title ...').fill(title);
    await adminPage.locator('#cooked-recipe-tab-gallery').click();

    const typeSelect = adminPage.locator('select[name="_recipe_settings[gallery][type]"]');
    await expect(typeSelect.locator('option[value="envira"]')).toHaveCount(1);
    await expect(typeSelect.locator('option[value="soliloquy"]')).toHaveCount(1);
    await expect(typeSelect.locator('option[value="revslider"]')).toHaveCount(1);

    await typeSelect.selectOption('envira');
    const enviraSelect = adminPage.locator('select[name="_recipe_settings[gallery][envira]"]');
    await expect(enviraSelect).toBeVisible();
    await expect(enviraSelect.locator(`option[value="${data.envira}"]`)).toHaveText('Cooked PW Envira');

    await typeSelect.selectOption('soliloquy');
    const soliloquySelect = adminPage.locator('select[name="_recipe_settings[gallery][soliloquy]"]');
    await expect(soliloquySelect).toBeVisible();
    await expect(soliloquySelect.locator(`option[value="${data.soliloquy}"]`)).toHaveText('Cooked PW Soliloquy');

    await typeSelect.selectOption('revslider');
    const revsliderSelect = adminPage.locator('select[name="_recipe_settings[gallery][revslider]"]');
    await expect(revsliderSelect).toBeVisible();
    await revsliderSelect.selectOption(data.alias);
    await expect(revsliderSelect.locator(`option[value="${data.alias}"]`)).toHaveText('Cooked PW Slider');

    const invalid = await adminPage.locator('#post').evaluate((form: HTMLFormElement) => {
      const bad = Array.from(form.querySelectorAll(':invalid')).map((element) => {
        const field = element as HTMLInputElement;
        return field.name || field.id || field.outerHTML.slice(0, 120);
      });
      return { valid: form.checkValidity(), bad };
    });
    expect(invalid, JSON.stringify(invalid)).toEqual({ valid: true, bad: [] });

    await adminPage.locator('#publish').click();
    await adminPage.waitForURL(/[?&]message=/, { timeout: 15000 });
    await expect(adminPage.locator('.notice.notice-success')).toBeVisible();

    const postIdMatch = adminPage.url().match(/post=(\d+)/);
    data.recipe = postIdMatch ? parseInt(postIdMatch[1], 10) : 0;
    expect(data.recipe).toBeGreaterThan(0);

    const gallery = JSON.parse(
      wpCliArgs([
        'eval',
        `echo wp_json_encode( get_post_meta( ${data.recipe}, '_recipe_settings', true )['gallery'] );`,
      ])
    ) as { type?: string; revslider?: string };

    expect(gallery.type).toBe('revslider');
    expect(gallery.revslider).toBe(data.alias);
    expect(gallery.revslider).not.toBe('0');

    await adminPage.goto(`/?post_type=cp_recipe&p=${data.recipe}`, { waitUntil: 'networkidle' });
    await expect(adminPage.locator('.cooked-recipe-gallery-revslider')).toHaveCount(1);
    await expect(adminPage.locator('body')).not.toContainText('[rev_slider alias="0"]');
  });
});
