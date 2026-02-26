 ______     ______   __     ______     __  __     __  __
/\  ___\   /\__  _\ /\ \   /\  ___\   /\ \/ /    /\ \_\ \
\ \___  \  \/_/\ \/ \ \ \  \ \ \____  \ \  _"-.  \ \____ \
 \/\_____\    \ \_\  \ \_\  \ \_____\  \ \_\ \_\  \/\_____\
  \/_____/     \/_/   \/_/   \/_____/   \/_/\/_/   \/_____/

## SUMMARY

Creating a sticky elements like a header, a footer or another element in your website has never been easier.

The Sticky module implements the Sticky JS library
(http://stickyjs.com/)

## MANUAL INSTALLATION

1. Install Sticky in the usual way
   (https://www.drupal.org/node/1897420)
2. Download the Sticky library (version 1.0.4: https://github.com/garand/sticky),
rename the folder to sticky and place it in /libraries/
in your drupal root.
3. Clear the cache
4. Go to Administration > Configuration > System > Sticky
   (/admin/config/system/sticky)
5. Change the DOM selector to the element you want to make sticky
6. Optionally change the javascript settings
7. Click "Save configuration"

Now visit your website, the selected item should now be sticky!

## INSTALLATION VIA COMPOSER

1. It is assumed you are installing Drupal with Composer (https://www.drupal.org/download).

2. Add the following entry in the "repositories" section of your main composer.json file.

{
    "type": "package",
    "package": {
        "name": "garand/sticky",
        "version": "1.0.3",
        "type": "drupal-library",
        "source": {
            "url": "https://github.com/garand/sticky.git",
            "type": "git",
            "reference": "1.0.3"
        }
    }
},

Now you can run the following command to install chosen in the right folder:

composer require garand/sticky

3. Clear the cache
4. Go to Administration > Configuration > System > Sticky
   (/admin/config/system/sticky)
5. Change the DOM selector to the element you want to make sticky
6. Optionally change the javascript settings
7. Click "Save configuration"

-- MAINTAINERS --

Fons Vandamme - https://www.drupal.org/u/fons-vandamme
