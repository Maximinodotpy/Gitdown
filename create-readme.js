const fs = require('fs');

// A function that turns markdown content into a WordPress readme.txt file
function MD_TO_WP(input) {
    const regex = /##(.*)/gm;

    const subst = `=$1 =`;

    // The substituted value will be contained in the result variable
    const result = input.replace(regex, subst);

    return result;
}

let result = ``;

const Head = `=== Gitdown: Git Repository to WordPress Blog Posts ===
Contributors: maximmaeder
Donate link: https://maximmaeder.com
Tags: markdown, github, posts, cms, article-management, markdown-to-html, blog
Requires at least: 6.1.0
Tested up to: 6.2.0
Stable tag: __MGD_VERSION__
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Use Gitdown to Publish Markdown Posts from a repository to your WordPress Blog.

== Description ==

`;

const README_MD_PATH = './readme.md';
const FAQ_PATH = './docs/faq.md';
const CHANGELOG_PATH = './docs/changelog.md';

result += Head;
result += MD_TO_WP(fs.readFileSync(README_MD_PATH, 'utf8'));
result += `== Frequently Asked Questions ==`;
result += MD_TO_WP(fs.readFileSync(FAQ_PATH, 'utf8'));
result += `\n\n== Screenshots ==\n`;
result += `
1. Dashboard User Interface
2. How to View Number One
3. How to View Number Two
4. Gitdown Reading Settings
`
result += `\n== Changelog ==\n`;
result += MD_TO_WP(fs.readFileSync(CHANGELOG_PATH, 'utf8'));


// Write the readme.md file to the readme.txt file
fs.writeFileSync('./readme.txt', result, 'utf8');
