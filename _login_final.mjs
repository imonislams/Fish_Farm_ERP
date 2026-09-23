export default async function run(page, ui) {
    const out = {};

    // ---------- loading state: click submit and observe the button immediately ----------
    await page.locator('input[name="email"]').fill("admin@example.com");
    await page.locator('input[name="password"]').fill("AdminFishFarm!2026");

    // Capture the button state right after the submit fires (before navigation).
    const clicked = page.locator("[data-login-submit]").click();
    await page.waitForTimeout(120);
    out.loadingState = {
        disabled: await page
            .locator("[data-login-submit]")
            .isDisabled()
            .catch(() => null),
        label: await page
            .locator("[data-login-label]")
            .innerText()
            .catch(() => null),
        ariaBusy: await page
            .locator("[data-login-submit]")
            .getAttribute("aria-busy")
            .catch(() => null),
        spinner: await page
            .locator("[data-login-spinner]")
            .count()
            .catch(() => null),
    };

    await clicked;
    await page.waitForSelector("aside", { timeout: 45000 }).catch(() => {});
    out.afterLogin = {
        url: page.url().replace(/^https?:\/\/[^/]+/, ""),
        heading: await page
            .locator("h1")
            .first()
            .innerText()
            .catch(() => null),
        sidebar: await page.locator("aside").count(),
    };

    // ---------- logout then confirm we land on ONE login page (previous task intact) ----------
    await page.locator('aside button:has-text("Logout")').first().click();
    await page
        .waitForSelector('input[name="email"]', { timeout: 45000 })
        .catch(() => {});
    await page.waitForTimeout(1000);
    out.afterLogout = {
        url: page.url().replace(/^https?:\/\/[^/]+/, ""),
        loginHeadings: await page
            .locator("h1:has-text('Welcome back')")
            .count(),
        emailInputs: await page.locator('input[name="email"]').count(),
        appRoots: await page.locator("#app").count(),
    };

    return out;
}
