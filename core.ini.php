; <?php exit(); ?>
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

; debug tools
[debug]
debug_logging_enabled	= true
always_log_banner	= true
add_pipeline_graph	= true
profiler_stats_file	= "var/profiler.stats"

; default output configuration
[output]
;default_type		= "xhtml"

; constants set by define(strtoupper(key), value)
[define]
; key			= value

; module replacement table
[module-map]
;old-module/name	= "replacement-module/name"


;
; starting modules
;

[module:HELLO]
.module		= core/print_r
.force-exec	= true
template	= "core/print_r"
data		= "Hello world!"
enable[]	= "PAGE:done"

[module:PAGE]
.module		= core/page


; vim:encoding=utf-8:filetype=dosini:

