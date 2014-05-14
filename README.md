# [Phalcon PHP](http://foundation.zurb.com) Dash docset


[Phalcon PHP](http://phalconphp.com/en/) documentation for Dash

This docset is to be used with [Dash for Mac](http://kapeli.com/dash), or [Zeal docs]( http://zealdocs.org) (which is available for Windows and many Linux distros).

Based on the version [dash-phalcon](https://github.com/omdevin/dash-phalcon) by **omdevin**.


## Basic Installation

* If you plan on using it with **Dash**, just copy the [Phalcon docset](https://github.com/simioprg/dash-phalcon/releases/download/v1.0.2/Phalcon.zip) provided to '/Users/username/Library/Application Support/Dash/Docsets/'. [Dash for Mac](http://kapeli.com/dash) for more info.
* If you're going to use it with **Zeal** you'll find where to store the [Phalcon docset](https://github.com/simioprg/dash-phalcon/releases/download/1.0/Phalcon.zip) in the application 'Options' menu. Visit [their Github page](https://github.com/jkozera/zeal) for more info. 


## Manual Build

Basically, execute the following commands:

```
git clone https://github.com/simioprg/dash-phalcon.git
cd dash-foundation
./create_docset.sh
cd ..
rm -rf dash-foundation
```

### Notes

Because the script provided downloads the full documentation recursively from the [Phalcon PHP](http://phalconphp.com/en/)  website, it takes some time to download all the files. At least 5-15 minutes will be necessary. It depends on the connection speed.

## Known Issues

* The guides aren't encoded/decoded properly. See also [#2](/../../issues/2)
* A "¶" symbol appears on every class, method, constant or guide. It appears to be concatenated when the documentation is parsed. See also [#3](/../../issues/3)
