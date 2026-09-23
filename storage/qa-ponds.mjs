export default async function run(page, ui) {
  const log = {}
  const email = process.env.FF_EMAIL
  const password = process.env.FF_PASSWORD
  page.setDefaultTimeout(90000)
  page.setDefaultNavigationTimeout(90000)

  const base = 'http://127.0.0.1:8000'
  const go = async (path) => {
    const res = await page.goto(base + path, { waitUntil: 'domcontentloaded' })
    return res ? res.status() : 'no-response'
  }

  // ---- sign in -----------------------------------------------------------
  await page.goto(base + '/login', { waitUntil: 'domcontentloaded' })
  await page.fill('input[name=email]', email)
  await page.fill('input[name=password]', password)
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')])
  log.afterLoginUrl = page.url()

  // ---- pond index + types index -----------------------------------------
  log.indexStatus = await go('/fish-farm/ponds')
  log.indexHeading = await page.locator('h2:has-text("All Ponds")').count()
  log.indexEmptyState = await page.locator('text=No ponds found').count()

  log.typesIndexStatus = await go('/fish-farm/ponds/types')
  log.typesHeading = await page.locator('h2:has-text("Pond Types")').count()

  // ---- create TWO pond types (one will be "in use") ----------------------
  const createType = async (name, desc) => {
    await go('/fish-farm/ponds/types/create')
    await page.fill('input[name=name]', name)
    if (desc) await page.fill('textarea[name=description]', desc)
    await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')])
    return page.url()
  }
  log.createdType1 = await createType('Grow Out Pond QA', 'QA type A')
  log.createdType2 = await createType('Nursery Pond QA', 'QA type B')
  log.typeVisibleInList = await page.locator('text=Nursery Pond QA').count()

  // ---- duplicate type name ------------------------------------------------
  await go('/fish-farm/ponds/types/create')
  await page.fill('input[name=name]', 'Grow Out Pond QA')
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')])
  log.dupTypeBlocked = (await page.locator('text=already exists').count()) > 0

  // ---- create a pond ------------------------------------------------------
  await go('/fish-farm/ponds/create')
  log.createFormLoaded = await page.locator('select[name=pond_type_id]').count()
  const typeValue = await page
    .locator('select[name=pond_type_id] option:has-text("Grow Out Pond QA")')
    .getAttribute('value')
  await page.fill('input[name=pond_number]', 'QA-P01')
  await page.fill('input[name=name]', 'QA Test Pond')
  await page.selectOption('select[name=pond_type_id]', typeValue)
  await page.fill('input[name=size]', '12.5')
  await page.selectOption('select[name=size_unit]', 'decimal')
  await page.fill('input[name=depth]', '1.8')
  await page.selectOption('select[name=depth_unit]', 'metre')
  await page.fill('input[name=location]', 'North block')
  await page.fill('input[name=water_source]', 'Canal')
  await page.selectOption('select[name=status]', 'active')
  await page.fill('textarea[name=description]', 'QA temporary pond')
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')])
  log.pondShowUrl = page.url()
  log.pondShowsSize = await page.locator('text=12.5 decimal').count()
  log.pondShowsDepth = await page.locator('text=1.8 m').count()
  log.pondShowsFutureModules = await page.locator('text=Future ERP modules').count()
  log.pondShowsHonestPlaceholder = (await page.locator('text=No fish stocking records yet').count()) > 0

  // ---- duplicate pond number ---------------------------------------------
  await go('/fish-farm/ponds/create')
  await page.fill('input[name=pond_number]', 'QA-P01')
  await page.fill('input[name=name]', 'Dup Pond')
  await page.selectOption('select[name=pond_type_id]', typeValue)
  await page.fill('input[name=size]', '5')
  await page.selectOption('select[name=size_unit]', 'acre')
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')])
  log.dupPondBlocked = (await page.locator('text=already in use').count()) > 0

  // ---- negative size -----------------------------------------------------
  await go('/fish-farm/ponds/create')
  await page.fill('input[name=pond_number]', 'QA-P02')
  await page.fill('input[name=name]', 'Bad Size Pond')
  await page.selectOption('select[name=pond_type_id]', typeValue)
  await page.fill('input[name=size]', '-4')
  await page.selectOption('select[name=size_unit]', 'acre')
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')])
  log.negativeSizeBlocked = (await page.locator('text=greater than zero').count()) > 0

  // ---- delete a pond type THAT IS IN USE -> must be refused --------------
  await go('/fish-farm/ponds/types')
  const inUseForm = page.locator('form[data-confirm*="Grow Out Pond QA"]')
  log.inUseDeleteFormCount = await inUseForm.count()
  // The "in use" row shows an "In use" link instead of a Delete button.
  log.inUseBadgeShown = await page.locator('text=In use').count()

  // ---- filters -----------------------------------------------------------
  log.filterMaintenanceStatus = await go('/fish-farm/ponds?status=maintenance')
  log.filterMaintenanceNoMatch = (await page.locator('text=No ponds found').count()) > 0
  log.filterActiveSearch = await go('/fish-farm/ponds?status=active&search=QA')
  log.filterActiveFound = (await page.locator('text=QA Test Pond').count()) > 0
  log.filterSearchPreserved = await page.locator('form[method=GET] input[name=search]').inputValue()

  // ---- pond status page --------------------------------------------------
  log.statusPage = await go('/fish-farm/ponds/status')
  log.statusHeading = await page.locator('h2:has-text("Pond Status")').count()
  log.statusUsableLabels = await page.locator('text=Usable for stocking').count()
  log.statusFiltered = await go('/fish-farm/ponds/status?status=active')
  log.statusFilteredRow = await page.locator('text=QA Test Pond').count()

  // ---- update the pond ---------------------------------------------------
  await go('/fish-farm/ponds?search=QA-P01')
  const editHref = await page.locator('a:has-text("Edit")').first().getAttribute('href')
  log.editHref = editHref
  await page.goto(base + editHref, { waitUntil: 'domcontentloaded' })
  await page.fill('input[name=name]', 'QA Test Pond Renamed')
  await page.selectOption('select[name=status]', 'maintenance')
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')])
  log.updatedNameShown = await page.locator('text=QA Test Pond Renamed').count()
  log.updatedStatusShown = await page.locator('text=Maintenance').count()

  return log
}
