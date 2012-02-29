; <?php exit(); __halt_compiler(); ?>

[outputs]
done = true
title = "Cascade skeleton"

[block:skeleton]
.block		= core/out/page
css_link	= /core/style/default.css

[block:slot_header]
.block		= core/out/slot
name		= header
slot		= html_body
slot_weight	= 10

[block:slot_default]
.block		= core/out/slot
name		= default
slot		= html_body
slot_weight	= 50

[block:slot_footer]
.block		= core/out/slot
name		= footer
slot		= html_body
slot_weight	= 90

[block:h1]
.block		= core/out/header
level		= 1
text[]		= page_title:title
slot[]		= slot_header:name
slot_weight	= 1

[block:menu_builder]
.block		= core/ini/router_links
config[]	= load_routes:data

[block:main_menu]
.block		= core/out/menu
items[]		= menu_builder:links
layout		= row
max_depth	= 0
slot[]		= slot_header:name
slot_weight	= 5

; vim:filetype=dosini:

