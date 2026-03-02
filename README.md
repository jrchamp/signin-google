# Google Login

> WordPress authentication plugin for Google Login

- [Google Login](#google-login)
  - [Overview](#overview)
  - [Installation](#installation)
  - [Usage Instructions](#usage-instructions)
    - [Plugin Constants](#plugin-constants)

## Overview

Google Login provides seamless experience for users to login in to WordPress
sites using their Google account. No need to manually create accounts or remember quirky
passwords.

## Installation

1. Clone this repository.
2. Upload the directory to the `wp-content/plugins` directory.
3. Activate the plugin from the WordPress dashboard.

## Usage Instructions

1. You will need to register a new application at https://console.cloud.google.com/apis/dashboard
2. `Authorization callback URL` should be like `https://example.com/wp-login.php` and the `Authorized JavaScript origins` should be `https://example.com` where
`https://example.com` is replaced by your site URL.
3. Once you create the app, you will receive the `Client ID` and `Client Secret`, add these credentials
in `Settings > Google Login` settings page in their respective fields.
4. `Create new user` enables new user registration irrespective of `Membership` settings in
   `Settings > General`; as sometimes enabling user registration can lead to lots of spam users.
   Plugin will take this setting as first priority and membership setting as second priority, so if
   any one of them is enabled, new users will be registered by this plugin after successful authorization.
5. `Allowed Domains` allows users from specific domains (domain in email) to get registered on site.
This will prevent unwanted registration on website.
**For Example:** If you want users only from your organization (`example.com`) to get registered on the website,
you enter `example.com` in allowed domains. Users with an email like `abc@example.com` will be able to register.
Conversely, users with emails like `something@gmail.com` would not be able to register.

### Plugin Constants

Above mentioned settings can also be configured via PHP constants by defining them in wp-config.php
file.

Refer following list of constants.

|                           | Type    | Description                                                                                                    |
|---------------------------|---------|----------------------------------------------------------------------------------------------------------------|
| GOOGLE_LOGIN_CLIENT_ID    | String  | Google client ID of your application.                                                                          |
| GOOGLE_LOGIN_SECRET       | String  | Secret key of your application                                                                                 |
| GOOGLE_LOGIN_REGISTRATION | Boolean | (Optional) Enable new user registration? If not set, inherits from `Settings > General Settings > Membership`. |
| GOOGLE_LOGIN_DOMAINS      | String  | (Optional) Comma-separated list of allowed domain names. If empty, all domains are allowed.                    |

These constants can also be configured via [wp-cli](https://developer.wordpress.org/cli/commands/config/).

**Note:** If the constant is defined, then the corresponding settings field cannot be edited on the settings page.
