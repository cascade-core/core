URL Routing
===========

@todo Review routing, especially reverse router and block scanning.


Forward router -- URL parser
----------------------------

Recommended solution is to use block `core/router`, connected to
`core/config:routes`. Then all routes are defined in the `routes.json.php`.

The routes are defined in groups, each group has its default values and there
are also global defaults. Each group also has `require` options, so it can be
allowed only for selected virtual hosts (domain), protocol (http or https), or
user (by checking her permission to access given block).

When translating URL to a route, groups are checked and if group requirements
are fulfilled, routes within group are scanned. Scanning is done in the same
order as groups and routes are specified in config file. The first match is used.

Typical block connected to router is `core/value/block_loader`, which takes
block name from the router and inserts it into the cascade.


### Route matching

URL path is split on slashes into tokens. Each token is compared with route path. Route path can contain:

  - Literal token, which must be same as in matched URI. 
  - Named token (starts with `$`), wich mathes any token in URL. Value of named token is stored.
  - Anonymous token (`*`), which matches any token in URL.
  - Path tail (`**`), which mathces rest of the URL (one or more tokens).

If route is matched, the values of named tokens are merged with route values,
group default values and global default values. The result values are set on
router's outputs. Any further processing is up to blocks connected to the
router.

The path tail is available on `path_tail` output.


Route postprocessor
-------------------

The postprocessor is callable which receives matched rule and returns output
values. If postprocessor returns false, the current group is not matched and
router continues with the next group in configuration.

The postprocessor is specified by `postprocessor` option in group definition.
This option defines input from which is postprocessor loaded.

This mechanism allows injecting custom functionality to common router. The
postprocessors are expected to load data from database or scan filesystem, to
verify `/**` rule or something similar. <!--- */ -->


Reverse router -- URL generator
-------------------------------

Reverse router translates an entity reference to its URL.

@todo Define reverse router API.


