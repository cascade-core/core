{
	"_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
	"php": {
		"date.timezone": "Europe/Prague",
		"log_errors": true,
		"html_errors": false,
		"display_errors": false,
		"error_reporting": 32767,
		"ignore_repeated_errors": true
	},
	"core": {
		"default_locale": "cs_CZ",
		"auth_class": "",
		"context_class": "Context",
		"app_init_file": []
	},
	"define": {
	},
	"debug": {
		"development_environment": true,
		"debug_logging_enabled": true,
		"always_log_banner": true,
		"log_memory_usage": true,
		"add_cascade_graph": true,
		"animate_cascade": false,
		"profiler_stats_file": "var/profiler.stats"
	},
	"output": {
		"template_engine_class": "Template",
		"default_type": "html5"
	},
	"graphviz": {
		"renderer": {
			"link": "/core/graphviz.php?hash={hash}&cfg={profile}&format={ext}"
		},
		"cascade": {
			"title": "Cascade {hash}",
			"src_file": "{DIR_ROOT}var/graphviz/cascade-{hash}.{ext}",
			"cache_file": "{DIR_ROOT}var/graphviz/cascade-{hash}.{ext}",
			"doc_link": null
		}
	},
	"block_map": {
	},
	"block_storage": {
		"class": {
			"storage_class": "ClassBlockStorage",
			"storage_weight": 30
		},
		"ini": {
			"storage_class": "IniBlockStorage",
			"storage_weight": 60
		}
	}
}
