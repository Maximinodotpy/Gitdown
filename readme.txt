=== Gitdown: Git Repository to WordPress Blog Posts ===
Contributors: maximmaeder
Donate link: https://maximmaeder.com
Tags: markdown, github, posts, cms, article-management, markdown-to-html, blog
Requires at least: 6.1.0
Tested up to: 6.2.0
Stable tag: 1.1.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Use Gitdown to Publish Markdown Posts from a repository to your Wordpress Blog.

== Description ==

*Gitdown* can be used to upload markdown posts from a remote repository to the WordPress. It allows you to specify a glob pattern that will be used to find the posts and then you can also specify a function that will resolve the content of the posts.

= 🎈 Easy to use =

Setting Gitdown up to work with your Repository and Glob Pattern takes only little time and can be easily changed at any time.

= 🔨 Customization =

It does not matter how your Repository is shaped you can change the glob pattern to your liking and I will understand your repo correctly.

= 🎁 100% Free =

Gitdown is completely free and you can use for what ever you want.

= 📚 Documentation =
As soon as you activate gitdown it will take you to the documentation page where your should get all the info that you need.

You can also consult the following documents for Help.

- [FAQ](https://github.com/Maximinodotpy/Gitdown/blob/master/docs/faq.md)
- [Frontmatter Keys](https://github.com/Maximinodotpy/Gitdown/blob/master/docs/keys.md)
- [Example File](https://github.com/Maximinodotpy/Gitdown/blob/master/docs/example.md)

⚠️ It may be outdated in some parts as I am somewhat unkeen in writing documentations.

= 👥 Contributing =
All contributions are very welcome, so feel free to make [issues](https://github.com/Maximinodotpy/Gitdown/issues), [proposals](https://github.com/Maximinodotpy/Gitdown/issues/proposals) and [pull requests](https://github.com/Maximinodotpy/Gitdown/pulls).

= ↗ Links =

- [Maximmaeder.com](https://maximmaeder.com/)
- [My Profile on Wordpress.org](https://profiles.wordpress.org/maximmaeder/)
- [Plugin Page](https://wordpress.org/plugins/gitdown)
- [SVN repository](http://plugins.svn.wordpress.org/gitdown/)
- [TortoiseSVN](https://tortoisesvn.net/)

= Third Party Services =

This plugin uses the following third Party Services and Programs.

- *Globster.xyz*: Is used in the how to section for Gitdown, to teach glob patterns.
    - [Homepage](https://globster.xyz/)
    - [Privacy Policy](https://globster.xyz/privacy/)
- *Tailwindcss*: Is used to style the main admin UI.
    - [Homepage](https://tailwindcss.com/)
- *Vue.js*: Is used for the main admin UI and its interactivity features.
    - [Homepage](https://vuejs.org/)
- *mnapoli/FrontYAML*: Is used to get frontmatter information in markdown file.
    - [Homepage](https://github.com/mnapoli/FrontYAML)
- *git-php*: Is used to clone and fetch git repositories.
    - [Homepage](https://github.com/czproject/git-php)


== Frequently Asked Questions ==

= Can I supply multiple glob patterns? =

Yes you can! Simply seperate them by commas.

= Does Gitdown read nested categories from the markdown file? =

Yes it does! Something like Animals/Cats, will create these two classes in a nested fashion and add the last one as the category.

= Does Gitdown read tags =

Yes It does!

= Does Gitdown support thumbnails? =

Yes It does! You simply have to provide a file called 'preview.png' in the same folder as your article and it will be added as the thumbnail. This means that you should have a folder for each article.


== Screenshots ==

1. Dashboard User Interface
2. How to View Number One
3. How to View Number Two
4. Gitdown Reading Settings

== Changelog ==

= 1.0.1 =
- Small Bugfixes
- Removed Pull Repo Button

= 1.0.4 =
- Added Tag Insertion
- wp_generate_attachment_metadata() -> thumbnails will get regenerated if Image Editor is available.

= 1.0.5 =
- Added automated updating and posting of posts (Experimental)

= 1.0.6 =
- Fix: Error when trying to clone a private repository