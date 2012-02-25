; <?php exit(); __halt_compiler(); ?>
; 
; Core fallback config file
;
;  - Copy this file to app/ directory and modify as you need.
;  - This is standard INI file (like php.ini).
;  - Sections named "[module:???]" represents starting set of modules,
;    where "???" is module ID. In these sections:
;  	- Key ".module" is required; specifies module name (ie. "core/output").
;  	- Key ".force-exec" is optional; if true, module is
;  	  executed even if dependencies don't require that.
;  	- All other keys define module's inputs. Scalar constants are
;  	  specified as usual; connections are written like arrays:
;  	  	input[] = "source-module:output"
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
add_pipeline_graph	= true
animate_pipeline	= false
pipeline_graph_link	= "/doc/%s"
profiler_stats_file	= "var/profiler.stats"

; default output configuration
[output]
default_type		= "html5"

; constants set by define(strtoupper(key), value)
[define]
; key			= value

; module replacement table
[module-map]
;old-module/name	= "replacement-module/name"


;
; starting modules
;

[module:router]
.module		= core/ini/router
config		= core/routes.ini.php

[module:page]
.module		= "core/value/pipeline_loader"
.force-exec	= true
output-forward	= "done,title"
skeleton[]	= "router:skeleton"
content[]	= "router:content"
extra[]		= "router:extra"
enable[]	= "router:done"

[module:page_title]
.module		= core/out/set_page_title
.force-exec	= true
title[]		= page:content_0_title
format[]	= router:title_fmt
title-fallback[] = router:title


; vim:filetype=dosini:

