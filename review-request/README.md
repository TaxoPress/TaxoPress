# WP-Product-In-Dash-Review-Requests
Library to handle dashboard review requests for WordPress plugins &amp; themes. Responsible for over 1000+ 5 star reviews for Popup Maker on wordpress.org


This class can be customized to quickly add a review request system.

It includes:
- Multiple trigger groups which can be ordered by priority.
- Multiple triggers per group.
- Customizable messaging per trigger.
- Link to review page.
- Request reviews on a per user basis rather than per site.
- Allows each user to dismiss it until later or permanently seamlessly via AJAX.
- Integrates with attached tracking server to keep anonymous records of each triggers effectiveness.
  - Tracking Server API: https://gist.github.com/danieliser/0d997532e023c46d38e1bdfd50f38801

To use this please include the following credit block as well as completing the following TODOS.

 * Original Author: danieliser
 * Original Author URL: https://danieliser.com

- TODO Search & Replace prefix_ with your prefix
- TODO Search & Replace Prefix_ with your prefix
- TODO Search & Replace 'text-domain' with your 'text-domain'
- TODO Change the $api_url if your using the accompanying tracking server. Leave it blank to disable this feature.
- TODO Modify the ::triggers function array with your custom triggers & text.
- TODO Keep in mind highest priority group/code combination that has all passing conditions will be chosen.
