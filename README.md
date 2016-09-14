# Header By Taxonomy Plugin

The **Header By Taxonomy** Plugin is for [Grav CMS](http://github.com/getgrav/grav). The plugin allows you to inject headers into pages based on their taxonomy

## Installation

Installing the Header by Taxonomy plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install header-by-taxonomy

This will install the Header by Taxonomy plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/header-by-taxonomy`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `header-by-taxonomy`. You can find these files either on [GitHub](https://github.com/Perlkonig/grav-plugin-header-by-taxonomy) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/header-by-taxonomy
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav), the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) plugins, and a theme to be installed in order to operate.

## Usage

All you need to do is configure the plugin as described below. Everything else is automatic. Possible uses include the following:

  * Activate a plugin (for plugins that allow page-by-page activation, like [`pubmed`](https://github.com/Perlkonig/grav-plugin-pubmed))

  * Inject specific meta tags [via the `metadata` header](https://learn.getgrav.org/content/headers#meta-page-headers)

  * Inject [twig variables](https://learn.getgrav.org/content/headers#custom-page-headers)

## Configuration

The plugin comes with a sample configuration file. To change it, copy `header-by-taxonomy.yaml` to your `user/config/plugins` folder and edit as you see fit.

```
enabled: true
overwrite: false
sets:
  - criteria:
    - taxonomy: category
      values: [Food]
      combinator: or
    - taxonomy: tag
      values: [Indian]
      combinator: or
    combinator: and
    overwrite: true
    header:
      metadata.refresh: 300
      metadata.key2: value2
      pubmed.active: true
      test.var: "Hi!"
```

  * The `enabled` field allows you to turn the plugin off and on.

  * The `overwrite` field tells the plugin what to do overall if a header already exists in the page file itself. The default is `false`.

  * The `sets` field is where all the action happens. The plugin processes each set in sequence.

    * The `criteria` field is how the plugin determines whether the given page should be processed. It is an array of one or more conditions that must evaluate to `true` for the header to be modified.

      * `taxonomy` is the name of the taxonomy you wish to examine.

      * `values` lists the one or more values you're looking for in that taxonomy.

      * `combinator` tells the plugin how to assess the given `values`. The only options are `or` and `and`. Anything else (including if it is simply omitted) is interpreted as `or`.

    * The overall `combinator` field is only useful if you have more than one `criteria`. It determines how the results of the individual `criteria` should be assessed. The only options are `or` and `and`. Anything else (including if it is simply omitted) is interpreted as `or`.

    * This optional `overwrite` field can be used to override the global `overwrite` field for a specific set.

    * The `header` field is where you put the key-value pairs you wish to insert into the page. Use periods (`.`) to separate nested values.

## Performance

The results *are* cached, so the hit should be minimal.



