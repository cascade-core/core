; <?php exit(); __halt_compiler(); ?>
; 
; Core fallback config file
;
;  - Copy this file to app/ directory and modify as you need.
;  - This is standard INI file (like php.ini).
;  - Sections named "[block:???]" represents starting set of blocks,
;    where "???" is block ID. In these sections:
;  	- Key ".block" is required; specifies block name (ie. "core/output").
;  	- Key ".force-exec" is optional; if true, block is
;  	  executed even if dependencies don't require that.
;  	- All other keys define block's inputs. Scalar constants are
;  	  specified as usual; connections are written like arrays:
;  	  	input[] = "source-block:output"
;  - All unknown options and sections are ignored, but can be used in
;    future versions.
;

; php.ini options here
[php]
log_errors		= true
html_errors		= false
display_errors		= false
error_reporting		= E_ALL
ignore_repeated_errors	= true

; core configuration
[core]
default_locale		= "cs_CZ"
;context_class		= Context
;app_init_file		= app/init.php
;fix_lighttpd_get	= true

; debug tools
[debug]
debug_logging_enabled	= true
always_log_banner	= true
log_memory_usage	= true
add_cascade_graph	= true
animate_cascade		= false
cascade_graph_link	= "/documentation/block/%s"
profiler_stats_file	= "var/profiler.stats"

; default output configuration
[output]
default_type		= "html5"

; constants set by define(strtoupper(key), value)
[define]
; key			= value

; block replacement table
[block-map]
;old-block/name	= "replacement-block/name"


;
; starting blocks
;

[block:router]
.block		= core/ini/router
config		= core/routes.ini.php

[block:content]
.block		= "core/value/cascade_loader"
output_forward	= "done,title,type"
content[]	= "router:content"
enable[]	= "router:done"

[block:skeleton]
.block		= "core/page/skeleton"
enable[]	= ":and"
enable[]	= "router:skeleton"
enable[]	= "content:content_0_done"

[block:page_title]
.block		= core/out/set_page_title
title[]		= :or
title[]		= content:content_0_title
title[]		= router:title
format[]	= router:title_fmt

[block:page_type]
.block		= core/out/set_type
type[]		= :or
type[]		= content:content_0_type
type[]		= router:type

;[block:extra]
;.block		= "core/value/cascade_loader"
;output_forward	= "done"
;extra[]	= "router:extra"
;enable[]	= "content:content_0_done"


[block:error_page]
.block		= core/page/error
enable[]	= :not
enable[]	= content:content_0_done


; vim:filetype=dosini:

