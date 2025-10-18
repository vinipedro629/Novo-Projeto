import { Router } from 'express';
import { YourController } from '../controllers/index';

const router = Router();

export const setRoutes = () => {
    router.get('/your-endpoint', YourController.getAll);
    router.get('/your-endpoint/:id', YourController.getById);
    // Adicione mais rotas conforme necessário

    return router;
};