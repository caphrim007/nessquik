<?xml version="1.0" encoding="utf-8"?>
<!--Copyright 2003-2008(C) Tenable Network Security-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:output method="html" indent="yes" />
  <xsl:key name="portID" match="Report/ReportHost/*" use="concat(../HostName,'',port)"></xsl:key>
  <xsl:template name="support_formats"><![CDATA[html]]></xsl:template>
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
            <td align="right">
              <a>
                <xsl:attribute name="href" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><![CDATA[#top]]></xsl:attribute><![CDATA[[^] Back]]></a>
            </td>
          </tr>
          <tr>
            <td bgcolor="#000080">
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
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </xsl:template>
  <!--List Host-->
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
                            <font align="right" color="#ff0000">High severity problem(s) found!</font>
                          </xsl:when>
                          <xsl:when test="number(num_med) &gt; 0">
                            <font align="right" color="#fdbe00">Medium severity problem(s) found</font>
                          </xsl:when>
                          <xsl:when test="number(num_lo) &gt; 0">
                            <font align="right" color="#000000">Low severity problem(s) found!</font>
                          </xsl:when>
                          <xsl:otherwise>
                            <font align="right" color="#000000">No problem found!</font>
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
  <xsl:template match="NessusClientData">
    <xsl:call-template name="report_html_head"></xsl:call-template>
    <center>
      <table width="755">
        <tr>
          <td>
            <table width="100%">
              <xsl:for-each select="Report">
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
                    <xsl:call-template name="host_summary" />
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
