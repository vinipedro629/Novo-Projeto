export interface CustomRequest {
    userId?: string;
    body: Record<string, any>;
}

export interface CustomResponse {
    status: number;
    message: string;
    data?: Record<string, any>;
}

export interface ApiResponse<T> {
    success: boolean;
    data?: T;
    error?: string;
}