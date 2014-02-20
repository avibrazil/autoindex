#!/usr/bin/python

import os
import urllib
from datetime import *
from stat import *

root="/media/Media/Musica"
rootAlias="/mus"
base="http://digitalk7.com"

mindate=date(2013,10,7) # auto-titles implemented
mindate=date(2013,12,22) # initial microdata for audio track implemented
mindate=date(2014,2,5) # further microdata, popovers and no more obvious "?play"

def sitemapURL(base,fileName):
	st = os.stat(fileName)
	theDate=date.fromtimestamp(st[ST_CTIME])
	if (theDate < mindate):
		theDate=mindate
#	theDate=date.today()
	urlPath=urllib.pathname2url(fileName.replace(root,rootAlias)) #.replace('%20',' ')

	print("	<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>monthly</changefreq></url>"
		% (base + urlPath, theDate))



def genSitemap():
	print("<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n")

	for r,d,f in os.walk(root):
		for relevant in d:
			full=os.path.join(r,relevant)
			#print(full + "\n");
			sitemapURL(base,full)
			
	print("</urlset>")
	
genSitemap()
