# [Phalcon PHP](http://foundation.zurb.com) Dash docset

**NOT WORKING YET. SOON WILL UPDATE**

[Phalcon PHP](http://phalconphp.com/en/) documentation for Dash

This docset is to be used with [Dash for Mac](http://kapeli.com/dash).

Based on the repo [dash-phalcon](https://github.com/omdevin/dash-phalcon) by omdevin.


## Basic Installation
Just copy the [Phalcon Docset](https://github.com/simioprg/dash-phalcon/blob/master/Phalcon.docset) provided to '/Users/myUserName/Library/Application Support/Dash/Docsets/**Phalcon**', but before create the **Phalcon** folder if it doesn't already exist.

## Manual Build

Basically, execute the following commands:

```
git clone https://github.com/simioprg/dash-phalcon.git
cd dash-foundation
./create_docset.sh
cd ..
rm -rf dash-foundation
```

## Notes

Because the script provided downloads the documentation recursively from the website,
it takes some time to download all the files. At least 4-6 minutes, so have patience.
