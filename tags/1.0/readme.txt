=== TNK Twitter importer ===
Contributors: TNK Software(Tanaka Yusuke)
Donate link: http://www.tnksoft.com/soft/internet/tnkti/
Tags: twitter,import,export,twitter to wordpress
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Collects your tweets and uploads them automatically as a blog article.

== Description ==

"TNK Twitter importer" is the WordPress plugin which collects your tweets and uploads them automatically as a blog article. By recording as a blog article, your tweets buried in the timeline become to find easily by internet search engines.

The additional information currently embedded in the tweet is also changed into suitable data. Users, hash tags and URLs are processed as a link, and you can preview posted images as a thumbnail.

There is no preblem even if you tweet a lot as for one day. It posts as an individual article in the group of 1 to 24 hours, one article does not become lazily long.

== Installation ==
1. Upload the folder includes program files to your WordPress plugin folder.
2. Activate the plugin through the plugins menu in WordPress.
3. Select "TNK TI" from the plugins menu and change the setting.

*Twitter app: 
This is a key of the app which gets the information of the timeline. The name and permission of an app are arbitrary. You can get a key from the Twitter's developer site but you should be careful of having to prepare your cellphone's number for authentication.

*Post as draft: 
It records the article of the collected tweets as a draft.

*Post category: 
Specify the category of articles if you need.

*Twitter ID: 
Specifies the user ID of the time line to get. ID is the number. If you know only a screen name(@name), you may use ID search service.

*Title: 
It is a title of a blog article. The following string are replaced.
{$name} -> User's name.
{$sname} -> User's screen name(@).
{$year} -> Tweeted year.
{$month} -> Tweeted month.
{$day} -> Tweeted day.
{$hour1} -> Start of the tweet time division.
{$hour2} -> End of the tweet time division.

*Collection: 
Specify the period which collects tweets by hours.

*Reset schedule: 
Deletes the history of the timeline. If you want to reset, you had better delete the existing tweet articles.

== Frequently Asked Questions ==
*I have a question(or a request): 
If you have any questions or requests. Please send a mail to me. It's free.


== Screenshots ==
1. Export exsample
2. Setting page


== Changelog ==

= 1.0 =
* First release
