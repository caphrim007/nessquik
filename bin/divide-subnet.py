#!/usr/bin/python
# vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent
#
# This file can accept a list of IP networks and print
# out the enumerated list of subnets 
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

class DivideSubnet:
	def __init__(self):
		h = NullHandler()
		self.logger = logging.getLogger('Exclude')
		self.logger.setLevel(logging.WARNING)
		self.logger.addHandler(h)
		self.format = 'json'
		self.include = []
		self.subnet = 24

		# An IP is made of 4 bytes from x00 to xFF which is d0 to d255
		self.RE_IP_BYTE = '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])'
		self.RE_IP_ADD = self.RE_IP_BYTE + '\.' + self.RE_IP_BYTE + '\.' + self.RE_IP_BYTE + '\.' + self.RE_IP_BYTE

		# An IPv4 block is an IP address and a prefix (d1 to d32)
		self.RE_IP_PREFIX = '(3[0-2]|[12]?\d)'
		self.RE_IP_BLOCK = self.RE_IP_ADD + '\/' + self.RE_IP_PREFIX

		# For IPv6 canonicalization (NOT for strict validation; these are quite lax!)
		self.RE_IPV6_WORD = '([0-9A-Fa-f]{1,4})'

		# An IPv6 block is an IP address and a prefix (d1 to d128)
		self.RE_IPV6_PREFIX ='(12[0-8]|1[01][0-9]|[1-9]?\d)'

		# An IPv6 IP is made up of 8 octets. However abbreviations like "::" can be used. This is lax!
		self.RE_IPV6_ADD = '(:(:' + self.RE_IPV6_WORD + '){1,7}|' + self.RE_IPV6_WORD + '(:{1,2}' + self.RE_IPV6_WORD + '|::$){1,7})'
		self.RE_IPV6_BLOCK = self.RE_IPV6_ADD + '\/' + self.RE_IPV6_PREFIX

	def usage(self, value):
		print ""
		print "exclude.py [--target=TARGETS] [--subnet=SUBNET_MASK]"
		print "    [--format=json|csv] [--debug|d] [--help|h]"
		print ""
		sys.exit(value)

	def addTarget(self, target):
		if self.isCidr(target):
			self.target = target
		else:
			raise Exception('The provided target must be in CIDR format')

	def isCidr(self, target):
		regex = re.compile('^' + self.RE_IP_BLOCK + '|' + self.RE_IPV6_BLOCK + '$', re.I)
		result = regex.match(target)
		if result:
			return True
		else:
			return False

	def run(self):
		results = []
		targets = netaddr.IPNetwork(self.target)
		subnets = list(targets.subnet(self.subnet))

		for subnet in subnets:
			results.append(str(subnet))

		if self.format == 'json':
			print json.dumps(results)
		elif self.format == 'csv':
			print ','.join(results)

def main():
	obj = DivideSubnet()

	try:
		opts, args = getopt.getopt(sys.argv[1:], 'dh',
			['debug', 'target=', 'format=', 'subnet=', 'help'] )
	except getopt.GetoptError, msg:
		# print help information and exit:
		print msg
		obj.usage(2)

	for o, a in opts:
		if o in ('--target'):
			targets = a.split(',')
			for target in targets:
				obj.addTarget(target)
		elif o in ('--debug'):
			ch = logging.StreamHandler()
			formatter = logging.Formatter("%(asctime)s %(name)s [%(levelname)s] %(message)s", "%Y-%m-%dT%H:%M:%S")
			ch.setFormatter(formatter)
			obj.logger.setLevel(logging.DEBUG)
			obj.logger.addHandler(ch)
			obj.logger.debug('Starting debugging of enum-network.py')
		elif o in ('--format'):
			obj.format = a
		elif o in ('--subnet'):
			obj.subnet = int(a)
		elif o in ('--help', '-h'):
			obj.usage(1)

	obj.run()

if __name__ == "__main__":
	main()
