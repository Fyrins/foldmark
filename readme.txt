=== Foldmark ===
Contributors: fyrins
Donate link: https://github.com/sponsors/Fyrins
Tags: faq, schema, structured data, accordion, json-ld
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn an accordion block into an FAQ with schema.org FAQPage structured data. Pure PHP, no JavaScript, nothing stored in your content.

== Description ==

An FAQ written with the accordion block reads well for people, but machines only see a list of headings and paragraphs. Foldmark adds an **FAQ** block to the editor. It is the accordion block you already know, preset as a list of questions, and the plugin publishes its questions and answers as schema.org `FAQPage` data in the page head.

Search engines and AI assistants that read structured data then get every question and its answer as an explicit pair, instead of guessing them from the layout.

= How you use it =

Insert the **FAQ** block, type a question in each heading and its answer in the panel below. To turn an existing accordion into an FAQ, select it and pick **FAQ** in the block variations of the sidebar. Pick **Accordion** to turn it back.

Several FAQ blocks on one page are merged into a single `FAQPage`, because one page should describe one FAQ. A question that appears twice is published once.

= What it does not do =

It does not promise anything in Google results. Google stopped showing FAQ rich results in May 2026 and no longer reports them in its testing tools. `FAQPage` is still a valid schema.org type, which Google says it keeps reading to understand pages, and which other search engines and AI systems use as well. Foldmark publishes valid markup; what any engine does with it is up to that engine.

It does not touch the look or the behaviour of the accordion, and it never changes its HTML.

= How it works =

When a singular post or page is displayed, the plugin reads the blocks saved in its content, including synced patterns, finds the accordions marked as FAQ and prints one JSON-LD script in the head.

* No JavaScript, in the editor or on the front end. The FAQ block is declared in PHP.
* Nothing is written into the saved content. The FAQ setting lives in the block comment, so deactivating the plugin never invalidates a block.
* No database option, no settings page.
* No outbound network request, no tracking, no external asset.
* Answers keep only the markup Google accepts in an answer: headings, paragraphs, lists, links, bold and italic.
* Nothing is printed on archives, feeds, or password-protected posts.
* If the page already contains a Yoast SEO or Rank Math FAQ block, Foldmark prints nothing, to avoid two `FAQPage` on the same page.

= For developers =

* `foldmark_output_enabled` (bool): return false to stop printing the script.
* `foldmark_get_graph()`: returns the nodes for the current view, available before `wp_head`. Combine both to move the FAQ into the graph of your SEO plugin.
* `foldmark_graph` (array $nodes, WP_Post $post): change or empty the nodes.
* `foldmark_answer_allowed_html` (array): the `wp_kses()` rules applied to answers.
* `foldmark_conflicting_blocks` (string[]): blocks whose presence turns the output off.

Development happens at https://github.com/Fyrins/foldmark .

== Installation ==

1. Install the plugin through **Plugins > Add New**, or upload the folder to `wp-content/plugins/`.
2. Activate it.
3. Edit a post and insert the **FAQ** block.

== Frequently Asked Questions ==

= Will I get FAQ rich results in Google? =

No. Google retired FAQ rich results for every site in May 2026. The markup is still read by search engines and AI systems to understand the page.

= Does it work with the Details block? =

No. The accordion block has the structure of an FAQ: a container, one heading per question and one panel per answer. The Details block has none of that.

= What happens if I deactivate the plugin? =

Your FAQ blocks stay valid accordions and keep working. Only the JSON-LD script disappears.

= Does it conflict with Yoast SEO or Rank Math? =

Their own FAQ blocks print an `FAQPage`. When one of them is on the page, Foldmark prints nothing. Their general page graph is not affected.

= Does it send anything anywhere? =

No. There is no outbound request of any kind.

== Screenshots ==

1. The FAQ block in the block inserter.
2. An FAQ filled in the editor.
3. The JSON-LD printed in the page head.

== Changelog ==

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.0.0 =
First release.
