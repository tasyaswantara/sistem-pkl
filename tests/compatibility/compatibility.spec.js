import { test, expect } from '@playwright/test';

async function login(page, email, password = 'password') {
  await page.goto('/login');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

test('Halaman login dapat tampil', async ({ page }) => {
  await page.goto('/login');
  await expect(page.locator('input[name="email"]')).toBeVisible();
  await expect(page.locator('input[name="password"]')).toBeVisible();
});

const roleTests = [
  {
    role: 'Humas',
    email: 'admin@example.com',
    paths: [
      '/data-pengguna',
      '/penempatan',
      '/presensi',
      '/elogbook',
      '/perizinan',
      '/penilaian',
      '/peringatan-dini',
      '/notifications',
      '/profile',
    ],
  },
  {
    role: 'Guru Pembimbing',
    email: process.env.PW_GURU_EMAIL,
    paths: [
      '/guru/siswa',
      '/guru/presensi',
      '/guru/elogbook',
      '/guru/perizinan',
      '/guru/penilaian',
      '/guru/peringatan-dini',
    ],
  },
  {
    role: 'Siswa',
    email: process.env.PW_SISWA_EMAIL,
    paths: [
      '/siswa/dashboard',
      '/siswa/presensi',
    ],
  },
  {
    role: 'Perwakilan Industri',
    email: process.env.PW_INDUSTRI_EMAIL,
    paths: [
      '/industri/pengajuan',
      '/industri/siswa',
      '/industri/presensi',
      '/industri/elogbook',
      '/industri/perizinan',
      '/industri/penilaian',
    ],
  },
];

for (const roleTest of roleTests) {
  test.describe(`Compatibility halaman ${roleTest.role}`, () => {
    test.skip(!roleTest.email, `Email ${roleTest.role} belum diatur.`);

    test.beforeEach(async ({ page }) => {
      await login(page, roleTest.email);
    });

    for (const path of roleTest.paths) {
      test(`Halaman ${path} dapat tampil`, async ({ page }) => {
        const response = await page.goto(path, { waitUntil: 'networkidle' });

        expect(response?.status()).toBeLessThan(400);
        await expect(page.locator('body')).toBeVisible();
      });
    }
  });
}