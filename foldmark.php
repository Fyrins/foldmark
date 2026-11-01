<?php
/**
 * Plugin Name:       Foldmark
 * Description:       Publish an FAQ accordion as schema.org FAQPage structured data.
 * Version:           1.0.0
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Author:            Alexandre Revire
 * Author URI:        https://alexandre-revire.fr
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       foldmark
 * Domain Path:       /languages
 *
 * @package Foldmark
 */

/*
 * Copyright (C) 2026 Alexandre Revire
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

defined( 'ABSPATH' ) || exit;

define( 'FOLDMARK_VERSION', '1.0.0' );
define( 'FOLDMARK_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-foldmark-editor.php';
require_once __DIR__ . '/includes/class-foldmark-faq-extractor.php';

Foldmark_Editor::init();
