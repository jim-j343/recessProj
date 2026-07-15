use smartforum
UPDATE users SET status = 'active' WHERE username = 'kayongo_moses';

UPDATE users SET status = 'active'
WHERE status = 'blacklisted' AND username != 'inactive_user';

UPDATE users SET status = 'active'
WHERE status = 'blacklisted' AND username != 'inactive_user';

UPDATE users SET status = 'active' WHERE user_id = 7;

DROP TABLE IF EXISTS group_invitations;

ALTER TABLE notifications MODIFY COLUMN type ENUM(
    'reply', 'warning', 'quiz_announced', 'blacklisted', 'mention'
) NOT NULL;

DELETE FROM migrations WHERE migration IN (
    '2026_07_14_000002_create_group_invitations_table',
    '2026_07_14_000003_add_group_invite_to_notifications_type'
);