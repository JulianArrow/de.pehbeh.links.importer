#!/bin/sh

var=$(pwd)
PACKAGE_NAME="$(basename $PWD)"
VERSION="$(xmlstarlet sel -N my=https://www.woltlab.com -t -v "my:package/my:packageinformation/my:version" package.xml | tr '[:upper:]' '[:lower:]' | tr ' ' '-')"

test -e $PACKAGE_NAME.$VERSION.tar.gz && rm $PACKAGE_NAME.$VERSION.tar.gz
export COPYFILE_DISABLE=true
#test -e requirements && echo "\nBuilding requirements\n-------------------------" && mkdir requirements
test -e acptemplates && echo "\nBuilding acptemplates.tar\n-------------------------" && cd acptemplates && tar cvf ../acptemplates.tar * && cd ..
test -e files && echo "\nBuilding files.tar\n------------------" && cd files && tar cvf ../files.tar --exclude ._lib --exclude .DS_Store * && cd ..
test -e files_wcf && echo "\nBuilding files_wcf.tar\n------------------" && cd files_wcf && tar cvf ../files_wcf.tar --exclude ._lib --exclude .DS_Store * && cd ..
test -e templates && echo "\nBuilding templates.tar\n----------------------" && cd templates && tar cvf ../templates.tar * && cd ..
echo "\nBuilding $PACKAGE_NAME archive"
for i in `seq 1 ${#PACKAGE_NAME}`;
do
printf "-"
done
printf "\n"
export COPYFILE_DISABLE=true
tar --exclude=composer* --exclude=package*json --exclude=.* --exclude=*.sh --exclude=tsconfig.json --exclude=ts --exclude=acptemplates --exclude=files_wcf --exclude=files --exclude=templates --exclude=nbproject --exclude=.DS_Store --exclude=._lib --exclude=README* --exclude=.gitignore --exclude=*.tar.gz --exclude=LICENSE* --exclude=c --exclude=z --exclude=v -czvf ../$PACKAGE_NAME.$VERSION.tar.gz *
test -e acptemplates.tar && rm acptemplates.tar
test -e files.tar && rm files.tar
test -e files_wcf.tar && rm files_wcf.tar
test -e templates.tar && rm templates.tar
exit 0