-- Fix Super Admin Password Hash
-- This updates the super admin password to a valid hash for 'superadmin123'

UPDATE `users` 
SET `password_hash` = '$argon2id$v=19$m=65536,t=4,p=1$aVdZWWp2dVVKLjdvaE01WA$QghqRYEnJ6KAThL0rtMrj5F5hvEsrUjF2S7fAkHxOIA'
WHERE `email` = 'superadmin@bloomvine.com' AND `role` = 'super_admin';

