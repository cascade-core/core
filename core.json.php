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
	"cascade_controller_class": "\\Cascade\\Core\\CascadeController",
        "auth_class": "",
        "app_init_file": [
        ],
        "umask": null
    },
    "define": {

    },
    "debug": {
        "development_environment": true,
	"throw_errors": false,
        "debug_logging_enabled": true,
        "always_log_banner": true,
        "log_memory_usage": true,
        "add_cascade_graph": true,
	"cascade_graph_slot": "default",
        "animate_cascade": false,
        "profiler_stats_file": "var/profiler.stats",
        "error_log": null
    },
    "context": {
        "class": "\\Cascade\\Core\\Context",
        "default_locale": "cs_CZ",
        "resources": {
    	    "config_loader": null,
    	    "template_engine": {
                "class": "\\Cascade\\Core\\Template",
                "default_type": "html5"
    	    }
        }
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
            "storage_class": "\\Cascade\\Core\\ClassBlockStorage",
            "storage_weight": 30
        },
        "json": {
            "storage_class": "\\Cascade\\Core\\JsonBlockStorage",
            "storage_weight": 50,
	    "default_block_class": "\\Cascade\\Core\\ProxyBlock",
	    "hashbang_classes": {
		    "plain": "\\Cascade\\Core\\ProxyBlock",
		    "template": "\\Cascade\\Core\\TemplatingProxyBlock"
	    }
        }
    }
}
