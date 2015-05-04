#!/bin/bash --
echo "Make sure you have $(tput setaf 3)libxml2$(tput sgr0) and $(tput setaf 3)libxslt$(tput sgr0) installed first before running this script!"
ruby -v >/dev/null 2>&1 || { echo "$(tput setaf 1)You need 'ruby' to install this docset!$(tput sgr0)"; exit 1; }
test `gem list -i bundle` != "true" && { echo "$(tput setaf 1)You need the ruby gem 'bundle' to install this docset!$(tput sgr0)"; exit 1; }
echo "$(tput setaf 2)Installing necessary Ruby gems$(tput sgr0)"
mkdir -p vendor/bundle && \ 
bundle install --path vendor/bundle && \
rake && \
bundle clean --force >/dev/null && \
rm -rf vendor/bundle