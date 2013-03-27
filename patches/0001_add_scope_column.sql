--- add scope column to oauth_states table
ALTER TABLE oauth_states ADD COLUMN scope TEXT DEFAULT NULL;
