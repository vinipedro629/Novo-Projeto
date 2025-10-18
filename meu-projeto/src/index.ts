import express from 'express';
import { setRoutes } from './routes';

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware configuration can go here

setRoutes(app);

app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
});