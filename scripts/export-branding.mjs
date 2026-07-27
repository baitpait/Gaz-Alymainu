/**
 * Export SVG branding assets to PNG for PDF, favicon, and legacy img tags.
 * Business purpose: keep vector source (SVG) while supplying raster fallbacks.
 */
import puppeteer from 'puppeteer';
import { readFileSync, mkdirSync, copyFileSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const brandingSrc = join(root, 'branding');
const publicBranding = join(root, 'public', 'branding');
const publicRoot = join(root, 'public');

mkdirSync(publicBranding, { recursive: true });

const exports = [
    { svg: 'logo-mark.svg', out: 'logo-mark.png', width: 256, height: 256 },
    { svg: 'logo-mark.svg', out: 'logo.png', width: 256, height: 256 },
    { svg: 'logo-mark.svg', out: 'logo-print.png', width: 512, height: 512 },
    { svg: 'logo-full.svg', out: 'logo-full.png', width: 640, height: 160 },
    { svg: 'logo-mark-light.svg', out: 'logo-mark-light.png', width: 256, height: 256 },
];

for (const name of ['logo-mark.svg', 'logo-full.svg', 'logo-mark-light.svg']) {
    copyFileSync(join(brandingSrc, name), join(publicBranding, name));
}

const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
});

for (const item of exports) {
    const svgPath = join(brandingSrc, item.svg);
    const outPath = join(publicBranding, item.out);
    const svg = readFileSync(svgPath, 'utf8');
    const html = `<!DOCTYPE html><html><body style="margin:0;background:transparent;">
        ${svg.replace('<svg', `<svg width="${item.width}" height="${item.height}"`)}
    </body></html>`;

    const page = await browser.newPage();
    await page.setViewport({ width: item.width, height: item.height, deviceScaleFactor: 2 });
    await page.setContent(html, { waitUntil: 'networkidle0' });
    await page.screenshot({
        path: outPath,
        omitBackground: true,
        clip: { x: 0, y: 0, width: item.width, height: item.height },
    });
    await page.close();
    console.log('Exported', item.out);
}

await browser.close();

copyFileSync(join(publicBranding, 'logo-mark.png'), join(publicRoot, 'favicon.png'));

console.log('Branding export complete.');
