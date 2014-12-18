#!/bin/bash

# Interface Setup Script
#
# Author: Russell Toris - rctoris@wpi.edu

echo "  ____    _    ____  _     "
echo " / ___|  / \  |  _ \| |    "
echo "| |     / _ \ | |_) | |    "
echo "| |___ / ___ \|  _ <| |___ "
echo " \____/_/   \_\_| \_\_____|"
echo

echo
echo "Carl Demo Interface Setup"
echo "Author: Russell Toris - rctoris@wpi.edu"
echo

# check the directory we are working in
DIR=`pwd`
if [[ $DIR != *CarlDemoInterface ]]
then
	echo "ERROR: Please run this script in the 'CarlDemoInterface' directory."
	exit;
fi

RMS="/var/www/rms"
if [ ! -d "$RMS" ]; then
	echo "ERROR: No RMS installation found in '$RMS'."
	exit;
fi

echo "Copying 'app' scripts to local RMS directory..."
cp app/Controller/*.php $RMS/Controller
cp -r app/View/* $RMS/View
cp -r app/webroot/img/* $RMS/webroot/img

echo "Installation complete!"
echo