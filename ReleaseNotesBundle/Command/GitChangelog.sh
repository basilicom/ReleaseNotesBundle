#!/bin/bash

#get tag of actual version
actualVersionTag=$(git tag | tail -1)
actualVersionHash=$(git rev-list -n 1 $actualVersionTag)

#get tag of the version before actual version
beforeVersionTag=$(git tag | tail -3 | head -n 1)
beforeVersionHash=$(git rev-list -n 1 $beforeVersionTag)

#echo $listOfTickets
git log --pretty="format:%s" $beforeVersionHash..$actualVersionHash