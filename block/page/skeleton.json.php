{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "outputs": {
        "done": 1,
        "title": "Cascade skeleton"
    },
    "blocks": {
        "skeleton": {
            "block": "core/out/page",
            "in_val": {
                "css_link": "/core/style/default.css"
            }
        },
        "slot_header": {
            "block": "core/out/slot",
            "in_val": {
                "name": "header",
                "slot": "html_body",
                "slot_weight": 10
            }
        },
        "slot_default": {
            "block": "core/out/slot",
            "in_val": {
                "name": "default",
                "slot": "html_body",
                "slot_weight": 50
            }
        },
        "slot_footer": {
            "block": "core/out/slot",
            "in_val": {
                "name": "footer",
                "slot": "html_body",
                "slot_weight": 90
            }
        },
        "h1": {
            "block": "core/out/header",
            "in_val": {
                "level": 1,
                "slot_weight": 1
            },
            "in_con": {
                "text": [
                    "page_title",
                    "title"
                ],
                "slot": [
                    "slot_header",
                    "name"
                ]
            }
        },
        "menu_builder": {
            "block": "core/ini/router_links",
            "in_con": {
                "config": [
                    "load_routes",
                    "data"
                ]
            }
        },
        "main_menu": {
            "block": "core/out/menu",
            "in_con": {
                "items": [
                    "menu_builder",
                    "links"
                ],
                "slot": [
                    "slot_header",
                    "name"
                ]
            },
            "in_val": {
                "layout": "row",
                "max_depth": 0,
                "slot_weight": 5
            }
        },
        "message_queue": {
            "block": "core/out/message_queue",
            "in_con": {
                "slot": [
                    "slot_default",
                    "name"
                ]
            }
        }
    }
}