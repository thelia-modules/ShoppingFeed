# Shopping Feed

Send your catalog to different channel like Amazon, Metro, C-Discount,... through ShoppingFeed API.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is ShoppingFeed.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/shopping-feed-module ~2.0.0
```

## Usage

This module uses [ShoppingFeed API](https://developer.shopping-feed.com/getting-started/introduction).

Set your different feeds in the configuration menu. 
A feed is a combination of a catalog and a channel (Amazon, Metro,..). You can select products from your thelia catalog in the feed edition page.
