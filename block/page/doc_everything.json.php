{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "outputs": {
        "done": [
            "doc:done"
        ],
        "title": "Documentation"
    },
    "blocks": {
        "doc": {
            "block": "core/devel/doc/everything",
            "force_exec": 1,
            "in_val": {
                "heading_level": 1,
                "require_description": "",
                "slot_weight": 60
            }
        },
        "version_hd": {
            "block": "core/out/header",
            "in_val": {
                "text": "Version",
                "level": "1*",
                "slot_weight": 30
            },
            "in_con": {
                "enable": [
                    "version",
                    "done"
                ]
            }
        },
        "version": {
            "block": "core/devel/version",
            "in_val": {
                "format": "details",
                "slot_weight": 40
            }
        }
    }
}