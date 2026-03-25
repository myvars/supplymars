-- playground-redact-staff.sql
-- Runs immediately after database restore to protect staff credentials.
-- The demo password ('demo') is public by design — the hash is a fixed constant.
-- The admin account (admin@supplymars.com) is preserved with ROLE_SUPER_ADMIN.

-- Scramble all staff emails and passwords (makes them unusable)
UPDATE user
SET email    = CONCAT(HEX(RANDOM_BYTES(8)), '@redacted.local'),
    password = HEX(RANDOM_BYTES(32))
WHERE is_staff = 1
  AND email NOT IN ('demo@supplymars.com', 'admin@supplymars.com');

-- Promote admin account to ROLE_SUPER_ADMIN (preserves existing password)
UPDATE user
SET roles = '["ROLE_SUPER_ADMIN"]'
WHERE email = 'admin@supplymars.com';

-- Create or reset the demo user (bcrypt hash of 'demo')
INSERT INTO user (public_id, email, password, full_name, roles, is_verified, is_staff, is_simulated, created_at, updated_at)
VALUES (
    LOWER(CONCAT(HEX(RANDOM_BYTES(4)), HEX(RANDOM_BYTES(2)), HEX(RANDOM_BYTES(2)), HEX(RANDOM_BYTES(2)), HEX(RANDOM_BYTES(6)))),
    'demo@supplymars.com',
    '$2y$12$97APfjE30ZOC2HUKuSjlZ.lhWRVegKaV.Whbm38u1UA3YSsijY3Im',
    'Demo User',
    '["ROLE_ADMIN"]',
    1, 1, 0, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    password    = '$2y$12$97APfjE30ZOC2HUKuSjlZ.lhWRVegKaV.Whbm38u1UA3YSsijY3Im',
    full_name   = 'Demo User',
    roles       = '["ROLE_ADMIN"]',
    is_verified = 1,
    is_staff    = 1,
    updated_at  = NOW();
