const { test, expect } = require('@playwright/test');

test.describe('SJR Rich Text Editor', () => {
  test('should load Quill editor on first material', async ({ page }) => {
    // Login first
    await page.goto('http://localhost:8000/login');
    await page.fill('input[name="email"]', 'prod04-asst-manager-e2e@example.test');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
    
    await page.goto('http://localhost:8000/sjr/production/multi-item/SJR-2026-00005');
    await page.waitForLoadState('networkidle');
    
    // Open all item cards
    await page.evaluate(() => {
      document.querySelectorAll('details.item-card').forEach(d => d.open = true);
    });
    await page.waitForTimeout(1000);
    
    // Check console
    page.on('console', msg => console.log('Console:', msg.type(), msg.text()));
    page.on('pageerror', err => console.log('Page error:', err.message));
    
    const editor = page.locator('.sjr-quill-shell').first();
    await expect(editor).toBeVisible({ timeout: 30000 });
    
    const toolbar = page.locator('.sjr-quill-toolbar').first();
    await expect(toolbar).toBeVisible();
    
    const fontSelect = page.locator('.ql-font').first();
    await expect(fontSelect).toBeVisible();
    
    const sizeSelect = page.locator('.ql-size').first();
    await expect(sizeSelect).toBeVisible();
  });
});