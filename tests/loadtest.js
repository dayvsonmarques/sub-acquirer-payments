import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  scenarios: {
    pixThroughput: {
      executor: 'constant-arrival-rate',
      rate: 6, // >= 3 req/s requirement
      timeUnit: '1s',
      duration: '2m',
      preAllocatedVUs: 12,
      maxVUs: 40,
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.05'], // <= 5% failures
    http_req_duration: ['p(95)<2000'], // 95% das reqs abaixo de 2s
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const LOGIN_EMAIL = __ENV.K6_EMAIL || 'clientea@example.com';
const LOGIN_PASSWORD = __ENV.K6_PASSWORD || 'password';

function buildPixPayload(iteration) {
  const amount = (Math.random() * 400 + 50).toFixed(2); // 50 - 450
  const suffix = (iteration % 99999999).toString().padStart(8, '0');

  return JSON.stringify({
    amount,
    pix_key: `1234567${suffix}`,
    pix_key_type: 'cpf',
    description: `Load test #${iteration}`,
  });
}

export function setup() {
  const loginPayload = JSON.stringify({
    email: LOGIN_EMAIL,
    password: LOGIN_PASSWORD,
  });

  const loginRes = http.post(`${BASE_URL}/api/login`, loginPayload, {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  });

  const loginOk = check(loginRes, {
    'login status 200': (res) => res.status === 200,
  });

  if (!loginOk) {
    throw new Error(`Login failed: ${loginRes.status} - ${loginRes.body}`);
  }

  const token = loginRes.json('data.token');

  if (!token) {
    throw new Error(`Token missing in login response: ${loginRes.body}`);
  }

  return { token };
}

export default function (data) {
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    Authorization: `Bearer ${data.token}`,
  };

  const payload = buildPixPayload(__ITER);
  const response = http.post(`${BASE_URL}/api/pix`, payload, { headers });

  const success = check(response, {
    'status 201': (res) => res.status === 201,
  });

  if (!success) {
    console.error(`Unexpected status ${response.status}: ${response.body}`);
  }

  sleep(0.2); // pequenas pausas ajudam a evitar burst local
}

