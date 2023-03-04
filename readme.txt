=== Gitdown: Git Repository to Blog Posts ===
Contributors: maximmaeder
Donate link: https://maximmaeder.com
Tags: markdown, github, posts, cms
Requires at least: 6.1.1
Tested up to: 6.1.1
Stable tag: 1.0.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Use Gitdown to Publish Markdown Posts from a repository to your Wordpress Blog.

== Description ==

*Gitdown* can be used to upload markdown posts from a remote repository to the WordPress. It allows you to specify a glob pattern that will be used to find the posts and then you can also specify a function that will resolve the content of the posts.

= Third Party Services =

This plugin uses the following third Party Services and Programs

- *Globster.xyz*: Is used in the how to section for Gitdown, to teach glob patterns.
    - [Homepage](https://globster.xyz/)
    - [Privacy Policy](https://globster.xyz/privacy/)
- *Tailwindcss*: Is used to style the main admin UI.
    - [Homepage](https://tailwindcss.com/)
- *Vue.js*: Is used for the main admin UI and its interactivity features.
    - [Homepage](https://vuejs.org/)
- *mnapoli/FrontYAML*: Is used to get frontmatter information in markdown file.
    - [Homepage](https://github.com/mnapoli/FrontYAML)
- *Parsedown*: Is used to parse the markdown content and turn it into HTMl.
    - [Homepage](https://parsedown.org/)
- *git-php*: Is used to clone and fetch git repositories.
    - [Homepage](https://github.com/czproject/git-php)

== Frequently Asked Questions ==

= Can I supply multiple glob patterns? =

Yes you can! Simply seperate them by commas.

= Does Gitdown read nested categories from the markdown file? =

Yes it does! Something like Animals/Cats, will create these two classes in a nested fashion and add the last one as the category.


== Screenshots ==

1. Dashboard User Interface
2. How to View Number One
3. How to View Number Two
4. Gitdown Reading Settings

== Changelog ==

= 1.0.1 =
- Small Bugfixes
- Removed Pull Repo Button
