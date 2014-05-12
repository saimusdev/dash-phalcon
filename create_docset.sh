#!/bin/bash --
DOCSET_NAME="Phalcon"
DOCUMENTATION_SRC="http://docs.phalconphp.com/en/latest/"

# CREATE THE DOCSET FOLDER...
echo -e "$(tput setaf 2)--> Creating the folder structure$(tput sgr0)"
test -d "${DOCSET_NAME}.docset/Contents/Resources"  && rm -rf "${DOCSET_NAME}.docset/Contents/Resources" 2>/dev/null >&2
mkdir -p "${DOCSET_NAME}.docset/Contents/Resources/Documents"
cp icon.tiff "${DOCSET_NAME}.docset/"

# DOWNLOAD THE DOCSET...
echo -e "$(tput setaf 2)--> Downloading the documentation of '$DOCSET_NAME'$(tput sgr0)"
wget --recursive --page-requisites --html-extension --convert-links \
     --restrict-file-names=windows  \
     --domains phalconphp.com --no-parent $DOCUMENTATION_SRC 2>&1 | egrep -i "%|Saving to"

cp -r phalcon-php-framework-documentation-latest/* "${DOCSET_NAME}.docset/Contents/Resources/Documents/"
mv phalcon-php-framework-documentation-latest/* "${DOCSET_NAME}.docset/Contents/Resources/Documents/"
rm -rf phalcon-php-framework-documentation-latest

# CREATE PROPERTY LIST...
echo -e "$(tput setaf 2)--> Creating the Property List...$(tput sgr0)"
cat > "${DOCSET_NAME}.docset/Contents/Info.plist" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>CFBundleIdentifier</key>
	<string>$DOCSET_NAME</string>
	<key>CFBundleName</key>
	<string>$DOCSET_NAME</string>
	<key>DocSetPlatformFamily</key>
	<string>$DOCSET_NAME</string>
	<key>isDashDocset</key>
	<true/>
	<key>dashIndexFilePath</key>
	<string>index.html</string>
	<key>DashDocSetFamily</key>
	<string>dashtoc</string>
	<key>isJavaScriptEnabled</key>
	<true/>

</dict>
</plist>
EOF

# PARSE & CLEAN THE HTML DOCUMENTATION. FILL THE DB...
echo -e "$(tput setaf 2)--> Parsing the documentation...$(tput sgr0)"
php phalcon_parser.php ${DOCSET_NAME}.docset ${DOCSET_NAME}.docset/Contents/Resources/Documents
echo -e "$(tput setaf 2)--> Finished parsing!$(tput sgr0)"

# OPEN THE DOCSET
if [ -d "$HOME/Library/Application Support/Dash/Docsets" ]; then
	echo -e "$(tput setaf 2)--> Moving the docset into $HOME/Library/Application Support/Dash/Docsets/$DOCSET_NAM$(tput sgr0)"
	mkdir -p "$HOME/Library/Application Support/Dash/Docsets/$DOCSET_NAME"
	#mv -f "${DOCSET_NAME}.docset" "$HOME/Library/Application Support/Dash/Docsets/$DOCSET_NAME/"
	cp -r "${DOCSET_NAME}.docset" "$HOME/Library/Application Support/Dash/Docsets/$DOCSET_NAME/"
	open -a "/Applications/Dash.app" $HOME/Library/Application\ Support/Dash/Docsets/${DOCSET_NAME}/${DOCSET_NAME}.docset
fi
echo -e "$(tput setaf 2)--> FINISHED!$(tput sgr0)"
echo The docset should have been added to Dash.
echo If not, copy \'${DOCSET_NAME}.docset\' into \'$HOME/Library/Application Support/Dash/Docsets/$DOCSET_NAME\'