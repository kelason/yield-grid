import { mkdir, readFile, writeFile } from 'node:fs/promises'
import { Buffer } from 'node:buffer'
import { chromium } from '@playwright/test'
import { BRAND_MARK_PATH } from '../src/constants/brand.js'
import { DESIGN_COLORS } from '../src/constants/designTokens.js'

const ROOT = new URL('../', import.meta.url)
const BRAND_DIRECTORY = new URL('public/brand/', ROOT)
const REPORT_DIRECTORY = new URL('../backend/resources/brand/', ROOT)
const LOGO_WIDTH = 160
const LOGO_HEIGHT = 32
const EXPORT_SCALE = 3
const FAVICON_SIZE = 32
const TOUCH_ICON_SIZE = 180
const ICO_HEADER_BYTES = 22
const font = await readFile(new URL('public/fonts/lora-normal-latin.woff2', ROOT))
const mark = `<path d="${BRAND_MARK_PATH}"/>`
const logo = `<svg xmlns="http://www.w3.org/2000/svg" width="${LOGO_WIDTH}" height="${LOGO_HEIGHT}" viewBox="0 0 ${LOGO_WIDTH} ${LOGO_HEIGHT}" role="img" aria-label="YieldGrid">
  <style>@font-face{font-family:Lora;src:url(data:font/woff2;base64,${font.toString('base64')}) format('woff2');font-weight:400 700}text{font-family:Lora,serif;font-size:24px;font-weight:700;letter-spacing:-.6px}</style>
  <g fill="${DESIGN_COLORS.moss[700]}">${mark}<text x="44" y="24">YieldGrid</text></g>
</svg>\n`
const icon = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">
  <rect width="40" height="40" rx="10" fill="${DESIGN_COLORS.stone[100]}"/>
  <g transform="translate(4 4)" fill="${DESIGN_COLORS.moss[700]}">${mark}</g>
</svg>\n`

function icoWithPng(png) {
  const header = Buffer.alloc(ICO_HEADER_BYTES)
  header.writeUInt16LE(1, 2) // ICO format
  header.writeUInt16LE(1, 4) // One image
  header.writeUInt8(FAVICON_SIZE, 6)
  header.writeUInt8(FAVICON_SIZE, 7)
  header.writeUInt16LE(1, 10) // Color planes
  header.writeUInt16LE(32, 12) // RGBA bits per pixel
  header.writeUInt32LE(png.length, 14)
  header.writeUInt32LE(ICO_HEADER_BYTES, 18)
  return Buffer.concat([header, png])
}

await mkdir(BRAND_DIRECTORY, { recursive: true })
await mkdir(REPORT_DIRECTORY, { recursive: true })
await writeFile(new URL('yieldgrid-logo.svg', BRAND_DIRECTORY), logo)
await writeFile(new URL('public/favicon.svg', ROOT), icon)
const browser = await chromium.launch()
try {
  const page = await browser.newPage({ deviceScaleFactor: EXPORT_SCALE })
  await page.setContent(`<body style="margin:0">${logo}</body>`)
  await page.evaluate(() => document.fonts.ready)
  const logoPng = await page.locator('svg').screenshot({ omitBackground: true })
  await writeFile(new URL('yieldgrid-logo.png', BRAND_DIRECTORY), logoPng)
  await writeFile(new URL('yieldgrid-logo.png', REPORT_DIRECTORY), logoPng)
  const icons = await browser.newPage({ deviceScaleFactor: 1 })
  await icons.setContent(`<body style="margin:0">${icon}</body>`)
  await icons.setViewportSize({ width: FAVICON_SIZE, height: FAVICON_SIZE })
  await writeFile(new URL('public/favicon.ico', ROOT), icoWithPng(await icons.screenshot()))
  await icons.setViewportSize({ width: TOUCH_ICON_SIZE, height: TOUCH_ICON_SIZE })
  await writeFile(new URL('public/apple-touch-icon.png', ROOT), await icons.screenshot())
} finally {
  await browser.close()
}
