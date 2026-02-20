<?php

// BEGIN iThemes Security - Do not modify or remove this line
// iThemes Security Config Details: 2
define( 'DISALLOW_FILE_EDIT', true ); // Disable File Editor - Security > Settings > WordPress Tweaks > File Editor
// END iThemes Security - Do not modify or remove this line

/* BEGIN KINSTA DEVELOPMENT ENVIRONMENT - DO NOT MODIFY THIS CODE BLOCK */ ?>
<?php if ( !defined('KINSTA_DEV_ENV') ) { define('KINSTA_DEV_ENV', true); /* Kinsta development - don't remove this line */ } ?>
<?php if ( !defined('JETPACK_STAGING_MODE') ) { define('JETPACK_STAGING_MODE', true); /* Kinsta development - don't remove this line */ } ?>
<?php /* END KINSTA DEVELOPMENT ENVIRONMENT - DO NOT MODIFY THIS CODE BLOCK */ ?>
<?php

define( 'ITSEC_ENCRYPTION_KEY', 'Ql8qZFFULkVxYzlqaHkzUHF0OjIlcChqTzJjUk1QWmhjIStvdyE6eX07MEs/WEAxe2N1YHNIaXhULXYuOChUdw==' );
define( 'WP_CACHE', true ); // Added by WP Rocket


##define('WP_DEBUG', true);

// Database Configuration
define( 'DB_NAME', 'gurutor');
define( 'DB_USER', 'gurutor');
define( 'DB_PASSWORD', 'UybRAQLoigjVyCe' );
define( 'DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', 'utf8_unicode_ci');
$table_prefix = 'wp_';
// Security Salts, Keys, Etc
define('AUTH_KEY',         'YnppQXl|Ypnssm8aw`bY4/b|.|$!15[UVlp-,sF3lWr_VCL4kbUcv_n;`1~a4)uE');
define('SECURE_AUTH_KEY',  '3Xe?[z8l4F<Dx )X4}$|4[>=VMc:nUGmA8fwhQnAbf#4a)LTM&H=!VP3Z[^|+1aB');
define('LOGGED_IN_KEY',    '=-ZM!kd$dY_q<Z)bMc+^bES]p0YIX,(.m#5!9];L]g-OLJ`k}%qnR}CfgYpDB|[0');
define('NONCE_KEY',        'VwdEQNFly/LG^WX>!%EJ~g:[*Ya @VUyk-l0 |5K#^of?|dgLE^>SeQHId|rQ<[%');
define('AUTH_SALT',        'G2ZM .[SE6M]{6-d@c7G[%-L#M:dh0QCb/6CrLO.-kLH<A<3-GnO3+j)^ gcG-Nl');
define('SECURE_AUTH_SALT', '3&(;puL+X-@iv>?]/3D)Jl*%Gf4r$lqPmChCjp+y1-OqC`,oTH<E $BVISn<podP');
define('LOGGED_IN_SALT',   '#fA-V06g8Xc4f*o? p3i-[%v59&CoAT?C,z6!+@;Os3MrLU@]^Bk/^>|l458/|g|');
define('NONCE_SALT',       '!hza*W#C*b9r|Wj0{s{2?gPVVvg@ndDzFjYnKGc!&K?gNmC7^F[z@1u/-z=1Y=x;');
// Localized Language Stuff
define( 'WP_AUTO_UPDATE_CORE', false );
define( 'FS_METHOD', 'direct' );
define( 'FS_CHMOD_DIR', 0755 );
define( 'FS_CHMOD_FILE', 0644 );
define( 'DISALLOW_FILE_MODS', FALSE );
// define( 'DISALLOW_FILE_EDIT', FALSE );
define( 'DISABLE_WP_CRON', false );
define( 'FORCE_SSL_LOGIN', false );
define( 'WP_POST_REVISIONS', FALSE );
define( 'WP_TURN_OFF_ADMIN_BAR', false );
define('WPLANG','');
// Memory limit.
define( 'WP_MEMORY_LIMIT', '256M' );
define("GB_LAUNCH_REVEAL_CONTENT_URL", true);

// ============================================================================
// CONSTANTS & CONFIGURATION
// ============================================================================
// GMAT API Authentication (keep this secret!)
define('GMAT_API_AUTH', 'MTMtNjk0MmJiNGMzMDNlMTM3OmI1YjFmYjk4YjM5ZGZlY2VhYjg0YWI0YTA=');



// ----------------------------------------------------------------------------
// GMAT Chatbox — AI Backend URL
// ----------------------------------------------------------------------------
// The full URL of the FastAPI endpoint running on AWS EC2.
// Provided by the AI team.
//
// Format:   https://your-ec2-instance.com/ask
//           https://api.yourdomain.com/v1/chat
//           http://12.34.56.78:8000/ask   ← HTTP only acceptable on private VPC;
//                                           always use HTTPS in production.
//
// Example (staging — replace with real value from AI team):
//   define( 'GMAT_CHATBOX_API_URL', 'https://api.gurutor-ai.com/ask' );
// ----------------------------------------------------------------------------

define( 'GMAT_CHATBOX_API_URL', 'https://bandoliered-gainly-rita.ngrok-free.dev/chat' );


// ----------------------------------------------------------------------------
// GMAT Chatbox — Shared API Secret Key
// ----------------------------------------------------------------------------
// A long, random secret string agreed upon between your WordPress server
// and the AI team's FastAPI backend.
//
// HOW TO GENERATE A STRONG KEY (run ONE of these in your terminal):
//
//   PHP:    php -r "echo bin2hex(random_bytes(32));"
//   Linux:  openssl rand -hex 32
//   Python: python3 -c "import secrets; print(secrets.token_hex(32))"
//
// This key is sent inside every request body as { "key": "..." }
// The FastAPI backend MUST reject any request where the key does not match.
// The backend also returns the same key in its response; WordPress verifies it.
//
// Example (staging — replace with the REAL shared key):
//   define( 'GMAT_CHATBOX_API_KEY', 'a3f9b2c1d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1' );
// ----------------------------------------------------------------------------

define( 'GMAT_CHATBOX_API_KEY', '061a8b75228f492fe3432c021f813f361653108f5c93d5ca339b797dde453471' );

// Free Trial Configuration
define('FREE_TRIAL_PRODUCT_ID', 7006);
define('FREE_TRIAL_COURSE_ID', 7472);
define('FREE_TRIAL_DAYS', 5);

// define( 'WP_DEBUG', true );
// define( 'WP_DEBUG_LOG', true );
// define( 'WP_DEBUG_DISPLAY', false );
// That's It. Pencils down
if ( !defined('ABSPATH') )
	define('ABSPATH', __DIR__ . '/');
require_once(ABSPATH . 'wp-settings.php');