
// @vitest-environment node
import { describe, it, expect } from 'vitest'
import { setup, createPage } from '@nuxt/test-utils/e2e'

describe('Login E2E', async () => {
  await setup({
    // host: 'http://localhost:3000',
    browser: true,
    setupTimeout: 120000
  })

  it('should login successfully and redirect to home', async () => {
    const page = await createPage('/login')
    
    // Wait for the form to be visible
    await page.waitForSelector('form')
    
    // Fill in credentials
    await page.fill('input[name="username"]', 'admin')
    await page.fill('input[name="password"]', 'password123')
    
    // Click login button
    await page.click('button[type="submit"]')
    
    // Wait for navigation to home page
    await page.waitForURL('**/')
    
    // Verify we are on the home page
    const url = page.url()
    // The URL should end with / or not have /login
    expect(url).not.toContain('/login')
    
    // Check for content on the home page
    // index.vue has "SAQ" text
    await page.waitForSelector('text=SAQ')
  }, 30000)
})
