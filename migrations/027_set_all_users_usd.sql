-- Force all users to USD (currency selector removed from UI)
UPDATE users SET preferred_currency = 'usd';
