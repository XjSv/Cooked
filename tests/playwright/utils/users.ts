import path from 'path';
import dotenv from 'dotenv';

dotenv.config({ path: path.resolve(__dirname, '../.env'), quiet: true });

export type TestUser = {
  user: string;
  password: string;
  email: string;
  role: string;
};

export const ADMIN_USER: TestUser = {
  user: process.env.WP_ADMIN_USER || 'e2e_admin',
  password: process.env.WP_ADMIN_PASSWORD || 'password',
  email: 'e2e_admin@test.local',
  role: 'administrator',
};

export const CONTRIB_USER: TestUser = {
  user: 'e2e_contributor',
  password: 'password',
  email: 'e2e_contributor@test.local',
  role: 'contributor',
};
