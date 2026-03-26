<?php
/**
 * config/auth_config.php - Auth setting & JWT configuration
 */

// JWT Secret Key (Ensure 32-64 characters)
// Better to get this from environment variable (Vercel)
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-32-character-very-secret-key-CHANGE-THIS');
define('JWT_ALGO', 'HS256');

// Cookie settings
define('COOKIE_NAME', 'auth_token');
define('EXPIRE_TIME', 43200); // 12 Hours (Access Token)
define('REFRESH_EXPIRE_TIME', 604800); // 7 Days (Optional)
