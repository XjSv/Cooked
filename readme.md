# Cooked - A Modern and Customizable Recipe Plugin for WordPress

Cooked is the absolute best way to create & display recipes with WordPress. SEO optimized (rich snippets), galleries, cooking timers, printable recipes and much more. Now open source!

## Features

- **Responsive Design:** Basil is built with a mobile-first approach, ensuring your site looks great on devices of all sizes.
- **Customization Options:** Easily customize the theme's colors, typography, and layout settings through the user-friendly Customizer.
- **Featured Images:** Showcase your featured images in a beautiful and prominent way on your posts and pages.
- **Social Media Integration:** Connect with your audience by seamlessly integrating your social media profiles.
- **Gutenberg-Ready:** Basil is fully compatible with the new Gutenberg editor, giving you a modern and intuitive writing experience.
- **Translation-Ready:** Prepare your site for a global audience with full translation and localization support.

## Installation/Update (Manual)

1. Download the latest release from the [Cooked repository](https://github.com/XjSv/Cooked) on GitHub.
2. Navigate to your WordPress installation's `wp-content/plugins` directory and extract the downloaded ZIP file there.
3. Activate the Cooked plugin through the WordPress Admin Dashboard by navigating to `Appearance > Plugins`.

## Installation/Update (WordPress.org)

Cooked is available for automatic updates through the WordPress Admin Dashboard. You can install it from the [WordPress.org Plugin Directory](https://wordpress.org/plugins/cooked/).

1. Search for "Cooked" in the WordPress Admin Dashboard under `Plugins > Add New`.
2. Install and activate the Cooked plugin.

## Contributing

We welcome contributions from the community! If you'd like to contribute to Cooked, please follow these steps:

1. Fork the [Cooked repository](https://github.com/XjSv/Cooked) on GitHub.
2. Create a new branch for your feature or bug fix: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a Pull Request describing your changes.

## Documentation

Detailed documentation for Cooked can be found in the [wiki](https://github.com/XjSv/Cooked/wiki).

## Support

If you encounter any issues or have questions about Cooked, please open an issue on the [GitHub repository](https://github.com/XjSv/Cooked/issues).

## License

Cooked is released under the [GPL-3.0 License](https://github.com/XjSv/Cooked/blob/main/LICENSE).

## Credits

Cooked was created by [Boxy Studio](https://www.boxystudio.com) and is now maintained by a team of contributors.

## Generating Language Files

To generate language files for the Cooked plugin, you can use the following command:

```bash
ddev wp i18n make-pot /var/www/html/wp-content/themes/cooked/ /var/www/html/wp-content/themes/cooked/languages/cooked.pot
```
