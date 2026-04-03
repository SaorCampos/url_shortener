import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    stages: [
        { duration: '1m', target: 100 },
        { duration: '2m', target: 200 },
        { duration: '2m', target: 500 },
        { duration: '1m', target: 0 },
    ],
    thresholds: {
        http_req_duration: ['p(95)<100'],
        http_req_failed: ['rate<0.01'],
    },
};

const TEST_CODE = 'mZmevP';
const BASE_URL = 'http://urlshortener_nginx';

export default function () {
    let resRedirect = http.get(`${BASE_URL}/${TEST_CODE}`, { redirects: 0 });
    check(resRedirect, {
        'status is 302': (r) => r.status === 302,
    });
    sleep(0.1);
    let resAnalytics = http.get(`${BASE_URL}/api/analytics/${TEST_CODE}`);
    check(resAnalytics, {
        'analytics is 200': (r) => r.status === 200,
    });
    sleep(1);
}
