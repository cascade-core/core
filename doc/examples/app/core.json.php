{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "core": [

    ],
    "block_storage": [

    ],
    "blocks": {
        "config": {
            "block": "core/config",
            "x": 0,
            "y": 0
        },
        "router": {
            "block": "core/router",
            "x": 178,
            "y": 34,
            "in_con": {
                "routes": [
                    "config",
                    "routes"
                ]
            }
        },
        "main": {
            "block": "core/value/block_loader",
            "x": 404,
            "y": 15,
            "in_con": {
                "enable": [
                    "router",
                    "done"
                ],
                "block_fmt": [
                    "router",
                    "block_fmt"
                ],
                "connections": [
                    "router",
                    "connections"
                ],
                "block": [
                    "router",
                    "block"
                ]
	    },
	    "in_val": {
		"output_forward": [ "title", "type", "done" ]
            }
        },
        "skeleton": {
            "block": "skeleton",
            "x": 436,
            "y": 239,
            "in_con": {
                "enable": [
                    "router",
                    "skeleton"
                ]
            }
        },
        "page_options": {
            "block": "core/out/page_options",
            "x": 671,
            "y": 114,
            "in_con": {
                "enable": [
                    "main",
                    "done"
                ],
                "title": [
                    ":or",
                    "main",
                    "title",
                    "router",
                    "title"
                ],
                "title_fmt": [
                    "router",
                    "title_fmt"
                ],
                "type": [
                    ":or",
                    "main",
                    "type",
                    "router",
                    "type"
                ]
            }
        }
    }
}
