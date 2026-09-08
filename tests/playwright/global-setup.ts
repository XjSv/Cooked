import path from 'path';
import dotenv from 'dotenv';
import { ADMIN_USER, CONTRIB_USER } from './utils/users';
import { ensureUser } from './utils/wp-cli';

dotenv.config({ path: path.resolve(__dirname, '.env'), quiet: true });

export default function globalSetup(): void {
  ensureUser(ADMIN_USER);
  ensureUser(CONTRIB_USER);
}
