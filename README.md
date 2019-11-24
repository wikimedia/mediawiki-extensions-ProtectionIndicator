# ProtectionIndicator
ProtectionIndicator is a personal project of mine where I try and create an extension over the span of a week, that adds a protection icon to each and every protected page. By default, it only adds a black OOUI lock icon with a popup that explaining why the lock icon is there.

**Note :** This extension is based on extension BoilerPlate. The authorship of most of the testing and linting rules are not mine but rather are the result of the contributions of volunteers of the Wikimedia movement. Please refer to [the commit log of the BoilerPlate extension](https://github.com/wikimedia/mediawiki-extensions-BoilerPlate) for attribution.

## Installation/Testing
* Git clone this repository into the ```extensions/``` directory of your mediawiki installation. 
* Add ```wfLoadExtension( 'ProtectionIndicator' )``` to your ```LocalSetting.php``` file.
* Navigate to a protected page on your wiki and check if the icons work.
* Add css to ```MediaWiki:ProtectionIndicatorCustom.css``` to customise the lock icons per your taste. (The class names for the icons go like ```protection-indicator-<cascading>-<level>-<action>```.)
## Configurational variables
* ```$wgShowIconsOnMainPage``` Set to be false by default.
* ```$ShowReasonInPopup``` Set to true by default.
## Development
* Install mediawiki-docker-dev and get an instance running
* Follow the instructions for installation.
* Start modifying the files :)
