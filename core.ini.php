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
;fix_lighttpd_get	= true

; debug tools
[debug]
debug_logging_enabled	= true
always_log_banner	= true
log_memory_usage	= true
add_pipeline_graph	= true
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

[module:skeleton]
.module		= core/out/page
.force-exec	= true

[module:hello]
.module		= core/out/raw
.force-exec	= true
; Never do this:
data		= "<p>Hello world!</p><p>Look at <a href='/doc'>documentation</a>!</p>"
enable[]	= "router:hello"

[module:doc_index]
.module		= "core/devel/doc/index"
.force-exec	= true
enable[]	= "router:index"

[module:doc_show]
.module		= "core/devel/doc/show"
.force-exec	= true
module[]	= "router:path_tail"
show-code	= false
enable[]	= "router:show"

;;
;; Use something like this for real sites:
;;
;
;[module:page]
;.module	= "core/value/pipeline_loader"
;.force-exec	= true
;output-forward	= "done,title"
;content[]	= "router:content"
;extra[]	= "router:extra"
;enable[]	= "router:done"
;
;[module:page_title]
;.module	= core/out/set_page_title
;.force-exec	= true
;title[]	= page:content_0_title
;title-fallback[] = router:title
;format[]	= router:title_fmt
;
;[module:page_error]
;.module	= core/out/message
;.force-exec	= true
;is-error	= true
;title		= "Sorry!"
;text		= "Page not found."
;http-status-code = 404
;hide[]		= page:content_0_done

; vim:filetype=dosini:

