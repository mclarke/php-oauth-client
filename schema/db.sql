CREATE TABLE IF NOT EXISTS oauth_states
  (
     callback_id VARCHAR(255) NOT NULL,
     user_id     VARCHAR(255) NOT NULL,
     state       VARCHAR(255) NOT NULL,
     return_uri  TEXT NOT NULL,
     scope       VARCHAR(255) DEFAULT NULL,
     UNIQUE (callback_id, user_id, scope),
     PRIMARY KEY (state)
  );
  
CREATE TABLE IF NOT EXISTS oauth_access_tokens
  (
     callback_id  VARCHAR(255) NOT NULL,
     user_id      VARCHAR(255) NOT NULL,
     access_token VARCHAR(255) NOT NULL,
     issue_time   INTEGER NOT NULL,
     expires_in   INTEGER DEFAULT NULL,
     scope        VARCHAR(255) DEFAULT NULL,
     UNIQUE (callback_id, user_id, scope)
  );

CREATE TABLE IF NOT EXISTS oauth_refresh_tokens
  (
     callback_id   VARCHAR(255) NOT NULL,
     user_id       VARCHAR(255) NOT NULL,
     refresh_token VARCHAR(255) NOT NULL,
     scope         VARCHAR(255) DEFAULT NULL,
     UNIQUE (callback_id, user_id, scope)
  );

CREATE TABLE IF NOT EXISTS oauth_applications
  (
     app_id      VARCHAR(255) NOT NULL,
     client_data TEXT NOT NULL,
     PRIMARY KEY (app_id)
  );

CREATE TABLE IF NOT EXISTS db_changelog
  (
     patch_number INTEGER NOT NULL,
     description TEXT NOT NULL,
     PRIMARY KEY (patch_number)
  );
