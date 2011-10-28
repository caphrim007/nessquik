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

class Exclude:
	def __init__(self):
		h = NullHandler()
		self.logger = logging.getLogger('Exclude')
		self.logger.setLevel(logging.WARNING)
		self.logger.addHandler(h)
		self.format = 'json'
		self.include = []
		self.exclude = []
		self.enumerate = False

		# An IP is made of 4 bytes from x00 to xFF which is d0 to d255
		self.RE_IP_BYTE = '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])'
		self.RE_IP_ADD = self.RE_IP_BYTE + '\.' + self.RE_IP_BYTE + '\.' + self.RE_IP_BYTE + '\.' + self.RE_IP_BYTE

		# An IPv4 block is an IP address and a prefix (d1 to d32)
		self.RE_IP_PREFIX = '(3[0-2]|[12]?\d)'
		self.RE_IP_BLOCK = self.RE_IP_ADD + '\/' + self.RE_IP_PREFIX

		# For IPv6 canonicalization (NOT for strict validation; these are quite lax!)
		self.RE_IPV6_WORD = '([0-9A-Fa-f]{1,4})'
		self.RE_IPV6_GAP = ':(?:0+:)*(?::(?:0+:)*)?'
		self.RE_IPV6_V4_PREFIX ='0*' + self.RE_IPV6_GAP + '(?:ffff:)?'

		# An IPv6 block is an IP address and a prefix (d1 to d128)
		self.RE_IPV6_PREFIX ='(12[0-8]|1[01][0-9]|[1-9]?\d)'

		# An IPv6 IP is made up of 8 octets. However abbreviations like "::" can be used. This is lax!
		self.RE_IPV6_ADD = '(:(:' + self.RE_IPV6_WORD + '){1,7}|' + self.RE_IPV6_WORD + '(:{1,2}' + self.RE_IPV6_WORD + '|::$){1,7})'
		self.RE_IPV6_BLOCK = self.RE_IPV6_ADD + '\/' + self.RE_IPV6_PREFIX

		# This might be useful for regexps used elsewhere, matches any IPv6 or IPv6 address or network
		self.IP_ADDRESS_STRING = self.RE_IP_ADD + '(\/' + self.RE_IP_PREFIX + '|)|' + self.RE_IPV6_ADD + '(\/' + self.RE_IPV6_PREFIX + '|)'

	def usage(self, value):
		print ""
		print "exclude.py [--target=TARGETS] [--exclude=TARGETS]"
		print "    [--target-file=FILE] [--exclude-file=FILE]"
		print "    [--format=json|csv] [--debug|d] [--help|h]"
		print ""
		sys.exit(value)

	def addTarget(self, target):
		if self.isIpAddress(target):
			self.include.append(target)
		elif self.isCidr(target):
			self.include.append(target)
		elif self.isRange(target):
			self.include.append(target)

	def excludeTarget(self, target):
		if self.isIpAddress(target):
			self.exclude.append(target)
		elif self.isCidr(target):
			self.exclude.append(target)
		elif self.isRange(target):
			self.exclude.append(target)

	def isIpAddress(self, target):
		if self.isIPv4Address(target):
			return True
		elif self.isIPv6Address(target):
			return True
		else:
			return False

	def isIPv4Address(self, target):
		regex = re.compile('^' + self.RE_IP_ADD + '$', re.I)
		result = regex.match(target)
		if result:
			return True
		else:
			return False

	def isIPv6Address(self, target):
		regex = re.compile('^' + self.RE_IPV6_ADD + '$', re.I)
		result = regex.match(target)
		if result and target.count('::') < 2:
			return True
		else:
			return False

	def isCidr(self, target):
		regex = re.compile('^' + self.RE_IP_BLOCK + '|' + self.RE_IPV6_BLOCK + '$', re.I)
		result = regex.match(target)
		if result:
			return True
		else:
			return False

	def isRange(self, target):
		target = target.replace(' ','')
		parts = target.split('-')

		if len(parts) != 2:
			return False

		if not self.isIpAddress(parts[0]) or not self.isIpAddress(parts[1]):
			return False

		regex = re.compile('^' + self.RE_IP_ADD + '|' + self.RE_IPV6_ADD + '-' + self.RE_IP_ADD + '|' + self.RE_IPV6_ADD + '$', re.I)
		result = regex.match(target)
		if result:
			return True
		else:
			return False

	def run(self):
		results = []
		targets = netaddr.IPSet()

		for target in self.include:
			if self.isIpAddress(target):
				self.logger.debug("Including: %s" % target)
				targets.add(target)
			elif self.isCidr(target):
				self.logger.debug("Including: %s" % target)
				targets.add(target)
			elif self.isRange(target):
				target = target.replace(' ','')
				self.logger.debug("Including: %s" % target)
				parts = target.split('-')
				range = netaddr.IPRange(parts[0], parts[1])
				for cidr in range.cidrs():
					targets.add(cidr)

		for target in self.exclude:
			if target not in targets:
				#self.logger.debug("%s not found in target set", target)
				continue;

			if self.isIpAddress(target):
				self.logger.debug("Excluding %s" % target)
				targets.remove(target)
			elif self.isCidr(target):
				self.logger.debug("Excluding %s" % target)
				targets.remove(target)
			elif self.isRange(target):
				target = target.replace(' ','')
				self.logger.debug("Excluding %s" % target)
				parts = target.split('-')
				range = netaddr.IPRange(parts[0], parts[1])
				for cidr in range.cidrs():
					targets.remove(cidr)

		targets.compact()
		if self.enumerate is True:
			for target in targets:
				results.append(str(target))
		else:
			for target in targets.iter_cidrs():
				results.append(str(target))

		if self.format == 'json':
			print json.dumps(results)
		elif self.format == 'csv':
			print ','.join(results)

def main():
	exObj = Exclude()

	try:
		opts, args = getopt.getopt(sys.argv[1:], 'dh',
			['debug', 'enumerate', 'target=', 'target-file=', 'exclude=', 'exclude-file=', 'format=', 'help'] )
	except getopt.GetoptError, msg:
		# print help information and exit:
		print msg
		exObj.usage(2)

	for o, a in opts:
		if o in ('--target'):
			targets = a.split(',')
			for target in targets:
				exObj.addTarget(target)
		elif o in ('--target-file'):
			fh = open(a, 'r')
			for line in fh:
				exObj.addTarget(line.strip())
		elif o in ('--exclude'):
			excludes = a.split(',')
			for exclude in excludes:
				exObj.excludeTarget(exclude)
		elif o in ('--exclude-file'):
			fh = open(a, 'r')
			for line in fh:
				exObj.excludeTarget(line.strip())
		elif o in ('--enumerate'):
			exObj.enumerate = True
		elif o in ('--debug'):
			ch = logging.StreamHandler()
			formatter = logging.Formatter("%(asctime)s %(name)s [%(levelname)s] %(message)s", "%Y-%m-%dT%H:%M:%S")
			ch.setFormatter(formatter)
			exObj.logger.setLevel(logging.DEBUG)
			exObj.logger.addHandler(ch)
			exObj.logger.debug('Starting debugging of exclude.py')
		elif o in ('--format'):
			exObj.format = a
		elif o in ('--help', '-h'):
			exObj.usage(1)

	exObj.run()

if __name__ == "__main__":
	main()
