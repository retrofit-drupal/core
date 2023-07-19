# Retrofit for Drupal

The Retrofit provides compatibility layers for legacy Drupal code to allow run on any version of Drupal.

## Installation

```shell
composer require mglaman/retrofit-drupal
```

And that's it! 🎉

## How it works

This library registers a service provider to integrate with Drupal automatically.

## Usage

Currently, the compatibility layers are drop-in replacements. This means you can use them in your code without any 
changes. Some functions are namespaced for compatibility.

### Modules

You must modify your Drupal 7 modules `.info` file to `info.yml`.

* Add `type: module`
* Add `core_version_requirement: >= 10`

### Themes

You must modify your Drupal 7 theme's `.info` file into a `info.yml` format.

* Add `type: theme`
* Add `core_version_requirement: >= 10`

You must also rename `template.php` to `THEME_NAME.theme`, the next extension file format.

You must create an asset library for your previously declared `stylesheets` and add a `libraries` definition
to attach those to the page. In the future, this may be automated with [#26](https://github.com/mglaman/retrofit-drupal/issues/26).

### Namespaced functions

* `module_load_include` is now `Retrofit\Drupal\module_load_include`

### Fixing placeholders in `t()`

The `!` placeholder is no longer valid. Placeholders using `!` in `t()` need to be manually changed to `@` or `:`.
