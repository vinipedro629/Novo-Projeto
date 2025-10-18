import request from 'supertest';
import app from '../src/app';

describe('Testes da aplicação', () => {
  it('deve responder com status 200 na rota raiz', async () => {
    const response = await request(app).get('/');
    expect(response.status).toBe(200);
  });

  // Adicione mais testes conforme necessário
});