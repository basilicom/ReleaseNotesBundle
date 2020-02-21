#!/bin/bash

# todo => actual version tag needs to become a variable! ($1)
# @todo use this script as a possibility to get the tag before a specific tag
#
# Author: Alexander.Heidrich
#ACTUAL_VERSION_TAG=$1
#PREVIOUS_VERSION_TAG=
#
#TAG_LIST=($(git tag -l | sort -V))
#length=${#TAG_LIST[@]}
#
#for (( i = 0; i < length; i++ )); do
#  if [[ "${TAG_LIST[$i]}" == "${ACTUAL_VERSION_TAG}" ]]; then
#    if [[ "$i" -gt "0" ]]; then
#      PREVIOUS_VERSION_TAG=${TAG_LIST[$i - 1]} # the tag in the tag list just before the current, actual tag
#    else
#      PREVIOUS_VERSION_TAG=$(git rev-list --max-parents=0 HEAD) # first commit
#    fi
#  fi
#done
#
#echo $(git log --pretty="format:%s" "$PREVIOUS_VERSION_TAG".."$ACTUAL_VERSION_TAG")

#get tag of actual version
ACTUAL_VERSION_TAG=$(git tag | tail -1)

#get tag of the version before actual version
PREVIOUS_VERSION_TAG=$(git tag | tail -3 | head -n 1)

#echo $listOfTickets
git log --pretty="format:%s" "$PREVIOUS_VERSION_TAG".."$ACTUAL_VERSION_TAG"
