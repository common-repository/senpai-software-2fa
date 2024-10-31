=== Senpai Software - Two-factor authentication (2FA) with a key file ===
Contributors: senpaisoftware
Donate link: https://senpai.software/donate
Tags: 2FA, two factor authentication, limit login attempts, disable XML-RPC, brute force, key file, security, senpai software
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 6.4
Stable tag: 2.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Get strong protection against brute force attacks with unique two-factor authentication.

== Description ==

### THE MOST UNIQUE TWO-FACTOR AUTHENTICATION METHOD

This plugin gives you the ability to turn any file on your computer into a unique key which you will use to access the admin area.

The plugin only works using HTTPS!

#### Properties
* File is not downloaded or stored physically on the site.
* The plugin does not create additional security risks.
* Plugin code does not create additional load on the site.
* Intuitive interface.
* Provides the maximum level of protection against brute force attacks. Even if a hacker has access to your computer, it will be extremely difficult for him to guess which file is the key.
* Any file can be used as a key, for example: photo, video, text document, song, operating system system file, whatever...
* File size up to 1 GB.
* Limit Login Attempts.
* Disable XML-RPC.

Keep in mind that if you change the contents of the file selected as a key, you will not be able to access the admin area.

https://youtu.be/odD0gaWsJQY

== Frequently Asked Questions ==

= Where are the settings? =

Settings are located in the edit section of your profile

= What should I do if I have lost my key file? =

If you are not an administrator.
Ask your site administrator to turn off two-factor authentication for your profile. After that, you will be able to log in using only your login and password, and then select a new file.

If you are administrator.
* Go to your website database.
* Find usermeta table.
* Find the row where 'user_id' = your user ID, and 'meta_key' = 'senpai_software_2fa_status'.
* Change the value of the 'meta_value' field to 'disable'.
* Now you can use only login and password, and then choose a new key file.

== Screenshots ==

1. This is what the login page will look like
2. This is how the section in the profile settings will look like
3. 2FA Settings page

== Changelog ==

= 2.0.1 =
* Added namespace

= 2.0.0 =
* Added ability to disable XML-RPC.
* Added ability to set restrictions for login attempts.

= 1.0.2 =
* Misc: Add deactivation function.

= 1.0.1 =
* Bug Fix: Fixed an issue where an admin couldn't change another user's settings.
* Misc: Replaced wp_hash with sha1.

= 1.0 =
* Release this plugin to the masses!

== Upgrade Notice ==

Disable authentication before update 1.0.1. After updating the plugin, update your key file.