{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "defaults": {
        "title_fmt": "%s - Cascade",
        "title": "(missing title)",
        "type": "html5",
        "block": null,
        "block_fmt": null,
        "connections": [

        ]
    },
    "groups": {
        "basic": {
            "require": [

            ],
            "defaults": {
                "skeleton": true
            },
            "routes": {
                "/": {
                    "title": "Hello",
                    "block": "page/hello",
		    "title_fmt": null,
                    "connections": [

                    ]
                }
            }
        },
        "admin": {
            "require": {
                "block_allowed": "admin/main"
            },
            "defaults": {
                "skeleton": false
            },
            "routes": {
                "/admin": {
                    "title": "Administration",
                    "block": "admin/main"
                },
                "/admin/**": {
                    "title": "Administration",
                    "block": "admin/main",
                    "connections": {
                        "path": [
                            "router",
                            "path_tail"
                        ]
                    }
                }
            }
        },
        "auto": {
            "defaults": {
                "block_fmt": "page/%s",
                "skeleton": true
            },
            "routes": {
                "/$block": [

                ],
                "/$block/**": [

                ]
            }
        }
    },
    "reverse_routes": {
        "admin": {
            "url": "/admin/{block}",
            "args": [
                "block"
            ]
        },
        "documentation": {
            "url": "/documentation/block/{block}",
            "args": [
                "block"
            ]
        }
    }
}
