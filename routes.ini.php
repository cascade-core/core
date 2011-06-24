; <?php exit(); ?>
;
; Default routes for 'hello world' and documentation browser
;
; Look at core/value/pipeline_loader module before you use this.
;

[/]
hello = true
index = false
show = false

[/doc]
hello = false
index = true
show = false

[/doc/**]
hello = false
index = false
show = true
; path_tail is '**' part


; vim:filetype=dosini:

