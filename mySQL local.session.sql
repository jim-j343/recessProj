use smartforum
UPDATE users SET status = 'active' WHERE username = 'kayongo_moses';

UPDATE users SET status = 'active'
WHERE status = 'blacklisted' AND username != 'inactive_user';

UPDATE users SET status = 'active'
WHERE status = 'blacklisted' AND username != 'inactive_user';

UPDATE users SET status = 'active' WHERE user_id = 7;