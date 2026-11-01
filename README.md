# Foldmark

[![An accordion on the left, an arrow, and on the right the FAQPage JSON-LD it produces.](.wordpress-org/banner-1544x500.png)](.wordpress-org/banner-1544x500.png)

Turns `core/accordion` blocks into an FAQ with schema.org `FAQPage` structured data. Pure PHP: no JavaScript, no build step, nothing written into saved content.

[![CI](https://github.com/Fyrins/foldmark/actions/workflows/ci.yml/badge.svg)](https://github.com/Fyrins/foldmark/actions/workflows/ci.yml)
[![License: GPL v2](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)
[![Sponsor](https://img.shields.io/badge/sponsor-GitHub%20Sponsors-ea4aaa.svg)](https://github.com/sponsors/Fyrins)

An FAQ built with the accordion block reads well for people, but machines only see headings and paragraphs. Foldmark adds an **FAQ** block to the editor (the accordion block, preset as a list of questions) and prints its questions and answers as one JSON-LD `FAQPage` in the page head.

- Plugin page: _submitted, awaiting review on wordpress.org_
- Requires WordPress 6.9, PHP 7.4
- Licensed GPL-2.0-or-later

## What it looks like

Insert the **FAQ** block from the inserter. It is the accordion block, preset with three questions.

[![The block inserter filtered on FAQ, showing the FAQ block and its description.](.wordpress-org/screenshot-1.png)](.wordpress-org/screenshot-1.png)

Write each question in a heading and its answer in the panel. The sidebar shows the block as an FAQ, and **Transform to variation** turns it back into a plain accordion.

[![An FAQ block filled in the editor, with the FAQ card in the block sidebar.](.wordpress-org/screenshot-2.png)](.wordpress-org/screenshot-2.png)

The page head gets one `FAQPage`, here checked in the Schema.org validator.

[![The Schema.org validator showing one FAQPage with its questions, 0 errors and 0 warnings.](.wordpress-org/screenshot-3.png)](.wordpress-org/screenshot-3.png)

## About Google

Google stopped showing FAQ rich results in May 2026 and removed them from its testing tools in June 2026 ([announcement](https://developers.google.com/search/updates)). `FAQPage` remains a valid schema.org type that Google says it still reads to understand pages, and that other search engines and AI systems use. Foldmark publishes valid markup and makes no promise about how any engine displays it.

## Why the accordion block, and not Details

`core/details` has no container: an FAQ made of Details blocks is a set of unrelated blocks scattered through the page, and its `<summary>` is not a heading. `core/accordion`, added in WordPress 6.9, has the shape of an FAQ:

```
.wp-block-accordion                         → FAQPage
  .wp-block-accordion-item                  → Question
    .wp-block-accordion-heading__toggle-title → name
    .wp-block-accordion-panel               → acceptedAnswer.text
```

## Why JSON-LD and not microdata

Microdata needs an element for `Answer.text` that is distinct from the element carrying the `Answer` item. The answer blocks are direct children of the panel, which uses flow layout. Adding a wrapper would change the block DOM and break the `:first-child` and `:last-child` spacing rules of the layout. JSON-LD leaves the accordion markup exactly as core renders it.

## No JavaScript, even in the editor

The `foldmark` attribute and both variations are declared on the server, with the `register_block_type_args` and `get_block_type_variations` filters. WordPress already sends server-side block definitions to the editor, so the FAQ block reaches the inserter without a single line of JavaScript.

| Variation | Scope | Attribute |
|---|---|---|
| `foldmark/faq` | inserter, transform | `{"foldmark":{"type":"faq"}}` |
| `foldmark/accordion` | transform | `{"foldmark":null}` |

The attribute is an object so that other schema types can be added later with another variation and another `type` value.

## Nothing is written into your content

The attribute has no `source`: it lives in the block comment, which block validation ignores. No class or wrapper is added, at save or at render. Deactivating the plugin leaves valid accordions behind; only the script disappears.

## How the graph is built

1. On a singular view, the post content is parsed with `parse_blocks()`, before `wp_head`. Nothing is rendered twice.
2. Synced patterns are followed, with a guard against patterns that include themselves. Unpublished and password-protected patterns are skipped, as core does when rendering.
3. The question is the plain text of the heading title, without the toggle icon. The answer is the saved HTML of the panel blocks, filtered with `wp_kses()` down to the tags Google accepts in `Answer.text`.
4. Empty questions and answers are dropped, duplicate questions are published once, and all FAQ blocks of the page are merged into one `FAQPage`.
5. The script is printed with `wp_print_inline_script_tag()`, encoded with `JSON_HEX_TAG` so no answer can close the script element.

Nothing is printed on archives, feeds, REST responses, password-protected posts, or pages that already contain a Yoast SEO or Rank Math FAQ block.

## Filters and functions

```php
// Hand the FAQ to another plugin's graph instead of printing it.
add_filter( 'foldmark_output_enabled', '__return_false' );
$nodes = foldmark_get_graph(); // Available before wp_head.

// Change or empty the nodes.
add_filter(
	'foldmark_graph',
	function ( array $nodes, WP_Post $post ) {
		return $nodes;
	},
	10,
	2
);

// Keep more markup in answers (wp_kses() format).
add_filter(
	'foldmark_answer_allowed_html',
	function ( array $allowed ) {
		$allowed['code'] = array();
		return $allowed;
	}
);

// Turn the output off when another FAQ block is on the page.
add_filter(
	'foldmark_conflicting_blocks',
	function ( array $names ) {
		$names[] = 'acme/faq';
		return $names;
	}
);
```

## Development

```bash
composer install
composer test      # PHPUnit against a real WordPress, see CONTRIBUTING.md
composer lint      # PHPCS, WordPress Coding Standards
```

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
