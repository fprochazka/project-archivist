UPDATE user_identities SET token = password WHERE password IS NOT NULL;

ALTER TABLE user_identities DROP password;
ALTER TABLE user_identities ALTER invalid SET NOT NULL;
