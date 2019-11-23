# ProtectionIndicator
ProtectionIndicator is a personal project of mine where I try and create an extension over the span of a week, that adds a protection icon to each and every protected page. As of now, it only adds a black OOUI lock icon with a title explaining why the lock icon is there. 

Among the things that need to be done are
* ~~Add support for customisation of the icon using javascript. I'll probably try to make this into a MediaWiki space on-wiki file thingy so that it can be manipulated per the wishes of the community.~~**(Done, not with JS but CSS.)**
* Add support for FlaggedRevs without going insane.
* ~~Convert the title into a OOUI popup and add some helpful links to it.~~**(Done)**
* Add a magic word to supress locks on a page.**(Doing)**
* ~~Try and add the latest log entry to the popup (the hard part) regulated by a cofig variable.~~**(Added the comment made by the admin when protecting page, should be good enough)**
* Write tests...

**Note :** This extension is based on extension BoilerPlate. The authorship of most of the testing and linting rules are not mine but rather are the result of the contributions of volunteers of the Wikimedia movement. Please refer to [the commit log of the BoilerPlate extension](https://github.com/wikimedia/mediawiki-extensions-BoilerPlate) for attribution.

## Installation/Testing
* Git clone this repository into the ```extensions/``` directory of your mediawiki installation. 
* Add ```wfLoadExtension( 'ProtectionIndicator' )``` to your ```LocalSetting.php``` file.
* Navigate to a protected page on your wiki and check if the icons work.
* Add css to ```MediaWiki:ProtectionIndicatorCustom.css``` to customise the lock icons per your taste. (The class names for the icons go like ```protection-indicator-<action>```.)