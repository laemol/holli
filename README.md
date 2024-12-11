# Holli API WordPress plugin 

This is a plugin to get products from the Holli API https://tickets-tours.com

## Setup 

 * Place the holli folder in in `wp-content/plugins/` opr upload in Wordpress plugin section
 * Activate the plugin in WordPress > Plugins > Holli > Activate
 * Go to Settings > Holli and set up your API key (from your backend profile)

## Usage

You can use the shortcode `[products]` to display Holli products  

For example: `[products area=86 limit=8 button=“More” color="#3CCD5B"]`

If you would like to use the shortcode multiple times, you need to give it a unique id. If you do not do this all shortcoded will return the same result.  

For example: `[products id=1]` and `[products id=2]`

## Cache

Results from the API are cached for a day. To clear the cache deactivate and activate the plugin or give the shorcode a (new) unique id.

## Options
`id` Gives the shortcode a unique id. Default value is 41

`limit` Sets the number of products that will be displayed. Default value is 4.

`area` Display products in a certain area. Default all areas are available. A list of areas is available in the Holli API settings page.

`recommended` Shows only recommended products in random order if set to 1. Default is 0.

`button` Sets the text on the button. Default value is "Buy Now".

`lang` Sets the language. Default value is EN.

## Support

For bugs and problems please create a Github issue.

## Changelog

1.6.4 - Add debugging option
1.6.3 - Bump update version  
1.6.2 - Increased timeout to fix limit bug  
1.6.1 - Fix updater  
1.6.0 - Extra parameter to add specific products with example shortcode  
1.5.1 - Add update checker  
1.5.0 - Changed product url to new format  
1.4.0 - Changed domain name to tickets-tours.com  
1.3.6 - Add cat attribute to filter by category  
1.3.5 - Set image height  
1.3.4 - Clear cache when changing shortcode parameters  
1.3.3 - Add id attribute so shortcode can be used multiple times. Clear cache on plugin reactivate.  
1.3.2 - Add cache busting for the stylesheet  
1.3.1 - Fixed price. Add responsive grid and add background color option  
1.3.0 - Changed domain and API version, fixed cache issues   
1.2.0 - Added caching  
1.1.0 - Added support for iframed content  
1.0.0 - Initial version
