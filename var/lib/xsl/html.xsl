<?xml version='1.0'?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<!--
 (C) 2009 - 2010, Tenable Network Security, Inc.

 @ReadableName@ = "HTML export"
 @Extension@  = "html"
!-->

<xsl:output method="html" 
    media-type="text/html" 
    doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
    doctype-system="DTD/xhtml1-strict.dtd"
    cdata-section-elements="script style"
    indent="yes"
    encoding="ISO-8859-1"/>
<xsl:strip-space elements="port" />
<xsl:template match="/">

<html> 
<head> 
<title>Nessus Scan Report</title> 
<style type="text/css" media="all"> 
BODY {BACKGROUND-COLOR: #2a4d66; font-family: tahoma,helvetica,sans-serif; font-size: 13px}
A {TEXT-DECORATION: none}
A {COLOR: #333; FONT-FAMILY: tahoma,helvetica,sans-serif, font-size: 13px}
A:link {COLOR: #333; FONT-FAMILY: tahoma,helvetica,sans-serif; TEXT-DECORATION:underline;font-size:13px}
A:active {COLOR: #333; FONT-FAMILY: tahoma,helvetica,sans-serif; TEXT-DECORATION:underline;font-size:13px}
a:hover {color: #000; font-family: tahoma,helvetica,sans-serif; text-decoration:none;font-size:13px}
TD {COLOR: #333; FONT-FAMILY: tahoma,helvetica,sans-serif; FONT-SIZE:13px; word-wrap:break-word;}
TR {COLOR: #333; FONT-FAMILY: tahoma,helvetica,sans-serif; FONT-SIZE:13px}
.even {background-color: #FFF;}
.odd {background-color: #DCDCDC;}
.sev_low {color: #397AB2}
.sev_med {color: #FDBE00}
.sev_high {color: red}
.ip_sev_low {color:#397AB2;font-weight:bold;font-size:1.5em;padding:3px}
.ip_sev_med {color:#FDBE00;font-weight:bold;font-size:1.5em;padding:3px}
.ip_sev_high {color:red;font-weight:bold;font-size:1.5em;padding:3px}
.hostlist {color:#FFF;font-size:2em;font-weight:bold;padding:3px}
.backTo a {color:#FFF;font-family:tahoma,helvetica,sans-serif;text-decoration:underline}
.backTo a:link {color:#FFF;font-family:tahoma,helvetica,sans-serif;text-decoration:underline}
.backTo a:active {color:#FFF;font-family:tahoma,helvetica,sans-serif;text-decoration:underline}
.backTo a:hover {color:#DFDFDF;font-family:tahoma,helvetica,sans-serif;text-decoration:none}
.backToContainer {padding: 4px 0 4px 0}
.vuln_info {font-weight:bold;text-decoration:underline}
.scan_time {font-weight:bold;text-decoration:underline}
.host_info {font-weight:bold;text-decoration:underline}
.plugin_sev_low {background-color:#397AB2}
.plugin_sev_med {background-color:#FDBE00}
.plugin_sev_high {background-color:red}
.plugin_label {color:#FFF;font-weight:bold;padding:3px}
.port_header {background-color:#67889f}
.port_header_label {font-weight:bold;color:#FFF;padding: 3px}
.toggle {color: #FFF}
.divider {padding-top: 2px}
.info_text {padding-left: 8px;}
.default_header {background-color:#67889f}
.info_bg {background-color:#EEF2F3; }
.plugin_output {
width: 600px;
overflow: auto;
white-space: -moz-pre-wrap; /* Mozilla */
white-space: -hp-pre-wrap; /* HP printers */
white-space: -o-pre-wrap; /* Opera 7 */
white-space: -pre-wrap; /* Opera 4-6 */
white-space: pre-wrap; /* CSS 2.1 */
white-space: pre-line; /* CSS 3 (and 2.1 as well, actually) */
word-wrap: break-word; /* IE */
}
</style> 
<script type="text/javascript"> 
function toggle(divId)
{
	var divObj = document.getElementById(divId);
 
	if (divObj) {
		var displayType = divObj.style.display;
		if (displayType == "" || displayType == "block") {
			divObj.style.display = "none";
		} else {
			divObj.style.display = "block";
		}	
	}
}
</script> 
</head> 
<body> 
 

<!-- TABLE OF CONTENTS / List of hosts --> 

<a name="toc"></a> 
<table width="70%" align="center" border="0" cellspacing="0" cellpadding="0"> 
	<tr class="default_header"><td align="left" class="hostlist">List of hosts</td></tr> 
	<xsl:for-each select="NessusClientData_v2/Report/ReportHost">
		<xsl:sort select="@name"/>
	    <xsl:call-template name="list_of_hosts" />
	</xsl:for-each>
</table> 
 
 
<xsl:for-each select="NessusClientData_v2/Report/ReportHost">
	<xsl:sort select="@name"/>
	
	<!-- HOST ENTRY -->
	<xsl:call-template name="host_summary" />

	<!-- BACK TO LINK --> 
	<div class="backToContainer"> 
	<table width="70%" align="center" border="0" cellspacing="0" cellpadding="0"> 
	<tr>
		<td class="backTo" align="right">
			<xsl:element name="a">
				<xsl:attribute name="href">
					<xsl:value-of select="concat('#toc_',@name)"/>
				</xsl:attribute>
				<xsl:value-of select="concat('[^] Back to ', @name)"/>
			</xsl:element>
		</td>
	</tr><xsl:text>&#xA;</xsl:text>
	</table><xsl:text>&#xA;</xsl:text> 
	</div><xsl:text>&#xA;</xsl:text> 
 
	<!-- Host Details -->
	<xsl:call-template name="host_details" />


	<!-- BACK TO LINK --> 
	<div class="backToContainer"> 
	<table width="70%" align="center" border="0" cellspacing="0" cellpadding="0"> 
	<tr>
		<td class="backTo" align="right">
			<xsl:element name="a">
				<xsl:attribute name="href">
					<xsl:value-of select="concat('#toc_',@name)"/>
				</xsl:attribute>
				<xsl:value-of select="concat('[^] Back to ', @name)"/>
			</xsl:element>
		</td>
	</tr><xsl:text>&#xA;</xsl:text>
	</table><xsl:text>&#xA;</xsl:text> 
	</div><xsl:text>&#xA;</xsl:text>


 </xsl:for-each>



</body> 
</html> 
	
</xsl:template>


<!-- List of Hosts -->
<xsl:template name="list_of_hosts" match="ReportHost">
	<tr><td> 
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<xsl:choose>
			<xsl:when test="position() mod 2 = 1">  
				<tr class="even"> 
					<xsl:call-template name="identify_severity"/>					
				</tr><xsl:text>&#xA;</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<tr class="odd"> 
					<xsl:call-template name="identify_severity"/>					
				</tr><xsl:text>&#xA;</xsl:text>
			</xsl:otherwise>
		</xsl:choose> 
	</table><xsl:text>&#xA;</xsl:text> 
	</td></tr><xsl:text>&#xA;</xsl:text> 
</xsl:template>


<!-- Serverity Identification in List of Hosts -->
<xsl:template name="identify_severity" match="ReportHost">
	<td width="60%">
	    <xsl:element name="a">
			<xsl:attribute name="href">
				<xsl:value-of select="concat('#toc_',@name)"/>
			</xsl:attribute>
			<xsl:value-of select="@name"/>
		</xsl:element>
	</td> 					
	<xsl:choose>
		<xsl:when test="count(ReportItem[@severity=3]) > 0">
			<td width="40%" align="right" class="sev_high">High Severity problem(s) found</td>
		</xsl:when>
		<xsl:when test="count(ReportItem[@severity=2]) > 0">
			<td width="40%" align="right" class="sev_med">Medium Severity problem(s) found</td>
		</xsl:when>
		<xsl:when test="count(ReportItem[@severity=1]) > 0">
			<td width="40%" align="right" class="sev_low">Low Severity problem(s) found</td>
		</xsl:when>
	</xsl:choose>

</xsl:template>


<!-- Host Summary -->
<xsl:template name="host_summary" match="ReportHost">
	<xsl:element name="a">
		<xsl:attribute name="name">
			<xsl:value-of select="concat('toc_',@name )"/>
		</xsl:attribute>
	</xsl:element>	 

	<!-- BACK TO LINK --> 
	<div class="backToContainer"> 
	<table width="70%" align="center" border="0" cellspacing="0" cellpadding="0"> 
	<tr><td class="backTo" align="right"><a href="#toc">[^] Back</a></td></tr><xsl:text>&#xA;</xsl:text> 
	</table><xsl:text>&#xA;</xsl:text> 
	</div><xsl:text>&#xA;</xsl:text> 

	<table width="70%" align="center" border="0" cellspacing="0" cellpadding="0"> 
		<tr class="default_header"> 
			<td align="left" class="ip_sev_med"><xsl:value-of select="@name"/></td> 
		</tr><xsl:text>&#xA;</xsl:text> 
		<tr class="info_bg"><td> 
		<table width="100%" border="0" cellspacing="0" cellpadding="2"> 
			<tr><td>
				<!-- SCAN TIME --> 
				<xsl:call-template name="host_summary_scan_time" />
			</td></tr><xsl:text>&#xA;</xsl:text> 
 
			<tr><td colspan="2"><hr/></td></tr> 
 	
			<!-- VULNERABILITIES -->
				<xsl:call-template name="host_summary_vulnerabilities" />

			<tr><td colspan="2"><hr/></td></tr><xsl:text>&#xA;</xsl:text> 
 			
			<!-- REMOTE HOST INFORMATION --> 
			<tr><td> 
				<xsl:call-template name="host_summary_remote_host_info" />
			</td></tr><xsl:text>&#xA;</xsl:text> 
		</table><xsl:text>&#xA;</xsl:text> 
		</td></tr><xsl:text>&#xA;</xsl:text> 
	</table><xsl:text>&#xA;</xsl:text> 

</xsl:template>


<!-- SCAN TIME --> 
<xsl:template name="host_summary_scan_time" match="ReportHost" >
	<span class="scan_time">Scan Time</span><br/> 
	<table width="60%" border="0" align="center"> 
		<tr> 
		<xsl:for-each select="HostProperties/tag">
			<xsl:if test=" @name ='HOST_START' ">
				<td align="left">Start time : </td>
				<td align="right"><xsl:value-of select="."/></td>  
			</xsl:if>
		</xsl:for-each>											
		</tr><xsl:text>&#xA;</xsl:text> 
		<tr> 
		<xsl:for-each select="HostProperties/tag">
			<xsl:if test=" @name ='HOST_END'">
				<td align="left">End time : </td>
				<td align="right"><xsl:value-of select="."/></td>  
			</xsl:if>
		</xsl:for-each>											
		</tr><xsl:text>&#xA;</xsl:text> 
	</table><xsl:text>&#xA;</xsl:text> 

</xsl:template>


<!-- Vulnerabilities -->
<xsl:template name="host_summary_vulnerabilities" match="ReportHost">
	<tr><td> 
		<span class="vuln_info">Number of vulnerabilities</span><br/> 
		
		<table width="60%" border="0" align="center"> 
			<tr> 
				<td align="left">Open ports : </td> 
				<td align="right"><xsl:value-of select="count(ReportItem[@severity='0'])"/></td> 
			</tr><xsl:text>&#xA;</xsl:text> 
			<tr> 
				<td align="left" class="sev_high">High : </td> 
				<td align="right" class="sev_high"><xsl:value-of select="count(ReportItem[@severity='3'])"/></td> 
			</tr><xsl:text>&#xA;</xsl:text> 
			<tr> 
				<td align="left" class="sev_med">Medium : </td> 
				<td align="right" class="sev_med"><xsl:value-of select="count(ReportItem[@severity='2'])"/></td> 
			</tr><xsl:text>&#xA;</xsl:text> 
			<tr> 
				<td align="left" class="sev_low">Low : </td> 
				<td align="right" class="sev_low"><xsl:value-of select="count(ReportItem[@severity='1'])"/></td> 
			</tr><xsl:text>&#xA;</xsl:text> 
	</table><xsl:text>&#xA;</xsl:text> 
	</td></tr><xsl:text>&#xA;</xsl:text>  

</xsl:template>


<!-- Remote Host Info -->
<xsl:template name="host_summary_remote_host_info" match="ReportHost">
	<span class="host_info">Remote host information</span><br/> 
	<table width="60%" border="0" align="center"> 
		<tr> 
			<td align="left">Operating System : </td>
			<xsl:for-each select="HostProperties/tag">
				<xsl:choose>
				<xsl:when test="@name = 'operating-system'">
					<td align="right"><xsl:value-of select="."/></td>  
				</xsl:when>
				</xsl:choose>
			</xsl:for-each>
		</tr><xsl:text>&#xA;</xsl:text>
		<tr>												
			<td align="left">NetBIOS name : </td> 
			<xsl:for-each select="HostProperties/tag">
				<xsl:choose>
				<xsl:when test="@name = 'netbios-name'">
					<td align="right"><xsl:value-of select="."/></td>  
				</xsl:when>
				</xsl:choose>
			</xsl:for-each>
		</tr><xsl:text>&#xA;</xsl:text> 
		<tr> 
			<td align="left">DNS name : </td> 
			<xsl:for-each select="HostProperties/tag">
				<xsl:choose>
				<xsl:when test="@name = 'host-fqdn' ">
					<td align="right"><xsl:value-of select="."/></td>  
				</xsl:when>
				</xsl:choose>
			</xsl:for-each>
		</tr><xsl:text>&#xA;</xsl:text> 
	</table><xsl:text>&#xA;</xsl:text> 
</xsl:template>





<xsl:template name="processUniqueValues" match="ReportHost">
	<xsl:param name="delimitedPorts"/>
	<xsl:variable name="firstOne">
		<!-- variable firstOne: the first value in the delimited list of-->
		<xsl:value-of select="normalize-space(substring-before($delimitedPorts,'~'))"/>
	</xsl:variable>
	<xsl:variable name="firstOneDelimited">
		<!-- variable firstOneDelimited: the first value in the delimitedof items with the tilde "~" delimiter -->
		<xsl:value-of select="substring-before($delimitedPorts,'~')"/>~
	</xsl:variable>
	<xsl:variable name="theRest">
		<!-- variable theRest: the rest of the delimited list after theone is removed -->
		<xsl:value-of select="substring-after($delimitedPorts,'~')"/>
	</xsl:variable>
	
	
	<xsl:choose>
		<!-- when the current one exists again in the remaining list ANDfirst one isn't empty, -->
		<xsl:when test="contains($theRest,$firstOneDelimited) and not($firstOne='')">
			<xsl:call-template name="processUniqueValues" select=".">
				<xsl:with-param name="delimitedPorts">
					<xsl:value-of select="$theRest"/>
				</xsl:with-param>
			</xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
			<!-- otherwise this is the last occurence in the list, so returnitem with a delimiter tilde "~". -->
			<xsl:text>
			</xsl:text>

			
			
			<xsl:for-each select="ReportItem[@port = $firstOne]">
				<xsl:if test="position()=1">
					<br/>
					<xsl:variable name="host_name" select="parent::node()/@name"/>
					<xsl:variable name="port_name" select="@port"/>
					<xsl:variable name="service_name" select="@svc_name"/>
					
					<xsl:element name="a">
						<xsl:attribute name="name">
							<xsl:value-of select="concat( parent::node()/@name,'_',@svc_name, '(', @port , '/', @svc_name , ')' )"/>
						</xsl:attribute>	
					</xsl:element>
					<table width="70%" align="center" border="0" cellspacing="0" cellpadding="2"> 
						<xsl:element name="tr">
							<xsl:attribute name="class">port_header</xsl:attribute>
							<xsl:attribute name="onclick">
								<xsl:value-of select="concat('toggle(&quot;',parent::node()/@name,'_',@svc_name, '_' , @port, '&quot;)' )"/>
							</xsl:attribute>
							<xsl:attribute name="onmouseover">this.style.cursor='pointer'</xsl:attribute>
							<xsl:attribute name="title">Collapse/Expand</xsl:attribute>				
							<xsl:element name="td">
								<xsl:attribute name="align">left</xsl:attribute>
								<xsl:attribute name="class">port_header_label</xsl:attribute>
								<xsl:value-of select="concat('Port ', @svc_name, ' (', @port, '/', @protocol,')' )"/>
							</xsl:element>
							<td align="right" class="toggle">[-/+]</td> 
						</xsl:element>
					</table><xsl:text>&#xA;</xsl:text> 
					<xsl:element name="div">
						<xsl:attribute name="id">
							<xsl:value-of select="concat( $host_name,'_',$service_name, '_', $port_name)"/>
						</xsl:attribute>	
						<xsl:attribute name="class">divider</xsl:attribute>
							<xsl:for-each select="../ReportItem[@port = $firstOne]">
								<xsl:sort select="@severity" order="descending"/>
								<xsl:call-template name="plugin_header"/>	
													
								<xsl:if test="position() != last()">
									<div class="divider"><xsl:text>&#xA;</xsl:text></div>
								</xsl:if>
							</xsl:for-each>
					</xsl:element>
				</xsl:if>
			</xsl:for-each>
			
			
			<xsl:if test="contains($theRest,'~')">
				<!-- when there are more left in the delimited list, call thewith the remaining items -->
				<xsl:call-template name="processUniqueValues" select=".">
							<xsl:with-param name="delimitedPorts">
							 <xsl:value-of select="$theRest"/>
							</xsl:with-param>
				</xsl:call-template>
			</xsl:if>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>






<xsl:template name="host_details" match="ReportHost">
	<!-- Create delimited list of ports -->
	<xsl:variable name="delimitedPorts">
		<xsl:for-each select="ReportItem">
			<xsl:sort select="@port"/>
			<xsl:value-of select="@port"/>~
		</xsl:for-each>
    </xsl:variable>
	
	<!-- Pass delimited ports list to processUniqueValues template -->
	<xsl:call-template name="processUniqueValues" select=".">
		<xsl:with-param name="delimitedPorts">
			<xsl:value-of select="$delimitedPorts"/>
		</xsl:with-param>
	</xsl:call-template>
</xsl:template>
	





<xsl:template name="plugin_header" match="ReportItem">
	<xsl:if test="@severity!=0">
		<table width="70%" align="center" border="0" cellspacing="0" cellpadding="2"> 
			<xsl:choose>
				<xsl:when test="@severity=1"> 
					<tr class="plugin_sev_low"> 
						<td align="left" class="plugin_label"><xsl:value-of select="@pluginName" /></td> 
					</tr><xsl:text>&#xA;</xsl:text>
				</xsl:when>
				<xsl:when test="@severity=2"> 
					<tr class="plugin_sev_med"> 
						<td align="left" class="plugin_label"><xsl:value-of select="@pluginName" /></td> 
					</tr><xsl:text>&#xA;</xsl:text>
				</xsl:when>
				<xsl:when test="@severity=3"> 
					<tr class="plugin_sev_high"> 
						<td align="left" class="plugin_label"><xsl:value-of select="@pluginName" /></td> 
					</tr><xsl:text>&#xA;</xsl:text>
				</xsl:when>
			</xsl:choose>	 
			<tr class="info_bg"> 
				<td colspan="2" class="info_text">
					<div class="plugin_output">
					<xsl:for-each select="synopsis">
					<br/><b>Synopsis:</b><br/>
						<xsl:call-template name="subs-newline">
	                    <xsl:with-param name="word" select="."/>
	                    </xsl:call-template>
					</xsl:for-each> 
				
					<xsl:if test="count(synopsis) = 1">
						<br/><br/><b>Description:</b><br/>
					</xsl:if>
				
					<xsl:for-each select="description">
					    <xsl:call-template name="subs-newline">
	                    <xsl:with-param name="word" select="."/>
	                    </xsl:call-template>
					</xsl:for-each>
					<xsl:for-each select="risk_factor">
					<br/><br/><b>Risk factor:</b><br/>
						<xsl:call-template name="subs-newline">
	                    <xsl:with-param name="word" select="."/>
	                    </xsl:call-template>
					</xsl:for-each>
					<xsl:for-each select="cvss_base_score">
					<br/><br/><b>CVSS Base Score:</b>
						<xsl:value-of select="."/>					
					</xsl:for-each>
					<xsl:for-each select="cvss_vector">
					<br/>
						<xsl:value-of select="."/>					
					</xsl:for-each>
					<xsl:for-each select="see_also">
					<br/><br/><b>See also:</b><br/>
						<xsl:call-template name="subs-newline">
	                    <xsl:with-param name="word" select="."/>
	                    </xsl:call-template>
					</xsl:for-each>
					<xsl:for-each select="solution">
					<br/><br/><b>Solution:</b><br/>
						<xsl:call-template name="subs-newline">
	                    <xsl:with-param name="word" select="."/>
	                    </xsl:call-template>
					</xsl:for-each>
					<xsl:for-each select="plugin_output">
					<br/><br/><b>Plugin output:</b><br/>
						<xsl:call-template name="subs-newline">
	                    <xsl:with-param name="word" select="."/>
	                    </xsl:call-template>
					</xsl:for-each>
					
					<br/><br/><b>Plugin ID:</b><br/>
					<xsl:element name="a">
						<xsl:attribute name="href">
							<xsl:value-of select="concat( 'http://www.nessus.org/plugins/index.php?view=single&amp;id=' , @pluginID )"/>
						</xsl:attribute>
						<xsl:value-of select="@pluginID"/>
					</xsl:element>
					
					<xsl:if test="count(cve) > 0">
						<br/><br/><b>CVE: </b><br/>
					</xsl:if>

					<xsl:for-each select="cve">
						<xsl:value-of select="."/>
						<xsl:if test="position() != last()">, </xsl:if>
					</xsl:for-each>
					<xsl:if test="count(bid) > 0">
						<br/><br/><b>BID: </b><br/>
					</xsl:if>
					<xsl:for-each select="bid">
						<xsl:element name="a">
							<xsl:attribute name="href">
								<xsl:value-of select="concat( 'http://www.securityfocus.com/bid/' , . )"/>
							</xsl:attribute>
							 <xsl:value-of select="."/>
						</xsl:element>
						<xsl:if test="position() != last()">, </xsl:if>
					</xsl:for-each>
					<xsl:if test="count(xref) > 0">
						<br/><br/><b>Other references: </b><br/>
					</xsl:if>
					<xsl:for-each select="xref">
						<xsl:value-of select="."/>
						<xsl:if test="position() != last()">, </xsl:if>
					</xsl:for-each>
					</div><xsl:text>&#xA;</xsl:text>
				</td> 
			</tr><xsl:text>&#xA;</xsl:text> 
		</table><xsl:text>&#xA;</xsl:text>
	</xsl:if>

</xsl:template>

<!-- recursive template to substitute newline -->
<xsl:template name="subs-newline">
   <xsl:param name="word"/>
   <!--<xsl:variable name="cr"><xsl:text>&#xA;</xsl:text></xsl:variable>
   <xsl:choose>
   <xsl:when test="contains($word,$cr)">
       <xsl:value-of select="substring-before($word,$cr)"/>
       <br/>
         <xsl:call-template name="subs-newline">
         <xsl:with-param name="word" 
             select="substring-after($word,$cr)"/>
       </xsl:call-template>
   </xsl:when>
   <xsl:otherwise>
     --><xsl:value-of select="translate($word,'&#xA;','&#xD;')"/>
   <!--</xsl:otherwise>
  </xsl:choose>-->
</xsl:template>

</xsl:stylesheet>
