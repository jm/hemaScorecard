import { test, expect } from '@playwright/test';
import { TEST_EVENT_ID, TEST_TOURNAMENT_ID } from './helpers/test-data';

/**
 * lastViewedContext cookie — set on tournament change, and used to restore
 * the event/tournament context after the PHP session expires.
 *
 * Session expiry is simulated by deleting only the PHPSESSID cookie while
 * keeping lastViewedContext.
 */

const CONTEXT_COOKIE = 'lastViewedContext';
const COOKIE_LIFETIME_SECONDS = 60 * 60 * 24 * 4; // CONTEXT_COOKIE_LIFETIME in includes/config.php

test('selecting a tournament sets the context cookie', async ({ page, context }) => {
  await page.goto(`/infoSummary.php?e=${TEST_EVENT_ID}&t=${TEST_TOURNAMENT_ID}`);

  const cookie = (await context.cookies()).find((c) => c.name === CONTEXT_COOKIE);
  expect(cookie).toBeDefined();
  expect(cookie!.value).toBe(`${TEST_EVENT_ID}-${TEST_TOURNAMENT_ID}`);
  expect(cookie!.httpOnly).toBe(true);

  // Expires ~4 days out (allow 10 minutes of clock slack).
  const expectedExpiry = Date.now() / 1000 + COOKIE_LIFETIME_SECONDS;
  expect(cookie!.expires).toBeGreaterThan(expectedExpiry - 600);
  expect(cookie!.expires).toBeLessThan(expectedExpiry + 600);
});

test('expired session restores event and tournament from the cookie', async ({ page, context }) => {
  // Establish context so the cookie gets set.
  await page.goto(`/infoSummary.php?e=${TEST_EVENT_ID}&t=${TEST_TOURNAMENT_ID}`);

  // Simulate session expiry: drop the session cookie, keep lastViewedContext.
  await context.clearCookies({ name: 'PHPSESSID' });

  // A bare page visit redirects to the session's context, which restoreContextFromCookie
  // rebuilt from the cookie.
  await page.goto('/infoSummary.php');
  await expect(page).toHaveURL(
    new RegExp(`infoSummary\\.php\\?e=${TEST_EVENT_ID}&t=${TEST_TOURNAMENT_ID}`)
  );
  await expect(page.getByText('No Event Selected')).toHaveCount(0);
});

test('cookie pointing at a nonexistent event is ignored', async ({ page, context, baseURL }) => {
  await context.addCookies([
    {
      name: CONTEXT_COOKIE,
      value: '9999-9999',
      url: baseURL!,
    },
  ]);

  await page.goto('/infoSummary.php');
  await expect(page).toHaveURL(/infoSummary\.php\?e=0&t=0/);
  await expect(page.getByText('No Event Selected')).toBeVisible();
});

test('cookie with a tournament from a different event restores only the event', async ({
  page,
  context,
  baseURL,
}) => {
  await context.addCookies([
    {
      name: CONTEXT_COOKIE,
      value: `${TEST_EVENT_ID}-9999`,
      url: baseURL!,
    },
  ]);

  await page.goto('/infoSummary.php');

  // The event is restored; the bogus tournament is discarded, after which the
  // app auto-selects the event's only seeded tournament.
  await expect(page).toHaveURL(
    new RegExp(`infoSummary\\.php\\?e=${TEST_EVENT_ID}&t=${TEST_TOURNAMENT_ID}`)
  );
  expect(page.url()).not.toContain('9999');
  await expect(page.getByText('No Event Selected')).toHaveCount(0);
});
