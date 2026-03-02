=== Google Login ===
Contributors: jrchamp, rtCamp, sh4lin, nikhiljoshua, mchirag2002, mi5t4n
Tags: Google login, sign in, sso, oauth, authentication, sign-in, single sign-on, log in
Requires at least: 5.5
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Minimal plugin that allows WordPress users to log in using Google.

== Description ==

Minimal plugin to let your users login to WordPress using their Google accounts.

### Initial Setup

1. Create a project from [Google Developers Console](https://console.developers.google.com/apis/dashboard) if none exists.


2. Go to **Credentials** tab, then create credential for OAuth client.
    * Application type will be **Web Application**
    * Add `YOUR_DOMAIN/wp-login.php` in **Authorized redirect URIs**


3. This will give you **Client ID** and **Secret key**.


4. Input these values either in `WP Admin > Settings > Google Login`, or in `wp-config.php` using the following code snippet:

```
define( 'GOOGLE_LOGIN_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID' );
define( 'GOOGLE_LOGIN_SECRET', 'YOUR_SECRET_KEY' );
```

### How to enable automatic user registration

You can enable user registration either by
- Enabling *Settings > Google Login > Enable Google Login Registration*


OR


- Adding
```
define( 'GOOGLE_LOGIN_REGISTRATION', 'true' );
```
in wp-config.php file.

**Note:** If the checkbox is ON then, it will register valid Google users even when WordPress default setting, under

*Settings > General Settings > Membership > Anyone can register* checkbox

is OFF.

### Restrict user registration to one or more domain(s)

By default, when you enable user registration via constant `GOOGLE_LOGIN_REGISTRATION` or enable *Settings > Google Login > Enable Google Login Registration*, it will create a user for any Google login (including gmail.com users). If you are planning to use this plugin on a private, internal site, then you may like to restrict user registration to users under a single Google Suite organization. This configuration variable does that.

Add your domain name, without any schema prefix and `www,` as the value of `GOOGLE_LOGIN_DOMAINS` constant or in the settings `Settings > Google Login > Allowed Domains`. You can allow multiple domains. Please separate domains with commas. See the below example to know how to do it via constants:
```
define( 'GOOGLE_LOGIN_DOMAINS', 'example.com,sample.com' );
```

**Note:** If a user already exists, they **will be allowed to log in with Google** regardless of whether their domain is allowed or not. Allowing domains will only prevent users from **registering** with email addresses from non-allowed domains.

#### wp-config.php parameters list

* `GOOGLE_LOGIN_CLIENT_ID` (string): Google client ID of your application.
* `GOOGLE_LOGIN_SECRET` (string): Secret key of your application
* `GOOGLE_LOGIN_REGISTRATION` (boolean) (optional): Enable new user registration? If not set, inherits from `Settings > General Settings > Membership`.
* `GOOGLE_LOGIN_DOMAINS` (string) (optional): Comma-separated list of allowed domain names. If empty, all domains are allowed.

== Screenshots ==

1. Google Login option added.
2. Plugin settings screen.
3. Google Developer Console settings.

== Changelog ==

= 1.0.0 =
Forked from rtCamp's Google Login plugin.
Chore: Documentation update.

== Upgrade Notice ==

