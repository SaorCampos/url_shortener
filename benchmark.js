import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    stages: [
        { duration: '30s', target: 50 },
        { duration: '1m', target: 100 },
        { duration: '30s', target: 0 },
    ],
    thresholds: {
        http_req_duration: ['p(95)<100'],
    },
};


const TEST_CODE = 'mZmevP'; // Replace with your actual test code
const BASE_URL = 'http://urlshortener_nginx';

export default function () {
    let resRedirect = http.get(`${BASE_URL}/${TEST_CODE}`, {
        redirects: 0,
    });
    check(resRedirect, {
        'redirect status is 302': (r) => r.status === 302,
        'redirect is fast': (r) => r.timings.duration < 50,
    });
    sleep(0.1);
    let resAnalytics = http.get(`${BASE_URL}/api/analytics/${TEST_CODE}`);
    check(resAnalytics, {
        'analytics status is 200': (r) => r.status === 200,
        'analytics is cached': (r) => r.timings.duration < 20,
    });
    sleep(1);
}
