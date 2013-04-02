CREATE TABLE IF NOT EXISTS oauth_states
  (
     callback_id VARCHAR(64) NOT NULL,
     user_id     VARCHAR(64) NOT NULL,
     state       VARCHAR(64) NOT NULL,
     return_uri  TEXT NOT NULL,
     scope       TEXT DEFAULT NULL,
     UNIQUE (callback_id, user_id, scope),
     PRIMARY KEY (state)
  );
  
CREATE TABLE IF NOT EXISTS oauth_access_tokens
  (
     callback_id  VARCHAR(64) NOT NULL,
     user_id      VARCHAR(64) NOT NULL,
     access_token VARCHAR(64) NOT NULL,
     issue_time   INT(11) NOT NULL,
     expires_in   INT(11) DEFAULT NULL,
     scope        TEXT DEFAULT NULL,
     UNIQUE (callback_id, user_id, scope)
  );

CREATE TABLE IF NOT EXISTS oauth_refresh_tokens
  (
     callback_id   VARCHAR(64) NOT NULL,
     user_id       VARCHAR(64) NOT NULL,
     refresh_token VARCHAR(64) NOT NULL,
     scope         TEXT DEFAULT NULL,
     UNIQUE (callback_id, user_id, scope)
  );

CREATE TABLE IF NOT EXISTS oauth_applications
  (
     app_id      VARCHAR(64) NOT NULL,
     client_data TEXT NOT NULL,
     PRIMARY KEY (app_id)
  );

CREATE TABLE IF NOT EXISTS schema_version
  (
     version	INT(11) NOT NULL,
     log	TEXT NOT NULL,
     PRIMARY KEY (version)
  );
