#!/usr/bin/python3
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
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
# http://www.gnu.org/copyleft/gpl.html
#
# Further tweaks by Brion Vibber <brion@pobox.com>:
# 2006-01-26: Add command-line options for the various parameters
# 2007-02-19: Add --dirs param for hash subdirectory splits
# Tweaks by Greg Sabino Mullane <greg@turnstep.com>:
# 2008-01-06: Add regex check to skip words containing other than a-z

from optparse import OptionParser
import hashlib
import json
import math
import multiprocessing
import os
import random
import re
import sys
import subprocess

try:
    from PIL import Image, ImageDraw, ImageEnhance, ImageFont, ImageOps
except ImportError:
    sys.exit(
        "This script requires the Python Imaging Library - http://www.pythonware.com/products/pil/"
    )

# regex to test for suitability of words
nonalpha = re.compile("[^a-z]")

# when il beside each other, hard to read
confusedletters = re.compile(
    "[ijtlr][ijtl]|r[nompqr]|[il]"
)

# Pillow 9.2 added getbbox to replace getsize, and getsize() was removed in Pillow 10
# https://pillow.readthedocs.io/en/stable/releasenotes/10.0.0.html#font-size-and-offset-methods
# We don't have a requirements.txt, and therefore don't declare any specific supported or min version...
IMAGEFONT_HAS_GETBBOX = hasattr(ImageFont.ImageFont, "getbbox")


# Does X-axis wobbly copy, sandwiched between two rotates
def wobbly_copy(src, wob, col, scale, ang):
    x, y = src.size
    f = random.uniform(4 * scale, 5 * scale)
    p = random.uniform(0, math.pi * 2)
    rr = ang + random.uniform(-30, 30)  # vary, but not too much
    int_d = Image.new("RGB", src.size, 0)  # a black rectangle
    rot = src.rotate(rr, Image.BILINEAR)
    # Do a cheap bounding-box op here to try to limit work below
    bbx = rot.getbbox()
    if bbx is None:
        return src
    else:
        l, t, r, b = bbx
    # and only do lines with content on
    for i in range(t, b + 1):
        # Drop a scan line in
        xoff = int(math.sin(p + (i * f / y)) * wob)
        xoff += int(random.uniform(-wob * 0.5, wob * 0.5))
        int_d.paste(rot.crop((0, i, x, i + 1)), (xoff, i))
    # try to stop blurring from building up
    int_d = int_d.rotate(-rr, Image.BILINEAR)
    enh = ImageEnhance.Sharpness(int_d)
    return enh.enhance(2)


def gen_captcha(text, fontname, fontsize, file_name):
    """Generate a captcha image"""
    # white text on a black background
    bgcolor = 0x0
    fgcolor = 0xFFFFFF
    # create a font object
    font = ImageFont.truetype(fontname, fontsize)

    # determine dimensions of the text
    if IMAGEFONT_HAS_GETBBOX:
        dim = font.getbbox(text)[2:]
    else:
        dim = font.getsize(text)

    # create a new image significantly larger that the text
    edge = max(dim[0], dim[1]) + 2 * min(dim[0], dim[1])
    im = Image.new("RGB", (edge, edge), bgcolor)
    d = ImageDraw.Draw(im)
    x, y = im.size
    # add the text to the image
    # Using between 5-6 pixels of negative kerning seemed
    # enough to confuse tesseract but still be very readable
    offset = 0
    for c in text:
        d.text(
            (x / 2 - dim[0] / 2 + offset, y / 2 - dim[1] / 2 + random.uniform(-3, 7)),
            c,
            font=font,
            fill=fgcolor,
        )
        if IMAGEFONT_HAS_GETBBOX:
            offset += font.getbbox(c)[2:][0]
        else:
            offset += font.getsize(c)[0]

        offset -= random.uniform(5, 6)

    for i in range(10):
        x0 = int(
            offset * ((i / 2) - 1) / 5
            + x / 2
            - dim[0] / 2
            + random.uniform(0, 10)
        )
        y0 = int(y / 2 - dim[1] + 30 + random.uniform(-10, 15))

        x1 = int(offset * i / 7 + x / 2 - dim[0] / 2 + random.uniform(-5, 5))
        y1 = int(y / 2 - dim[1] + 30 + random.uniform(-10, 30))

        if x1 < x0:
            x0, x1 = x1, x0

        if y1 < y0:
            y0, y1 = y1, y0

        d.arc(
            (x0, y0, x1, y1),
            int(random.uniform(-30, 30)),
            int(random.uniform(160, 300)),
            fill=fgcolor,
        )

    # now get the bounding box of the nonzero parts of the image
    bbox = im.getbbox()
    bord = min(dim[0], dim[1]) / 4  # a bit of a border
    im = im.crop((bbox[0] - bord, bbox[1] - bord, bbox[2] + bord, bbox[3] + bord))

    # and turn into black on white
    im = ImageOps.invert(im)

    # save the image, in format determined from filename
    im.save(file_name)


def gen_subdir(basedir, md5hash, levels):
    """Generate a subdirectory path out of the first _levels_
    characters of _hash_, and ensure the directories exist
    under _basedir_."""
    subdir = None
    for i in range(0, levels):
        char = md5hash[i]
        if subdir:
            subdir = os.path.join(subdir, char)
        else:
            subdir = char
        fulldir = os.path.join(basedir, subdir)
        if not os.path.exists(fulldir):
            os.mkdir(fulldir)
    return subdir


def try_pick_word(words, badwordlist, verbose, nwords, min_length, max_length):
    if words is not None:
        word = words[random.randint(0, len(words) - 1)]
        while nwords > 1:
            word2 = words[random.randint(0, len(words) - 1)]
            word = word + word2
            nwords = nwords - 1
    else:
        word = ""
        max_length = max_length if max_length > 0 else 10
        for i in range(0, random.randint(min_length, max_length)):
            word = word + chr(97 + random.randint(0, 25))

    if verbose:
        print("word is %s" % word)

    if len(word) < min_length:
        if verbose:
            print(
                "skipping word pair '%s' because it has fewer than %d characters"
                % (word, min_length)
            )
        return None

    if max_length > 0 and len(word) > max_length:
        if verbose:
            print(
                "skipping word pair '%s' because it has more than %d characters"
                % (word, max_length)
            )
        return None

    if nonalpha.search(word):
        if verbose:
            print(
                "skipping word pair '%s' because it contains non-alphabetic characters"
                % word
            )
        return None
    if confusedletters.search(word):
        if verbose:
            print(
                "skipping word pair '%s' because it contains confusing letters beside each other"
                % word
            )
        return None

    for naughty in badwordlist:
        if naughty in word:
            if verbose:
                print(
                    "skipping word pair '%s' because it contains word '%s'"
                    % (word, naughty)
                )
            return None
    return word


def pick_word(words, badwordlist, verbose, nwords, min_length, max_length):
    for x in range(
        1000
    ):  # If we can't find a valid combination in 1000 tries, just give up
        word = try_pick_word(
            words, badwordlist, verbose, nwords, min_length, max_length
        )
        if word:
            return word
    sys.exit("Unable to find valid word combinations")


def read_wordlist(filename):
    filename = os.path.expanduser(filename)
    filename = os.path.expandvars(filename)
    if not os.path.isfile(filename):
        return []
    f = open(filename)
    words = [x.strip().lower() for x in f.readlines()]
    f.close()
    return words


def run_in_thread(object):
    count = object[0]
    words = object[1]
    badwordlist = object[2]
    opts = object[3]
    font = object[4]
    fontsize = object[5]
    jsonmap = object[6]

    for i in range(count):
        word = pick_word(
            words,
            badwordlist,
            opts.verbose,
            opts.number_words,
            opts.min_length,
            opts.max_length,
        )
        salt = "%08x" % random.randrange(2**32)
        # 64 bits of hash is plenty for this purpose
        md5hash = hashlib.md5(
            (opts.key + salt + word + opts.key + salt).encode("utf-8")
        ).hexdigest()[:16]
        filename = "image_%s_%s.png" % (salt, md5hash)
        if opts.dirs:
            subdir = gen_subdir(opts.output, md5hash, opts.dirs)
            filename = os.path.join(subdir, filename)
        if opts.verbose:
            print(filename)
        if opts.jsonmap:
            jsonmap[filename] = word

        gen_captcha(word, font, fontsize, os.path.join(opts.output, filename))


if __name__ == "__main__":
    """This grabs random words from the dictionary 'words' (one
    word per line) and generates a captcha image for each one,
    with a keyed salted hash of the correct answer in the filename.

    To check a reply, hash it in the same way with the same salt and
    secret key, then compare with the hash value given.
    """
    script_dir = os.path.dirname(os.path.realpath(__file__))
    parser = OptionParser()
    parser.add_option(
        "--wordlist",
        help="A list of words (required)",
        metavar="WORDS.txt"
    )
    parser.add_option(
        "--random",
        help="Use random characters instead of a wordlist",
        action="store_true",
    )
    parser.add_option(
        "--key",
        help="The passphrase set as $wgCaptchaSecret. "
        "Either --key or --php-key-file must be specified.",
        metavar="KEY",
        default="",
    )
    parser.add_option(
        "--php-key-file",
        help="A PHP file that contains the $wgCaptchaSecret variable. "
        "Either --key or --php-key-file must be specified.",
        metavar="FILE",
        default=None,
    )
    parser.add_option(
        "--output",
        help="The directory to put the images in - $wgCaptchaDirectory (required)",
        metavar="DIR",
    )
    parser.add_option(
        "--font",
        help="The font to use (required)",
        metavar="FONT.ttf"
    )
    parser.add_option(
        "--font-size",
        help="The font size (default 40)",
        metavar="N",
        type="int",
        default=40,
    )
    parser.add_option(
        "--count",
        help="The maximum number of images to make (default 20)",
        metavar="N",
        type="int",
        default=20,
    )
    parser.add_option(
        "--badwordlist",
        help="A list of words that should not be used",
        metavar="FILE",
        default=os.path.join(script_dir, "badwordlist"),
    )
    parser.add_option(
        "--fill",
        help="Fill the output directory to contain N files, overrides count, cannot be used with --dirs",
        metavar="N",
        type="int",
    )
    parser.add_option(
        "--dirs",
        help="Put the images into subdirectories N levels deep - $wgCaptchaDirectoryLevels",
        metavar="N",
        type="int",
    )
    parser.add_option(
        "--verbose",
        "-v",
        help="Show debugging information",
        action="store_true"
    )
    parser.add_option(
        "--number-words",
        help="Number of words from the wordlist which make a captcha challenge (default 2)",
        type="int",
        default=2,
    )
    parser.add_option(
        "--min-length",
        help="Minimum length for a captcha challenge",
        type="int",
        default=1,
    )
    parser.add_option(
        "--max-length",
        help="Maximum length for a captcha challenge",
        type="int",
        default=-1,
    )
    parser.add_option(
        "--threads",
        help="Maximum number of threads to be used to generate captchas",
        type="int",
        default=1,
    )
    parser.add_option(
        "--jsonmap",
        help="Outputs \"filename\": \"word\" mapping for test/debug purposes",
        action="store_true"
    )

    opts, args = parser.parse_args()

    if opts.wordlist:
        wordlist = opts.wordlist
    elif opts.random:
        wordlist = None
    else:
        sys.exit("Need to specify a wordlist")
    if not opts.key:
        # If the key is not specified, try to read it from a PHP file by including it
        # and echoing the value of $wgCaptchaSecret. This is useful in environments where
        # you might want to run this script outside of MediaWiki but still use the same key.
        if not opts.php_key_file or not os.path.isfile(opts.php_key_file):
            sys.exit("Need to specify a key or a php file with the key")
        inline_php = f"include '{opts.php_key_file}'; echo $wgCaptchaSecret;"
        opts.key = subprocess.run(["php", "-r", inline_php], capture_output=True, text=True, check=True).stdout.strip()

    if opts.output:
        if not os.path.exists(opts.output):
            try:
                os.makedirs(opts.output)
            except OSError:
                sys.exit("%s doesn't exist, and unable to create it" % opts.output)

        output = opts.output
    else:
        sys.exit("Need to specify an output directory")
    if opts.font and os.path.exists(opts.font):
        font = opts.font
    else:
        sys.exit("Need to specify the location of a font")

    badwordlist = read_wordlist(opts.badwordlist)

    if opts.verbose and not badwordlist:
        print("badwordlist is empty.")

    count = opts.count
    fill = opts.fill
    fontsize = opts.font_size
    threads = opts.threads

    if fill:
        count = max(0, fill - len(os.listdir(output)))

    if count == 0:
        sys.exit("No need to generate CAPTCHA images.")

    words = None
    if wordlist:
        words = read_wordlist(wordlist)

        if not words:
            sys.exit("No words were read from the wordlist")

        words = [
            x
            for x in words
            if len(x) in (4, 5) and x[0] != "f" and x[0] != x[1] and x[-1] != x[-2]
        ]

    if count < threads:
        chunks = 1
        threads = 1
    else:
        chunks = count // threads

    p = multiprocessing.Pool(threads)
    data = []
    print(
        "Generating %s CAPTCHA images separated in %s image(s) per chunk run by %s threads..."
        % (count, chunks, threads)
    )
    jsonmap = multiprocessing.Manager().dict()
    for i in range(0, threads):
        data.append([chunks, words, badwordlist, opts, font, fontsize, jsonmap])

    result = p.map_async(run_in_thread, data)
    result.wait()

    if opts.jsonmap:
        with open("map.json", "w") as outfile:
            json.dump(jsonmap.copy(), outfile, indent=4)

    print("Done!")
