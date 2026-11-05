=== SaveTheDate for Gravity Forms ===
Contributors: kelexlabs
Tags: gravity forms, add to calendar, ics, event registration, google calendar
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Google, Outlook, Microsoft 365, Yahoo and .ics calendar links to Gravity Forms event confirmations and emails.

== Description ==

= From registration to “see you there.” =

Help attendees save your next webinar, workshop, class, or meetup to the calendar they already use.

**SaveTheDate for Gravity Forms** adds calendar buttons to Text confirmations, links to notification emails, and a shortcode for public event pages. Set up one fixed event per form and let visitors choose **Google Calendar, Outlook.com, Microsoft 365, Yahoo Calendar, or an .ics download** for Apple Calendar and Outlook desktop.

Attendees review and save the event themselves. No calendar account connection is required.

**By Kelex Labs** · [Visit kelex-labs.com](https://kelex-labs.com/)

![Register with Gravity Forms, see calendar links, and choose a calendar.](https://ps.w.org/savethedate-for-gravity-forms/assets/description-workflow.png)

= Free: a simple next step for shared events =

* **One fixed event per form** — title, dates, timezone, location, description, and event URL.
* **Timed or all-day events** — timed output follows the selected timezone and daylight saving rules.
* **Confirmation buttons** — automatic output in Text confirmations, with regular and AJAX submissions.
* **Email links** — insert `{save_the_date}` in notification messages; HTML and plain-text emails are supported.
* **Public event pages** — use `[savethedate form="12"]` with your form ID, including on redirect-confirmation pages.
* **Your look** — Solid or Outline buttons, an accent color, a wrapping layout, and theme template overrides.
* **Accessible presentation** — keyboard focus styles, translation support, and RTL layout.

![Five calendar options for confirmations, notification emails, and public event pages.](https://ps.w.org/savethedate-for-gravity-forms/assets/description-features.png)

= SaveTheDate Pro =

**More flexibility for registrations with individual dates and richer calendar delivery.** SaveTheDate Pro, our premium edition, is built for workflows that go beyond a shared event.

Pro features include:

* **Events from submitted form fields** — map Date and Time fields for entry-specific events, with optional event-detail fields.
* **Protected entry .ics downloads** — expiring, revocable links for entry-specific calendars. Anyone with a forwarded link can access it until it expires or is revoked.
* **Calendar attachments in email** — add .ics files to selected Gravity Forms notification emails.
* **More styles and layouts** — Pill and Minimal styles, plus vertical and dropdown layouts alongside Solid, Outline, and horizontal output.
* **Calendar provider controls** — choose which providers to display and their order.
* **Organizer details and calendar alarms** — include organizer information and DISPLAY alarms in .ics files; support varies by calendar app. These are not reminder emails.
* **More ways to place your links** — a calendar block for public fixed events, individual provider URL merge tags, and plain-text link output.
* **Kelex Sunstone included** — one dashboard to install, update, roll back and license all your Kelex Labs plugins.

These features are not included in this free plugin. [Compare Free and Pro, and upgrade, on kelex-labs.com](https://kelex-labs.com/plugins/savethedate/). No purchase or subscription is required to use the free features above.

= Requirements =

Requires an active, licensed copy of **Gravity Forms 2.7+**, sold separately, plus WordPress 6.2+ and PHP 7.4+. SaveTheDate is an independent Kelex Labs add-on, not affiliated with Gravity Forms or the calendar providers.

= Public events and privacy =

**Free event details and .ics downloads are public**, without requiring a submission or login. Do not configure private information.

The plugin makes no automatic external requests and sends no form entry data to providers. Clicking a provider link shares the configured event title, dates, location, description, and URL with that provider. .ics files are served by your WordPress site and contain no submission data.

* Google: [Terms](https://policies.google.com/terms) · [Privacy](https://policies.google.com/privacy)
* Microsoft: [Terms](https://www.microsoft.com/servicesagreement) · [Privacy](https://privacy.microsoft.com/privacystatement)
* Yahoo: [Terms](https://legal.yahoo.com/us/en/yahoo/terms/otos/index.html) · [Privacy](https://legal.yahoo.com/us/en/yahoo/privacy/index.html)

== Installation ==

1. Activate your licensed Gravity Forms 2.7+ copy, then install and activate SaveTheDate.
2. Open your form's Settings > SaveTheDate and enable "This form is an event".
3. Add future event dates, times, timezone, and other details, then save. For all-day events, select the inclusive last day.
4. Submit the form and check its Text confirmation. Add `{save_the_date}` to notification messages or `[savethedate form="12"]` to a public page as needed.

Global button styles, accent color, and cleanup preferences are under Forms > Settings > SaveTheDate.

== Frequently Asked Questions ==

= Is Gravity Forms included? =
No. Gravity Forms is a separate commercial plugin and is required.

= What is the difference between Free and Pro? =
Free covers one fixed, shared event per form, calendar links, confirmation buttons, and a public shortcode. SaveTheDate Pro adds entry-based events, protected entry downloads, email attachments, and more presentation options. See the [full comparison](https://kelex-labs.com/plugins/savethedate/).

= Why aren't my calendar buttons showing? =
Check that the form is active, event settings are enabled, dates are valid, and the event has not ended. Automatic output applies to Text confirmations. For a redirect confirmation, use the shortcode on the destination page.

= Does Free attach calendar files or synchronize saved events? =
No. Free adds email links; attendees choose and save or import events themselves. It does not send calendar invitations or synchronize later changes. Email .ics attachments are available in SaveTheDate Pro.

= Are Free downloads restricted to registered attendees? =
No. Free event details and downloads are public. Protected entry-specific downloads are available in SaveTheDate Pro.

= What about timezones, caching, and cleanup? =
Enter local times in the selected timezone and avoid ambiguous daylight saving times. Exclude `/wp-json/savethedate/*` from long-lived caches and clear page caches after event changes. Settings are preserved on uninstall unless you opt into cleanup; forms and entries are preserved.

== Screenshots ==

1. Real calendar buttons in a Gravity Forms confirmation, shown in a presentation frame.
2. Real event settings for a shared fixed event, including dates, timezone, location, and description. Admin navigation is cropped from the capture.
3. Real global settings for button style, accent color, and opt-in cleanup. Admin navigation is cropped from the capture.

== Changelog ==

= 1.0.5 =
* Security updates and optimizations.

= 1.0.4 =
* The missing-requirements notice now appears only on the Plugins screen and explains how to fix it.

= 1.0.3 =
* Confirmations, emails and the shortcode now use the same calendar links and button settings.

= 1.0.2 =
* Improved calendar file compatibility for event updates.

= 1.0.1 =
* Internal improvements to how events and calendar links are prepared.

= 1.0.0 =
* Initial release with fixed events, calendar links, confirmations, email merge tags and public .ics downloads.

== Upgrade Notice ==

= 1.0.0 =
First stable version. Review public event details and timezone settings before enabling an event.
