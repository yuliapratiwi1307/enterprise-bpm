const { test, expect } = require('@playwright/test');

test.describe('SJR Rich Text Editor', () => {
  test('login and check page', async ({ page }) => {
    await page.goto('http://localhost:8000/login');
    console.log('On login page');
    
    await page.fill('input[name="email"]', 'prod04-asst-manager-e2e@example.test');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    console.log('After login, URL:', page.url());
    console.log('Title:', await page.title());
    
    // Check for errors
    const bodyText = await page.locator('body').innerText();
    console.log('Body:', bodyText.substring(0, 500));
    
    // Try to go directly to the SJR page
    await page.goto('http://localhost:8000/sjr/production/multi-item/SJR-2026-00005');
    await page.waitForLoadState('networkidle');
    console.log('On detail page, URL:', page.url());
    console.log('Title:', await page.title());
    
    // Check for editor
    const shells = await page.locator('.sjr-quill-shell').count();
    console.log('Quill shells:', shells);
    
    const textareas = await page.locator('textarea[data-spec-visible]').count();
    console.log('Textareas:', textareas);
    
    // Check console
    page.on('console', msg => console.log('Console:', msg.type(), msg.text()));
    page.on('pageerror', err => console.log('Page error:', err.message));
    
    await page.waitForTimeout(5000);
  });
});