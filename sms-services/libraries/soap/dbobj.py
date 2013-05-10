#!/usr/bin/python
import re

class DbObj:
  def __init__(self,host,user,passwd,db):
    self.host = host
    self.user = user
    self.passwd = passwd
    self.db = db
    self.rows = {}
    return
  def printRows(self):
    for key in self.rows.keys():
      print ("%s" % (self.rows[key])),
    return
  def printCol(self, col):
    for rowKey in self.rows.keys():
      for colKey in self.rows[rowKey]:
        m = re.compile(r"(.*?)%s(.*?)"%col, re.IGNORECASE).match(str(colKey), 0)
        if (m is None) or (str(m.group(0)) != col):
          continue
        if (m is not None) and (str(m.group(0)) == col):
          print("")
          print "%s %s %s "%(str(rowKey), colKey.strip(), str(self.rows[rowKey][colKey])),
          break
    return
