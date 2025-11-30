import axios from 'axios';
import { createMockApiInterceptor } from './mockApi.js';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

const useMockApi = import.meta.env.VITE_USE_MOCK_API === 'true';

if (useMockApi) {
  const mockAdapter = (config) => {
    return new Promise(async (resolve, reject) => {
      const mockInterceptor = createMockApiInterceptor();
      try {
        const result = await mockInterceptor(config);
        resolve({
          data: result.data,
          status: 200,
          statusText: 'OK',
          headers: {},
          config,
        });
      } catch (error) {
        if (error.response) {
          reject({
            response: {
              data: error.response.data,
              status: error.response.status || 500,
              statusText: error.response.statusText || 'Error',
              headers: {},
              config,
            },
          });
        } else {
          reject({
            response: {
              data: error.data || { error: 'Mock API error' },
              status: error.status || 500,
              statusText: 'Error',
              headers: {},
              config,
            },
          });
        }
      }
    });
  };

  api.defaults.adapter = mockAdapter;
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('API Error:', error);
    return Promise.reject(error);
  }
);

export default api;


