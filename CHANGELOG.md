# Changelog

## Unreleased

- Load the plugin's translations (`load_plugin_textdomain`) and the block editor's translations (`wp_set_script_translations`), so the card and its sidebar controls can be translated from a `languages/` folder.
- Show renewal, end and "member since" dates in the site's timezone (`wp_date()`). They were formatted with `date_i18n()` from UTC timestamps, so a date could appear a day early or late for sites far from UTC.
