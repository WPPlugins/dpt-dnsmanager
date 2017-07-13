=== Plugin Name ===
Contributors: digitalpixies
Donate link: http://digitalpixies.com/
Tags: dns, bind, bind9, nsd4, nsdc, domain name, cname, soa, mx
Requires at least: 3.0.0
Tested up to: 4.8.0
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage a self hosted DNS from Wordpress.

== Description ==

Manage a self hosted DNS from Wordpress. Ability to add, edit, delete DNS records (MX, SOA, CNAME, A RECORDS). This plugin is designed to generate the DNS file that can be used on the same server with nsd4 or the dns file can be exported and manually copied and pasted to another server. Future enhancements will be added such as dynamic ip resolution and change notifications.

QUICK GUIDE
1) Install and activate module
2) Add/edit DNS records as needed. Save the changes.
3) Either download or use the DNS that exists in the wordpress uploads folder. If the dns server is running on the same wordpress server, you can create a symbolic link to the dns file to your nsd's dns zone folder location.
4) Click on "Restart" or "Save and Restart" to force nsd to restart. (If there are problems with permissions executing shell commands, you must make changes to sudo to allow execution of nsd-control by the webserver user. use "System Check" to confirm if there is a permission problem.)

== Installation ==

1. Upload `dpt-dnsmanager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Question ==

= I'm having problems logging in after plugin updates and/or WP upgrade =

A plugin or Wordpress upgrade has introduced some incomplete javascript into the login page (which it shouldn't) and has caused this plugin's javascript to subsequently not execute. To gain back access to the website:
1) Delete the dpt-dnsmanager plugin (or other plugins that you suspect is preventing your site from loading)
2) Login
3) Disable or uninstall the plugins impacting this module before re-installing dpt-dnsmanager plugin.

= I'm having problems with permissions =

If you encounter any errors with the "System Check" it fails for a few reasons:
* The response from the commands were different from what I expected. This could be due to differing versions
* You do not have sudo installed
* The webserver user is not configured to have permission to run some shell commands

= How do I allow webserver user permission to execute sudo commands =

This will differ between different Linux environments. Assuming you are using nsd4 and your webserver user is www-data:
1) create a file /etc/sudoers.d/nsd4
2) contents of the file should be (without quotes) "www-data ALL = (ALL) NOPASSWD: /usr/sbin/nsd-control, /usr/sbin/nsd"

== Changelog ==

= 1.2.1 =
* Incorrectly parsed the parameters due to extra spaces
* Moved reload function into angular from jquery

= 1.2.0 =
* Change the way bootstrap is loaded so it works with other dpt modules

= 1.1.0 =
* relocate dns host file to reside in wordpress' upload dir

= 1.0 =
* Initial release

== Upgrade Notice ==
