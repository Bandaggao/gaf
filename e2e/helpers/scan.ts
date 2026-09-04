import { APIRequestContext, expect } from '@playwright/test';

export const sessionTokens = {
  active: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee01',
  expired: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee02',
  closed: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee03',
  invalid: '00000000-0000-0000-0000-000000000000',
} as const;

export async function scanAsStudent(
  request: APIRequestContext,
  apiURL: string,
  bearerToken: string,
  sessionQrToken: string,
) {
  return request.post(`${apiURL}/api/student/scan`, {
    headers: {
      Authorization: `Bearer ${bearerToken}`,
      Accept: 'application/json',
    },
    data: { token: sessionQrToken },
  });
}

export async function expectScanSuccess(
  request: APIRequestContext,
  apiURL: string,
  bearerToken: string,
  sessionQrToken: string,
) {
  const response = await scanAsStudent(request, apiURL, bearerToken, sessionQrToken);
  expect(response.ok()).toBeTruthy();
  const body = await response.json();
  expect(body.message).toMatch(/recorded successfully/i);
}

export async function expectScanFailure(
  request: APIRequestContext,
  apiURL: string,
  bearerToken: string,
  sessionQrToken: string,
  expectedStatus = 422,
) {
  const response = await scanAsStudent(request, apiURL, bearerToken, sessionQrToken);
  expect(response.status()).toBe(expectedStatus);
}
