CREATE TABLE IF NOT EXISTS oauth_states (
    state VARCHAR(255) NOT NULL,
    client_config_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    scope VARCHAR(255) DEFAULT NULL,
    UNIQUE (client_config_id , user_id , scope),
    PRIMARY KEY (state)
);

CREATE TABLE IF NOT EXISTS oauth_access_tokens (
    client_config_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    scope VARCHAR(255) DEFAULT NULL,
    access_token VARCHAR(255) NOT NULL,
    token_type VARCHAR(255) NOT NULL,
    expires_in INTEGER DEFAULT NULL,
    refresh_token VARCHAR(255) DEFAULT NULL,
    issue_time INTEGER NOT NULL,
    is_usable INTEGER DEFAULT 1,
    UNIQUE (client_config_id , user_id , scope)
);
