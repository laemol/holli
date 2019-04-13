# Holli API WordPress plugin 

This is a very simple plugin to get products from the Holli API

## Setup 

 * Place the holli folder in in `wp-content/plugins/` opr upload in Wordpress plugin section
 * Activate the plugin in WordPress > Plugins > Holli > Activate
 * Go to Settings > Holli and set up your API key (from your backend profile)

## Usage

You can use the shortcode `[products]` to display Holli products  

For example: `[products area=86 limit=8 button=“More” color="#3CCD5B"]`

## Options

`limit` Sets the number of products that will be displayed. Default value is 4.

`area` Display products in a certain area. Default all areas are available. A list of areas is available in the Holli API settings page.

`recommended` Shows only recommended products in random order if set to 1. Default is 0.

`button` Sets the text on the button. Default value is "Buy Now".

`lang` Sets the language. Default value is EN.

## Support

For bugs and problems please create a Github issue.

## Changelog

1.3.1 - Fixed price. Add responsive grid and add background color option  
1.3.0 - Changed domain and API version, fixed cache issues   
1.2.0 - Added caching  
1.1.0 - Added support for iframed content  
1.0.0 - Initial version
