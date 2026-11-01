# Changelog

All notable changes to this project are documented in this file. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the
project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-11-01

### Added

- FAQ variation of the `core/accordion` block, preset with three questions, and
  an Accordion variation to turn it back.
- One `FAQPage` JSON-LD script per singular view, built from the saved content,
  synced patterns included.
- Filters `foldmark_output_enabled`, `foldmark_graph`,
  `foldmark_answer_allowed_html`, `foldmark_conflicting_blocks` and
  `foldmark_types`, and the `foldmark_get_graph()` function.
- Schema types behind the `Foldmark_Type` interface, so other types can be
  published from accordions.
- No output when the FAQ block of Yoast SEO, Rank Math, SEOPress or All in One
  SEO is on the page.
- French translation.

[1.0.0]: https://github.com/Fyrins/foldmark/releases/tag/v1.0.0
