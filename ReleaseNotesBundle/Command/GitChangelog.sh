#!/bin/bash

ACTUAL_VERSION_TAG=$1
PREVIOUS_VERSION_TAG=

TAG_LIST=($(git tag -l))
length=${#TAG_LIST[@]}

for (( i = 0; i < length; i++ )); do
  if [[ "${TAG_LIST[$i]}" == "${ACTUAL_VERSION_TAG}" ]]; then
    if [[ "$i" -gt "0" ]]; then
      PREVIOUS_VERSION_TAG=${TAG_LIST[$i - 1]} # the tag in the tag list just before the current, actual tag
    else
      PREVIOUS_VERSION_TAG=$(git rev-list --max-parents=0 HEAD) # first commit
    fi
  fi
done

git log --pretty="format:%s" "$PREVIOUS_VERSION_TAG".."$ACTUAL_VERSION_TAG"
