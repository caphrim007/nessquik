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
import logging
import json
import re
import os

try:
	import netaddr
except ImportError, msg:
	# If netaddr is not installed on the system, this will
	# try to import it using the locally available copy in
	# nessquik
	BASE_PATH = reduce (lambda l,r: l + os.path.sep + r, os.path.dirname( os.path.realpath( __file__ ) ).split( os.path.sep )[:-1] )
	sys.path.append( os.path.join( BASE_PATH, "opt/python" ) )
	import netaddr

class NullHandler(logging.Handler):
	def emit(self, record):
		pass

class InetContains:
	def __init__(self):
		h = NullHandler()
		self.logger = logging.getLogger('Exclude')
		self.logger.setLevel(logging.WARNING)
		self.logger.addHandler(h)
		self.format = 'json'
		self.target = []
		self.ipSet = netaddr.IPSet()

	def usage(self, value):
		print ""
		print "inet-contains.py [--target=TARGETS] [--within=TARGETS]"
		print "    [--within-file=FILE] [--format=json|csv]"
		print "    [--debug|d] [--help|h]"
		print ""
		sys.exit(value)

	def setTarget(self, target):
		ipSet = netaddr.IPSet()
		try:
			if target.find('-') >= 0:
				parts = target.split('-')
				range = netaddr.IPRange(parts[0], parts[1])
				for cidr in range.cidrs():
					ipSet.add(cidr)				
					self.target.append(cidr)
			else:
				ipSet.add(target)
				self.target.append(target)
		except netaddr.core.AddrFormatError:
			return false

	def addWithin(self, target):
		try:
			if target.find('-') >= 0:
				parts = target.split('-')
				range = netaddr.IPRange(parts[0], parts[1])
				for cidr in range.cidrs():
					self.ipSet.add(cidr)				
			else:
				self.ipSet.add(target)
		except netaddr.core.AddrFormatError:
			return false

	def run(self):
		results = []

		if self.target is None:
			raise Exception('The provided target cannot be empty')

		self.ipSet.compact()

		for target in self.target:
			if target in self.ipSet:
				results = True
				break
			else:
				results = False;

		print results

def main():
	obj = InetContains()

	try:
		opts, args = getopt.getopt(sys.argv[1:], 'dh',
			['debug', 'target=', 'within=', 'within-file=', 'format=', 'help'] )
	except getopt.GetoptError, msg:
		# print help information and exit:
		print msg
		obj.usage(2)

	for o, a in opts:
		if o in ('--target'):
			obj.setTarget(a)
		elif o in ('--within'):
			withins = a.split(',')
			for within in withins:
				obj.addWithin(within)
		elif o in ('--within-file'):
			fh = open(a, 'r')
			for line in fh:
				obj.addWithin(line.strip())
		elif o in ('--debug'):
			ch = logging.StreamHandler()
			formatter = logging.Formatter("%(asctime)s %(name)s [%(levelname)s] %(message)s", "%Y-%m-%dT%H:%M:%S")
			ch.setFormatter(formatter)
			obj.logger.setLevel(logging.DEBUG)
			obj.logger.addHandler(ch)
			obj.logger.debug('Starting debugging of inet-contains.py')
		elif o in ('--format'):
			obj.format = a
		elif o in ('--help', '-h'):
			obj.usage(1)

	obj.run()

if __name__ == "__main__":
	main()
