=== SaveTheDate for Gravity Forms ===
Contributors: kelexlabs
Tags: gravity forms, add to calendar, ics, event registration, google calendar
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Google, Outlook, Microsoft 365, Yahoo and .ics calendar links to Gravity Forms event confirmations and emails.

== Description ==

SaveTheDate adds calendar links for one fixed event per Gravity Forms form.
An active, licensed copy of Gravity Forms 2.7 or newer is required. Gravity Forms is a separate commercial plugin.

* Local event dates and times, timezone, all-day events, location, plain-text description and event URL.
* Google Calendar, Outlook.com, Microsoft 365, Yahoo Calendar and .ics downloads for Apple Calendar and Outlook desktop.
* Automatic calendar buttons in Text confirmations, with AJAX and regular submissions.
* The {save_the_date} merge tag in confirmations and notifications, including plain-text email.
* The [savethedate form="12"] shortcode for public event pages.
* Solid and Outline buttons, accent color and a horizontal layout that wraps on small screens.
* Theme template overrides, translation support, RTL layout and keyboard focus styles.

= Public events and external services =

All enabled event details and .ics downloads are public, without requiring a submission or login. Do not configure private details.
This plugin sends no external requests or entry data. Clicking a calendar-provider link opens that provider and transfers the configured event title, dates, location, description and URL in the link. Provider use is subject to that provider's terms and privacy policy:

* Google: https://policies.google.com/terms and https://policies.google.com/privacy
* Microsoft: https://www.microsoft.com/servicesagreement and https://privacy.microsoft.com/privacystatement
* Yahoo: https://legal.yahoo.com/us/en/yahoo/terms/otos/index.html and https://legal.yahoo.com/us/en/yahoo/privacy/index.html

.ics downloads are served by your WordPress site at /wp-json/savethedate/v1/ics/form/{form_id}.
Missing, inactive, trashed, disabled, invalid and ended events return 404. No submission data is included.

= Timezones and caching =

Enter local times in the selected event timezone. Timed calendar output uses UTC and applies daylight saving rules. All-day end dates include the last day in the editor; calendar output uses the following exclusive date. Nonexistent DST times are rejected. Repeated autumn DST times use PHP timezone rules.

Downloads advertise caching for at most five minutes, shortened to the remaining event lifetime. Exclude /wp-json/savethedate/* from longer CDN and page caches. Cached pages can show old buttons until their page cache expires.

== Installation ==

1. Install and activate your licensed Gravity Forms 2.7+ copy on WordPress 6.2+ with PHP 7.4+.
2. Upload the plugin ZIP under Plugins > Add New > Upload Plugin and activate it.
3. Open a form's Settings > SaveTheDate and enable "This form is an event".
4. Enter future start and end dates, times and timezone, then save. For all-day events, set the inclusive last day.
5. Submit the form. Buttons appear in its Text confirmation by default. Insert {save_the_date} into a notification message for email links.

Global style and cleanup settings are under Forms > Settings > SaveTheDate.
A sample form export is included in examples/sample-form.json. Update its event dates after importing it.

== Frequently Asked Questions ==

= Does this work without Gravity Forms? =
No. Gravity Forms is a required commercial dependency and is not bundled.

= Why are the buttons missing? =
Check that the form is active and not trashed, event settings are enabled, the range is valid and the event has not ended. Auto-append applies only to Text confirmations. Redirect confirmations can use the shortcode on their destination page.

= Can I display the links before registration? =
Yes. Use [savethedate form="12"] with your form ID. Event details and downloads are public.

= Can I use an email notification? =
Insert {save_the_date} in its message. HTML notifications use a simple table of links; text notifications use labeled URLs. No attachments are added.

= Can my theme customize the buttons? =
Copy templates/buttons.php into your theme as savethedate/buttons.php. Keep escaping and accessible labels. Custom CSS can target stdgf-buttons and stdgf-buttons__item.

= Are settings deleted on uninstall? =
No, by default. Opt in under Forms > Settings > SaveTheDate. Cleanup preserves forms and entries, skips deletion if the standalone companion edition is installed, and preserves settings if Gravity Forms is unavailable. Multisite cleanup respects each site's opt-in.

== Screenshots ==

1. Calendar buttons in a real event registration confirmation.
2. Native Gravity Forms event settings, with local dates and timezone.
3. Global Solid/Outline style, accent color and opt-in cleanup settings.

== Changelog ==

= 1.0.0 =
* Initial Free implementation with fixed events, calendar links, confirmations, email merge tags and public .ics downloads.

== Upgrade Notice ==

= 1.0.0 =
First stable version. Review public event details and timezone settings before enabling an event.
