BEGIN;

CREATE TABLE IF NOT EXISTS user_action_permissions (
    user_id INTEGER PRIMARY KEY REFERENCES users(user_id) ON DELETE CASCADE,
    can_add BOOLEAN NOT NULL DEFAULT TRUE,
    can_edit BOOLEAN NOT NULL DEFAULT TRUE,
    can_delete BOOLEAN NOT NULL DEFAULT FALSE
);

INSERT INTO user_action_permissions (user_id, can_add, can_edit, can_delete)
SELECT user_id, TRUE, TRUE, FALSE
FROM users
ON CONFLICT (user_id) DO NOTHING;

COMMIT;
