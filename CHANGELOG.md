# Changelog

## Unreleased

- Load the plugin's translations (`load_plugin_textdomain`) and the block editor's translations (`wp_set_script_translations`), so the card and its sidebar controls can be translated from a `languages/` folder.
- Show renewal, end and "member since" dates in the site's timezone (`wp_date()`). They were formatted with `date_i18n()` from UTC timestamps, so a date could appear a day early or late for sites far from UTC.
- Show "Membership" or "Course" when the underlying product or course has been deleted or has no title, instead of an empty row (and an unlabelled button for screen readers).
- Add the `member_snapshot_ending_soon_days` filter so sites can change the 14-day window for the "ending soon" status.
