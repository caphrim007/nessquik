#!/usr/bin/python
# vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent
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

class Range2Cidr:
	def __init__(self):
		h = NullHandler()
		self.logger = logging.getLogger('Exclude')
		self.logger.setLevel(logging.WARNING)
		self.logger.addHandler(h)

		self.start = None
		self.end = None

		self.ipSet = netaddr.IPSet()

	def usage(self, value):
		print ""
		print "range2cidr.py [--start=TARGET] [--end=TARGET] [--help|h]"
		print ""
		sys.exit(value)

	def setStart(self, target):
		self.start = target

	def setEnd(self, target):
		self.end = target

	def run(self):
		results = []

		if self.start is None:
			raise Exception('The provided start address cannot be empty')

		if self.end is None:
			raise Exception('The provided end address cannot be empty')

		try:
			range = netaddr.IPRange(self.start, self.end)
			for subnet in range.cidrs():
				results.append(str(subnet))

			print json.dumps(results)
		except netaddr.core.AddrFormatError:
			print json.dumps([])

def main():
	obj = Range2Cidr()

	try:
		opts, args = getopt.getopt(sys.argv[1:], 'dh',
			['debug', 'start=', 'end=', 'help'] )
	except getopt.GetoptError, msg:
		# print help information and exit:
		print msg
		obj.usage(2)

	for o, a in opts:
		if o in ('--start'):
			obj.setStart(a)
		elif o in ('--end'):
			obj.setEnd(a)
		elif o in ('--debug'):
			ch = logging.StreamHandler()
			formatter = logging.Formatter("%(asctime)s %(name)s [%(levelname)s] %(message)s", "%Y-%m-%dT%H:%M:%S")
			ch.setFormatter(formatter)
			obj.logger.setLevel(logging.DEBUG)
			obj.logger.addHandler(ch)
			obj.logger.debug('Starting debugging of inet-contains.py')
		elif o in ('--help', '-h'):
			obj.usage(1)

	obj.run()

if __name__ == "__main__":
	main()
