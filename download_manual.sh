#!/bin/bash
#
# download_manual.sh
# 
# Copyright (C) 2012  Josef Kufner <jk@frozen-doe.net>
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

if [ -z "$1" ]
then
	echo "Usage: $0 servername" >&2
	exit 1
fi


server="$1"

prefix="http://$server"
url="$prefix/documentation/everything.tex"


set -e

[ -d "manual" ] || mkdir "manual"

cd "manual"

wget -nv -O "manual.tex" "$url"

sed -n '/^\\includegraphics{\/.*}$/s/.*{\/data\/\(.*\)}.*/\1/p' manual.tex \
| while read f
do
	d=`dirname "$f"`
	if ! [ -z "$d" ] && ! [ -d "$d" ]
	then
		mkdir -p -- "$d"
	fi

	wget -nv -O "$f" "$prefix/data/$f"
done

sed -i manual.tex -e '/^\\includegraphics{\/.*}$/s/{\/data\/\(.*\)}/{\1}/'

pdflatex manual.tex


