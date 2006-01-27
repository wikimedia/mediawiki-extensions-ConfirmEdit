#!/usr/bin/python
#
# Script to generate distorted text images for a captcha system.
#
# Copyright (C) 2005 Neil Harris
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
# http://www.gnu.org/copyleft/gpl.html
#
# Further tweaks by Brion Vibber <brion@pobox.com>:
# 2006-01-26: Add command-line options for the various parameters

import random
import Image
import ImageFont
import ImageDraw
import ImageEnhance
import ImageOps
import math, string, md5
import getopt
import os
import sys

# Does X-axis wobbly copy, sandwiched between two rotates
def wobbly_copy(src, wob, col, scale, ang):
	x, y = src.size
	f = random.uniform(4*scale, 5*scale)
	p = random.uniform(0, math.pi*2)
	rr = ang+random.uniform(-30, 30) # vary, but not too much
	int_d = Image.new('RGB', src.size, 0) # a black rectangle
	rot = src.rotate(rr, Image.BILINEAR)
	# Do a cheap bounding-box op here to try to limit work below
	bbx = rot.getbbox()
	if bbx == None:
		print "whoops"
		return src
	else:
		l, t, r, b= bbx
	# and only do lines with content on
	for i in range(t, b+1):
		# Drop a scan line in
		xoff = int(math.sin(p+(i*f/y))*wob)
		xoff += int(random.uniform(-wob*0.5, wob*0.5))
		int_d.paste(rot.crop((0, i, x, i+1)), (xoff, i))
	# try to stop blurring from building up
	int_d = int_d.rotate(-rr, Image.BILINEAR)
	enh = ImageEnhance.Sharpness(int_d)
	return enh.enhance(2)


def gen_captcha(text, fontname, fontsize, file_name):
	"""Generate a captcha image"""
	# white text on a black background
	bgcolor = 0x0
	fgcolor = 0xffffff
	# create a font object 
	font = ImageFont.truetype(fontname,fontsize)
	# determine dimensions of the text
	dim = font.getsize(text)
	# create a new image significantly larger that the text
	edge = max(dim[0], dim[1]) + 2*min(dim[0], dim[1])
	im = Image.new('RGB', (edge, edge), bgcolor)
	d = ImageDraw.Draw(im)
	x, y = im.size
	# add the text to the image
	d.text((x/2-dim[0]/2, y/2-dim[1]/2), text, font=font, fill=fgcolor)
	k = 3
	wob = 0.20*dim[1]/k
	rot = 45
	# Apply lots of small stirring operations, rather than a few large ones
	# in order to get some uniformity of treatment, whilst
	# maintaining randomness
	for i in range(k):
		im = wobbly_copy(im, wob, bgcolor, i*2+3, rot+0)
		im = wobbly_copy(im, wob, bgcolor, i*2+1, rot+45)
		im = wobbly_copy(im, wob, bgcolor, i*2+2, rot+90)
		rot += 30
	
	# now get the bounding box of the nonzero parts of the image
	bbox = im.getbbox()
	bord = min(dim[0], dim[1])/4 # a bit of a border
	im = im.crop((bbox[0]-bord, bbox[1]-bord, bbox[2]+bord, bbox[3]+bord))
	# and turn into black on white
	im = ImageOps.invert(im)
		
	# save the image, in format determined from filename
	im.save(file_name)

if __name__ == '__main__':
	"""This grabs random words from the dictionary 'words' (one
	word per line) and generates a captcha image for each one,
	with a keyed salted hash of the correct answer in the filename.
	
	To check a reply, hash it in the same way with the same salt and
	secret key, then compare with the hash value given.
	"""
	font = "VeraBd.ttf"
	wordlist = "awordlist.txt"
	key = "CHANGE_THIS_SECRET!"
	output = "."
	count = 20
	fill = 0
	verbose = False
	
	opts, args = getopt.getopt(sys.argv[1:], "", ["font=", "wordlist=", "key=", "output=", "count=", "fill=", "verbose"])
	for o, a in opts:
		if o == "--font":
			font = a
		if o == "--wordlist":
			wordlist = a
		if o == "--key":
			key = a
		if o == "--output":
			output = a
		if o == "--count":
			count = int(a)
		if o == "--fill":
			fill = int(a)
		if o == "--verbose":
			verbose = True
	
	if fill:
		# Option processing order is not guaranteed, so count the output
		# files after...
		count = max(0, fill - len(os.listdir(output)))
	
	words = [string.lower(x.strip()) for x in open(wordlist).readlines()]
	words = [x for x in words
		if len(x) <= 5 and len(x) >= 4 and x[0] != "f"
		and x[0] != x[1] and x[-1] != x[-2]
		and (not "'" in x)]
	for i in range(count):
		word1 = words[random.randint(0,len(words)-1)]
		word2 = words[random.randint(0,len(words)-1)]
		word = word1+word2
		salt = "%08x" % random.randrange(2**32)
		# 64 bits of hash is plenty for this purpose
		hash = md5.new(key+salt+word+key+salt).hexdigest()[:16]
		filename = "image_%s_%s.png" % (salt, hash)
		if verbose:
			print filename
		gen_captcha(word, font, 40, os.path.join(output, filename))
