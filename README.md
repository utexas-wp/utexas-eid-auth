# utexas-eid-auth

This is a WordPress plugin that provides configuration for using the OneLogin library to sign in using Enterprise Authentication.

## Installation

1. In the site's Pantheon dashboard, put the "Dev" environment in SFTP mode and clone the database and files from the "Live" environment into the "Dev" environment (under "Database/Files").
2. Navigate to the "Dev" environment in the browser and sign into the WordPress site with an administrative account.
3. In WordPress "Dev" environment UI, go to Plugins (`/wp-admin/plugins.php`).
4. Go to **Plugins > Add New Plugin** (`/wp-admin/plugin-install.php`)
5. Using the "Search plugins" form, find and add "Native PHP Sessions" and "WP SAML Auth", both of which are authored by Pantheon Systems.
6. Download the latest version of UTexas EID Authentication at https://wcms.its.utexas.edu/utexas-eid-auth.zip
7. From the same interface, click "Upload New Plugin."
8. Upload the zip file and activate the plugin.
10. Sign out of the site and confirm you can authenticate at `dev-yoursite.pantheonsite.io/saml/login/`

## Updates

Updates are provided by the WordPress Update API. Use either the WordPress UI to update this plugin, or use Terminus (`terminus wp <site>.dev -- plugin update utexas-eid-auth`).

## Overriding configuration on a specific site

Options defined in `wpsa-options.php` are the defaults for UT sites on Pantheon. If for some reason you must override anything set there, create your own mini-plugin by renaming `utexas-eid-auth-overrides.php.inc` to `utexas-eid-auth-overrides.php` and making relevant configuration changes. You must then activate this plugin for its changes to take effect.

- **auto_provision**: (default: `false`). For sites that should automatically create accounts from successful EID authentication, this should be changed to `true`.
- **permit_wp_login**: (default: `false`). **Changing this configuration option is currently not supported. The current design of `utexas-eid-auth` only allows SSO sign-in.**
- **allowRepeatAttributeName**: MUST be set to true (allow). The OneLogin SAML library includes a validation check for duplicate attribute names in the Authorization Response. The IAM team's SAML response includes two attributes with `FriendlyName="utexasEduPersonAffiliation"` . To avoid this being flagged as invalid, configuration of `samlauth.authentication` needs to include `security_allow_repeat_attribute_name: true` , which passes the value to the underlying library's configuration for `allowRepeatAttributeName`.

- Additional configuration options can be found in:
  - https://github.com/pantheon-systems/wp-saml-auth?tab=readme-ov-file#installation
  - https://github.com/SAML-Toolkits/php-saml/blob/master/advanced_settings_example.php
