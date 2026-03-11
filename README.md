# Sign in with Google

WordPress authentication plugin so users can Sign in with Google

## Overview

Sign in with Google provides a seamless experience for users to login in to WordPress
sites using their Google account. No need to manually create accounts or remember passwords.

## Installation

1. Clone this repository.
2. Upload the directory to the `wp-content/plugins` directory.
3. Activate the plugin from the WordPress dashboard.

## Usage Instructions

1. You will need to [register a new Google OAuth application](https://console.cloud.google.com/apis/dashboard). If `https://example.com/wordpress/` is your site URL, then:
   - `Authorization callback URL` should be `https://example.com/wordpress/wp-login.php`
   - `Authorized JavaScript origins` should be `https://example.com`
2. Add the `Client ID` and `Client Secret` credentials on the settings page.
3. `Allow New Users` allows new user registration for verified Google accounts.
4. `Allowed Domains` restricts which users can register on your site. This helps prevent unwanted registrations.

### Plugin Constants

Alternatively, the settings can be configured using PHP constants by defining them in the wp-config.php file.

|                            | Type    | Description                                                                            |
|----------------------------|---------|----------------------------------------------------------------------------------------|
| SIGNIN_GOOGLE_CLIENT_ID    | String  | Google client ID of your application.                                                  |
| SIGNIN_GOOGLE_SECRET       | String  | Secret key of your application                                                         |
| SIGNIN_GOOGLE_REGISTRATION | Boolean | (Optional) Enable new user registration?                                               |
| SIGNIN_GOOGLE_DOMAINS      | String  | (Optional) Comma-separated list of allowed domains. If empty, all domains are allowed. |

**Note:** If the constant is defined, then the corresponding settings field cannot be edited on the settings page.
