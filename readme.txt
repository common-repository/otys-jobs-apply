=== OTYS Plugin ===
Contributors: otysplugin
Tags: ats, otys, recruiting, recruitment
Requires at least: 5.1
Tested up to: 6.6
Stable tag: 2.0.56
License: GPLv2 or later
License URI: [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)

The OTYS Plugin makes your Wordpress website a proper recruiting website integrated with OTYS. The integration makes sure every step of the process is automated and no extra work has to be done. Vacancies are automaticly pushed to your website without any hassle.

== Description ==
The OTYS Plugin makes your Wordpress website a proper recruiting website integrated with OTYS. The integration makes sure every step of the process is automated and no extra work has to be done. Vacancies are automaticly pushed to your website without any hassle.

What is included:
- Vacancies list
- Vacancies filters
- Vacancies keyword search in combination with filters
- Vacancies geo search based on postal code
- Vacancies show selected filters
- Vacancy Detail
- Vacancy Application using dynamic OTYS questionsets
- Open applications using OTYS dynamic questionsets
- Mail notifications
- OTYS Candidate login
- OTYS Candidate forgot password

== README ==
For more information please see our knowledge base https://wordpress.otys.com/.

== Changelog ==

== 2.0.56 ==
- External apply url's are now automatically placed on the vacancy detail page if available
- Added possibility exclude premium vacancies from vacancies list / shortlist

== 2.0.55 ==
- Added automatic redirecting of old OTYS vacancies url's containing underscores
- Improved german translation
- Other small improvements

== 2.0.54 ==
- Fixed warning showing for new customers when settings are not yet done for vacancy urls

== 2.0.52 ==
- Added trailing slashes to url's where they were missing

== 2.0.51 ==
- Fixed procedure motivation not being added to the procedure

= 2.0.50 ==
- Fixed warning on settings pages when recaptcha is not filled

== 2.0.49 ==
- Added the ability to choose a vacancy field to be used a meta description in the settings
- Added support for custom page title for vacanies (SEO Widget in Go! in vacancies module)
- Missing vacancy detail label setting is now shown in vacancy detail tab in settings
- Procedure UTM tags are now also allowed to have dots in them

== 2.0.48 ==
- Added support for mobile questionsets
- Added geo location to selected filters
- Dots are now allowed in UTM values

== 2.0.47 ==
- Added Candidate login (https://wordpress.otys.com/kb/guide/en/candidate-login-1bnikrmOII/Steps/3746121)
- Added Candidate forgot password (https://wordpress.otys.com/kb/guide/en/candidate-logout-jrn7fIlHr4/Steps/3747400)
- Added Applying as logged in candidate with pre filled data and known candidate questionset
- Added Optional login link to application form when candidate is not logged in
- Added link to candidate portal when candidate is logged in
- Added multi brand support for Google for Jobs
- When using slug system the vacancy preview url is now available from Go
- Fixed issue with mutliselect extra fields in questionset
- Fixed issue with vacancy view counter not working
- Code improvements

== 2.0.46 ==
- Fixed a preview mode bug for single brand otys environments

== 2.0.45 ==
- Version bump

== 2.0.44 ==
- HotFix for a bug resulting out of 2.0.43, as this caused single websites to have issues on vacancy detail

== 2.0.43 ==
- Bug fix for single website that use the custom slug system.

== 2.0.42 ==
- Added webhook for change vacancy slug structure
- Improved WCAG autocomplete
- Improved page caching logic to prevent excessive caching
- Candidate owner will not change anymore when candidate applies for the second time using the same e-mail address
- Minor bug fixes

== 2.0.41 ==
- Added support for WPML plugin (translation plugin)

== 2.0.40 ==
- Added possibility to add portals based on UTM Tags (see our WordPress knowledge base FAQ)

== 2.0.39 ==
- Added rest form event (For more information see https://app.stonly.com/app/guide/FQV642JGzj/editor/3613946)
- Created fallback for UTM params being saved in cookies if there is no session
- Created fallback for Portal being saved in cookies if there is no session

== 2.0.38 ==
- Hotfix for caching issue causing error
- Fixed issue with cache not refreshing sometimes
- Fixed status not being set when a new candidate applies

== 2.0.37 ==
- Fixed issue with cache not refreshing sometimes
- Fixed status not being set when a new candidate applies

== 2.0.36 ==
- Small bug fixes

== 2.0.35 ==
- Made it possible to add filter attributes to the shortlist and vacancies even if they are not in the user filters
- Document type can now be selected in the questionset and is used when uploading documents
- Small bug fixes
- When custom slugs are enabled the url is communicated back to OTYS when a website is marked as live. This makes it so custom url's are communicated to third party platforms and are shows in OTYS Go.

== 2.0.34 ==
- Email validation is now forced no matter what is defined in the questionset, this to prevent misconfiguration

== 2.0.33 ==
- Fixed GDPR small difference in end date

== 2.0.32 ==
- Made it possible to change the thank you page in the OTYS settings menu OTYS -> Settings -> Urls
- Fixed warning displaying when removing API key
- It's now required to choose a brand, the all option has been removed
- For creating a shortlist based on the relation the relation uid is now used instead of the relation refernece number
- [otys-vacancies-list] and [otys-vacancies-shortlist] now support the search attribute
- [functie_o] gets now replaced with the vacancy title for vacancy textfield titles
- Bugfix for customer rights level

== 2.0.31 ==
- Document right levels are now based on the questionset
- Added vacancy apply url to vacancy list $args

== 2.0.30 ==
- Hotfix for warning displaying while uploading document

== 2.0.29 ==
- Added sitemap xml [yourwebsite]/otys-sitemap
- Prepared support for custom slug system
- Prepared support for communicating WordPress urls to OTYS
- Fix old vacancy redirect not working for some scenarios

== 2.0.28 ==
- Added more information to vacancy list & detail response (otys urls)

== 2.0.27 ==
- Fixed issue with postal code search not working in some scenarios

== 2.0.26 ==
- Added salary min and max field to vacancy detail and vacancy list result

== 2.0.25 ==
- Hotfix vacancies url redirect potential undefined array key

== 2.0.24 ==
- Added auto redirecting old OTYS vacancies url's to correct detail url page
- Added the exclude parameter to [otys-vacancies-shortlist] which allows for excluding a vacancy based on uid

== 2.0.0 ==
*Warning*: Significant changes have been made, please read the changelog. When updating to version 2.0.0 the application form look & feel (templates) will be reset to the default view. Version 2.0.0 does not use the same application form templates as previous versions. Make sure to let your developer read the DEV Notes.

- Read changes https://stonly.com/guide/en/update-2-0-0-5398epXxe5/Steps/

Changelog from previous versions is available in the plugin folder changelog.txt