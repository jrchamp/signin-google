=== Sign in with Google ===
Contributors: jrchamp, rtCamp
Tags: sso, oauth, authentication, google, login
Requires at least: 5.5
Tested up to: 6.9
Requires PHP: 7.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://github.com/sponsors/jrchamp

Minimal plugin that allows WordPress users to log in using Google.

== Description ==

Minimal plugin to let your users log in to WordPress using their Google accounts.

### Initial Setup

1. Create a project on the [Google Developers Console](https://console.developers.google.com/apis/dashboard).


2. Go to the **Credentials** tab, then create OAuth client.
   * Application type will be **Web Application**
   * Add `YOUR_DOMAIN/wp-login.php` in **Authorized redirect URIs**


3. This will give you **Client ID** and **Secret key**.


4. Input these values in `Settings > Sign in with Google`, or in `wp-config.php` using the following code snippet:

```
define( 'SIGNIN_GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID' );
define( 'SIGNIN_GOOGLE_SECRET', 'YOUR_SECRET_KEY' );
```

### How to enable automatic user registration

You can enable user registration either by
- Enabling *Settings > Sign in with Google > Enable Registration*

OR

- Adding
```
define( 'SIGNIN_GOOGLE_REGISTRATION', 'true' );
```
in wp-config.php file.

### Restrict user registration to one or more domain(s)

If you are planning to use this plugin on a private, internal site, then you probably want to restrict user registration to users under a single Google domain.

Add your domain name (the part after `@`) as the value of `SIGNIN_GOOGLE_DOMAINS` constant or in the settings `Settings > Sign in with Google > Allowed Domains`. You can allow multiple domains, separated by a comma. For example:
```
define( 'SIGNIN_GOOGLE_DOMAINS', 'example.com,wordpress.org' );
```

**Note:** If a user already exists, they **will be allowed to log in with Google** regardless of whether their domain is allowed or not. Allowing domains will only prevent users from **registering** with email addresses from non-allowed domains.

#### wp-config.php parameters list

* `SIGNIN_GOOGLE_CLIENT_ID` (string): Google client ID of your application.
* `SIGNIN_GOOGLE_SECRET` (string): Secret key of your application
* `SIGNIN_GOOGLE_REGISTRATION` (boolean) (optional): Enable new user registration?
* `SIGNIN_GOOGLE_DOMAINS` (string) (optional): Comma-separated list of allowed domain names. If empty, all domains are allowed.

== Screenshots ==

1. Sign in with Google option page.
2. Plugin settings screen.
3. Google Developer Console settings.

== Frequently Asked Questions ==

== Changelog ==

= 1.0.0 =
Forked from rtCamp's Login with Google plugin (thank you!).
Support for WordPress multisite (network configuration level)
Streamlined (no external depenencies, no build system, a single API call)

== Upgrade Notice ==

