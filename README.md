# [Phalcon PHP 2.0.0](http://foundation.zurb.com) Dash docset


[Phalcon PHP 2.0.0](http://phalconphp.com/en/) documentation for Dash

This docset is to be used with [Dash for Mac](http://kapeli.com/dash) (Mac only App) or [Zeal docs]( http://zealdocs.org) (which is available for Windows and many Linux distros).

## Basic Installation

#### for Dash

If you plan on using it with **Dash**, just copy the [Phalcon docset](https://github.com/simioprg/dash-phalcon/releases/download/v1.0.2/Phalcon.zip) provided to '/Users/username/Library/Application Support/Dash/Docsets/'. Visit [Dash](http://kapeli.com/dash) website for more info.

#### for Zeal

If you're going to use it with **Zeal** you'll find where to store the [Phalcon docset](https://github.com/saimusdev/dash-phalcon/releases/download/2.0.0/Phalcon_2.docset.zip) in the application 'Options' menu. Visit [their Github page](https://github.com/jkozera/zeal) for more info. 

## Manual Build

The docset can be built by running the following commands in your terminal

```
git clone https://github.com/saimusdev/dash-phalcon.git
cd dash-phalcon
git co phalcon-2.0.0
./build.sh
cd ..
rm -rf dash-phalcon
```
