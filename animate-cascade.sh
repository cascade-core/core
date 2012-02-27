#!/bin/sh
#
# animate-cascade.sh
# 
# Copyright (C) 2010  Josef Kufner <jk@frozen-doe.net>
# 
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# 


if [ "$1" = "" ]
then
	echo "Usage: $0 md5_hash_of_cascade" >&2
	echo "Note: Files must be in current directory." >&2
	exit 1
fi

# args
hash="$1"

# input
last="cascade-$hash.dot"
mask="cascade-$hash.*.dot.gz"

# output
dir="cascade-$hash"
avi="$dir/cascade-$hash.avi"

if ! [ -f "$last" ]
then
	cd ../data/graphviz
	echo -n 'Current working directory: '
	pwd
fi

if ! [ -f "$last" ]
then
	echo "Error: \"$last\" not found" >&2
	exit 1
fi


frame_count=`find . -type f -name "$mask" | wc -l`

if [ "$frame_count" = 0 ]
then
	echo "Error: Zero frames to animate. Is 'debug.animate_cascade' in core.ini.php enabled ?" >&2
	exit 1
fi

set -e

# target dir
[ -d "$dir" ] || mkdir "$dir"

# other frames
find . -type f -name "$mask" \
| sort -n \
| while read f
  do
	if ! [ -f "$dir/$f.png" ] || [ "$f" -nt "$dir/$f.png" ]
	then
		echo Drawing $f ... >&2
		gunzip -c "$f" | dot -Tpng -Gbgcolor=white -o "$dir/$f".png
	else
		echo Skipping $f - already done. >&2
	fi
  done

# last frame
if ! [ -f "$dir/$last.png" ] || [ "$last" -nt "$dir/$last.png" ]
then
	echo Drawing $last ... >&2
	dot -Tpng -Gbgcolor=white "$last" -o "$dir/$last".png
else
	echo Skipping $last - already done. >&2
fi

# build movie
echo Creating video $avi ... >&2
mencoder -mf 'fps=3:type=png' -o "$avi" -ovc lavc -lavcopts vcodec=mpeg4:autoaspect=1:keyint=1 \
	"mf://$dir/$mask.png" \
	"mf://$dir/$last.png" "mf://$dir/$last.png" "mf://$dir/$last.png" "mf://$dir/$last.png" "mf://$dir/$last.png" \
	"mf://$dir/$last.png" "mf://$dir/$last.png" "mf://$dir/$last.png" "mf://$dir/$last.png" "mf://$dir/$last.png"

echo >&2
echo Video saved in `pwd`/$avi >&2

