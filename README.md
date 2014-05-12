# [Phalcon PHP](http://foundation.zurb.com) Dash docset


[Phalcon PHP](http://phalconphp.com/en/) documentation for Dash

This docset is to be used with [Dash for Mac](http://kapeli.com/dash),
or [Zeal docs]( http://zealdocs.org) (which is available for Windows and many
Linux distros).

Based on the version [dash-phalcon](https://github.com/omdevin/dash-phalcon) by **omdevin**.


## Basic Installation

Just copy the [Phalcon Docset](https://github.com/simioprg/dash-phalcon/releases/download/1.0/Phalcon.zip) provided to '/Users/username/Library/Application Support/Dash/Docsets/'.

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

Because the script provided downloads the full documentation recursively from the [Phalcon PHP](http://phalconphp.com/en/)  website, it takes some time to download all the files. At least 4-6 minutes will be necessary. Please have patience.
