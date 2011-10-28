<?xml version="1.0" encoding="utf-8"?>
<!--Copyright 2003-2008(C) Tenable Network Security-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:output method="html" indent="yes" />
  <xsl:key name="portID" match="Report/ReportHost/*" use="concat(../HostName,'',port)"></xsl:key>
  <xsl:template name="support_formats"><![CDATA[html]]></xsl:template>
  <xsl:template name="report_sub_header2">
    <xsl:param name="title" />
    <tr>
      <td width="100%" colspan="2">
        <table width="100%" class="report_table_title" cellspacing="0" cellpadding="0">
          <tr>
            <td align="right">
              <a>
                <xsl:attribute name="name">
                  <xsl:value-of select="$title"></xsl:value-of>
                </xsl:attribute>
                <xsl:value-of select="$title" />
              </a>
            </td>
            <td align="right">
              <a href="#top">[Return to top]</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </xsl:template>
  <xsl:template name="report_showalert">
    <xsl:param name="donot_print_id" />
    <tr bgcolor="#eef2f3">
      <td>
        <table width="100%" height="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td>
              <div class="desc_text">
                <xsl:value-of disable-output-escaping="yes" select="data" />
                <xsl:if test="not($donot_print_id='yes')">
                  <br />
                  <xsl:text>Nessus ID : </xsl:text>
                  <a target="_blank">
                    <xsl:attribute name="href"><![CDATA[http://www.nessus.org/plugins/index.php?view=single&id=]]><xsl:value-of select="pluginID" /></xsl:attribute>
                    <xsl:value-of select="pluginID" />
                  </a>
                  <br />
                </xsl:if>
                <br />
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </xsl:template>
  <xsl:template name="report_html_head">
    <title>Nessus Scan Report</title>
    <style type="text/css"><![CDATA[<!--

BODY {BACKGROUND-COLOR: #ffffff }

A { TEXT-DECORATION: none }

A {COLOR: #333333; FONT-FAMILY: tahoma,helvetica,sans-serif }

            A:link {COLOR: #333333; FONT-FAMILY: tahoma,helvetica,sans-serif; TEXT-DECORATION:underline }

	    A:active {COLOR: #333333; FONT-FAMILY: tahoma,helvetica,sans-serif; TEXT-DECORATION:underline }

P {COLOR: #333333; FONT-FAMILY: tahoma,helvetica,sans-serif;  FONT-SIZE:8pt}

TD {	COLOR: #333333; FONT-FAMILY: tahoma,helvetica,sans-serif; FONT-SIZE:8pt }

TR {	COLOR: #333333; FONT-FAMILY: tahoma,helvetica,sans-serif; FONT-SIZE:8pt }

!-->]]></style>
  </xsl:template>
  <xsl:template match="Targets"></xsl:template>
  <!--Host Summary-->
  <xsl:template name="host_summary">
    <tr>
      <td>
        <table width="100%" bgcolor="#eef2f3">
          <tr>
            <td>
              <a>
                <xsl:attribute name="name">
                  <xsl:value-of select="../ReportName" />
                  <xsl:value-of select="HostName" />
                </xsl:attribute>
              </a>
              <xsl:choose>
                <xsl:when test="number(num_hi) &gt; 0">
                  <table width="100%">
                    <tr bgcolor="#ff0000">
                      <td class="hi_alert_head" align="left">
                        <b>
                          <font color="#ffffff" size="+2">
                            <xsl:value-of select="HostName" />
                          </font>
                        </b>
                      </td>
                    </tr>
                  </table>
                </xsl:when>
                <xsl:when test="number(num_med) &gt; 0">
                  <table width="100%">
                    <tr bgcolor="#fdbe00">
                      <td align="left">
                        <b>
                          <font color="#ffffff" size="+2">
                            <xsl:value-of select="HostName" />
                          </font>
                        </b>
                      </td>
                    </tr>
                  </table>
                </xsl:when>
                <xsl:when test="number(ReportHost/num_lo) &gt; 0">
                  <table width="100%">
                    <tr bgcolor="#000080">
                      <td class="lo_alert_head" align="left">
                        <b>
                          <font color="#ffffff" size="+2">
                            <xsl:value-of select="HostName" />
                          </font>
                        </b>
                      </td>
                    </tr>
                  </table>
                </xsl:when>
                <xsl:otherwise>
                  <table width="100%">
                    <tr bgcolor="#000080">
                      <td class="lo_alert_head" align="left">
                        <b>
                          <font color="#ffffff" size="+2">
                            <xsl:value-of select="HostName" />
                          </font>
                        </b>
                      </td>
                    </tr>
                  </table>
                </xsl:otherwise>
              </xsl:choose>
            </td>
          </tr>
          <tr>
            <td>
              <table width="100%">
                <tr>
                  <td height="15" />
                </tr>
                <tr>
                  <td>
                    <u><![CDATA[Scan Time:]]></u>
                    <br />
                    <table width="80%">
                      <tr>
                        <td align="right"><![CDATA[Start Time:]]></td>
                        <td align="right">
                          <xsl:value-of select="startTime" />
                        </td>
                      </tr>
                      <tr>
                        <td align="right"><![CDATA[End Time:]]></td>
                        <td align="right">
                          <xsl:value-of select="stopTime" />
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td>
                    <u><![CDATA[Number of vulnerabilities :]]></u>
                    <br />
                    <table width="80%">
                      <tr>
                        <td align="right"><![CDATA[Open Ports:]]></td>
                        <td align="right">
                          <xsl:value-of select="num_ports" />
                        </td>
                      </tr>
                      <tr>
                        <td align="right"><![CDATA[Low:]]></td>
                        <td align="right">
                          <xsl:value-of select="num_lo" />
                        </td>
                      </tr>
                      <tr>
                        <td align="right"><![CDATA[Medium:]]></td>
                        <td align="right">
                          <xsl:value-of select="num_med" />
                        </td>
                      </tr>
                      <tr>
                        <td align="right"><![CDATA[High:]]></td>
                        <td align="right">
                          <xsl:value-of select="num_hi" />
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td>
                    <hr></hr>
                    <u><![CDATA[Information about the remote host :]]></u>
                    <br />
                    <table width="100%">
                      <tr>
                        <td align="right"><![CDATA[Operating System:]]></td>
                        <td align="right">
                          <xsl:value-of select="os_name" />
                        </td>
                      </tr>
                      <tr>
                        <td align="right"><![CDATA[NetBIOS Name:]]></td>
                        <td align="right">
                          <xsl:value-of select="netbios_name" />
                        </td>
                      </tr>
                      <tr>
                        <td align="right"><![CDATA[DNS Name:]]></td>
                        <td align="right">
                          <xsl:value-of select="dns_name" />
                        </td>
                      </tr>
                      <tr>
                        <td height="15" />
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </xsl:template>
  <!--Scan Summary-->
  <xsl:template name="scan_summary">
    <tr>
      <td>
        <table width="100%" bgcolor="#eef2f3">
          <tr>
            <td>
              <a>
                <xsl:attribute name="name">
                  <xsl:value-of select="ReportName" />
                </xsl:attribute>
              </a>
              <xsl:choose>
                <xsl:when test="number(num_hi) &gt; 0">
                  <table>
                    <tr bgcolor="#ff0000">
                      <td class="hi_alert_head" align="left">
                        <b>
                          <font color="#ffffff" size="+2"><![CDATA[Report : ]]><xsl:value-of select="ReportName" /></font>
                        </b>
                      </td>
                    </tr>
                  </table>
                </xsl:when>
                <xsl:when test="number(num_med) &gt; 0">
                  <table width="100%">
                    <tr bgcolor="#fdbe00">
                      <td align="left">
                        <b>
                          <font color="#ffffff" size="+2"><![CDATA[Report : ]]><xsl:value-of select="ReportName" /></font>
                        </b>
                      </td>
                    </tr>
                  </table>
                </xsl:when>
                <xsl:when test="number(num_lo) &gt; 0">
                  <table width="100%">
                    <tr bgcolor="#000080">
                      <td class="lo_alert_head" align="left">
                        <b>
                          <font color="#ffffff" size="+2"><![CDATA[Report : ]]><xsl:value-of select="ReportName" /></font>
                        </b>
                      </td>
                    </tr>
                  </table>
                </xsl:when>
                <xsl:otherwise>
                  <table width="100%">
                    <tr bgcolor="#000080">
                      <td class="lo_alert_head" align="left">
                        <b>
                          <font color="#ffffff" size="+2"><![CDATA[Report : ]]><xsl:value-of select="ReportName" /></font>
                        </b>
                      </td>
                    </tr>
                  </table>
                </xsl:otherwise>
              </xsl:choose>
            </td>
          </tr>
          <tr>
            <td>
              <table width="100%">
                <tr>
                  <td height="15" />
                </tr>
                <tr>
                  <td>
                    <u><![CDATA[Scan Time:]]></u>
                    <br />
                    <table width="80%">
                      <tr>
                        <td align="right"><![CDATA[Start Time:]]></td>
                        <td align="right">
                          <xsl:value-of select="StartTime" />
                        </td>
                      </tr>
                      <tr>
                        <td align="right"><![CDATA[End Time:]]></td>
                        <td align="right">
                          <xsl:value-of select="StopTime" />
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td>
                    <u><![CDATA[PolicyUUID:]]></u>
                    <br />
                    <table width="80%">
                      <tr>
                        <td align="right">
                          <xsl:value-of select="PolicyUUID" />
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td height="15" />
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </xsl:template>
  <!--Report Summary-->
  <xsl:template name="Report_Summary">
    <xsl:param name="mode" />
    <xsl:if test="$mode='yes'">
      <tr>
        <td>
          <table width="100%" bgcolor="#eef2f3">
            <tr bgcolor="#397ab2">
              <td align="left">
                <b>
                  <font color="#ffffff" size="+2">List of Reports</font>
                </b>
              </td>
              <a>
                <xsl:attribute name="Top" />
              </a>
            </tr>
            <xsl:for-each select="Report">
              <tr>
                <td>
                  <a>
                    <xsl:attribute name="href"><![CDATA[#]]><xsl:value-of select="ReportName" /></xsl:attribute>
                    <u>
                      <xsl:value-of select="ReportName"></xsl:value-of>
                    </u>
                  </a>
                </td>
              </tr>
            </xsl:for-each>
          </table>
        </td>
      </tr>
    </xsl:if>
  </xsl:template>
  <!--List Hosts-->
  <xsl:template name="ListHost">
    <tr>
      <td>
        <table width="100%">
          <!--List hosts-->
          <tr bgcolor="#397ab2">
            <td align="left">
              <b>
                <font color="#ffffff" size="+2">List of hosts</font>
              </b>
            </td>
          </tr>
          <!--Alert level of each host-->
          <tr bgcolor="#eef2f3">
            <td>
              <center />
              <table width="100%">
                <xsl:for-each select="ReportHost">
                  <xsl:sort data-type="number" select="substring-before(HostName, '.')" />
                  <xsl:sort data-type="number" select="substring-before(substring-after(HostName,'.'),'.')"></xsl:sort>
                  <xsl:sort data-type="number" select="substring-before(substring-after(substring-after(HostName,'.'),'.'),'.')" />
                  <xsl:sort data-type="number" select="substring-after(substring-after(substring-after(HostName,'.'),'.'),'.')" />
                  <xsl:if test="number(num_ports+num_lo+num_med+num_hi)&gt;0">
                    <tr>
                      <td width="60%" class="report_host" align="left">
                        <a>
                          <xsl:attribute name="href"><![CDATA[#]]><xsl:value-of select="../ReportName" /><xsl:value-of select="HostName" /></xsl:attribute>
                          <u>
                            <xsl:value-of select="HostName" />
                          </u>
                        </a>
                      </td>
                      <td width="40%" class="report_host">
                        <xsl:choose>
                          <xsl:when test="number(num_hi) &gt; 0">
                            <b>
                              <font align="right" color="#ff0000">High severity problem(s) found!</font>
                            </b>
                          </xsl:when>
                          <xsl:when test="number(num_med) &gt; 0 and number(num_hi)=0">
                            <b>
                              <font align="right" color="#fdbe00">Medium severity problem(s) found!</font>
                            </b>
                          </xsl:when>
                          <xsl:when test="number(num_lo) &gt; 0 and number(num_med)=0 and number(num_hi)=0">
                            <b>
                              <font align="right" color="#397AB2">Low severity problem(s) found!</font>
                            </b>
                          </xsl:when>
                          <xsl:otherwise>
                            <b>
                              <font align="right" color="#000000">No problem found!</font>
                            </b>
                          </xsl:otherwise>
                        </xsl:choose>
                      </td>
                    </tr>
                  </xsl:if>
                </xsl:for-each>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </xsl:template>
  <!--List Ports-->
  <xsl:template name="ListPort">
    <tr>
      <td>
        <table width="100%">
          <!--List ports-->
          <tr bgcolor="#397ab2">
            <td align="left">
              <b>
                <font color="#ffffff" size="+2">List of ports</font>
              </b>
            </td>
          </tr>
          <!--Alert level of each host-->
          <tr bgcolor="#eef2f3">
            <td>
              <center />
              <table width="100%">
                <xsl:for-each select="ReportItem[generate-id(.)=generate-id(key('portID', concat(../HostName,'',port)))]">
                  <xsl:sort data-type="number" select="translate(substring-after(port,'('),translate(substring-after(port,'('),'0123456789',''),'')" />
                  <tr>
                    <td width="60%" align="left">
                      <a>
                        <xsl:attribute name="href" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><![CDATA[#]]><xsl:value-of select="concat(../HostName,'',port)" /></xsl:attribute>
                        <u>
                          <xsl:value-of select="port" />
                        </u>
                      </a>
                    </td>
                    <td width="40%" align="left">
                      <xsl:for-each select="key('portID', concat(../HostName,'',port))">
                        <xsl:sort data-type="number" select="number(severity)" />
                        <xsl:if test="position() = last()">
                          <xsl:variable name="alert">
                            <xsl:value-of select="severity" />
                          </xsl:variable>
                          <xsl:choose>
                            <xsl:when test="number($alert) &gt; 2">
                              <table>
                                <tr>
                                  <td align="right">
                                    <b>
                                      <font color="#ff0000"><![CDATA[High vulnerability problem(s) found]]></font>
                                    </b>
                                  </td>
                                </tr>
                              </table>
                            </xsl:when>
                            <xsl:when test="number($alert) &gt; 1">
                              <table>
                                <tr>
                                  <td align="right">
                                    <b>
                                      <font color="#fdbe00"><![CDATA[Medium vulnerability problem(s) found]]></font>
                                    </b>
                                  </td>
                                </tr>
                              </table>
                            </xsl:when>
                            <xsl:when test="number(severity) &gt; 0">
                              <table>
                                <tr>
                                  <td align="right">
                                    <b>
                                      <font color="#397AB2"><![CDATA[Low vulnerability problem(s) found]]></font>
                                    </b>
                                  </td>
                                </tr>
                              </table>
                            </xsl:when>
                            <xsl:otherwise>
                              <table>
                                <tr>
                                  <td align="right">
                                    <b>
                                      <font color="#397AB2"><![CDATA[No problem found]]></font>
                                    </b>
                                  </td>
                                </tr>
                              </table>
                            </xsl:otherwise>
                          </xsl:choose>
                        </xsl:if>
                      </xsl:for-each>
                    </td>
                  </tr>
                </xsl:for-each>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </xsl:template>
  <xsl:template match="NessusClientData">
    <xsl:call-template name="report_html_head"></xsl:call-template>
    <center>
      <table width="755">
        <xsl:call-template name="Report_Summary">
          <xsl:with-param name="mode" select="'html'" />
        </xsl:call-template>
        <!--Space-->
        <tr>
          <td height="15" />
        </tr>
        <tr>
          <td>
            <table width="100%">
              <xsl:for-each select="Report">
                <!--Scan Summary-->
                <xsl:call-template name="scan_summary"></xsl:call-template>
                <!--List Hosts-->
                <xsl:call-template name="ListHost" />
                <tr width="100%">
                  <td align="right">
                    <a>
                      <xsl:attribute name="name">
                        <xsl:value-of select="../ReportName" />
                        <xsl:value-of select="ReportHost/HostName" />
                      </xsl:attribute>
                    </a>
                    <a>
                      <xsl:attribute name="href"><![CDATA[#top]]></xsl:attribute><![CDATA[[^] Back]]></a>
                  </td>
                </tr>
                <!--Space-->
                <tr>
                  <td align="right" height="40"></td>
                </tr>
                <xsl:for-each select="ReportHost">
                  <xsl:sort data-type="number" select="substring-before(HostName, '.')" />
                  <xsl:sort data-type="number" select="substring-before(substring-after(HostName,'.'),'.')"></xsl:sort>
                  <xsl:sort data-type="number" select="substring-before(substring-after(substring-after(HostName,'.'),'.'),'.')" />
                  <xsl:sort data-type="number" select="substring-after(substring-after(substring-after(HostName,'.'),'.'),'.')" />
                  <xsl:variable name="hostname">
                    <xsl:value-of select="HostName" />
                  </xsl:variable>
                  <xsl:if test="number(num_ports+num_lo+num_med+num_hi)&gt;0">
                    <tr>
                      <td align="right">
                        <a href="#top">[Return to top]</a>
                      </td>
                    </tr>
                    <xsl:call-template name="host_summary" />
                    <xsl:call-template name="ListPort" />
                    <tr>
                      <td>
                        <xsl:for-each select="ReportItem[generate-id(.)=generate-id(key('portID', concat($hostname,'',port)))]">
                          <xsl:sort data-type="number" select="translate(substring-after(port,'('),translate(substring-after(port,'('),'0123456789',''),'')" />
                          <tr width="100%">
                            <td align="right">
                              <a>
                                <xsl:attribute name="href"><![CDATA[#]]><xsl:value-of select="../../ReportName" /><xsl:value-of select="$hostname" /></xsl:attribute>[^]Back<![CDATA[ to ]]><xsl:value-of select="$hostname"></xsl:value-of></a>
                            </td>
                          </tr>
                          <tr bgcolor="#0f346c">
                            <td>
                              <a>
                                <xsl:attribute name="name">
                                  <xsl:value-of select="$hostname" />
                                  <xsl:value-of select="port" />
                                </xsl:attribute>
                              </a>
                              <b>
                                <font color="#ffffff">
                                  <xsl:value-of select="port" />
                                </font>
                              </b>
                            </td>
                          </tr>
                          <xsl:for-each select="key('portID',concat($hostname,'',port))">
                            <xsl:sort data-type="number" select="pluginID" />
                            <xsl:choose>
                              <xsl:when test="pluginID[number(.) &gt; 0]">
                                <xsl:choose>
                                  <xsl:when test="number(severity) &gt; 2">
                                    <tr bgcolor="#ff0000">
                                      <td align="left">
                                        <b>
                                          <font color="#ffffff">
                                            <xsl:value-of select="pluginName" />
                                          </font>
                                        </b>
                                      </td>
                                    </tr>
                                  </xsl:when>
                                  <xsl:when test="number(severity) &gt; 1">
                                    <tr bgcolor="#fdbe00">
                                      <td align="left">
                                        <b>
                                          <font color="#ffffff">
                                            <xsl:value-of select="pluginName" />
                                          </font>
                                        </b>
                                      </td>
                                    </tr>
                                  </xsl:when>
                                  <xsl:when test="number(severity) &gt; 0">
                                    <tr bgcolor="#397AB2">
                                      <td align="left">
                                        <b>
                                          <font color="#ffffff">
                                            <xsl:value-of select="pluginName" />
                                          </font>
                                        </b>
                                      </td>
                                    </tr>
                                  </xsl:when>
                                  <xsl:otherwise>
                                    <tr bgcolor="#397AB2">
                                      <td align="left">
                                        <b>
                                          <font color="#ffffff">
                                            <xsl:value-of select="pluginName" />
                                          </font>
                                        </b>
                                      </td>
                                    </tr>
                                  </xsl:otherwise>
                                </xsl:choose>
                                <xsl:call-template name="report_showalert" />
                                <tr>
                                  <td height="1"></td>
                                </tr>
                              </xsl:when>
                              <xsl:otherwise></xsl:otherwise>
                            </xsl:choose>
                          </xsl:for-each>
                          <tr>
                            <td height="5"></td>
                          </tr>
                        </xsl:for-each>
                      </td>
                    </tr>
                  </xsl:if>
                </xsl:for-each>
              </xsl:for-each>
            </table>
          </td>
        </tr>
      </table>
    </center>
  </xsl:template>
</xsl:stylesheet>
