const { test, expect } = require('@playwright/test');

test.describe('SJR Rich Text Editor', () => {
  test.beforeEach(async ({ page }) => {
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
    await page.waitForTimeout(500);
  });

  test('should load Quill editor on new material (Tambah Material)', async ({ page }) => {
    // Click Tambah Material
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    // Find the new row (last one) and click "+ Ajukan Material Baru" to show the panel
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    // Now the editor should be visible
    const editor = newRow.locator('.sjr-quill-shell');
    await expect(editor).toBeVisible();
    
    const toolbar = newRow.locator('.sjr-quill-toolbar');
    await expect(toolbar).toBeVisible();
    
    const fontSelect = newRow.locator('select.ql-font');
    await expect(fontSelect).toBeVisible();
    
    const sizeSelect = newRow.locator('select.ql-size');
    await expect(sizeSelect).toBeVisible();
  });

  test('bold should work on new material', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('PENGUJIAN RICH TEXT SJR');
    await page.keyboard.press('Control+A');
    await newRow.locator('.ql-bold').click();
    
    const html = await newRow.locator('.sjr-quill-editor .ql-editor').innerHTML();
    expect(html).toContain('<strong>');
  });

  test('italic should work on new material', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('PENGUJIAN RICH TEXT SJR');
    await page.keyboard.press('Control+A');
    await newRow.locator('.ql-italic').click();
    
    const html = await newRow.locator('.sjr-quill-editor .ql-editor').innerHTML();
    expect(html).toContain('<em>');
  });

  test('underline should work on new material', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('PENGUJIAN RICH TEXT SJR');
    await page.keyboard.press('Control+A');
    await newRow.locator('.ql-underline').click();
    
    const html = await newRow.locator('.sjr-quill-editor .ql-editor').innerHTML();
    expect(html).toContain('<u>');
  });

  test('font family should work on new material', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('PENGUJIAN RICH TEXT SJR');
    await page.keyboard.press('Control+A');
    await newRow.locator('select.ql-font').selectOption('georgia');
    
    const html = await newRow.locator('.sjr-quill-editor .ql-editor').innerHTML();
    // Quill uses class-based font formatting: ql-font-georgia
    expect(html).toContain('ql-font-georgia');
  });

  test('font size should work on new material', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('PENGUJIAN RICH TEXT SJR');
    await page.keyboard.press('Control+A');
    await newRow.locator('select.ql-size').selectOption('24px');
    
    const html = await newRow.locator('.sjr-quill-editor .ql-editor').innerHTML();
    // Quill uses inline style for size: font-size: 24px
    expect(html).toContain('font-size: 24px');
  });

  test('text color should work on new material', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('PENGUJIAN RICH TEXT SJR');
    await page.keyboard.press('Control+A');
    await newRow.locator('.ql-color').fill('#ff0000');
    await newRow.locator('.ql-color').dispatchEvent('input');
    
    const html = await newRow.locator('.sjr-quill-editor .ql-editor').innerHTML();
    expect(html).toContain('color: rgb(255, 0, 0)');
  });

  test('alignment should work on new material', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('PENGUJIAN RICH TEXT SJR');
    await page.keyboard.press('Control+A');
    await newRow.locator('.ql-align[data-value="center"]').click();
    
    const html = await newRow.locator('.sjr-quill-editor .ql-editor').innerHTML();
    // Quill uses class-based alignment: ql-align-center
    expect(html).toContain('ql-align-center');
  });

  test('bullet list should work on new material', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('Line 1');
    await page.keyboard.press('Enter');
    await page.keyboard.type('Line 2');
    await page.keyboard.press('Control+A');
    await newRow.locator('.ql-list[value="bullet"]').click();
    
    const html = await newRow.locator('.sjr-quill-editor .ql-editor').innerHTML();
    // Quill uses data-list attribute on ol/ul
    expect(html).toContain('data-list="bullet"');
  });

  test('numbered list should work on new material', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('Line 1');
    await page.keyboard.press('Enter');
    await page.keyboard.type('Line 2');
    await page.keyboard.press('Control+A');
    await newRow.locator('.ql-list[value="ordered"]').click();
    
    const html = await newRow.locator('.sjr-quill-editor .ql-editor').innerHTML();
    // Quill uses data-list attribute
    expect(html).toContain('data-list="ordered"');
  });

  test('add multiple materials should create independent editors', async ({ page }) => {
    const initialCount = await page.locator('.dynamic-material-row').count();
    
    // Add first new material
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    const row1 = page.locator('.dynamic-material-row').nth(initialCount);
    await row1.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    // Add second new material
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    const row2 = page.locator('.dynamic-material-row').last();
    await row2.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    // Test first editor
    await row1.locator('.sjr-quill-editor').click();
    await page.keyboard.type('FIRST EDITOR');
    await page.keyboard.press('Control+A');
    await row1.locator('.ql-bold').click();
    const html1 = await row1.locator('.sjr-quill-editor .ql-editor').innerHTML();
    expect(html1).toContain('<strong>');
    
    // Test second editor
    await row2.locator('.sjr-quill-editor').click();
    await page.keyboard.type('SECOND EDITOR');
    await page.keyboard.press('Control+A');
    await row2.locator('.ql-italic').click();
    const html2 = await row2.locator('.sjr-quill-editor .ql-editor').innerHTML();
    expect(html2).toContain('<em>');
    
    // Verify first editor unchanged
    const html1Again = await row1.locator('.sjr-quill-editor .ql-editor').innerHTML();
    expect(html1Again).toContain('<strong>');
    expect(html1Again).not.toContain('<em>');
  });

  test('save draft should persist HTML', async ({ page }) => {
    await page.locator('[data-add="materials"]').first().click();
    await page.waitForTimeout(500);
    
    const newRow = page.locator('.dynamic-material-row').last();
    await newRow.locator('.sjr-new-material-toggle').click();
    await page.waitForTimeout(500);
    
    const editor = newRow.locator('.sjr-quill-editor');
    await editor.click();
    await page.keyboard.type('TEST SAVE DRAFT');
    await page.keyboard.press('Control+A');
    await newRow.locator('.ql-bold').click();
    await newRow.locator('.ql-italic').click();
    
    // Trigger blur to sync content to hidden textarea
    await newRow.locator('.sjr-quill-editor .ql-editor').evaluate(el => {
      el.dispatchEvent(new Event('blur', { bubbles: true }));
    });
    await page.waitForTimeout(200);
    
    // Get the hidden textarea value directly via evaluate to ensure sync
    const hiddenValue = await newRow.locator('textarea[data-spec-visible]').evaluate(el => el.value);
    expect(hiddenValue).toContain('<strong>');
    expect(hiddenValue).toContain('<em>');
    
    // Also verify form submit works - submit the form
    await page.locator('form[data-tech-form]').first().evaluate(form => form.submit());
    await page.waitForLoadState('networkidle');
    
    // Check if content persisted after reload
    const hiddenTextarea = page.locator('textarea[data-spec-visible]').last();
    const value = await hiddenTextarea.inputValue();
    expect(value).toContain('<strong>');
    expect(value).toContain('<em>');
  });
});