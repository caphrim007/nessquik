#!/usr/bin/python
# vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent
#
# This file can accept a list of IP addresses and exclude
# addresses in a variety of formats and print out the
# subsequent list of parsed IP addresses in a couple
# different formats.
#
import sys
import time
import getopt

def usage(value):
	print ""
	print "split-response.py [--response-file=FILE]"
	print ""
	sys.exit(value)

def main():
	try:
		opts, args = getopt.getopt(sys.argv[1:], 'h',
			['response-file=', 'help'] )
	except getopt.GetoptError, msg:
		# print help information and exit:
		print msg
		exObj.usage(2)

	for o, a in opts:
		if o in ('--response-file'):
			fh = open(a, 'r')
			for line in fh:
				results = line.split('&')
				results.sort()
				print "\n".join(results)
		elif o in ('--help', '-h'):
			usage(1)


if __name__ == "__main__":
	main()
