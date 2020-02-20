#!/bin/bash

#get tag of actual version
ACTUAL_VERSION_TAG=$(git tag | tail -1)

#get tag of the version before actual version
PREVIOUS_VERSION_TAG=$(git tag | tail -3 | head -n 1)

#echo $listOfTickets
git log --pretty="format:%s" "$PREVIOUS_VERSION_TAG".."$ACTUAL_VERSION_TAG"
