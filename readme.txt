=== jsDelivr - Wordpress CDN Plugin ===
Contributors: jimaek,martinsuly 
Donate link: http://www.jsdelivr.com
Tags: cdn,speed,jsdelivr,optimize,delivery,network,javascript,async,defer,performance,
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 0.1

The official plugin of jsDelivr.com, a free public CDN. An easy way to integrate the service and speed up your website using our super fast CDN.

== Description ==

This plugin allows you to easily integrate and use the services of [jsDelivr.com](http://www.jsdelivr.com) in your WordPress website.
Just update and then scan your website for files that can be loaded from our CDN.
Features:
* On the fly rewriting of all URLs. No need to change the code.
* Move selected files to footer
* Apply Async/Defer loading to your javascripts.
* Compatible with W3 Total Cache and WP Super Cache
* Automatic synchronization with our DB.
* Allows you to select the files you want to load from the CDN
* Supports HTTPS
* Uses Google CDN to load jQuery to take advantage of the user's browser cache.

== Installation ==

1. Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation.
2. Activate the Plugin from Plugins page.
3. Go to Settings-> jsDelivr CDN and follow the instructions


== Frequently Asked Questions ==

= How the priority system works? =
The priority works only when the files are moved to footer.
Zero has the highest priority. So for example you can give to jQuery the priority 0 then to a plugin priority 1 and if the plugins has javascript addons then 2,3,4,5...
This ensures that there will be no problems with undeclared functions etc...
If you leave the priority as is then the files will be moved to footer with the same order they were originally declared.

= What does the yellow match mean? =
You have to be careful with those. It can be two things.
1. It can be a more recent version of the same file you are using. In this case you must make sure that the newer version wont break anything. You can enable it temporary and test it.
2. It can be a similar file (from the plugin's perspective) from an other package. Again you will have to test it to be sure.

= I get a 100% matching file but the name of the package is wrong =
If the match is 100% then you just matched a file used also by that package.
Some plugins use common images or well known libraries to work, you just matched an identical file from an other package.
Dont worry about it.




== Changelog ==

= 0.1 =
* First release
