; <?php exit(); ?>
; 
; Core fallback config file
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
always_log_banner	= true
;app_init_file		= app/init.php

; starting module
[module:INIT]
module		= core/init
force-exec	= true


; vim:encoding=utf-8:filetype=dosini:
