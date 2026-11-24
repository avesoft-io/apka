export type User = {
    id: number;
    name: string;
    email: string;
    role: 'staff' | 'supervisor';
    organization?: {
        id: number;
        name: string;
    };
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Auth = {
    user: User | null;
};
