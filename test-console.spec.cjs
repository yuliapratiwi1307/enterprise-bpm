const { test, expect } = require('@playwright/test');

test.describe('SJR Rich Text Editor Debug', () => {
  test('check console errors', async ({ page }) => {
    const errors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
        console.log('Console Error:', msg.text());
      }
    });
    page.on('pageerror', err => {
      errors.push(err.message);
      console.log('Page Error:', err.message);
    });
    
    await page.goto('http://localhost:8000/login');
    await page.fill('input[name="email"]', 'prod04-asst-manager-e2e@example.test');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');
    
    await page.goto('http://localhost:8000/sjr/production/multi-item/SJR-2026-00005');
    await page.waitForLoadState('networkidle');
    
    await page.waitForTimeout(3000);
    
    console.log('Total errors:', errors.length);
    
    // Check if Vite script loaded
    const scripts = await page.locator('script[src*="sjr-rich-editor"]').count();
    console.log('SJR Rich Editor scripts:', scripts);
    
    // Check if Quill is defined
    const quillDefined = await page.evaluate(() => typeof window.Quill !== 'undefined');
    console.log('Quill defined:', quillDefined);
    
    // Check if start function ran
    const sjrQuillActive = await page.evaluate(() => window.__sjrQuillObserver !== undefined);
    console.log('SJR Quill observer:', sjrQuillActive);
  });
});