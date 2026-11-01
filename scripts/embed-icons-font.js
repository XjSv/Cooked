const { readFileSync, writeFileSync, existsSync } = require('fs');
const { resolve, extname } = require('path');

const pluginRoot = resolve(__dirname, '..');
const defaultWoffPath = resolve(pluginRoot, 'assets/fonts/CookedIcons.woff');
const iconsCssPath = resolve(pluginRoot, 'assets/scss/base/_icons.scss');
const dataUriPattern = /url\(data:application\/font-woff;charset=utf-8;base64,[^)]+\)/;

const woffPath = process.argv[2]
  ? resolve(pluginRoot, process.argv[2])
  : defaultWoffPath;

if (extname(woffPath).toLowerCase() !== '.woff') {
  console.error(`Expected a .woff file, got: ${woffPath}`);
  process.exit(1);
}

if (!existsSync(woffPath)) {
  console.error(`Font file not found: ${woffPath}`);
  console.error('Export from FontForge and copy to assets/fonts/CookedIcons.woff, or pass a path.');
  process.exit(1);
}

const woffBuffer = readFileSync(woffPath);
const base64 = woffBuffer.toString('base64');
const iconsCss = readFileSync(iconsCssPath, 'utf8');

if (!dataUriPattern.test(iconsCss)) {
  console.error(`Could not find embedded font data URI in ${iconsCssPath}`);
  process.exit(1);
}

const updatedCss = iconsCss.replace(
  dataUriPattern,
  `url(data:application/font-woff;charset=utf-8;base64,${base64})`
);

writeFileSync(iconsCssPath, updatedCss);

console.log(`Embedded ${woffBuffer.length} bytes from ${woffPath}`);
console.log(`Updated ${iconsCssPath}`);
console.log('Run "bun run build" when ready to rebuild the stylesheets');
